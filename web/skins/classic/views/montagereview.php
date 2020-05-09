<?php
//
// ZoneMinder web montagereview view file, $Date$, $Revision$
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


// ---------------------

// Montage Review -- show all (visible) monitors, and allow to review time ranges easily for all monitors.
//
// This is similar to the timeline view, but is NOT linked to events directly, and shows all monitors' images.
// It also will do a pseudo play function from history, as well as a live display of images.
//

// Valid query string:
//
//        &maxTime, minTime = string formats (locale) of starting and ending time for history (pass both or none), default = last hour
//
//        &current = string format of time, where the slider is positioned first in history mode (normally only used in reloads, default = half scale)
//
//        &speed = one of the valid speeds below (see $speeds in php section, default = 1.0)
//
//        &scale = image sie scale (.1 to 1.0, or 1.1 = fit, default = fit)
//
//        &live=1 whether to start in live mode, 1 = yes, 0 = no
//
//        &z1, &z2, etc. = initial monitor specific zoom, numeral is a monitor id, default = 1.0, used in page reloads normally
//
//        &group = group number of monitors to display, comes from console if set, default is none (i.e. not limited by group)
//
//        &fit = if present then use fit-mode and ignore scale
//

// It takes very high bandwidth to the server, and a pretty fast client to keep up with the image rate.  To reduce the rate
// change the playback slider to 0 and then it does not try to play at the same time it is scrubbing.
//
// Jul 23 2015 update:
//          - Correct problem propagating selected playback speed
//          - Change from blank monitor to specific message about no data in times with no recording
//          - Enlarge and better center fonts on labels for lines.
//          - Add support for monitor groups in selecting criteria from console
//          - Fix some no-update conditions when playback was off but scale changed or refreshed.
//          - Added translate call around buttons so as to facilitate possible translations later
//          - Removed range from/to labels on very small graphs to keep from overlapping slider
//          - Changed initial (from other page) position of slider to be in the middle to be more obvious
//
// Jul 29 2015 update
//          - Add live mode shots from cameras via single frame pull mode
//          - Added dynamic refresh rate based on how fast we can upload images
//          - Closed some gaps in playback frames due to time rounding in retrieval.
//          - Consolidated frame in-memory records to contiguous time rather than individual frame-seconds (still requires a good deal of browser memory)
//          - Took out a lot of the integral second rounding so that it works better at subsequent replay speeds
//
// Jul 30 2015 update
//          - Smoother adjustment of frame rate, fixed upper/lower limits (caching can cause runaway) (and a display, probably temporary, at the bottom)
//          - Change to using index.php?view= instead of direct access so image access is authenticated
//          - Add fractional speed for replay, and non-linear speed slider, and update current setting as slider moves (not when done)
//          - Experimenting with a black background for monitors (this should be replaced with proper CSS later)
//
// Aug 02, 2015 update
//          - Add max fit, make it default
//          - Remove timeline in live mode, and restore when switched back (live button becomes toggle)
//          - Add +/- zooms to individual monitors so you can adjust size, persist across reload buttons (only)
//          - Change default to 1 hour and live mode (reduce workload on initial load, let people ask for huge history amounts)
//          - Since this may be run as a standalone window for shortcuts, etc., add a "console" link to get back to the console
//
// August 6, 2015 update
//          - Fix regression on linkage to events when starting and staying in live mode
//          - Remove zoom/pan buttons in live mode as they are meaningless
//          - Change "fit" to a button, and remove scale when fit is in use (this means fit/live has no sliders)
//
// August 8, 2015 update:
//          - Optimize events query to significantly decrease load times
//          - Consolidate frames to 10 seconds not 1 for faster load and less memory usage
//          - Replace graphic image for no-data with text-on-canvas (faster)
//          - Correct sorting issue related to normalized scale so biggest goes to top left more reliably
//          - Corrections to Safari which won't support inline-flex (thanks Apple, really?!)
//
// August 9, 2015 updates:
//          - Add auth tokens to zms call for those using authorization
//

if ( !canView( 'Events' ) )
{
    $view = "error";
    return;
}

if ( !empty($_REQUEST['group']) )
{
    $group = $_REQUEST['group'];
    $row = dbFetchOne( 'select * from Groups where Id = ?', NULL, array($_REQUEST['group']) );
    $monitorsSql = "select * from Monitors where Function != 'None' and find_in_set( Id, '".$row['MonitorIds']."' ) ";
}
else
{
    $monitorsSql = "select *  from Monitors ";
    $group = "";
}

// Note that this finds incomplete events as well, and any frame records written, but still cannot "see" to the end frame
// if the bulk record has not been written - to be able to include more current frames reduce bulk frame sizes (event size can be large)
// Note we round up just a bit on the end time as otherwise you get gaps, like 59.78 to 00 in the next second, which can give blank frames when moved through slowly.

$eventsSql = "
  select E.Id,E.Name,UNIX_TIMESTAMP(E.StartTime) as StartTimeSecs,
         case when E.EndTime is null then (Select UNIX_TIMESTAMP(DATE_ADD(E.StartTime, Interval max(Delta)+0.5 Second)) from Frames F where F.EventId=E.Id)
              else UNIX_TIMESTAMP(E.EndTime)
         end as CalcEndTimeSecs, E.Length,
         case when E.Frames is null then (Select count(*) from Frames F where F.EventId=E.Id) else E.Frames end as Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId
  from Events as E
  inner join Monitors as M on (E.MonitorId = M.Id)
  where not isnull(E.Frames) and not isnull(StartTime) ";



//    select E.Id,E.Name,UNIX_TIMESTAMP(E.StartTime) as StartTimeSecs,UNIX_TIMESTAMP(max(DATE_ADD(E.StartTime, Interval Delta+0.5 Second))) as CalcEndTimeSecs, E.Length,max(F.FrameId) as Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId
//    from Events as E
//    inner join Monitors as M on (E.MonitorId = M.Id)
//    inner join Frames F on F.EventId=E.Id
//    where not isnull(E.Frames) and not isnull(StartTime) ";

// Note that the delta value seems more accurate than the time stamp for some reason.
$frameSql = "
    select E.Id as eId, E.MonitorId, UNIX_TIMESTAMP(DATE_ADD(E.StartTime, Interval Delta Second)) as TimeStampSecs, max(F.Score) as Score
    from Events as E
    inner join Frames as F on (F.EventId = E.Id)
    where not isnull(StartTime) and F.Score>0 ";

// This program only calls itself with the time range involved -- it does all monitors (the user can see, in the called group) all the time

if ( !empty($user['MonitorIds']) )
{
    $monFilterSql = ' AND M.Id IN ('.$user['MonitorIds'].')';

    $eventsSql   .= $monFilterSql;
    $monitorsSQL .= $monFilterSql;
    $frameSql    .= $monFilterSql;
}

// Parse input parameters -- note for future, validate/clean up better in case we don't get called from self.
// Live overrides all the min/max stuff but it is still processed

// The default (nothing at all specified) is for 1 hour so we do not read the whole database


