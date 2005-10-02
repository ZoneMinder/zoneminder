<?php
//
// ZoneMinder web timeline view file, $Date$, $Revision$
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

error_reporting( E_ALL );

$mouseover = true;
if ( !isset($mouseover) )
	$mouseover = true;

$mode = "overlay";
if ( !isset($mode) )
	$mode = "overlay";

$min_event_width = 5;
$max_event_width = 20;

$chart = array(
	"width"=>700,
	"height"=>460,
	"image" => array(
		"width"=>200,
		"height"=>200,
		"top_offset"=>20,
	),
	"image_text" => array(
		"width"=>400,
		"height"=>30,
		"top_offset"=>20,
	),
	"graph" => array(
		"width"=>600,
		"height"=>160,
		"top_offset"=>30,
	),
	"title" => array(
		"top_offset"=>50
	),
	"key" => array(
		"top_offset"=>50
	),
	"axes" => array(
		"x" => array(
			"height" => 20,
		),
		"y" => array(
			"width" => 30,
		),
	),
	"grid" => array(
		"x" => array(
			"major" => array(
				"max" => 12,
				"min" => 4,
			),
			"minor" => array(
				"max" => 48,
				"min" => 12,
			),
		),
		"y" => array(
			"major" => array(
				"max" => 8,
				"min" => 1,
			),
			"minor" => array(
				"max" => 0,
				"min" => 0,
			),
		),
	),
);

$monitors = array();
$monitors_sql = "select * from Monitors order by Id asc";
if ( !($result = mysql_query( $monitors_sql )) )
	die( mysql_error() );
srand( 97981 );
while ( $row = mysql_fetch_assoc( $result ) )
{
	if ( empty($row['WebColour']) )
	{
		$row['WebColour'] = sprintf( "#%02x%02x%02x", rand( 0, 255 ), rand( 0, 255), rand( 0, 255 ) );
	}
	$monitors[$row['Id']] = $row;
}

$range_sql = "select min(E.StartTime) as MinTime, max(E.EndTime) as MaxTime from Events as E inner join Monitors as M on (E.MonitorId = M.Id) where not isnull(E.StartTime) and not isnull(E.EndTime)";
$events_sql = "select E.Id,E.Name,E.StartTime,E.EndTime,E.Length,E.Frames,E.MaxScore,E.MonitorId from Events as E inner join Monitors as M on (E.MonitorId = M.Id) where not isnull(StartTime)";

if ( $user['MonitorIds'] )
{
	$mon_filter_sql = " and M.Id in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";

	$range_sql .= $mon_filter_sql;
	$events_sql .= $mon_filter_sql;
}

if ( isset($range) )
{
	$half_range = (int)($range/2);
	if ( isset($mid_time) )
	{
		$mid_time_t = strtotime($mid_time);
		$min_time_t = $mid_time_t-$half_range; 
		$max_time_t = $mid_time_t+$half_range; 
		if ( !($range%1) )
		{
			$max_time_t--;
		}
		$min_time = date( "Y-m-d H:i:s", $min_time_t );
		$max_time = date( "Y-m-d H:i:s", $max_time_t );
	}
	elseif ( isset($min_time) )
	{
		$min_time_t = strtotime($min_time);
		$max_time_t = $min_time_t + $range;
		$mid_time_t = $min_time_t + $half_range;
		$mid_time = date( "Y-m-d H:i:s", $mid_time_t );
		$max_time = date( "Y-m-d H:i:s", $max_time_t );
	}
	elseif ( isset($max_time) )
	{
		$max_time_t = strtotime($max_time);
		$min_time_t = $max_time_t - $range;
		$mid_time_t = $min_time_t + $half_range;
		$min_time = date( "Y-m-d H:i:s", $min_time_t );
		$mid_time = date( "Y-m-d H:i:s", $mid_time_t );
	}
}
elseif ( isset($min_time) && isset($max_time) )
{
	$min_time_t = strtotime($min_time);
	$max_time_t = strtotime($max_time);
	$range = ($max_time_t - $min_time_t) + 1;
	$half_range = (int)($range/2);
	$mid_time_t = $min_time_t + $half_range;
	$mid_time = date( "Y-m-d H:i:s", $mid_time_t );
}

if ( !isset($min_time) || !isset($max_time) )
{
	parseFilter();
	if ( $filter_sql )
	{
		$range_sql .= $filter_sql;
		$events_sql .= $filter_sql;
	}

	// Dynamically determine range
	if ( !($result = mysql_query( $range_sql )) )
		die( mysql_error() );
	$row = mysql_fetch_assoc( $result );

	if ( !isset($min_time) )
		$min_time = $row['MinTime'];
	if ( !isset($max_time) )
		$max_time = $row['MaxTime'];
	$min_time_t = strtotime($min_time);
	$max_time_t = strtotime($max_time);
	$range = ($max_time_t - $min_time_t) + 1;
	$half_range = (int)($range/2);
	$mid_time_t = $min_time_t + $half_range;
	$mid_time = date( "Y-m-d H:i:s", $mid_time_t );
}

