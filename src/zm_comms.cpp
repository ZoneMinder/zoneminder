//
// ZoneMinder Communications Class Implementation, $Date$, $Revision$
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

#include "zm_comms.h"

#include "zm_logger.h"
#include <arpa/inet.h>   // for debug output
#include <cerrno>
#include <cstdarg>
#include <cstdio>       // for snprintf
#include <fcntl.h>
#include <netinet/tcp.h>
#include <sys/ioctl.h>
#include <sys/param.h>
#include <utility>

#ifdef SOLARIS
#include <sys/filio.h> // define FIONREAD
#endif

int zm::CommsBase::readV(int iovcnt, /* const void *, int, */ ...) {
  va_list arg_ptr;
  std::vector<iovec> iov(iovcnt);

  va_start(arg_ptr, iovcnt);
  for (int i = 0; i < iovcnt; i++) {
    iov[i].iov_base = va_arg(arg_ptr, void *);
    iov[i].iov_len = va_arg(arg_ptr, int);
  }
  va_end(arg_ptr);

  int nBytes = ::readv(mRd, iov.data(), iovcnt);
  if (nBytes < 0) {
    Debug(1, "Readv of %d buffers max on rd %d failed: %s", iovcnt, mRd, strerror(errno));
  }
  return nBytes;
}

int zm::CommsBase::writeV(int iovcnt, /* const void *, int, */ ...) {
  va_list arg_ptr;
  std::vector<iovec> iov(iovcnt);

  va_start(arg_ptr, iovcnt);
  for (int i = 0; i < iovcnt; i++) {
    iov[i].iov_base = va_arg(arg_ptr, void *);
    iov[i].iov_len = va_arg(arg_ptr, int);
  }
  va_end(arg_ptr);

  ssize_t nBytes = ::writev(mWd, iov.data(), iovcnt);
  if (nBytes < 0) {
    Debug(1, "Writev of %d buffers on wd %d failed: %s", iovcnt, mWd, strerror(errno));
  }
  return nBytes;
}

