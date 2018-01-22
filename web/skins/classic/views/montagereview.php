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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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

if ( !canView( 'Events' ) ) {
  $view = 'error';
  return;
}

require_once( 'includes/Monitor.php' );

# FIXME THere is no way to select group at this time.
if ( !empty($_REQUEST['group']) ) {
  $group = $_REQUEST['group'];
  $row = dbFetchOne( 'SELECT * FROM Groups WHERE Id = ?', NULL, array($_REQUEST['group']) );
  $monitorsSql = "SELECT * FROM Monitors WHERE Function != 'None' AND find_in_set( Id, '".$row['MonitorIds']."' ) ";
} else {
  $monitorsSql = "SELECT * FROM Monitors WHERE Function != 'None'";
  $group = '';
}

// Note that this finds incomplete events as well, and any frame records written, but still cannot "see" to the end frame
// if the bulk record has not been written - to be able to include more current frames reduce bulk frame sizes (event size can be large)
// Note we round up just a bit on the end time as otherwise you get gaps, like 59.78 to 00 in the next second, which can give blank frames when moved through slowly.

$eventsSql = '
  SELECT E.Id,E.Name,UNIX_TIMESTAMP(E.StartTime) AS StartTimeSecs,
         CASE WHEN E.EndTime IS NULL THEN (SELECT UNIX_TIMESTAMP(DATE_ADD(E.StartTime, Interval max(Delta)+0.5 Second)) FROM Frames F WHERE F.EventId=E.Id)
              ELSE UNIX_TIMESTAMP(E.EndTime)
         END AS CalcEndTimeSecs, E.Length,
         CASE WHEN E.Frames IS NULL THEN (Select count(*) FROM Frames F WHERE F.EventId=E.Id) ELSE E.Frames END AS Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId
  FROM Events AS E
  INNER JOIN Monitors AS M ON (E.MonitorId = M.Id)
  WHERE NOT isnull(E.Frames) AND NOT isnull(StartTime)';

//    select E.Id,E.Name,UNIX_TIMESTAMP(E.StartTime) as StartTimeSecs,UNIX_TIMESTAMP(max(DATE_ADD(E.StartTime, Interval Delta+0.5 Second))) as CalcEndTimeSecs, E.Length,max(F.FrameId) as Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId
//    from Events as E
//    inner join Monitors as M on (E.MonitorId = M.Id)
//    inner join Frames F on F.EventId=E.Id
//    where not isnull(E.Frames) and not isnull(StartTime) ";

// Note that the delta value seems more accurate than the time stamp for some reason.
$frameSql = '
    SELECT E.Id AS eId, E.MonitorId, UNIX_TIMESTAMP(DATE_ADD(E.StartTime, Interval Delta Second)) AS TimeStampSecs, max(F.Score) AS Score
    FROM Events AS E
    INNER JOIN Frames AS F ON (F.EventId = E.Id)
    WHERE NOT isnull(StartTime) AND F.Score>0';

// This program only calls itself with the time range involved -- it does all monitors (the user can see, in the called group) all the time

if ( ! empty( $user['MonitorIds'] ) ) {
    $eventsSql   .= ' AND M.Id IN ('.$user['MonitorIds'].')';
    $monitorsSql .= ' AND Id IN ('.$user['MonitorIds'].')';
    $frameSql    .= ' AND E.MonitorId IN ('.$user['MonitorIds'].')';
}

// Parse input parameters -- note for future, validate/clean up better in case we don't get called from self.
// Live overrides all the min/max stuff but it is still processed

// The default (nothing at all specified) is for 1 hour so we do not read the whole database


if ( !isset($_REQUEST['minTime']) && !isset($_REQUEST['maxTime']) ) {
  $maxTime = strftime("%c",time());
  $minTime = strftime("%c",time() - 3600);
}
if ( isset($_REQUEST['minTime']) )
  $minTime = validHtmlStr($_REQUEST['minTime']);

if ( isset($_REQUEST['maxTime']) )
  $maxTime = validHtmlStr($_REQUEST['maxTime']);

// AS a special case a "all" is passed in as an exterme interval - if so , clear them here and let the database query find them

if ( (strtotime($maxTime) - strtotime($minTime))/(365*24*3600) > 30 ) {
  // test years
  $minTime = null;
  $maxTime = null;
}

$fitMode=1;
if (isset($_REQUEST['fit']) && $_REQUEST['fit']=='0' )
  $fitMode = 0;

if ( isset($_REQUEST['scale']) )
  $defaultScale = validHtmlStr($_REQUEST['scale']);
else
  $defaultScale = 1;

$speeds=[0, 0.1, 0.25, 0.5, 0.75, 1.0, 1.5, 2, 3, 5, 10, 20, 50];

if (isset($_REQUEST['speed']) )
  $defaultSpeed = validHtmlStr($_REQUEST['speed']);
else
  $defaultSpeed = 1;

