<?php
//
// ZoneMinder web time-line view file, $Date$, $Revision$
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

$count_sql = "select count(E.Id) as EventCount from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where";
$events_sql = "select E.Id,E.MonitorId,M.Name As MonitorName,M.Width,M.Height,E.Name,E.Cause,E.StartTime,E.EndTime,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived,E.LearnState from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where";
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

parseSort();
parseFilter();

if ( $filter_sql )
{
	$count_sql .= $filter_sql;
	$events_sql .= $filter_sql;
}
$events_sql .= " order by $sort_column $sort_order";
if ( $page )
{
	$limit_start = (($page-1)*ZM_WEB_EVENTS_PER_PAGE);
	if ( empty( $limit ) )
	{
		$limit_amount = ZM_WEB_EVENTS_PER_PAGE;
	}
	else
	{
		$limit_left = $limit - $limit_start;
		$limit_amount = ($limit_left>ZM_WEB_EVENTS_PER_PAGE)?ZM_WEB_EVENTS_PER_PAGE:$limit_left;
	}
	$events_sql .= " limit $limit_start, $limit_amount";
}
elseif ( !empty( $limit ) )
{
	$events_sql .= " limit 0, $limit";
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangEvents ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function newWindow(Url,Name,Width,Height)
{
   	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function eventWindow(Url,Name,Width,Height)
{
	var Name = window.open(Url,Name,"resizable,width="+Width+",height="+Height );
}
function filterWindow(Url,Name)
{
	var Name = window.open(Url,Name,"resizable,scrollbars,width=<?= $jws['filter']['w'] ?>,height=<?= $jws['filter']['h'] ?>");
}
function closeWindow()
{
	window.close();
	// This is a hack. The only way to close an existing window is to try and open it!
	var filterWindow = window.open( "<?= $PHP_SELF ?>?view=none", 'zmFilter', 'width=1,height=1' );
	filterWindow.close();
}
var width = document.window.screen.availWidth;
var height = document.window.screen.availHeight;

window.focus();
<?php
if ( isset($filter) )
{
?>
//opener.location.reload(true);
filterWindow( '<?= $PHP_SELF ?>?view=filter&page=<?= $page ?><?= $filter_query ?>', 'zmFilter' );
location.replace( '<?= $PHP_SELF ?>?view=<?= $view ?>&page=<?= $page ?><?= $filter_query ?>' );
</script>
</head>
</html>
<?php
}
else
{
	if ( !($result = mysql_query( $count_sql )) )
		die( mysql_error() );
	$row = mysql_fetch_assoc( $result );
	$n_events = $row['EventCount'];
	if ( !empty($limit) && $n_events > $limit )
	{
		$n_events = $limit;
	}
	if ( !($result = mysql_query( $events_sql )) )
		die( mysql_error() );
	$max_width = 0;
	$max_height = 0;
	$archived = false;
	$unarchived = false;
	$events = array();
	while( $event = mysql_fetch_assoc( $result ) )
	{
		$events[] = $event;
		if ( $max_width < $event['Width'] ) $max_width = $event['Width'];
		if ( $max_height < $event['Height'] ) $max_height = $event['Height'];
		if ( $event['Archived'] )
			$archived = true;
		else
			$unarchived = true;
	}
?>
function viewEvents( form, name )
{
	var events = new Array();
	for (var i = 0; i < form.elements.length; i++)
	{
		if ( form.elements[i].name.indexOf(name) == 0)
		{
			if ( form.elements[i].checked )
			{
				events[events.length] = form.elements[i].value;
			}
		}
	}
	if ( events.length > 0 )
	{
		eventWindow( '<?= $PHP_SELF ?>?view=event&eid='+events[0]+'&trms=1&attr1=Id&op1=%3D%5B%5D&val1='+events.join('%2C')+'<?= $sort_query ?>&page=1&play=1', 'zmEvent', <?= $max_width+$jws['event']['w']  ?>, <?= $max_height+$jws['event']['h'] ?> );
	}
}
</script>
</head>
<body>
<div style="text-align: center; margin: auto">
<form name="event_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="">
<input type="hidden" name="page" value="<?= $page ?>">
<?= $filter_fields ?>
<input type="hidden" name="sort_field" value="<?= $sort_field ?>">
<input type="hidden" name="sort_asc" value="<?= $sort_asc ?>">
<input type="hidden" name="limit" value="<?= $limit ?>">
<?php
$width=600;
$height=400;
$start_date = "2005-09-20 00:00:00";
$end_date = "2005-09-21 00:00:00";
$start_secs = strtotime( $start_date );
$end_secs = strtotime( $end_date );
$total_secs = ($end_secs - $start_secs) + 1;
$secs_per_pixel = $total_secs/$width;
$event_array = array();
foreach ( $events as $event )
{
	$event_start_index = (strtotime( $event['StartTime'] ) - $start_secs) / $width;
	$event_end_index = (strtotime( $event['EndTime'] ) - $end_secs) / $width;
	for ( $i = $event_start_index; $i <= $event_end_index; $i++ )
	{
		$event_array[$i] = 1;
	}
}
?>
</form>
</div>
</body>
</html>
