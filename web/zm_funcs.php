<?php
//
// ZoneMinder web function library, $Date$, $Revision$
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
	$HTTP_SESSION_VARS['username'] = $username;
	$HTTP_SESSION_VARS['password'] = $password;
	if ( $db_user = mysql_fetch_assoc( $result ) )
	{
		$HTTP_SESSION_VARS['user'] = $user = $db_user;
	}
	else
	{
		unset( $user );
	}
	session_write_close();
}

function userLogout()
{
	global $user, $HTTP_SESSION_VARS;

	unset( $HTTP_SESSION_VARS['user'] );
	unset( $user );

	session_destroy();
}

function visibleMonitor( $mid )
{
	global $user;

	return( empty($user['MonitorIds']) || in_array( $mid, split( ',', $user['MonitorIds'] ) ) );
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

	if ( $user['Events'] == 'Edit' && $eid )
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
	if ( preg_match( "/^(\w+)\s*\[\s*['\"]?(\w+)[\"']?\s*]$/", $name, $matches ) )
	{
		$arr = $matches[1];
		$idx = $matches[2];
		global $$arr;
		$value = ${$arr}[$idx];
	}
	else
	{
		global $$name;
		$value = $$name;
	}
	ob_start();
?>
<select name="<?= $name ?>" class="form"<?php if ( $onchange ) { echo " onChange=\"$onchange\""; } ?>>
<?php
	foreach ( $contents as $content_value => $content_text )
	{
?>
<option value="<?= $content_value ?>"<?php if ( $value == $content_value ) { echo " selected"; } ?>><?= $content_text ?></option>
<?php
	}
?>
</select>
<?php
	$html = ob_get_contents();
	ob_end_clean();
	
	return( $html );
}

function getFormChanges( $values, $new_values, $types=false )
{
	$changes = array();
	if ( !$types )
		$types = array();

	foreach( $new_values as $key=>$value )
	{
		switch( $types[$key] )
		{
			case 'set' :
			{
				if ( is_array( $new_values[$key] ) )
				{
					if ( join(',',$new_values[$key]) != $values[$key] )
					{
						$changes[] = "$key = '".join(',',$new_values[$key])."'";
					}
				}
				elseif ( $values[$key] )
				{
					$changes[] = "$key = ''";
				}
				break;
			}
			default :
			{
				if ( $values[$key] != $value )
				{
					$changes[] = "$key = '$value'";
				}
				break;
			}
		}
	}
	return( $changes );
}

function getBrowser( &$browser, &$version )
{
	global $HTTP_SERVER_VARS;

	if (ereg( 'MSIE ([0-9].[0-9]{1,2})',$HTTP_SERVER_VARS['HTTP_USER_AGENT'],$log_version))
	{
		$version = $log_version[1];
		$browser = 'ie';
	}
	elseif (ereg( 'Safari/([0-9.]+)',$HTTP_SERVER_VARS['HTTP_USER_AGENT'],$log_version))
	{
		$version = $log_version[1];
		$browser = 'safari';
	}
	elseif (ereg( 'Opera ([0-9].[0-9]{1,2})',$HTTP_SERVER_VARS['HTTP_USER_AGENT'],$log_version))
	{
		$version = $log_version[1];
		$browser = 'opera';
	}
	elseif (ereg( 'Mozilla/([0-9].[0-9]{1,2})',$HTTP_SERVER_VARS['HTTP_USER_AGENT'],$log_version))
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

function canStreamNative()
{
	return( ZM_CAN_STREAM == "yes" || ( ZM_CAN_STREAM == "auto" && isNetscape() ) );
}

function canStreamApplet()
{
	return( (ZM_OPT_CAMBOZOLA && file_exists( ZM_PATH_WEB.'/'.ZM_PATH_CAMBOZOLA )) );
}

function canStream()
{
	return( canStreamNative() | canStreamApplet() );
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
	if ( $monitor['Type'] == "Local" )
	{
		$sql = "select count(if(Function!='None',1,NULL)) as ActiveCount from Monitors where Device = '".$monitor['Device']."'";
		$zmc_args = "-d ".$monitor['Device'];
	}
	else
	{
		$sql = "select count(if(Function!='None',1,NULL)) as ActiveCount from Monitors where Id = '".$monitor['Id']."'";
		$zmc_args = "-m ".$monitor['Id'];
	}
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$row = mysql_fetch_assoc( $result );
	$active_count = $row['ActiveCount'];

	if ( !$active_count )
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
		$sql = "select Id,Function,RunMode from Monitors where Id = '$monitor'";
		$result = mysql_query( $sql );
		if ( !$result )
			echo mysql_error();
		$monitor = mysql_fetch_assoc( $result );
	}
	if ( $monitor['RunMode'] == 'Triggered' )
	{
		// Don't touch anything that's triggered
		return;
	}
	switch ( $monitor['Function'] )
	{
		case 'Modect' :
		case 'Record' :
		case 'Mocord' :
		{
			if ( $restart )
			{
				daemonControl( "stop", "zma", "-m ".$monitor['Id'] );
				if ( ZM_OPT_FRAME_SERVER )
				{
					daemonControl( "stop", "zmf", "-m ".$monitor['Id'] );
				}
			}
			if ( ZM_OPT_FRAME_SERVER )
			{
				daemonControl( "start", "zmf", "-m ".$monitor['Id'] );
			}
			daemonControl( "start", "zma", "-m ".$monitor['Id'] );
			break;
		}
		default :
		{
			daemonControl( "stop", "zma", "-m ".$monitor['Id'] );
			if ( ZM_OPT_FRAME_SERVER )
			{
				daemonControl( "stop", "zmf", "-m ".$monitor['Id'] );
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
	if ( $monitor['Type'] == 'Local' )
	{
		$zmc_args = "-d ".$monitor['Device'];
	}
	else
	{
		$zmc_args = "-m ".$monitor['Id'];
	}
	return( daemonCheck( "zmc", $zmc_args ) );
}

function zmaCheck( $monitor )
{
	if ( is_array( $monitor ) )
	{
		$monitor = $monitor['Id'];
	}
	return( daemonCheck( "zma", "-m $monitor" ) );
}

function createVideo( $event, $rate, $scale, $overwrite=0 )
{
	$command = ZM_PATH_BIN."/zmvideo.pl -e ".$event['Id']." -r $rate -s $scale";
	if ( $overwrite )
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
