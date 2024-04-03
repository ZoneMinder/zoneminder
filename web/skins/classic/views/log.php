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
getBodyTopHTML();
  echo getNavBarHTML() ?>
  <div id="content" class="px-3 table-responsive-sm">

    <div id="logSummary" class="text-center">
      <?php echo translate('State') ?>:&nbsp;<span id="logState"></span>&nbsp;-&nbsp;
      <?php echo translate('Total') ?>:&nbsp;<span id="totalLogs"></span>&nbsp;-&nbsp;
      <?php echo translate('Available') ?>:&nbsp;<span id="availLogs"></span>&nbsp;-&nbsp;
      <?php echo translate('Displaying') ?>:&nbsp;<span id="displayLogs"></span>&nbsp;-&nbsp;
      <?php echo translate('Updated') ?>:&nbsp;<span id="lastUpdate"></span>
    </div>
    <div id="logsTable">
    <div id="toolbar">
      <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      <div class="controlHeader">
      <span class="term ComponentFilter">
        <label><?php echo translate('Component') ?></label>
<?php
$components = dbFetchAll('SELECT DISTINCT Component FROM Logs ORDER BY Component', 'Component');
ZM\Debug(print_r($components, true));
$options = [''=>translate('All')] + array_combine($components, $components);
ZM\Debug(print_r($options, true));
echo '<span class="term-value-wrapper">';
echo htmlSelect('filterComponent', $options, '', array('id'=>'filterComponent', 'class'=>'chosen'));
echo '</span>';
?>
      </span>
<?php if (count($Servers)>1) { ?>
      <span class="term ServerFilter">
        <label><?php echo translate('Server') ?></label>
<?php
$ServersById = array(''=>translate('All')) + array_to_hash_by_key('Id', $Servers);
echo '<span class="term-value-wrapper">';
echo htmlSelect('filterServerId', $ServersById, '', array('id'=>'filterServerId', 'class'=>'chosen'));
echo '</span>';
?>
      </span>
<?php } ?>
      <span class="term LevelFilter">
        <label><?php echo translate('Level') ?></label>
<?php
$levels = array(''=>translate('All'));
foreach (array_values(ZM\Logger::$codes) as $level) {
  $levels[$level] = $level;
}
echo '<span class="term-value-wrapper">';
echo htmlSelect('filterLevel', $levels,
    (isset($_SESSION['ZM_LOG_FILTER_LEVEL']) ? $_SESSION['ZM_LOG_FILTER_LEVEL'] : ''),
    array('data-on-change'=>'filterLog', 'id'=>'filterLevel', 'class'=>'chosen'));
    #array('class'=>'form-control chosen', 'data-on-change'=>'filterLog'));
echo '</span>';
?>
      </span>
      <span class="term StartDateTimeFilter">
        <label><?php echo translate('Start Date/Time') ?></label>
        <span class="term-value-wrapper">
          <input type="text" name="filterStartDateTime" id="filterStartDateTime" value=""/>
        </span>
      </span>
      <span class="term EndDateTimeFilter">
        <label><?php echo translate('End Date/Time') ?></label>
        <span class="term-value-wrapper">
          <input type="text" name="filterEndDateTime" id="filterEndDateTime" value=""/>
        </span>
      </span>
      </div>
    </div><!--toolbar-->

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
      data-auto-refresh-interval="30"
    >
      <thead class="thead-highlight">
        <tr>
          <th data-sortable="true" data-field="DateTime"><?php echo translate('DateTime') ?></th>
          <th data-sortable="true" data-field="Component"><?php echo translate('Component') ?></th>
<?php if (count($Servers)>1) { ?>
          <th data-sortable="false" data-field="Server"><?php echo translate('Server') ?></th>
<?php } ?>
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
  </div><!--logstable-->
</div><!--content-->
<?php xhtmlFooter() ?>