if ( !isset($_REQUEST['minTime']) && !isset($_REQUEST['maxTime']) )
{
    $maxTime=strftime("%c",time());
    $minTime=strftime("%c",time() - 3600);
}
if ( isset($_REQUEST['minTime']) )
    $minTime = validHtmlStr($_REQUEST['minTime']);

if ( isset($_REQUEST['maxTime']) )
    $maxTime = validHtmlStr($_REQUEST['maxTime']);

// AS a special case a "all" is passed in as an exterme interval - if so , clear them here and let the database query find them

if ( (strtotime($maxTime) - strtotime($minTime))/(365*24*3600) > 30 ) // test years
{
    $minTime=null;
    $maxTime=null;
}

$fitMode=1;
if (isset($_REQUEST['fit']) && $_REQUEST['fit']=='0' )
    $fitMode=0;

if ( isset($_REQUEST['scale']) )
    $defaultScale=validHtmlStr($_REQUEST['scale']);
else
    $defaultScale=1;

$speeds=[0, 0.1, 0.25, 0.5, 0.75, 1.0, 1.5, 2, 3, 5, 10, 20, 50];

if (isset($_REQUEST['speed']) )
    $defaultSpeed=validHtmlStr($_REQUEST['speed']);
else
    $defaultSpeed=1;

$speedIndex=5; // default to 1x
for ($i=0; $i<count($speeds); $i++)
    if($speeds[$i]==$defaultSpeed)
    {
        $speedIndex=$i;
        break;
    }

if (isset($_REQUEST['current']) )
    $defaultCurrentTime=validHtmlStr($_REQUEST['current']);


$initialModeIsLive=1;
if(isset($_REQUEST['live']) && $_REQUEST['live']=='0' )
    $initialModeIsLive=0;

$initialDisplayInterval=1000;
if(isset($_REQUEST['displayinterval']))
    $initialDisplayInterval=validHtmlStr($_REQUEST['displayinterval']);

$eventsSql .= "group by E.Id,E.Name,E.StartTime,E.Length,E.Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId ";

if( isset($minTime) && isset($maxTime) )
{
    $minTimeSecs = strtotime($minTime);
    $maxTimeSecs = strtotime($maxTime);
    $eventsSql .= "having CalcEndTimeSecs > '" . $minTimeSecs . "' and StartTimeSecs < '" . $maxTimeSecs . "'";
    $frameSql .= "and TimeStamp > '" . $minTime . "' and TimeStamp < '" . $maxTime . "'";
}
$frameSql .= "group by E.Id, E.MonitorId, F.TimeStamp order by E.MonitorId, F.TimeStamp asc";

// This loads all monitors the user can see - even if we don't have data for one we still show all for switch to live.

$monitors = array();
$monitorsSql .= " order by Sequence asc ";
$index=0;
foreach( dbFetchAll( $monitorsSql ) as $row )
{
    $monitors[$index] = $row;
    $index = $index + 1;
}

// These are zoom ranges per visible monitor



xhtmlHeaders(__FILE__, translate('montagereview') );
?>
<style>
input[type=range]::-ms-tooltip {
    display: none;
}
</style>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="./"><?php echo translate('Console') ?></a>
        <a href="#" onclick="closeWindow();"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('Montage Review') ?></h2>
    </div>
    <div id='ScaleDiv' style='display: inline-flex; border: 1px solid black;'>
        <label style='margin:5px;' for=scaleslider><?php echo translate('Scale')?></label>
        <input id=scaleslider type=range min=0.1 max=1.0 value=<?php echo $defaultScale ?> step=0.10 width=20% onchange='setScale(this.value)' oninput='showScale(this.value)'/>
        <span style='margin:5px;' id=scaleslideroutput><?php echo number_format((float)$defaultScale,2,'.','')?> x</span>
    </div>
    <div id='SpeedDiv' style='display: inline-flex; border: 1px solid black;'>
        <label style='margin:5px;' for=speedslider><?php echo translate('Speed') ?></label>
        <input id=speedslider type=range min=0 max=<?php echo count($speeds)-1?> value=<?php echo $speedIndex ?> step=1 wdth=20% onchange='setSpeed(this.value)' oninput='showSpeed(this.value)'/>
        <span style='margin:5px;' id=speedslideroutput><?php echo $speeds[$speedIndex] ?> fps</span>
    </div>
    <div style='display: inline-flex; border: 1px solid black; flex-flow: row wrap;'>
        <button type='button' id=panleft   onclick='panleft()     '>&lt; <?php echo translate('Pan') ?></button>
        <button type='button' id=zoomin    onclick='zoomin()           '><?php echo translate('In +') ?></button>
        <button type='button' id=zoomout   onclick='zoomout()          '><?php echo translate('Out -') ?></button>
        <button type='button' id=lasteight onclick='lastEight()        '><?php echo translate('8 Hour') ?></button>
        <button type='button' id=lasthour  onclick='lastHour()         '><?php echo translate('1 Hour') ?></button>
        <button type='button' id=allof     onclick='allof()            '><?php echo translate('All Events') ?></button>
        <button type='button' id=live      onclick='setLive(1-liveMode)'><?php echo translate('Live') ?></button>
        <button type='button' id=fit       onclick='setFit(1-fitMode)  '><?php echo translate('Fit') ?></button>
        <button type='button' id=panright  onclick='panright()         '><?php echo translate('Pan') ?> &gt;</button>
    </div>
    <div id=timelinediv style='position:relative; width:93%;'>
        <canvas id=timeline style='border:1px solid;' onmousemove='mmove(event)' ontouchmove='tmove(event)' onmousedown='mdown(event)' onmouseup='mup(event)' onmouseout='mout(event)' ></canvas>
        <span id=scrubleft ></span>
        <span id=scrubright ></span>
        <span id=scruboutput ></span>
    </div>
<?php
// Monitor images - these had to be loaded after the monitors used were determined (after loading events)

echo "<div id='monitors' style='position:relative; background-color:black;' width='100%' height='100%'>\n";
foreach ($monitors as $m)
{
    {
          echo "<canvas width='" . $m['Width'] * $defaultScale . "px' height='"  . $m['Height'] * $defaultScale . "px' id='Monitor" . $m['Id'] . "' style='border:3px solid " . $m['WebColour'] . "' onclick='clickMonitor(event," . $m['Id'] . ")'>No Canvas Support!!</canvas>\n";
    }
}
echo "</div>\n";
echo "<p id='fps'>evaluating fps</p>\n";
echo "<script>\n";

?>

