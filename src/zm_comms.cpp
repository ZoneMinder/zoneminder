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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

#include "zm_comms.h"

#include "zm_logger.h"

#include <errno.h>
#include <fcntl.h>
#include <stdarg.h>
//#include <memory.h>
#include <alloca.h>
#include <string.h>
//#include <unistd.h>
#include <sys/ioctl.h>
#include <sys/param.h>
//#include <sys/socket.h>
//#include <netinet/in.h>
#include <netinet/tcp.h>

int CommsBase::readV( int iovcnt, /* const void *, int, */ ... )
{
	va_list arg_ptr;
	//struct iovec iov[iovcnt];
	struct iovec *iov = (struct iovec *)alloca( sizeof(struct iovec)*iovcnt );

	va_start( arg_ptr, iovcnt );
	for ( int i = 0; i < iovcnt; i++ )
	{
		iov[i].iov_base = va_arg( arg_ptr, void * );
		iov[i].iov_len = va_arg( arg_ptr, int );
	}
	va_end( arg_ptr );

    int nBytes = ::readv( mRd, iov, iovcnt );
    if ( nBytes < 0 )
        Debug( 1, "Readv of %d buffers max on rd %d failed: %s", iovcnt, mRd, strerror(errno) );
    return( nBytes );
}

int CommsBase::writeV( int iovcnt, /* const void *, int, */ ... )
{
	va_list arg_ptr;
	//struct iovec iov[iovcnt];
	struct iovec *iov = (struct iovec *)alloca( sizeof(struct iovec)*iovcnt );

	va_start( arg_ptr, iovcnt );
	for ( int i = 0; i < iovcnt; i++ )
	{
		iov[i].iov_base = va_arg( arg_ptr, void * );
		iov[i].iov_len = va_arg( arg_ptr, int );
	}
	va_end( arg_ptr );

    ssize_t nBytes = ::writev( mWd, iov, iovcnt );
    if ( nBytes < 0 )
        Debug( 1, "Writev of %d buffers on wd %d failed: %s", iovcnt, mWd, strerror(errno) );
    return( nBytes );
}