$scales = array(
	array( "name"=>"year",     "factor"=>60*60*24*365, "align"=>1,  "zoomout"=>2, "label"=>"Y" ),
	array( "name"=>"month",    "factor"=>60*60*24*30,  "align"=>1,  "zoomout"=>12, "label"=>"M" ),
	array( "name"=>"week",     "factor"=>60*60*24*7,   "align"=>1,  "zoomout"=>4.25, "label"=>"j/n",  "label_check"=>"W" ),
	array( "name"=>"day",      "factor"=>60*60*24,     "align"=>1,  "zoomout"=>7, "label"=>"j" ),
	array( "name"=>"hour",     "factor"=>60*60,        "align"=>1,  "zoomout"=>24, "label"=>"H:00", "label_check"=>"H" ),
	array( "name"=>"minute10", "factor"=>60,           "align"=>10, "zoomout"=>6, "label"=>"H:i",  "label_check"=>"i" ),
	array( "name"=>"minute",   "factor"=>60,           "align"=>1,  "zoomout"=>10, "label"=>"H:i",  "label_check"=>"i" ),
	array( "name"=>"second10", "factor"=>1,            "align"=>10, "zoomout"=>6, "label"=>"s" ),
	array( "name"=>"second",   "factor"=>1,            "align"=>1,  "zoomout"=>10, "label"=>"s" ),
);

$maj_x_scale = getDateScale( $scales, $range, $chart['grid']['x']['major']['min'], $chart['grid']['x']['major']['max'] );
//print_r( $maj_x_scale );

// Adjust the range etc for scale
$min_time_t -= $min_time_t%($maj_x_scale['factor']*$maj_x_scale['align']);
$min_time = date( "Y-m-d H:i:s", $min_time_t );
$max_time_t += (($maj_x_scale['factor']*$maj_x_scale['align'])-$max_time_t%($maj_x_scale['factor']*$maj_x_scale['align']))-1;
if ( $max_time_t > time() )
	$max_time_t = time();
$max_time = date( "Y-m-d H:i:s", $max_time_t );
$range = ($max_time_t - $min_time_t) + 1;
$half_range = (int)($range/2);
$mid_time_t = $min_time_t + $half_range;
$mid_time = date( "Y-m-d H:i:s", $mid_time_t );

//echo "R:$range<br>";
//echo "MnT:$min_time<br>";
//echo "MnTt:$min_time_t<br>";
//echo "MdT:$mid_time<br>";
//echo "MdTt:$mid_time_t<br>";
//echo "MxT:$max_time<br>";
//echo "MxTt:$max_time_t<br>";

if ( isset($min_time) && isset($max_time) )
{
	$range_sql .= " and E.EndTime >= '$min_time' and E.StartTime <= '$max_time'";
	$events_sql .= " and E.EndTime >= '$min_time' and E.StartTime <= '$max_time'";
}
$events_sql .= " order by Id asc";
//echo "ESQL: $events_sql<br>";

$chart['data'] = array(
	"x" => array(
		"lo" => strtotime( $min_time ),
		"hi" => strtotime( $max_time ),
	),
	"y" => array(
		"lo" => 0,
		"hi" => 0,
	)
);

$chart['data']['x']['range'] = ($chart['data']['x']['hi'] - $chart['data']['x']['lo']) + 1;
$chart['data']['x']['density'] = $chart['data']['x']['range']/$chart['graph']['width'];

$mon_event_slots = array();
$mon_frame_slots = array();
if ( !($event_result = mysql_query( $events_sql )) )
	die( mysql_error() );
