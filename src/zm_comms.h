//
// ZoneMinder Communicatoions Class Interface, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

#ifndef ZM_COMMS_H
#define ZM_COMMS_H

#include "zm_exception.h"
#include "zm_logger.h"
#include "zm_time.h"
#include <cerrno>
#include <netdb.h>
#include <set>
#include <sys/uio.h>
#include <sys/un.h>
#include <unistd.h>
#include <vector>

#if defined(BSD)
#include <sys/socket.h>
#include <netinet/in.h>
#endif

namespace zm {

class CommsException : public Exception {
 public:
  explicit CommsException(const std::string &message) : Exception(message) {}
};

class CommsBase {
 protected:
  CommsBase(const int &rd, const int &wd) : mRd(rd), mWd(wd) {}
  virtual ~CommsBase() = default;

 public:
  virtual bool close() = 0;
  virtual bool isOpen() const = 0;
  virtual bool isClosed() const = 0;
  virtual bool setBlocking(bool blocking) = 0;

 public:
  virtual int getReadDesc() const { return mRd; }
  virtual int getWriteDesc() const { return mWd; }
  int getMaxDesc() const { return mRd > mWd ? mRd : mWd; }

  virtual int read(void *msg, int len) {
    ssize_t nBytes = ::read(mRd, msg, len);
    if (nBytes < 0) {
      Debug(1, "Read of %d bytes max on rd %d failed: %s", len, mRd, strerror(errno));
    }
    return nBytes;
  }

  virtual int write(const void *msg, int len) {
    ssize_t nBytes = ::write(mWd, msg, len);
    if (nBytes < 0) {
      Debug(1, "Write of %d bytes on wd %d failed: %s", len, mWd, strerror(errno));
    }
    return nBytes;
  }

  virtual int readV(const iovec *iov, int iovcnt) {
    int nBytes = ::readv(mRd, iov, iovcnt);
    if (nBytes < 0) {
      Debug(1, "Readv of %d buffers max on rd %d failed: %s", iovcnt, mRd, strerror(errno));
    }
    return nBytes;
  }

  virtual int writeV(const iovec *iov, int iovcnt) {
    ssize_t nBytes = ::writev(mWd, iov, iovcnt);
    if (nBytes < 0) {
      Debug(1, "Writev of %d buffers on wd %d failed: %s", iovcnt, mWd, strerror(errno));
    }
    return nBytes;
  }

  virtual int readV(int iovcnt, /* const void *msg1, int len1, */ ...);
  virtual int writeV(int iovcnt, /* const void *msg1, int len1, */ ...);

 protected:
  const int &mRd;
  const int &mWd;
};

class Pipe : public CommsBase {
 public:
  Pipe() : CommsBase(mFd[0], mFd[1]) {
    mFd[0] = -1;
    mFd[1] = -1;
  }

  ~Pipe() override { close(); }

  bool open();
  bool close() override;

  bool isOpen() const override { return mFd[0] != -1 && mFd[1] != -1; }
  bool isClosed() const override { return !isOpen(); }
  int getReadDesc() const override { return mFd[0]; }
  int getWriteDesc() const override { return mFd[1]; }

  bool setBlocking(bool blocking) override;

 protected:
  int mFd[2];
};

class SockAddr {
 public:
  explicit SockAddr(const sockaddr *addr) : mAddr(addr) {}
  virtual ~SockAddr() = default;

  static SockAddr *newSockAddr(const sockaddr &addr, socklen_t len);
  static SockAddr *newSockAddr(const SockAddr *addr);

  int getDomain() const { return mAddr ? mAddr->sa_family : AF_UNSPEC; }
  const sockaddr *getAddr() const { return mAddr; }

  virtual socklen_t getAddrSize() const = 0;
  virtual sockaddr *getTempAddr() const = 0;

 private:
  const sockaddr *mAddr;
};

class SockAddrInet : public SockAddr {
 public:
  SockAddrInet() : SockAddr((sockaddr *) &mAddrIn) {}
  explicit SockAddrInet(const SockAddrInet &addr)
      : SockAddr((const sockaddr *) &mAddrIn), mAddrIn(addr.mAddrIn) {}
  explicit SockAddrInet(const sockaddr_in *addr)
      : SockAddr((const sockaddr *) &mAddrIn), mAddrIn(*addr) {}

  bool resolve(const char *host, const char *serv, const char *proto);
  bool resolve(const char *host, int port, const char *proto);
  bool resolve(const char *serv, const char *proto);
  bool resolve(int port, const char *proto);

