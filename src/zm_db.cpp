//
// ZoneMinder MySQL Implementation, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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

#include <stdlib.h>

#include "zm.h"
#include "zm_db.h"

MYSQL dbconn;

void zmDbConnect()
{
	if ( !mysql_init( &dbconn ) )
	{
		Error(( "Can't initialise structure: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	if ( !mysql_real_connect( &dbconn, ZM_DB_SERVER, ZM_DB_USER, ZM_DB_PASS, 0, 0, 0, 0 ) )
	{
		Error(( "Can't connect to server: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
	if ( mysql_select_db( &dbconn, ZM_DB_NAME ) )
	{
		Error(( "Can't select database: %s", mysql_error( &dbconn ) ));
		exit( mysql_errno( &dbconn ) );
	}
}