$monitor_ids = array();
//echo "YYY:".date( "r" )."<br>"; flush();
while( $event = mysql_fetch_assoc( $event_result ) )
{
	if ( !isset($monitor_ids[$event['MonitorId']]) )
		$monitor_ids[$event['MonitorId']] = true;

	if ( !isset($mon_event_slots[$event['MonitorId']]) )
		$mon_event_slots[$event['MonitorId']] = array();
	if ( !isset($mon_frame_slots[$event['MonitorId']]) )
		$mon_frame_slots[$event['MonitorId']] = array();

	$curr_event_slots = &$mon_event_slots[$event['MonitorId']];
	$curr_frame_slots = &$mon_frame_slots[$event['MonitorId']];

	$start_time_t = strtotime($event['StartTime']);
	$start_index = $raw_start_index = (int)(($start_time_t - $chart['data']['x']['lo']) / $chart['data']['x']['density']);
	if ( $start_index < 0 )
		$start_index = 0;

	if ( isset($event['EndTime']) )
	{
		$end_time_t = strtotime($event['EndTime']);
	}
	else
	{
		$end_time_t = time();
	}
	$end_index = $raw_end_index = (int)(($end_time_t - $chart['data']['x']['lo']) / $chart['data']['x']['density']);
	if ( $end_index >= $chart['graph']['width'] )
		$end_index = $chart['graph']['width'] - 1;

	for ( $i = $start_index; $i <= $end_index; $i++ )
	{
		if ( !isset($curr_event_slots[$i]) )
		{
			if ( $raw_start_index == $raw_end_index )
			{
				$offset = 1;
			}
			else
			{
				$offset = 1+ ($event['Frames']?((int)($event['Frames']*(($i-$raw_start_index)/($raw_end_index-$raw_start_index)))):0);
			}
			$curr_event_slots[$i] = array( "count"=>0, "width"=>1, "offset"=>$offset, "event"=>$event );
		}
		else
		{
			$curr_event_slots[$i]['count']++;
		}
	}
	if ( $event['MaxScore'] > 0 )
	{
		if ( $start_index == $end_index )
		{
			$i = $start_index;
			if ( !isset($curr_frame_slots[$i]) )
			{
				$curr_frame_slots[$i] = array( "count"=>1, "value"=>$event['MaxScore'], "event"=>$event );
			}
			else
			{
				$curr_frame_slots[$i]['count']++;
				if ( $event['MaxScore'] > $curr_frame_slots[$i]['value'] )
				{
					$curr_frame_slots[$i]['value'] = $event['MaxScore'];
					$curr_frame_slots[$i]['event'] = $event;
				}
			}
			if ( $event['MaxScore'] > $chart['data']['y']['hi'] )
			{
				$chart['data']['y']['hi'] = $event['MaxScore'];
			}
		}
		else
		{
			$frames_sql = "select F.FrameId,F.Delta,unix_timestamp(F.TimeStamp) as TimeT,F.Score from Frames as F where F.EventId = '".$event['Id']."' and F.Score > 0";
			if ( !($frame_result = mysql_query( $frames_sql )) )
				die( mysql_error() );
			while( $frame = mysql_fetch_assoc( $frame_result ) )
			{
				$frame_time_t = $frame['TimeT'];
				$frame_time_t = $start_time_t + $frame['Delta'];
				$frame_index = (int)(($frame_time_t - $chart['data']['x']['lo']) / $chart['data']['x']['density']);
				if ( $frame_index < 0 )
					continue;
				if ( $frame_index >= $chart['graph']['width'] )
					continue;

				if ( !isset($curr_frame_slots[$frame_index]) )
				{
					$curr_frame_slots[$frame_index] = array( "count"=>1, "value"=>$frame['Score'], "event"=>$event, "frame"=>$frame );
				}
				else
				{
					$curr_frame_slots[$frame_index]['count']++;
					if ( $frame['Score'] > $curr_frame_slots[$frame_index]['value'] )
					{
						$curr_frame_slots[$frame_index]['value'] = $frame['Score'];
						$curr_frame_slots[$frame_index]['event'] = $event;
						$curr_frame_slots[$frame_index]['frame'] = $frame;
					}
				}
				if ( $frame['Score'] > $chart['data']['y']['hi'] )
				{
					$chart['data']['y']['hi'] = $frame['Score'];
				}
			}
		}
	}
}

ksort($monitor_ids,SORT_NUMERIC);
ksort($mon_event_slots,SORT_NUMERIC);
ksort($mon_frame_slots,SORT_NUMERIC);

//echo "AAA:".date( "r" )."<br>"; flush();
// Add on missing frames
$xcount = 0;
foreach( array_keys($mon_frame_slots) as $monitor_id )
{
	unset( $curr_frame_slots );
	$curr_frame_slots = &$mon_frame_slots[$monitor_id];
	for ( $i = 0; $i < $chart['graph']['width']; $i++ )
	{
		if ( isset($curr_frame_slots[$i]) )
		{
			if ( !isset($curr_frame_slots[$i]['frame']) )
			{
				$xcount++;
				$frames_sql = "select F.FrameId,F.Score from Frames as F where F.EventId = '".$curr_frame_slots[$i]['event']['Id']."' and F.Score > 0 order by F.FrameId limit 0,1";
				if ( !($frame_result = mysql_query( $frames_sql )) )
					die( mysql_error() );
				$curr_frame_slots[$i]['frame'] = mysql_fetch_assoc( $frame_result );
			}
		}
	}
}
//echo "Fetched $xcount frames<br>";
//echo "BBB:".date( "r" )."<br>"; flush();

$chart['data']['y']['range'] = ($chart['data']['y']['hi'] - $chart['data']['y']['lo']) + 1;
$chart['data']['y']['density'] = $chart['data']['y']['range']/$chart['graph']['height'];

$maj_y_scale = getYScale( $chart['data']['y']['range'], $chart['grid']['y']['major']['min'], $chart['grid']['y']['major']['max'] );
//print_r( $maj_y_scale );

$max_width = 0;
$max_height = 0;

foreach ( array_keys($monitor_ids) as $monitor_id )
{
	if ( $max_width < $monitors[$monitor_id]['Width'] )
		$max_width = $monitors[$monitor_id]['Width'];
	if ( $max_height < $monitors[$monitor_id]['Height'] )
		$max_height = $monitors[$monitor_id]['Height'];
}

