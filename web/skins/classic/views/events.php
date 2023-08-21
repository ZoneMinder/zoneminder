<?php
//
// ZoneMinder web events view file, $Date$, $Revision$
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

if ( !canView('Events') || (!empty($_REQUEST['execute']) && !canEdit('Events')) ) {
  $view = 'error';
  return;
}

require_once('includes/Event.php');
require_once('includes/Filter.php');

$eventsSql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultScale FROM Monitors AS M INNER JOIN Events AS E on (M.Id = E.MonitorId) WHERE';
if (count($user->unviewableMonitorIds())) {
  $user_monitor_ids = ' M.Id in ('.implode(',', $user->viewableMonitorIds()).')';
  $eventsSql .= $user_monitor_ids;
} else {
  $eventsSql .= ' 1';
}

$filter = isset($_REQUEST['filter_id']) ? new ZM\Filter($_REQUEST['filter_id']) : new ZM\Filter();
if ( isset($_REQUEST['filter'])) {
  $filter->set($_REQUEST['filter']);
}
 
if (!$filter->Id()) {
  $num_terms = count($filter->terms());
  if (!$filter->has_term('Monitor')) {
    $filter->addTerm(array('cnj'=>'and', 'attr'=>'Monitor', 'op'=> '=', 'val'=>'', 'cookie'=>'eventsMonitor'), 0);
  }
  if (ZM\Group::find_one() and !$filter->has_term('Group'))
    $filter->addTerm(array('cnj'=>'and', 'attr'=>'Group', 'op'=> '=', 'cookie'=>'eventsGroup'), 0);
  #if (!$filter->has_term('Notes')) {
    #$filter->addTerm(array('cnj'=>'and', 'attr'=>'Notes', 'op'=> 'LIKE', 'val'=>'', 'cookie'=>'eventsNotes'));
  #}
  if (!$filter->has_term('StartDateTime')) {
    $filter->addTerm(array('attr' => 'StartDateTime', 'op' => '>=', 
      'val' => $num_terms ? '' : (isset($_COOKIE['eventsStartDateTimeStart']) ? $_COOKIE['eventsStartDateTimeStart'] : date('Y-m-d h:i:s', time()-3600)),
      'cnj' => 'and', 'cookie'=>'eventsStartDateTimeStart'));
  }
  if (!$filter->has_term('EndDateTime')) {
    $filter->addTerm(array('attr' => 'EndDateTime', 'op' => '<=',
      'val' => $num_terms ? '' : (isset($_COOKIE['eventsEndDateTimeEnd']) ? $_COOKIE['eventsEndDateTimeEnd'] : ''),
      'cnj' => 'and', 'cookie'=>'eventsEndDateTimeEnd'));
  }
  $filter->sort_terms(['Group','Monitor','StartDateTime','EndDateTime']);
  #$filter->addTerm(array('cnj'=>'and', 'attr'=>'AlarmFrames', 'op'=> '>', 'val'=>'10'));
  #$filter->addTerm(array('cnj'=>'and', 'attr'=>'StartDateTime', 'op'=> '<=', 'val'=>''));
}

parseSort();

$filterQuery = $filter->querystring();

xhtmlHeaders(__FILE__, translate('Events'));
getBodyTopHTML();
?>
  <div id="page">