var currentScale=<?php echo $defaultScale?>;
var liveMode=<?php echo $initialModeIsLive?>;
var fitMode=<?php echo $fitMode?>;
var currentSpeed=<?php echo $speeds[$speedIndex]?>;  // slider scale, which is only for replay and relative to real time
var speedIndex=<?php echo $speedIndex?>;
var currentDisplayInterval=<?php echo $initialDisplayInterval?>;  // will be set based on performance, this is the display interval in milliseconds for history, and fps for live, and dynamically determined (in ms)
var playSecsperInterval=1;         // How many seconds of recorded image we play per refresh determined by speed (replay rate) and display interval; (default=1 if coming from live)
var timerInterval;               // milliseconds between interrupts
var timerObj;               // object to hold timer interval;
var freeTimeLastIntervals=[];    // Percentage of current interval used in loading most recent image
var imageLoadTimesEvaluated=0;   // running count
var imageLoadTimesNeeded=15;     // and how many we need
var timeLabelsFractOfRow = 0.9;
var eMonId = [];
var eId = [];
var eStartSecs = [];
var eEndSecs = [];
var ePath = [];
var eventFrames = [];            // this is going to presume all frames equal durationlength

<?php

// Because we might not have time as the criteria, figure out the min/max time when we run the query

$minTimeSecs = strtotime("2036-01-01 01:01:01");
$maxTimeSecs = strtotime("1950-01-01 01:01:01");

// This builds the list of events that are eligible from this range

$index=0;
$anyAlarms=false;

foreach( dbFetchAll( $eventsSql ) as $event )
{
    if( $minTimeSecs > $event['StartTimeSecs'])   $minTimeSecs=$event['StartTimeSecs'];
    if( $maxTimeSecs < $event['CalcEndTimeSecs']) $maxTimeSecs=$event['CalcEndTimeSecs'];
    echo "eMonId[$index]=" . $event['MonitorId'] . "; eId[$index]=" . $event['Id'] . "; ";
    echo "eStartSecs[$index]=" . $event['StartTimeSecs'] . "; eEndSecs[$index]=" . $event['CalcEndTimeSecs'] . "; ";
    echo "eventFrames[$index]=" . $event['Frames'] . "; ";

    if ( ZM_USE_DEEP_STORAGE )
        echo "ePath[$index] = \"events/" . $event['MonitorId'] . "/" . strftime("%y/%m/%d/%H/%M/%S", $event['StartTimeSecs']) . "/\";" ;
    else
        echo "ePath[$index] = \"events/" . $event['MonitorId'] . "/" . $event['Id'] . "/\";" ;
    $index=$index+1;
    if($event['MaxScore']>0)
        $anyAlarms=true;
    echo "\n";
}

if($index == 0)  // if there is no data set the min/max to the passed in values
{
    if(isset($minTime) && isset($maxTime))
    {
        $minTimeSecs = strtotime($minTime);
        $maxTimeSecs = strtotime($maxTime);
    }
    else // this is the case of no passed in times AND no data -- just set something arbitrary
    {
        $minTimeSecs=strtotime('1950-06-01 01:01:01');  // random time so there's something to display
        $maxTimeSecs=strtotime('2020-06-02 02:02:02');
    }
}

// We only reset the calling time if there was no calling time
if(!isset($minTime) || !isset($maxTime))
{
    $maxTime = strftime($maxTimeSecs);
    $minTime = strftime($minTimeSecs);
}
else
{
    $minTimeSecs = strtotime($minTime);
    $maxTimeSecs = strtotime($maxTime);
}

// If we had any alarms in those events, this builds the list of all alarm frames, but consolidated down to (nearly) contiguous segments 
// comparison in else governs how aggressively it consolidates

echo "var fMonId = [];\n";
echo "var fTimeFromSecs = [];\n";
echo "var fTimeToSecs = [];\n";
echo "var fScore = [];\n";
$maxScore=0;
$index=0;
$mId=-1;
$fromSecs=-1;
$toSecs=-1;
$maxScore=-1;

if($anyAlarms)
    foreach( dbFetchAll ($frameSql) as $frame )
    {
        if($mId<0)
        {
            $mId=$frame['MonitorId'];
            $fromSecs=$frame['TimeStampSecs'];
            $toSecs=$frame['TimeStampSecs'];
            $maxScore=$frame['Score'];
        }
        else if ($mId != $frame['MonitorId'] || $frame['TimeStampSecs'] - $toSecs > 10) // dump this one start a new
        {
            $index++;
            echo "  fMonId[$index]=" . $mId . ";";
            echo "  fTimeFromSecs[$index]=" . $fromSecs . ";";
            echo "  fTimeToSecs[$index]=" . $toSecs . ";";
            echo "  fScore[$index]=" . $maxScore . ";\n";
            $mId=$frame['MonitorId'];
            $fromSecs=$frame['TimeStampSecs'];
            $toSecs=$frame['TimeStampSecs'];
            $maxScore=$frame['Score'];
        }
        else  // just add this one on
        {
            $toSecs=$frame['TimeStampSecs'];
            if($maxScore < $frame['Score']) $maxScore=$frame['Score'];
        }
    }
    if($mId>0)
    {
            echo "  fMonId[$index]=" . $mId . ";";
            echo "  fTimeFromSecs[$index]=" . $fromSecs . ";";
            echo "  fTimeToSecs[$index]=" . $toSecs . ";";
            echo "  fScore[$index]=" . $maxScore . ";\n";
    }

echo "var maxScore=$maxScore;\n";  // used to skip frame load if we find no alarms.
echo "var monitorName = [];\n";
echo "var monitorLoading = [];\n";
echo "var monitorImageObject = [];\n";
echo "var monitorLoadingStageURL = [];\n";
echo "var monitorLoadStartTimems = [];\n";
echo "var monitorLoadEndTimems = [];\n";
echo "var monitorColour = [];\n";
echo "var monitorWidth = [];\n";
echo "var monitorHeight = [];\n";
echo "var monitorIndex = [];\n";
echo "var monitorNormalizeScale = [];\n";
echo "var monitorZoomScale = [];\n";
echo "var monitorCanvasObj = [];\n"; // stash location of these here so we don't have to search
echo "var monitorCanvasCtx = [];\n";
echo "var monitorPtr = []; // monitorName[monitorPtr[0]] is first monitor\n";


$numMonitors=0;  // this array is indexed by the monitor ID for faster access later, so it may be sparse
$avgArea=floatval(0);  // Calculations the normalizing scale

foreach ($monitors as $m)
{
    $avgArea = $avgArea + floatval($m['Width'] * $m['Height']);
    $numMonitors++;
}

if($numMonitors>0) $avgArea= $avgArea / $numMonitors;

