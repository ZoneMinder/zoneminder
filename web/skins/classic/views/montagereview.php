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
    select E.Id,E.Name,UNIX_TIMESTAMP(E.StartTime) as StartTimeSecs,UNIX_TIMESTAMP(max(DATE_ADD(E.StartTime, Interval Delta+0.5 Second))) as CalcEndTimeSecs, E.Length,max(F.FrameId) as Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId
    from Events as E
    inner join Monitors as M on (E.MonitorId = M.Id)
    inner join Frames F on F.EventId=E.Id
    where not isnull(E.Frames) and not isnull(StartTime) ";

// Note that the delta value seems more accurate than the time stamp for some reason.
$frameSql = "
    select E.Id as EventId, E.MonitorId, UNIX_TIMESTAMP(DATE_ADD(E.StartTime, Interval Delta Second)) as TimeStampSecs, max(F.Score) as Score
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

if ( isset($_REQUEST['minTime']) )
    $minTime = validHtmlStr($_REQUEST['minTime']);

if ( isset($_REQUEST['maxTime']) )
    $maxTime = validHtmlStr($_REQUEST['maxTime']);

if ( isset($_REQUEST['scale']) )
    $defaultScale=validHtmlStr($_REQUEST['scale']);
else
    $defaultScale=0.3;

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


$initialModeIsLive=0;
if(isset($_REQUEST['live']) )
    $initialModeIsLive=1;

$initialDisplayInterval=1000;
if(isset($_REQUEST['displayinterval']))
    $initialDisplayInterval=validHtmlStr($_REQUEST['displayinterval']);

$archive=1;
if (isset($_REQUEST['archive']) )
    $archive=validHtmlStr($_REQUEST['archive']);

if ($archive==0)
{
    $eventsSql .= " and E.Archived=0 ";
}    $frameSql .= " and E.Archived=0 ";

$eventsSql .= "group by E.Id,E.Name,E.StartTime,E.Length,E.Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId ";

if( isset($minTime) && isset($maxTime) )
{
    $minTimeSecs = strtotime($minTime);
    $maxTimeSecs = strtotime($maxTime);
    $eventsSql .= "having CalcEndTimeSecs > '" . $minTimeSecs . "' and StartTimeSecs < '" . $maxTimeSecs . "'";
    $frameSql .= "and TimeStamp > '" . $minTime . "' and TimeStamp < '" . $maxTime . "'";
}
$frameSql .= "group by E.Id, E.MonitorId, F.TimeStamp order by E.MonitorId, F.TimeStamp asc";

xhtmlHeaders(__FILE__, translate('montagereview') );
?>
<style>
input[type=range]::-ms-tooltip {
    display: none;
}

</style>
</head>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow();"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('Montage Review') ?></h2>
    </div>

    <div style='display: inline-flex; border: 1px solid black;'>
        <label style='margin:5px;' for=scaleslider><?php echo translate('Scale')?></label>
        <input id=scaleslider type=range min=0.1 max=1.00 value=<?php echo $defaultScale ?> step=0.10 width=20% onchange='changescale(this.value)' oninput='inputscale(this.value)'/>
        <output style='margin:5px;' id=scaleslideroutput from=scaleslider><?php echo number_format((float)$defaultScale,2,'.','')?> x</output>
    </div>
    <div id='SpeedDiv' style='display: inline-flex; border: 1px solid black;'>
        <label style='margin:5px;' for=speedslider><?php echo translate('Speed') ?></label>
        <input id=speedslider type=range min=0 max=<?php echo count($speeds)?> value=<?php echo $speedIndex ?> step=1 wdth=20% onchange='changespeed(this.value)' oninput='inputspeed(this.value)'/>
        <output style='margin:5px;' id=speedslideroutput from=speedslider><?php echo $speeds[$speedIndex] ?> fps</output>
    </div>
    <div style='display: inline-flex; border: 1px solid black; flex-flow: row wrap;'>
        <button type='button' id=panleft   onclick='panleft()  '>&lt;&nbsp;<?php echo translate('Pan&nbsp;Left') ?></button>
        <button type='button' id=zoomin    onclick='zoomin()   '><?php echo translate('Zoom&nbsp;In&nbsp;+') ?></button>
        <button type='button' id=zoomout   onclick='zoomout()  '><?php echo translate('Zoom&nbsp;Out&nbsp;-') ?></button>
        <button type='button' id=lasthour  onclick='lasthour() '><?php echo translate('Last Hour') ?></button>
        <button type='button' id=allof     onclick='allof()    '><?php echo translate('All Events') ?></button>
        <button type='button' id=allnon    onclick='allnon()   '><?php echo translate('All&nbsp;Non-Archive') ?></button>
        <button type='button' id=panright  onclick='panright() '><?php echo translate('Pan&nbsp;Right&nbsp;&gt;') ?></button>
        <button type='button' id=live      onclick='liveview(1)'><?php echo translate('Live Feed') ?></button>
    </div>

    <div id=timelinediv style='position:relative; width:93%;'>
        <canvas id=timeline style='border:1px solid;' onmousemove='mmove(event)' ontouchmove='tmove(event)' onmousedown='mdown(event)' onmouseup='mup(event)' onmouseout='mout(event)' ></canvas>
        <output id=scrubleft for=scrub></output>
        <output id=scrubright for=scrub></output>
        <output id=scruboutput for=scrub></output>
    </div>



