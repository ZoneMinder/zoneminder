<?php
//
// ZoneMinder web timeline view file, $Date$, $Revision$
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

//
// Date/time formats used in charts 
//
// These are the time axis range text. The first of each pair is the start date/time
// and the second is the last so often contains additional information
//

// When the chart range is years
define( "STRF_TL_AXIS_RANGE_YEAR1", "%b %Y" );
define( "STRF_TL_AXIS_RANGE_YEAR2", STRF_TL_AXIS_RANGE_YEAR1 );

// When the chart range is months
define( "STRF_TL_AXIS_RANGE_MONTH1", "%b" );
define( "STRF_TL_AXIS_RANGE_MONTH2", STRF_TL_AXIS_RANGE_MONTH1." %Y" );

// When the chart range is days
define( "STRF_TL_AXIS_RANGE_DAY1", "%d" );
define( "STRF_TL_AXIS_RANGE_DAY2", STRF_TL_AXIS_RANGE_DAY1." %b %Y" );

// When the chart range is less than a day
define( "STRF_TL_AXIS_RANGE_TIME1", "%H:%M" );
define( "STRF_TL_AXIS_RANGE_TIME2", STRF_TL_AXIS_RANGE_TIME1.", %d %b %Y" );

//
// These are the time axis tick labels
//
define( "STRF_TL_AXIS_LABEL_YEAR", "%Y" );
define( "STRF_TL_AXIS_LABEL_MONTH", "%M" );
define( "STRF_TL_AXIS_LABEL_WEEK", "%d/%m" );
define( "STRF_TL_AXIS_LABEL_DAY", "%d" );
define( "STRF_TL_AXIS_LABEL_HOUR", "%H:00" );
define( "STRF_TL_AXIS_LABEL_10MINUTE", "%H:%M" );
define( "STRF_TL_AXIS_LABEL_MINUTE", "%H:%M" );
define( "STRF_TL_AXIS_LABEL_10SECOND", "%S" );
define( "STRF_TL_AXIS_LABEL_SECOND", "%S" );

// Turn error reporting on full temporarily.
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
$monitors_sql = "select * from Monitors order by Sequence asc";
//srand( 97981 );
foreach( dbFetchAll( $monitors_sql ) as $row )
{
	//if ( empty($row['WebColour']) )
	//{
		//$row['WebColour'] = sprintf( "#%02x%02x%02x", rand( 0, 255 ), rand( 0, 255), rand( 0, 255 ) );
	//}
	$monitors[$row['Id']] = $row;
}

$range_sql = "select min(E.StartTime) as MinTime, max(E.EndTime) as MaxTime from Events as E inner join Monitors as M on (E.MonitorId = M.Id) where not isnull(E.StartTime) and not isnull(E.EndTime)";
$events_sql = "select E.Id,E.Name,E.StartTime,E.EndTime,E.Length,E.Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId from Events as E inner join Monitors as M on (E.MonitorId = M.Id) where not isnull(StartTime)";

if ( !empty($user['MonitorIds']) )
{
	$mon_filter_sql = " and M.Id in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";

	$range_sql .= $mon_filter_sql;
	$events_sql .= $mon_filter_sql;
}

if ( isset($filter) )
    $tree = parseFilterToTree( $filter );
else
    $tree = false;

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
		$min_time = strftime( STRF_FMT_DATETIME_DB, $min_time_t );
		$max_time = strftime( STRF_FMT_DATETIME_DB, $max_time_t );
	}
	elseif ( isset($min_time) )
	{
		$min_time_t = strtotime($min_time);
		$max_time_t = $min_time_t + $range;
		$mid_time_t = $min_time_t + $half_range;
		$mid_time = strftime( STRF_FMT_DATETIME_DB, $mid_time_t );
		$max_time = strftime( STRF_FMT_DATETIME_DB, $max_time_t );
	}
	elseif ( isset($max_time) )
	{
		$max_time_t = strtotime($max_time);
		$min_time_t = $max_time_t - $range;
		$mid_time_t = $min_time_t + $half_range;
		$min_time = strftime( STRF_FMT_DATETIME_DB, $min_time_t );
		$mid_time = strftime( STRF_FMT_DATETIME_DB, $mid_time_t );
	}
}
elseif ( isset($min_time) && isset($max_time) )
{
	$min_time_t = strtotime($min_time);
	$max_time_t = strtotime($max_time);
	$range = ($max_time_t - $min_time_t) + 1;
	$half_range = (int)($range/2);
	$mid_time_t = $min_time_t + $half_range;
	$mid_time = strftime( STRF_FMT_DATETIME_DB, $mid_time_t );
}