<?php echo getNavBarHTML(); ?>
    <div id="content">
      <!-- Toolbar button placement and styling handled by bootstrap-tables -->
      <div id="toolbar">
        <div id="leftButtons" class="buttons">
          <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
          <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
          <button id="tlineBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('ShowTimeline') ?>" ><i class="fa fa-history"></i></button>
          <button id="filterBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Filter') ?>"><i class="fa fa-filter"></i></button>
        </div>
  <?php
    if (!$filter->Id()) {
      echo $filter->simple_widget();
    } else {
      echo $filter->widget();
    }
  ?>
        <div id="rightButtons" class="buttons">
          <button id="viewBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('View') ?>" disabled><i class="fa fa-binoculars"></i></button>
          <button id="archiveBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Archive') ?>" disabled><i class="fa fa-archive"></i></button>
          <button id="unarchiveBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Unarchive') ?>" disabled><i class="fa fa-file-archive-o"></i></button>
          <button id="editBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Edit') ?>" disabled><i class="fa fa-pencil"></i></button>
          <button id="exportBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Export') ?>" disabled><i class="fa fa-external-link"></i></button>
          <button id="downloadBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('DownloadVideo') ?>" disabled><i class="fa fa-download"></i></button>
          <button id="deleteBtn" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete') ?>" disabled><i class="fa fa-trash"></i></button>
        </div><!--buttons-->
      </div>

    <div id="inner-content">
    <div id="events" class="table-responsive">
    <!-- Table styling handled by bootstrap-tables -->
      <table
        id="eventTable"
        data-locale="<?php echo i18n() ?>"
        data-side-pagination="server"
        data-ajax="ajaxRequest"
        data-pagination="true"
        data-show-pagination-switch="true"
        data-page-list="[5, 10, 25, 50, 100, 200, All]"
        data-search="true"
        data-cookie="true"
        data-cookie-same-site="Strict"
        data-cookie-id-table="zmEventsTable"
        data-cookie-expire="2y"
        data-click-to-select="true"
        data-remember-order="false"
        data-show-columns="true"
        data-show-export="true"
        data-uncheckAll="true"
        data-toolbar="#toolbar"
        data-sort-name="<?php echo $filter->sort_field() ?>"
        data-sort-order="<?php echo $filter->sort_asc() ? 'asc' : 'desc' ?>"
        data-server-sort="true"
        data-show-fullscreen="true"
        data-click-to-select="true"
        data-maintain-meta-data="true"
        data-buttons-class="btn btn-normal"
        data-show-jump-to="true"
        data-show-refresh="true"
data-columns-hidden="['Archived','Emailed','Monitor','Id','Name'.'Frames','AlarmFrames','TotScore','AvgScore']"
data-check-on-init="true"
data-mobile-responsive="true"
data-min-width="562"
        class="table-sm table-borderless table"
        style="display:none;"
      >
        <thead>
            <!-- Row styling is handled by bootstrap-tables -->
            <tr>
              <th data-sortable="false" data-field="toggleCheck" data-checkbox="true"></th>
              <th data-sortable="false" data-field="Thumbnail" style="width: <?php echo ZM_WEB_LIST_THUMB_WIDTH?>px;"><?php echo translate('Thumbnail') ?></th>
              <th data-sortable="true" data-field="Id" class="EventId"><?php echo translate('Id') ?></th>
              <th data-sortable="true" data-field="Name" class="Name"><?php echo translate('Name') ?></th>
              <th data-sortable="true" data-field="Archived" class="Archived"><?php echo translate('Archived') ?></th>
              <th data-sortable="true" data-field="Emailed" class="Emailed"><?php echo translate('Emailed') ?></th>
              <th data-sortable="true" data-field="Monitor" class="Monitor"><?php echo translate('Monitor') ?></th>
              <th data-sortable="true" data-field="Cause" class="Cause" data-click-to-select="false"><?php echo translate('Cause') ?></th>
              <th data-sortable="true" data-field="StartDateTime" class="StartDateTime"><?php echo translate('AttrStartTime') ?></th>
              <th data-sortable="true" data-field="EndDateTime" class="EndDateTime"><?php echo translate('AttrEndTime') ?></th>
              <th data-sortable="true" data-field="Length" class="Length"><?php echo translate('Duration') ?></th>
              <th data-sortable="true" data-field="Frames" class="Frames"><?php echo translate('Frames') ?></th>
              <th data-sortable="true" data-field="AlarmFrames" class="AlarmFrames"><?php echo translate('AlarmBrFrames') ?></th>
              <th data-sortable="true" data-field="TotScore" class="TotScore"><?php echo translate('TotalBrScore') ?></th>
              <th data-sortable="true" data-field="AvgScore" class="AvgScore"><?php echo translate('AvgBrScore') ?></th>
              <th data-sortable="true" data-field="MaxScore" class="MaxScore"><?php echo translate('MaxBrScore') ?></th>
              <th data-sortable="false" data-field="Storage" class="Storage"><?php echo translate('Storage') ?></th>
              <th data-sortable="true" data-field="DiskSpace" class="DiskSpace"><?php echo translate('DiskSpace') ?></th>
            </tr>
          </thead>

          <tbody>
          <!-- Row data populated via Ajax -->
          </tbody>
        </table>
      </div> <!--events-->
      </div><!--inner-content-->
    </div><!--content-->
  </div><!--page-->
  <script src="<?php echo cache_bust('skins/classic/js/export.js') ?>"></script>
<?php xhtmlFooter() ?>
