<?php
//
// ZoneMinder web events view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}

$sql = "select * from Monitors";
if ( !($result = mysql_query( $sql )) )
	die( mysql_error() );
while( $row = mysql_fetch_assoc( $result ) )
{
	$monitors[$row[Id]] = $row;
}
mysql_free_result( $result );

if ( $filter_name )
{
	$result = mysql_query( "select * from Filters where Name = '$filter_name'" );
	if ( !$result )
		die( mysql_error() );
	$filter_data = mysql_fetch_assoc( $result );
	mysql_free_result( $result );

	if ( !empty($filter_data) )
	{
		foreach( split( '&', $filter_data['Query'] ) as $filter_parm )
		{
			list( $key, $value ) = split( '=', $filter_parm, 2 );
			if ( $key )
			{
				$$key = $value;
			}
		}
	}
	if ( !$sort_field )
	{
		$sort_field = "DateTime";
	}
}

$count_sql = "select count(E.Id) as EventCount from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where";
$events_sql = "select E.Id,E.MonitorId,M.Name As MonitorName,E.Name,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived,E.LearnState from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where";
if ( $user['MonitorIds'] )
{
	$count_sql .= " M.Id in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
	$events_sql .= " M.Id in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
}
else
{
	$count_sql .= " 1";
	$events_sql .= " 1";
}

parseSort( true, '&amp;' );
parseFilter( true, '&amp;' );

if ( $filter_sql )
{
	$count_sql .= $filter_sql;
	$events_sql .= $filter_sql;
}
$events_sql .= " order by $sort_column $sort_order";

if ( $page )
{
	$device_lines = (isset($device)&&!empty($device['lines']))?$device['lines']:DEVICE_LINES;
	// Allow for headers etc
	$device_lines -= 2;

	$limit_start = (($page-1)*$device_lines);
	if ( empty( $limit ) )
	{
		$limit_amount = $device_lines;
	}
	else
	{
		$limit_left = $limit - $limit_start;
		$limit_amount = ($limit_left>$device_lines)?$device_lines:$limit_left;
	}
	$events_sql .= " limit $limit_start, $limit_amount";
}
elseif ( !empty( $limit ) )
{
	$events_sql .= " limit 0, $limit";
}

?>
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangEvents ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css">
<?php
if ( !($result = mysql_query( $count_sql )) )
	die( mysql_error() );
$row = mysql_fetch_assoc( $result );
mysql_free_result( $result );
$n_events = $row['EventCount'];
if ( !empty($limit) && $n_events > $limit )
{
	$n_events = $limit;
}
?>
</head>
<body>
<table>
<tr>
<td align="left"><?= sprintf( $zmClangEventCount, $n_events, zmVlang( $zmVlangEvent, $n_events ) ) ?></td>
<td align="right"><?= makeLink( "$PHP_SELF?view=filter", empty($filter_data)?$zmSlangChooseFilter:$filter_name, canView( 'Events' ) ) ?></td>
</tr>
</table>
<?php
	$pages = (int)ceil($n_events/$device_lines);
	$max_shortcuts = 3;
	if ( $pages > 1 )
	{
?>
<table style="width:100%">
<tr>
<?php
		if ( !empty($page) )
		{
			if ( $page < 0 )
				$page = 1;
			if ( $page > $pages )
				$page = $pages;

			if ( $page > 1 )
			{
				if ( false && $page > 2 )
				{
?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter_query ?><?= $sort_query ?>&amp;page=1">&lt;&lt;</a></td>
<?php
				}
?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter_query ?><?= $sort_query ?>&amp;page=<?= $page-1 ?>">&lt;</a></td>
<?php
				$new_pages = array();
				$pages_used = array();
				$lo_exp = max(2,log($page-1)/log($max_shortcuts));
				for ( $i = 0; $i < $max_shortcuts; $i++ )
				{
					$new_page = round($page-pow($lo_exp,$i));
					if ( isset($pages_used[$new_page]) )
						continue;
					if ( $new_page <= 1 )
						break;
					$pages_used[$new_page] = true;
					array_unshift( $new_pages, $new_page );
				}
				if ( !isset($pages_used[1]) )
					array_unshift( $new_pages, 1 );

				foreach ( $new_pages as $new_page )
				{
?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter_query ?><?= $sort_query ?>&amp;page=<?= $new_page ?>"><?= $new_page ?></a></td>
<?php
				}
			}
?>
<td align="center" class="text"><?= $page ?></td>
<?php
			if ( $page < $pages )
			{
				$new_pages = array();
				$pages_used = array();
				$hi_exp = max(2,log($pages-$page)/log($max_shortcuts));
				for ( $i = 0; $i < $max_shortcuts; $i++ )
				{
					$new_page = round($page+pow($hi_exp,$i));
					if ( isset($pages_used[$new_page]) )
						continue;
					if ( $new_page > $pages )
						break;
					$pages_used[$new_page] = true;
					array_push( $new_pages, $new_page );
				}
				if ( !isset($pages_used[$pages]) )
					array_push( $new_pages, $pages );

				foreach ( $new_pages as $new_page )
				{
?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter_query ?><?= $sort_query ?>&amp;page=<?= $new_page ?>"><?= $new_page ?></a></td>
<?php
				}
?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter_query ?><?= $sort_query ?>&amp;page=<?= $page+1 ?>">&gt;</a></td>
<?php
				if ( false && $page < ($pages-1) )
				{
?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter_query ?><?= $sort_query ?>&amp;page=<?= $pages ?>">&gt;&gt;</a></td>
<?php
				}
			}
		}
?>
</tr>
</table>
<?php
	}
