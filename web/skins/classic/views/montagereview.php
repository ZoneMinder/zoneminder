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

if ( !canView('Events') ) {
  $view = 'error';
  return;
}

ob_start();
include('_monitor_filters.php');
$filter_bar = ob_get_contents();
ob_end_clean();

$filter = array();
if ( isset($_REQUEST['filter']) ) {
  $filter = $_REQUEST['filter'];

	# Try to guess min/max time from filter
	foreach ( $filter['Query'] as $term ) {
		if ( $term['attr'] == 'StartDateTime' ) {
			if ( $term['op'] == '<=' or $term['op'] == '<' ) {
				$maxTime = $term['val'];
			} else if ( $term['op'] == '>=' or $term['op'] == '>' ) {
				$minTime = $term['val'];
			}
		}
	}
} else {

  if ( isset($_REQUEST['minTime']) && isset($_REQUEST['maxTime']) && (count($displayMonitors) != 0) ) {
    $filter = array(
      'Query' => array(
        'terms' => array(
          array('attr' => 'StartDateTime', 'op' => '>=', 'val' => $_REQUEST['minTime'], 'obr' => '1'),
          array('attr' => 'StartDateTime', 'op' => '<=', 'val' => $_REQUEST['maxTime'], 'cnj' => 'and', 'cbr' => '1'),
        )
      ),
    );
    if ( count($selected_monitor_ids) ) {
      $filter['Query']['terms'][] = (array('attr' => 'MonitorId', 'op' => 'IN', 'val' => implode(',',$selected_monitor_ids), 'cnj' => 'and'));
    } else if ( ( $group_id != 0 || isset($_SESSION['ServerFilter']) || isset($_SESSION['StorageFilter']) || isset($_SESSION['StatusFilter']) ) ) {
      # this should be redundant
      for ( $i = 0; $i < count($displayMonitors); $i++ ) {
        if ( $i == '0' ) {
          $filter['Query']['terms'][] = array('attr' => 'MonitorId', 'op' => '=', 'val' => $displayMonitors[$i]['Id'], 'cnj' => 'and', 'obr' => '1');
        } else if ( $i == (count($displayMonitors)-1) ) {
          $filter['Query']['terms'][] = array('attr' => 'MonitorId', 'op' => '=', 'val' => $displayMonitors[$i]['Id'], 'cnj' => 'or', 'cbr' => '1');
        } else {
          $filter['Query']['terms'][] = array('attr' => 'MonitorId', 'op' => '=', 'val' => $displayMonitors[$i]['Id'], 'cnj' => 'or');
        }
      }
    }
  } # end if REQUEST[Filter]
}
if ( count($filter) ) {
  parseFilter($filter);
  # This is to enable the download button
  zm_session_start();
  $_SESSION['montageReviewFilter'] = $filter;
  session_write_close();
}

// Note that this finds incomplete events as well, and any frame records written, but still cannot "see" to the end frame
// if the bulk record has not been written - to be able to include more current frames reduce bulk frame sizes (event size can be large)
// Note we round up just a bit on the end time as otherwise you get gaps, like 59.78 to 00 in the next second, which can give blank frames when moved through slowly.

$eventsSql = 'SELECT
    E.Id,E.Name,E.StorageId,
    E.StartTime AS StartTime,UNIX_TIMESTAMP(E.StartTime) AS StartTimeSecs,
    CASE WHEN E.EndTime IS NULL THEN (SELECT NOW()) ELSE E.EndTime END AS EndTime,
    UNIX_TIMESTAMP(EndTime) AS EndTimeSecs,
    E.Length, E.Frames, E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId
  FROM Events AS E
  WHERE 1 > 0 
';

//    select E.Id,E.Name,UNIX_TIMESTAMP(E.StartTime) as StartTimeSecs,UNIX_TIMESTAMP(max(DATE_ADD(E.StartTime, Interval Delta+0.5 Second))) as CalcEndTimeSecs, E.Length,max(F.FrameId) as Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId
//    from Events as E
//    inner join Monitors as M on (E.MonitorId = M.Id)
//    inner join Frames F on F.EventId=E.Id
//    where not isnull(E.Frames) and not isnull(StartTime) ";

