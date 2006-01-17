//
// ZoneMinder Communications Class Implementation, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

#include <errno.h>
#include <fcntl.h>
#include <stdarg.h>
#include <memory.h>
#include <alloca.h>
#include <unistd.h>
#include <sys/ioctl.h>
#include <sys/param.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <netinet/tcp.h>

#include "zm.h"
#include "zm_db.h"
#include "zm_comms.h"

bool CommsBase::Terminate()
{
	if ( IsOpen() )
	{
		Close();
	}
	return( true );
}

bool CommsBase::Reopen()
{
	if ( !Close() )
		return( false );
	if ( !Open() )
		return( false );

	return( true );
}

int CommsBase::ReadV( int iovcnt, /* const void *, int, */ ... )
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

	return( ::readv( rd, iov, iovcnt ) );
}

int CommsBase::WriteV( int iovcnt, /* const void *, int, */ ... )
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

	return( ::writev( wd, iov, iovcnt ) );
}

bool Pipe::Open()
{
	if ( pipe( fd ) < 0 )
	{
		Error(( "pipe(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}

	return( true );
}

bool Pipe::Close()
{
	if ( fd[0] > -1 ) close( fd[0] );
	fd[0] = -1;
	if ( fd[1] > -1 ) close( fd[1] );
	fd[1] = -1;
	return( true );
}

bool Pipe::SetBlocking( bool blocking )
{
	int flags;

	/* Now set it for non-blocking I/O */
	if ( (flags = fcntl( fd[1], F_GETFL )) < 0 )
	{
		Error(( "fcntl(), errno = %d, error = %s", errno, strerror(errno) ));
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
	if ( fcntl( fd[1], F_SETFL, flags ) < 0 )
	{
		Error(( "fcntl(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}

	return( true );
}

bool SocketBase::Socket()
{
	if ( (sd = ::socket( AF_INET, SOCK_STREAM, 0 ) ) < 0 )
	{
		Error(( "socket(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}

	int val = 1;

	(void)::setsockopt( sd, SOL_SOCKET, SO_REUSEADDR, &val, sizeof(val) );
	(void)::setsockopt( sd, SOL_SOCKET, SO_KEEPALIVE, &val, sizeof(val) );

	return( true );
}

bool SocketBase::Close()
{
	if ( sd > -1 ) close( sd );
	sd = -1;
	return( true );
}

int SocketBase::BytesToRead() const
{
	int bytes_to_read = 0;

	if ( ioctl( sd, FIONREAD, &bytes_to_read ) < 0 )
	{
		Error(( "ioctl(), errno = %d, error = %s", errno, strerror(errno) ));
		return( -1 );
	}
	return( bytes_to_read );
}

bool SocketBase::GetBlocking( bool &blocking )
{
	int flags;

	if ( (flags = fcntl( sd, F_GETFL )) < 0 )
	{
		Error(( "fcntl(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}
	blocking = (flags & O_NONBLOCK);
	return( true );
}

bool SocketBase::SetBlocking( bool blocking )
{
#if 0
	// ioctl is apparently not recommended
	int ioctl_arg = !blocking;
	if ( ioctl( sd, FIONBIO, &ioctl_arg ) < 0 )
	{
		Error(( "ioctl(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}
	return( true );
#endif

	int flags;

	/* Now set it for non-blocking I/O */
	if ( (flags = fcntl( sd, F_GETFL )) < 0 )
	{
		Error(( "fcntl(), errno = %d, error = %s", errno, strerror(errno) ));
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
	if ( fcntl( sd, F_SETFL, flags ) < 0 )
	{
		Error(( "fcntl(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}

	return( true );
}

bool SocketBase::GetSendBufferSize( int &buffersize ) const
{
	socklen_t optlen = sizeof(buffersize);
	if ( getsockopt( sd, SOL_SOCKET, SO_SNDBUF, &buffersize, &optlen ) < 0 )
	{
		Error(( "getsockopt(), errno = %d, error = %s", errno, strerror(errno) ));
		return( -1 );
	}
	return( buffersize );
}

bool SocketBase::GetRecvBufferSize( int &buffersize ) const
{
	socklen_t optlen = sizeof(buffersize);
	if ( getsockopt( sd, SOL_SOCKET, SO_RCVBUF, &buffersize, &optlen ) < 0 )
	{
		Error(( "getsockopt(), errno = %d, error = %s", errno, strerror(errno) ));
		return( -1 );
	}
	return( buffersize );
}

bool SocketBase::SetSendBufferSize( int buffersize )
{
	if ( setsockopt( sd, SOL_SOCKET, SO_SNDBUF, (char *)&buffersize, sizeof(buffersize)) < 0 )
	{
		Error(( "setsockopt(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}
	return( true );
}

bool SocketBase::SetRecvBufferSize( int buffersize )
{
	if ( setsockopt( sd, SOL_SOCKET, SO_RCVBUF, (char *)&buffersize, sizeof(buffersize)) < 0 )
	{
		Error(( "setsockopt(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}
	return( true );
}

bool SocketBase::GetRouting( bool &route ) const
{
	int dontroute;
	socklen_t optlen = sizeof(dontroute);
	if ( getsockopt( sd, SOL_SOCKET, SO_DONTROUTE, &dontroute, &optlen ) < 0 )
	{
		Error(( "getsockopt(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}
	route = !dontroute;
	return( true );
}

bool SocketBase::SetRouting( bool route )
{
	int dontroute = !route;
	if ( setsockopt( sd, SOL_SOCKET, SO_DONTROUTE, (char *)&dontroute, sizeof(dontroute)) < 0 )
	{
		Error(( "setsockopt(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}
	return( true );
}

bool SocketBase::GetNoDelay( bool &nodelay ) const
{
	int int_nodelay;
	socklen_t optlen = sizeof(int_nodelay);
	if ( getsockopt( sd, IPPROTO_TCP, TCP_NODELAY, &int_nodelay, &optlen ) < 0 )
	{
		Error(( "getsockopt(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}
	nodelay = int_nodelay;
	return( true );
}

bool SocketBase::SetNoDelay( bool nodelay )
{
	int int_nodelay = nodelay;

	if ( setsockopt( sd, IPPROTO_TCP, TCP_NODELAY, (char *)&int_nodelay, sizeof(int_nodelay)) < 0 )
	{
		Error(( "setsockopt(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}
	return( true );
}

bool SocketClient::SetupRemoteHost( const char *host )
{

	struct hostent *p_rem_host=0;

	if ( !(p_rem_host = ::gethostbyname( host ) ) )
	{
		Error(( "gethostbyname( %s ), h_errno = %d", host, h_errno ));
		return( false );
	}

	memcpy( &rem_host, p_rem_host, sizeof(rem_host) );

	return( true );
}

bool SocketClient::SetupRemoteServ( const char *serv, const char *protocol )
{
	struct servent *p_rem_serv=0;

	if ( !(p_rem_serv = ::getservbyname( serv, protocol ) ) )
	{
		Error(( "getservbyname( %s ), errno = %d, error = %s", serv, errno, strerror(errno) ));
		return( false );
	}

	memcpy( &rem_serv, p_rem_serv, sizeof(rem_serv) );

	return( true );
}

SocketClient::SocketClient()
{
	memset( &rem_host, 0, sizeof(rem_host) );
	memset( &rem_serv, 0, sizeof(rem_serv) );
}

bool SocketClient::Open()
{
	if ( !Socket() ) 
		return( false );

	struct sockaddr_in rem_addr;

	memset( &rem_addr, 0, sizeof(rem_addr) );

	rem_addr.sin_port = rem_serv.s_port;
	rem_addr.sin_family = AF_INET;
	rem_addr.sin_addr.s_addr = ((struct in_addr *)(rem_host.h_addr))->s_addr;

	if ( ::connect( sd, (struct sockaddr *)&rem_addr, sizeof(rem_addr) ) == -1 )
	{
		Error(( "connect(), errno = %d, error = %s", errno, strerror(errno) ));
		Close();
		return( false );
	}

	return( true );
}

bool UDPSocket::Socket()
{
	if ( (sd = ::socket( AF_INET, SOCK_DGRAM, 0 ) ) < 0 )
	{
		Error(( "socket(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}

	return( true );
}

bool UDPSocket::Initialise( const char *host, const char *service )
{
	if ( !SetupRemoteHost( host ) ) 
		return( false );
	if ( !SetupRemoteServ( service, "udp" ) ) 
		return( false );

	if ( !Terminate() ) 
		return( false );

	return( true );
}

TCPClient::TCPClient()
{
	state = DISCONNECTED;
}

bool TCPClient::Initialise( const char *host, const char *service )
{
	state = DISCONNECTED;

	if ( !SetupRemoteHost( host ) )
		return( false );
	if ( !SetupRemoteServ( service, "tcp" ) )
		return( false );

	if ( !Terminate() )
		return( false );

	return( true );
}

bool TCPClient::Open()
{
	if ( !SocketClient::Open() )
		return( false );

	state = CONNECTED;

	return( true );
}

bool TCPClient::Close()
{
	if ( !SocketClient::Close() )
		return( false );

	state = DISCONNECTED;

	return( true );
}

bool TCPServer::SetupLocalHost()
{
	char host[MAXHOSTNAMELEN];

	if ( ::gethostname( host, sizeof(host) ) == -1 )
	{
		Error(( "gethostname(), errno = %d, error = %s", errno, strerror(errno) ));
		return( false );
	}

	struct hostent *p_loc_host=0;

	if ( !(p_loc_host = ::gethostbyname( host ) ) )
	{
		Error(( "gethostbyname( %s ), h_errno = %d", host, h_errno ));
		return( false );
	}

	memcpy( &loc_host, p_loc_host, sizeof(loc_host) );

	return( true );
}

bool TCPServer::SetupLocalServ( const char *serv )
{
	struct servent *p_loc_serv=0;

	if ( !(p_loc_serv = ::getservbyname( serv, "tcp" ) ) )
	{
		Error(( "getservbyname( %s ), errno = %d, error = %s", serv, errno, strerror(errno) ));
		return( false );
	}

	memcpy( &loc_serv, p_loc_serv, sizeof(loc_serv) );

	return( true );
}

TCPServer::TCPServer()
{
	state = DISCONNECTED;

	memset( &loc_host, 0, sizeof(loc_host) );
	memset( &loc_serv, 0, sizeof(loc_serv) );
}

TCPServer::TCPServer( const TCPServer &server, int new_sd )
{
	state = server.state;

	memcpy( &loc_host, &server.loc_host, sizeof(loc_host) );
	memcpy( &loc_serv, &server.loc_serv, sizeof(loc_serv) );

	sd = new_sd;
}

bool TCPServer::Initialise( const char *service )
{
	state = DISCONNECTED;

	if ( !SetupLocalHost() )
		return( false );
	if ( !SetupLocalServ( service ) )
		return( false );

	if ( !Terminate() )
		return( false );

	return( true );
}

bool TCPServer::Open()
{
	if ( !Socket() )
		return( false );

	struct sockaddr_in	loc_addr;

	memset( &loc_addr, 0, sizeof(loc_addr) );

	loc_addr.sin_port = loc_serv.s_port;
	loc_addr.sin_family = AF_INET;
	loc_addr.sin_addr.s_addr = INADDR_ANY;

	if ( ::bind( sd, (struct sockaddr *)&loc_addr, sizeof(loc_addr) ) == -1 )
	{
		Error(( "bind(), errno = %d, error = %s", errno, strerror(errno) ));
		Close();
		return( false );
	}

	if ( ::listen( sd, SOMAXCONN ) == -1 )
	{
		Error(( "listen(), errno = %d, error = %s", errno, strerror(errno) ));
		Close();
		return( false );
	}

	state = LISTENING;

	return( true );
}

bool TCPServer::Accept()
{
	struct sockaddr_in rem_addr;
	socklen_t rem_addr_size = sizeof(rem_addr);

	memset( &rem_addr, 0, sizeof(rem_addr) );

	int new_sd=-1;

	if ( (new_sd = accept( sd, (struct sockaddr *)&rem_addr, &rem_addr_size )) == -1 )
	{
		Error(( "accept(), errno = %d, error = %s", errno, strerror(errno) ));
		Close();
		return( false );
	}

	close( sd );

	sd = new_sd;

	state = CONNECTED;

	return( true );
}

bool TCPServer::Accept( TCPServer *&server )
{
	struct sockaddr_in rem_addr;
	socklen_t rem_addr_size = sizeof(rem_addr);

	memset( &rem_addr, 0, sizeof(rem_addr) );

	int new_sd=-1;

	if ( (new_sd = accept( sd, (struct sockaddr *)&rem_addr, &rem_addr_size )) == -1 )
	{
		Error(( "connect(), errno = %d, error = %s", errno, strerror(errno) ));
		Close();
		return( false );
	}

	server = new TCPServer( *this, new_sd );

	return( true );
}

bool TCPServer::Close()
{
	if ( !SocketBase::Close() )
		return( false );

	state = DISCONNECTED;

	return( true );
}