$numMonitors=0;
foreach ($monitors as $m)
{
    echo "  monitorLoading["         . $m['Id'] . "]=false;  ";
    echo "  monitorImageObject["     . $m['Id'] . "]=null;  ";
    echo "  monitorLoadingStageURL[" . $m['Id'] . "] = ''; ";
    echo "  monitorColour["          . $m['Id'] . "]=\"" . $m['WebColour'] . "\"; ";
    echo "  monitorWidth["           . $m['Id'] . "]=" . $m['Width'] . "; ";
    echo "  monitorHeight["          . $m['Id'] . "]=" . $m['Height'] . "; ";
    echo "  monitorIndex["           . $m['Id'] . "]=" . $numMonitors . "; ";
    echo "  monitorName["            . $m['Id'] . "]=\"" . $m['Name'] . "\"; ";
    echo "  monitorLoadStartTimems[" . $m['Id'] . "]=0; ";
    echo "  monitorLoadEndTimems["   . $m['Id'] . "]=0; ";
    echo "  monitorCanvasObj["       . $m['Id'] . "]=document.getElementById('Monitor" . $m['Id'] . "'); ";
    echo "  monitorCanvasCtx["       . $m['Id'] . "]=monitorCanvasObj[" . $m['Id'] . "].getContext('2d'); ";
    echo "  monitorNormalizeScale["  . $m['Id'] . "]=" . sqrt($avgArea / ($m['Width'] * $m['Height'] )) . "; ";
    $zoomScale=1.0;
    if(isset($_REQUEST[ 'z' . $m['Id'] ]) )
        $zoomScale = floatval( validHtmlStr($_REQUEST[ 'z' . $m['Id'] ]) );
    echo "  monitorZoomScale["       . $m['Id'] . "]=" . $zoomScale . ";";
    echo "  monitorPtr["         . $numMonitors . "]=" . $m['Id'] . ";\n";
    $numMonitors += 1;
}
echo "var numMonitors = $numMonitors;\n";
echo "var minTimeSecs="     . $minTimeSecs . ";\n";
echo "var maxTimeSecs="     . $maxTimeSecs . ";\n";
echo "var rangeTimeSecs="   . ( $maxTimeSecs - $minTimeSecs + 1) . ";\n";
if(isset($defaultCurrentTime))
    echo "var currentTimeSecs=" . strtotime($defaultCurrentTime) . ";\n";
else
    echo "var currentTimeSecs=" . ($minTimeSecs + $maxTimeSecs)/2 . ";\n";

echo "var speeds=[";
for ($i=0; $i<count($speeds); $i++)
    echo (($i>0)?", ":"") . $speeds[$i];
echo "];\n";
?>

var scrubAsObject=document.getElementById('scrub');
var cWidth;   // save canvas width
var cHeight;  // save canvas height
var canvas=document.getElementById("timeline");  // global canvas definition so we don't have to keep looking it up
var ctx=canvas.getContext('2d');
var underSlider;    // use this to hold what is hidden by the slider
var underSliderX;   // Where the above was taken from (left side, Y is zero)

function evaluateLoadTimes()
{   // Only consider it a completed event if we load ALL monitors, then zero all and start again
    var start=0;
    var end=0;
    if(liveMode!=1 && currentSpeed==0) return;  // don't evaluate when we are not moving as we can do nothing really fast.
    for(var i=0; i<monitorIndex.length; i++)
        if( monitorName[i]>"")
        {
            if( monitorLoadEndTimems[i]==0) return;   // if we have a monitor with no time yet just wait
            if( start == 0 || start > monitorLoadStartTimems[i] ) start = monitorLoadStartTimems[i];
            if( end   == 0 || end   < monitorLoadEndTimems[i]   ) end   = monitorLoadEndTimems[i];
        }
    if(start==0 || end==0) return; // we really should not get here
    for(var i=0; i<numMonitors; i++)
        {
            monitorLoadStartTimems[monitorPtr[i]]=0;
            monitorLoadEndTimems[monitorPtr[i]]=0;
        }
    freeTimeLastIntervals[imageLoadTimesEvaluated++] = 1 - ((end - start)/currentDisplayInterval);
    if( imageLoadTimesEvaluated < imageLoadTimesNeeded ) return;
    var avgFrac=0;
    for(var i=0; i<imageLoadTimesEvaluated; i++)
        avgFrac += freeTimeLastIntervals[i];
    avgFrac = avgFrac / imageLoadTimesEvaluated;
    // The larger this is(positive) the faster we can go
    if      (avgFrac >= 0.9)  currentDisplayInterval = (currentDisplayInterval * 0.50).toFixed(1);  // we can go much faster
    else if (avgFrac >= 0.8)  currentDisplayInterval = (currentDisplayInterval * 0.55).toFixed(1);
    else if (avgFrac >= 0.7)  currentDisplayInterval = (currentDisplayInterval * 0.60).toFixed(1);
    else if (avgFrac >= 0.6)  currentDisplayInterval = (currentDisplayInterval * 0.65).toFixed(1);
    else if (avgFrac >= 0.5)  currentDisplayInterval = (currentDisplayInterval * 0.70).toFixed(1);
    else if (avgFrac >= 0.4)  currentDisplayInterval = (currentDisplayInterval * 0.80).toFixed(1);
    else if (avgFrac >= 0.35) currentDisplayInterval = (currentDisplayInterval * 0.90).toFixed(1);
    else if (avgFrac >= 0.3)  currentDisplayInterval = (currentDisplayInterval * 1.00).toFixed(1);
    else if (avgFrac >= 0.25) currentDisplayInterval = (currentDisplayInterval * 1.20).toFixed(1);
    else if (avgFrac >= 0.2)  currentDisplayInterval = (currentDisplayInterval * 1.50).toFixed(1);
    else if (avgFrac >= 0.1)  currentDisplayInterval = (currentDisplayInterval * 2.00).toFixed(1);
    else currentDisplayInterval                      = (currentDisplayInterval * 2.50).toFixed(1);
    currentDisplayInterval=Math.min(Math.max(currentDisplayInterval, 30),10000);   // limit this from about 30fps to .1 fps
    imageLoadTimesEvaluated=0;
    setSpeed(speedIndex);
    $('fps').innerHTML="Display refresh rate is " + (1000 / currentDisplayInterval).toFixed(1) + " per second, avgFrac=" + avgFrac.toFixed(3) + ".";
}

function SetImageSource(monId,val)
{
    if(liveMode==1)
    {   // This uses the standard php routine to set up the url and authentication, but because it is called repeatedly the built in random number is not usable, so one is appended below for two total (yuck)
        var effectiveScale = (100.0 * monitorCanvasObj[monId].width) / monitorWidth[monId];
        var $x =  "<?php echo getStreamSrc( array("mode=single"),"&" )?>" + "&monitor=" + monId.toString() +  "&scale=" + effectiveScale +  Math.random().toString() ;
        return $x;
    }
    else
    {
        var zeropad = <?php echo  sprintf("\"%0" . ZM_EVENT_IMAGE_DIGITS . "d\"",0); ?>;
        for(var i=0; i<ePath.length; i++)  // Search for a match
        {
            if(eMonId[i]==monId && val >= eStartSecs[i] && val <= eEndSecs[i])
            {
                var frame=parseInt((val - eStartSecs[i])/(eEndSecs[i]-eStartSecs[i])*eventFrames[i])+1;
//                img = ePath[i] + zeropad.substr(frame.toString().length) + frame.toString() + "-capture.jpg";
                  img = "index.php?view=image&path=" + ePath[i].substring(6) + zeropad.substr(frame.toString().length) + frame.toString() + "-capture.jpg" + "&width=" + monitorCanvasObj[monId].width + "&height=" + monitorCanvasObj[monId].height;
               return img;
            }
        }
        return "no data";
    }
}

