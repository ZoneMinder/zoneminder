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

#include "zm_logger.h"
#include "zm_exception.h"

#include <string.h>
#include <unistd.h>
#include <netdb.h>
#include <errno.h>
#include <sys/un.h>

#include <set>
#include <vector>
#include <sys/uio.h>

#if defined(BSD)
#include <sys/socket.h>
#include <netinet/in.h>
#endif

class CommsException : public Exception {
public:
  explicit CommsException( const std::string &message ) : Exception( message ) { }
};

class CommsBase {
protected:
  const int  &mRd;
  const int  &mWd;

protected:
  CommsBase( int &rd, int &wd ) : mRd( rd ), mWd( wd ) {
  }
  virtual ~CommsBase() {
  }

public:
  virtual bool close()=0;
  virtual bool isOpen() const=0;
  virtual bool isClosed() const=0;
  virtual bool setBlocking( bool blocking )=0;

public:
  int getReadDesc() const {
    return( mRd );
  }
  int getWriteDesc() const {
    return( mWd );
  }
  int getMaxDesc() const {
    return( mRd>mWd?mRd:mWd );
  }

  virtual int read( void *msg, int len ) {
    ssize_t nBytes = ::read( mRd, msg, len );
    if ( nBytes < 0 )
      Debug( 1, "Read of %d bytes max on rd %d failed: %s", len, mRd, strerror(errno) );
    return( nBytes );
  }
  virtual int write( const void *msg, int len ) {
    ssize_t nBytes = ::write( mWd, msg, len );
    if ( nBytes < 0 )
      Debug( 1, "Write of %d bytes on wd %d failed: %s", len, mWd, strerror(errno) );
    return( nBytes );
  }
  virtual int readV( const struct iovec *iov, int iovcnt ) {
    int nBytes = ::readv( mRd, iov, iovcnt );
    if ( nBytes < 0 )
      Debug( 1, "Readv of %d buffers max on rd %d failed: %s", iovcnt, mRd, strerror(errno) );
    return( nBytes );
  }
  virtual int writeV( const struct iovec *iov, int iovcnt ) {
    ssize_t nBytes = ::writev( mWd, iov, iovcnt );
    if ( nBytes < 0 )
      Debug( 1, "Writev of %d buffers on wd %d failed: %s", iovcnt, mWd, strerror(errno) );
    return( nBytes );
  }
  virtual int readV( int iovcnt, /* const void *msg1, int len1, */ ... );
  virtual int writeV( int iovcnt, /* const void *msg1, int len1, */ ... );
};

class Pipe : public CommsBase {
protected:
  int mFd[2];

public:
  Pipe() : CommsBase( mFd[0], mFd[1] ) {
    mFd[0] = -1;
    mFd[1] = -1;
  }
  ~Pipe() {
    close();
  }

public:
  bool open();
  bool close();

  bool isOpen() const
  {
    return( mFd[0] != -1 && mFd[1] != -1 );
  }
  int getReadDesc() const
  {
    return( mFd[0] );
  }
  int getWriteDesc() const
  {
    return( mFd[1] );
  }

  bool setBlocking( bool blocking );
};

class SockAddr {
private:
  const struct sockaddr *mAddr;

public:
  explicit SockAddr( const struct sockaddr *addr );
  virtual ~SockAddr() {
  }

  static SockAddr *newSockAddr( const struct sockaddr &addr, socklen_t len );
  static SockAddr *newSockAddr( const SockAddr *addr );

  int getDomain() const {
    return( mAddr?mAddr->sa_family:AF_UNSPEC );
  }

  const struct sockaddr *getAddr() const {
    return( mAddr );
  }
  virtual socklen_t getAddrSize() const=0;
  virtual struct sockaddr *getTempAddr() const=0;
};

class SockAddrInet : public SockAddr {
private:
  struct sockaddr_in  mAddrIn;
  struct sockaddr_in  mTempAddrIn;

public:
  SockAddrInet();
  explicit SockAddrInet( const SockAddrInet &addr ) : SockAddr( (const struct sockaddr *)&mAddrIn ), mAddrIn( addr.mAddrIn ) {
  }
  explicit SockAddrInet( const struct sockaddr_in *addr ) : SockAddr( (const struct sockaddr *)&mAddrIn ), mAddrIn( *addr ) {
  }


  bool resolve( const char *host, const char *serv, const char *proto );
  bool resolve( const char *host, int port, const char *proto );
  bool resolve( const char *serv, const char *proto );
  bool resolve( int port, const char *proto );

