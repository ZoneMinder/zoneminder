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

$bandwidth = "phone";

//ini_set( "magic_quotes_gpc", "Off" );

require_once( 'zm_config.php' );

if ( ZM_OPT_USE_AUTH )
{
	session_start();
	if ( isset( $HTTP_SESSION_VARS['user'] ) )
	{
		$user = $HTTP_SESSION_VARS['user'];
		define( "ZMU_COMMAND", ZMU_PATH." -U ".$HTTP_SESSION_VARS['username']." -P ".$HTTP_SESSION_VARS['password'] );
	}
	else
	{
		unset( $user );
	}
}
else
{
	$user = array(
		"Username"=>"admin",
		"Password"=>"",
		"Language"=>"",
		"Enabled"=>1,
		"Stream"=>'View',
		"Events"=>'Edit',
		"Monitors"=>'Edit',
		"System"=>'Edit',
	);
	define( "ZMU_COMMAND", ZMU_PATH );
}

require_once( 'zm_lang.php' );
require_once( 'zm_funcs.php' );
require_once( 'zm_actions.php' );

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");         // HTTP/1.0
header("Content-type: application/xhtml+xml" );

echo( '<?xml version="1.0" encoding="iso-8859-1"?>'."\n" ); 
echo( '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">'."\n" );

ob_start();

$rates = array(
	"10000" => "100x",
	"5000" => "50x",
	"2500" => "25x",
	"1000" => "10x",
	"400" => "4x",
	"200" => "2x",
	"100" => $zmSlangReal,
	"50" => "1/2x",
	"25" => "1/4x",
);

$scales = array(
	"400" => "4x",
	"300" => "3x",
	"200" => "2x",
	"150" => "1.5x",
	"100" => $zmSlangActual,
	"75" => "3/4x",
	"50" => "1/2x",
	"33" => "1/3x",
	"25" => "1/4x",
);

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
	case "login" :
	case "postlogin" :
	case "logout" :
	case "console" :
	case "state" :
	case "cycle" :
	case "watch" :
	case "montage" :
	case "settings" :
	case "events" :
	case "filter" :
	case "event" :
	case "frame" :
	case "frames" :
	case "monitor" :
	case "video" :
	case "function" :
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

$fp = fopen( "/tmp/output.html", "w" );
fwrite( $fp, ob_get_contents() );
fclose( $fp );
ob_end_flush();

?>
