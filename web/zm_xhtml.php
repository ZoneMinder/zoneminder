<?php
//
// ZoneMinder xHTML interface file, $Date$, $Revision$
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

$bandwidth = "phone";

//ini_set( "magic_quotes_gpc", "Off" );

require_once( 'zm_config.php' );

if ( ZM_OPT_USE_AUTH )
{
	session_start();
	if ( isset( $_SESSION['user'] ) )
	{
		$user = $_SESSION['user'];
	}
	else
	{
		unset( $user );
	}
}
else
{
	$user = $default_user;
}

require_once( 'zm_lang.php' );
require_once( 'zm_funcs.php' );
require_once( 'zm_xhtml_config.php' );

noCacheHeaders();
header("Content-type: application/xhtml+xml" );

echo( '<?xml version="1.0" encoding="iso-8859-1"?>'."\n" ); 
echo( '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">'."\n" );

ob_start();

if ( !isset($user) && ZM_OPT_USE_AUTH )
{
	if ( ZM_AUTH_TYPE == "remote" && !empty( $_SERVER['REMOTE_USER'] ) )
	{
		$view = "postlogin";
		$action = "login";
		$username = $_SERVER['REMOTE_USER'];
	}
}

require_once( 'zm_actions.php' );

if ( !isset($user) )
{
	$view = "login";
}
elseif ( !isset($view) )
{
	$view = "console";
}

switch( $view )
{
	case "console" :
	case "cycle" :
	case "devices" :
	case "event" :
	case "eventdetails" :
	case "events" :
	case "filter" :
	case "frame" :
	case "function" :
	case "login" :
	case "logout" :
	case "monitor" :
	case "montage" :
	case "postlogin" :
	case "settings" :
	case "state" :
	case "video" :
	case "watch" :
	case "none" :
	{
		require_once( "zm_".$format."_view_".$view.".php" );
		break;
	}
	default :
	{
		$view = "error";
	}
}

if ( $view == "error" )
{
	require_once( "zm_".$format."_view_".$view.".php" );
}

//$fp = fopen( "/tmp/output.html", "w" );
//fwrite( $fp, ob_get_contents() );
//fclose( $fp );
ob_end_flush();

?>
