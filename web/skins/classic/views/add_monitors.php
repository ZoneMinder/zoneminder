<?php
//
// ZoneMinder web function view file
// Copyright (C) 2017 ZoneMinder LLC
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

if (!canEdit('Monitors')) {
  $view = 'error';
  return;
}
$canCreateMonitors = canCreate('Monitors');

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Add Monitors'));
getBodyTopHTML();
?>
  <div id="page">
    <?php echo getNavBarHTML(); ?>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="?">
        <div class="AddMonitors" id="inner-content">

          <div class="toolbar" id="toolbar">
            <div class="filters">
  <?php 
  $manufacturers = array_to_hash_by_key('Name', ZM\Manufacturer::find());
  echo htmlSelect('probe_Manufacturer', [''=>translate('All Manufacturers')]+$manufacturers, 
    (isset($_COOKIE['addMonitorsprobe_Manufacturer']) ? validCardinal($_COOKIE['addMonitorsprobe_Manufacturer']) : ''), [
    #'multiple'=>'multiple',
          'class'=>'chosen']);
  ?>
              <input type="text" id="ip" name="ip" placeholder="<?php echo translate('Camera IP Address') ?>"/>
              <input type="text" id="probe_username" name="probe_username" placeholder="<?php echo translate('Camera Username') ?>"
                value="<?php echo isset($_COOKIE['addMonitorsprobe_username']) ? validHtmlStr($_COOKIE['addMonitorsprobe_username']) : '' ?>"/>
              <input type="text" id="probe_password" name="probe_password" placeholder="<?php echo translate('Camera Password') ?>"
                value="<?php echo isset($_COOKIE['addMonitorsprobe_password']) ? validHtmlStr($_COOKIE['addMonitorsprobe_password']) : '' ?>"/>
            </div>
            <div id="contentButtons">
<?php 
  if ($canCreateMonitors) {
?>
              <button type="button" name="addBtn" data-on-click-this="addMonitor" title="<?php echo translate('Add New Monitor') ?>">
                <i class="material-icons md-18">add_circle</i>
                <span class="text"><?php echo translate('AddNewMonitor') ?></span>
              </button>
              <button type="button" id="importBtn" data-on-click-this="importMonitors" title="<?php echo translate('Import CSV') ?>">
                <i class="material-icons md-18">upload</i>
                <span class="text"><?php echo translate('Import CSV') ?></span>
              </button>
<?php 
  }
?>
            </div>
          </div><!--toolbar-->

<!--
              Defaults to apply to each monitor:<br/>
              <table><tr><th>Setting</th><th>Value</th></tr>
  <?php
                $servers = ZM\Server::find();
                $ServersById = array();
                foreach ( $servers as $S ) {
                  $ServersById[$S->Id()] = $S;
                }

                if ( count($ServersById) > 0 ) { ?>
                <tr class="Server"><td><?php echo translate('Server')?></td><td>
                <?php echo htmlSelect('newMonitor[ServerId]', [''=>translate('Auto')]+$ServersById, ''); ?>
                </td></tr>
  <?php
                }
                $storage_areas = ZM\Storage::find();
                $StorageById = array();
                foreach ( $storage_areas as $S ) {
                  $StorageById[$S->Id()] = $S;
                }
                if ( count($StorageById) > 1 ) {
  ?>
  <tr class="Storage"><td><?php echo translate('Storage')?></td><td>
  <?php echo htmlSelect('newMonitor[StorageId]', [''=>translate('Auto')]+$StorageById, ''); ?>
  </tr>
  <?php
                }
  ?>
                </td></tr>
              </table>
-->
<!-- Table styling handled by bootstrap-tables -->
      <table
        id="AddMonitorsTable"
        data-locale="<?php echo i18n() ?>"
        data-ajax="ajaxRequest"
        data-pagination="false"
        data-show-pagination-switch="false"
        data-page-list="[10, 25, 50, 100, 200, All]"
        data-search="true"
        data-cookie="true"
        data-cookie-id-table="AddMonitorsTable"
        data-cookie-expire="2y"
        data-click-to-select="true"
        data-remember-order="false"
        data-show-columns="true"
        data-show-export="true"
        data-uncheckAll="true"
        data-toolbar="#toolbar"
        data-sort-name="Monitor.Name,Name"
        data-sort-order="asc"
        data-server-sort="false"
        data-click-to-select="true"
        data-maintain-meta-data="true"
        data-buttons-class="btn btn-normal"
        data-show-jump-to="true"
        data-show-refresh="true"
        data-show-multi-sort="false"
data-check-on-init="true"
data-mobile-responsive="true"
data-min-width="562"
        class="table-sm table-borderless"
        style="display:none;"
      >
        <thead>
            <!-- Row styling is handled by bootstrap-tables -->
            <tr>
              <th data-sortable="true" data-field="camera.Name" class="CameraName"><?php echo translate('Name') ?></th>
              <th data-sortable="true" data-field="mac" class="CameraMAC"><?php echo translate('MAC Address') ?></th>
              <th data-sortable="true" data-field="camera.ip" class="CameraIP"><?php echo translate('IP Address') ?></th>
              <th data-sortable="true" data-field="url" class="Url"><?php echo translate('URL') ?></th>
              <th data-sortable="true" data-field="camera.Manufacturer" class="Manufacturer"><?php echo translate('Manufacturer') ?></th>
              <th data-sortable="true" data-field="camera.Model" class="Model"><?php echo translate('Model') ?></th>
              <th data-sortable="true" data-field="camera.monitor.Width" class="Width"><?php echo translate('Width') ?></th>
              <th data-sortable="true" data-field="camera.monitor.Height" class="Height"><?php echo translate('Height') ?></th>
              <th data-sortable="true" data-field="camera.Codec" class="Codec"><?php echo translate('Codec') ?></th>
              <th data-sortable="true" data-field="Monitor.Name" class="MonitorName"><?php echo translate('Monitor') ?></th>
              <th data-sortable="false" data-field="Thumbnail" style="width: <?php echo ZM_WEB_LIST_THUMB_WIDTH?>px;" class="Thumbnail"><?php echo translate('Thumbnail') ?></th>
              <th data-sortable="false" data-field="buttons"><?php echo translate('Add').' / '.translate('Edit') ?></th>
            </tr>
          </thead>

          <tbody>
          <!-- Row data populated via Ajax -->
          </tbody>
        </table>
        </div><!--AddMonitors inner-content-->
      </form>
    </div><!--content-->
  </div><!--page-->
<?php xhtmlFooter() ?>
