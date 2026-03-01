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
//                            if not specified, but current is, then should center 1 hour on current
//
//        &current = string format of time, where the slider is positioned first in history mode (normally only used in reloads, default = half scale)
//                   also used when jumping from event view to montagereview
//
//        &speed = one of the valid speeds below (see $speeds in php section, default = 1.0)
//
//        &scale = image size scale (.1 to 1.0, or 1.1 = fit, default = fit)
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

require_once('includes/Filter.php');
include('_monitor_filters.php');
$resultMonitorFilters = buildMonitorsFilters();
$filterbar = $resultMonitorFilters['filterBar'];
$displayMonitors = $resultMonitorFilters['displayMonitors'];
$selected_monitor_ids = $resultMonitorFilters['selected_monitor_ids'];

$preference = ZM\User_Preference::find_one([
    'UserId'=>$user->Id(),
    'Name'=>'MontageSort'.(isset($_SESSION['GroupId']) ? is_array($_SESSION['GroupId']) ? implode(',', $_SESSION['GroupId']) : $_SESSION['GroupId']: '')
]);
if ($preference) {
  $monitors_by_id = array_to_hash_by_key('Id', $displayMonitors);
  $sorted_monitors = [];
  foreach (explode(',', $preference->Value()) as $id) {
    if (isset($monitors_by_id[$id])) {
      $sorted_monitors[] = $monitors_by_id[$id];
    } else {
      ZM\Debug("Ordered monitor not found in monitorsById $id");
    }
  }
  if (count($sorted_monitors)) $displayMonitors = $sorted_monitors;
}

$liveMode = 0; // default to live
if ( isset($_REQUEST['live']) && ($_REQUEST['live'] != '0') )
  $liveMode = 1;

// Parse input parameters -- note for future, validate/clean up better in case we don't get called from self.
// Live overrides all the min/max stuff but it is still processed

// The default (nothing at all specified) is for 1 hour so we do not read the whole database

if (isset($_REQUEST['current'])) {
  $defaultCurrentTime = validHtmlStr($_REQUEST['current']);
  $defaultCurrentTimeSecs = strtotime($defaultCurrentTime);
}

if ( !isset($_REQUEST['minTime']) && !isset($_REQUEST['maxTime']) ) {
  if (isset($defaultCurrentTimeSecs)) {
    $minTime = date('Y-m-d H:i:s', $defaultCurrentTimeSecs - 1800);
    $maxTime = date('Y-m-d H:i:s', $defaultCurrentTimeSecs + 1800);
  } else {
    $time = time();
    $maxTime = date('Y-m-d H:i:s', $time);
    $minTime = date('Y-m-d H:i:s', $time - 3600);
  }
} else {
  if (isset($_REQUEST['minTime']))
    $minTime = validHtmlStr($_REQUEST['minTime']);

  if (isset($_REQUEST['maxTime']))
    $maxTime = validHtmlStr($_REQUEST['maxTime']);
}

// AS a special case a "all" is passed in as an extreme interval - if so, clear them here and let the database query find them

if ( (strtotime($maxTime) - strtotime($minTime))/(365*24*3600) > 30 ) {
  // test years
  $minTime = null;
  $maxTime = null;
}

