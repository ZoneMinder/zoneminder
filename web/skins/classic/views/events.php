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
if ( $user['MonitorIds'] ) {
  $user_monitor_ids = ' M.Id in ('.$user['MonitorIds'].')';
  $eventsSql .= $user_monitor_ids;
} else {
  $eventsSql .= ' 1';
}

$filter = isset($_REQUEST['filter_id']) ? new ZM\Filter($_REQUEST['filter_id']) : new ZM\Filter();
if ( isset($_REQUEST['filter'])) {
  $filter->set($_REQUEST['filter']);
}

parseSort();

$filterQuery = $filter->querystring();

xhtmlHeaders(__FILE__, translate('Events'));
getBodyTopHTML();

?>
  <?php echo getNavBarHTML() ?>
  <div id="page" class="container-fluid p-3">
    <!-- Toolbar button placement and styling handled by bootstrap-tables -->
    <div id="toolbar">
      <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      <button id="tlineBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('ShowTimeline') ?>" ><i class="fa fa-history"></i></button>
      <button id="filterBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Filter') ?>"><i class="fa fa-filter"></i></button>
      <button id="viewBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('View') ?>" disabled><i class="fa fa-binoculars"></i></button>
      <button id="archiveBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Archive') ?>" disabled><i class="fa fa-archive"></i></button>
      <button id="unarchiveBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Unarchive') ?>" disabled><i class="fa fa-file-archive-o"></i></button>
      <button id="editBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Edit') ?>" disabled><i class="fa fa-pencil"></i></button>
      <button id="exportBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Export') ?>" disabled><i class="fa fa-external-link"></i></button>
      <button id="downloadBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('DownloadVideo') ?>" disabled><i class="fa fa-download"></i></button>
      <button id="deleteBtn" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete') ?>" disabled><i class="fa fa-trash"></i></button>
    </div>

    <!-- Table styling handled by bootstrap-tables -->
    <div class="row justify-content-center table-responsive-sm">
      <table
        id="eventTable"
        data-locale="<?php echo i18n() ?>"
        data-side-pagination="server"
        data-ajax="ajaxRequest"
        data-pagination="true"
        data-show-pagination-switch="true"
        data-page-list="[10, 25, 50, 100, 200, All]"
        data-search="true"
        data-cookie="true"
        data-cookie-id-table="zmEventsTable<?php echo $filterQuery?>"
        data-cookie-expire="60s"
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
        class="table-sm table-borderless"
        style="display:none;"
      >
        <thead>
            <!-- Row styling is handled by bootstrap-tables -->
            <tr>
              <th data-sortable="false" data-field="toggleCheck" data-checkbox="true"></th>
              <th data-sortable="true" data-field="Id"><?php echo translate('Id') ?></th>
              <th data-sortable="true" data-field="Name"><?php echo translate('Name') ?></th>
              <th data-sortable="true" data-field="Archived"><?php echo translate('Archived') ?></th>
              <th data-sortable="true" data-field="Emailed"><?php echo translate('Emailed') ?></th>
              <th data-sortable="true" data-field="Monitor"><?php echo translate('Monitor') ?></th>
              <th data-sortable="true" data-field="Cause" data-click-to-select="false"><?php echo translate('Cause') ?></th>
              <th data-sortable="true" data-field="StartDateTime"><?php echo translate('AttrStartTime') ?></th>
              <th data-sortable="true" data-field="EndDateTime"><?php echo translate('AttrEndTime') ?></th>
              <th data-sortable="true" data-field="Length"><?php echo translate('Duration') ?></th>
              <th data-sortable="true" data-field="Frames"><?php echo translate('Frames') ?></th>
              <th data-sortable="true" data-field="AlarmFrames"><?php echo translate('AlarmBrFrames') ?></th>
              <th data-sortable="true" data-field="TotScore"><?php echo translate('TotalBrScore') ?></th>
              <th data-sortable="true" data-field="AvgScore"><?php echo translate('AvgBrScore') ?></th>
              <th data-sortable="true" data-field="MaxScore"><?php echo translate('MaxBrScore') ?></th>
              <th data-sortable="false" data-field="Storage"><?php echo translate('Storage') ?></th>
              <th data-sortable="true" data-field="DiskSpace"><?php echo translate('DiskSpace') ?></th>
              <th data-sortable="false" data-field="Thumbnail"><?php echo translate('Thumbnail') ?></th>
            </tr>
          </thead>

          <tbody>
          <!-- Row data populated via Ajax -->
          </tbody>

        </table>
      </div>       
  </div>
<?php xhtmlFooter() ?>
