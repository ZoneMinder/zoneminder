//
// ZoneMinder Communicatoions Class Interface, $Date$, $Revision$
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

#ifndef ZM_COMMS_H
#define ZM_COMMS_H

#include <netdb.h>

#include "zm.h"
#include "zm_debug.h"

class CommsBase
{
protected:
	const int	&rd;
	const int	&wd;

protected:
	CommsBase( int &p_rd, int &p_wd ) : rd( p_rd ), wd( p_wd )
	{
	}
	virtual ~CommsBase()
	{
	}

protected:
	virtual bool Terminate();

	virtual bool Open()=0;
	virtual bool Close()=0;

	virtual bool IsOpen() const=0;

	virtual bool SetBlocking( bool blocking )=0;

public:
	virtual bool Reopen();

	virtual int Read( void *msg, int len )
	{
		return( ::read( rd, msg, len ) );
	}
	virtual int Write( const void *msg, int len )
	{
		return( ::write( wd, msg, len ) );
	}
	virtual int ReadV( const struct iovec *iov, int iovcnt )
	{
		return( ::readv( rd, iov, iovcnt ) );
	}
	virtual int WriteV( const struct iovec *iov, int iovcnt )
	{
		return( ::writev( wd, iov, iovcnt ) );
	}
	virtual int ReadV( int iovcnt, /* const void *msg1, int len1, */ ... );
	virtual int WriteV( int iovcnt, /* const void *msg1, int len1, */ ... );
};

class Pipe : public CommsBase
{
protected:
	int fd[2];

public:
	Pipe() : CommsBase( fd[0], fd[1] )
	{
		fd[0] = -1;
		fd[1] = -1;
	}
	~Pipe()
	{
		Terminate();
	}

public:
	bool Open();
	bool Close();

	bool IsOpen() const
	{
		return( fd[0] != -1 && fd[1] != -1 );
	}
	int GetReadDesc() const
	{
		return( fd[0] );
	}
	int GetWriteDesc() const
	{
		return( fd[1] );
	}

	bool SetBlocking( bool blocking );
};

class SocketBase : public CommsBase
{
protected:
	int	sd;

protected:
	SocketBase() : CommsBase( sd, sd )
	{
		sd = -1;
	}
	~SocketBase()
	{
		Terminate();
	}

	virtual bool Socket();

public:
	virtual bool Open()=0;
	virtual bool Close();

	virtual int Send( const void *msg, int len )
	{
		return( ::send( sd, msg, len, 0 ) );
	}
	virtual int Recv( void *msg, int len )
	{
		return( ::recv( sd, msg, len, 0 ) );
	}
	int BytesToRead() const;

	virtual bool IsOpen() const
	{
		return( sd != -1 );
	}
	int GetDesc() const
	{
		return( sd );
	}

	bool GetBlocking( bool &blocking );
	bool SetBlocking( bool blocking );

	bool GetSendBufferSize( int & ) const;
	bool GetRecvBufferSize( int & ) const;

	bool SetSendBufferSize( int );
	bool SetRecvBufferSize( int );

	bool GetRouting( bool & ) const;
	bool SetRouting( bool );

	bool GetNoDelay( bool & ) const;
	bool SetNoDelay( bool );

	virtual const char *GetHostName() const=0;
	virtual const char *GetServName() const=0;
};

class SocketClient : public SocketBase
{
protected:
	struct hostent		rem_host;
	struct servent		rem_serv;

protected:
	bool SetupRemoteHost( const char *host );
	bool SetupRemoteServ( const char *serv, const char *protocol );

public:
	SocketClient();

	virtual bool Initialise( const char *host, const char *service )=0;

	virtual bool Open();

	const char *GetHostName() const
	{
		return( rem_host.h_name );
	}
	const char *GetServName() const
	{
		return( rem_serv.s_name );
	}
};

class UDPSocket : public SocketClient
{
protected:
	virtual bool Socket();

public:
	virtual bool Initialise( const char *host, const char *service );
};

class TCPClient : public SocketClient
{
public:
	typedef enum { DISCONNECTED, CONNECTED } ConnectionState;

protected:
	ConnectionState		state;

public:
	TCPClient();

	virtual bool Initialise( const char *host, const char *service );

	virtual bool Open();
	virtual bool Close();

	ConnectionState GetConnectionState() const
	{
		return( state );
	}
	bool IsDisconnected() const
	{
		return( state == DISCONNECTED );
	}
	bool IsConnected() const
	{
		return( state == CONNECTED );
	}
};

class TCPServer : public SocketBase
{
public:
	typedef enum { DISCONNECTED, LISTENING, CONNECTED } ConnectionState;

protected:
	ConnectionState		state;

protected:
	struct hostent		loc_host;
	struct servent		loc_serv;

protected:
	bool SetupLocalHost();
	bool SetupLocalServ( const char *serv );

public:
	TCPServer( const TCPServer &, int );

public:
	TCPServer();

	virtual bool Initialise( const char *service );

	virtual bool Open();
	bool Accept();
	bool Accept( TCPServer *& );
	virtual bool Close();

	ConnectionState GetConnectionState() const
	{
		return( state );
	}
	bool IsDisconnected() const
	{
		return( state == DISCONNECTED );
	}
	bool IsListening() const
	{
		return( state == LISTENING );
	}
	bool IsConnected() const
	{
		return( state == CONNECTED );
	}
	const char *GetHostName() const
	{
		return( loc_host.h_name );
	}
	const char *GetServName() const
	{
		return( loc_serv.s_name );
	}
};

#endif // ZM_COMMS_H