function imagedone(obj, monId, success)
{
    if(success)
    {
        monitorCanvasCtx[monId].drawImage( monitorImageObject[monId], 0, 0, monitorCanvasObj[monId].width, monitorCanvasObj[monId].height);
        var iconSize=(Math.max(monitorCanvasObj[monId].width,monitorCanvasObj[monId].height) * 0.10);
        monitorCanvasCtx[monId].font = "600 " + iconSize.toString() + "px Arial";
        monitorCanvasCtx[monId].fillStyle="white";
        monitorCanvasCtx[monId].globalCompositeOperation="difference";
        monitorCanvasCtx[monId].fillText("+",iconSize*0.2, iconSize*1.2);
        monitorCanvasCtx[monId].fillText("-",monitorCanvasObj[monId].width - iconSize*1.2, iconSize*1.2);
        monitorCanvasCtx[monId].globalCompositeOperation="source-over";
        monitorLoadEndTimems[monId] = new Date().getTime(); // elapsed time to load
        evaluateLoadTimes();
    }
    monitorLoading[monId]=false;
    if(!success) // if we had a failrue queue up the no-data image
        loadImage2Monitor(monId,"no data");  // leave the staged URL if there is one, just ignore it here.
    else
    {
        if(monitorLoadingStageURL[monId]=="") return;
        loadImage2Monitor(monId,monitorLoadingStageURL[monId]);
        monitorLoadingStageURL[monId]="";
    }
    return;
}

function loadImage2Monitor(monId,url)
{
    if(monitorLoading[monId] && monitorImageObject[monId].src != url )  // never queue the same image twice (if it's loading it has to be defined, right?
    {
       monitorLoadingStageURL[monId]=url;   // we don't care if we are overriting, it means it didn't change fast enough
    }
    else
    {
        var skipthis=0;
        if( typeof monitorImageObject[monId] !== "undefined" && monitorImageObject[monId] != null && monitorImageObject[monId].src == url ) return;   // do nothing if it's the same
        if( monitorImageObject[monId] == null )
        {
            monitorImageObject[monId]=new Image();
            monitorImageObject[monId].onload  = function() {imagedone(this, monId,true )};
            monitorImageObject[monId].onerror = function() {imagedone(this, monId,false)};
        }
        if(url=='no data')
        {
            monitorCanvasCtx[monId].fillStyle="white";
            monitorCanvasCtx[monId].fillRect(0,0,monitorCanvasObj[monId].width,monitorCanvasObj[monId].height);
            var textSize=monitorCanvasObj[monId].width * 0.15;
            var text="No Data";
            monitorCanvasCtx[monId].font = "600 " + textSize.toString() + "px Arial";
            monitorCanvasCtx[monId].fillStyle="black";
            var textWidth = monitorCanvasCtx[monId].measureText(text).width;
            monitorCanvasCtx[monId].fillText(text,monitorCanvasObj[monId].width/2 - textWidth/2,monitorCanvasObj[monId].height/2);
        }
        else
        {
            monitorLoading[monId]=true;
            monitorLoadStartTimems[monId]=new Date().getTime();
            monitorImageObject[monId].src=url;  // starts a load but doesn't refresh yet, wait until ready
        }
    }
}
function timerFire()
{
    // See if we need to reschedule
    if(currentDisplayInterval != timerInterval || currentSpeed == 0) // zero just turn off interrupts
    {
        clearInterval(timerObj);
        timerInterval=currentDisplayInterval;
        if(currentSpeed>0 || liveMode!=0) timerObj=setInterval(timerFire,timerInterval);  // don't fire out of live mode if speed is zero
    }

    if (liveMode) outputUpdate(currentTimeSecs); // In live mode we basically do nothing but redisplay
    else if (currentTimeSecs + playSecsperInterval >= maxTimeSecs) // beyond the end just stop
    {
        setSpeed(0);
        outputUpdate(currentTimeSecs);
    }
    else outputUpdate(currentTimeSecs + playSecsperInterval);
    return;
}

function drawSliderOnGraph(val)
{
    var sliderWidth=10;
    var sliderLineWidth=1;
    var sliderHeight=cHeight;

    if(liveMode==1)
    {
        val=Math.floor( Date.now() / 1000);
    }
    // Set some sizes

    var labelpx = Math.max( 6, Math.min( 20, parseInt(cHeight * timeLabelsFractOfRow / (numMonitors+1)) ) );
    var labbottom=parseInt(cHeight * 0.2 / (numMonitors+1)).toString() + "px";  // This is positioning same as row labels below, but from bottom so 1-position
    var labfont=labelpx + "px Georgia";  // set this like below row labels

    if(numMonitors>0) // if we have no data to display don't do the slider itself 
    {
        var sliderX=parseInt( (val - minTimeSecs) / rangeTimeSecs * cWidth - sliderWidth/2);  // position left side of slider
        if(sliderX < 0) sliderX=0;
        if(sliderX+sliderWidth > cWidth) sliderX=cWidth-sliderWidth-1;

        // If we have data already saved first restore it from LAST time

        if(typeof underSlider !== 'undefined')
        {
            ctx.putImageData(underSlider,underSliderX, 0, 0, 0, sliderWidth, sliderHeight);
            underSlider=undefined;
        }
        if(liveMode==0)  // we get rid of the slider if we switch to live (since it may not be in the "right" place)
        {
            // Now save where we are putting it THIS time
            underSlider=ctx.getImageData(sliderX, 0, sliderWidth, sliderHeight);
            // And add in the slider'
            ctx.lineWidth=sliderLineWidth;
            ctx.strokeStyle='black';
            // looks like strokes are on the outside (or could be) so shrink it by the line width so we replace all the pixels
            ctx.strokeRect(sliderX+sliderLineWidth,sliderLineWidth,sliderWidth - 2*sliderLineWidth, sliderHeight - 2*sliderLineWidth);
            underSliderX=sliderX;
        }
        var o = $('scruboutput');
        if(liveMode==1)
        {
            o.innerHTML="Live Feed @ " + (1000 / currentDisplayInterval).toFixed(1) + " fps";
            o.style.color="red";
        }
        else
        {
            o.innerHTML=secs2dbstr(val);
            o.style.color="blue";
        }
        o.style.position="absolute";
        o.style.bottom=labbottom;
        o.style.font=labfont;
        // try to get length and then when we get too close to the right switch to the left
        var len = o.offsetWidth;
        var x;
        if(sliderX > cWidth/2)
            x=sliderX - len - 10;
        else
            x=sliderX + 10;
        o.style.left=x.toString() + "px";
    }

    // This displays (or not) the left/right limits depending on how close the slider is.
    // Because these change widths if the slider is too close, use the slider width as an estimate for the left/right label length (i.e. don't recalculate len from above)
    // If this starts to collide increase some of the extra space

    var o = $('scrubleft');
    o.innerHTML=secs2dbstr(minTimeSecs);
    o.style.position="absolute";
    o.style.bottom=labbottom;
    o.style.font=labfont;
    o.style.left="5px";
    if(numMonitors==0)  // we need a len calculation if we skipped the slider
        len = o.offsetWidth;
    // If the slider will overlay part of this suppress (this is the left side)
    if(len + 10 > sliderX || cWidth < len * 4 )  // that last check is for very narrow browsers
        o.style.display="none";
    else
    {
        o.style.display="inline";
        o.style.display="inline-flex";  // safari won't take this but will just ignore
    }

    var o = $('scrubright');
    o.innerHTML=secs2dbstr(maxTimeSecs);
    o.style.position="absolute";
    o.style.bottom=labbottom;
    o.style.font=labfont;
    // If the slider will overlay part of this suppress (this is the right side)
    o.style.left=(cWidth - len - 15).toString() + "px";
    if(sliderX > cWidth - len - 20 || cWidth < len * 4 )
        o.style.display="none";
    else
    {
        o.style.display="inline";
        o.style.display="inline-flex";
    }
}