$filter = null;
if (isset($_REQUEST['filter'])) {
  $filter = ZM\Filter::parse($_REQUEST['filter']);
  $terms = $filter->terms();

	# Try to guess min/max time from filter
	foreach ($terms as &$term) {
    if ($term['attr'] == 'Notes') {
      $term['cookie'] = 'Notes';
      if (empty($term['val']) and isset($_COOKIE['Notes'])) $term['val'] = $_COOKIE['Notes'];
    } else if ($term['attr'] == 'StartDateTime') {
			if ($term['op'] == '<=' or $term['op'] == '<') {
				$maxTime = $term['val'];
			} else if ( $term['op'] == '>=' or $term['op'] == '>' ) {
				$minTime = $term['val'];
			}
    } else if ($term['attr'] == 'DateTime') {
			if ($term['op'] == '<=' or $term['op'] == '<') {
				$maxTime = $term['val'];
			} else if ( $term['op'] == '>=' or $term['op'] == '>' ) {
				$minTime = $term['val'];
			}
    }
  } # end foreach term
  $filter->terms($terms);
} else {
  $filter = new ZM\Filter();
  if (isset($_REQUEST['minTime']) && isset($_REQUEST['maxTime']) && (count($displayMonitors) != 0)) {
    $filter->addTerm(array('attr' => 'DateTime', 'op' => '>=', 'val' => $_REQUEST['minTime'], 'obr' => '1', 'cookie'=>htmlspecialchars('DateTime<=')));
    $filter->addTerm(array('attr' => 'DateTime', 'op' => '<=', 'val' => $_REQUEST['maxTime'], 'cnj' => 'and', 'cbr' => '1', 'cookie'=>htmlspecialchars('DateTime<=')));
    if (count($selected_monitor_ids)) {
      $filter->addTerm(array('attr' => 'Monitor', 'op' => 'IN', 'val' => implode(',',$selected_monitor_ids), 'cnj' => 'and'));
    } else if ( isset($_SESSION['GroupId']) || isset($_SESSION['ServerFilter']) || isset($_SESSION['StorageFilter']) || isset($_SESSION['StatusFilter']) ) {
      # this should be redundant
      for ( $i = 0; $i < count($displayMonitors); $i++ ) {
        if ( $i == '0' ) {
          $filter->addTerm(array('attr' => 'MonitorId', 'op' => '=', 'val' => $displayMonitors[$i]['Id'], 'cnj' => 'and', 'obr' => '1'));
        } else if ( $i == (count($displayMonitors)-1) ) {
          $filter->addTerm(array('attr' => 'MonitorId', 'op' => '=', 'val' => $displayMonitors[$i]['Id'], 'cnj' => 'or', 'cbr' => '1'));
        } else {
          $filter->addTerm(array('attr' => 'MonitorId', 'op' => '=', 'val' => $displayMonitors[$i]['Id'], 'cnj' => 'or'));
        }
      }
    }
  } # end if REQUEST[Filter]
}
if (!$liveMode) {
  if (!$filter->has_term('Archived')) {
    $filter->addTerm(array('attr' => 'Archived', 'op' => '=', 'val' => '', 'cnj' => 'and', 'cookie'=>'Archived'));
  }
  if (!$filter->has_term('DateTime', '>=')) {
    $filter->addTerm(array('attr' => 'DateTime', 'op' => '>=', 'val' => $minTime, 'cnj' => 'and', 'cookie'=>htmlspecialchars('DateTime>=')));
  }
  if (!$filter->has_term('DateTime', '<=')) {
    $filter->addTerm(array('attr' => 'DateTime', 'op' => '<=', 'val' => $maxTime, 'cnj' => 'and', 'cookie'=>htmlspecialchars('DateTime<=')));
  }
  if (!$filter->has_term('Tags')) {
    $filter->addTerm(array('attr' => 'Tags', 'op' => '=',
      'val' => (isset($_COOKIE['eventsTags']) ? $_COOKIE['eventsTags'] : ''),
      'cnj' => 'and', 'cookie'=>'eventsTags'));
  }
  if (!$filter->has_term('Notes')) {
    $filter->addTerm(array('cnj'=>'and', 'attr'=>'Notes', 'op'=> 'LIKE', 'val'=>'', 'cookie'=>'eventsNotes'));
  }
}
if (count($filter->terms()) ) {
  # This is to enable the download button
  zm_session_start();
  $_SESSION['montageReviewFilter'] = $filter;
  session_write_close();
}

// Note that this finds incomplete events as well, and any frame records written, but still cannot "see" to the end frame
// if the bulk record has not been written - to be able to include more current frames reduce bulk frame sizes (event size can be large)
// Note we round up just a bit on the end time as otherwise you get gaps, like 59.78 to 00 in the next second, which can give blank frames when moved through slowly.

$eventsSql = 'SELECT
  E.*, E.StartDateTime AS StartDateTime,UNIX_TIMESTAMP(E.StartDateTime) AS StartTimeSecs,
    CASE WHEN E.EndDateTime IS NULL THEN (SELECT NOW()) ELSE E.EndDateTime END AS EndDateTime,
    CASE WHEN E.EndDateTime IS NULL THEN (SELECT UNIX_TIMESTAMP(NOW())) ELSE UNIX_TIMESTAMP(EndDateTime) END AS EndTimeSecs,
    M.Name AS MonitorName,M.DefaultScale FROM Monitors AS M INNER JOIN Events AS E on (M.Id = E.MonitorId)
  WHERE 1 > 0 
';

// This program only calls itself with the time range involved -- it does all monitors (the user can see, in the called group) all the time

$monitor_ids_sql = '';
if (count($user->unviewableMonitorIds())) {
  $eventsSql .= ' AND E.MonitorId IN ('.implode(',', $user->viewableMonitorIds()).')';
}
if ( count($selected_monitor_ids) ) {
  $monitor_ids_sql = ' IN (' . implode(',',$selected_monitor_ids).')';
  $eventsSql .= ' AND E.MonitorId '.$monitor_ids_sql;
}

$fitMode = 1;
if (isset($_REQUEST['fit']))
  $fitMode = validCardinal($_REQUEST['fit']);

if (isset($_REQUEST['scale'])) {
  $defaultScale = validCardinal($_REQUEST['scale']);
  if ($defaultScale > 1.1) $defaultScale = 1.0;
} else {
  $defaultScale = 1;
}

$speeds = [0, 0.1, 0.25, 0.5, 0.75, 1.0, 1.5, 2, 3, 5, 10, 20, 50];