  socklen_t getAddrSize() const override { return sizeof(mAddrIn); }
  sockaddr *getTempAddr() const override { return (sockaddr *) &mTempAddrIn; }

  static socklen_t addrSize() { return sizeof(sockaddr_in); }

 private:
  sockaddr_in mAddrIn;
  sockaddr_in mTempAddrIn;
};

class SockAddrUnix : public SockAddr {
 public:
  SockAddrUnix() : SockAddr((sockaddr *) &mAddrUn) {}
  SockAddrUnix(const SockAddrUnix &addr)
      : SockAddr((const sockaddr *) &mAddrUn), mAddrUn(addr.mAddrUn) {}
  explicit SockAddrUnix(const sockaddr_un *addr)
      : SockAddr((const sockaddr *) &mAddrUn), mAddrUn(*addr) {}

  bool resolve(const char *path, const char *proto);

  socklen_t getAddrSize() const override { return sizeof(mAddrUn); }
  sockaddr *getTempAddr() const override { return (sockaddr *) &mTempAddrUn; }

  static socklen_t addrSize() { return sizeof(sockaddr_un); }

 private:
  sockaddr_un mAddrUn;
  sockaddr_un mTempAddrUn;
};

class Socket : public CommsBase {
 protected:
  enum State { CLOSED, DISCONNECTED, LISTENING, CONNECTED };

  Socket() : CommsBase(mSd, mSd),
             mSd(-1),
             mState(CLOSED),
             mLocalAddr(nullptr),
             mRemoteAddr(nullptr) {}
  Socket(const Socket &socket, int newSd) : CommsBase(mSd, mSd),
                                            mSd(newSd),
                                            mState(CONNECTED),
                                            mLocalAddr(nullptr),
                                            mRemoteAddr(nullptr) {
    if (socket.mLocalAddr)
      mLocalAddr = SockAddr::newSockAddr(mLocalAddr);
    if (socket.mRemoteAddr)
      mRemoteAddr = SockAddr::newSockAddr(mRemoteAddr);
  }

  virtual ~Socket() {
    close();
    delete mLocalAddr;
    delete mRemoteAddr;
  }

 public:
  bool isOpen() const override { return !isClosed(); }
  bool isClosed() const override { return mState == CLOSED; }
  bool isDisconnected() const { return mState == DISCONNECTED; }
  bool isConnected() const { return mState == CONNECTED; }
  bool close() override;

  virtual int send(const void *msg, int len) const {
    ssize_t nBytes = ::send(mSd, msg, len, 0);
    if (nBytes < 0) {
      Debug(1, "Send of %d bytes on sd %d failed: %s", len, mSd, strerror(errno));
    }
    return nBytes;
  }

  virtual int recv(void *msg, int len) const {
    ssize_t nBytes = ::recv(mSd, msg, len, 0);
    if (nBytes < 0) {
      Debug(1, "Recv of %d bytes max on sd %d failed: %s", len, mSd, strerror(errno));
    }
    return nBytes;
  }

  virtual int send(const std::string &msg) const {
    ssize_t nBytes = ::send(mSd, msg.data(), msg.size(), 0);
    if (nBytes < 0) {
      Debug(1, "Send of string '%s' (%zd bytes) on sd %d failed: %s",
            msg.c_str(),
            msg.size(),
            mSd,
            strerror(errno));
    }
    return nBytes;
  }

  virtual ssize_t recv(std::string &msg) const {
    msg.reserve(ZM_NETWORK_BUFSIZ);
    std::vector<char> buffer(msg.capacity());
    ssize_t nBytes;
    if ((nBytes = ::recv(mSd, buffer.data(), buffer.size(), 0)) < 0) {
      Debug(1, "Recv of %zd bytes max to string on sd %d failed: %s", msg.size(), mSd, strerror(errno));
      return nBytes;
    }
    buffer[nBytes] = '\0';
    msg = {buffer.begin(), buffer.begin() + nBytes};
    return nBytes;
  }

  virtual ssize_t recv(std::string &msg, size_t maxLen) const {
    std::vector<char> buffer(maxLen);
    ssize_t nBytes;
    if ((nBytes = ::recv(mSd, buffer.data(), buffer.size(), 0)) < 0) {
      Debug(1, "Recv of %zd bytes max to string on sd %d failed: %s", maxLen, mSd, strerror(errno));
      return nBytes;
    }
    buffer[nBytes] = '\0';
    msg = {buffer.begin(), buffer.begin() + nBytes};
    return nBytes;
  }

  virtual int bytesToRead() const;

