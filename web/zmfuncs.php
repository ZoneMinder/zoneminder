<?php

//
// Zone Monitor function library file, $Date$, $Revision$
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

function startDaemon( $daemon, $did )
{
	$ps_command = "ps -edalf | grep '$daemon $did' | grep -v grep";
	$ps_array = preg_split( "/\s+/", exec( $ps_command ) );
	$pid = $ps_array[3];
	if ( $pid )
	{
		exec( "kill -HUP $pid" );
		return;
	}
	$command = ZM_PATH."/$daemon $did".' 2>/dev/null >&- <&- >/dev/null &';
	exec( $command );
	$ps_array = preg_split( "/\s+/", exec( $ps_command ) );
	while ( !$pid )
	{
		sleep( 1 );
		$ps_array = preg_split( "/\s+/", exec( $ps_command ) );
		$pid = $ps_array[3];
	}
}

function stopDaemon( $daemon, $did )
{
	$ps_command = "ps -edalf | grep '$daemon $did' | grep -v grep";
	$ps_array = preg_split( "/\s+/", exec( $ps_command ) );
	if ( $ps_array[3] )
	{
		$pid = $ps_array[3];
		exec( "kill -TERM $pid" );
	}
	else
	{
		return;
	}
	while( $pid )
	{
		sleep( 1 );
		$ps_array = preg_split( "/\s+/", exec( $ps_command ) );
		$pid = $ps_array[3];
	}
}

function controlDaemons( $device )
{
	$sql = "select count(if(Function='Passive',1,NULL)) as PassiveCount, count(if(Function='Active',1,NULL)) as ActiveCount from Monitors where Device = '$device'";
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$row = mysql_fetch_assoc( $result );
	$passive_count = $row[PassiveCount];
	$active_count = $row[ActiveCount];

	if ( !$passive_count && !$active_count )
	{
		stopDaemon( "zmc", $device );
	}
	else
	{
		startDaemon( "zmc", $device );
	}
	if ( !$active_count )
	{
		stopDaemon( "zma", $device );
	}
	else
	{
		startDaemon( "zma", $device );
	}
}
?>