if ( isset($min_time) && isset($max_time) )
{
	$temp_min_time = $temp_max_time = $temp_expandable = false;
	extractDatetimeRange( $tree, $temp_min_time, $temp_max_time, $temp_expandable );
	$filter_sql = parseTreeToSQL( $tree );

	if ( $filter_sql )
	{
		$filter_sql = " and $filter_sql";
		$events_sql .= $filter_sql;
	}
}
else
{
	//$filter_query = parseTreeToQuery( $tree );
	//echo $filter_query;
	//echo '<br>';
	$filter_sql = parseTreeToSQL( $tree );
	$temp_min_time = $temp_max_time = $temp_expandable = false;
	extractDatetimeRange( $tree, $temp_min_time, $temp_max_time, $temp_expandable );
	//echo $filter_sql;
	//echo '<br>';

	if ( $filter_sql )
	{
		$filter_sql = " and $filter_sql";
		$range_sql .= $filter_sql;
		$events_sql .= $filter_sql;
	}

	if ( !isset($min_time) || !isset($max_time) )
	{
		// Dynamically determine range
        $row = dbFetchOne( $range_sql );

		if ( !isset($min_time) )
			$min_time = $row['MinTime'];
		if ( !isset($max_time) )
			$max_time = $row['MaxTime'];
	}

	if ( empty($min_time) )
		$min_time = $temp_min_time;
	if ( empty($max_time) )
		$max_time = $temp_max_time;
	if ( empty($max_time) )
		$max_time = "now";

	$min_time_t = strtotime($min_time);
	$max_time_t = strtotime($max_time);
	$range = ($max_time_t - $min_time_t) + 1;
	$half_range = (int)($range/2);
	$mid_time_t = $min_time_t + $half_range;
	$mid_time = strftime( STRF_FMT_DATETIME_DB, $mid_time_t );
}

//echo "MnT: $temp_min_time, MxT: $temp_max_time, ExP: $temp_expandable<br>";
appendDatetimeRange( $tree, $min_time, $max_time );

$filter_query = parseTreeToQuery( $tree );
if ( $filter_query )
{
	$filter_query = '&'.$filter_query;
}
//echo $filter_query;
//echo '<br>';

$scales = array(
	array( "name"=>"year",     "factor"=>60*60*24*365, "align"=>1,  "zoomout"=>2, "label"=>STRF_TL_AXIS_LABEL_YEAR ),
	array( "name"=>"month",    "factor"=>60*60*24*30,  "align"=>1,  "zoomout"=>12, "label"=>STRF_TL_AXIS_LABEL_MONTH ),
	array( "name"=>"week",     "factor"=>60*60*24*7,   "align"=>1,  "zoomout"=>4.25, "label"=>STRF_TL_AXIS_LABEL_WEEK, "label_check"=>"%W" ),
	array( "name"=>"day",      "factor"=>60*60*24,     "align"=>1,  "zoomout"=>7, "label"=>STRF_TL_AXIS_LABEL_DAY ),
	array( "name"=>"hour",     "factor"=>60*60,        "align"=>1,  "zoomout"=>24, "label"=>STRF_TL_AXIS_LABEL_HOUR, "label_check"=>"%H" ),
	array( "name"=>"minute10", "factor"=>60,           "align"=>10, "zoomout"=>6, "label"=>STRF_TL_AXIS_LABEL_10MINUTE,  "label_check"=>"%M" ),
	array( "name"=>"minute",   "factor"=>60,           "align"=>1,  "zoomout"=>10, "label"=>STRF_TL_AXIS_LABEL_MINUTE,  "label_check"=>"%M" ),
	array( "name"=>"second10", "factor"=>1,            "align"=>10, "zoomout"=>6, "label"=>STRF_TL_AXIS_LABEL_10SECOND ),
	array( "name"=>"second",   "factor"=>1,            "align"=>1,  "zoomout"=>10, "label"=>STRF_TL_AXIS_LABEL_SECOND ),
);

$maj_x_scale = getDateScale( $scales, $range, $chart['grid']['x']['major']['min'], $chart['grid']['x']['major']['max'] );
//print_r( $maj_x_scale );

// Adjust the range etc for scale
$min_time_t -= $min_time_t%($maj_x_scale['factor']*$maj_x_scale['align']);
$min_time = strftime( STRF_FMT_DATETIME_DB, $min_time_t );
$max_time_t += (($maj_x_scale['factor']*$maj_x_scale['align'])-$max_time_t%($maj_x_scale['factor']*$maj_x_scale['align']))-1;
if ( $max_time_t > time() )
	$max_time_t = time();
