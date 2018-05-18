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

xhtmlHeaders(__FILE__, translate('SystemLog') );
?>
<body>
  <div id="page">
    <div id="header">
      <table class="table">
     	  <tr class="row">
          <td class="col text-center">
		        <div id="logSummary">
            <?php echo translate('State') ?>: <span id="logState"></span>/
            <?php echo translate('Total') ?>: <span id="totalLogs"></span>/
            <?php echo translate('Available') ?>: <span id="availLogs"></span>/
            <?php echo translate('Displaying') ?>: <span id="displayLogs"></span>/
            <?php echo translate('Updated') ?>: <span id="lastUpdate"></span>
            </div>
          </td>
        </tr>
        <tr class="row">
	        <td class="col text-center">
            <div class="btn-group">
              <button type="button" class="btn btn-sm" onclick="expandLog()"> <?php echo translate('More') ?></button>
              <button type="button" class="btn btn-sm" onclick="clearLog()"> <?php echo translate('Clear') ?></button>
              <button type="button" class="btn btn-sm" onclick="refreshLog()"> <?php echo translate('Refresh') ?></button>
              <button type="button" class="btn btn-sm" onclick="exportLog()"> <?php echo translate('Export') ?></button>
              <button type="button" class="btn btn-sm" onclick="closeWindow()"> <?php echo translate('Close') ?></button>
            </div> <!--btn-->
          </td>
      </tr>
	  </table>

  </div> <!--header-->
  <div id="content">
    <div id="filters">

      <table class="table-condensed">
        <tr class="row">
          <td class="col">
            <?php echo translate('Component') ?>
            <select class="form-control chosen" id="filter[Component]" onchange="filterLog(this)"><option value="">-----</option></select>
          </td>
          <td class="col">
            <?php echo translate('Server') ?>
            <select class="form-control chosen" id="filter[ServerId]" onchange="filterLog(this)"><option value="">-----</option></select>
          </td>
          <td class="col">
            <?php echo translate('Pid') ?>
            <select class="form-control chosen" id="filter[Pid]" onchange="filterLog(this)"><option value="">-----</option></select>
          </td>
        </tr>
        <tr class="row">
          <td class="col">
            <?php echo translate('Level') ?>
            <select class="form-control chosen" id="filter[Level]" onchange="filterLog(this)"><option value="">---</option></select>
          </td>
          <td class="col">
            <?php echo translate('File') ?>
            <select class="form-control chosen" id="filter[File]" onchange="filterLog(this)"><option value="">------</option></select>
          </td>
          <td  class="col">
            <?php echo translate('Line') ?>
            <select class="form-control chosen" id="filter[Line]" onchange="filterLog(this)"><option value="">----</option></select>
          </td>
        </tr>
      </table>
      <input type="reset" value="<?php echo translate('Reset') ?>" onclick="resetLog()"/>
    </div>
    <form name="logForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
      <input type="hidden" name="view" value="<?php echo $view ?>"/>
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
      <div id="contentButtons">
      </div>
    </form>
  </div>
</div>
  <div id="exportLog" class="overlay">
    <div class="overlayHeader">
      <div class="overlayTitle"><?php echo translate('ExportLog') ?></div>
    </div>
    <div class="overlayBody">
      <div class="overlayContent">
        <form id="exportForm" action="" method="post">
          <fieldset>
            <legend><?php echo translate('SelectLog') ?></legend>
            <label for="selectorAll"><?php echo translate('All') ?></label><input type="radio" id="selectorAll" name="selector" value="all"/>
            <label for="selectorFilter"><?php echo translate('Filter') ?></label><input type="radio" id="selectorFilter" name="selector" value="filter"/>
            <label for="selectorCurrent"><?php echo translate('Current') ?></label><input type="radio" id="selectorCurrent" name="selector" value="current" title="<?php echo translate('ChooseLogSelection') ?>" data-validators="validate-one-required"/>
          </fieldset>
          <fieldset>
            <legend><?php echo translate('SelectFormat') ?></legend>
            <label for="formatText">TXT</label><input type="radio" id="formatText" name="format" value="text"/>
            <label for="formatTSV">TSV</label><input type="radio" id="formatTSV" name="format" value="tsv"/>
            <label for="formatXML">HTML</label><input type="radio" id="formatHTML" name="format" value="html"/>
            <label for="formatXML">XML</label><input type="radio" id="formatXML" name="format" value="xml" title="<?php echo translate('ChooseLogFormat') ?>" class="validate-one-required"/>
          </fieldset>
          <div id="exportError">
            <?php echo translate('ExportFailed') ?>: <span id="exportErrorText"></span>
          </div>
          <input type="button" id="exportButton" value="<?php echo translate('Export') ?>" onclick="exportRequest()"/>
          <input type="button" value="<?php echo translate('Cancel') ?>" class="overlayCloser"/>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
