<?php

//
// ZoneMinder function library file, $Date$, $Revision$
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

function userLogin( $username, $password )
{
	global $user, $HTTP_SESSION_VARS;

	$sql = "select * from Users where Username = '$username' and Password = password('$password') and Enabled = 1";
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$HTTP_SESSION_VARS[username] = $username;
	$HTTP_SESSION_VARS[password] = $password;
	if ( $user = mysql_fetch_assoc( $result ) )
	{
		$HTTP_SESSION_VARS[user] = $user;
	}
	else
	{
		$HTTP_SESSION_VARS[user] = array();
	}
	session_write_close();
}

function userLogout()
{
	global $user, $HTTP_SESSION_VARS;

	unset( $HTTP_SESSION_VARS[user] );
	unset( $user );

	session_destroy();
}

function visibleMonitor( $mid )
{
	global $user;

	return( !$user[MonitorIds] || in_array( $mid, split( ',', $user[MonitorIds] ) ) );
}

function canView( $area, $mid=false )
{
	global $user;

	return( ($user[$area] == 'View' || $user[$area] == 'Edit') && ( !$mid || visibleMonitor( $mid ) ) );
}

function canEdit( $area, $mid=false )
{
	global $user;

	return( $user[$area] == 'Edit' && ( !$mid || visibleMonitor( $mid ) ) );
}

function deleteEvent( $eid )
{
	global $user;

	if ( $user[Events] == 'Edit' && $eid )
	{
		$result = mysql_query( "delete from Events where Id = '$eid'" );
		if ( !$result )
			die( mysql_error() );
		if ( !ZM_OPT_FAST_DELETE )
		{
			$result = mysql_query( "delete from Stats where EventId = '$eid'" );
			if ( !$result )
				die( mysql_error() );
			$result = mysql_query( "delete from Frames where EventId = '$eid'" );
			if ( !$result )
				die( mysql_error() );
			system( escapeshellcmd( "rm -rf ".ZM_PATH_EVENTS."/*/".sprintf( "%d", $eid ) ) );
		}
	}
}

function makeLink( $url, $label, $condition=1 )
{
	$string = "";
	if ( $condition )
	{
		$string .= '<a href="'.$url.'">';
	}
	$string .= $label;
	if ( $condition )
	{
		$string .= '</a>';
	}
	return( $string );
}

function buildSelect( $name, $contents, $onchange="" )
{
	global $$name;
?>
<select name="<?= $name ?>" class="form"<?php if ( $onchange ) { echo " onChange=\"$onchange\""; } ?>>
<?php
	foreach ( $contents as $content_value => $content_text )
	{
?>
<option value="<?= $content_value ?>"<?php if ( $$name == $content_value ) { echo " selected"; } ?>><?= $content_text ?></option>
<?php
	}
?>
</select>
<?php
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
	return( ZM_CAN_STREAM || isNetscape() || (ZM_OPT_CAMBOZOLA && file_exists( ZM_PATH_WEB.'/'.ZM_PATH_CAMBOZOLA )) );
}

function fixDevices()
{
	$string = ZM_PATH_BIN."/zmfix";
	$string .= " 2>/dev/null >&- <&- >/dev/null";
	exec( $string );
}

function packageControl( $command )
{
	$string = ZM_PATH_BIN."/zmpkg.pl $command";
	$string .= " 2>/dev/null >&- <&- >/dev/null";
	exec( $string );
}

function daemonControl( $command, $daemon=false, $args=false )
{
	$string = ZM_PATH_BIN."/zmdc.pl $command";
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

function zmcControl( $monitor, $restart=false )
{
	if ( $monitor[Type] == "Local" )
	{
		$sql = "select count(if(Function='Monitor',1,NULL)) as PassiveCount, count(if(Function>'Monitor',1,NULL)) as ActiveCount, count(if(Function='X10',1,NULL)) as X10Count from Monitors where Device = '$monitor[Device]'";
		$zmc_args = "-d $monitor[Device]";
	}
	else
	{
		$sql = "select count(if(Function='Monitor',1,NULL)) as PassiveCount, count(if(Function>'Monitor',1,NULL)) as ActiveCount, count(if(Function='X10',1,NULL)) as X10Count from Monitors where Host = '$monitor[Host]' and Port = '$monitor[Port]' and Path = '$monitor[Path]'";
		$zmc_args = "-H $monitor[Host] -P $monitor[Port] -p '$monitor[Path]'";
	}
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$row = mysql_fetch_assoc( $result );
	$passive_count = $row[PassiveCount];
	$active_count = $row[ActiveCount];
	$x10_count = $row[X10Count];

	if ( !$passive_count && !$active_count && !$x10_count )
	{
		daemonControl( "stop", "zmc", $zmc_args );
	}
	else
	{
		if ( $restart )
		{
			daemonControl( "stop", "zmc", $zmc_args );
		}
		daemonControl( "start", "zmc", $zmc_args );
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
	switch ( $monitor['Function'] )
	{
		case 'Modect' :
		case 'Record' :
		case 'Mocord' :
		{
			if ( $restart )
			{
				daemonControl( "stop", "zmfilter.pl", "-m $monitor[Id] -e -1" );
				daemonControl( "stop", "zma", "-m $monitor[Id]" );
				if ( ZM_OPT_FRAME_SERVER )
				{
					daemonControl( "stop", "zmf", "-m $monitor[Id]" );
				}
			}
			if ( ZM_OPT_FRAME_SERVER )
			{
				daemonControl( "start", "zmf", "-m $monitor[Id]" );
			}
			daemonControl( "start", "zma", "-m $monitor[Id]" );
			daemonControl( "start", "zmfilter.pl", "-m $monitor[Id] -e -1" );
			break;
		}
		default :
		{
			daemonControl( "stop", "zmfilter.pl", "-m $monitor[Id] -e -1" );
			daemonControl( "stop", "zma", "-m $monitor[Id]" );
			if ( ZM_OPT_FRAME_SERVER )
			{
				daemonControl( "stop", "zmf", "-m $monitor[Id]" );
			}
			break;
		}
	}
}

function daemonCheck( $daemon=false, $args=false )
{
	$string = ZM_PATH_BIN."/zmdc.pl check";
	if ( $daemon )
	{
		$string .= " $daemon";
		if ( $args )
			$string .= " $args";
	}
	$result = exec( $string );
	return( preg_match( '/running/', $result ) );
}

function zmcCheck( $monitor )
{
	if ( $monitor[Type] == 'Local' )
	{
		$zmc_args = "-d $monitor[Device]";
	}
	else
	{
		$zmc_args = "-H $monitor[Host] -P $monitor[Port] -p '$monitor[Path]'";
	}
	return( daemonCheck( "zmc", $zmc_args ) );
}

function zmaCheck( $monitor )
{
	if ( is_array( $monitor ) )
	{
		$monitor = $monitor[Id];
	}
	return( daemonCheck( "zma", "-m $monitor" ) );
}

function createVideo( $event, $rate, $scale, $overwrite=0 )
{
	$command = ZM_PATH_BIN."/zmvideo.pl -e $event[Id] -r $rate -s $scale";
	if ( $overwite )
		$command .= " -o";
	$result = exec( $command, $output, $status );
	return( $status?"":rtrim($result) );
}

function reScale( $dimension, $scale=1 )
{
	if ( $scale == 1 )
		return( $dimension );

	if ( $scale > 0 )
	{
		return( (int)($dimension*$scale) );
	}
	else
	{
		return( (int)(1+(($dimension-1)/-$scale)) );
	}
}

?>