$max_time = strftime( STRF_FMT_DATETIME_DB, $max_time_t );
$range = ($max_time_t - $min_time_t) + 1;
$half_range = (int)($range/2);
$mid_time_t = $min_time_t + $half_range;
$mid_time = strftime( STRF_FMT_DATETIME_DB, $mid_time_t );

//echo "R:$range<br>";
//echo "MnT:$min_time<br>";
//echo "MnTt:$min_time_t<br>";
//echo "MdT:$mid_time<br>";
//echo "MdTt:$mid_time_t<br>";
//echo "MxT:$max_time<br>";
//echo "MxTt:$max_time_t<br>";

if ( isset($min_time) && isset($max_time) )
{
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
$monitor_ids = array();
foreach( dbFetchAll( $events_sql ) as $event )
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
            foreach( dbFetchAll( $frames_sql ) as $frame )
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
				$curr_frame_slots[$i]['frame'] = dbFetchOne( $frames_sql );
			}
		}
	}
}

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

//print_r( $mon_event_slots );
//print_r( $mon_frame_slots );
//print_r( $chart );

preg_match( '/^(\d+)-(\d+)-(\d+) (\d+):(\d+)/', $min_time, $start_matches );
preg_match( '/^(\d+)-(\d+)-(\d+) (\d+):(\d+)/', $max_time, $end_matches );

