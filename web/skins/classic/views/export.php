<?php
//
// ZoneMinder web export view file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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
global $sortQuery;

if ( !canView('Events') ) {
  $view = 'error';
  return;
}

if ( isset($_SESSION['export']) ) {
  if ( isset($_SESSION['export']['detail']) )
    $_REQUEST['exportDetail'] = $_SESSION['export']['detail'];
  if ( isset($_SESSION['export']['frames']) )
    $_REQUEST['exportFrames'] = $_SESSION['export']['frames'];
  if ( isset($_SESSION['export']['images']) )
    $_REQUEST['exportImages'] = $_SESSION['export']['images'];
  if ( isset($_SESSION['export']['video']) )
    $_REQUEST['exportVideo'] = $_SESSION['export']['video'];
  if ( isset($_SESSION['export']['misc']) )
    $_REQUEST['exportMisc'] = $_SESSION['export']['misc'];
  if ( isset($_SESSION['export']['format']) )
    $_REQUEST['exportFormat'] = $_SESSION['export']['format'];
  if ( isset($_SESSION['export']['compress']) )
    $_REQUEST['exportCompress'] = $_SESSION['export']['compress'];
} else {
  $_REQUEST['exportDetail'] =
  $_REQUEST['exportFrames'] =
  $_REQUEST['exportImages'] =
  $_REQUEST['exportVideo'] =
  $_REQUEST['exportMisc'] = 1;
  $_REQUEST['exportCompress'] = 0;
}

if (isset($_REQUEST['exportFormat'])) {
  if (!in_array($_REQUEST['exportFormat'], array('zip', 'tar'))) {
    ZM\Error('Invalid exportFormat');
    return;
  }
}

$focusWindow = true;
$connkey = isset($_REQUEST['connkey']) ? validInt($_REQUEST['connkey']) : generateConnKey();

xhtmlHeaders(__FILE__, translate('Export'));
?>
<body>
  <div id="page">
    <?php echo getNavBarHTML() ?>
      <div class="w-100 py-1">
        <div class="float-left pl-3">
          <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
          <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
        </div>
        <div class="w-100 pt-2">
          <h2><?php echo translate('ExportOptions') ?></h2>
        </div>
      </div>    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="?view=export">
        <input type="hidden" name="connkey" value="<?php echo $connkey; ?>"/>
<?php

$eventsSql = 'SELECT E.*,M.Name AS MonitorName FROM Monitors AS M INNER JOIN Events AS E on (M.Id = E.MonitorId) WHERE';
$eventsValues = array();
$filterQuery = '';
$sortColumn = '';
$sortOrder = '';
$limitQuery = '';

if ( $user['MonitorIds'] ) {
  $user_monitor_ids = ' M.Id in ('.$user['MonitorIds'].')';
  $eventsSql .= $user_monitor_ids;
} else if ( !isset($_REQUEST['filter']) ) {
  $eventsSql .= ' 1';
}

if ( isset($_REQUEST['eid']) and $_REQUEST['eid'] ) {
  $eventsSql .= ' AND E.Id=?';
  $eventsValues[] = $_REQUEST['eid'];
} else if ( isset($_REQUEST['eids']) and count($_REQUEST['eids']) > 0 ) {
  $eventsSql .= ' AND E.Id IN ('.implode(',', array_map(function(){return '?';}, $_REQUEST['eids'])). ')';
  $eventsValues += $_REQUEST['eids'];
} else if ( isset($_REQUEST['filter']) ) {
  parseSort();
  $filter = ZM\Filter::parse($_REQUEST['filter']);
  $filterQuery = $filter->querystring();

  if ( $filter->sql() ) {
    $eventsSql .= $filter->sql();
  }
  $eventsSql .= " ORDER BY $sortColumn $sortOrder";
  if ( isset($_REQUEST['filter']['Query']['limit']) and validInt($_REQUEST['filter']['Query']['limit']))
    $eventsSql .= ' LIMIT '.validInt($_REQUEST['filter']['Query']['limit']);
} # end if filter

$results = dbQuery($eventsSql, $eventsValues);

