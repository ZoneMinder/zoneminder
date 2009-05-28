//
// ZoneMinder Remote Camera Class Implementation, $Date$, $Revision$
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

#include "zm_remote_camera.h"

#include "zm_utils.h"

RemoteCamera::RemoteCamera( int p_id, const std::string &p_protocol, const std::string &p_host, const std::string &p_port, const std::string &p_path, int p_width, int p_height, int p_palette, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture ) :
    Camera( p_id, REMOTE_SRC, p_width, p_height, p_palette, p_brightness, p_contrast, p_hue, p_colour, p_capture ),
    protocol( p_protocol ),
    host( p_host ),
    port( p_port ),
    path( p_path ),
    hp( 0 )
{
    if ( path[0] != '/' )
        path = '/'+path;
}

RemoteCamera::~RemoteCamera()
{
}

void RemoteCamera::Initialise()
{
	if( protocol.empty() )
		Fatal( "No protocol specified for remote camera" );

	if( host.empty() )
		Fatal( "No host specified for remote camera" );

	if( port.empty() )
		Fatal( "No port specified for remote camera" );

	//if( path.empty() )
		//Fatal( "No path specified for remote camera" );

	// Cache as much as we can to speed things up
    std::string::size_type authIndex = host.find( '@' );

	if ( authIndex != std::string::npos )
	{
        auth = host.substr( 0, authIndex );
        host.erase( 0, authIndex+1 );
		auth64 = base64Encode( auth );
	}

	if ( !hp )
	{
		if ( !(hp = gethostbyname(host.c_str())) )
		{
			Fatal( "Can't gethostbyname(%s): %s", host.c_str(), strerror(h_errno) );
		}
		memcpy((char *)&sa.sin_addr, (char *)hp->h_addr, hp->h_length);
		sa.sin_family = hp->h_addrtype;
		sa.sin_port = htons(atoi(port.c_str()));
	}
}

