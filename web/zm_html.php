<?php

//
// ZoneMinder HTML interface file, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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

if ( !$bandwidth )
{
	$bandwidth = "low";
}

ini_set( "session.use_trans_sid", "0" );
ini_set( "session.name", "ZMSESSID" );
//ini_set( "magic_quotes_gpc", "Off" );

session_start();

require_once( 'zm_config.php' );

if ( ZM_OPT_USE_AUTH )
{
	$user = $HTTP_SESSION_VARS[user];
	define( "ZMU_COMMAND", ZMU_PATH." -U $HTTP_SESSION_VARS[username] -P $HTTP_SESSION_VARS[password]" );
}
else
{
	$user = array(
		"Username"=>"admin",
		"Password"=>"",
		"Enabled"=>1,
		"Stream"=>'View',
		"Events"=>'Edit',
		"Monitors"=>'Edit',
		"System"=>'Edit',
	);
	define( "ZMU_COMMAND", ZMU_PATH );
}

require_once( 'zm_funcs.php' );
require_once( 'zm_actions.php' );

if ( !$user )
{
	$view = "login";
}
elseif ( !$view )
{
	$view = "console";
}

switch( $view )
{
	case "login" :
	case "postlogin" :
	case "logout" :
	case "console" :
	case "bandwidth" : 
	case "options" :
	case "optionhelp" :
	case "restarting" :
	case "user" :
	case "cycle" :
	case "montage" :
	case "montagefeed" :
	case "montagestatus" :
	case "watch" :
	case "watchfeed" :
	case "settings" :
	case "watchstatus" :
	case "watchevents" :
	case "events" :
	case "filter" :
	case "filtersave" :
	case "event" :
	case "image" :
	case "stats" :
	case "monitor" :
	case "zones" :
	case "zone" :
	case "video" :
	case "function" :
	case "none" :
	{
		require_once( "zm_html_view_$view.php" );
		break;
	}
	default :
	{
		$view = "error";
	}
}

if ( $view == "error" )
{
	require_once( "zm_html_view_$view.php" );
}
?>