// Note that the delta value seems more accurate than the time stamp for some reason.
$framesSql = '
    SELECT Id, FrameId, EventId, TimeStamp, UNIX_TIMESTAMP(TimeStamp) AS TimeStampSecs, Score, Delta, Type
    FROM Frames 
    WHERE EventId IN (SELECT E.Id FROM Events AS E WHERE 1>0
';

// This program only calls itself with the time range involved -- it does all monitors (the user can see, in the called group) all the time

$monitor_ids_sql = '';
if ( !empty($user['MonitorIds']) ) {
  $eventsSql .= ' AND E.MonitorId IN ('.$user['MonitorIds'].')';
  $framesSql .= ' AND E.MonitorId IN ('.$user['MonitorIds'].')';
}
if ( count($selected_monitor_ids) ) {
  $monitor_ids_sql = ' IN (' . implode(',',$selected_monitor_ids).')';
  $eventsSql .= ' AND E.MonitorId '.$monitor_ids_sql;
  $framesSql .= ' AND E.MonitorId '.$monitor_ids_sql;
}
if ( isset($_REQUEST['archive_status']) ) {
  $_SESSION['archive_status'] = $_REQUEST['archive_status'];
}
if ( isset($_SESSION['archive_status']) ) {
  if ( $_SESSION['archive_status'] == 'Archived' ) {
    $eventsSql .= ' AND E.Archived=1';
    $framesSql .= ' AND E.Archived=1';
  } else if ( $_SESSION['archive_status'] == 'Unarchived' ) {
    $eventsSql .= ' AND E.Archived=0';
    $framesSql .= ' AND E.Archived=0';
  }
}

// Parse input parameters -- note for future, validate/clean up better in case we don't get called from self.
// Live overrides all the min/max stuff but it is still processed

// The default (nothing at all specified) is for 1 hour so we do not read the whole database

if ( !isset($_REQUEST['minTime']) && !isset($_REQUEST['maxTime']) ) {
  $time = time();
  $maxTime = strftime('%FT%T',$time);
  $minTime = strftime('%FT%T',$time - 3600);
}
if ( isset($_REQUEST['minTime']) )
  $minTime = validHtmlStr($_REQUEST['minTime']);

if ( isset($_REQUEST['maxTime']) )
  $maxTime = validHtmlStr($_REQUEST['maxTime']);

// AS a special case a "all" is passed in as an extreme interval - if so, clear them here and let the database query find them

if ( (strtotime($maxTime) - strtotime($minTime))/(365*24*3600) > 30 ) {
  // test years
  $minTime = null;
  $maxTime = null;
}

$fitMode = 1;
if ( isset($_REQUEST['fit']) && ($_REQUEST['fit'] == '0') )
  $fitMode = 0;

if ( isset($_REQUEST['scale']) )
  $defaultScale = validHtmlStr($_REQUEST['scale']);
else
  $defaultScale = 1;

$speeds = [0, 0.1, 0.25, 0.5, 0.75, 1.0, 1.5, 2, 3, 5, 10, 20, 50];

if ( isset($_REQUEST['speed']) )
  $defaultSpeed = validHtmlStr($_REQUEST['speed']);
else
  $defaultSpeed = 1;

$speedIndex = 5; // default to 1x
for ( $i = 0; $i < count($speeds); $i++ ) {
  if ( $speeds[$i] == $defaultSpeed ) {
    $speedIndex = $i;
    break;
  }
}

if ( isset($_REQUEST['current']) )
  $defaultCurrentTime = validHtmlStr($_REQUEST['current']);

$liveMode = 1; // default to live
if ( isset($_REQUEST['live']) && ($_REQUEST['live'] == '0') )
  $liveMode = 0;

$initialDisplayInterval = 1000;
if ( isset($_REQUEST['displayinterval']) )
  $initialDisplayInterval = validHtmlStr($_REQUEST['displayinterval']);

#$eventsSql .= ' GROUP BY E.Id,E.Name,E.StartTime,E.Length,E.Frames,E.MaxScore,E.Cause,E.Notes,E.Archived,E.MonitorId';

$minTimeSecs = $maxTimeSecs = 0;
if ( isset($minTime) && isset($maxTime) ) {
  $minTimeSecs = strtotime($minTime);
  $maxTimeSecs = strtotime($maxTime);
  $eventsSql .= " AND EndTime > '" . $minTime . "' AND StartTime < '" . $maxTime . "'";
  $framesSql .= " AND EndTime > '" . $minTime . "' AND StartTime < '" . $maxTime . "'";
  $framesSql .= ") AND TimeStamp > '" . $minTime . "' AND TimeStamp < '" . $maxTime . "'";
} else {
  $framesSql .= ')';
}
#$framesSql .= ' GROUP BY E.Id, E.MonitorId, F.TimeStamp, F.Delta ORDER BY E.MonitorId, F.TimeStamp ASC';
#$framesSql .= ' GROUP BY E.Id, E.MonitorId, F.TimeStamp, F.Delta ORDER BY E.MonitorId, F.TimeStamp ASC';
$eventsSql .= ' ORDER BY E.Id ASC';
// DESC is intentional. We process them in reverse order so that we can point each frame to the next one in time.
$framesSql .= ' ORDER BY Id DESC';

$monitors = array();
foreach( $displayMonitors as $row ) {
  if ( $row['Function'] == 'None' || $row['Type'] == 'WebSite' )
    continue;
  $Monitor = new ZM\Monitor($row);
  $monitors[] = $Monitor;
}

// These are zoom ranges per visible monitor

xhtmlHeaders(__FILE__, translate('MontageReview') );
getBodyTopHTML();
?>
<div id="page">
  <?php echo getNavBarHTML() ?>
  <form id="montagereview_form" action="?" method="get">
    <input type="hidden" name="view" value="montagereview"/>
    <div id="header">&nbsp;&nbsp;
      <a href="#"><span id="hdrbutton" class="glyphicon glyphicon-menu-up pull-right"></span></a>
      <div id="flipMontageHeader">
        <?php echo $filter_bar ?>
        <div id="DateTimeDiv">
          <input type="text" name="minTime" id="minTime" value="<?php echo preg_replace('/T/', ' ', $minTime ) ?>"/> to 
          <input type="text" name="maxTime" id="maxTime" value="<?php echo preg_replace('/T/', ' ', $maxTime ) ?>"/>
        </div>
        <div id="ScaleDiv">
          <label for="scaleslider"><?php echo translate('Scale')?></label>
          <input id="scaleslider" type="range" min="0.1" max="1.0" value="<?php echo $defaultScale ?>" step="0.10"/>
          <span id="scaleslideroutput"><?php echo number_format((float)$defaultScale,2,'.','')?> x</span>
        </div>
        <div id="SpeedDiv">
          <label for="speedslider"><?php echo translate('Speed') ?></label>
          <input id="speedslider" type="range" min="0" max="<?php echo count($speeds)-1?>" value="<?php echo $speedIndex ?>" step="1"/>
          <span id="speedslideroutput"><?php echo $speeds[$speedIndex] ?> fps</span>
        </div>
        <div id="ButtonsDiv">
          <button type="button" id="panleft"   data-on-click="click_panleft"    >&lt; <?php echo translate('Pan') ?></button>
          <button type="button" id="zoomin"    data-on-click="click_zoomin"     ><?php echo translate('In +') ?></button>
          <button type="button" id="zoomout"   data-on-click="click_zoomout"    ><?php echo translate('Out -') ?></button>
          <button type="button" id="lasteight" data-on-click="click_lastEight"  ><?php echo translate('8 Hour') ?></button>
          <button type="button" id="lasthour"  data-on-click="click_lastHour"   ><?php echo translate('1 Hour') ?></button>
          <button type="button" id="allof"     data-on-click="click_all_events" ><?php echo translate('All Events') ?></button>
          <button type="button" id="liveButton"><?php echo translate('Live') ?></button>
          <button type="button" id="fit"       ><?php echo translate('Fit') ?></button>
          <button type="button" id="panright"  data-on-click="click_panright"   ><?php echo translate('Pan') ?> &gt;</button>
<?php
  if ( (!$liveMode) and (count($displayMonitors) != 0) ) {
?>
          <button type="button" id="downloadVideo" data-on-click="click_download"><?php echo translate('Download Video') ?></button>
<?php
  }
?>
        </div>
<?php if ( !$liveMode ) { ?>
        <div id="eventfilterdiv" class="input-group">
          <label>Archive Status 
  <?php echo htmlSelect(
    'archive_status',
    array(
      '' => translate('All'),
      'Archived' => translate('Archived'),
      'Unarchived' => translate('UnArchived'),
    ),
    ( isset($_SESSION['archive_status']) ? $_SESSION['archive_status'] : '')
  ); ?>
          </label>
        </div>
<?php } // end if !live ?>
        <div id="timelinediv">
          <canvas id="timeline"></canvas>
          <span id="scrubleft"></span>
          <span id="scrubright"></span>
          <span id="scruboutput"></span>
        </div>
      </div><!--flipMontageHeader-->
      <input type="hidden" name="fit" value="<?php echo $fitMode ?>"/>
      <input type="hidden" name="live" value="<?php echo $liveMode ?>"/>
    </div><!--header-->
  </form>
  <div id="monitors">
<?php
  // Monitor images - these had to be loaded after the monitors used were determined (after loading events)
  foreach ( $monitors as $m ) {
    echo '<canvas title="'.$m->Id().' '.validHtmlStr($m->Name()).'" width="'.($m->Width() * $defaultScale).'" height="'.($m->Height() * $defaultScale).'" id="Monitor'.$m->Id().'" style="border:1px solid '.$m->WebColour().'" monitor_id="'.$m->Id().'">No Canvas Support!!</canvas>
';
  }
?>
  </div>
  <p id="fps">evaluating fps</p>
</div>
<?php xhtmlFooter() ?>