?>
<table bgcolor="#7F7FB2">
<?php
	flush();
	$count = 0;
	if ( !($result = mysql_query( $events_sql )) )
		die( mysql_error() );
	while( $event = mysql_fetch_assoc( $result ) )
	{
		if ( ($count++%$device_lines) == 0 )
		{
?>
<tr align="center" bgcolor="#FFFFFF">
<td style="width:50px"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1&sort_field=Id&sort_asc=<?= $sort_field == 'Id'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= substr( $zmSlangId, 0, 5 ) ?><?php if ( $sort_field == "Id" ) if ( $sort_asc ) echo "<small><i>^</i></small>"; else echo "<small><i>v</i></small>"; ?></a></td>
<td style="width:60px"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1&sort_field=StartTime&sort_asc=<?= $sort_field == 'StartTime'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= substr( $zmSlangTime, 0, 5 ) ?><?php if ( $sort_field == "StartTime" ) if ( $sort_asc ) echo "<small><i>^</i></small>"; else echo "<small><i>v</i></small>"; ?></a></td>
<td style="width:35px"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1&sort_field=Secs&sort_asc=<?= $sort_field == 'Secs'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= substr( $zmSlangDuration, 0, 2 ) ?><?php if ( $sort_field == "Secs" ) if ( $sort_asc ) echo "<small><i>^</i></small>"; else echo "<small><i>v</i></small>"; ?></a></td>
<td style="width:30px"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1&sort_field=AlarmFrames&sort_asc=<?= $sort_field == 'AlarmFrames'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= substr( $zmSlangFrames, 0, 2 ) ?><?php if ( $sort_field == "AlarmFrames" ) if ( $sort_asc ) echo "<small><i>^</i></small>"; else echo "<small><i>v</i></small>"; ?></a></td>
<td style="width:30px"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1&sort_field=TotScore&sort_asc=<?= $sort_field == 'TotScore'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= substr( $zmSlangScore, 0, 2 ) ?><?php if ( $sort_field == "TotScore" ) if ( $sort_asc ) echo "<small><i>^</i></small>"; else echo "<small><i>v</i></small>"; ?></a></td>
</tr>
<?php
		}
		unset( $bgcolor );
?>
<tr<?= ' bgcolor="'.(isset($bgcolor)?$bgcolor:"#FFFFFF").'"' ?> >
<td align="center"><a href="<?= $PHP_SELF ?>?view=eventdetails&eid=<?= $event['Id'] ?>&page=1"><?= $event['Id'] ?><?php if ( $event['Archived'] ) echo "*" ?></a></td>
<td align="center"><?= strftime( "%d/%H:%M", strtotime($event['StartTime']) ) ?></td>
<td align="center"><?= sprintf( "%d", $event['Length'] ) ?></td>
<td align="center"><a href="<?= $PHP_SELF ?>?view=event&eid=<?= $event['Id'] ?>&page=1"><?= $event['AlarmFrames'] ?></a></td>
<td align="center"><a href="<?= $PHP_SELF ?>?view=frame&eid=<?= $event['Id'] ?>&fid=0"><?= $event['MaxScore'] ?></a></td>
</tr>
<?php
	}
	mysql_free_result( $result );
?>
</table>
</body>
</html>
