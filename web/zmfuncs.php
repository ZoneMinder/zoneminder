<?php

//
// ZoneMinder function library file, $Date$, $Revision$
// Copyright (C) 2002  Philip Coombes
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

function deleteEvent( $eid )
{
	if ( $eid )
	{
		$result = mysql_query( "delete from Events where Id = '$eid'" );
		if ( !$result )
			die( mysql_error() );
		if ( !FAST_DELETE )
		{
			$result = mysql_query( "delete from Stats where EventId = '$eid'" );
			if ( !$result )
				die( mysql_error() );
			$result = mysql_query( "delete from Frames where EventId = '$eid'" );
			if ( !$result )
				die( mysql_error() );
			system( escapeshellcmd( "rm -rf ".EVENT_PATH."/*/".sprintf( "%04d", $eid ) ) );
		}
	}
}

function getBrowser( &$browser, &$version )
{
	global $HTTP_SERVER_VARS;

	if (ereg( 'MSIE ([0-9].[0-9]{1,2})',$HTTP_SERVER_VARS[HTTP_USER_AGENT],$log_version))
	{
		$version = $log_version[1];
		$browser = 'ie';
	}
	elseif (ereg( 'Opera ([0-9].[0-9]{1,2})',$HTTP_SERVER_VARS[HTTP_USER_AGENT],$log_version))
	{
		$version = $log_version[1];
		$browser = 'opera';
	}
	elseif (ereg( 'Mozilla/([0-9].[0-9]{1,2})',$HTTP_SERVER_VARS[HTTP_USER_AGENT],$log_version))
	{
		$version = $log_version[1];
		$browser = 'mozilla';
	}
	else
	{
		$version = 0;
		$browser = 'unknown';
	}
}

function isNetscape()
{
	getBrowser( $browser, $version );

	return( $browser == "mozilla" );
}

function canStream()
{
	return( isNetscape() || (CAMBOZOLA_PATH && file_exists( CAMBOZOLA_PATH )) );
}

function daemonControl( $command, $daemon=false, $args=false )
{
	$string = ZM_PATH."/zmdc.pl $command";
	if ( $daemon )
	{
		$string .= " $daemon";
		if ( $args )
		{
			$string .= " $args";
		}
	}
	$string .= " 2>/dev/null >&- <&- >/dev/null";
	exec( $string );
}

function zmcControl( $device, $restart=false )
{
	if ( is_array( $device ) )
	{
		$device = $device[Device];
	}
	$sql = "select count(if(Function='Passive',1,NULL)) as PassiveCount, count(if(Function='Active',1,NULL)) as ActiveCount, count(if(Function='X10',1,NULL)) as X10Count from Monitors where Device = '$device'";
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$row = mysql_fetch_assoc( $result );
	$passive_count = $row[PassiveCount];
	$active_count = $row[ActiveCount];
	$x10_count = $row[X10Count];

	if ( !$passive_count && !$active_count && !$x10_count )
	{
		daemonControl( "stop", "zmc", "-d $device" );
	}
	else
	{
		if ( $restart )
		{
			daemonControl( "stop", "zmc", "-d $device" );
		}
		daemonControl( "start", "zmc", "-d $device" );
	}
}

function zmaControl( $monitor, $restart=false )
{
	if ( !is_array( $monitor ) )
	{
		$sql = "select Id,Function from Monitors where Id = '$monitor'";
		$result = mysql_query( $sql );
		if ( !$result )
			echo mysql_error();
		$monitor = mysql_fetch_assoc( $result );
	}
	if ( $monitor['Function'] == 'Active' )
	{
		if ( $restart )
		{
			daemonControl( "stop", "zma", "-m $monitor[Id]" );
		}
		daemonControl( "start", "zma", "-m $monitor[Id]" );
	}
	else
	{
		daemonControl( "stop", "zma", "-m $monitor[Id]" );
	}
}

function daemonCheck( $daemon=false, $args=false )
{
	$string = ZM_PATH."/zmdc.pl check";
	if ( $daemon )
	{
		$string .= " $daemon";
		if ( $args )
			$string .= " $args";
	}
	$result = exec( $string );
	return( preg_match( '/running/', $result ) );
}

function zmcCheck( $device )
{
	if ( is_array( $device ) )
	{
		$device = $device[Device];
	}
	return( daemonCheck( "zmc", "-d $device" ) );
}

function zmaCheck( $monitor )
{
	if ( is_array( $monitor ) )
	{
		$monitor = $monitor[Id];
	}
	return( daemonCheck( "zma", "-m $monitor" ) );
}
?>