//echo "ZZZ:".date( "r" )."<br>"; flush();
// Optimise boxes
foreach( array_keys($mon_event_slots) as $monitor_id )
{
	unset( $curr_event_slots );
	$curr_event_slots = &$mon_event_slots[$monitor_id];
	for ( $i = 0; $i < $chart['graph']['width']; $i++ )
	{
		if ( isset($curr_event_slots[$i]) )
		{
			//if ( isset($curr_slot) && (($curr_slot['width'] < $min_event_width) || (($curr_slot['event']['Id'] == $curr_event_slots[$i]['event']['Id']) && ($curr_slot['frame']['FrameId'] == $curr_event_slots[$i]['frame']['FrameId'])) ) )
			//if ( isset($curr_slot) && ($curr_slot['event']['Id'] == $curr_event_slots[$i]['event']['Id']) )
			if ( isset($curr_slot) )
			{
				if ( $curr_slot['event']['Id'] == $curr_event_slots[$i]['event']['Id'] )
				{
					if ( $curr_slot['width'] < $max_event_width )
					{
						// Merge slots for the same long event
						$curr_slot['width']++;
						unset( $curr_event_slots[$i] );
						continue;
					}
					elseif ( $curr_slot['offset'] < $curr_event_slots[$i]['offset'] )
					{
						// Split very long events
						$curr_event_slots[$i]['frame'] = array( 'FrameId'=>$curr_event_slots[$i]['offset'] );
					}
				}
				elseif ( $curr_slot['width'] < $min_event_width )
				{
					// Merge multiple small events
					$curr_slot['width']++;
					unset( $curr_event_slots[$i] );
					continue;
				}
			}
			$curr_slot = &$curr_event_slots[$i];

			//if ( isset($curr_slot) && ($curr_slot['width'] < $min_event_width || ($curr_slot['event']['Id'] == $curr_event_slots[$i]['event']['Id']) ) )
			//{
				//$curr_slot['width']++;
				//unset( $curr_event_slots[$i] );
			//}
			//else
			//{
				//$curr_slot = &$curr_event_slots[$i];
			//}
		}
		else
		{
			unset( $curr_slot );
		}
	}
	if ( isset( $curr_slot ) )
		unset( $curr_slot );
}

// Stack events
//echo "XXX:".date( "r" )."<br>"; flush();
$frame_slots = array();
$frame_monitor_ids = array_keys($mon_frame_slots);
for ( $i = 0; $i < $chart['graph']['width']; $i++ )
{
	foreach ( $frame_monitor_ids as $frame_monitor_id )
	{
		unset( $curr_frame_slots );
		$curr_frame_slots = &$mon_frame_slots[$frame_monitor_id];
		if ( isset($curr_frame_slots[$i]) )
		{
			if ( !isset($frame_slots[$i]) )
			{
				$frame_slots[$i] = array();
				$frame_slots[$i][] = &$curr_frame_slots[$i];
			}
			else
			{
				$slot_count = count($frame_slots[$i]);
				for ( $j = 0; $j < $slot_count; $j++ )
				{
					if ( $curr_frame_slots[$i]['value'] > $frame_slots[$i][$j]['value'] )
					{
						for ( $k = $slot_count; $k > $j; $k-- )
						{
							$frame_slots[$i][$k] = $frame_slots[$i][$k-1];
						}
						$frame_slots[$i][$j] = &$curr_frame_slots[$i];
						break 2;
					}
				}
				$frame_slots[$i][] = &$curr_frame_slots[$i];
			}
		}
	}
}
//echo "YYY:".date( "r" )."<br>"; flush();

//print_r( $mon_event_slots );
//print_r( $mon_frame_slots );
//print_r( $chart );

preg_match( '/^(\d+)-(\d+)-(\d+) (\d+):(\d+)/', $min_time, $start_matches );
preg_match( '/^(\d+)-(\d+)-(\d+) (\d+):(\d+)/', $max_time, $end_matches );

if ( $start_matches[1] != $end_matches[1] )
{
	// Different years
	$title = date( "M Y", $chart['data']['x']['lo'] )." - ".date( "M Y", $chart['data']['x']['hi'] );
}
elseif ( $start_matches[2] != $end_matches[2] )
{
	// Different months
	$title = date( "M", $chart['data']['x']['lo'] )." - ".date( "M Y", $chart['data']['x']['hi'] );
}
elseif ( $start_matches[3] != $end_matches[3] )
{
	// Different dates
	$title = date( "j", $chart['data']['x']['lo'] )." - ".date( "j M Y", $chart['data']['x']['hi'] );
}
else
{
	// Different times
	$title = date( "H:i", $chart['data']['x']['lo'] )." - ".date( "H:i, j M Y", $chart['data']['x']['hi'] );
}

function getDateScale( $scales, $range, $min_lines, $max_lines )
{
	foreach ( $scales as $scale )
	{
		$align = isset($scale['align'])?$scale['align']:1;
		$scale_range = (int)($range/($scale['factor']*$align));
		//echo "S:".$scale['name'].", A:$align, SR:$scale_range<br>";
		if ( $scale_range >= $min_lines )
		{
			$scale['range'] = $scale_range;
			break;
		}
	}
	if ( !isset($scale['range']) )
	{
		$scale['range'] = (int)($range/($scale['factor']*$align));
	}
	$scale['divisor'] = 1;
	while ( ($scale['range']/$scale['divisor']) > $max_lines )
	{
		$scale['divisor']++;
	}
	$scale['lines'] = (int)($scale['range']/$scale['divisor']);
	return( $scale );
}

function getYScale( $range, $min_lines, $max_lines )
{
	$scale['range'] = $range;
	$scale['divisor'] = 1;
	while ( $scale['range']/$scale['divisor'] > $max_lines )
	{
		$scale['divisor']++;
	}
	$scale['lines'] = (int)(($scale['range']-1)/$scale['divisor'])+1;

	return( $scale );
}