function drawGraph()
{
    var divWidth=$('timelinediv').clientWidth
    canvas.width = cWidth = divWidth;   // Let it float and determine width (it should be sized a bit smaller percentage of window)
    canvas.height=cHeight = parseInt(window.innerHeight * 0.10);
    if(eId.length==0)
    {
        ctx.font="40px Georgia";
        ctx.fillStyle="Black";
        ctx.globalAlpha=1;
        var t="No data found in range - choose differently";
        var l=ctx.measureText(t).width;
        ctx.fillText(t,(cWidth - l)/2, cHeight-10);
        underSlider=undefined;
        return;
    }
    var rowHeight=parseInt(cHeight / (numMonitors + 1) );  // Leave room for a scale of some sort

    // first fill in the bars for the events (not alarms)

    for(var i=0; i<eId.length; i++)  // Display all we loaded
    {
        var x1=parseInt( (eStartSecs[i] - minTimeSecs) / rangeTimeSecs * cWidth) ;        // round low end down
        var x2=parseInt( (eEndSecs[i]   - minTimeSecs) / rangeTimeSecs * cWidth + 0.5 ) ; // round high end up to be sure consecutive ones connect
        ctx.fillStyle=monitorColour[eMonId[i]];
        ctx.globalAlpha = 0.2;    // light color for background
        ctx.clearRect(x1,monitorIndex[eMonId[i]]*rowHeight,x2-x1,rowHeight);  // Erase any overlap so it doesn't look artificially darker
        ctx.fillRect (x1,monitorIndex[eMonId[i]]*rowHeight,x2-x1,rowHeight);
    }
    for(var i=0; (i<fScore.length) && (maxScore>0); i++)  // Now put in scored frames (if any)
    {
        var x1=parseInt( (fTimeFromSecs[i] - minTimeSecs) / rangeTimeSecs * cWidth) ;        // round low end down
        var x2=parseInt( (fTimeToSecs[i]   - minTimeSecs) / rangeTimeSecs * cWidth + 0.5 ) ; // round up
        if(x2-x1 < 2) x2=x1+2;    // So it is visible make them all at least this number of seconds wide
        ctx.fillStyle=monitorColour[fMonId[i]];
        ctx.globalAlpha = 0.4 + 0.6 * (1 - fScore[i]/maxScore);    // Background is scaled but even lowest is twice as dark as the background
        ctx.fillRect(x1,monitorIndex[fMonId[i]]*rowHeight,x2-x1,rowHeight);
    }
    for(var i=0; i<numMonitors; i++)  // Note that this may be a sparse array
    {
        ctx.font= parseInt(rowHeight * timeLabelsFractOfRow).toString() + "px Georgia";
        ctx.fillStyle="Black";
        ctx.globalAlpha=1;
        ctx.fillText(monitorName[monitorPtr[i]], 0, (i + 1 - (1 - timeLabelsFractOfRow)/2 ) * rowHeight );  // This should roughly center font in row
    }
    underSlider=undefined;   // flag we don't have a slider cached
    drawSliderOnGraph(currentTimeSecs);
    return;
}

function redrawScreen()
{
    if(fitMode==0) // if we fit, then monitors were absolutely positioned already (or will be) otherwise release them to float
    {
        for(var i=0; i<numMonitors; i++)
            monitorCanvasObj[monitorPtr[i]].style.position="";
        $('monitors').setStyle('height',"auto");
    }
    if(liveMode==1) // if we are not in live view switch to history -- this has to come before fit in case we re-establish the timeline
    {
        $('SpeedDiv').style.display="none";
        $('timelinediv').style.display="none";
        $('live').innerHTML="History";
        $('zoomin').style.display="none";
        $('zoomout').style.display="none";
        $('panleft').style.display="none";
        $('panright').style.display="none";

    }
    else  // switch out of liveview mode
    {
        $('SpeedDiv').style.display="inline";
        $('SpeedDiv').style.display="inline-flex";
        $('timelinediv').style.display=null;
        $('live').innerHTML="Live";
        $('zoomin').style.display="inline";
        $('zoomin').style.display="inline-flex";
        $('zoomout').style.display="inline";
        $('zoomout').style.display="inline-flex";
        $('panleft').style.display="inline";
        $('panleft').style.display="inline-flex";
        $('panright').style.display="inline";
        $('panright').style.display="inline-flex";
    }

    if(fitMode==1)
    {
        $('ScaleDiv').style.display="none";
        $('fit').innerHTML="Scale";
        var vh=window.innerHeight;
        var vw=window.innerWidth;
        var pos=$('monitors').getPosition();
        var mh=(vh - pos.y - $('fps').getSize().y);
        $('monitors').setStyle('height',mh.toString() + "px");  // leave a small gap at bottom
        if(maxfit2($('monitors').getSize().x,$('monitors').getSize().y) == 0)   /// if we fail to fix we back out of fit mode -- ??? This may need some better handling
            fitMode=1-fitMode;
    }
    else  // switch out of fit mode
    {
        $('ScaleDiv').style.display="inline";
        $('ScaleDiv').style.display="inline-flex";
        $('fit').innerHTML="Fit";
        setScale(currentScale);
    }
    drawGraph();
    outputUpdate(currentTimeSecs);
    timerFire();  // force a fire in case it's not timing
}


function outputUpdate(val)
{
    drawSliderOnGraph(val);
    for(var i=0; i<numMonitors; i++)
    {
            loadImage2Monitor(monitorPtr[i],SetImageSource(monitorPtr[i],val));
    }
    var currentTimeMS = new Date(val*1000);
    currentTimeSecs=val;
}


/// Found this here: http://stackoverflow.com/questions/55677/how-do-i-get-the-coordinates-of-a-mouse-click-on-a-canvas-element
function relMouseCoords(event){
    var totalOffsetX = 0;
    var totalOffsetY = 0;
    var canvasX = 0;
    var canvasY = 0;
    var currentElement = this;

    do{
        totalOffsetX += currentElement.offsetLeft - currentElement.scrollLeft;
        totalOffsetY += currentElement.offsetTop - currentElement.scrollTop;
    }
    while(currentElement = currentElement.offsetParent)

    canvasX = event.pageX - totalOffsetX;
    canvasY = event.pageY - totalOffsetY;

    return {x:canvasX, y:canvasY}
}
HTMLCanvasElement.prototype.relMouseCoords = relMouseCoords;