if ( $start_matches[1] != $end_matches[1] )
{
	// Different years
	$title = strftime( STRF_TL_AXIS_RANGE_YEAR1, $chart['data']['x']['lo'] )." - ".strftime( STRF_TL_AXIS_RANGE_YEAR2, $chart['data']['x']['hi'] );
}
elseif ( $start_matches[2] != $end_matches[2] )
{
	// Different months
	$title = strftime( STRF_TL_AXIS_RANGE_MONTH1, $chart['data']['x']['lo'] )." - ".strftime( STRF_TL_AXIS_RANGE_MONTH2, $chart['data']['x']['hi'] );
}
elseif ( $start_matches[3] != $end_matches[3] )
{
	// Different dates
	$title = strftime( STRF_TL_AXIS_RANGE_DAY1, $chart['data']['x']['lo'] )." - ".strftime( STRF_TL_AXIS_RANGE_DAY2, $chart['data']['x']['hi'] );
}
else
{
	// Different times
	$title = strftime( STRF_TL_AXIS_RANGE_TIME1, $chart['data']['x']['lo'] )." - ".strftime( STRF_TL_AXIS_RANGE_TIME2, $chart['data']['x']['hi'] );
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
	global $zmSlangZoomIn;

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
			$label = (int)(strftime( $label_check, $time_offset )/$scale['align']);
		}
		else
		{
			$label = strftime( $label_check, $time_offset );
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
      <div class="<?= $label_class ?>" style="left: <?= $x-25 ?>px;"><?= strftime( $scale['label'], $time_offset ); ?></div>
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

					$zoom_min_time = strftime( STRF_FMT_DATETIME_DB, (int)($chart['data']['x']['lo'] + ($last_tick * $chart['data']['x']['density'])) );
					$zoom_max_time = strftime( STRF_FMT_DATETIME_DB, (int)($chart['data']['x']['lo'] + ($i * $chart['data']['x']['density'])) );
?>
      <div class="<?= $zoom_class ?>" style="left: <?= $last_tick-1 ?>px; width: <?= $i-$last_tick ?>px;" title="<?= $zmSlangZoomIn ?>" onClick="zoomTimeline( '<?= urlencode($zoom_min_time) ?>', '<?= urlencode($zoom_max_time) ?>' )"></div>
<?php
				}
				$last_tick = $i;
			}
		}
		$last_label = $label;
	}
	if ( $zoom_class )
	{
		$zoom_min_time = strftime( STRF_FMT_DATETIME_DB, (int)($chart['data']['x']['lo'] + ($last_tick * $chart['data']['x']['density'])) );
		$zoom_max_time = strftime( STRF_FMT_DATETIME_DB, (int)($chart['data']['x']['lo'] + ($i * $chart['data']['x']['density'])) );
?>
      <div class="<?= $zoom_class ?>" style="left: <?= $last_tick-1 ?>px; width: <?= $i-$last_tick ?>px;" title="<?= $zmSlangZoomIn ?>" onClick="zoomTimeline( '<?= urlencode($zoom_min_time) ?>', '<?= urlencode($zoom_max_time) ?>' )"></div>
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
      <div class="<?= $label_class ?>" style="top: <?= $chart['graph']['height']-($y+8) ?>px;"><?= $label ?></div>
<?php
		}
		if ( $tick_class )
		{
?>
      <div class="<?= $tick_class ?>" style="top: <?= $chart['graph']['height']-($y+2) ?>px;"></div>
<?php
		}
		if ( $grid_class )
		{
?>
      <div class="<?= $grid_class ?>" style="top: <?= $chart['graph']['height']-($y+2) ?>px;<?= $i <= 0?' border-top: solid 1px black;':'' ?>"></div>
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
	global $zmSlangArchived;

    $event_path = getEventPath( $slot['event'] );
	$image_path = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $event_path, isset($slot['frame'])?$slot['frame']['FrameId']:1 );
	$anal_image = preg_replace( "/capture/", "analyse", $image_path );
	if ( file_exists( $anal_image ) )
	{
		$image_path = $anal_image;
	}

	$slot_frame = isset($slot['frame'])?$slot['frame']['FrameId']:0;
	$monitor = &$monitors[$slot['event']['MonitorId']];
	$annotation = '';
	if ( $slot['event']['Archived'] )
		$annotation .= "<em>";
	$annotation .= $monitor['Name'].
		"<br>".$slot['event']['Name'].($slot_frame?("(".$slot_frame.")"):"").
		"<br>".strftime( STRF_FMT_DATETIME_SHORT, strtotime($slot['event']['StartTime']) ).
		" - ".$slot['event']['Length']."s".
		"<br>".htmlentities($slot['event']['Cause']).
		(!empty($slot['event']['Notes'])?("<br>".preg_replace( "/\n/", "<br>", htmlentities($slot['event']['Notes']))):"").
		(!empty($slot['event']['Archived'])?("<br>".$zmSlangArchived):"");
	if ( $slot['event']['Archived'] )
		$annotation .= "</em>";
	if ( $slot_frame )
	{
		$slot_frame -= $monitor['PreEventCount'];
		if ( $slot_frame < 1 )
			$slot_frame = 1;
	}
	return( "\"loadEventImage( '".$image_path."', '".$annotation."', ".$slot['event']['Id'].", ".($slot_frame?$slot_frame:1).", ".(reScale( $monitor['Width'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE )+$jws['event']['w']).", ".(reScale( $monitor['Height'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE )+$jws['event']['h'])." );\"" );
}

function getSlotViewEventBehaviour( $slot )
{
	global $monitors, $jws, $PHP_SELF;

	$monitor = &$monitors[$slot['event']['MonitorId']];
	$slot_frame = isset($slot['frame'])?$slot['frame']['FrameId']:0;
	if ( $slot_frame )
	{
		$slot_frame -= $monitor['PreEventCount'];
		if ( $slot_frame < 1 )
			$slot_frame = 1;
	}
	return( "\"eventWindow( ".$slot['event']['Id'].", ".($slot_frame?$slot_frame:1).", ".(reScale( $monitor['Width'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE )+$jws['event']['w']).", ".(reScale( $monitor['Height'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE )+$jws['event']['h'])." );\"" );
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
function eventWindow(Eid,Fid,Width,Height)
{
	var Url = '<?= $PHP_SELF ?>?view=event&eid='+Eid+'&fid='+Fid;
	Url += '<?= $filter_query ?>';
	var Name = 'zmEvent';
	var Win = window.open(Url,Name,"resizable,width="+Width+",height="+Height );
}
function closeWindow()
{
	window.close();
}

window.focus();

function loadEventImage( image_path, image_label, eid, fid, width, height )
{
	var image_src = document.getElementById('ImageSrc');
	var image_text = document.getElementById('ImageText');
	image_src.src = image_path;
	image_src.setAttribute( "onclick",  "eventWindow( "+eid+", "+fid+", "+width+", "+height+" )" );
	image_text.innerHTML = image_label;
	image_text.setAttribute( "onclick",  "eventWindow( "+eid+", "+fid+", "+width+", "+height+" )" );
}

function zoomTimeline( min_time, max_time )
{
	window.location='<?= $PHP_SELF ?>?view=<?= $view ?><?= $filter_query ?>&min_time='+min_time+'&max_time='+max_time;
}
</script>
<style type="text/css">
<!--
#ChartBox {
	position: relative;
	text-align: center;
	border: 1px solid #666666;
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
	line-height: 20px;
	z-index: 0;
}
#ChartBox #List {
	position: absolute;
	top: 5px;
	left: 20px;
	height: 15;
	z-index: 1;
}
#ChartBox #Close {
	position: absolute;
	top: 5px;
	right: 20px;
	height: 15;
	z-index: 1;
}
#ChartBox #TopPanel {
	position: relative;
	height: 220px;
	width: 90%;
	padding: 0px;
	margin: auto;
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
	top: 50%;
	left: 50%;
	width: 180px;
	height: 70px;
	padding: 0px;
	margin: auto;
}
#ChartBox #TopPanel #RightNav a {
	border: 1px solid #cccccc;
	background-color: #eeeeee;
	padding: 5px;
}
#ChartBox #TopPanel #RightNav a:link,a:visited,a:hover {
	text-decoration: none
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
	top: 0px;
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
	height: 90px;
	padding: 0px;
	margin: auto;
	color: #016A9D;
	font-size: 11px;
	font-weight: bold;
	line-height: 14px;
	background-color: #f8f8f8;
}
#ChartBox #TopPanel #Key {
	position: absolute;
	text-align: left;
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
	top: 0px;
	left: 0px;
	width: <?= $chart['graph']['width'] ?>px;
	height: <?= $chart['graph']['activity_height'] ?>px;
	padding: 0px;
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
	top: <?= $top ?>px;
	left: 0px;
	width: <?= $chart['graph']['width'] ?>px;
	height: <?= $chart['graph']['event_bar_height'] ?>px;
	padding: 0px;
	background-color: #fcfcfc;