  int getDesc() const { return mSd; }
  //virtual bool isOpen() const
  //{
  //return( mSd != -1 );
  //}

  virtual int getDomain() const = 0;
  virtual int getType() const = 0;
  virtual const char *getProtocol() const = 0;

  const SockAddr *getLocalAddr() const { return mLocalAddr; }
  const SockAddr *getRemoteAddr() const { return mRemoteAddr; }
  virtual socklen_t getAddrSize() const = 0;

  bool getBlocking(bool &blocking);
  bool setBlocking(bool blocking) override;

  int getSendBufferSize(int &) const;
  int getRecvBufferSize(int &) const;

  bool setSendBufferSize(int);
  bool setRecvBufferSize(int);

  bool getRouting(bool &) const;
  bool setRouting(bool);

  bool getNoDelay(bool &) const;
  bool setNoDelay(bool);

 protected:
  virtual bool isListening() const { return mState == LISTENING; }

  bool socket();
  bool bind();

  bool connect();
  virtual bool listen();
  virtual bool accept();
  bool accept(int &);

  int mSd;
  State mState;
  SockAddr *mLocalAddr;
  SockAddr *mRemoteAddr;
};

class InetSocket : virtual public Socket {
 public:
  int getDomain() const override { return mAddressFamily; }
  socklen_t getAddrSize() const override { return SockAddrInet::addrSize(); }

 protected:
  bool connect(const char *host, const char *serv);
  bool connect(const char *host, int port);

  bool bind(const char *host, const char *serv);
  bool bind(const char *host, int port);
  bool bind(const char *serv);
  bool bind(int port);

  int mAddressFamily;
};

class UnixSocket : virtual public Socket {
 public:
  int getDomain() const override { return AF_UNIX; }
  socklen_t getAddrSize() const override { return SockAddrUnix::addrSize(); }

 protected:
  bool resolveLocal(const char *serv, const char *proto) {
    SockAddrUnix *addr = new SockAddrUnix;
    mLocalAddr = addr;
    return addr->resolve(serv, proto);
  }

  bool resolveRemote(const char *path, const char *proto) {
    SockAddrUnix *addr = new SockAddrUnix;
    mRemoteAddr = addr;
    return addr->resolve(path, proto);
  }

  bool bind(const char *path) {
    if (!UnixSocket::resolveLocal(path, getProtocol()))
      return false;
    return Socket::bind();
  }

  bool connect(const char *path) {
    if (!UnixSocket::resolveRemote(path, getProtocol()))
      return false;
    return Socket::connect();
  }
};

class UdpSocket : virtual public Socket {
 public:
  int getType() const override { return SOCK_DGRAM; }
  const char *getProtocol() const override { return "udp"; }

  virtual int sendto(const void *msg, int len, const SockAddr *addr = nullptr) const {
    ssize_t nBytes = ::sendto(mSd, msg, len, 0, addr ? addr->getAddr() : nullptr, addr ? addr->getAddrSize() : 0);
    if (nBytes < 0) {
      Debug(1, "Sendto of %d bytes on sd %d failed: %s", len, mSd, strerror(errno));
    }
    return nBytes;
  }

  virtual int recvfrom(void *msg, int len, SockAddr *addr = nullptr) const {
    ssize_t nBytes = 0;
    if (addr) {
      sockaddr sockAddr = {};
      socklen_t sockLen;
      nBytes = ::recvfrom(mSd, msg, len, 0, &sockAddr, &sockLen);
      if (nBytes < 0) {
        Debug(1, "Recvfrom of %d bytes max on sd %d (with address) failed: %s", len, mSd, strerror(errno));
      }
    } else {
      nBytes = ::recvfrom(mSd, msg, len, 0, nullptr, nullptr);
      if (nBytes < 0) {
        Debug(1, "Recvfrom of %d bytes max on sd %d (no address) failed: %s", len, mSd, strerror(errno));
      }
    }
    return nBytes;
  }
};

class UdpInetSocket : virtual public UdpSocket, virtual public InetSocket {
 public:
  bool bind(const char *host, const char *serv) {
    return InetSocket::bind(host, serv);
  }

  bool bind(const char *host, int port) {
    return InetSocket::bind(host, port);
  }

  bool bind(const char *serv) {
    return InetSocket::bind(serv);
  }

  bool bind(int port) {
    return InetSocket::bind(port);
  }

  bool connect(const char *host, const char *serv) {
    return InetSocket::connect(host, serv);
  }

