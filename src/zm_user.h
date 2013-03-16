/*
 * ZoneMinder User Class Interface, $Date$, $Revision$
 * Copyright (C) 2001-2008 Philip Coombes
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/  

#include "zm.h"
#include "zm_db.h"

#ifndef ZM_USER_H
#define ZM_USER_H

#if HAVE_GNUTLS_OPENSSL_H
#include <gnutls/openssl.h>
#endif
#if HAVE_GNUTLS_GNUTLS_H
#include <gnutls/gnutls.h>
#endif

#if HAVE_GCRYPT_H
#include <gcrypt.h>
#elif HAVE_LIBCRYPTO
#include <openssl/md5.h>
#endif // HAVE_L || HAVE_LIBCRYPTO

class User
{
public:
	typedef enum { PERM_NONE=1, PERM_VIEW, PERM_EDIT } Permission;

protected:
	char username[32+1];
	char password[64+1];
	bool enabled;
	Permission stream;
	Permission events;
	Permission control;
	Permission monitors;
	Permission system;
	int *monitor_ids;

public:
	User();
	User( MYSQL_ROW &dbrow );
	~User();

	const char *getUsername() const { return( username ); }
	const char *getPassword() const { return( password ); }
	bool isEnabled() const { return( enabled ); }
	Permission getStream() const { return( stream ); }
	Permission getEvents() const { return( events ); }
	Permission getControl() const { return( control ); }
	Permission getMonitors() const { return( monitors ); }
	Permission getSystem() const { return( system ); }
	bool canAccess( int monitor_id );
};

User *zmLoadUser( const char *username, const char *password=0 );
User *zmLoadAuthUser( const char *auth, bool use_remote_addr );

#endif // ZM_USER_H