echo 'Export the following ' .$results->rowCount() . ' events:<br/>';
$disk_space_total = 0;
?>
        <table id="contentTable" class="major">
          <thead>
            <tr>
              <th class="colId"><a href="<?php echo sortHeader('Id') ?>"><?php echo translate('Id') ?><?php echo sortTag('Id') ?></a></th>
              <th class="colName"><a href="<?php echo sortHeader('Name') ?>"><?php echo translate('Name') ?><?php echo sortTag('Name') ?></a></th>
              <th class="colMonitor"><a href="<?php echo sortHeader('MonitorName') ?>"><?php echo translate('Monitor') ?><?php echo sortTag('MonitorName') ?></a></th>
              <th class="colCause"><a href="<?php echo sortHeader('Cause') ?>"><?php echo translate('Cause') ?><?php echo sortTag('Cause') ?></a></th>
              <th class="colTime"><a href="<?php echo sortHeader('StartTime') ?>"><?php echo translate('Time') ?><?php echo sortTag('StartTime') ?></a></th>
              <th class="colDuration"><a href="<?php echo sortHeader('Length') ?>"><?php echo translate('Duration') ?><?php echo sortTag('Length') ?></a></th>
              <th class="colFrames"><a href="<?php echo sortHeader('Frames') ?>"><?php echo translate('Frames') ?><?php echo sortTag('Frames') ?></a></th>
              <th class="colAlarmFrames"><a href="<?php echo sortHeader('AlarmFrames') ?>"><?php echo translate('AlarmBrFrames') ?><?php echo sortTag('AlarmFrames') ?></a></th>
              <th class="colTotScore"><a href="<?php echo sortHeader('TotScore') ?>"><?php echo translate('TotalBrScore') ?><?php echo sortTag('TotScore') ?></a></th>
              <th class="colAvgScore"><a href="<?php echo sortHeader('AvgScore') ?>"><?php echo translate('AvgBrScore') ?><?php echo sortTag('AvgScore') ?></a></th>
              <th class="colMaxScore"><a href="<?php echo sortHeader('MaxScore') ?>"><?php echo translate('MaxBrScore') ?><?php echo sortTag('MaxScore') ?></a></th>
<?php
    if ( ZM_WEB_EVENT_DISK_SPACE ) {
?>
              <th class="colDiskSpace"><a href="<?php echo sortHeader('DiskSpace') ?>"><?php echo translate('DiskSpace') ?><?php echo sortTag('DiskSpace') ?></a></th>
<?php
    }
?>
        </tr>
      </thead>
      <tbody>
<?php
$event_count = 0;
while ( $event_row = dbFetchNext($results) ) {
  $event = new ZM\Event($event_row);
  $scale = max(reScale(SCALE_BASE, $event->Monitor()->DefaultScale(), ZM_WEB_DEFAULT_SCALE), SCALE_BASE);
  $event_link = '?view=event&amp;eid='.$event->Id().$filterQuery.$sortQuery.'&amp;page=1';
  global $dateTimeFormatter;
?>
          <tr<?php echo $event->Archived() ? ' class="archived"' : '' ?>>
              <td class="colId">
                <input type="hidden" name="eids[]" value="<?php echo $event->Id()?>"/>
                <a href="<?php echo $event_link ?>"><?php echo $event->Id().($event->Archived()?'*':'') ?></a>
              </td>
              <td class="colName"><a href="<?php echo $event_link ?>"><?php echo validHtmlStr($event->Name()).($event->Archived()?'*':'') ?></a></td>
              <td class="colMonitorName"><?php echo makeLink('?view=monitor&amp;mid='.$event->MonitorId(), $event->MonitorName(), canEdit('Monitors')) ?></td>
              <td class="colCause"><?php echo makeLink($event_link, validHtmlStr($event->Cause()), canView('Events'), 'title="' .htmlspecialchars($event->Notes()). '" class="eDetailLink" data-eid="'.$event->Id().'"') ?></td>
              <td class="colTime"><?php echo $dateTimeFormatter->format(strtotime($event->StartDateTime())) .
( $event->EndDateTime() ? ' until ' . $dateTimeFormatter->format(strtotime($event->EndDateTime())) : '' ) ?></td>
              <td class="colDuration"><?php echo gmdate('H:i:s', intval($event->Length())) ?></td>
              <td class="colFrames"><?php echo makeLink('?view=frames&amp;eid='.$event->Id(), $event->Frames()) ?></td>
              <td class="colAlarmFrames"><?php echo makeLink('?view=frames&amp;eid='.$event->Id(), $event->AlarmFrames()) ?></td>
              <td class="colTotScore"><?php echo $event->TotScore() ?></td>
              <td class="colAvgScore"><?php echo $event->AvgScore() ?></td>
              <td class="colMaxScore"><?php echo $event->MaxScore() ?></td>
<?php
  if ( ZM_WEB_EVENT_DISK_SPACE ) {
    $disk_space_total += $event->DiskSpace();
		$event_count += 1;
    echo '<td class="colDiskSpace">'.human_filesize($event->DiskSpace()).'</td>';
  }
  unset($event);
  echo PHP_EOL.'</tr>'.PHP_EOL;
} # end foreach event
?>
        </tbody>
				<tfoot>
          <tr>
            <td colspan="11"><?php echo $event_count ?> events</td>
