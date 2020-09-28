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

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('SystemLog'));
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page">
    <div id="header">
      <div id="logSummary" class="text-center">
      <?php echo translate('State') ?>: <span id="logState"></span>/
      <?php echo translate('Total') ?>: <span id="totalLogs"></span>/
      <?php echo translate('Available') ?>: <span id="availLogs"></span>/
      <?php echo translate('Displaying') ?>: <span id="displayLogs"></span>/
      <?php echo translate('Updated') ?>: <span id="lastUpdate"></span>
      </div>
      <div class="btn-toolbar justify-content-center py-1">
        <button type="button" data-on-click="expandLog"><?php echo translate('More') ?></button>
        <button type="button" data-on-click="clearLog"><?php echo translate('Clear') ?></button>
        <button type="button" data-on-click="refreshLog"><?php echo translate('Refresh') ?></button>
        <button type="button" data-on-click="exportLog"><?php echo translate('Export') ?></button>
        <button type="reset" data-on-click="resetLog"><?php echo translate('Reset') ?></button>
      </div> <!--btn-->
    </div> <!--header-->
    <div id="content">
      <form id="logForm" name="logForm" method="post" action="?">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <div class="container" id="filters">
          <div class="row">
            <div class="col">
              <label><?php echo translate('Component') ?></label>
              <select class="form-control chosen" id="filter[Component]" data-on-change="filterLog"><option value="">-----</option></select>
            </div>
            <div class="col">
              <label><?php echo translate('Server') ?></label>
              <select class="form-control chosen" id="filter[ServerId]" data-on-change="filterLog"><option value="">-----</option></select>
            </div>
            <div class="col">
              <label><?php echo translate('Pid') ?></label>
              <select class="form-control chosen" id="filter[Pid]" data-on-change="filterLog"><option value="">-----</option></select>
            </div>
            <div class="col">
              <label><?php echo translate('Level') ?></label>
              <select class="form-control chosen" id="filter[Level]" data-on-change="filterLog"><option value="">---</option></select>
            </div>
            <div class="col">
              <label><?php echo translate('File') ?></label>
              <select class="form-control chosen" id="filter[File]" data-on-change="filterLog"><option value="">------</option></select>
            </div>
            <div class="col">
              <label><?php echo translate('Line') ?></label>
              <select class="form-control chosen" id="filter[Line]" data-on-change="filterLog"><option value="">----</option></select>
            </div>
          </div><!--row-->
        </div><!--container-->
        <table id="logTable" class="major">
          <thead class="thead-highlight">
            <tr>
              <th><?php echo translate('DateTime') ?></th>
              <th class="table-th-nosort"><?php echo translate('Component') ?></th>
              <th class="table-th-nosort"><?php echo translate('Server') ?></th>
              <th class="table-th-nosort"><?php echo translate('Pid') ?></th>
              <th class="table-th-nosort"><?php echo translate('Level') ?></th>
              <th class="table-th-nosort"><?php echo translate('Message') ?></th>
              <th class="table-th-nosort"><?php echo translate('File') ?></th>
              <th class="table-th-nosort"><?php echo translate('Line') ?></th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </form>
    </div><!--content-->
  </div><!--page-->
<?php xhtmlFooter() ?>