$speedIndex=5; // default to 1x
for ($i=0; $i<count($speeds); $i++) {
  if($speeds[$i]==$defaultSpeed) {
    $speedIndex=$i;
    break;
  }
}

if (isset($_REQUEST['current']) )
  $defaultCurrentTime=validHtmlStr($_REQUEST['current']);


$initialModeIsLive=1;
if(isset($_REQUEST['live']) && $_REQUEST['live']=='0' )
  $initialModeIsLive=0;

$initialDisplayInterval=1000;
if(isset($_REQUEST['displayinterval']))
  $initialDisplayInterval=validHtmlStr($_REQUEST['displayinterval']);

$eventsSql .= ' GROUP BY E.Id,E.Name,E.StartTime,E.Length,E.Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId';

if( isset($minTime) && isset($maxTime) ) {
  $minTimeSecs = strtotime($minTime);
  $maxTimeSecs = strtotime($maxTime);
  $eventsSql .= " HAVING CalcEndTimeSecs > '" . $minTimeSecs . "' AND StartTimeSecs < '" . $maxTimeSecs . "'";
  $frameSql .= " AND TimeStamp > '" . $minTime . "' AND TimeStamp < '" . $maxTime . "'";
}
$frameSql .= ' GROUP BY E.Id, E.MonitorId, F.TimeStamp, F.Delta ORDER BY E.MonitorId, F.TimeStamp ASC';

// This loads all monitors the user can see - even if we don't have data for one we still show all for switch to live.

$monitors = array();
$monitorsSql .= ' ORDER BY Sequence ASC';
$index=0;
foreach( dbFetchAll( $monitorsSql ) as $row ) {
  $monitors[$index] = new Monitor( $row );
  $index = $index + 1;
}

// These are zoom ranges per visible monitor

xhtmlHeaders(__FILE__, translate('MontageReview') );
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
        <a href="#" onclick="closeWindow();"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('MontageReview') ?></h2>
    </div>
    <div id="ScaleDiv">
        <label for="scaleslider"><?php echo translate('Scale')?></label>
        <input id="scaleslider" type="range" min="0.1" max="1.0" value="<?php echo $defaultScale ?>" step="0.10" onchange="setScale(this.value);" oninput="showScale(this.value);"/>
        <span id="scaleslideroutput"><?php echo number_format((float)$defaultScale,2,'.','')?> x</span>
    </div>
    <div id="SpeedDiv">
        <label for="speedslider"><?php echo translate('Speed') ?></label>
        <input id="speedslider" type="range" min="0" max="<?php echo count($speeds)-1?>" value="<?php echo $speedIndex ?>" step="1" onchange="setSpeed(this.value);" oninput="showSpeed(this.value);"/>
        <span id="speedslideroutput"><?php echo $speeds[$speedIndex] ?> fps</span>
    </div>
    <div style="display: inline-flex; border: 1px solid black; flex-flow: row wrap;">
        <button type="button" id="panleft"   onclick="panleft();"         >&lt; <?php echo translate('Pan') ?></button>
        <button type="button" id="zoomin"    onclick="zoomin();"           ><?php echo translate('In +') ?></button>
        <button type="button" id="zoomout"   onclick="zoomout();"          ><?php echo translate('Out -') ?></button>
        <button type="button" id="lasteight" onclick="lastEight();"        ><?php echo translate('8 Hour') ?></button>
        <button type="button" id="lasthour"  onclick="lastHour();"         ><?php echo translate('1 Hour') ?></button>
        <button type="button" id="allof"     onclick="allof();"            ><?php echo translate('All Events') ?></button>
        <button type="button" id="live"      onclick="setLive(1-liveMode);"><?php echo translate('Live') ?></button>
        <button type="button" id="fit"       onclick="setFit(1-fitMode);"  ><?php echo translate('Fit') ?></button>
        <button type="button" id="panright"  onclick="panright();"         ><?php echo translate('Pan') ?> &gt;</button>
    </div>
    <div id="timelinediv">
        <canvas id="timeline" onmousemove="mmove(event);" ontouchmove="tmove(event);" onmousedown="mdown(event);" onmouseup="mup(event);" onmouseout="mout(event);"></canvas>
        <span id="scrubleft"></span>
        <span id="scrubright"></span>
        <span id="scruboutput"></span>
    </div>
<div id="monitors">
<?php
// Monitor images - these had to be loaded after the monitors used were determined (after loading events)
foreach ($monitors as $m) {
  echo '<canvas width="' . $m->Width() * $defaultScale . 'px" height="'  . $m->Height() * $defaultScale . 'px" id="Monitor' . $m->Id() . '" style="border:3px solid ' . $m->WebColour() . '" onclick="clickMonitor(event,' . $m->Id() . ')">No Canvas Support!!</canvas>';
}
?>
</div>
<p id="fps">evaluating fps</p>

</div>
</body>
</html>
