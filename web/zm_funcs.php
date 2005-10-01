<?php
//
// ZoneMinder web function library, $Date$, $Revision$
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

// Compatibility functions
if ( version_compare( phpversion(), "4.3.0", "<") )
{
	function ob_get_clean()
	{
		$buffer = ob_get_contents();
		ob_end_clean();
		return( $buffer );
	}
}

function userLogin( $username, $password )
{
	global $user, $cookies;
	if ( version_compare( phpversion(), "4.1.0", "<") )
	{
		global $_SESSION, $_SERVER;
	}

	$sql = "select * from Users where Username = '".mysql_escape_string($username)."' and Password = password('".mysql_escape_string($password)."') and Enabled = 1";
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$_SESSION['username'] = $username;
	if ( ZM_AUTH_METHOD == "plain" )
	{
		// Need to save this in session
		$_SESSION['password'] = $password;
	}
	$_SESSION['remote_addr'] = $_SERVER['REMOTE_ADDR']; // To help prevent session hijacking
	if ( $db_user = mysql_fetch_assoc( $result ) )
	{
		$_SESSION['user'] = $user = $db_user;
		$_SESSION['password_hash'] = $user['Password'];
	}
	else
	{
		unset( $user );
	}
	if ( $cookies ) session_write_close();
}

function userLogout()
{
	global $user;
	if ( version_compare( phpversion(), "4.1.0", "<") )
	{
		global $_SESSION;
	}

	unset( $_SESSION['user'] );
	unset( $user );

	session_destroy();
}

function authHash( $use_remote_addr=true )
{
	if ( version_compare( phpversion(), "4.1.0", "<") )
	{
		global $_SESSION;
	}

	if ( ZM_OPT_USE_AUTH && ZM_AUTH_METHOD == "hashed" )
	{
		$time = localtime();
		if ( $use_remote_addr )
		{
			$auth_key = ZM_AUTH_SECRET.$_SESSION['username'].$_SESSION['password_hash'].$_SESSION['remote_addr'].$time[2].$time[3].$time[4].$time[5];
		}
		else
		{
			$auth_key = ZM_AUTH_SECRET.$_SESSION['username'].$_SESSION['password_hash'].$time[2].$time[3].$time[4].$time[5];
		}
		$auth = md5( $auth_key );
	}
	else
	{
		$auth = "";
	}
	return( $auth );
}

function getStreamSrc( $args )
{
	if ( version_compare( phpversion(), "4.1.0", "<") )
	{
		global $_SESSION, $_SERVER;
	}

	$stream_src = "http://".$_SERVER['HTTP_HOST'].ZM_PATH_ZMS;

	if ( ZM_OPT_USE_AUTH )
	{
		if ( ZM_AUTH_METHOD == "hashed" )
		{
			$args[] = "auth=".authHash();
		}
		else
		{
			$args[] = "user=".$_SESSION['username'];
			$args[] = "pass=".$_SESSION['password'];
		}
	}
	if ( ZM_RAND_STREAM )
	{
		$args[] = "rand=".time();
	}

	if ( count($args) )
	{
		$stream_src .= "?".join( "&", $args );
	}

	return( $stream_src );
}

function getZmuCommand( $args )
{
	if ( version_compare( phpversion(), "4.1.0", "<") )
	{
		global $_SESSION;
	}

	$zmu_command = ZMU_PATH;

	if ( ZM_OPT_USE_AUTH )
	{
		if ( ZM_AUTH_METHOD == "hashed" )
		{
			$zmu_command .= " -A ".authHash( false );
		}
		else
		{
			$zmu_command .= " -U ".$_SESSION['username']." -P ".$_SESSION['password'];
		}
	}

	$zmu_command .= $args;

	return( $zmu_command );
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
			system( escapeshellcmd( "rm -rf ".ZM_DIR_EVENTS."/*/".sprintf( "%d", $eid ) ) );
		}
	}
}