// These are the functions for mouse movement in the timeline.  Note that touch is treated as a mouse move with mouse down

var mouseisdown=false;
function mdown(event) {mouseisdown=true; mmove(event)}
function mup(event)   {mouseisdown=false;}
function mout(event)  {mouseisdown=false;} // if we go outside treat it as release
function tmove(event) {mouseisdown=true; mmove(event);}

function mmove(event)
{
    if(mouseisdown) // only do anything if the mouse is depressed while on the sheet
    {
        var sec = minTimeSecs + rangeTimeSecs / event.target.width * event.target.relMouseCoords(event).x;
        outputUpdate(sec);
    }
}

function secs2dbstr (s)
{
    var st = (new Date(s * 1000)).format("%Y-%m-%d %H:%M:%S");
    return st;
}

function setFit(value)
{
    fitMode=value;
    redrawScreen();
}

function showScale(newscale) // updates slider only
{
    $('scaleslideroutput').innerHTML = parseFloat(newscale).toFixed(2).toString() + " x";
    return;
}

function setScale(newscale) // makes actual change
{
    showScale(newscale);
    for(var i=0; i<numMonitors; i++)
    {
        monitorCanvasObj[monitorPtr[i]].width=monitorWidth[monitorPtr[i]]*monitorNormalizeScale[monitorPtr[i]]*monitorZoomScale[monitorPtr[i]]*newscale;
        monitorCanvasObj[monitorPtr[i]].height=monitorHeight[monitorPtr[i]]*monitorNormalizeScale[monitorPtr[i]]*monitorZoomScale[monitorPtr[i]]*newscale;
    }
    currentScale=newscale;
}

function showSpeed(val) // updates slider only
{
    $('speedslideroutput').innerHTML = parseFloat(speeds[val]).toFixed(2).toString() + " x";
}

function setSpeed(val)   // Note parameter is the index not the speed
{
    var t;
    if(liveMode==1) return;  // we shouldn't actually get here but just in case
    currentSpeed=parseFloat(speeds[val]);
    speedIndex=val;
    playSecsperInterval = currentSpeed * currentDisplayInterval / 1000;
    showSpeed(val);
    if( timerInterval != currentDisplayInterval || currentSpeed == 0 )  timerFire(); // if the timer isn't firing we need to trigger it to update
}

function setLive(value)
{
    liveMode=value;
    redrawScreen();
}


//vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
// The section below are to reload this program with new parameters

function clicknav(minSecs,maxSecs,arch,live)  // we use the current time if we can
{
    var now = new Date() / 1000;
    var minStr="";
    var maxStr="";
    var currentStr="";
    if(minSecs>0)
    {
        if(maxSecs > now)
            maxSecs = parseInt(now);
        maxStr="&maxTime=" + secs2dbstr(maxSecs);
    }
    if(maxSecs>0)
        minStr="&minTime=" + secs2dbstr(minSecs);
    if(maxSecs==0 && minSecs==0)
    {
        minStr="&minTime=01/01/1950 12:00:00";
        maxStr="&maxTime=12/31/2035 12:00:00";
    }
    var intervalStr="&displayinterval=" + currentDisplayInterval.toString();
    if(minSecs && maxSecs)
    {
        if(currentTimeSecs > minSecs && currentTimeSecs < maxSecs)  // make sure time is in the new range
        currentStr="&current=" + secs2dbstr(currentTimeSecs);
    }

    var liveStr="&live=0";
    if(live==1)
        liveStr="&live=1";

    var fitStr="&fit=0";
    if(fitMode==1)
        fitStr="&fit=1";

    var zoomStr="";
    for(var i=0; i<numMonitors; i++)
        if(monitorZoomScale[monitorPtr[i]] < 0.99 || monitorZoomScale[monitorPtr[i]] > 1.01)  // allow for some up/down changes and just treat as 1 of almost 1
            zoomStr += "&z" + monitorPtr[i].toString() + "=" + monitorZoomScale[monitorPtr[i]].toFixed(2);

    var groupStr=<?php if($group=="") echo '""'; else echo "\"&group=$group\""; ?>;
    var uri = "?view=" + currentView + fitStr + groupStr + minStr + maxStr + currentStr + intervalStr + liveStr + zoomStr + "&scale=" + document.getElementById("scaleslider").value + "&speed=" + speeds[document.getElementById("speedslider").value];
    window.location=uri;
}

function lastHour()
{
    var now = new Date() / 1000;
    clicknav(now - 3600 + 1, now,1,0);
}
function lastEight()
{
    var now = new Date() / 1000;
    clicknav(now - 3600*8 + 1, now,1,0);
}
function zoomin()
{
    rangeTimeSecs = parseInt(rangeTimeSecs / 2);
    minTimeSecs = parseInt(currentTimeSecs - rangeTimeSecs/2);  // this is the slider current time, we center on that
    maxTimeSecs = parseInt(currentTimeSecs + rangeTimeSecs/2);
    clicknav(minTimeSecs,maxTimeSecs,1,0);
}

function zoomout()
{
    rangeTimeSecs = parseInt(rangeTimeSecs * 2);
    minTimeSecs = parseInt(currentTimeSecs - rangeTimeSecs/2);  // this is the slider current time, we center on that
    maxTimeSecs = parseInt(currentTimeSecs + rangeTimeSecs/2);
    clicknav(minTimeSecs,maxTimeSecs,1,0);
}
function panleft()
{
    minTimeSecs = parseInt(minTimeSecs - rangeTimeSecs/2);
    maxTimeSecs = minTimeSecs + rangeTimeSecs - 1;
    clicknav(minTimeSecs,maxTimeSecs,1,0);
}
function panright()
{
    minTimeSecs = parseInt(minTimeSecs + rangeTimeSecs/2);
    maxTimeSecs = minTimeSecs + rangeTimeSecs - 1;
    clicknav(minTimeSecs,maxTimeSecs,1,0);
}
function allof()
{
    clicknav(0,0,1,0);
}
function allnon()
{
    clicknav(0,0,0,0);
}
/// >>>>>>>>>>>>>>>>> handles packing different size/aspect monitors on screen    <<<<<<<<<<<<<<<<<<<<<<<<

function compSize(a, b) // sort array by some size parameter  - height seems to work best.  A semi-greedy algorithm
{
    if      ( monitorHeight[a] * monitorWidth[a] * monitorNormalizeScale[a] * monitorZoomScale[a] * monitorNormalizeScale[a] * monitorZoomScale[a] >  monitorHeight[b] * monitorWidth[b] * monitorNormalizeScale[b] * monitorZoomScale[b] * monitorNormalizeScale[b] * monitorZoomScale[b]) return -1;
    else if ( monitorHeight[a] * monitorWidth[a] * monitorNormalizeScale[a] * monitorZoomScale[a] * monitorNormalizeScale[a] * monitorZoomScale[a] == monitorHeight[b] * monitorWidth[b] * monitorNormalizeScale[b] * monitorZoomScale[b] * monitorNormalizeScale[b] * monitorZoomScale[b])  return 0;
    else return 1;
}