<?php
		if ( $event_bar_count < count($monitor_ids) )
		{
?>
	border-bottom: 1px solid #cccccc;
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
	top: <?= $top ?>px;
	left: 0px;
	width: <?= $chart['graph']['width'] ?>px;
	height: <?= $chart['graph']['activity_bar_height'] ?>px;
	padding: 0px;
<?php
		if ( $bar_count < count($monitor_ids) )
		{
?>
	border-bottom: 1px solid #cccccc;
<?php
		}
?>
}
#ChartBox #ChartPanel #Events<?= $monitor_id ?> {
	position: absolute;
	text-align: center;
	top: <?= $top+$chart['graph']['activity_bar_height']+1 ?>px;
	left: 0px;
	width: <?= $chart['graph']['width'] ?>px;
	height: <?= $chart['graph']['event_bar_height'] ?>px;
	padding: 0px;
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
#ChartBox #ChartPanel #Activity<?= $mode=='split'?$monitor_id:'' ?> div.activity<?= $monitor_id ?> {
	position: absolute;
	bottom: 0px;
	z-index: 3;
	width: 1px;
	background-color: <?= $monitors[$monitor_id]['WebColour'] ?>;
}
#ChartBox #ChartPanel #Events<?= $monitor_id ?> div.event<?= $monitor_id ?> {
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
	border-left: dotted 1px #cccccc;
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
	border-top: dotted 1px #cccccc;
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
  <div id="List" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=events&page=1&filter=1$filter_query', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $zmSlangList, canView( 'Events' ) ) ?></div>
  <div id="Title">Event Navigator</div>
  <div id="Close" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></div>
  <div id="TopPanel">
    <div id="ImageNav">
      <div id="Image"><img id="ImageSrc" src="graphics/spacer.gif" height="<?= $chart['image']['height'] ?>" title="<?= $zmSlangViewEvent ?>"></div>
      <div id="RightNav">
        <a href="<?= $PHP_SELF ?>?view=<?= $view ?><?= $filter_query ?>&mid_time=<?= urlencode($min_time) ?>&range=<?= $range ?>" title="<?= $zmSlangPanLeft ?>">&lt;</a>&nbsp;&nbsp;
        <a href="<?= $PHP_SELF ?>?view=<?= $view ?><?= $filter_query ?>&mid_time=<?= urlencode($mid_time) ?>&range=<?= (int)($range*$maj_x_scale['zoomout']) ?>" title="<?= $zmSlangZoomOut ?>">-</a>&nbsp;&nbsp;
        <a href="<?= $PHP_SELF ?>?view=<?= $view ?><?= $filter_query ?>&min_time=<?= urlencode($mid_time) ?>&range=<?= $range ?>" title="<?= $zmSlangPanRight ?>">&gt;</a>
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
<?php