  socklen_t getAddrSize() const {
    return( sizeof(mAddrIn) );
  }
  struct sockaddr *getTempAddr() const {
    return( (sockaddr *)&mTempAddrIn );
  }

public:
  static socklen_t addrSize() {
    return( sizeof(sockaddr_in) );
  }
};

class SockAddrUnix : public SockAddr {
private:
  struct sockaddr_un  mAddrUn;
  struct sockaddr_un  mTempAddrUn;

public:
  SockAddrUnix();
  SockAddrUnix( const SockAddrUnix &addr ) : SockAddr( (const struct sockaddr *)&mAddrUn ), mAddrUn( addr.mAddrUn ) {
  }
  explicit SockAddrUnix( const struct sockaddr_un *addr ) : SockAddr( (const struct sockaddr *)&mAddrUn ), mAddrUn( *addr ) {
  }

  bool resolve( const char *path, const char *proto );

  socklen_t getAddrSize() const {
    return( sizeof(mAddrUn) );
  }
  struct sockaddr *getTempAddr() const {
    return( (sockaddr *)&mTempAddrUn );
  }

public:
  static socklen_t addrSize() {
    return( sizeof(sockaddr_un) );
  }
};

class Socket : public CommsBase {
protected:
  typedef enum { CLOSED, DISCONNECTED, LISTENING, CONNECTED } State;

protected:
  int  mSd;
  State mState;
  SockAddr *mLocalAddr;
  SockAddr *mRemoteAddr;

protected:
  Socket() : CommsBase( mSd, mSd ), mSd( -1 ), mState( CLOSED ), mLocalAddr( 0 ), mRemoteAddr( 0 ) {
  }
  Socket( const Socket &socket, int newSd ) : CommsBase( mSd, mSd ), mSd( newSd ), mState( CONNECTED ), mLocalAddr( 0 ), mRemoteAddr( 0 ) {
    if ( socket.mLocalAddr )
      mLocalAddr = SockAddr::newSockAddr( mLocalAddr );
    if ( socket.mRemoteAddr )
      mRemoteAddr = SockAddr::newSockAddr( mRemoteAddr );
  }
  virtual ~Socket() {
    close();
    delete mLocalAddr;
    delete mRemoteAddr;
  }

public:
  bool isOpen() const {
    return( !isClosed() );
  }
  bool isClosed() const {
    return( mState == CLOSED );
  }
  bool isDisconnected() const {
    return( mState == DISCONNECTED );
  }
  bool isConnected() const {
    return( mState == CONNECTED );
  }
  virtual bool close();

protected:
  bool isListening() const {
    return( mState == LISTENING );
  }

protected:
  virtual bool socket();
  virtual bool bind();

protected:
  virtual bool connect();
  virtual bool listen();
  virtual bool accept();
  virtual bool accept( int & );

public:
  virtual int send( const void *msg, int len ) const {
    ssize_t nBytes = ::send( mSd, msg, len, 0 );
    if ( nBytes < 0 )
      Debug( 1, "Send of %d bytes on sd %d failed: %s", len, mSd, strerror(errno) );
    return( nBytes );
  }
  virtual int recv( void *msg, int len ) const {
    ssize_t nBytes = ::recv( mSd, msg, len, 0 );
    if ( nBytes < 0 )
      Debug( 1, "Recv of %d bytes max on sd %d failed: %s", len, mSd, strerror(errno) );
    return( nBytes );
  }
  virtual int send( const std::string &msg ) const {
    ssize_t nBytes = ::send( mSd, msg.data(), msg.size(), 0 );
    if ( nBytes < 0 )
      Debug( 1, "Send of string '%s' (%zd bytes) on sd %d failed: %s", msg.c_str(), msg.size(), mSd, strerror(errno) );
    return( nBytes );
  }
  virtual int recv( std::string &msg ) const {
    char buffer[msg.capacity()];
    int nBytes = 0;
    if ( (nBytes = ::recv( mSd, buffer, sizeof(buffer), 0 )) < 0 ) {
      Debug( 1, "Recv of %zd bytes max to string on sd %d failed: %s", sizeof(buffer), mSd, strerror(errno) );
      return( nBytes );
    }
    buffer[nBytes] = '\0';
    msg = buffer;
    return( nBytes );
  }
  virtual int recv( std::string &msg, size_t maxLen ) const {
    char buffer[maxLen];
    int nBytes = 0;
    if ( (nBytes = ::recv( mSd, buffer, sizeof(buffer), 0 )) < 0 ) {
      Debug( 1, "Recv of %zd bytes max to string on sd %d failed: %s", maxLen, mSd, strerror(errno) );
      return( nBytes );
    }
    buffer[nBytes] = '\0';
    msg = buffer;
    return( nBytes );
  }
  virtual int bytesToRead() const;