<script>
var currentScale=<?php echo $defaultScale?>;
var liveMode=<?php echo $initialModeIsLive?>;
var currentSpeed=<?php echo $speeds[$speedIndex]?>;  // slider scale, which is only for replay and relative to real time
var speedIndex=<?php echo $speedIndex?>;
var currentDisplayInterval=<?php echo $initialDisplayInterval?>;  // will be set based on performance, this is the display interval in milliseconds for history, and fps for live, and dynamically determined (in ms)
var playSecsperInterval;         // How many seconds of recorded image we play per refresh determined by speed (replay rate) and display interval;
var timerInterval;               // milliseconds between interrupts
var timerObj;               // object to hold timer interval;
var freeTimeLastIntervals=[];    // Percentage of current interval used in loading most recent image
var imageLoadTimesEvaluated=0;   // running count
var imageLoadTimesNeeded=15;     // and how many we need
var timeLabelsFractOfRow = 0.9;
var eventMonitorId = [];
var eventId = [];
var eventStartTimeSecs = [];
var eventEndTimeSecs = [];
var eventPath = [];
var eventFrames = [];            // this is going to presume all frames equal durationlength

<?php

// Because we might not have time as the criteria, figure out the min/max time when we run the query

$minTimeSecs = strtotime("2036-01-01 01:01:01");
$maxTimeSecs = strtotime("1950-01-01 01:01:01");

// This loads all monitors the user can see -- we will prune this list later based on what we find

$monitors = array();
$monitorsSql .= " order by Sequence asc ";
$index=0;
foreach( dbFetchAll( $monitorsSql ) as $row )
{
    $monitors[$index] = $row;
    $index = $index + 1;
}
// This builds the list of events that are eligible from this range

$index=0;
$anyAlarms=false;
$eventsMonitorsFound = array();     // this will just flag which ones found