function drawXGrid( $chart, $scale, $label_class, $tick_class, $grid_class, $zoom_class=0 )
{
	global $PHP_SELF, $view;

	ob_start();
	$label_count = 0;
	$last_tick = 0;
	unset( $last_label );
	$label_check = isset($scale['label_check'])?$scale['label_check']:$scale['label'];
	for ( $i = 0; $i < $chart['graph']['width']; $i++ )
	{
		$x = $i - 1;
		$time_offset = (int)($chart['data']['x']['lo'] + ($i * $chart['data']['x']['density']));
		if ( $scale['align'] > 1 )
		{
			$label = (int)(date( $label_check, $time_offset )/$scale['align']);
		}
		else
		{
			$label = date( $label_check, $time_offset );
		}
		if ( !isset($last_label) || ($last_label != $label) )
		{
			$label_count++;
		}
		if ( $label_count >= $scale['divisor'] )
		{
			$label_count = 0;
			if ( isset($last_label) )
			{
				if ( $label_class )
				{
?>
      <div class="<?= $label_class ?>" style="left: <?= $x-25 ?>px;"><?= date( $scale['label'], $time_offset ); ?></div>
<?php
				}
				if ( $tick_class )
				{
?>
      <div class="<?= $tick_class ?>" style="left: <?= $x ?>px;"></div>
<?php
				}
				if ( $grid_class )
				{
?>
      <div class="<?= $grid_class ?>" style="left: <?= $x ?>px;"></div>
<?php
				}
				if ( $scale['name'] != 'second' && $zoom_class )
				{
					//$zoom_mid_time = (int)($chart['data']['x']['lo'] + (($last_tick+(($i - $last_tick)/2)) * $chart['data']['x']['density'])); 

					$zoom_min_time = date( "Y-m-d H:i:s", (int)($chart['data']['x']['lo'] + ($last_tick * $chart['data']['x']['density'])) );
					$zoom_max_time = date( "Y-m-d H:i:s", (int)($chart['data']['x']['lo'] + ($i * $chart['data']['x']['density'])) );
?>
      <div class="<?= $zoom_class ?>" style="left: <?= $last_tick-1 ?>px; width: <?= $i-$last_tick ?>px;" onClick="window.location='<?= $PHP_SELF ?>?view=<?= $view ?>&min_time=<?= $zoom_min_time ?>&max_time=<?= $zoom_max_time ?>'"></div>
<?php
				}
				$last_tick = $i;
			}
		}
		$last_label = $label;
	}
	if ( $zoom_class )
	{
		$zoom_min_time = date( "Y-m-d H:i:s", (int)($chart['data']['x']['lo'] + ($last_tick * $chart['data']['x']['density'])) );
		$zoom_max_time = date( "Y-m-d H:i:s", (int)($chart['data']['x']['lo'] + ($i * $chart['data']['x']['density'])) );
?>
      <div class="<?= $zoom_class ?>" style="left: <?= $last_tick-1 ?>px; width: <?= $i-$last_tick ?>px;" onClick="window.location='<?= $PHP_SELF ?>?view=<?= $view ?>&min_time=<?= $zoom_min_time ?>&max_time=<?= $zoom_max_time ?>'"></div>
<?php
	}
	$contents = ob_get_contents();
	ob_end_clean();
	return( $contents );
}

function drawYGrid( $chart, $scale, $label_class, $tick_class, $grid_class )
{
	ob_start();
	for ( $i = 0; $i < $scale['lines']; $i++ )
	{
		$label = (int)($i * $scale['divisor']);
		$y = $chart['graph']['events_height']+(int)(($i * $scale['divisor'])/$chart['data']['y']['density'])-1;
		if ( $label_class )
		{
?>
      <div class="<?= $label_class ?>" style="bottom: <?= $y-3 ?>px;"><?= $label ?></div>
<?php
		}
		if ( $tick_class )
		{
?>
      <div class="<?= $tick_class ?>" style="bottom: <?= $y ?>px;"></div>
<?php
		}
		if ( $grid_class )
		{
?>
      <div class="<?= $grid_class ?>" style="bottom: <?= $y ?>px;<?= $i <= 0?' border-top: solid 1px black;':'' ?>"></div>
<?php
		}
	}

	$contents = ob_get_contents();
	ob_end_clean();
	return( $contents );
}

function getSlotLoadImageBehaviour( $slot )
{
	global $monitors, $jws, $PHP_SELF;

	$event_path = ZM_DIR_EVENTS.'/'.$slot['event']['MonitorId'].'/'.$slot['event']['Id'];
	$image_path = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $event_path, isset($slot['frame'])?$slot['frame']['FrameId']:1 );
	$anal_image = preg_replace( "/capture/", "analyse", $image_path );
	if ( file_exists( $anal_image ) )
	{
		$image_path = $anal_image;
	}

	return( "\"loadEventImage( '".$image_path."', '".$monitors[$slot['event']['MonitorId']]['Name']."<br>".$slot['event']['Name'].(isset($slot['frame'])?("(".$slot['frame']['FrameId'].")"):"")."<br>".strftime( "%m/%d %H:%M:%S", strtotime($slot['event']['StartTime']) )."<br>".$slot['event']['Length']."s', '".$PHP_SELF."?view=event&eid=".$slot['event']['Id']."', ".($monitors[$slot['event']['MonitorId']]['Width']+$jws['event']['w']).", ".($monitors[$slot['event']['MonitorId']]['Height']+$jws['event']['h'])." );\"" );
}

