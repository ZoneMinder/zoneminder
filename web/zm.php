<?php
//
// ZoneMinder main web interface file, $Date$, $Revision$
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

error_reporting (E_ALL ^ E_NOTICE);
import_request_variables( "GPC" );

$debug = false;
if ( $debug )
{
	// Use these for debugging, though not both at once!
	phpinfo( INFO_VARIABLES );
	//error_reporting( E_ALL );
}

// Use new style autoglobals where possible
if ( version_compare( phpversion(), "4.1.0", "<") )
{
  	$_SERVER = &$HTTP_SERVER_VARS;
  	$_SESSION = &$HTTP_SESSION_VARS;
}

// Useful debugging lines for mobile devices
//ob_start();
//phpinfo( INFO_VARIABLES );
//$fp = fopen( "/tmp/env.html", "w" );
//fwrite( $fp, ob_get_contents() );
//fclose( $fp );
//ob_end_clean();

if ( !isset($PHP_SELF) )
{
	$PHP_SELF = $_SERVER['PHP_SELF'];
}

if ( empty($format) )
{
	$wurfl_file = "./wurfl_class.php";
	if ( file_exists( $wurfl_file ) )
	{
		require_once( $wurfl_file );
		$wurfl = new wurfl_class( $wurfl, $wurfl_agents );
		// Set the user agent
		$wurfl->GetDeviceCapabilitiesFromAgent($_SERVER['HTTP_USER_AGENT']);
	
		//print_r( $wurfl->wurfl_agent );
		if ( $wurfl->wurfl_agent )
		{
			if ( $wurfl->getDeviceCapability( 'html_wi_oma_xhtmlmp_1_0' ) )
			{
				$format = "xhtml";
				$cookies = false;
				$device['width'] = $wurfl->getDeviceCapability( 'resolution_width' );
				$device['height'] = $wurfl->getDeviceCapability( 'resolution_height' );
			}
			elseif ( $wurfl->getDeviceCapability( 'wml_1_3' ) )
			{
				$format = "wml";
				$cookies = false;
				$device['width'] = $wurfl->getDeviceCapability( 'resolution_width' );
				$device['height'] = $wurfl->getDeviceCapability( 'resolution_height' );
			}
		}
		else
		{
			$format = "html";
			$cookies = true;
		}
	}
	else
	{
		// This is an example of using fixed device strings to just match your phone etc
		$devices = array(
			array( 'name'=>"Motorola V600", 'ua_match'=>"MOT-V600", 'format'=>"xhtml", 'cookies'=>false, 'width'=>176, 'height'=>220 ),
			array( 'name'=>"Motorola V600", 'ua_match'=>"MOT-A820", 'format'=>"xhtml", 'cookies'=>false, 'width'=>176, 'height'=>220 )
		);

		foreach ( $devices as $device )
		{
			if ( preg_match( '/'.$device['ua_match'].'/', $_SERVER['HTTP_USER_AGENT'] ) )
			{
				$format = $device['format'];
				$cookies = $device['cookies'];
				break;
			}
		}

		if ( empty($format) )
		{
			unset( $device );
			$accepts_wml = preg_match( '/text\/vnd.wap.wml/i', $_SERVER['HTTP_ACCEPT'] );
			$accepts_html = preg_match( '/text\/html/i', $_SERVER['HTTP_ACCEPT'] );

			if ( $accepts_wml && !$accepts_html )
			{
				$format = "wml";
				$cookies = false;
			}
			else
			{
				$format = "html";
				$cookies = true;
			}
		}
	}
}

ini_set( "session.name", "ZMSESSID" );
if ( $cookies )
{
	ini_set( "session.use_cookies", "1" );
	ini_set( "session.use_trans_sid", "0" );
	ini_set( "url_rewriter.tags", "" );
}
else
{
	//ini_set( "session.auto_start", "1" );
	ini_set( "session.use_cookies", "0" );
	ini_set( "session.use_trans_sid", "1" );

	if ( $format == "xhtml" )
	{
		ini_set( "arg_separator.output", "&amp;" );
		ini_set( "url_rewriter.tags", "a=href,area=href,frame=src,input=src,fieldset=" );
	}
	elseif ( $format == "wml" )
	{
		ini_set( "arg_separator.output", "&amp;" );
		ini_set( "url_rewriter.tags", "a=href,area=href,frame=src,input=src,go=href,card=ontimer" );
	}
}

session_start();

if ( !$_SESSION['format'] )
{
	$_SESSION['format'] = $format;
	$_SESSION['cookies'] = $cookies;
	$_SESSION['device'] = $device;
	if ( $cookies )
	{
		setcookie( "format", $format );
	}
}

require_once( "zm_$format.php" );

?>