bool zm::Pipe::open() {
  if (::pipe(mFd) < 0) {
    Error("pipe(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }

  return true;
}

bool zm::Pipe::close() {
  if (mFd[0] > -1) {
    ::close(mFd[0]);
  }
  mFd[0] = -1;
  if (mFd[1] > -1) {
    ::close(mFd[1]);
  }
  mFd[1] = -1;
  return true;
}

bool zm::Pipe::setBlocking(bool blocking) {
  int flags;

  /* Now set it for non-blocking I/O */
  if ((flags = fcntl(mFd[1], F_GETFL)) < 0) {
    Error("fcntl(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }
  if (blocking) {
    flags &= ~O_NONBLOCK;
  } else {
    flags |= O_NONBLOCK;
  }
  if (fcntl(mFd[1], F_SETFL, flags) < 0) {
    Error("fcntl(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }

  return true;
}

zm::SockAddr *zm::SockAddr::newSockAddr(const sockaddr &addr, socklen_t len) {
  if ((addr.sa_family == AF_INET) && (len == SockAddrInet::addrSize())) {
    return new SockAddrInet((const sockaddr_in *) &addr);
  } else if ((addr.sa_family == AF_UNIX) && (len == SockAddrUnix::addrSize())) {
    return new SockAddrUnix((const sockaddr_un *) &addr);
  }

  Error("Unable to create new SockAddr from addr family %d with size %d", addr.sa_family, len);
  return nullptr;
}

zm::SockAddr *zm::SockAddr::newSockAddr(const SockAddr *addr) {
  if (!addr) {
    return nullptr;
  }

  if (addr->getDomain() == AF_INET) {
    return new SockAddrInet(*(SockAddrInet *) addr);
  } else if (addr->getDomain() == AF_UNIX) {
    return new SockAddrUnix(*(SockAddrUnix *) addr);
  }

  Error("Unable to create new SockAddr from addr family %d", addr->getDomain());
  return nullptr;
}

bool zm::SockAddrInet::resolve(const char *host, const char *serv, const char *proto) {
  memset(&mAddrIn, 0, sizeof(mAddrIn));

  hostent *hostent = nullptr;
  if (!(hostent = ::gethostbyname(host))) {
    Error("gethostbyname(%s), h_errno = %d", host, h_errno);
    return false;
  }

  servent *servent = nullptr;
  if (!(servent = ::getservbyname(serv, proto))) {
    Error("getservbyname( %s ), errno = %d, error = %s", serv, errno, strerror(errno));
    return false;
  }

  mAddrIn.sin_port = servent->s_port;
  mAddrIn.sin_family = AF_INET;
  mAddrIn.sin_addr.s_addr = ((in_addr *) (hostent->h_addr))->s_addr;

  return true;
}

bool zm::SockAddrInet::resolve(const char *host, int port, const char *proto) {
  memset(&mAddrIn, 0, sizeof(mAddrIn));

  hostent *hostent = nullptr;
  if (!(hostent = ::gethostbyname(host))) {
    Error("gethostbyname(%s), h_errno = %d", host, h_errno);
    return false;
  }

  mAddrIn.sin_port = htons(port);
  mAddrIn.sin_family = AF_INET;
  mAddrIn.sin_addr.s_addr = ((in_addr *) (hostent->h_addr))->s_addr;
  return true;
}

bool zm::SockAddrInet::resolve(const char *serv, const char *proto) {
  memset(&mAddrIn, 0, sizeof(mAddrIn));

  servent *servent = nullptr;
  if (!(servent = ::getservbyname(serv, proto))) {
    Error("getservbyname(%s), errno = %d, error = %s", serv, errno, strerror(errno));
    return false;
  }

  mAddrIn.sin_port = servent->s_port;
  mAddrIn.sin_family = AF_INET;
  mAddrIn.sin_addr.s_addr = INADDR_ANY;

  return true;
}

bool zm::SockAddrInet::resolve(int port, const char *proto) {
  memset(&mAddrIn, 0, sizeof(mAddrIn));

  mAddrIn.sin_port = htons(port);
  mAddrIn.sin_family = AF_INET;
  mAddrIn.sin_addr.s_addr = INADDR_ANY;

  return true;
}

bool zm::SockAddrUnix::resolve(const char *path, const char *proto) {
  memset(&mAddrUn, 0, sizeof(mAddrUn));

  strncpy(mAddrUn.sun_path, path, sizeof(mAddrUn.sun_path));
  mAddrUn.sun_path[sizeof(mAddrUn.sun_path) - 1] = '\0';
  mAddrUn.sun_family = AF_UNIX;

  return true;
}

bool zm::Socket::socket() {
  if (mSd >= 0) {
    return true;
  }

  if ((mSd = ::socket(getDomain(), getType(), 0)) < 0) {
    Error("socket(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }

  int val = 1;

  ::setsockopt(mSd, SOL_SOCKET, SO_REUSEADDR, &val, sizeof(val));
  ::setsockopt(mSd, SOL_SOCKET, SO_KEEPALIVE, &val, sizeof(val));

  mState = DISCONNECTED;
  return true;
}

bool zm::Socket::connect() {
  if (!socket()) {
    return false;
  }

  if (::connect(mSd, mRemoteAddr->getAddr(), getAddrSize()) == -1) {
    Error("connect(), errno = %d, error = %s", errno, strerror(errno));
    close();
    return false;
  }

  mState = CONNECTED;
  return true;
}

bool zm::Socket::bind() {
  if (!socket()) {
    return false;
  }

  if (::bind(mSd, mLocalAddr->getAddr(), getAddrSize()) == -1) {
    Error("bind(), errno = %d, error = %s", errno, strerror(errno));
    close();
    return false;
  }
  return true;
}

bool zm::Socket::listen() {
  if (::listen(mSd, SOMAXCONN) == -1) {
    Error("listen(), errno = %d, error = %s", errno, strerror(errno));
    close();
    return false;
  }

  mState = LISTENING;
  return true;
}

bool zm::Socket::accept() {
  sockaddr rem_addr = {};
  socklen_t rem_addr_size = getAddrSize();

  int newSd = -1;
  if ((newSd = ::accept(mSd, &rem_addr, &rem_addr_size)) == -1) {
    Error("accept(), errno = %d, error = %s", errno, strerror(errno));
    close();
    return false;
  }

  ::close(mSd);
  mSd = newSd;

  mState = CONNECTED;
  return true;
}

bool zm::Socket::accept(int &newSd) {
  sockaddr rem_addr = {};
  socklen_t rem_addr_size = getAddrSize();

  newSd = -1;
  if ((newSd = ::accept(mSd, &rem_addr, &rem_addr_size)) == -1) {
    Error("accept(), errno = %d, error = %s", errno, strerror(errno));
    close();
    return false;
  }

  return true;
}

bool zm::Socket::close() {
  if (mSd > -1) {
    ::close(mSd);
  }

  mSd = -1;
  mState = CLOSED;
  return true;
}

int zm::Socket::bytesToRead() const {
  int bytes_to_read = 0;

  if (ioctl(mSd, FIONREAD, &bytes_to_read) < 0) {
    Error("ioctl(), errno = %d, error = %s", errno, strerror(errno));
    return -1;
  }
  return bytes_to_read;
}

bool zm::Socket::getBlocking(bool &blocking) {
  int flags;

  if ((flags = fcntl(mSd, F_GETFL)) < 0) {
    Error("fcntl(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }
  blocking = flags & O_NONBLOCK;
  return true;
}

bool zm::Socket::setBlocking(bool blocking) {
  int flags;

  /* Now set it for non-blocking I/O */
  if ((flags = fcntl(mSd, F_GETFL)) < 0) {
    Error("fcntl(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }
  if (blocking) {
    flags &= ~O_NONBLOCK;
  } else {
    flags |= O_NONBLOCK;
  }
  if (fcntl(mSd, F_SETFL, flags) < 0) {
    Error("fcntl(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }

  return true;
}

int zm::Socket::getSendBufferSize(int &buffersize) const {
  socklen_t optlen = sizeof(buffersize);
  if (getsockopt(mSd, SOL_SOCKET, SO_SNDBUF, &buffersize, &optlen) < 0) {
    Error("getsockopt(), errno = %d, error = %s", errno, strerror(errno));
    return -1;
  }
  return buffersize;
}

int zm::Socket::getRecvBufferSize(int &buffersize) const {
  socklen_t optlen = sizeof(buffersize);
  if (getsockopt(mSd, SOL_SOCKET, SO_RCVBUF, &buffersize, &optlen) < 0) {
    Error("getsockopt(), errno = %d, error = %s", errno, strerror(errno));
    return -1;
  }
  return buffersize;
}

bool zm::Socket::setSendBufferSize(int buffersize) {
  if (setsockopt(mSd, SOL_SOCKET, SO_SNDBUF, (char *) &buffersize, sizeof(buffersize)) < 0) {
    Error("setsockopt(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }
  return true;
}

bool zm::Socket::setRecvBufferSize(int buffersize) {
  if (setsockopt(mSd, SOL_SOCKET, SO_RCVBUF, (char *) &buffersize, sizeof(buffersize)) < 0) {
    Error("setsockopt(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }
  return true;
}

bool zm::Socket::getRouting(bool &route) const {
  int dontRoute;
  socklen_t optlen = sizeof(dontRoute);
  if (getsockopt(mSd, SOL_SOCKET, SO_DONTROUTE, &dontRoute, &optlen) < 0) {
    Error("getsockopt(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }
  route = !dontRoute;
  return true;
}

bool zm::Socket::setRouting(bool route) {
  int dontRoute = !route;
  if (setsockopt(mSd, SOL_SOCKET, SO_DONTROUTE, (char *) &dontRoute, sizeof(dontRoute)) < 0) {
    Error("setsockopt(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }
  return true;
}

bool zm::Socket::getNoDelay(bool &nodelay) const {
  int int_nodelay;
  socklen_t optlen = sizeof(int_nodelay);
  if (getsockopt(mSd, IPPROTO_TCP, TCP_NODELAY, &int_nodelay, &optlen) < 0) {
    Error("getsockopt(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }
  nodelay = int_nodelay;
  return true;
}

bool zm::Socket::setNoDelay(bool nodelay) {
  int int_nodelay = nodelay;

  if (setsockopt(mSd, IPPROTO_TCP, TCP_NODELAY, (char *) &int_nodelay, sizeof(int_nodelay)) < 0) {
    Error("setsockopt(), errno = %d, error = %s", errno, strerror(errno));
    return false;
  }
  return true;
}

bool zm::InetSocket::connect(const char *host, const char *serv) {
  addrinfo hints;
  addrinfo *result, *rp;
  int s;
  char buf[255];

  mAddressFamily = AF_UNSPEC;
  memset(&hints, 0, sizeof(addrinfo));
  hints.ai_family = AF_UNSPEC;    /* Allow IPv4 or IPv6 */
  hints.ai_socktype = getType();
  hints.ai_flags = 0;
  hints.ai_protocol = 0;          /* Any protocol */

  s = getaddrinfo(host, serv, &hints, &result);
  if (s != 0) {
    Error("connect(): getaddrinfo: %s", gai_strerror(s));
    return false;
  }

  /* getaddrinfo() returns a list of address structures.
   * Try each address until we successfully connect(2).
   * If socket(2) (or connect(2)) fails, we (close the socket
   * and) try the next address. */

  for (rp = result; rp != nullptr; rp = rp->ai_next) {
    if (mSd != -1) {
      if (::connect(mSd, rp->ai_addr, rp->ai_addrlen) != -1) {
        break;                  /* Success */
      }
      continue;
    }

    memset(&buf, 0, sizeof(buf));
    if (rp->ai_family == AF_INET) {
      inet_ntop(AF_INET, &((sockaddr_in *) rp->ai_addr)->sin_addr, buf, sizeof(buf) - 1);
    } else if (rp->ai_family == AF_INET6) {
      inet_ntop(AF_INET6, &((sockaddr_in6 *) rp->ai_addr)->sin6_addr, buf, sizeof(buf) - 1);
    } else {
      strncpy(buf, "n/a", sizeof(buf) - 1);
    }

    Debug(1, "connect(): Trying '%s', family '%d', proto '%d'", buf, rp->ai_family, rp->ai_protocol);
    mSd = ::socket(rp->ai_family, rp->ai_socktype, rp->ai_protocol);
    if (mSd == -1) {
      continue;
    }

    int val = 1;

    ::setsockopt(mSd, SOL_SOCKET, SO_REUSEADDR, &val, sizeof(val));
    ::setsockopt(mSd, SOL_SOCKET, SO_KEEPALIVE, &val, sizeof(val));
    mAddressFamily = rp->ai_family;         /* save AF_ for ctrl and data connections */

    if (::connect(mSd, rp->ai_addr, rp->ai_addrlen) != -1) {
      break;                  /* Success */
    }

    ::close(mSd);
  }

  freeaddrinfo(result);   /* No longer needed */

  if (rp == nullptr) {               /* No address succeeded */
    Error("connect(), Could not connect");
    mAddressFamily = AF_UNSPEC;
    return false;
  }

  mState = CONNECTED;
  return true;
}

bool zm::InetSocket::connect(const char *host, int port) {
  char serv[8];
  snprintf(serv, sizeof(serv), "%d", port);

  return connect(host, serv);
}

bool zm::InetSocket::bind(const char *host, const char *serv) {
  addrinfo hints;

  memset(&hints, 0, sizeof(addrinfo));
  hints.ai_family = AF_UNSPEC;    /* Allow IPv4 or IPv6 */
  hints.ai_socktype = getType();
  hints.ai_flags = AI_PASSIVE;    /* For wildcard IP address */
  hints.ai_protocol = 0;          /* Any protocol */
  hints.ai_canonname = nullptr;
  hints.ai_addr = nullptr;
  hints.ai_next = nullptr;

  addrinfo *result, *rp;
  int s = getaddrinfo(host, serv, &hints, &result);
  if (s != 0) {
    Error("bind(): getaddrinfo: %s", gai_strerror(s));
    return false;
  }

  char buf[255];
  /* getaddrinfo() returns a list of address structures.
   * Try each address until we successfully bind(2).
   * If socket(2) (or bind(2)) fails, we (close the socket
   * and) try the next address. */
  for (rp = result; rp != nullptr; rp = rp->ai_next) {
    memset(&buf, 0, sizeof(buf));
    if (rp->ai_family == AF_INET) {
      inet_ntop(AF_INET, &((sockaddr_in *) rp->ai_addr)->sin_addr, buf, sizeof(buf) - 1);
    } else if (rp->ai_family == AF_INET6) {
      inet_ntop(AF_INET6, &((sockaddr_in6 *) rp->ai_addr)->sin6_addr, buf, sizeof(buf) - 1);
    } else {
      strncpy(buf, "n/a", sizeof(buf) - 1);
    }

    Debug(1, "bind(): Trying '%s', family '%d', proto '%d'", buf, rp->ai_family, rp->ai_protocol);
    mSd = ::socket(rp->ai_family, rp->ai_socktype, rp->ai_protocol);
    if (mSd == -1) {
      continue;
    }

    mState = DISCONNECTED;
    if (::bind(mSd, rp->ai_addr, rp->ai_addrlen) == 0) {
      break;                  /* Success */
    }

    ::close(mSd);
    mSd = -1;
  }

  if (rp == nullptr) {               /* No address succeeded */
    Error("bind(), Could not bind");
    return false;
  }

  freeaddrinfo(result);   /* No longer needed */
  return true;
}

bool zm::InetSocket::bind(const char *serv) {
  return bind(nullptr, serv);
}

bool zm::InetSocket::bind(const char *host, int port) {
  char serv[8];
  snprintf(serv, sizeof(serv), "%d", port);

  return bind(host, serv);
}

bool zm::InetSocket::bind(int port) {
  char serv[8];
  snprintf(serv, sizeof(serv), "%d", port);

  return bind(nullptr, serv);
}

bool zm::TcpInetServer::listen() {
  return Socket::listen();
}

bool zm::TcpInetServer::accept() {
  return Socket::accept();
}

bool zm::TcpInetServer::accept(TcpInetSocket *&newSocket) {
  int newSd = -1;
  newSocket = nullptr;

  if (!Socket::accept(newSd)) {
    return false;
  }

  newSocket = new TcpInetSocket(*this, newSd);
  return true;
}

bool zm::TcpUnixServer::accept(TcpUnixSocket *&newSocket) {
  int newSd = -1;
  newSocket = nullptr;

  if (!Socket::accept(newSd)) {
    return false;
  }

  newSocket = new TcpUnixSocket(*this, newSd);
  return true;
}

void zm::Select::setTimeout(Microseconds timeout) {
  mTimeout = timeout;
  mHasTimeout = true;
}

void zm::Select::clearTimeout() {
  mHasTimeout = false;
}

void zm::Select::calcMaxFd() {
  mMaxFd = -1;
  for (CommsSet::iterator iter = mReaders.begin(); iter != mReaders.end(); ++iter) {
    if ((*iter)->getMaxDesc() > mMaxFd)
      mMaxFd = (*iter)->getMaxDesc();
  }
  for (CommsSet::iterator iter = mWriters.begin(); iter != mWriters.end(); ++iter) {
    if ((*iter)->getMaxDesc() > mMaxFd)
      mMaxFd = (*iter)->getMaxDesc();
  }
}

bool zm::Select::addReader(CommsBase *comms) {
  if (!comms->isOpen()) {
    Error("Unable to add closed reader");
    return false;
  }
  std::pair<CommsSet::iterator, bool> result = mReaders.insert(comms);
  if (result.second) {
    if (comms->getMaxDesc() > mMaxFd) {
      mMaxFd = comms->getMaxDesc();
    }
  }
  return result.second;
}

bool zm::Select::deleteReader(CommsBase *comms) {
  if (!comms->isOpen()) {
    Error("Unable to add closed reader");
    return false;
  }
  if (mReaders.erase(comms)) {
    calcMaxFd();
    return true;
  }
  return false;
}

void zm::Select::clearReaders() {
  mReaders.clear();
  mMaxFd = -1;
}

bool zm::Select::addWriter(CommsBase *comms) {
  std::pair<CommsSet::iterator, bool> result = mWriters.insert(comms);
  if (result.second) {
    if (comms->getMaxDesc() > mMaxFd) {
      mMaxFd = comms->getMaxDesc();
    }
  }
  return result.second;
}

bool zm::Select::deleteWriter(CommsBase *comms) {
  if (mWriters.erase(comms)) {
    calcMaxFd();
    return true;
  }
  return false;
}

void zm::Select::clearWriters() {
  mWriters.clear();
  mMaxFd = -1;
}

int zm::Select::wait() {
  timeval tempTimeout = zm::chrono::duration_cast<timeval>(mTimeout);
  timeval *selectTimeout = mHasTimeout ? &tempTimeout : nullptr;

  fd_set rfds;
  fd_set wfds;

  mReadable.clear();
  FD_ZERO(&rfds);
  for (CommsSet::iterator iter = mReaders.begin(); iter != mReaders.end(); ++iter) {
    FD_SET((*iter)->getReadDesc(), &rfds);
  }

  mWriteable.clear();
  FD_ZERO(&wfds);
  for (CommsSet::iterator iter = mWriters.begin(); iter != mWriters.end(); ++iter) {
    FD_SET((*iter)->getWriteDesc(), &wfds);
  }

  int nFound = select(mMaxFd + 1, &rfds, &wfds, nullptr, selectTimeout);
  if (nFound == 0) {
    Debug(1, "Select timed out");
  } else if (nFound < 0) {
    Error("Select error: %s", strerror(errno));
  } else {
    for (CommsSet::iterator iter = mReaders.begin(); iter != mReaders.end(); ++iter) {
      if (FD_ISSET((*iter)->getReadDesc(), &rfds)) {
        mReadable.push_back(*iter);
      }
    }
    for (CommsSet::iterator iter = mWriters.begin(); iter != mWriters.end(); ++iter) {
      if (FD_ISSET((*iter)->getWriteDesc(), &rfds)) {
        mWriteable.push_back(*iter);
      }
    }
  }
  return nFound;
}