function parseFilterToTree( $filter )
{
	if ( count($filter['terms']) > 0 )
	{
		$postfix_expr = array();
		$postfix_stack = array();

		$priorities = array(
			'<' => 1,
			'<=' => 1,
			'>' => 1,
			'>=' => 1,
			'=' => 2,
			'!=' => 2,
			'=~' => 2,
			'!~' => 2,
			'=[]' => 2,
			'![]' => 2,
			'and' => 3,
			'or' => 4,
		);

		for ( $i = 0; $i <= count($filter['terms']); $i++ )
		{
			if ( !empty($filter['terms'][$i]['cnj']) )
			{
				while( true )
				{
					if ( !count($postfix_stack) )
					{
						$postfix_stack[] = array( 'type'=>"cnj", 'value'=>$filter['terms'][$i]['cnj'], 'sql_value'=>$filter['terms'][$i]['cnj']);
						break;
					}
					elseif ( $postfix_stack[count($postfix_stack)-1]['type'] == 'obr' )
					{
						$postfix_stack[] = array( 'type'=>"cnj", 'value'=>$filter['terms'][$i]['cnj'], 'sql_value'=>$filter['terms'][$i]['cnj']);
						break;
					}
					elseif ( $priorities[$filter['terms'][$i]['cnj']] < $priorities[$postfix_stack[count($postfix_stack)-1]['value']] )
					{
						$postfix_stack[] = array( 'type'=>"cnj", 'value'=>$filter['terms'][$i]['cnj'], 'sql_value'=>$filter['terms'][$i]['cnj']);
						break;
					}
					else
					{
						$postfix_expr[] = array_pop( $postfix_stack );
					}
				}
			}
			if ( !empty($filter['terms'][$i]['obr']) )
			{
				for ( $j = 0; $j < $filter['terms'][$i]['obr']; $j++ )
				{
					$postfix_stack[] = array( 'type'=>"obr", 'value'=>$filter['terms'][$i]['obr']);
				}
			}
			if ( !empty($filter['terms'][$i]['attr']) )
			{
				$dt_attr = false;
				switch ( $filter['terms'][$i]['attr'])
				{
					case 'MonitorName':
						$sql_value = 'M.'.preg_replace( '/^Monitor/', '', $filter['terms'][$i]['attr']);
						break;
					case 'Name':
						$sql_value = "E.Name";
						break;
					case 'Cause':
						$sql_value = "E.Cause";
						break;
					case 'DateTime':
						$sql_value = "E.StartTime";
						$dt_attr = true;
						break;
					case 'Date':
						$sql_value = "to_days( E.StartTime )";
						$dt_attr = true;
						break;
					case 'Time':
						$sql_value = "extract( hour_second from E.StartTime )";
						break;
					case 'Weekday':
						$sql_value = "weekday( E.StartTime )";
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
					case 'Archived':
						$sql_value = "E.".$filter['terms'][$i]['attr'];
						break;
					case 'DiskPercent':
						$sql_value = getDiskPercent();
						break;
					case 'DiskBlocks':
						$sql_value = getDiskBlocks();
						break;
					default :
						$sql_value = $filter['terms'][$i]['attr'];
						break;
				}
				if ( $dt_attr )
				{
					$postfix_expr[] = array( 'type'=>"attr", 'value'=>$filter['terms'][$i]['attr'], 'sql_value'=>$sql_value, 'dt_attr'=>true );
				}
				else
				{
					$postfix_expr[] = array( 'type'=>"attr", 'value'=>$filter['terms'][$i]['attr'], 'sql_value'=>$sql_value );
				}
			}
			if ( isset($filter['terms'][$i]['op']) )
			{
				if ( empty($filter['terms'][$i]['op']) )
				{
					$filter['terms'][$i]['op' ]= '=';
				}
				switch ( $filter['terms'][$i]['op' ])
				{
					case '=' :
					case '!=' :
					case '>=' :
					case '>' :
					case '<' :
					case '<=' :
						$sql_value = $filter['terms'][$i]['op'];
						break;
					case '=~' :
						$sql_value = "regexp";
						break;
					case '!~' :
						$sql_value = "not regexp";
						break;
					case '=[]' :
						$sql_value = 'in (';
						break;
					case '![]' :
						$sql_value = 'not in (';
						break;
				}
				while( true )
				{
					if ( !count($postfix_stack) )
					{
						$postfix_stack[] = array( 'type'=>"op", 'value'=>$filter['terms'][$i]['op'], 'sql_value'=>$sql_value );
						break;
					}
					elseif ( $postfix_stack[count($postfix_stack)-1]['type'] == 'obr' )
					{
						$postfix_stack[] = array( 'type'=>"op", 'value'=>$filter['terms'][$i]['op'], 'sql_value'=>$sql_value );
						break;
					}
					elseif ( $priorities[$filter['terms'][$i]['op']] < $priorities[$postfix_stack[count($postfix_stack)-1]['value']] )
					{
						$postfix_stack[] = array( 'type'=>"op", 'value'=>$filter['terms'][$i]['op'], 'sql_value'=>$sql_value );
						break;
					}
					else
					{
						$postfix_expr[] = array_pop( $postfix_stack );
					}
				}
			}
			if ( isset($filter['terms'][$i]['val']) )
			{
				$value_list = array();
				foreach ( preg_split( '/["\'\s]*?,["\'\s]*?/', preg_replace( '/^["\']+?(.+)["\']+?$/', '$1', $filter['terms'][$i]['val' ]) ) as $value )
				{
					switch ( $filter['terms'][$i]['attr'])
					{
						case 'MonitorName':
						case 'Name':
						case 'Cause':
							$value = "'$value'";
							break;
						case 'DateTime':
							$value = "'".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."'";
							break;
						case 'Date':
							$value = "to_days( '".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."' )";
							break;
						case 'Time':
							$value = "extract( hour_second from '".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."' )";
							break;
						case 'Weekday':
							$value = "weekday( '".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."' )";
							break;
					}
					$value_list[] = $value;
				}
				$postfix_expr[] = array( 'type'=>"val", 'value'=>$filter['terms'][$i]['val'], 'sql_value'=>join( ',', $value_list ) );
			}
			if ( !empty($filter['terms'][$i]['cbr']) )
			{
				for ( $j = 0; $j < $filter['terms'][$i]['cbr']; $j++ )
				{
					while ( count($postfix_stack) )
					{
						$element = array_pop( $postfix_stack );
						if ( $element['type'] == "obr" )
						{
							$postfix_expr[count($postfix_expr)-1]['bracket'] = true;
							break;
						}
						$postfix_expr[] = $element;
					}
				}
			}
		}
		while ( count($postfix_stack) )
		{
			$postfix_expr[] = array_pop( $postfix_stack );
		}

		$expr_stack = array();
		//foreach ( $postfix_expr as $element )
		//{
			//echo $element['value']." "; 
		//}
		//echo "<br>";
		foreach ( $postfix_expr as $element )
		{
			if ( $element['type'] == 'attr' || $element['type'] == 'val' )
			{
				$node = array( 'data'=>$element, 'count'=>0 );
				$expr_stack[] = $node;
			}
			elseif ( $element['type'] == 'op' || $element['type'] == 'cnj' )
			{
				$right = array_pop( $expr_stack );
				$left = array_pop( $expr_stack );
				$node = array( 'data'=>$element, 'count'=>2+$left['count']+$right['count'], 'right'=>$right, 'left'=>$left );
				$expr_stack[] = $node;
			}
			else
			{
				die( "Unexpected element type '".$element['type']."', value '".$element['value']."'" );
			}
		}
		if ( count($expr_stack) != 1 )
		{
			die( "Expression stack has ".count($expr_stack)." elements" );
		}
		$expr_tree = array_pop( $expr_stack );
		return( $expr_tree );
	}
	return( false );
}

