<?php
//
// ZoneMinder web reports view file
// Copyright (C) 2022 Isaac Connor
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

#if (!canView('Reports')) {
  #$view = 'error';
  #return;
#} else if (!ZM_FEATURES_SNAPSHOTS) {
  #$view = 'console';
  #return;
#}

require_once('includes/Event.php');
require_once('includes/Filter.php');
require_once('includes/Report.php');

xhtmlHeaders(__FILE__, translate('Reports'));
getBodyTopHTML();
   echo getNavBarHTML();
?>
  <div id="page" class="container-fluid p-3">
    <!-- Toolbar button placement and styling handled by bootstrap-tables -->
    <div id="toolbar">
      <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      <button type="button" id="newBtn" class="btn btn-normal" value="AddNew" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Add New Report') ?>"><i class="fa fa-plus"></i></button>
      <button type="button" id="deleteBtn" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete') ?>" disabled><i class="fa fa-trash"></i></button>
    </div>

      <!-- Table styling handled by bootstrap-tables -->
      <div class="row justify-content-center table-responsive-sm">
        <table
          id="reportsTable"
          data-locale="<?php echo i18n() ?>"
          data-side-pagination="server"
          data-ajax="ajaxRequest"
          data-pagination="true"
          data-show-pagination-switch="true"
          data-page-list="[10, 25, 50, 100, 200, All]"
          data-search="true"
          data-cookie="true"
          data-cookie-id-table="zmReportsTable"
          data-cookie-expire="2y"
          data-click-to-select="true"
          data-remember-order="true"
          data-show-columns="true"
          data-show-export="true"
          data-uncheckAll="true"
          data-toolbar="#toolbar"
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
              <th data-sortable="false" data-field="Description"><?php echo translate('Description') ?></th>
              <th data-sortable="true" data-field="StartDateTime"><?php echo translate('Starting') ?></th>
              <th data-sortable="true" data-field="EndDateTime"><?php echo translate('Ending') ?></th>
              <th data-sortable="true" data-field="Interval"><?php echo translate('Interval') ?></th>
            </tr>
          </thead>

          <tbody>
          <!-- Row data populated via Ajax -->
          </tbody>

        </table>
      </div>       
  </div>
<?php xhtmlFooter() ?>
