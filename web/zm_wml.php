<?php

//
// ZoneMinder WML interface file, $Date$, $Revision$
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

//
// Note: This is _really_ prototypical and not intended to be much
// use at present. However I'm working on a much nicer version with
// built in brower capability detection which should be much nicer.
//

ini_set( "session.name", "ZMSESSID" );
ini_set( "session.auto_start", "1" );
ini_set( "session.use_cookies", "0" );
ini_set( "session.use_trans_sid", "1" );
ini_set( "arg_separator.output", "&amp;" );
ini_set( "url_rewriter.tags", ini_get( "url_rewriter.tags" ).",card=ontimer" );

session_start();

$bandwidth = "mobile";

require_once( 'zm_config.php' );
require_once( 'zm_funcs.php' );
require_once( 'zm_actions.php' );

define( "WAP_COOKIES", false );

header("Content-type: text/vnd.wap.wml"); 
header("Cache-Control: no-cache, must-revalidate"); 
header("Pragma: no-cache"); 
echo( '<?xml version="1.0"?>'."\n" );
echo( '<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">'."\n" );

if ( !$view )
{
	$view = "console";
}

if ( $view == "console" )
{
	if ( !$HTTP_SESSION_VARS[event_reset_time] )
		$HTTP_SESSION_VARS[event_reset_time] = "2000-01-01 00:00:00";

	$db_now = strftime( "%Y-%m-%d %H:%M:%S" );
	$sql = "select M.*, count(E.Id) as EventCount, count(if(E.StartTime>'$HTTP_SESSION_VARS[event_reset_time]' && E.Archived = 0,1,NULL)) as ResetEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 HOUR && E.Archived = 0,1,NULL)) as HourEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 DAY && E.Archived = 0,1,NULL)) as DayEventCount from Monitors as M left join Events as E on E.MonitorId = M.Id group by M.Id order by M.Id";
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$monitors = array();
	$max_width = 0;
	$max_height = 0;
	$cycle_count = 0;
	while( $row = mysql_fetch_assoc( $result ) )
	{
		if ( $max_width < $row[Width] ) $max_width = $row[Width];
		if ( $max_height < $row[Height] ) $max_height = $row[Height];
		$sql = "select count(Id) as ZoneCount, count(if(Type='Active',1,NULL)) as ActZoneCount, count(if(Type='Inclusive',1,NULL)) as IncZoneCount, count(if(Type='Exclusive',1,NULL)) as ExcZoneCount, count(if(Type='Inactive',1,NULL)) as InactZoneCount from Zones where MonitorId = '$row[Id]'";
		$result2 = mysql_query( $sql );
		if ( !$result2 )
			echo mysql_error();
		$row2 = mysql_fetch_assoc( $result2 );
		$monitors[] = array_merge( $row, $row2 );
		if ( $row['Function'] != 'None' ) $cycle_count++;
	}

	$sql = "select distinct Device from Monitors order by Device";
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$devices = array();

	while( $row = mysql_fetch_assoc( $result ) )
	{
		$ps_array = preg_split( "/\s+/", exec( "ps -edalf | grep 'zmc $row[Device]' | grep -v grep" ) );
		if ( $ps_array[3] )
		{
			$row['zmc'] = 1;
		}
		$ps_array = preg_split( "/\s+/", exec( "ps -edalf | grep 'zma $row[Device]' | grep -v grep" ) );
		if ( $ps_array[3] )
		{
			$row['zma'] = 1;
		}
		$devices[] = $row;
	}
?>
<wml>
<card id="zmConsole" title="ZM - Console" ontimer="<?= $PHP_SELF ?>?view=<?= $view ?>">
<timer value="<?= REFRESH_MAIN*10 ?>"/>
<p mode="nowrap" align="center"><strong>ZM - Console</strong></p>
<p mode="nowrap" align="center"><?= count($monitors) ?> Monitors - <?= strftime( "%T" ) ?></p>
<p mode="nowrap" align="center"><?= $HTTP_SESSION_VARS[event_reset_time] ?></p>
<p align="center">
<table columns="3">
<tr>
<td>Name</td>
<td>Func</td>
<td>Events</td>
</tr>
<?php
	$reset_event_count = 0;
	foreach( $monitors as $monitor )
	{
		$device = $devices[$monitor[Device]];
		$reset_event_count += $monitor[ResetEventCount];
?>
<tr>
<td><a href="<?= $PHP_SELF ?>?view=feed&amp;mid=<?= $monitor[Id] ?>"><?= $monitor[Name] ?></a></td>
<td><a href="<?= $PHP_SELF ?>?view=function&amp;mid=<?= $monitor[Id] ?>"><?= substr( $monitor['Function'], 0, 1 ) ?></a></td>
<td><a href="<?= $PHP_SELF ?>?view=events&amp;mid=<?= $monitor[Id] ?>"><?= $monitor[ResetEventCount] ?></a></td>
</tr>
<?php
	}
?>
</table>
</p>
<p mode="nowrap" align="center"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;action=reset">Reset Event Counts</a></p>
</card>
</wml>
<?php
}
elseif( $view == "feed" )
{
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );
	$browser = array();
	$browser[Width] = 100;
	$browser[Height] = 80;

	// Generate an image
	chdir( ZM_DIR_IMAGES );
	$status = exec( escapeshellcmd( ZMU_PATH." -m $mid -i" ) );
	$monitor_image = "$monitor[Name].jpg";
	$image_time = filemtime( $monitor_image );
	$browser_image = "$monitor[Name]-wap-$image_time.jpg";
	$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $monitor_image | ".ZM_PATH_NETPBM."/pnmscale -xysize $browser[Width] $browser[Height] | ".ZM_PATH_NETPBM."/ppmtojpeg > $browser_image";
	exec( $command );
	chdir( '..' );
?>
<wml>
<card id="zmFeed" title="ZM - <?= $monitor[Name] ?>" ontimer="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;mid=<?= $mid ?>">
<timer value="<?= REFRESH_IMAGE*10 ?>"/>
<p mode="nowrap" align="center"><strong>ZM - <?= $monitor[Name] ?></strong></p>
<p mode="nowrap" align="center"><img src="<?= ZM_DIR_IMAGES.'/'.$browser_image ?>" alt="<?= $monitor[Name] ?>" hspace="0" vspace="0" align="middle"/></p>
</card>
</wml>
<?php
	flush();
}
?>