<?php
  if ( ZM_WEB_EVENT_DISK_SPACE ) {
    echo '<td class="colDiskSpace">'.human_filesize($disk_space_total).'</td>'.PHP_EOL;
  }
?>
          </tr>
				</tfoot>
      </table>
<div class="container-fluid export_options">
  <div class="row">
    <div class="col-md-3">
      <div class="form-group">
        <label for="exportDetail"><?php echo translate('ExportDetails') ?>
          <input type="checkbox" name="exportDetail" value="1"<?php if ( !empty($_REQUEST['exportDetail']) ) { ?> checked="checked"<?php } ?> data-on-click="configureExportButton"/>
        </label>
      </div>
      <div class="form-group">
        <label for="exportFrames"><?php echo translate('ExportFrames') ?>
          <input type="checkbox" name="exportFrames" value="1"<?php if ( !empty($_REQUEST['exportFrames']) ) { ?> checked="checked"<?php } ?> data-on-click="configureExportButton"/>
        </label>
      </div>
    </div>
    <div class="col-md-3">
      <div class="form-group">
        <label for="exportImages"><?php echo translate('ExportImageFiles') ?>
          <input type="checkbox" name="exportImages" value="1"<?php if ( !empty($_REQUEST['exportImages']) ) { ?> checked="checked"<?php } ?> data-on-click="configureExportButton"/>

        </label>
       </div>
       <div class="form-group">
         <label for="exportVideo"><?php echo translate('ExportVideoFiles') ?>
         <input type="checkbox" name="exportVideo" value="1"<?php if ( !empty($_REQUEST['exportVideo']) ) { ?> checked="checked"<?php } ?> data-on-click="configureExportButton"/>
         </label>
       </div>
    </div>
    <div class="col-md-3">
      <div class="form-group">
        <label for="exportMisc"><?php echo translate('ExportMiscFiles') ?>
        <input type="checkbox" name="exportMisc" value="1"<?php if ( !empty($_REQUEST['exportMisc']) ) { ?> checked="checked"<?php } ?> data-on-click="configureExportButton"/>
        </label>
      </div>
    </div>
    <div class="col-md-3">
      <div class="form-group">
        <label for="exportFormat"><?php echo translate('ExportFormat') ?>
          <?php echo html_radio('exportFormat',
            array('tar'=>translate('ExportFormatTar'), 'zip' => translate('ExportFormatZip')),
            (isset($_REQUEST['exportFormat'])?$_REQUEST['exportFormat']:'zip'), # default to zip
            array(),
            array('data-on-click'=>'configureExportButton')
          ); ?>
        </label>
      </div>
      <div class="form-group">
        <label for="exportCompress"><?php echo translate('ExportCompress') ?>
          <?php echo html_radio('exportCompress',
            array('1'=>translate('Yes'), '0' => translate('No')),
            (isset($_REQUEST['exportCompress'])?$_REQUEST['exportCompress']:'0'), # default to no
            array(),
            array('data-on-click'=>'configureExportButton')
          ); ?>
        </label>
      </div>
    </div>
  </div><!--row-->
  <button type="button" id="exportButton" name="exportButton" value="Export" disabled="disabled"><?php echo translate('Export') ?></button>
</div><!--container-->
          <h2 id="exportProgress" class="<?php
            if ( isset($_REQUEST['generated']) ) {
              if ( $_REQUEST['generated'] )
                echo 'infoText';
              else
                echo 'errorText';
            } else {
              echo 'hidden warnText';
            }
        ?>">
            <span id="exportProgressText">
              <?php
                if ( isset($_REQUEST['generated']) ) {
                  if ( $_REQUEST['generated'] )
                    echo translate('ExportSucceeded');
                  else
                    echo translate('ExportFailed');
                }
            ?></span>
            <span id="exportProgressTicker"></span>
          </h2>
          <button type="button" data-on-click-this="startDownload"<?php echo empty($_REQUEST['generated'])? ' class="hidden"' : '' ?>><?php echo translate('Download') ?></button>
          <input type="hidden" name="exportFile" value="<?php echo isset($_REQUEST['exportFile']) ? validHtmlStr($_REQUEST['exportFile']) : '' ?>"/>
          <input type="hidden" name="generated" value="<?php echo isset($_REQUEST['generated']) ? validHtmlStr($_REQUEST['generated']) : '' ?>"/>
        </form>
      </div>
    </div>
<?php xhtmlFooter() ?>