if (isset($_REQUEST['speed'])) {
  $defaultSpeed = validNum($_REQUEST['speed']);
} else if (isset($_COOKIE['speed'])) {
  $defaultSpeed = validNum($_COOKIE['speed']);
} else {
  $defaultSpeed = 1;
}

$speedIndex = 5; // default to 1x
for ( $i = 0; $i < count($speeds); $i++ ) {
  if ( $speeds[$i] == $defaultSpeed ) {
    $speedIndex = $i;
    break;
  }
}

$initialDisplayInterval = 100;
if (isset($_REQUEST['displayinterval']))
  $initialDisplayInterval = validCardinal($_REQUEST['displayinterval']);

$minTimeSecs = $maxTimeSecs = 0;
if (isset($minTime) && isset($maxTime)) {
  if ($minTime >= $maxTime) {
    if (!isset($error_message)) $error_message = '';
    $error_message .= 'Invalid minTime and maxTime specified.<br/>';
    if ($minTime > $maxTime) {
      $temp = $minTime;
      $maxTime = $minTime;
      $minTime = $temp;
    }
  }
  $minTimeSecs = strtotime($minTime);
  $maxTimeSecs = strtotime($maxTime);
}
$eventsSql .= ' AND '.$filter->sql();
$eventsSql .= ' ORDER BY E.StartDateTime ASC';

$monitors = array();
foreach ($displayMonitors as $row) {
  if ($row['Type'] == 'WebSite')
    continue;
  $monitors[] = new ZM\Monitor($row);
}

xhtmlHeaders(__FILE__, translate('MontageReview') );
getBodyTopHTML();
?>
<div id="page">
  <?php echo getNavBarHTML() ?>
  <div id="content">
  <form id="montagereview_form" action="?" method="get">
    <input type="hidden" name="view" value="montagereview"/>
    <div id="header">
<?php
$html = '<a class="flip" href="#"
         data-flip-control-object="#mfbpanel"
         data-flip-control-run-after-func="applyChosen drawGraph"
         data-flip-control-run-after-complet-func="changeScale">
           <i id="mfbflip" class="material-icons md-18" data-icon-visible="filter_alt_off" data-icon-hidden="filter_alt"></i>
         </a>'.PHP_EOL;
$html .= '<div id="mfbpanel" class="hidden-shift container-fluid">'.PHP_EOL;
echo $html;
echo $filterbar;
if (count($filter->terms())) {
  echo $filter->simple_widget();
}
?>

<!--
        <div id="DateTimeDiv">
          <input type="text" name="minTime" id="minTime" value="<?php echo preg_replace('/T/', ' ', $minTime) ?>"/> to 
          <input type="text" name="maxTime" id="maxTime" value="<?php echo preg_replace('/T/', ' ', $maxTime) ?>"/>
        </div>
-->
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
          <button type="button" id="last24" data-on-click="click_last24"     ><?php echo translate('24 Hour') ?></button>
          <button type="button" id="lasteight" data-on-click="click_lastEight"  ><?php echo translate('8 Hour') ?></button>
          <button type="button" id="lasthour"  data-on-click="click_lastHour"   ><?php echo translate('1 Hour') ?></button>
          <button type="button" id="allof"     data-on-click="click_all_events" ><?php echo translate('All Events') ?></button>
          <button type="button" id="liveButton"><?php echo translate('Live') ?></button>
          <button type="button" id="fit"       ><?php echo translate('Fit') ?></button>
          <button type="button" id="panright"  data-on-click="click_panright"   ><?php echo translate('Pan') ?> &gt;</button>
<?php
  if ($liveMode) {
    if (defined('ZM_FEATURES_SNAPSHOTS') and ZM_FEATURES_SNAPSHOTS) { ?>
          <button type="button" name="snapshotBtn" data-on-click-this="takeSnapshot">
            <i class="material-icons md-18">camera_enhance</i>
            &nbsp;<?php echo translate('Snapshot') ?>
          </button>
<?php
    }
  } else if (count($displayMonitors) != 0) {
?>
          <button type="button" id="downloadVideo" data-on-click="click_download"><?php echo translate('Download Video') ?></button>
<?php } // end if !live ?>
<button type="button" id="collapse" data-flip-control-object="#timelinediv" data-flip-control-run-after-func="drawGraph" title="<?php echo translate('Toggle timeline visibility');?>"> <!-- OR run redrawScreen? -->
            <i class="material-icons" data-icon-visible="history_toggle_off" data-icon-hidden="schedule"></i>
          </button>
        </div>
        <div id="timelinediv" class="hidden-shift">
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
  </div><!--content-->
</div><!--page-->
<script src="<?php echo cache_bust('skins/classic/js/export.js') ?>"></script>
<script src="<?php echo cache_bust('skins/classic/js/montage_common.js') ?>"></script>
<script src="<?php echo cache_bust('js/EventStream.js') ?>"></script>
<?php xhtmlFooter() ?>