function makeLink( $url, $label, $condition=1, $options="" )
{
	$string = "";
	if ( $condition )
	{
		$string .= '<a href="'.$url.'"'.($options?(' '.$options):'').'>';
	}
	$string .= $label;
	if ( $condition )
	{
		$string .= '</a>';
	}
	return( $string );
}

function truncText( $text, $length, $deslash=1 )
{       
	return( preg_replace( "/^(.{".$length.",}?)\b.*$/", "\\1&hellip;", ($deslash?stripslashes($text):$text) ) );       
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

function getFormChanges( $values, $new_values, $types=false, $columns=false )
{
	$changes = array();
	if ( !$types )
		$types = array();

	foreach( $new_values as $key=>$value )
	{
		if ( $columns && !$columns[$key] )
			continue;

		switch( $types[$key] )
		{
			case 'set' :
			{
				if ( is_array( $new_values[$key] ) )
				{
					if ( join(',',$new_values[$key]) != $values[$key] )
					{
						$changes[$key] = "$key = '".join(',',$new_values[$key])."'";
					}
				}
				elseif ( $values[$key] )
				{
					$changes[$key] = "$key = ''";
				}
				break;
			}
			case 'image' :
			{
				if ( is_array( $new_values[$key] ) )
				{
					$image_data = getimagesize( $new_values[$key]['tmp_name'] );
					$changes[$key.'Width'] = $key."Width = ".$image_data[0];
					$changes[$key.'Height'] = $key."Height = ".$image_data[1];
					$changes[$key.'Type'] = $key."Type = '".$new_values[$key]['type']."'";
					$changes[$key.'Size'] = $key."Size = ".$new_values[$key]['size'];
					ob_start();
					readfile( $new_values[$key]['tmp_name'] );
					$changes[$key] = $key." = '".addslashes( ob_get_contents() )."'";
					ob_end_clean();
				}
				else
				{
					$changes[$key] = "$key = '$value'";
				}
				break;
			}
			case 'document' :
			{
				if ( is_array( $new_values[$key] ) )
				{
					$image_data = getimagesize( $new_values[$key]['tmp_name'] );
					$changes[$key.'Type'] = $key."Type = '".$new_values[$key]['type']."'";
					$changes[$key.'Size'] = $key."Size = ".$new_values[$key]['size'];
					ob_start();
					readfile( $new_values[$key]['tmp_name'] );
					$changes[$key] = $key." = '".addslashes( ob_get_contents() )."'";
					ob_end_clean();
				}
				else
				{
					$changes[$key] = "$key = '$value'";
				}
				break;
			}
			case 'file' :
			{
				$changes[$key.'Type'] = $key."Type = '".$new_values[$key]['type']."'";
				$changes[$key.'Size'] = $key."Size = ".$new_values[$key]['size'];
				ob_start();
				readfile( $new_values[$key]['tmp_name'] );
				$changes[$key] = $key." = '".addslashes( ob_get_contents() )."'";
				ob_end_clean();
				break;
			}
			case 'raw' :
			{
				if ( $values[$key] != $value )
				{
					$changes[$key] = "$key = $value";
				}
				break;
			}
			default :
			{
				if ( $values[$key] != $value )
				{
					$changes[$key] = "$key = '$value'";
				}
				break;
			}
		}
	}
	foreach( $values as $key=>$value )
	{
		if ( $columns[$key] && $types[$key] == 'toggle' )
		{
			if ( !isset($new_values[$key]) && !empty($value) )
			{
				$changes[$key] = "$key = 0";
			}
		}
	}
	return( $changes );
}

function getBrowser( &$browser, &$version )
{
	if ( version_compare( phpversion(), "4.1.0", "<") )
	{
		global $_SERVER;
	}

	if (ereg( 'MSIE ([0-9].[0-9]{1,2})',$_SERVER['HTTP_USER_AGENT'],$log_version))
	{
		$version = $log_version[1];
		$browser = 'ie';
	}
	elseif (ereg( 'Safari/([0-9.]+)',$_SERVER['HTTP_USER_AGENT'],$log_version))
	{
		$version = $log_version[1];
		$browser = 'safari';
	}
	elseif (ereg( 'Opera ([0-9].[0-9]{1,2})',$_SERVER['HTTP_USER_AGENT'],$log_version))
	{
		$version = $log_version[1];
		$browser = 'opera';
	}
	elseif (ereg( 'Mozilla/([0-9].[0-9]{1,2})',$_SERVER['HTTP_USER_AGENT'],$log_version))
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

function isInternetExplorer()
{
	getBrowser( $browser, $version );

	return( $browser == "ie" );
}

function isWindows()
{
	if ( version_compare( phpversion(), "4.1.0", "<") )
	{
		global $_SERVER;
	}

	return ( preg_match( '/Win/', $_SERVER['HTTP_USER_AGENT'] ) );
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
		case 'Nodect' :
		{
			if ( $restart )
			{
				if ( ZM_OPT_CONTROL )
				{
					daemonControl( "stop", "zmtrack.pl", "-m ".$monitor['Id'] );
				}
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
			if ( ZM_OPT_CONTROL && $monitor['Controllable'] && $monitor['TrackMotion'] && ( $monitor['Function'] == 'Modect' || $monitor['Function'] == 'Mocord' ) )
			{
				daemonControl( "start", "zmtrack.pl", "-m ".$monitor['Id'] );
			}
			break;
		}
		default :
		{
			if ( ZM_OPT_CONTROL )
			{
				daemonControl( "stop", "zmtrack.pl", "-m ".$monitor['Id'] );
			}
			daemonControl( "stop", "zma", "-m ".$monitor['Id'] );
			if ( ZM_OPT_FRAME_SERVER )
			{
				daemonControl( "stop", "zmf", "-m ".$monitor['Id'] );
			}
			break;
		}
	}
}

function initDaemonStatus()
{
	global $daemon_status;

	if ( !$daemon_status )
	{
		$string = ZM_PATH_BIN."/zmdc.pl status";
		$daemon_status = shell_exec( $string );
	}
}

function daemonStatus( $daemon, $args=false )
{
	global $daemon_status;

	initDaemonStatus();
	
	$string .= "$daemon";
	if ( $args )
		$string .= " $args";
	return( strpos( $daemon_status, "'$string' running" ) !== false );
}

function zmcStatus( $monitor )
{
	if ( $monitor['Type'] == 'Local' )
	{
		$zmc_args = "-d ".$monitor['Device'];
	}
	else
	{
		$zmc_args = "-m ".$monitor['Id'];
	}
	return( daemonStatus( "zmc", $zmc_args ) );
}

function zmaStatus( $monitor )
{
	if ( is_array( $monitor ) )
	{
		$monitor = $monitor['Id'];
	}
	return( daemonStatus( "zma", "-m $monitor" ) );
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

function createListThumbnail( $event, $overwrite=false )
{
	$sql = "select * from Frames where EventId = '".$event['Id']."' and Score = '".$event['MaxScore']."' order by FrameId limit 0,1";
	if ( !($result = mysql_query( $sql )) )
		die( mysql_error() );
	$frame = mysql_fetch_assoc( $result );
	$frame_id = $frame['FrameId'];

	if ( ZM_WEB_LIST_THUMB_WIDTH )
	{
		$thumb_width = ZM_WEB_LIST_THUMB_WIDTH;
		$fraction = ZM_WEB_LIST_THUMB_WIDTH/$event['Width'];
		$thumb_height = $event['Height']*$fraction;
	}
	elseif ( ZM_WEB_LIST_THUMB_HEIGHT )
	{
		$thumb_height = ZM_WEB_LIST_THUMB_HEIGHT;
		$fraction = ZM_WEB_LIST_THUMB_HEIGHT/$event['Height'];
		$thumb_width = $event['Width']*$fraction;
	}
	else
	{
		die( "No thumbnail width or height specified, please check in Options->Web" );
	}
	$event_path = ZM_DIR_EVENTS.'/'.$event['MonitorId'].'/'.$event['Id'];
	$image_path = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $event_path, $frame_id );
	$capt_image = $image_path;
	if ( $scale == 1 || !file_exists( ZM_PATH_NETPBM."/jpegtopnm" ) )
	{
		$anal_image = preg_replace( "/capture/", "analyse", $image_path );

		if ( file_exists($anal_image) && filesize( $anal_image ) )
		{
			$thumb_image = $anal_image;
		}
		else
		{
			$thumb_image = $capt_image;
		}
	}
	else
	{
		$thumb_image = preg_replace( "/capture/", "mini", $capt_image );

		if ( !file_exists($thumb_image) || !filesize( $thumb_image ) )
		{
			$anal_image = preg_replace( "/capture/", "analyse", $capt_image );
			if ( file_exists( $anal_image ) )
				$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $anal_image | ".ZM_PATH_NETPBM."/pnmscalefixed $fraction | ".ZM_PATH_NETPBM."/ppmtojpeg --dct=fast > $thumb_image";
			else
				$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $capt_image | ".ZM_PATH_NETPBM."/pnmscalefixed $fraction | ".ZM_PATH_NETPBM."/ppmtojpeg --dct=fast > $thumb_image";
			#exec( escapeshellcmd( $command ) );
			exec( $command );
		}
	}
	$thumb_data = $frame;
	$thumb_data['Path'] = $thumb_image;
	$thumb_data['Width'] = (int)$thumb_width;
	$thumb_data['Height'] = (int)$thumb_height;

	return( $thumb_data );
}

function createVideo( $event, $rate, $scale, $overwrite=false )
{
	$command = ZM_PATH_BIN."/zmvideo.pl -e ".$event['Id']." -r ".sprintf( "%.2f", ($rate/RATE_SCALE) )." -s ".sprintf( "%.2f", ($scale/SCALE_SCALE) );
	if ( $overwrite )
		$command .= " -o";
	$result = exec( $command, $output, $status );
	return( $status?"":rtrim($result) );
}

// Now deprecated
function createImage( $monitor, $scale )
{
	if ( is_array( $monitor ) )
	{
		$monitor = $monitor['Id'];
	}
	chdir( ZM_DIR_IMAGES );
	$command = getZmuCommand( " -m $monitor -i" );
	if ( !empty($scale) && $scale < 100 )
		$command .= " -S $scale";
	$status = exec( escapeshellcmd( $command ) );
	chdir( '..' );
	return( $status );
}

function reScale( $dimension, $scale=SCALE_SCALE )
{
	if ( $scale == SCALE_SCALE )
		return( $dimension );

	return( (int)(($dimension*$scale)/SCALE_SCALE) );
}

function deScale( $dimension, $scale=SCALE_SCALE )
{
	if ( $scale == SCALE_SCALE )
		return( $dimension );

	return( (int)(($dimension*SCALE_SCALE)/$scale) );
}

function parseSort( $save_to_session=false )
{
	global $sort_field, $sort_asc; // Inputs
	global $sort_query, $sort_column, $sort_order; // Outputs
	if ( version_compare( phpversion(), "4.1.0", "<") )
	{
		global $_SESSION;
	}

	if ( !isset($sort_field) )
	{
		$sort_field = "StartTime";
		$sort_asc = false;
	}
	switch( $sort_field )
	{
		case 'Id' :
			$sort_column = "E.Id";
			break;
		case 'MonitorName' :
			$sort_column = "M.Name";
			break;
		case 'Name' :
			$sort_column = "E.Name";
			break;
		case 'Cause' :
			$sort_column = "E.Cause";
			break;
		case 'DateTime' :
		case 'StartTime' :
			$sort_column = "E.StartTime";
			break;
		case 'Length' :
			$sort_column = "E.Length";
			break;
		case 'Frames' :
			$sort_column = "E.Frames";
			break;
		case 'AlarmFrames' :
			$sort_column = "E.AlarmFrames";
			break;
		case 'TotScore' :
			$sort_column = "E.TotScore";
			break;
		case 'AvgScore' :
			$sort_column = "E.AvgScore";
			break;
		case 'MaxScore' :
			$sort_column = "E.MaxScore";
			break;
		default:
			$sort_column = "E.StartTime";
			break;
	}
	$sort_order = $sort_asc?"asc":"desc";
	if ( !$sort_asc ) $sort_asc = 0;
	$sort_query = "&sort_field=$sort_field&sort_asc=$sort_asc";
	if ( $save_to_session )
	{
		$_SESSION['sort_field'] = $sort_field;
		$_SESSION['sort_asc'] = $sort_asc;
	}
}

function parseFilter( $save_to_session=false )
{
	global $trms; // Inputs
	global $filter_query, $filter_sql, $filter_fields; // Outputs
	if ( version_compare( phpversion(), "4.1.0", "<") )
	{
		global $_SESSION;
	}

	$filter_query = ''; 
	$filter_sql = '';
	$filter_fields = '';

	if ( $trms )
	{
		if ( $save_to_session )
		{
			$_SESSION['trms'] = $trms;
		}
		$filter_query .= "&trms=$trms";
		$filter_fields .= '<input type="hidden" name="trms" value="'.$trms.'"/>'."\n";

		for ( $i = 1; $i <= $trms; $i++ )
		{
			$conjunction_name = "cnj$i";
			$obracket_name = "obr$i";
			$cbracket_name = "cbr$i";
			$attr_name = "attr$i";
			$op_name = "op$i";
			$value_name = "val$i";

			global $$conjunction_name, $$obracket_name, $$cbracket_name, $$attr_name, $$op_name, $$value_name;

			if ( isset($$conjunction_name) )
			{
				$filter_query .= "&$conjunction_name=".$$conjunction_name;
				$filter_sql .= " ".$$conjunction_name." ";
				$filter_fields .= '<input type="hidden" name="'.$conjunction_name.'" value="'.$$conjunction_name.'"/>'."\n";
				if ( $save_to_session )
				{
					$_SESSION[$conjunction_name] = $$conjunction_name;
				}
			}
			if ( isset($$obracket_name) )
			{
				$filter_query .= "&$obracket_name=".$$obracket_name;
				$filter_sql .= str_repeat( "(", $$obracket_name );
				$filter_fields .= '<input type="hidden" name="'.$obracket_name.'" value="'.$$obracket_name.'"/>'."\n";
				if ( $save_to_session )
				{
					$_SESSION[$obracket_name] = $$obracket_name;
				}
			}
			if ( isset($$attr_name) )
			{
				$filter_query .= "&$attr_name=".$$attr_name;
				$filter_fields .= '<input type="hidden" name="'.$attr_name.'" value="'.$$attr_name.'"/>'."\n";
				switch ( $$attr_name )
				{
					case 'MonitorName':
						$filter_sql .= 'M.'.preg_replace( '/^Monitor/', '', $$attr_name );
						break;
					case 'Name':
						$filter_sql .= "E.Name";
						break;
					case 'Cause':
						$filter_sql .= "E.Cause";
						break;
					case 'DateTime':
						$filter_sql .= "E.StartTime";
						break;
					case 'Date':
						$filter_sql .= "to_days( E.StartTime )";
						break;
					case 'Time':
						$filter_sql .= "extract( hour_second from E.StartTime )";
						break;
					case 'Weekday':
						$filter_sql .= "weekday( E.StartTime )";
						break;
					case 'Id':
					case 'Name':
					case 'MonitorId':
					case 'Length':
					case 'Frames':
					case 'AlarmFrames':
					case 'TotScore':
					case 'AvgScore':
					case 'MaxScore':
						$filter_sql .= "E.".$$attr_name;
						break;
					case 'Archived':
						$filter_sql .= "E.Archived = ".$$value_name;
						break;
					case 'DiskPercent':
						$filter_sql .= getDiskPercent();
						break;
					case 'DiskBlocks':
						$filter_sql .= getDiskBlocks();
						break;
				}
				$value_list = array();
				foreach ( preg_split( '/["\'\s]*?,["\'\s]*?/', preg_replace( '/^["\']+?(.+)["\']+?$/', '$1', $$value_name ) ) as $value )
				{
					switch ( $$attr_name )
					{
						case 'MonitorName':
						case 'Name':
						case 'Cause':
							$value = "'$value'";
							break;
						case 'DateTime':
							$value = "'".strftime( "%Y-%m-%d %H:%M:%S", strtotime( $value ) )."'";
							break;
						case 'Date':
							$value = "to_days( '".strftime( "%Y-%m-%d %H:%M:%S", strtotime( $value ) )."' )";
							break;
						case 'Time':
							$value = "extract( hour_second from '".strftime( "%Y-%m-%d %H:%M:%S", strtotime( $value ) )."' )";
							break;
						case 'Weekday':
							$value = "weekday( '".strftime( "%Y-%m-%d %H:%M:%S", strtotime( $value ) )."' )";
							break;
					}
					$value_list[] = $value;
				}

				switch ( $$op_name )
				{
					case '=' :
					case '!=' :
					case '>=' :
					case '>' :
					case '<' :
					case '<=' :
						$filter_sql .= " ".$$op_name." $value";
						break;
					case '=~' :
						$filter_sql .= " regexp $value";
						break;
					case '!~' :
						$filter_sql .= " not regexp $value";
						break;
					case '=[]' :
						$filter_sql .= " in (".join( ",", $value_list ).")";
						break;
					case '![]' :
						$filter_sql .= " not in (".join( ",", $value_list ).")";
						break;
				}

				$filter_query .= "&$op_name=".urlencode($$op_name);
				$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'"/>'."\n";
				$filter_query .= "&$value_name=".urlencode($$value_name);
				$filter_fields .= '<input type="hidden" name="'.$value_name.'" value="'.$$value_name.'"/>'."\n";
				if ( $save_to_session )
				{
					$_SESSION[$attr_name] = $$attr_name;
					$_SESSION[$op_name] = $$op_name;
					$_SESSION[$value_name] = $$value_name;
				}
			}
			if ( isset($$cbracket_name) )
			{
				$filter_query .= "&$cbracket_name=".$$cbracket_name;
				$filter_sql .= str_repeat( ")", $$cbracket_name );
				$filter_fields .= '<input type="hidden" name="'.$cbracket_name.'" value="'.$$cbracket_name.'"/>'."\n";
				if ( $save_to_session )
				{
					$_SESSION[$cbracket_name] = $$cbracket_name;
				}
			}
		}
		$filter_sql = " and ( $filter_sql )";
	}
}

function getLoad()
{
	$uptime = shell_exec( 'uptime' );
	$load = '';
	if ( preg_match( '/load average: ([\d.]+)/', $uptime, $matches ) )
		$load = $matches[1];
	return( $load );
}

function getDiskPercent()
{
	$df = shell_exec( 'df '.ZM_DIR_EVENTS );
	$space = -1;
	if ( preg_match( '/\s(\d+)%/ms', $df, $matches ) )
		$space = $matches[1];
	return( $space );
}

function getDiskBlocks()
{
	$df = shell_exec( 'df '.ZM_DIR_EVENTS );
	$space = -1;
	if ( preg_match( '/\s(\d+)\s+\d+\s+\d+%/ms', $df, $matches ) )
		$space = $matches[1];
	return( $space );
}

// Function to fix a problem whereby the built in PHP session handling 
// features want to put the sid as a hidden field after the form or 
// fieldset tag, neither of which will work with strict XHTML Basic.
function sidField()
{
	if ( SID )
	{
		list( $sessname, $sessid ) = split( "=", SID );
?>
<input type="hidden" name="<?= $sessname ?>" value="<?= $sessid ?>"/>
<?php
	}
}

function verNum( $version )
{
	$vNum = "";
	$maxFields = 3;
	$vFields = explode( ".", $version );
	array_splice( $vFields, $maxFields );
	while ( count($vFields) < $maxFields )
	{
		$vFields[] = 0;
	}
	foreach ( $vFields as $vField )
	{
		$vField = sprintf( "%02d", $vField );
		while ( strlen($vField) < 2 )
		{
			$vField = "0".$vField;
		}
		$vNum .= $vField;
	}
	return( $vNum );
}
?>