function _parseTreeToInfix( $node )
{
	$expression = '';
	if ( isset($node) )
	{
		if ( isset($node['left']) )
		{
			if ( !empty($node['data']['bracket']) )
				$expression .= '( ';
			$expression .= _parseTreeToInfix( $node['left'] );
		}
		$expression .= $node['data']['value']." ";
		if ( isset($node['right']) )
		{
			$expression .= _parseTreeToInfix( $node['right'] );
			if ( !empty($node['data']['bracket']) )
				$expression .= ') ';
		}
	}
	return( $expression );
}

function parseTreeToInfix( $tree )
{
	return( _parseTreeToInfix( $tree ) );
}

function _parseTreeToSQL( $node, $cbr=false )
{
	$expression = '';
	if ( $node )
	{
		if ( isset($node['left']) )
		{
			if ( !empty($node['data']['bracket']) )
				$expression .= '( ';
			$expression .= _parseTreeToSQL( $node['left'] );
		}
		$in_expr = $node['data']['type'] == 'op' && ($node['data']['value'] == '=[]' || $node['data']['value'] == '![]');
		$expression .= $node['data']['sql_value'];
		if ( !$in_expr )
			$expression .= ' ';
		if ( $cbr )
			$expression .= ') ';
		if ( isset($node['right']) )
		{
			$expression .= _parseTreeToSQL( $node['right'], $in_expr );
			if ( !empty($node['data']['bracket']) )
				$expression .= ') ';
		}
	}
	return( $expression );
}

function parseTreeToSQL( $tree )
{
	return( _parseTreeToSQL( $tree ) );
}

function _parseTreeToFilter( $node, &$terms, &$level )
{
	$elements = array();
	if ( $node )
	{
		if ( isset($node['left']) )
		{
            if ( !empty($node['data']['bracket']) )
                $terms[$level]['obr'] = 1;
			_parseTreeToFilter( $node['left'], $terms, $level );
		}
		if ( $node['data']['type'] == 'cnj' )
		{
			$level++;
		}
		$terms[$level][$node['data']['type']] = $node['data']['value'];
		if ( isset($node['right']) )
		{
			_parseTreeToFilter( $node['right'], $terms, $level );
            if ( !empty($node['data']['bracket']) )
                $terms[$level]['cbr'] = 1;
		}
	}
}