function maxfit2(divW, divH)
{
    var bestFitX=[];   // how we arranged the so-far best match
    var bestFitX2=[];
    var bestFitY=[];
    var bestFitY2=[];
    var bestFitScale;

    var minScale=0.05;
    var maxScale=5.00;
    var bestFitArea=0;

    var borders=-1;

    monitorPtr.sort(compSize);

    while(1)
    {
        if( maxScale - minScale < 0.01 ) break;
        var thisScale = (maxScale + minScale) / 2;
        var allFit=1;
        var thisArea=0;
        var thisX=[];  // top left
        var thisY=[];
        var thisX2=[];  // bottom right
        var thisY2=[];

        for(var m=0; m<numMonitors; m++)
        {
                // this loop places each monitor (if it can)

                function doesItFit(x,y,w,h,d)
                {  // does block (w,h) fit at position (x,y) relative to edge and other nodes already done (0..d)
                   if(x+w>=divW) return 0;
                   if(y+h>=divH) return 0;
                   for(var i=0; i<=d; i++)
                       if( !( thisX[i]>x+w-1 || thisX2[i] < x || thisY[i] > y+h-1 || thisY2[i] < y ) ) return 0;
                   return 1; // it's OK
                }

                if(borders<=0) borders=$("Monitor"+monitorPtr[m]).getStyle("border").toInt() * 2;   // assume fixed size border, and added to both sides and top/bottom
                // try fitting over first, then down.  Each new one must land at either upper right or lower left corner of last (try in that order)
                // Pick the one with the smallest Y, then smallest X if Y equal
                var fitX = 999999999;
                var fitY = 999999999;
                for( adjacent=0; adjacent<m; adjacent++)
                {
                    // try top right of adjacent
                    if( doesItFit(thisX2[adjacent]+1, thisY[adjacent], monitorWidth[monitorPtr[m]] * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders, monitorHeight[monitorPtr[m]] * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders, m-1) == 1 )
                    {
                        if(thisY[adjacent]<fitY || ( thisY[adjacent]==fitY && thisX2[adjacent]+1 < fitX ))
                        {
                            fitX=thisX2[adjacent]+1;
                            fitY=thisY[adjacent];
                        }
                    }
                    // try bottom left
                    if ( doesItFit(thisX[adjacent], thisY2[adjacent]+1, monitorWidth[monitorPtr[m]] * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders, monitorHeight[monitorPtr[m]] * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders, m-1) == 1 )
                    {
                        if(thisY2[adjacent]+1<fitY || ( thisY2[adjacent]+1 == fitY && thisX[adjacent]<fitX ))
                        {
                            fitX=thisX[adjacent];
                            fitY=thisY2[adjacent]+1;
                        }
                    }
                 }
                 if(m==0) // note for teh very first one there were no adjacents so the above loop didn't run
                 {
                     if( doesItFit(0,0,monitorWidth[monitorPtr[m]] * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders, monitorHeight[monitorPtr[m]] * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders, -1) == 1 )
                     {
                        fitX=0;
                        fitY=0;
                     }
                 }
                 if(fitX==999999999)
                 {
                     allFit=0;
                     break; // break out of monitor loop flagging we didn't fit
                 }
                 thisX[m] =fitX;
                 thisX2[m]=fitX + monitorWidth[monitorPtr[m]]  * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders;
                 thisY[m] =fitY;
                 thisY2[m]=fitY + monitorHeight[monitorPtr[m]] * thisScale * monitorNormalizeScale[monitorPtr[m]] * monitorZoomScale[monitorPtr[m]] + borders;
                 thisArea += (thisX2[m] - thisX[m])*(thisY2[m] - thisY[m]);
        }
        if(allFit==1)
        {
            minScale=thisScale;
            if(bestFitArea<thisArea)
            {
                bestFitArea=thisArea;
                bestFitX=thisX;
                bestFitY=thisY;
                bestFitX2=thisX2;
                bestFitY2=thisY2;
                bestFitScale=thisScale;
            }
        }
        else // didn't fit
        {
            maxScale=thisScale;
        }
    }
    if(bestFitArea>0)   // only rearrange if we could fit -- otherwise just do nothing, let them start coming out, whatever
    {
        for(m=0; m<numMonitors; m++)
        {
            c = $("Monitor" + monitorPtr[m]);
            c.style.position="absolute";
            c.style.left=bestFitX[m].toString() + "px";
            c.style.top=bestFitY[m].toString() + "px";
            c.width = bestFitX2[m] - bestFitX[m] + 1 - borders;
            c.height= bestFitY2[m] - bestFitY[m] + 1 - borders;
        }
        return 1;
    }
    else
        return 0;
}

// >>>>>>>>>>>>>>>> Handles individual monitor clicks and navigation to the standard event/watch display

function showOneMonitor(monId)  // link out to the normal view of one event's data
{
    // We know the monitor, need to determine the event based on current time
    var url;
    if(liveMode!=0) url="?view=watch&mid=" + monId.toString();
    else
        for(var i=0; i<eId.length; i++)
            if(eMonId[i]==monId && currentTimeSecs >= eStartSecs[i] && currentTimeSecs <= eEndSecs[i])
                url="?view=event&eid=" + eId[i] + '&fid=' + parseInt(Math.max(1, Math.min(eventFrames[i], eventFrames[i] * (currentTimeSecs - eStartSecs[i]) / (eEndSecs[i] - eStartSecs[i] + 1) ) ));
    createPopup(url, 'zmEvent', 'event', monitorWidth[eMonId[i]], monitorHeight[eMonId[i]]);
}

function zoom(monId,scale)
{
    var lastZoomMonPriorScale=monitorZoomScale[monId];
    monitorZoomScale[monId] *= scale;
    if(redrawScreen()==0) // failure here is probably because we zoomed too far
    {
        monitorZoomScale[monId]=lastZoomMonPriorScale;
        alert("You can't zoom that far -- rolling back");
        redrawScreen();  // put things back and hope it works
    }
}

function clickMonitor(event,monId)
{
    var pos_x = event.offsetX ? (event.offsetX) : event.pageX - $("Monitor"+monId.toString()).offsetLeft;
    var pos_y = event.offsetY ? (event.offsetY) : event.pageY - $("Monitor"+monId.toString()).offsetTop;
    if(pos_x < $("Monitor"+monId.toString()).width/4     && pos_y < $("Monitor"+monId.toString()).height/4) zoom(monId,1.15);
    else if(pos_x > $("Monitor"+monId.toString()).width * 3/4 && pos_y < $("Monitor"+monId.toString()).height/4) zoom(monId,1/1.15);
    else showOneMonitor(monId);
    return;
}

// >>>>>>>>> Initialization that runs on window load by being at the bottom 

drawGraph();
setSpeed(speedIndex);
setFit(fitMode);  // will redraw 
setLive(liveMode);  // will redraw
window.addEventListener("resize",redrawScreen);

</script>

</div>
</body>
</html>