  int getDesc() const {
    return( mSd );
  }
  //virtual bool isOpen() const
  //{
    //return( mSd != -1 );
  //}

  virtual int getDomain() const=0;
  virtual int getType() const=0;
  virtual const char *getProtocol() const=0;

  const SockAddr *getLocalAddr() const {
    return( mLocalAddr );
  }
  const SockAddr *getRemoteAddr() const {
    return( mRemoteAddr );
  }
  virtual socklen_t getAddrSize() const=0;

  bool getBlocking( bool &blocking );
  bool setBlocking( bool blocking );

  bool getSendBufferSize( int & ) const;
  bool getRecvBufferSize( int & ) const;

  bool setSendBufferSize( int );
  bool setRecvBufferSize( int );

  bool getRouting( bool & ) const;
  bool setRouting( bool );

  bool getNoDelay( bool & ) const;
  bool setNoDelay( bool );
};

class InetSocket : virtual public Socket
{
protected:
    int mAddressFamily;

public:
int getDomain() const {
  return( mAddressFamily );
}
virtual socklen_t getAddrSize() const {
  return( SockAddrInet::addrSize() );
}

protected:
  bool connect( const char *host, const char *serv );
  bool connect( const char *host, int port );

  bool bind( const char *host, const char *serv );
  bool bind( const char *host, int port );
  bool bind( const char *serv );
  bool bind( int port );
};

class UnixSocket : virtual public Socket {
public:
  int getDomain() const {
    return( AF_UNIX );
  }
  virtual socklen_t getAddrSize() const {
    return( SockAddrUnix::addrSize() );
  }

protected:
  bool resolveLocal( const char *serv, const char *proto ) {
    SockAddrUnix *addr = new SockAddrUnix;
    mLocalAddr = addr;
    return( addr->resolve( serv, proto ) );
  }

  bool resolveRemote( const char *path, const char *proto ) {
    SockAddrUnix *addr = new SockAddrUnix;
    mRemoteAddr = addr;
    return( addr->resolve( path, proto ) );
  }

protected:
  bool bind( const char *path ) {
    if ( !UnixSocket::resolveLocal( path, getProtocol() ) )
      return( false );
    return( Socket::bind() );
  }

  bool connect( const char *path ) {
    if ( !UnixSocket::resolveRemote( path, getProtocol() ) )
      return( false );
    return( Socket::connect() );
  }
};

class UdpSocket : virtual public Socket {
public:
  int getType() const {
    return( SOCK_DGRAM );
  }
  const char *getProtocol() const {
    return( "udp" );
  }

public:
  virtual int sendto( const void *msg, int len, const SockAddr *addr=0 ) const {
    ssize_t nBytes = ::sendto( mSd, msg, len, 0, addr?addr->getAddr():NULL, addr?addr->getAddrSize():0 );
    if ( nBytes < 0 )
      Debug( 1, "Sendto of %d bytes on sd %d failed: %s", len, mSd, strerror(errno) );
    return( nBytes );
  }
  virtual int recvfrom( void *msg, int len, SockAddr *addr=0 ) const {
    ssize_t nBytes = 0;
    if ( addr ) {
      struct sockaddr sockAddr;
      socklen_t sockLen;
      nBytes = ::recvfrom( mSd, msg, len, 0, &sockAddr, &sockLen );
      if ( nBytes < 0 ) {
        Debug( 1, "Recvfrom of %d bytes max on sd %d (with address) failed: %s", len, mSd, strerror(errno) );
      }
    } else {
      nBytes = ::recvfrom( mSd, msg, len, 0, NULL, 0 );
      if ( nBytes < 0 )
        Debug( 1, "Recvfrom of %d bytes max on sd %d (no address) failed: %s", len, mSd, strerror(errno) );
    }
    return( nBytes );
  }
};

class UdpInetSocket : virtual public UdpSocket, virtual public InetSocket {
public:
  bool bind( const char *host, const char *serv ) {
    return( InetSocket::bind( host, serv ) );
  }
  bool bind( const char *host, int port ) {
    return( InetSocket::bind( host, port ) );
  }
  bool bind( const char *serv ) {
    return( InetSocket::bind( serv ) );
  }
  bool bind( int port ) {
    return( InetSocket::bind( port ) );
  }

  bool connect( const char *host, const char *serv ) {
    return( InetSocket::connect( host, serv ) );
  }
  bool connect( const char *host, int port ) {
    return( InetSocket::connect( host, port ) );
  }
};

class UdpUnixSocket : virtual public UdpSocket, virtual public UnixSocket {
public:
  bool bind( const char *path ) {
    return( UnixSocket::bind( path ) );
  }