function getSlotViewEventBehaviour( $slot )
{
	global $monitors, $jws, $PHP_SELF;

	return( "\"eventWindow( '".$PHP_SELF."?view=event&eid=".$slot['event']['Id']."', 'zmEvent', ".($monitors[$slot['event']['MonitorId']]['Width']+$jws['event']['w']).", ".($monitors[$slot['event']['MonitorId']]['Height']+$jws['event']['h'])." );\"" );
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangTimeline ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function newWindow(Url,Name,Width,Height)
{
   	var Win = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function eventWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,width="+Width+",height="+Height );
}
function closeWindow()
{
	window.close();
}
//var width = document.screen.availWidth;
//var height = document.screen.availHeight;

window.focus();

function loadEventImage( image_path, image_label, image_link, image_width, image_height )
{
	var image_src = document.getElementById('ImageSrc');
	var image_text = document.getElementById('ImageText');
	image_src.src = image_path;
	image_src.setAttribute( "onclick",  "eventWindow( '"+image_link+"', 'zmEvent', "+image_width+", "+image_height+" )" );
	image_text.innerHTML = image_label;
	image_text.setAttribute( "onclick",  "eventWindow( '"+image_link+"', 'zmEvent', "+image_width+", "+image_height+" )" );
}
</script>
<style type="text/css">
<!--
#ChartBox {
	position: relative;
	text-align: center;
	vertical-align: middle;
	border: 1px solid grey;
	width: <?= $chart['width'] ?>px;
	height: <?= $chart['height'] ?>px;
	padding: 0px;
	margin: auto;
	font-family: Verdana, Arial, Helvetica, sans-serif;
}
#ChartBox #Title {
	position: relative;
	margin: auto;
	color: #016A9D;
	height: 30px;
	font-size: 13px;
	font-weight: bold;
	line-height: 20px
}
#ChartBox #Close {
	position: absolute;
	top: 5px;
	right: 20px;
	margin: auto;
	height: 15;
}
#ChartBox #TopPanel {
	position: relative;
	height: 220px;
	width: 90%;
	padding: 0px;
	margin: auto;
}
#ChartBox #TopPanel a {
	border: 1px solid #cccccc;
	background-color: #eeeeee;
	padding: 5px;
}
#ChartBox #TopPanel a:link, a:visited {
	text-decoration: none
}
#ChartBox #TopPanel a:hover {
	text-decoration: none
}
#ChartBox #TopPanel #LeftNav {
	position: absolute;
	top: 40%;
	left: 50px;
	height: 50px;
	text-align: right;
	width: 20%;
	padding: 0px;
	margin: auto;
}
#ChartBox #TopPanel #RightNav {
	text-align: left;
	position: absolute;
	top: 40%;
	left: 50%;
	width: 180px;
	height: 70px
	padding: 0px;
	margin: auto;
}
#ChartBox #TopPanel #Image {
	position: absolute;
	right: 50%;
	width: 50%;
	height: <?= $chart['image']['height'] ?>px;
	padding: 0px;
	margin: auto;
}
#ChartBox #TopPanel #Image img{
	position: relative;
	vertical-align: middle;
	bottom: 0px;
	height: <?= $chart['image']['height'] ?>px;
	background-color: #f8f8f8;
	margin: auto;
}
#ChartBox #TopPanel #ImageText {
	position: absolute;
	text-align: left;
	top: 0px;
	left: 50%;
	width: 40%;
	height: 60px;
	padding: 0px;
	margin: auto;
	line-height: 16px;
	color: #016A9D;
	font-size: 11px;
	font-weight: bold;
	line-height: 20px
	background-color: #f8f8f8;
}
#ChartBox #TopPanel #Key {
	position: absolute;
	text-align: left;
	vertical-align: bottom;
	margin: auto;
	bottom: 30px;
	left: 50%;
	height: 40px;
	font-size: 9px;
	line-height: 20px;
}
#ChartBox #TopPanel #Key .Entry {
	padding: 2px;
}
#ChartBox #TopPanel #Key .Box {
	border: 1px solid black;
	width: 10px;
	height: 10px;
	padding: 0px;
}

#ChartBox #ChartPanel {
	position: relative;
	padding: 0px;
	margin: auto;
}
#ChartBox #ChartPanel #Chart {
	position: relative;
	border: 1px solid black;
	width: <?= $chart['graph']['width'] ?>px;
	height: <?= $chart['graph']['height'] ?>px;
	padding: 0px;
	margin: auto;
	z-index: 3;
}
<?php
$graph_height = $chart['graph']['height'];