bool Pipe::open()
{
	if ( ::pipe( mFd ) < 0 )
	{
		Error( "pipe(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}

	return( true );
}

bool Pipe::close()
{
	if ( mFd[0] > -1 ) ::close( mFd[0] );
	mFd[0] = -1;
	if ( mFd[1] > -1 ) ::close( mFd[1] );
	mFd[1] = -1;
	return( true );
}

bool Pipe::setBlocking( bool blocking )
{
	int flags;

	/* Now set it for non-blocking I/O */
	if ( (flags = fcntl( mFd[1], F_GETFL )) < 0 )
	{
		Error( "fcntl(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}
	if ( blocking )
	{
		flags &= ~O_NONBLOCK;
	}
	else
	{
		flags |= O_NONBLOCK;
	}
	if ( fcntl( mFd[1], F_SETFL, flags ) < 0 )
	{
		Error( "fcntl(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}

	return( true );
}

SockAddr::SockAddr( const struct sockaddr *addr ) : mAddr( addr )
{
}

SockAddr *SockAddr::newSockAddr( const struct sockaddr &addr, socklen_t len )
{
    if ( addr.sa_family == AF_INET && len == SockAddrInet::addrSize() )
    {
        return( new SockAddrInet( (const struct sockaddr_in *)&addr ) );
    }
    else if ( addr.sa_family == AF_UNIX && len == SockAddrUnix::addrSize() )
    {
        return( new SockAddrUnix( (const struct sockaddr_un *)&addr ) );
    }
    Error( "Unable to create new SockAddr from addr family %d with size %d", addr.sa_family, len );
    return( 0 );
}

SockAddr *SockAddr::newSockAddr( const SockAddr *addr )
{
    if ( !addr )
        return( 0 );

    if ( addr->getDomain() == AF_INET )
    {
        return( new SockAddrInet( *(SockAddrInet *)addr ) );
    }
    else if ( addr->getDomain() == AF_UNIX )
    {
        return( new SockAddrUnix( *(SockAddrUnix *)addr ) );
    }
    Error( "Unable to create new SockAddr from addr family %d", addr->getDomain() );
    return( 0 );
}

SockAddrInet::SockAddrInet() : SockAddr( (struct sockaddr *)&mAddrIn )
{
}

bool SockAddrInet::resolve( const char *host, const char *serv, const char *proto )
{
    memset( &mAddrIn, 0, sizeof(mAddrIn) );

    struct hostent *hostent=0;
    if ( !(hostent = ::gethostbyname( host ) ) )
    {
        Error( "gethostbyname( %s ), h_errno = %d", host, h_errno );
        return( false );
    }

    struct servent *servent=0;
    if ( !(servent = ::getservbyname( serv, proto ) ) )
    {
        Error( "getservbyname( %s ), errno = %d, error = %s", serv, errno, strerror(errno) );
        return( false );
    }

    mAddrIn.sin_port = servent->s_port;
    mAddrIn.sin_family = AF_INET;
    mAddrIn.sin_addr.s_addr = ((struct in_addr *)(hostent->h_addr))->s_addr;

    return( true );
}

bool SockAddrInet::resolve( const char *host, int port, const char *proto )
{
    memset( &mAddrIn, 0, sizeof(mAddrIn) );

    struct hostent *hostent=0;
    if ( !(hostent = ::gethostbyname( host ) ) )
    {
        Error( "gethostbyname( %s ), h_errno = %d", host, h_errno );
        return( false );
    }

    mAddrIn.sin_port = htons(port);
    mAddrIn.sin_family = AF_INET;
    mAddrIn.sin_addr.s_addr = ((struct in_addr *)(hostent->h_addr))->s_addr;
    return( true );
}

bool SockAddrInet::resolve( const char *serv, const char *proto )
{
    memset( &mAddrIn, 0, sizeof(mAddrIn) );

    struct servent *servent=0;
    if ( !(servent = ::getservbyname( serv, proto ) ) )
    {
        Error( "getservbyname( %s ), errno = %d, error = %s", serv, errno, strerror(errno) );
        return( false );
    }

    mAddrIn.sin_port = servent->s_port;
    mAddrIn.sin_family = AF_INET;
	mAddrIn.sin_addr.s_addr = INADDR_ANY;

    return( true );
}

bool SockAddrInet::resolve( int port, const char *proto )
{
    memset( &mAddrIn, 0, sizeof(mAddrIn) );

    mAddrIn.sin_port = htons(port);
    mAddrIn.sin_family = AF_INET;
	mAddrIn.sin_addr.s_addr = INADDR_ANY;

    return( true );
}

SockAddrUnix::SockAddrUnix() : SockAddr( (struct sockaddr *)&mAddrUn )
{
}

bool SockAddrUnix::resolve( const char *path, const char *proto )
{
    memset( &mAddrUn, 0, sizeof(mAddrUn) );

    strncpy( mAddrUn.sun_path, path, sizeof(mAddrUn.sun_path) );
    mAddrUn.sun_family = AF_UNIX;

    return( true );
}

bool Socket::socket()
{
    if ( mSd >= 0 )
        return( true );

	if ( (mSd = ::socket( getDomain(), getType(), 0 ) ) < 0 )
	{
		Error( "socket(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}

	int val = 1;

	(void)::setsockopt( mSd, SOL_SOCKET, SO_REUSEADDR, &val, sizeof(val) );
	(void)::setsockopt( mSd, SOL_SOCKET, SO_KEEPALIVE, &val, sizeof(val) );

    mState = DISCONNECTED;

	return( true );
}

bool Socket::connect()
{
	if ( !socket() ) 
		return( false );

	if ( ::connect( mSd, mRemoteAddr->getAddr(), getAddrSize() ) == -1 )
	{
		Error( "connect(), errno = %d, error = %s", errno, strerror(errno) );
		close();
		return( false );
	}

    mState = CONNECTED;

	return( true );
}

bool Socket::bind()
{
	if ( !socket() )
		return( false );

	if ( ::bind( mSd, mLocalAddr->getAddr(), getAddrSize() ) == -1 )
	{
		Error( "bind(), errno = %d, error = %s", errno, strerror(errno) );
		close();
		return( false );
	}
    return( true );
}

bool Socket::listen()
{
	if ( ::listen( mSd, SOMAXCONN ) == -1 )
	{
		Error( "listen(), errno = %d, error = %s", errno, strerror(errno) );
		close();
		return( false );
	}

    mState = LISTENING;

	return( true );
}

bool Socket::accept()
{
    struct sockaddr *rem_addr = mLocalAddr->getTempAddr();
    socklen_t rem_addr_size = getAddrSize();

    int newSd = -1;
    if ( (newSd = ::accept( mSd, rem_addr, &rem_addr_size )) == -1 )
    {
        Error( "accept(), errno = %d, error = %s", errno, strerror(errno) );
        close();
        return( false );
    }

	::close( mSd );
	mSd = newSd;

    mState = CONNECTED;

	return( true );
}

bool Socket::accept( int &newSd )
{
    struct sockaddr *rem_addr = mLocalAddr->getTempAddr();
    socklen_t rem_addr_size = getAddrSize();

    newSd = -1;
    if ( (newSd = ::accept( mSd, rem_addr, &rem_addr_size )) == -1 )
    {
        Error( "accept(), errno = %d, error = %s", errno, strerror(errno) );
        close();
        return( false );
    }

	return( true );
}

bool Socket::close()
{
	if ( mSd > -1 ) ::close( mSd );
	mSd = -1;
    mState = CLOSED;
	return( true );
}

int Socket::bytesToRead() const
{
	int bytes_to_read = 0;

	if ( ioctl( mSd, FIONREAD, &bytes_to_read ) < 0 )
	{
		Error( "ioctl(), errno = %d, error = %s", errno, strerror(errno) );
		return( -1 );
	}
	return( bytes_to_read );
}

bool Socket::getBlocking( bool &blocking )
{
	int flags;

	if ( (flags = fcntl( mSd, F_GETFL )) < 0 )
	{
		Error( "fcntl(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}
	blocking = (flags & O_NONBLOCK);
	return( true );
}

bool Socket::setBlocking( bool blocking )
{
#if 0
	// ioctl is apparently not recommended
	int ioctl_arg = !blocking;
	if ( ioctl( mSd, FIONBIO, &ioctl_arg ) < 0 )
	{
		Error( "ioctl(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}
	return( true );
#endif

	int flags;

	/* Now set it for non-blocking I/O */
	if ( (flags = fcntl( mSd, F_GETFL )) < 0 )
	{
		Error( "fcntl(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}
	if ( blocking )
	{
		flags &= ~O_NONBLOCK;
	}
	else
	{
		flags |= O_NONBLOCK;
	}
	if ( fcntl( mSd, F_SETFL, flags ) < 0 )
	{
		Error( "fcntl(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}

	return( true );
}

bool Socket::getSendBufferSize( int &buffersize ) const
{
	socklen_t optlen = sizeof(buffersize);
	if ( getsockopt( mSd, SOL_SOCKET, SO_SNDBUF, &buffersize, &optlen ) < 0 )
	{
		Error( "getsockopt(), errno = %d, error = %s", errno, strerror(errno) );
		return( -1 );
	}
	return( buffersize );
}

bool Socket::getRecvBufferSize( int &buffersize ) const
{
	socklen_t optlen = sizeof(buffersize);
	if ( getsockopt( mSd, SOL_SOCKET, SO_RCVBUF, &buffersize, &optlen ) < 0 )
	{
		Error( "getsockopt(), errno = %d, error = %s", errno, strerror(errno) );
		return( -1 );
	}
	return( buffersize );
}

bool Socket::setSendBufferSize( int buffersize )
{
	if ( setsockopt( mSd, SOL_SOCKET, SO_SNDBUF, (char *)&buffersize, sizeof(buffersize)) < 0 )
	{
		Error( "setsockopt(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}
	return( true );
}

bool Socket::setRecvBufferSize( int buffersize )
{
	if ( setsockopt( mSd, SOL_SOCKET, SO_RCVBUF, (char *)&buffersize, sizeof(buffersize)) < 0 )
	{
		Error( "setsockopt(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}
	return( true );
}

bool Socket::getRouting( bool &route ) const
{
	int dontRoute;
	socklen_t optlen = sizeof(dontRoute);
	if ( getsockopt( mSd, SOL_SOCKET, SO_DONTROUTE, &dontRoute, &optlen ) < 0 )
	{
		Error( "getsockopt(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}
	route = !dontRoute;
	return( true );
}

bool Socket::setRouting( bool route )
{
	int dontRoute = !route;
	if ( setsockopt( mSd, SOL_SOCKET, SO_DONTROUTE, (char *)&dontRoute, sizeof(dontRoute)) < 0 )
	{
		Error( "setsockopt(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}
	return( true );
}

bool Socket::getNoDelay( bool &nodelay ) const
{
	int int_nodelay;
	socklen_t optlen = sizeof(int_nodelay);
	if ( getsockopt( mSd, IPPROTO_TCP, TCP_NODELAY, &int_nodelay, &optlen ) < 0 )
	{
		Error( "getsockopt(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}
	nodelay = int_nodelay;
	return( true );
}

bool Socket::setNoDelay( bool nodelay )
{
	int int_nodelay = nodelay;

	if ( setsockopt( mSd, IPPROTO_TCP, TCP_NODELAY, (char *)&int_nodelay, sizeof(int_nodelay)) < 0 )
	{
		Error( "setsockopt(), errno = %d, error = %s", errno, strerror(errno) );
		return( false );
	}
	return( true );
}

bool TcpInetServer::listen()
{
    return( Socket::listen() );
}

bool TcpInetServer::accept()
{
    return( Socket::accept() );
}

bool TcpInetServer::accept( TcpInetSocket *&newSocket )
{
    int newSd = -1;
    newSocket = 0;

    if ( !Socket::accept( newSd ) )
        return( false );

	newSocket = new TcpInetSocket( *this, newSd );

    return( true );
}

bool TcpUnixServer::accept( TcpUnixSocket *&newSocket )
{
    int newSd = -1;
    newSocket = 0;

    if ( !Socket::accept( newSd ) )
        return( false );

	newSocket = new TcpUnixSocket( *this, newSd );

    return( true );
}

Select::Select() : mHasTimeout( false ), mMaxFd( -1 )
{
}

Select::Select( struct timeval timeout ) : mMaxFd( -1 )
{
    setTimeout( timeout );
}

Select::Select( int timeout ) : mMaxFd( -1 )
{
    setTimeout( timeout );
}

Select::Select( double timeout ) : mMaxFd( -1 )
{
    setTimeout( timeout );
}

void Select::setTimeout( int timeout )
{
    mTimeout.tv_sec = timeout;
    mTimeout.tv_usec = 0;
    mHasTimeout = true;
}

void Select::setTimeout( double timeout )
{
    mTimeout.tv_sec = int(timeout);
    mTimeout.tv_usec = suseconds_t((timeout-mTimeout.tv_sec)*1000000.0);
    mHasTimeout = true;
}

void Select::setTimeout( struct timeval timeout )
{
    mTimeout = timeout;
    mHasTimeout = true;
}

void Select::clearTimeout()
{
    mHasTimeout = false;
}

void Select::calcMaxFd()
{
    mMaxFd = -1;
    for ( CommsSet::iterator iter = mReaders.begin(); iter != mReaders.end(); iter++ )
        if ( (*iter)->getMaxDesc() > mMaxFd )
            mMaxFd = (*iter)->getMaxDesc();
    for ( CommsSet::iterator iter = mWriters.begin(); iter != mWriters.end(); iter++ )
        if ( (*iter)->getMaxDesc() > mMaxFd )
            mMaxFd = (*iter)->getMaxDesc();
}

bool Select::addReader( CommsBase *comms )
{
    if ( !comms->isOpen() )
    {
        Error( "Unable to add closed reader" );
        return( false );
    }
    std::pair<CommsSet::iterator,bool> result = mReaders.insert( comms );
    if ( result.second )
        if ( comms->getMaxDesc() > mMaxFd )
            mMaxFd = comms->getMaxDesc();
    return( result.second );
}

bool Select::deleteReader( CommsBase *comms )
{
    if ( !comms->isOpen() )
    {
        Error( "Unable to add closed reader" );
        return( false );
    }
    if ( mReaders.erase( comms ) )
    {
        calcMaxFd();
        return( true );
    }
    return( false );
}

void Select::clearReaders()
{
    mReaders.clear();
    mMaxFd = -1;
}

bool Select::addWriter( CommsBase *comms )
{
    std::pair<CommsSet::iterator,bool> result = mWriters.insert( comms );
    if ( result.second )
        if ( comms->getMaxDesc() > mMaxFd )
            mMaxFd = comms->getMaxDesc();
    return( result.second );
}

bool Select::deleteWriter( CommsBase *comms )
{
    if ( mWriters.erase( comms ) )
    {
        calcMaxFd();
        return( true );
    }
    return( false );
}

void Select::clearWriters()
{
    mWriters.clear();
    mMaxFd = -1;
}

int Select::wait()
{
    struct timeval tempTimeout = mTimeout;
    struct timeval *selectTimeout = mHasTimeout?&tempTimeout:NULL;

    fd_set rfds;
    fd_set wfds;

    mReadable.clear();
    FD_ZERO(&rfds);
    for ( CommsSet::iterator iter = mReaders.begin(); iter != mReaders.end(); iter++ )
        FD_SET((*iter)->getReadDesc(),&rfds);

    mWriteable.clear();
    FD_ZERO(&wfds);
    for ( CommsSet::iterator iter = mWriters.begin(); iter != mWriters.end(); iter++ )
        FD_SET((*iter)->getWriteDesc(),&wfds);

    int nFound = select( mMaxFd+1, &rfds, &wfds, NULL, selectTimeout );
    if( nFound == 0 )
    {
        Debug( 1, "Select timed out" );
    }
    else if ( nFound < 0)
    {
        Error( "Select error: %s", strerror(errno) );
    }
    else
    {
        for ( CommsSet::iterator iter = mReaders.begin(); iter != mReaders.end(); iter++ )
            if ( FD_ISSET((*iter)->getReadDesc(),&rfds) )
                mReadable.push_back( *iter );
        for ( CommsSet::iterator iter = mWriters.begin(); iter != mWriters.end(); iter++ )
            if ( FD_ISSET((*iter)->getWriteDesc(),&rfds) )
                mWriteable.push_back( *iter );
    }
    return( nFound );
}

const Select::CommsList &Select::getReadable() const
{
    return( mReadable );
}

const Select::CommsList &Select::getWriteable() const
{
    return( mWriteable );
}