  bool connect(const char *host, int port) {
    return InetSocket::connect(host, port);
  }
};

class UdpUnixSocket : virtual public UdpSocket, virtual public UnixSocket {
 public:
  bool bind(const char *path) {
    return UnixSocket::bind(path);
  }

  bool connect(const char *path) {
    return UnixSocket::connect(path);
  }
};

class UdpInetClient : public UdpInetSocket {
 public:
  bool connect(const char *host, const char *serv) {
    return UdpInetSocket::connect(host, serv);
  }

  bool connect(const char *host, int port) {
    return UdpInetSocket::connect(host, port);
  }
};

class UdpUnixClient : public UdpUnixSocket {
 public:
  bool bind(const char *path) {
    return UdpUnixSocket::bind(path);
  }

  bool connect(const char *path) {
    return UdpUnixSocket::connect(path);
  }
};

class UdpInetServer : public UdpInetSocket {
 public:
  bool bind(const char *host, const char *serv) {
    return UdpInetSocket::bind(host, serv);
  }

  bool bind(const char *host, int port) {
    return UdpInetSocket::bind(host, port);
  }

  bool bind(const char *serv) {
    return UdpInetSocket::bind(serv);
  }

  bool bind(int port) {
    return UdpInetSocket::bind(port);
  }

 protected:
  bool connect(const char *host, const char *serv) {
    return UdpInetSocket::connect(host, serv);
  }

  bool connect(const char *host, int port) {
    return UdpInetSocket::connect(host, port);
  }
};

class UdpUnixServer : public UdpUnixSocket {
 public:
  bool bind(const char *path) {
    return UdpUnixSocket::bind(path);
  }

 protected:
  bool connect(const char *path) {
    return UdpUnixSocket::connect(path);
  }
};

class TcpSocket : virtual public Socket {
 public:
  TcpSocket() = default;
  TcpSocket(const TcpSocket &socket, int newSd) : Socket(socket, newSd) {}

  int getType() const override { return SOCK_STREAM; }
  const char *getProtocol() const override { return "tcp"; }
};

class TcpInetSocket : virtual public TcpSocket, virtual public InetSocket {
 public:
  TcpInetSocket() = default;
  TcpInetSocket(const TcpInetSocket &socket, int newSd)
      : TcpSocket(socket, newSd) {}
};

class TcpUnixSocket : virtual public TcpSocket, virtual public UnixSocket {
 public:
  TcpUnixSocket() = default;
  TcpUnixSocket(const TcpUnixSocket &socket, int newSd)
      : TcpSocket(socket, newSd) {}
};

class TcpInetClient : public TcpInetSocket {
 public:
  bool connect(const char *host, const char *serv) {
    return TcpInetSocket::connect(host, serv);
  }

  bool connect(const char *host, int port) {
    return TcpInetSocket::connect(host, port);
  }
};

class TcpUnixClient : public TcpUnixSocket {
 public:
  bool connect(const char *path) { return TcpUnixSocket::connect(path); }
};

class TcpInetServer : public TcpInetSocket {
 public:
  bool bind(int port) { return TcpInetSocket::bind(port); }

  bool isListening() const override { return Socket::isListening(); }
  bool listen() override;
  bool accept() override;
  bool accept(TcpInetSocket *&newSocket);
};

class TcpUnixServer : public TcpUnixSocket {
 public:
  bool bind(const char *path) { return TcpUnixSocket::bind(path); }

  bool isListening() const override { return Socket::isListening(); }
  bool listen() override;
  bool accept() override;
  bool accept(TcpUnixSocket *&newSocket);
};

class Select {
 public:
  typedef std::set<CommsBase *> CommsSet;
  typedef std::vector<CommsBase *> CommsList;

  Select() : mHasTimeout(false), mMaxFd(-1) {}
  explicit Select(Microseconds timeout) : mMaxFd(-1) { setTimeout(timeout); }

  void setTimeout(Microseconds timeout);
  void clearTimeout();

  void calcMaxFd();

  bool addReader(CommsBase *comms);
  bool deleteReader(CommsBase *comms);
  void clearReaders();

  bool addWriter(CommsBase *comms);
  bool deleteWriter(CommsBase *comms);
  void clearWriters();

  int wait();

  const CommsList &getReadable() const { return mReadable; }
  const CommsList &getWriteable() const { return mWriteable; }

 protected:
  CommsSet mReaders;
  CommsSet mWriters;
  CommsList mReadable;
  CommsList mWriteable;
  bool mHasTimeout;
  Microseconds mTimeout;
  int mMaxFd;
};

}

#endif // ZM_COMMS_H