if ( $mode == "overlay" )
{
	$min_event_bar_height = 10;
	$max_event_bar_height = 40;

	if ( count($monitor_ids) )
	{
		$chart['graph']['event_bar_height'] = $min_event_bar_height;
		while ( ($chart['graph']['events_height'] = (($chart['graph']['event_bar_height'] * count($monitor_ids)) + (count($monitor_ids)-1))) < $max_event_bar_height )
		{
			$chart['graph']['event_bar_height']++;
		}
	}
	else
	{
		$chart['graph']['event_bar_height'] = $max_event_bar_height;
		$chart['graph']['events_height'] = $max_event_bar_height;
	}
	$chart['graph']['activity_height'] = ($graph_height - $chart['graph']['events_height']);
	$chart['data']['y']['density'] = $chart['data']['y']['range']/$chart['graph']['activity_height'];

?>
#ChartBox #ChartPanel #Activity {
	position: absolute;
	text-align: center;
	vertical-align: top;
	top: 0px;
	width: <?= $chart['graph']['width'] ?>px;
	height: <?= $chart['graph']['activity_height'] ?>px;
	padding: 0px;
	margin: auto;
}
<?php
	$top = $chart['graph']['activity_height'];
	$event_bar_count = 1;
	foreach ( array_keys($monitor_ids) as $monitor_id )
	{
?>
#ChartBox #ChartPanel #Events<?= $monitor_id ?> {
	position: absolute;
	text-align: center;
	vertical-align: middle;
	top: <?= $top ?>px;
	width: <?= $chart['graph']['width'] ?>px;
	height: <?= $chart['graph']['event_bar_height'] ?>px;
	padding: 0px;
	margin: auto;
	background-color: #fcfcfc;
<?php
		if ( $event_bar_count < count($monitor_ids) )
		{
?>
	border-bottom: 1px solid lightgrey;
<?php
		}
?>
}
<?php
		$event_bar_count++;
		$top += $chart['graph']['event_bar_height']+1;
	}
}
elseif ( $mode == "split" )
{
	$min_activity_bar_height = 30;
	$min_event_bar_height = 10;
	$max_event_bar_height = 40;

	if ( count($monitor_ids) )
	{
		$chart['graph']['event_bar_height'] = $min_event_bar_height;
		$chart['graph']['activity_bar_height'] = $min_activity_bar_height;
		while ( ((($chart['graph']['event_bar_height']+$chart['graph']['activity_bar_height']) * count($monitor_ids)) + ((2*count($monitor_ids))-1)) < $graph_height )
		{
			$chart['graph']['activity_bar_height']++;
			if ( $chart['graph']['event_bar_height'] < $max_event_bar_height )
			{
				$chart['graph']['event_bar_height']++;
			}
		}
	}
	else
	{
		$chart['graph']['event_bar_height'] = $max_event_bar_height;
		$chart['graph']['activity_bar_height'] = $graph_height - $chart['graph']['event_bar_height'];
	}
	$chart['data']['y']['density'] = $chart['data']['y']['range']/$chart['graph']['activity_bar_height'];

?>
<?php
	$top = 0;
	$bar_count = 1;
	foreach ( array_keys($monitor_ids) as $monitor_id )
	{
?>
#ChartBox #ChartPanel #Activity<?= $monitor_id ?> {
	position: absolute;
	text-align: center;
	vertical-align: top;
	top: <?= $top ?>px;
	width: <?= $chart['graph']['width'] ?>px;
	height: <?= $chart['graph']['activity_bar_height'] ?>px;
	padding: 0px;
	margin: auto;
<?php
		if ( $bar_count < count($monitor_ids) )
		{
?>
	border-bottom: 1px solid lightgrey;
<?php
		}
?>
}
#ChartBox #ChartPanel #Events<?= $monitor_id ?> {
	position: absolute;
	text-align: center;
	vertical-align: middle;
	top: <?= $top+$chart['graph']['activity_bar_height']+1 ?>px;
	width: <?= $chart['graph']['width'] ?>px;
	height: <?= $chart['graph']['event_bar_height'] ?>px;
	padding: 0px;
	margin: auto;
	background-color: #fcfcfc;
<?php
		if ( $bar_count < count($monitor_ids) )
		{
?>
	border-bottom: 1px solid black;
<?php
		}
?>
}
<?php
		$bar_count++;
		$top +=  $chart['graph']['activity_bar_height']+1+$chart['graph']['event_bar_height']+1;
	}
}

foreach ( array_keys($monitor_ids) as $monitor_id )
{
?>
div.activity<?= $monitor_id ?> {
	position: absolute;
	bottom: 0px;
	z-index: 3;
	width: 1px;
	background-color: <?= $monitors[$monitor_id]['WebColour'] ?>;
}
div.event<?= $monitor_id ?> {
	position: absolute;
	height: <?= $chart['graph']['event_bar_height'] ?>px; 
	bottom: 0px;
	z-index: 3;
	background-color: <?= $monitors[$monitor_id]['WebColour'] ?>;
}
<?php
}
?>
#ChartBox #Range {
	position: relative;
	text-align: center;
	margin: auto;
	color: #016A9D;
	top: 20px;
	font-size: 11px;
	font-weight: bold;
	line-height: 20px;
}
div.majgridx {
	position: absolute;
	z-index: 1;
	top: 0px;
	width: 1px;
	height: <?= $chart['graph']['height'] ?>px;
	border-left: dotted 1px grey;
}
div.majtickx {
	position: absolute;
	z-index: 0;
	bottom: -7px;
	width: 1px;
	height: 7px;
	border-left: solid 1px black;
}
div.majlabelx {
	position: absolute;
	text-align: center;
	z-index: 0;
	bottom: -20px;
	width: 50px;
	font-size: 9px;
	font-weight: normal;
}

div.majgridy {
	position: absolute;
	z-index: 1;
	left: 0px;
	height: 1px;
	width: <?= $chart['graph']['width'] ?>px;
	border-top: dotted 1px grey;
}
div.majticky {
	position: absolute;
	z-index: 0;
	left: -7px;
	height: 1px;
	width: 7px;
	border-top: solid 1px black;
}
div.majlabely {
	position: absolute;
	text-align: right;
	vertical-align: middle;
	z-index: 0;
	left: -30px;
	width: 20px;
	font-size: 9px;
	font-weight: normal;
}

