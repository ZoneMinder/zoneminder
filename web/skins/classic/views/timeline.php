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

foreach ( getSkinIncludes( 'includes/timeline_functions.php' ) as $includeFile )
    require_once $includeFile;

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
define( "STRF_TL_AXIS_LABEL_4HOUR", "%H:00" );
define( "STRF_TL_AXIS_LABEL_HOUR", "%H:00" );
define( "STRF_TL_AXIS_LABEL_10MINUTE", "%H:%M" );
define( "STRF_TL_AXIS_LABEL_MINUTE", "%H:%M" );
define( "STRF_TL_AXIS_LABEL_10SECOND", "%S" );
define( "STRF_TL_AXIS_LABEL_SECOND", "%S" );

$mouseover = true;
if ( !isset($mouseover) )
    $mouseover = true;

$mode = "overlay";
if ( !isset($mode) )
    $mode = "overlay";

$minEventWidth = 3;
$maxEventWidth = 6;

$chart = array(
    "width"=>700,
    "height"=>460,
    "image" => array(
        "width"=>264,
        "height"=>220,
        "topOffset"=>20,
    ),
    "imageText" => array(
        "width"=>400,
        "height"=>30,
        "topOffset"=>20,
    ),
    "graph" => array(
        "width"=>600,
        "height"=>160,
        "topOffset"=>30,
    ),
    "title" => array(
        "topOffset"=>50
    ),
    "key" => array(
        "topOffset"=>50
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
$monitorsSql = "select * from Monitors order by Sequence asc";
//srand( 97981 );
foreach( dbFetchAll( $monitorsSql ) as $row )
{
    //if ( empty($row['WebColour']) )
    //{
        //$row['WebColour'] = sprintf( "#%02x%02x%02x", rand( 0, 255 ), rand( 0, 255), rand( 0, 255 ) );
    //}
    $monitors[$row['Id']] = $row;
}

$rangeSql = "select min(E.StartTime) as MinTime, max(E.EndTime) as MaxTime from Events as E inner join Monitors as M on (E.MonitorId = M.Id) where not isnull(E.StartTime) and not isnull(E.EndTime)";
$eventsSql = "select E.Id,E.Name,E.StartTime,E.EndTime,E.Length,E.Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId from Events as E inner join Monitors as M on (E.MonitorId = M.Id) where not isnull(StartTime)";

if ( !empty($user['MonitorIds']) )
{
    $monFilterSql = " and M.Id in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";

    $rangeSql .= $monFilterSql;
    $eventsSql .= $monFilterSql;
}

if ( isset($_REQUEST['filter']) )
    $tree = parseFilterToTree( $_REQUEST['filter'] );
else
    $tree = false;

if ( isset($_REQUEST['range']) )
    $range = validHtmlStr($_REQUEST['range']);
if ( isset($_REQUEST['minTime']) )
    $minTime = validHtmlStr($_REQUEST['minTime']);
if ( isset($_REQUEST['midTime']) )
    $midTime = validHtmlStr($_REQUEST['midTime']);
if ( isset($_REQUEST['maxTime']) )
    $maxTime = validHtmlStr($_REQUEST['maxTime']);

if ( isset($range) )
{
    $halfRange = (int)($range/2);
    if ( isset($midTime) )
    {
        $midTimeT = strtotime($midTime);
        $minTimeT = $midTimeT-$halfRange; 
        $maxTimeT = $midTimeT+$halfRange; 
        if ( !($range%1) )
        {
            $maxTimeT--;
        }
        $minTime = strftime( STRF_FMT_DATETIME_DB, $minTimeT );
        $maxTime = strftime( STRF_FMT_DATETIME_DB, $maxTimeT );
    }
    elseif ( isset($minTime) )
    {
        $minTimeT = strtotime($minTime);
        $maxTimeT = $minTimeT + $range;
        $midTimeT = $minTimeT + $halfRange;
        $midTime = strftime( STRF_FMT_DATETIME_DB, $midTimeT );
        $maxTime = strftime( STRF_FMT_DATETIME_DB, $maxTimeT );
    }
    elseif ( isset($maxTime) )
    {
        $maxTimeT = strtotime($maxTime);
        $minTimeT = $maxTimeT - $range;
        $midTimeT = $minTimeT + $halfRange;
        $minTime = strftime( STRF_FMT_DATETIME_DB, $minTimeT );
        $midTime = strftime( STRF_FMT_DATETIME_DB, $midTimeT );
    }
}
elseif ( isset($minTime) && isset($maxTime) )
{
    $minTimeT = strtotime($minTime);
    $maxTimeT = strtotime($maxTime);
    $range = ($maxTimeT - $minTimeT) + 1;
    $halfRange = (int)($range/2);
    $midTimeT = $minTimeT + $halfRange;
    $midTime = strftime( STRF_FMT_DATETIME_DB, $midTimeT );
}

if ( isset($minTime) && isset($maxTime) )
{
    $tempMinTime = $tempMaxTime = $tempExpandable = false;
    extractDatetimeRange( $tree, $tempMinTime, $tempMaxTime, $tempExpandable );
    $filterSql = parseTreeToSQL( $tree );

    if ( $filterSql )
    {
        $filterSql = " and $filterSql";
        $eventsSql .= $filterSql;
    }
}
else
{
    $filterSql = parseTreeToSQL( $tree );
    $tempMinTime = $tempMaxTime = $tempExpandable = false;
    extractDatetimeRange( $tree, $tempMinTime, $tempMaxTime, $tempExpandable );

    if ( $filterSql )
    {
        $filterSql = " and $filterSql";
        $rangeSql .= $filterSql;
        $eventsSql .= $filterSql;
    }

    if ( !isset($minTime) || !isset($maxTime) )
    {
        // Dynamically determine range
        $row = dbFetchOne( $rangeSql );

        if ( !isset($minTime) )
            $minTime = $row['MinTime'];
        if ( !isset($maxTime) )
            $maxTime = $row['MaxTime'];
    }

    if ( empty($minTime) )
        $minTime = $tempMinTime;
    if ( empty($maxTime) )
        $maxTime = $tempMaxTime;
    if ( empty($maxTime) )
        $maxTime = "now";

    $minTimeT = strtotime($minTime);
    $maxTimeT = strtotime($maxTime);
    $range = ($maxTimeT - $minTimeT) + 1;
    $halfRange = (int)($range/2);
    $midTimeT = $minTimeT + $halfRange;
    $midTime = strftime( STRF_FMT_DATETIME_DB, $midTimeT );
}

//echo "MnT: $tempMinTime, MxT: $tempMaxTime, ExP: $tempExpandable<br>";
if ( $tree )
{
    appendDatetimeRange( $tree, $minTime, $maxTime );

    $filterQuery = parseTreeToQuery( $tree );
}
else
{
    $filterQuery = false;
}

$scales = array(
    array( "name"=>"year",     "factor"=>60*60*24*365, "align"=>1,  "zoomout"=>2,    "label"=>STRF_TL_AXIS_LABEL_YEAR ),
    array( "name"=>"month",    "factor"=>60*60*24*30,  "align"=>1,  "zoomout"=>12,   "label"=>STRF_TL_AXIS_LABEL_MONTH ),
    array( "name"=>"week",     "factor"=>60*60*24*7,   "align"=>1,  "zoomout"=>4.25, "label"=>STRF_TL_AXIS_LABEL_WEEK,     "labelCheck"=>"%W" ),
    array( "name"=>"day",      "factor"=>60*60*24,     "align"=>1,  "zoomout"=>7,    "label"=>STRF_TL_AXIS_LABEL_DAY ),
    array( "name"=>"hour4",    "factor"=>60*60,        "align"=>4,  "zoomout"=>6,    "label"=>STRF_TL_AXIS_LABEL_4HOUR,    "labelCheck"=>"%H" ),
    array( "name"=>"hour",     "factor"=>60*60,        "align"=>1,  "zoomout"=>4,    "label"=>STRF_TL_AXIS_LABEL_HOUR,     "labelCheck"=>"%H" ),
    array( "name"=>"minute10", "factor"=>60,           "align"=>10, "zoomout"=>6,    "label"=>STRF_TL_AXIS_LABEL_10MINUTE, "labelCheck"=>"%M" ),
    array( "name"=>"minute",   "factor"=>60,           "align"=>1,  "zoomout"=>10,   "label"=>STRF_TL_AXIS_LABEL_MINUTE,   "labelCheck"=>"%M" ),
    array( "name"=>"second10", "factor"=>1,            "align"=>10, "zoomout"=>6,    "label"=>STRF_TL_AXIS_LABEL_10SECOND ),
    array( "name"=>"second",   "factor"=>1,            "align"=>1,  "zoomout"=>10,   "label"=>STRF_TL_AXIS_LABEL_SECOND ),
);

$majXScale = getDateScale( $scales, $range, $chart['grid']['x']['major']['min'], $chart['grid']['x']['major']['max'] );

// Adjust the range etc for scale
$minTimeT -= $minTimeT%($majXScale['factor']*$majXScale['align']);
$minTime = strftime( STRF_FMT_DATETIME_DB, $minTimeT );
$maxTimeT += (($majXScale['factor']*$majXScale['align'])-$maxTimeT%($majXScale['factor']*$majXScale['align']))-1;
if ( $maxTimeT > time() )
    $maxTimeT = time();
$maxTime = strftime( STRF_FMT_DATETIME_DB, $maxTimeT );
$range = ($maxTimeT - $minTimeT) + 1;
$halfRange = (int)($range/2);
$midTimeT = $minTimeT + $halfRange;
$midTime = strftime( STRF_FMT_DATETIME_DB, $midTimeT );

//echo "R:$range<br>";
//echo "MnT:$minTime<br>";
//echo "MnTt:$minTimeT<br>";
//echo "MdT:$midTime<br>";
//echo "MdTt:$midTimeT<br>";
//echo "MxT:$maxTime<br>";
//echo "MxTt:$maxTimeT<br>";

if ( isset($minTime) && isset($maxTime) )
{
    $eventsSql .= " and E.EndTime >= '$minTime' and E.StartTime <= '$maxTime'";
}

$eventsSql .= " order by Id asc";
//echo "ESQL: $eventsSql<br>";

$chart['data'] = array(
    "x" => array(
        "lo" => strtotime( $minTime ),
        "hi" => strtotime( $maxTime ),
    ),
    "y" => array(
        "lo" => 0,
        "hi" => 0,
    )
);

$chart['data']['x']['range'] = ($chart['data']['x']['hi'] - $chart['data']['x']['lo']) + 1;
$chart['data']['x']['density'] = $chart['data']['x']['range']/$chart['graph']['width'];

$monEventSlots = array();
$monFrameSlots = array();
$monitorIds = array();
foreach( dbFetchAll( $eventsSql ) as $event )
{
    if ( !isset($monitorIds[$event['MonitorId']]) )
        $monitorIds[$event['MonitorId']] = true;

    if ( !isset($monEventSlots[$event['MonitorId']]) )
        $monEventSlots[$event['MonitorId']] = array();
    if ( !isset($monFrameSlots[$event['MonitorId']]) )
        $monFrameSlots[$event['MonitorId']] = array();

    $currEventSlots = &$monEventSlots[$event['MonitorId']];
    $currFrameSlots = &$monFrameSlots[$event['MonitorId']];

    $startTimeT = strtotime($event['StartTime']);
    $startIndex = $rawStartIndex = (int)(($startTimeT - $chart['data']['x']['lo']) / $chart['data']['x']['density']);
    if ( $startIndex < 0 )
        $startIndex = 0;

    if ( isset($event['EndTime']) )
        $endTimeT = strtotime($event['EndTime']);
    else
        $endTimeT = time();
    $endIndex = $rawEndIndex = (int)(($endTimeT - $chart['data']['x']['lo']) / $chart['data']['x']['density']);

    if ( $endIndex >= $chart['graph']['width'] )
        $endIndex = $chart['graph']['width'] - 1;

    for ( $i = $startIndex; $i <= $endIndex; $i++ )
    {
        if ( !isset($currEventSlots[$i]) )
        {
            if ( $rawStartIndex == $rawEndIndex )
            {
                $offset = 1;
            }
            else
            {
                $offset = 1 + ($event['Frames']?((int)(($event['Frames']-1)*(($i-$rawStartIndex)/($rawEndIndex-$rawStartIndex)))):0);
            }
            $currEventSlots[$i] = array( "count"=>0, "width"=>1, "offset"=>$offset, "event"=>$event );
        }
        else
        {
            $currEventSlots[$i]['count']++;
        }
    }
    if ( $event['MaxScore'] > 0 )
    {
        if ( $startIndex == $endIndex )
        {
            $framesSql = "select FrameId,Score from Frames where EventId = ? and Score > 0 order by Score desc limit 1";
            $frame = dbFetchOne( $framesSql, NULL, array($event['Id']) );

            $i = $startIndex;
            if ( !isset($currFrameSlots[$i]) )
            {
                $currFrameSlots[$i] = array( "count"=>1, "value"=>$event['MaxScore'], "event"=>$event, "frame"=>$frame );
            }
            else
            {
                $currFrameSlots[$i]['count']++;
                if ( $event['MaxScore'] > $currFrameSlots[$i]['value'] )
                {
                    $currFrameSlots[$i]['value'] = $event['MaxScore'];
                    $currFrameSlots[$i]['event'] = $event;
                    $currFrameSlots[$i]['frame'] = $frame;
                }
            }
            if ( $event['MaxScore'] > $chart['data']['y']['hi'] )
            {
                $chart['data']['y']['hi'] = $event['MaxScore'];
            }
        }
        else
        {
            $framesSql = "select FrameId,Delta,unix_timestamp(TimeStamp) as TimeT,Score from Frames where EventId = ? and Score > 0";
            $result = dbQuery( $framesSql, array( $event['Id'] ) );
            while( $frame = dbFetchNext( $result ) )
            {
                if ( $frame['Score'] == 0 )
                    continue;
                $frameTimeT = $frame['TimeT'];
                $frameTimeT = $startTimeT + $frame['Delta'];
                $frameIndex = (int)(($frameTimeT - $chart['data']['x']['lo']) / $chart['data']['x']['density']);
                if ( $frameIndex < 0 )
                    continue;
                if ( $frameIndex >= $chart['graph']['width'] )
                    continue;

                if ( !isset($currFrameSlots[$frameIndex]) )
                {
                    $currFrameSlots[$frameIndex] = array( "count"=>1, "value"=>$frame['Score'], "event"=>$event, "frame"=>$frame );
                }
                else
                {
                    $currFrameSlots[$frameIndex]['count']++;
                    if ( $frame['Score'] > $currFrameSlots[$frameIndex]['value'] )
                    {
                        $currFrameSlots[$frameIndex]['value'] = $frame['Score'];
                        $currFrameSlots[$frameIndex]['event'] = $event;
                        $currFrameSlots[$frameIndex]['frame'] = $frame;
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

ksort( $monitorIds, SORT_NUMERIC );
ksort( $monEventSlots, SORT_NUMERIC );
ksort( $monFrameSlots, SORT_NUMERIC );

// No longer needed?
if ( false )
{
    // Add on missing frames
    foreach( array_keys($monFrameSlots) as $monitorId )
    {
        unset( $currFrameSlots );
        $currFrameSlots = &$monFrameSlots[$monitorId];
        for ( $i = 0; $i < $chart['graph']['width']; $i++ )
        {
            if ( isset($currFrameSlots[$i]) )
            {
                if ( !isset($currFrameSlots[$i]['frame']) )
                {
                    $framesSql = "select FrameId,Score from Frames where EventId = ? and Score > 0 order by FrameId limit 1";
                    $currFrameSlots[$i]['frame'] = dbFetchOne( $framesSql, NULL, array( $currFrameSlots[$i]['event']['Id'] ) );
                }
            }
        }
    }
}

$chart['data']['y']['range'] = ($chart['data']['y']['hi'] - $chart['data']['y']['lo']) + 1;
$chart['data']['y']['density'] = $chart['data']['y']['range']/$chart['graph']['height'];

$majYScale = getYScale( $chart['data']['y']['range'], $chart['grid']['y']['major']['min'], $chart['grid']['y']['major']['max'] );

$maxWidth = 0;
$maxHeight = 0;

foreach ( array_keys($monitorIds) as $monitorId )
{
    if ( $maxWidth < $monitors[$monitorId]['Width'] )
        $maxWidth = $monitors[$monitorId]['Width'];
    if ( $maxHeight < $monitors[$monitorId]['Height'] )
        $maxHeight = $monitors[$monitorId]['Height'];
}

//print_r( $monEventSlots );
// Optimise boxes
foreach( array_keys($monEventSlots) as $monitorId )
{
    unset( $currEventSlots );
    $currEventSlots = &$monEventSlots[$monitorId];
    for ( $i = 0; $i < $chart['graph']['width']; $i++ )
    {
        if ( isset($currEventSlots[$i]) )
        {
            if ( isset($currSlot) )
            {
                if ( $currSlot['event']['Id'] == $currEventSlots[$i]['event']['Id'] )
                {
                    if ( $currSlot['width'] < $maxEventWidth )
                    {
                        // Merge slots for the same long event
                        $currSlot['width']++;
                        unset( $currEventSlots[$i] );
                        continue;
                    }
                    elseif ( $currSlot['offset'] < $currEventSlots[$i]['offset'] )
                    {
                        // Split very long events
                        $currEventSlots[$i]['frame'] = array( 'FrameId'=>$currEventSlots[$i]['offset'] );
                    }
                }
                elseif ( $currSlot['width'] < $minEventWidth )
                {
                    // Merge multiple small events
                    $currSlot['width']++;
                    unset( $currEventSlots[$i] );
                    continue;
                }
            }
            $currSlot = &$currEventSlots[$i];
        }
        else
        {
            unset( $currSlot );
        }
    }
    if ( isset( $currSlot ) )
        unset( $currSlot );
}
//print_r( $monEventSlots );

// Stack events
$frameSlots = array();
$frameMonitorIds = array_keys($monFrameSlots);
for ( $i = 0; $i < $chart['graph']['width']; $i++ )
{
    foreach ( $frameMonitorIds as $frameMonitorId )
    {
        unset( $currFrameSlots );
        $currFrameSlots = &$monFrameSlots[$frameMonitorId];
        if ( isset($currFrameSlots[$i]) )
        {
            if ( !isset($frameSlots[$i]) )
            {
                $frameSlots[$i] = array();
                $frameSlots[$i][] = &$currFrameSlots[$i];
            }
            else
            {
                $slotCount = count($frameSlots[$i]);
                for ( $j = 0; $j < $slotCount; $j++ )
                {
                    if ( $currFrameSlots[$i]['value'] > $frameSlots[$i][$j]['value'] )
                    {
                        for ( $k = $slotCount; $k > $j; $k-- )
                        {
                            $frameSlots[$i][$k] = $frameSlots[$i][$k-1];
                        }
                        $frameSlots[$i][$j] = &$currFrameSlots[$i];
                        break 2;
                    }
                }
                $frameSlots[$i][] = &$currFrameSlots[$i];
            }
        }
    }
}

//print_r( $monEventSlots );
//print_r( $monFrameSlots );
//print_r( $chart );

$graphHeight = $chart['graph']['height'];

if ( $mode == "overlay" )
{
    $minEventBarHeight = 10;
    $maxEventBarHeight = 40;

    if ( count($monitorIds) )
    {
        $chart['graph']['eventBarHeight'] = $minEventBarHeight;
        while ( ($chart['graph']['eventsHeight'] = (($chart['graph']['eventBarHeight'] * count($monitorIds)) + (count($monitorIds)-1))) < $maxEventBarHeight )
        {
            $chart['graph']['eventBarHeight']++;
        }
    }
    else
    {
        $chart['graph']['eventBarHeight'] = $maxEventBarHeight;
        $chart['graph']['eventsHeight'] = $maxEventBarHeight;
    }
    $chart['graph']['activityHeight'] = ($graphHeight - $chart['graph']['eventsHeight']);
    $chart['data']['y']['density'] = $chart['data']['y']['range']/$chart['graph']['activityHeight'];

    $chart['eventBars'] = array();
    $top = $chart['graph']['activityHeight'];
    foreach ( array_keys($monitorIds) as $monitorId )
    {
        $chart['eventBars'][$monitorId] = array( 'top' => $top );
        $top += $chart['graph']['eventBarHeight']+1;
    }
}
elseif ( $mode == "split" )
{
    $minActivityBarHeight = 30;
    $minEventBarHeight = 10;
    $maxEventBarHeight = 40;

    if ( count($monitorIds) )
    {
        $chart['graph']['eventBarHeight'] = $minEventBarHeight;
        $chart['graph']['activityBarHeight'] = $minActivityBarHeight;
        while ( ((($chart['graph']['eventBarHeight']+$chart['graph']['activityBarHeight']) * count($monitorIds)) + ((2*count($monitorIds))-1)) < $graphHeight )
        {
            $chart['graph']['activityBarHeight']++;
            if ( $chart['graph']['eventBarHeight'] < $maxEventBarHeight )
            {
                $chart['graph']['eventBarHeight']++;
            }
        }
    }
    else
    {
        $chart['graph']['eventBarHeight'] = $maxEventBarHeight;
        $chart['graph']['activityBarHeight'] = $graphHeight - $chart['graph']['eventBarHeight'];
    }
    $chart['data']['y']['density'] = $chart['data']['y']['range']/$chart['graph']['activityBarHeight'];

?>
<?php
    $chart['activityBars'] = array();
    $chart['eventBars'] = array();
    $top = 0;
    $barCount = 1;
    foreach ( array_keys($monitorIds) as $monitorId )
    {
        $chart['eventBars'][$monitorId] = array( 'top' => $top );
        $chart['eventBars'][$monitorId] = array( 'top' => $top+$chart['graph']['activityBarHeight']+1 );
        $top +=  $chart['graph']['activityBarHeight']+1+$chart['graph']['eventBarHeight']+1;
    }
}

preg_match( '/^(\d+)-(\d+)-(\d+) (\d+):(\d+)/', $minTime, $startMatches );
preg_match( '/^(\d+)-(\d+)-(\d+) (\d+):(\d+)/', $maxTime, $endMatches );

if ( $startMatches[1] != $endMatches[1] )
{
    // Different years
    $title = strftime( STRF_TL_AXIS_RANGE_YEAR1, $chart['data']['x']['lo'] )." - ".strftime( STRF_TL_AXIS_RANGE_YEAR2, $chart['data']['x']['hi'] );
}
elseif ( $startMatches[2] != $endMatches[2] )
{
    // Different months
    $title = strftime( STRF_TL_AXIS_RANGE_MONTH1, $chart['data']['x']['lo'] )." - ".strftime( STRF_TL_AXIS_RANGE_MONTH2, $chart['data']['x']['hi'] );
}
elseif ( $startMatches[3] != $endMatches[3] )
{
    // Different dates
    $title = strftime( STRF_TL_AXIS_RANGE_DAY1, $chart['data']['x']['lo'] )." - ".strftime( STRF_TL_AXIS_RANGE_DAY2, $chart['data']['x']['hi'] );
}
else
{
    // Different times
    $title = strftime( STRF_TL_AXIS_RANGE_TIME1, $chart['data']['x']['lo'] )." - ".strftime( STRF_TL_AXIS_RANGE_TIME2, $chart['data']['x']['hi'] );
}

function drawXGrid( $chart, $scale, $labelClass, $tickClass, $gridClass, $zoomClass=false )
{
    global $SLANG;

    ob_start();
    $labelCount = 0;
    $lastTick = 0;
    unset( $lastLabel );
    $labelCheck = isset($scale['labelCheck'])?$scale['labelCheck']:$scale['label'];
?>
          <div id="xScale">
<?php
    for ( $i = 0; $i < $chart['graph']['width']; $i++ )
    {
        $x = $i - 1;
        $timeOffset = (int)($chart['data']['x']['lo'] + ($i * $chart['data']['x']['density']));
        if ( $scale['align'] > 1 )
        {
            $label = (int)(strftime( $labelCheck, $timeOffset )/$scale['align']);
        }
        else
        {
            $label = strftime( $labelCheck, $timeOffset );
        }
        if ( !isset($lastLabel) || ($lastLabel != $label) )
        {
            $labelCount++;
        }
        if ( $labelCount >= $scale['divisor'] )
        {
            $labelCount = 0;
            if ( isset($lastLabel) )
            {
                if ( $labelClass )
                {
?>
            <div class="<?= $labelClass ?>" style="left: <?= $x-25 ?>px;"><?= strftime( $scale['label'], $timeOffset ); ?></div>
<?php
                }
                if ( $tickClass )
                {
?>
            <div class="<?= $tickClass ?>" style="left: <?= $x ?>px;"></div>
<?php
                }
                if ( $gridClass )
                {
?>
            <div class="<?= $gridClass ?>" style="left: <?= $x ?>px;"></div>
<?php
                }
                if ( $scale['name'] != 'second' && $zoomClass )
                {
                    $zoomMinTime = strftime( STRF_FMT_DATETIME_DB, (int)($chart['data']['x']['lo'] + ($lastTick * $chart['data']['x']['density'])) );
                    $zoomMaxTime = strftime( STRF_FMT_DATETIME_DB, (int)($chart['data']['x']['lo'] + ($i * $chart['data']['x']['density'])) );
?>
            <div class="<?= $zoomClass ?>" style="left: <?= $lastTick-1 ?>px; width: <?= $i-$lastTick ?>px;" title="<?= $SLANG['ZoomIn'] ?>" onclick="tlZoomBounds( '<?= $zoomMinTime ?>', '<?= $zoomMaxTime ?>' )"></div>
<?php
                }
                $lastTick = $i;
            }
        }
        $lastLabel = $label;
    }
    if ( $zoomClass )
    {
        $zoomMinTime = strftime( STRF_FMT_DATETIME_DB, (int)($chart['data']['x']['lo'] + ($lastTick * $chart['data']['x']['density'])) );
        $zoomMaxTime = strftime( STRF_FMT_DATETIME_DB, (int)($chart['data']['x']['lo'] + ($i * $chart['data']['x']['density'])) );
?>
            <div class="<?= $zoomClass ?>" style="left: <?= $lastTick-1 ?>px; width: <?= $i-$lastTick ?>px;" title="<?= $SLANG['ZoomIn'] ?>" onclick="tlZoomBounds( '<?= $zoomMinTime ?>', '<?= $zoomMaxTime ?>' )"></div>
<?php
    }
?>
          </div>
<?php
    return( ob_get_clean() );
}

function drawYGrid( $chart, $scale, $labelClass, $tickClass, $gridClass )
{
    ob_start();
?>
          <div id="yScale">
<?php
    for ( $i = 0; $i < $scale['lines']; $i++ )
    {
        $label = (int)($i * $scale['divisor']);
        $y = $chart['graph']['eventsHeight']+(int)(($i * $scale['divisor'])/$chart['data']['y']['density'])-1;
        if ( $labelClass )
        {
?>
            <div class="<?= $labelClass ?>" style="top: <?= $chart['graph']['height']-($y+8) ?>px;"><?= $label ?></div>
<?php
        }
        if ( $tickClass )
        {
?>
            <div class="<?= $tickClass ?>" style="top: <?= $chart['graph']['height']-($y+2) ?>px;"></div>
<?php
        }
        if ( $gridClass )
        {
?>
            <div class="<?= $gridClass ?>" style="top: <?= $chart['graph']['height']-($y+2) ?>px;<?= $i <= 0?' border-top: solid 1px black;':'' ?>"></div>
<?php
        }
    }
?>
          </div>
<?php

    return( ob_get_clean() );
}

function getSlotPreviewEventBehaviour( $slot )
{
    return( "previewEvent( '".$slot['event']['Id']."', '".getSlotFrame( $slot )."' )" );
}

function getSlotShowEventBehaviour( $slot )
{
    global $monitors;

    $monitor = &$monitors[$slot['event']['MonitorId']];

    return( "showEvent( '".$slot['event']['Id']."', '".getSlotFrame( $slot )."', '".reScale( $monitor['Width'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE )."', '".reScale( $monitor['Height'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE )."' )" );
}

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Timeline'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <?= makePopupLink( '?view=events&amp;page=1'.htmlspecialchars($filterQuery), 'zmEvents', 'events', $SLANG['List'], canView( 'Events' ) ) ?>
        <a href="#" onclick="closeWindow();"><?= $SLANG['Close'] ?></a>
      </div>
      <h2><?= $SLANG['Timeline'] ?></h2>
    </div>
    <div id="content" class="chartSize">
      <div id="topPanel" class="graphWidth">
        <div id="imagePanel">
          <div id="image" class="imageHeight"><img id="imageSrc" class="imageWidth" src="graphics/transparent.gif" alt="<?= $SLANG['ViewEvent'] ?>" title="<?= $SLANG['ViewEvent'] ?>"/></div>
        </div>
        <div id="dataPanel">
          <div id="textPanel">
            <div id="instruction">
              <p><?= $SLANG['TimelineTip1'] ?></p>
              <p><?= $SLANG['TimelineTip2'] ?></p>
              <p><?= $SLANG['TimelineTip3'] ?></p>
              <p><?= $SLANG['TimelineTip4'] ?></p>
              </div>
            <div id="eventData">
            </div>
          </div>
          <div id="navPanel">
            <input type="button" title="<?= $SLANG['PanLeft'] ?>" value="&lt;&lt;" onclick="tlPan( '<?= $minTime ?>', '<?= $range ?>' )"/>
            <input type="button" title="<?= $SLANG['ZoomOut'] ?>" value="&ndash;" onclick="tlZoomRange( '<?= $midTime ?>', '<?= (int)($range*$majXScale['zoomout']) ?>' )"/>
            <input type="button" title="<?= $SLANG['PanRight'] ?>" value="&gt;&gt;" onclick="tlPan( '<?= $maxTime ?>', '<?= $range ?>' )"/>
          </div>
        </div>
      </div>
      <div id="chartPanel">
        <div id="chart" class="graphSize">
<?php
if ( $mode == "overlay" )
{
    echo drawYGrid( $chart, $majYScale, "majLabelY", "majTickY", "majGridY graphWidth" );
}
echo drawXGrid( $chart, $majXScale, "majLabelX", "majTickX", "majGridX graphHeight", "zoom graphHeight" );

if ( $mode == "overlay" )
{
?>
          <div id="activity" class="activitySize">
<?php
    foreach ( $frameSlots as $index=>$slots )
    {
        foreach ( $slots as $slot )
        {
            $slotHeight = (int)($slot['value']/$chart['data']['y']['density']);
    
            if ( $slotHeight <= 0 )
                continue;
    
            if ( $mouseover )
            {
                $behaviours = array(
                    'onclick="'.getSlotShowEventBehaviour( $slot ).'"',
                    'onmouseover="'.getSlotPreviewEventBehaviour( $slot ).'"'
                );
            }
            else
            {
                $behaviours = array(
                    'onclick="'.getSlotPreviewEventBehaviour( $slot ).'"'
                );
            }
?>
            <div class="activity monitorColour<?= $slot['event']['MonitorId'] ?>" style="left: <?= $index ?>px; height: <?= $slotHeight ?>px;" <?= join( " ", $behaviours ) ?>></div>
<?php
        }
    }
?>
          </div>
<?php
}
elseif ( $mode == "split" )
{
    foreach( array_keys($monFrameSlots) as $monitorId )
    {
?>
          <div id="activity<?= $monitorId ?>">
<?php
        unset( $currFrameSlots );
        $currFrameSlots = &$monFrameSlots[$monitorId];
        foreach ( $currFrameSlots as $index=>$slot )
        {
            $slotHeight = (int)($slot['value']/$chart['data']['y']['density']);
    
            if ( $slotHeight <= 0 )
                continue;
    
            if ( $mouseover )
            {
                $behaviours = array(
                    'onclick="'.getSlotShowEventBehaviour( $slot ).'"',
                    'onmouseover="'.getSlotPreviewEventBehaviour( $slot ).'"'
                );
            }
            else
            {
                $behaviours = array(
                    'onclick="'.getSlotPreviewEventBehaviour( $slot ).'"'
                );
            }
    ?>
            <div class="activity activity<?= $slot['event']['MonitorId'] ?>" style="left: <?= $index ?>px; height: <?= $slotHeight ?>px;" <?= join( " ", $behaviours ) ?>></div>
    <?php
        }
?>
          </div>
<?php
    }
}
foreach( array_keys($monEventSlots) as $monitorId )
{
?>
          <div id="events<?= $monitorId ?>" class="events eventsSize eventsPos<?= $monitorId ?>">
<?php
    unset( $currEventSlots );
    $currEventSlots = &$monEventSlots[$monitorId];
    for ( $i = 0; $i < $chart['graph']['width']; $i++ )
    {
        if ( isset($currEventSlots[$i]) )
        {
            unset( $slot );
            $slot = &$currEventSlots[$i];

            if ( $mouseover )
            {
                $behaviours = array(
                    'onclick="'.getSlotShowEventBehaviour( $slot ).'"',
                    'onmouseover="'.getSlotPreviewEventBehaviour( $slot ).'"'
                );
            }
            else
            {
                $behaviours = array(
                    'onclick="'.getSlotPreviewEventBehaviour( $slot ).'"'
                );
            }
?>
            <div class="event eventsHeight monitorColour<?= $monitorId ?>" style="left: <?= $i ?>px; width: <?= $slot['width'] ?>px;" <?= join( " ", $behaviours ) ?>></div>
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
      <div id="chartLabels" class="graphWidth">
        <div id="key">
<?php
foreach( array_keys($monEventSlots) as $monitorId )
{
?>
          <span class="keyEntry"><?= $monitors[$monitorId]['Name'] ?><img id="keyBox<?= $monitorId ?>" class="keyBox monitorColour<?= $monitorId ?>" src="graphics/transparent.gif" alt="<?= $monitors[$monitorId]['Name'] ?>"/></span>
<?php
}
?>
        </div>
        <div id="range"><?= $title ?></div>
      </div>
    </div>
  </div>
</body>
</html>