foreach( dbFetchAll( $eventsSql ) as $event )
{
    if( $minTimeSecs > $event['StartTimeSecs'])   $minTimeSecs=$event['StartTimeSecs'];
    if( $maxTimeSecs < $event['CalcEndTimeSecs']) $maxTimeSecs=$event['CalcEndTimeSecs'];
    echo "eventMonitorId[$index]=" . $event['MonitorId'] . "; eventId[$index]=" . $event['Id'] . "; ";
    echo "eventStartTimeSecs[$index]=" . $event['StartTimeSecs'] . "; eventEndTimeSecs[$index]=" . $event['CalcEndTimeSecs'] . "; ";
    echo "eventFrames[$index]=" . $event['Frames'] . "; ";

    if ( ZM_USE_DEEP_STORAGE )
        echo "eventPath[$index] = \"events/" . $event['MonitorId'] . "/" . strftime("%y/%m/%d/%H/%M/%S", $event['StartTimeSecs']) . "/\";" ;
    else
        echo "eventPath[$index] = \"events/" . $event['MonitorId'] . "/" . $event['Id'] . "/\";" ;
    $eventsMonitorsFound[$event['MonitorId']] = true;
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

echo "var frameMonitorId = [];\n";
echo "var frameTimeStampFromSecs = [];\n";
echo "var frameTimeStampToSecs = [];\n";
echo "var frameScore = [];\n";
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
        else if ($mId != $frame['MonitorId'] || $frame['TimeStampSecs'] - $toSecs > 1) // dump this one start a new
        {
            $index++;
            echo "  frameMonitorId[$index]=" . $mId . ";";
            echo "  frameTimeStampFromSecs[$index]=" . $fromSecs . ";";
            echo "  frameTimeStampToSecs[$index]=" . $toSecs . ";";
            echo "  frameScore[$index]=" . $maxScore . ";\n";
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
            echo "  frameMonitorId[$index]=" . $mId . ";";
            echo "  frameTimeStampFromSecs[$index]=" . $fromSecs . ";";
            echo "  frameTimeStampToSecs[$index]=" . $toSecs . ";";
            echo "  frameScore[$index]=" . $maxScore . ";\n";
    }

// This is where we have to display the canvases -- AFTER determining which monitors we use (above in events) and BEFORE we loop through them to cache the objects
// This splits up the javascript and html a bit, but it's a lot simpler than trying in php to cache one while completing the other

// Monitor images - these had to be loaded after the monitors used were determined (after loading events)

echo "</script>\n";
echo "<div id='monitors' style='background-color:black;' width='100%' height='100%'>\n";
foreach ($monitors as $m)
{
    if(!empty($eventsMonitorsFound[$m['Id']]))  // only save the monitor if it's part of these events
    {
          echo "<canvas width='" . $m['Width'] * $defaultScale . "px' height='"  . $m['Height'] * $defaultScale . "px' id='Monitor" . $m['Id'] . "' style='border:3px solid " . $m['WebColour'] . "' onclick='showOneEvent(" . $m['Id'] . ")'>No Canvas Support!!</canvas>\n";
    }
}
echo "</div>\n";
echo "<p id='fps'>evaluating fps</p>\n";
echo "<script>\n";

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
echo "var monitorCanvasObj = [];\n"; // stash location of these here so we don't have to search
echo "var monitorCanvasCtx = [];\n";

// This builds the list of monitors.

$numMonitors=0;  // this array is indexed by the monitor ID for faster access later, so it may be sparse
foreach ($monitors as $m)
{
    if(!empty($eventsMonitorsFound[$m['Id']]))  // only save the monitor if it's part of these events
    {
        echo "  monitorLoading["         . $m['Id'] . "]=false;  ";
        echo "  monitorImageObject["     . $m['Id'] . "]=null;  ";
        echo "  monitorLoadingStageURL[" . $m['Id'] . "] = ''; ";
        echo "  monitorColour["          . $m['Id'] . "]=\"" . $m['WebColour'] . "\"; ";
        echo "  monitorWidth["           . $m['Id'] . "]=" . $m['Width'] . "; ";
        echo "  monitorHeight["          . $m['Id'] . "]=" . $m['Height'] . ";";
        echo "  monitorIndex["           . $m['Id'] . "]=" . $numMonitors . ";";
        echo "  monitorName["            . $m['Id'] . "]=\"" . $m['Name'] . "\"; ";
        echo "  monitorLoadStartTimems[" . $m['Id'] . "]=0; ";
        echo "  monitorLoadEndTimems["   . $m['Id'] . "]=0; ";
        echo "  monitorCanvasObj["       . $m['Id'] . "]=document.getElementById('Monitor" . $m['Id'] . "');";
        echo "  monitorCanvasCtx["       . $m['Id'] . "]=monitorCanvasObj[" . $m['Id'] . "].getContext('2d');\n";
        $numMonitors += 1;
    }
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


function outputUpdate(val)
{
    drawSliderOnGraph(val);
    for(var i=0; i<monitorIndex.length; i++)
    {
        if(monitorName[i]>"")
            loadImage2Monitor(i,SetImageSource(i,val));
    }
    var currentTimeMS = new Date(val*1000);
    currentTimeSecs=val;
}

function SetImageSource(monId,val)
{
    if(liveMode==1)
    {
        return "../cgi-bin/nph-zms?mode=single&monitor=" + monId.toString() + "&scale=" + (currentScale*100).toString() + "&cachekill=" + Math.random().toString();
    }
    else
    {
        var zeropad = <?php echo  sprintf("\"%0" . ZM_EVENT_IMAGE_DIGITS . "d\"",0); ?>;
        for(var i=0; i<eventPath.length; i++)  // Search for a match
        {
            if(eventMonitorId[i]==monId && val >= eventStartTimeSecs[i] && val <= eventEndTimeSecs[i])
            {
                frame=parseInt((val - eventStartTimeSecs[i])/(eventEndTimeSecs[i]-eventStartTimeSecs[i])*eventFrames[i])+1;
//                img = eventPath[i] + zeropad.substr(frame.toString().length) + frame.toString() + "-capture.jpg";
                  img = "index.php?view=image&path=" + eventPath[i].substring(6) + zeropad.substr(frame.toString().length) + frame.toString() + "-capture.jpg";
                i=eventPath.length+1;  // force loop exit
               return img;
            }
        }
        return "graphics/NoDataImage.gif";
    }
}

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
    for(var i=0; i<monitorIndex.length; i++)
        if(monitorName[i]>"")
        {
            monitorLoadStartTimems[i]=0;
            monitorLoadEndTimems[i]=0;
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
    changespeed(speedIndex);
    document.getElementById("fps").innerHTML="Display refresh rate is " + (1000 / currentDisplayInterval).toFixed(1) + " per second, avgFrac=" + avgFrac.toFixed(3) + ".";
}

function imagedone(obj, monId, success)
{
// don't need?     monitorCanvasCtx[monId].clearRect(0,0,monitorCanvasObj[monId].width,monitorCanvasObj[monId].height);  // just in case image is no good
    if(success)
    {
        monitorCanvasCtx[monId].drawImage(monitorImageObject[monId],0,0,monitorCanvasObj[monId].width,monitorCanvasObj[monId].height);
        monitorLoadEndTimems[monId] = new Date().getTime(); // elapsed time to load
        evaluateLoadTimes();
    }
    monitorImageObject[monId]=null;
    monitorLoading[monId]=false;
    if(!success) // if we had a failrue queue up the no-data image
        loadImage2Monitor(monId,"graphics/NoDataImage.gif");  // leave the staged URL if there is one, just ignore it here.
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
//        monitorImageObject[monId]=undefined;
        monitorImageObject[monId]=new Image();
        monitorImageObject[monId].onload  = function() {imagedone(this, monId,true )};
        monitorImageObject[monId].onerror = function() {imagedone(this, monId,false)};
        monitorImageObject[monId].src=url;  // starts a load but doesn't refresh yet, wait until ready
        monitorLoading[monId]=true;
        monitorLoadStartTimems[monId]=new Date().getTime();
    }
}

function inputscale(newscale) // updates slider only
{
    document.getElementById('scaleslideroutput').value = parseFloat(newscale).toFixed(2).toString() + " x";
    return;
}

function changescale(newscale) // makes actual change
{
    for(var i=0; i<monitorIndex.length; i++)
    {
        if(monitorName[i]>"")
          {
            monitorCanvasObj[i].width=monitorWidth[i]*newscale;
            monitorCanvasObj[i].height=monitorHeight[i]*newscale;
          }
    }
    document.getElementById('scaleslideroutput').value = parseFloat(newscale).toFixed(2).toString() + " x";
    outputUpdate(currentTimeSecs);
    currentScale=newscale;
    return;
}
function inputspeed(val) // updates slider only
{
    document.getElementById('speedslideroutput').innerHTML = parseFloat(speeds[val]).toFixed(2).toString() + " x";
}

function changespeed(val)   // Note parameter is the index not the speed
{
    var t;
    if(liveMode==1) return;  // we shouldn't actually get here but just in case
    currentSpeed=parseFloat(speeds[val]);
    speedIndex=val;
    if( timerInterval != currentDisplayInterval && timerInterval == 0)  TimerFire(); // if the timer isn't firing we need to trigger it to update
    playSecsperInterval = currentSpeed * currentDisplayInterval / 1000;
    document.getElementById('speedslideroutput').innerHTML = currentSpeed.toFixed(2).toString() + " x";
}

function TimerFire()
{
    // See if we need to reschedule
    if(currentDisplayInterval != timerInterval) // zero just turn off interrupts
    {
        clearInterval(timerObj);
        timerInterval=currentDisplayInterval;
        if(currentDisplayInterval>0) timerObj=setInterval(TimerFire,timerInterval);
    }

    if (liveMode) outputUpdate(currentTimeSecs); // In live mode we basically do nothing but redisplay
    else if (currentTimeSecs + playSecsperInterval >= maxTimeSecs) // beyond the end just stop
    {
        changespeed(0);
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

    labelpx = Math.max( 6, Math.min( 20, parseInt(cHeight * timeLabelsFractOfRow / (numMonitors+1)) ) );
    labbottom=parseInt(cHeight * 0.2 / (numMonitors+1)).toString() + "px";  // This is positioning same as row labels below, but from bottom so 1-position
    labfont=labelpx + "px Georgia";  // set this like below row labels

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
        var o = document.getElementById('scruboutput');
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
        if(sliderX > cWidth/2)
            x=sliderX - len - 10;
        else
            x=sliderX + 10;
        o.style.left=x.toString() + "px";
    }

    // This displays (or not) the left/right limits depending on how close the slider is.
    // Because these change widths if the slider is too close, use the slider width as an estimate for the left/right label length (i.e. don't recalculate len from above)
    // If this starts to collide increase some of the extra space

    var o = document.getElementById('scrubleft');
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
        o.style.display="inline";

    var o = document.getElementById('scrubright');
    o.innerHTML=secs2dbstr(maxTimeSecs);
    o.style.position="absolute";
    o.style.bottom=labbottom;
    o.style.font=labfont;
    // If the slider will overlay part of this suppress (this is the right side)
    o.style.left=(cWidth - len - 15).toString() + "px";
    if(sliderX > cWidth - len - 20 || cWidth < len * 4 )
        o.style.display="none";
    else
        o.style.display="inline";
}

function drawGraph()
{
    var divWidth=document.getElementById("timelinediv").clientWidth
    canvas.width = cWidth = divWidth;   // Let it float and determine width (it should be sized a bit smaller percentage of window)
    canvas.height=cHeight = parseInt(window.innerHeight * 0.10);
    if(eventId.length==0)
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

    for(var i=0; i<eventId.length; i++)  // Display all we loaded
    {
        var x1=parseInt( (eventStartTimeSecs[i] - minTimeSecs) / rangeTimeSecs * cWidth) ;        // round low end down
        var x2=parseInt( (eventEndTimeSecs[i]   - minTimeSecs) / rangeTimeSecs * cWidth + 0.5 ) ; // round high end up to be sure consecutive ones connect
        ctx.fillStyle=monitorColour[eventMonitorId[i]];
        ctx.globalAlpha = 0.2;    // light color for background
        ctx.clearRect(x1,monitorIndex[eventMonitorId[i]]*rowHeight,x2-x1,rowHeight);  // Erase any overlap so it doesn't look artificially darker
        ctx.fillRect (x1,monitorIndex[eventMonitorId[i]]*rowHeight,x2-x1,rowHeight);
    }
    for(var i=0; (i<frameScore.length) && (maxScore>0); i++)  // Now put in scored frames (if any)
    {
        var x1=parseInt( (frameTimeStampFromSecs[i] - minTimeSecs) / rangeTimeSecs * cWidth) ;        // round low end down
        var x2=parseInt( (frameTimeStampToSecs[i]   - minTimeSecs) / rangeTimeSecs * cWidth + 0.5 ) ; // round up
        if(x2-x1 < 2) x2=x1+2;    // So it is visible make them all at least this number of seconds wide
        ctx.fillStyle=monitorColour[frameMonitorId[i]];
        ctx.globalAlpha = 0.4 + 0.6 * (1 - frameScore[i]/maxScore);    // Background is scaled but even lowest is twice as dark as the background
        ctx.fillRect(x1,monitorIndex[frameMonitorId[i]]*rowHeight,x2-x1,rowHeight);
    }
    for(var i=0; i<monitorName.length; i++)  // Note that this may be a sparse array
    {
        if(monitorName[i]>"")
        {
            ctx.font= parseInt(rowHeight * timeLabelsFractOfRow).toString() + "px Georgia";
            ctx.fillStyle="Black";
            ctx.globalAlpha=1;
            ctx.fillText(monitorName[i], 0, (monitorIndex[i] + 1 - (1 - timeLabelsFractOfRow)/2 ) * rowHeight );  // This should roughly center font in row
        }
    }
    underSlider=undefined;   // flag we don't have a slider cached
    drawSliderOnGraph(currentTimeSecs);
    return;
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
function mup(event) {mouseisdown=false;}
function mout(event) {mouseisdown=false;} // if we go outside treat it as release
function tmove(event) {mouseisdown=true; mmove(event);}

function mmove(event)
{
    if(mouseisdown) // only do anything if the mouse is depressed while on the sheet
    {
        var sec = minTimeSecs + rangeTimeSecs / event.target.width * event.target.relMouseCoords(event).x;
        if(liveMode==1) liveview(0);  // If we were in live mode turn it off
        outputUpdate(sec);
    }
}

function secs2dbstr (s)
{
    var st = (new Date(s * 1000)).format("%Y-%m-%d %H:%M:%S");
    return st;
}

function liveview(which)
{
    liveMode=which;
    if(which==1)
    {
        document.getElementById("SpeedDiv").style.display="none";

    }
    else  // switch out of liveview mode
    {
        document.getElementById("SpeedDiv").style.display="inline-flex";
    }
}

//vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
// The sceond below are to reload this program with new parameters

function clicknav(minSecs,maxSecs,arch,live)  // we use the current time if we can
{
    var now = new Date() / 1000;
    var minStr="";
    var maxStr="";
    var currentStr="";
    var archiveStr="";
    if(minSecs>0)
    {
        if(maxSecs > now)
            maxSecs = parseInt(now);
        maxStr="&maxTime=" + secs2dbstr(maxSecs);
    }
    if(maxSecs>0)
        minStr="&minTime=" + secs2dbstr(minSecs);
    if(arch==0)
        archiveStr="&archive=0";
    var intervalStr="&displayinterval=" + currentDisplayInterval.toString();
    if(minSecs && maxSecs)
    {
        if(currentTimeSecs > minSecs && currentTimeSecs < maxSecs)  // make sure time is in the new range
        currentStr="&current=" + secs2dbstr(currentTimeSecs);
    }
    var liveStr="";
    if(live==1)
        liveStr="&live";
    var groupStr=<?php if($group=="") echo '""'; else echo "\"&group=$group\""; fi; ?>;
    var uri = "?view=" + currentView + groupStr + minStr + maxStr + currentStr + intervalStr + liveStr + "&scale=" + document.getElementById("scaleslider").value + "&speed=" + speeds[document.getElementById("speedslider").value] + "&archive=" + arch;
    window.location=uri;
}

function lasthour()
{
    var now = new Date() / 1000;
    clicknav(now - 3600 + 1, now,1,0);
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
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^ End of handlers for reloading this program ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

function showOneEvent(monId)  // link out to the normal view of one event's data
{
    // We know the monitor, need to determine the event based on current time
    for(var i=0; i<eventId.length; i++)  // Display all we loaded
    {
        if(eventMonitorId[i]==monId && currentTimeSecs >= eventStartTimeSecs[i] && currentTimeSecs <= eventEndTimeSecs[i])
        {
            var url = '?view=event&eid=' + eventId[i] + '&fid=' + parseInt(Math.max(1, Math.min(eventFrames[i], eventFrames[i] * (currentTimeSecs - eventStartTimeSecs[i]) / (eventEndTimeSecs[i] - eventStartTimeSecs[i] + 1) ) ));
            createPopup(url, 'zmEvent', 'event', monitorWidth[eventMonitorId[i]], monitorHeight[eventMonitorId[i]]);
        }
    }

}


// Do this on load implicitly
liveview(<?php echo $initialModeIsLive?>);
drawGraph();
timerObj=setInterval(TimerFire,timerInterval=currentDisplayInterval); //start the timer
changespeed(<?php echo $speedIndex ?>);
outputUpdate(currentTimeSecs);
window.addEventListener("resize",drawGraph);

</script>

</div>
</body>
</html>


