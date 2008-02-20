<?php
//
// ZoneMinder web events view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}

$sql = "select * from Monitors";
foreach ( dbFetchAll( $sql ) as $row )
{
	$monitors[$row[Id]] = $row;
}

if ( $filter_name )
{
    $db_filter = dbFetchOne( "select * from Filters where Name = '$filter_name'" );
    $filter = unserialize( $db_filter['Query'] );
    $sort_field = $filter['sort_field'];
    $sort_asc = $filter['sort_asc'];
    $limit = $filter['limit'];
}

if ( !$sort_field )
{
    $sort_field = "DateTime";
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
parseFilter( $filter, true, '&amp;' );

if ( $filter['sql'] )
{
    $count_sql .= $filter['sql'];
    $events_sql .= $filter['sql'];
}
$events_sql .= " order by $sort_column $sort_order";

$device_lines = (isset($device)&&!empty($device['lines']))?$device['lines']:DEVICE_LINES;
// Allow for headers etc
$device_lines -= 2;

if ( $page )
{
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

noCacheHeaders();
header("Content-type: application/xhtml+xml" );
echo( '<?xml version="1.0" encoding="iso-8859-1"?>'."\n" );
?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangEvents ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css"/>
<?php
$n_events = dbFetchOne( $count_sql, 'EventCount' );
if ( !empty($limit) && $n_events > $limit )
{
	$n_events = $limit;
}
?>
</head>
<body>
<table style="width: 100%">
<tr>
<td align="left"><?= sprintf( $zmClangEventCount, $n_events, zmVlang( $zmVlangEvent, $n_events ) ) ?></td>
<td align="right"><?= makeLink( "$PHP_SELF?view=filter", empty($filter_name)?$zmSlangChooseFilter:$filter_name, canView( 'Events' ) ) ?></td>
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
<td>&nbsp;</td>
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
<td align="center"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter['query'] ?><?= $sort_query ?>&amp;page=1">&lt;&lt;</a></td>
<?php
				}
?>
<td align="center"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter['query'] ?><?= $sort_query ?>&amp;page=<?= $page-1 ?>">&lt;</a></td>
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
<td align="center"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter['query'] ?><?= $sort_query ?>&amp;page=<?= $new_page ?>"><?= $new_page ?></a></td>
<?php
				}
			}
?>
<td align="center"><?= $page ?></td>
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
<td align="center"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter['query'] ?><?= $sort_query ?>&amp;page=<?= $new_page ?>"><?= $new_page ?></a></td>
<?php
				}
?>
<td align="center"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter['query'] ?><?= $sort_query ?>&amp;page=<?= $page+1 ?>">&gt;</a></td>
<?php
				if ( false && $page < ($pages-1) )
				{
?>
<td align="center"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;limit=<?= $limit ?><?= $filter['query'] ?><?= $sort_query ?>&amp;page=<?= $pages ?>">&gt;&gt;</a></td>
<?php
				}
			}
		}
?>
<td>&nbsp;</td>
</tr>
</table>
<?php
	}
?>
<table style="width: 100%; background-color: #7F7FB2">
<?php
	flush();
	$count = 0;
    foreach ( dbFetchAll( $events_sql ) as $event )
	{
		if ( ($count++%$device_lines) == 0 )
		{
?>
<tr align="center" bgcolor="#FFFFFF">
<td style="width:50px"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;page=1&amp;sort_field=Id&amp;sort_asc=<?= $sort_field == 'Id'?!$sort_asc:0 ?>&amp;limit=<?= $limit ?>"><?= substr( $zmSlangId, 0, 5 ) ?><?php if ( $sort_field == "Id" ) if ( $sort_asc ) echo "<small><i>^</i></small>"; else echo "<small><i>v</i></small>"; ?></a></td>
<td style="width:60px"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;page=1&amp;sort_field=StartTime&amp;sort_asc=<?= $sort_field == 'StartTime'?!$sort_asc:0 ?>&amp;limit=<?= $limit ?>"><?= substr( $zmSlangTime, 0, 5 ) ?><?php if ( $sort_field == "StartTime" ) if ( $sort_asc ) echo "<small><i>^</i></small>"; else echo "<small><i>v</i></small>"; ?></a></td>
<td style="width:35px"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;page=1&amp;sort_field=Secs&amp;sort_asc=<?= $sort_field == 'Secs'?!$sort_asc:0 ?>&amp;limit=<?= $limit ?>"><?= substr( $zmSlangDuration, 0, 2 ) ?><?php if ( $sort_field == "Secs" ) if ( $sort_asc ) echo "<small><i>^</i></small>"; else echo "<small><i>v</i></small>"; ?></a></td>
<td style="width:30px"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;page=1&amp;sort_field=AlarmFrames&amp;sort_asc=<?= $sort_field == 'AlarmFrames'?!$sort_asc:0 ?>&amp;limit=<?= $limit ?>"><?= substr( $zmSlangFrames, 0, 2 ) ?><?php if ( $sort_field == "AlarmFrames" ) if ( $sort_asc ) echo "<small><i>^</i></small>"; else echo "<small><i>v</i></small>"; ?></a></td>
<td style="width:30px"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&amp;page=1&amp;sort_field=TotScore&amp;sort_asc=<?= $sort_field == 'TotScore'?!$sort_asc:0 ?>&amp;limit=<?= $limit ?>"><?= substr( $zmSlangScore, 0, 2 ) ?><?php if ( $sort_field == "TotScore" ) if ( $sort_asc ) echo "<small><i>^</i></small>"; else echo "<small><i>v</i></small>"; ?></a></td>
</tr>
<?php
		}
		unset( $bgcolor );
?>
<tr<?= ' bgcolor="'.(isset($bgcolor)?$bgcolor:"#FFFFFF").'"' ?> >
<td align="center"><a href="<?= $PHP_SELF ?>?view=eventdetails&amp;eid=<?= $event['Id'] ?>&amp;page=1"><?= $event['Id'] ?><?php if ( $event['Archived'] ) echo "*" ?></a></td>
<td align="center"><?= strftime( "%d/%H:%M", strtotime($event['StartTime']) ) ?></td>
<td align="center"><?= sprintf( "%d", $event['Length'] ) ?></td>
<td align="center"><a href="<?= $PHP_SELF ?>?view=event&amp;eid=<?= $event['Id'] ?>&amp;page=1"><?= $event['AlarmFrames'] ?></a></td>
<td align="center"><a href="<?= $PHP_SELF ?>?view=frame&amp;eid=<?= $event['Id'] ?>&amp;fid=0"><?= $event['MaxScore'] ?></a></td>
</tr>
<?php
	}
?>
</table>
<p align="center"><a href="<?= $PHP_SELF ?>?view=console"><?= $zmSlangConsole ?></a></p>
</body>
</html>