div.zoom {
	position: absolute;
	z-index: 1;
	bottom: 0px;
	height: <?= $chart['graph']['height'] ?>px;
}

-->
</style>
</head>
<body>
<div id="ChartBox">
  <div id="Title">Event Navigator</div>
  <div id="Close" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></div>
  <div id="TopPanel">
    <div id="ImageNav">
      <div id="Image"><img id="ImageSrc" src="graphics/spacer.gif" height="<?= $chart['image']['height'] ?>"/></div>
      <div id="RightNav">
        <a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mid_time=<?= $min_time ?>&range=<?= $range ?>">&lt;&lt;</a>&nbsp;&nbsp;
	    <a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mid_time=<?= $mid_time ?>&range=<?= (int)($range*$maj_x_scale['zoomout']) ?>">-</a>&nbsp;&nbsp;
		<a href="<?= $PHP_SELF ?>?view=<?= $view ?>&min_time=<?= $mid_time ?>&range=<?= $range ?>">&gt;&gt;</a>
      </div>
      <div id="ImageText">No Event</div>
      <div id="Key">
<?php
foreach( array_keys($mon_event_slots) as $monitor_id )
{
?>
    <span class="Entry"><?= $monitors[$monitor_id]['Name'] ?></span>&nbsp;<span class="Box" style="background-color: <?= $monitors[$monitor_id]['WebColour'] ?>">&nbsp;&nbsp;&nbsp;&nbsp;</span>
<?php
}
?>
      </div>
    </div>
  </div>
  <div id="ChartPanel">
    <div id="Chart">
<?php if ( $mode == "overlay" ) { echo drawYGrid( $chart, $maj_y_scale, "majlabely", "majticky", "majgridy" ); } ?>
<?= drawXGrid( $chart, $maj_x_scale, "majlabelx", "majtickx", "majgridx", "zoom" ) ?>
<?php
if ( $mode == "overlay" )
{
?>
      <div id="Activity">
<?php
	foreach ( $frame_slots as $index=>$slots )
	{
		foreach ( $slots as $slot )
		{
			$slot_height = (int)($slot['value']/$chart['data']['y']['density']);
	
			if ( $slot_height <= 0 )
				continue;
	
			if ( $mouseover )
			{
				$behaviours = array(
					"onClick=".getSlotViewEventBehaviour( $slot ),
					"onMouseOver=".getSlotLoadImageBehaviour( $slot ),
				);
			}
			else
			{
				$behaviours = array(
					"onClick=".getSlotLoadImageBehaviour( $slot ),
				);
			}
?>
        <div class="activity<?= $slot['event']['MonitorId'] ?>" style="left: <?= $index ?>px; height: <?= $slot_height ?>px;" <?= join( " ", $behaviours ) ?>></div>
<?php
		}
	}
?>
      </div>
<?php
}
elseif ( $mode == "split" )
{
	foreach( array_keys($mon_frame_slots) as $monitor_id )
	{
?>
      <div id="Activity<?= $monitor_id ?>">
<?php
		unset( $curr_frame_slots );
		$curr_frame_slots = &$mon_frame_slots[$monitor_id];
		foreach ( $curr_frame_slots as $index=>$slot )
		{
			$slot_height = (int)($slot['value']/$chart['data']['y']['density']);
	
			if ( $slot_height <= 0 )
				continue;
	
			if ( $mouseover )
			{
				$behaviours = array(
					"onClick=".getSlotViewEventBehaviour( $slot ),
					"onMouseOver=".getSlotLoadImageBehaviour( $slot ),
				);
			}
			else
			{
				$behaviours = array(
					"onClick=".getSlotLoadImageBehaviour( $slot ),
				);
			}
	?>
	  <div class="activity<?= $slot['event']['MonitorId'] ?>" style="left: <?= $index ?>px; height: <?= $slot_height ?>px;" <?= join( " ", $behaviours ) ?>></div>
	<?php
		}
?>
      </div>
<?php
	}
}
foreach( array_keys($mon_event_slots) as $monitor_id )
{
?>
      <div id="Events<?= $monitor_id ?>">
<?php
	unset( $curr_event_slots );
	$curr_event_slots = &$mon_event_slots[$monitor_id];
	for ( $i = 0; $i < $chart['graph']['width']; $i++ )
	{
		if ( isset($curr_event_slots[$i]) )
		{
			unset( $slot );
			$slot = &$curr_event_slots[$i];

			if ( $mouseover )
			{
				$behaviours = array(
					"onClick=".getSlotViewEventBehaviour( $slot ),
					"onMouseOver=".getSlotLoadImageBehaviour( $slot ),
				);
			}
			else
			{
				$behaviours = array(
					"onClick=".getSlotLoadImageBehaviour( $slot ),
				);
			}
?>
        <div class="event<?= $monitor_id ?>" style="left: <?= $i ?>px; width: <?= $slot['width'] ?>px;" <?= join( " ", $behaviours ) ?>></div>
<?php
		}
	}
?>
      </div>
<?php
}
?>
    </div>
  </div>
  <div id="Range"><?= $title ?></div>
</div>
</body>
</html>
