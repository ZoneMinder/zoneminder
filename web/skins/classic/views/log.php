<?php
//
// ZoneMinder web log view file, $Date: 2010-02-23 09:10:36 +0000 (Tue, 23 Feb 2010) $, $Revision: 3030 $
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

if ( !canView('System') ) {
  $view = 'error';
  return;
}

xhtmlHeaders(__FILE__, translate('SystemLog'));
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page" class="px-3 table-responsive-sm">

    <div id="logSummary" class="text-center">
      <?php echo translate('State') ?>:&nbsp;<span id="logState"></span>&nbsp;-&nbsp;
      <?php echo translate('Total') ?>:&nbsp;<span id="totalLogs"></span>&nbsp;-&nbsp;
      <?php echo translate('Available') ?>:&nbsp;<span id="availLogs"></span>&nbsp;-&nbsp;
      <?php echo translate('Displaying') ?>:&nbsp;<span id="displayLogs"></span>&nbsp;-&nbsp;
      <?php echo translate('Updated') ?>:&nbsp;<span id="lastUpdate"></span>
    </div>

    <div id="toolbar">
      <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
    </div>

    <table
      id="logTable"
      data-locale="<?php echo i18n() ?>"
      class="table-sm table-borderless"
      data-side-pagination="server"
      data-ajax="ajaxRequest"
      data-pagination="true"
      data-page-list="[10, 25, 50, 100, 200, 300, 400, 500]"
      data-search="true"
      data-advanced-search="true"
      data-id-table="advancedTable"
      data-cookie="true"
      data-cookie-id-table="zmLogsTable"
      data-cookie-expire="2y"
      data-remember-order="true"
      data-show-columns="true"
      data-show-export="true"
      data-toolbar="#toolbar"
      data-show-fullscreen="true"
      data-maintain-meta-data="true"
      data-buttons-class="btn btn-normal"
      data-show-jump-to="true"
      data-auto-refresh="true"
      data-auto-refresh-silent="true"
      data-show-refresh="true"
      data-auto-refresh-interval="5"
    >
      <thead class="thead-highlight">
        <tr>
          <th data-sortable="true" data-field="DateTime"><?php echo translate('DateTime') ?></th>
          <th data-sortable="true" data-field="Component"><?php echo translate('Component') ?></th>
          <th data-sortable="false" data-field="Server"><?php echo translate('Server') ?></th>
          <th data-sortable="true" data-field="Pid"><?php echo translate('Pid') ?></th>
          <th data-sortable="true" data-field="Code"><?php echo translate('Level') ?></th>
          <th data-sortable="true" data-field="Message"><?php echo translate('Message') ?></th>
          <th data-sortable="true" data-field="File"><?php echo translate('File') ?></th>
          <th data-sortable="true" data-field="Line"><?php echo translate('Line') ?></th>
        </tr>
      </thead>

      <tbody>
      <!-- Row data populated via Ajax -->
      </tbody>

    </table>
  </div><!--page-->
<?php xhtmlFooter() ?>