function parseTreeToFilter( $tree )
{
    $terms = array();
	if ( isset($tree) )
	{
		$level = 0;
		_parseTreeToFilter( $tree, $terms, $level );
	}
	return( array( 'terms' => $terms ) );
}

function parseTreeToQuery( $tree )
{
    $filter = parseTreeToFilter( $tree );
    parseFilter( $filter );
	return( $filter['query'] );
}

function _drawTree( $node, $level )
{
	if ( isset($node['left']) )
	{
		_drawTree( $node['left'], $level+1 );
	}
	echo str_repeat( ".", $level*2 ).$node['data']['value']."<br>";
	if ( isset($node['right']) )
	{
		_drawTree( $node['right'], $level+1 );
	}
}

function drawTree( $tree )
{
	_drawTree( $tree, 0 );
}

function _extractDatetimeRange( &$node, &$min_time, &$max_time, &$expandable, $sub_or )
{
	$pruned = $left_pruned = $right_pruned = false;
	if ( $node )
	{
		if ( isset($node['left']) && isset($node['right']) )
		{
			if ( $node['data']['type'] == 'cnj' && $node['data']['value'] == 'or' )
			{
				$sub_or = true;
			}
			elseif ( !empty($node['left']['data']['dt_attr']) )
			{
				if ( $sub_or )
				{
					$expandable = false;
				}
				elseif ( $node['data']['type'] == 'op' )
				{
					if ( $node['data']['value'] == '>' || $node['data']['value'] == '>=' )
					{
						if ( !$min_time || $min_time > $node['right']['data']['sql_value'] )
						{
							$min_time = $node['right']['data']['value'];
							return( true );
						}
					}
					if ( $node['data']['value'] == '<' || $node['data']['value'] == '<=' )
					{
						if ( !$max_time || $max_time < $node['right']['data']['sql_value'] )
						{
							$max_time = $node['right']['data']['value'];
							return( true );
						}
					}
				}
				else
				{
					die( "Unexpected node type '".$node['data']['type']."'" );
				}
				return( false );
			}

			$left_pruned = _extractDatetimeRange( $node['left'], $min_time, $max_time, $expandable, $sub_or );
			$right_pruned = _extractDatetimeRange( $node['right'], $min_time, $max_time, $expandable, $sub_or );

			if ( $left_pruned && $right_pruned )
			{
				$pruned = true;
			}
			elseif ( $left_pruned )
			{
				$node = $node['right'];
			}
			elseif ( $right_pruned )
			{
				$node = $node['left'];
			}
		}
	}
	return( $pruned );
}

function extractDatetimeRange( &$tree, &$min_time, &$max_time, &$expandable )
{
	$min_time = "";
	$max_time = "";
	$expandable = true;

	_extractDateTimeRange( $tree, $min_time, $max_time, $expandable, false );
}

function appendDatetimeRange( &$tree, $min_time, $max_time=false )
{
	$attr_node = array( 'data'=>array( 'type'=>'attr', 'value'=>'DateTime', 'sql_value'=>'E.StartTime', 'dt_attr'=>true ), 'count'=>0 );
	$val_node = array( 'data'=>array( 'type'=>'val', 'value'=>$min_time, 'sql_value'=>$min_time ), 'count'=>0 );
	$op_node = array( 'data'=>array( 'type'=>'op', 'value'=>'>=', 'sql_value'=>'>=' ), 'count'=>2, 'left'=>$attr_node, 'right'=>$val_node );
	if ( isset($tree) )
	{
		$cnj_node = array( 'data'=>array( 'type'=>'cnj', 'value'=>'and', 'sql_value'=>'and' ), 'count'=>2+$tree['count']+$op_node['count'], 'left'=>$tree, 'right'=>$op_node );
		$tree = $cnj_node;
	}
	else
	{
		$tree = $op_node;
	}

	if ( $max_time )
	{
		$attr_node = array( 'data'=>array( 'type'=>'attr', 'value'=>'DateTime', 'sql_value'=>'E.StartTime', 'dt_attr'=>true ), 'count'=>0 );
		$val_node = array( 'data'=>array( 'type'=>'val', 'value'=>$max_time, 'sql_value'=>$max_time ), 'count'=>0 );
		$op_node = array( 'data'=>array( 'type'=>'op', 'value'=>'<=', 'sql_value'=>'<=' ), 'count'=>2, 'left'=>$attr_node, 'right'=>$val_node );
		$cnj_node = array( 'data'=>array( 'type'=>'cnj', 'value'=>'and', 'sql_value'=>'and' ), 'count'=>2+$tree['count']+$op_node['count'], 'left'=>$tree, 'right'=>$op_node );
		$tree = $cnj_node;
	}
}

?>