  bool connect( const char *path ) {
    return( UnixSocket::connect( path ) );
  }
};

class UdpInetClient : public UdpInetSocket {
public:
  bool connect( const char *host, const char *serv ) {
    return( UdpInetSocket::connect( host, serv ) );
  }
  bool connect( const char *host, int port ) {
    return( UdpInetSocket::connect( host, port ) );
  }
};

class UdpUnixClient : public UdpUnixSocket {
public:
  bool bind( const char *path ) {
    return( UdpUnixSocket::bind( path ) );
  }

public:
  bool connect( const char *path ) {
    return( UdpUnixSocket::connect( path) );
  }
};

class UdpInetServer : public UdpInetSocket {
public:
  bool bind( const char *host, const char *serv ) {
    return( UdpInetSocket::bind( host, serv ) );
  }
  bool bind( const char *host, int port ) {
    return( UdpInetSocket::bind( host, port ) );
  }
  bool bind( const char *serv ) {
    return( UdpInetSocket::bind( serv ) );
  }
  bool bind( int port ) {
    return( UdpInetSocket::bind( port ) );
  }

protected:
  bool connect( const char *host, const char *serv ) {
    return( UdpInetSocket::connect( host, serv ) );
  }
  bool connect( const char *host, int port ) {
    return( UdpInetSocket::connect( host, port ) );
  }
};

class UdpUnixServer : public UdpUnixSocket {
public:
  bool bind( const char *path ) {
    return( UdpUnixSocket::bind( path ) );
  }

protected:
  bool connect( const char *path ) {
    return( UdpUnixSocket::connect( path ) );
  }
};

class TcpSocket : virtual public Socket {
public:
  TcpSocket() {
  }
  TcpSocket( const TcpSocket &socket, int newSd ) : Socket( socket, newSd ) {
  }

public:
  int getType() const {
    return( SOCK_STREAM );
  }
  const char *getProtocol() const {
    return( "tcp" );
  }
};

class TcpInetSocket : virtual public TcpSocket, virtual public InetSocket {
public:
  TcpInetSocket() {
  }
  TcpInetSocket( const TcpInetSocket &socket, int newSd ) : TcpSocket( socket, newSd ) {
  }
};

class TcpUnixSocket : virtual public TcpSocket, virtual public UnixSocket {
public:
  TcpUnixSocket() {
  }
  TcpUnixSocket( const TcpUnixSocket &socket, int newSd ) : TcpSocket( socket, newSd ) {
  }
};

class TcpInetClient : public TcpInetSocket {
public:
  bool connect( const char *host, const char *serv ) {
    return( TcpInetSocket::connect( host, serv ) );
  }
  bool connect( const char *host, int port ) {
    return( TcpInetSocket::connect( host, port ) );
  }
};

class TcpUnixClient : public TcpUnixSocket {
public:
  bool connect( const char *path ) {
    return( TcpUnixSocket::connect( path) );
  }
};

class TcpInetServer : public TcpInetSocket {
public:
  bool bind( int port ) {
    return( TcpInetSocket::bind( port ) );
  }

public:
  bool isListening() const { return( Socket::isListening() ); }
  bool listen();
  bool accept();
  bool accept( TcpInetSocket *&newSocket );
};

class TcpUnixServer : public TcpUnixSocket {
public:
  bool bind( const char *path ) {
    return( TcpUnixSocket::bind( path ) );
  }

public:
  bool isListening() const { return( Socket::isListening() ); }
  bool listen();
  bool accept();
  bool accept( TcpUnixSocket *&newSocket );
};

class Select {
public:
  typedef std::set<CommsBase *> CommsSet;
  typedef std::vector<CommsBase *> CommsList;

protected:
  CommsSet    mReaders;
  CommsSet    mWriters;
  CommsList     mReadable;
  CommsList     mWriteable;
  bool      mHasTimeout;
  struct timeval  mTimeout;
  int       mMaxFd;

public:
  Select();
  explicit Select( struct timeval timeout );
  explicit Select( int timeout );
  explicit Select( double timeout );

  void setTimeout( int timeout );
  void setTimeout( double timeout );
  void setTimeout( struct timeval timeout );
  void clearTimeout();

  void calcMaxFd();

  bool addReader( CommsBase *comms );
  bool deleteReader( CommsBase *comms );
  void clearReaders();

  bool addWriter( CommsBase *comms );
  bool deleteWriter( CommsBase *comms );
  void clearWriters();

  int wait();

  const CommsList &getReadable() const;
  const CommsList &getWriteable() const;
};

#endif // ZM_COMMS_H
