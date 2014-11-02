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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

if ( !canView( 'System' ) )
{
    $view = "error";
    return;
}

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['SystemLog'] );
?>
<body>

	<?php include("header.php"); ?>

  <div class="container-fluid">
		<div class="row">


		<div class="col-md-2">

			<div id="filter-buttons">
				<div class="btn-group btn-group-justified">
					<div class="btn-group"><input class="btn btn-default btn-block" type="button" value="<?= $SLANG['More'] ?>" onclick="expandLog()"/></div>
					<div class="btn-group"><input class="btn btn-default btn-block" type="button" value="<?= $SLANG['Clear'] ?>" onclick="clearLog()"/></div>
				</div>
				<div class="btn-group btn-group-justified">
					<div class="btn-group"><input class="btn btn-default btn-block" type="button" value="<?= $SLANG['Refresh'] ?>" onclick="refreshLog()"/></div>
					<div class="btn-group"><input class="btn btn-default btn-block" type="button" value="<?= $SLANG['Export'] ?>" onclick="exportLog()"/></div>
				</div>
			</div>

      <div id="filters">
				<div class="form-group">
        	<label class="sr-only" for="filter[Component]">Component</label>
					<select class="form-control" id="filter[Component]" onchange="filterLog(this)"><option value="">Component</option></select>
				</div>

				<div class="form-group">
					<label class="sr-only" for="filter[Pid]">PID</label>
					<select class="form-control" id="filter[Pid]" onchange="filterLog(this)"><option value="">PID</option></select>
				</div>

				<div class="form-group">
					<label class="sr-only" for="filter[Level]">Level</label>
					<select class="form-control" id="filter[Level]" onchange="filterLog(this)"><option value="">Level</option></select>
				</div>

				<div class="form-group">
					<label class="sr-only" for="filter[File]">File</label>
					<select class="form-control" id="filter[File]" onchange="filterLog(this)"><option value="">File</option></select>
				</div>

				<div class="form-group">
					<label class="sr-only" for="filter[Line]">Line</label>
					<select class="form-control" id="filter[Line]" onchange="filterLog(this)"><option value="">Line</option></select>
				</div>

        <input class="btn btn-default" type="reset" value="<?= $SLANG['Reset'] ?>" onclick="resetLog()"/>
      </div>
		</div>

    <div class="col-md-10">
      <div id="headerControl">
        <table id="logSummary" cellspacing="0">
          <tr>
            <td><?= $SLANG['Updated'] ?>: <span id="lastUpdate"></span></td>
            <td><?= $SLANG['State'] ?>: <span id="logState"></span></td>
            <td><?= $SLANG['Total'] ?>: <span id="totalLogs"></span></td>
            <td><?= $SLANG['Available'] ?>: <span id="availLogs"></span></td>
            <td><?= $SLANG['Displaying'] ?>: <span id="displayLogs"></span></td>
          </tr>
        </table>
      </div>


      <form name="logForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <table class="table" id="logTable">
          <thead>
            <tr>
              <th><?= $SLANG['DateTime'] ?></th>
              <th class="table-th-nosort"><?= $SLANG['Component'] ?></th>
              <th class="table-th-nosort"><?= $SLANG['Pid'] ?></th>
              <th class="table-th-nosort"><?= $SLANG['Level'] ?></th>
              <th class="table-th-nosort"><?= $SLANG['Message'] ?></th>
              <th class="table-th-nosort"><?= $SLANG['File'] ?></th>
              <th class="table-th-nosort"><?= $SLANG['Line'] ?></th>
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
      <div class="overlayTitle"><?= $SLANG['ExportLog'] ?></div>
    </div>
    <div class="overlayBody">
      <div class="overlayContent">
        <form id="exportForm" action="" method="post">
          <fieldset>
            <legend><?= $SLANG['SelectLog'] ?></legend>
            <label for="selectorAll">All</label><input type="radio" id="selectorAll" name="selector" value="all"/>
            <label for="selectorFilter">Filter</label><input type="radio" id="selectorFilter" name="selector" value="filter"/>
            <label for="selectorCurrent">Current</label><input type="radio" id="selectorCurrent" name="selector" value="current" title="<?= $SLANG['ChooseLogSelection'] ?>" data-validators="validate-one-required"/>
          </fieldset>
          <fieldset>
            <legend><?= $SLANG['SelectFormat'] ?></legend>
            <label for="formatText">Text</label><input type="radio" id="formatText" name="format" value="text"/>
            <label for="formatTSV">TSV</label><input type="radio" id="formatTSV" name="format" value="tsv"/>
            <label for="formatXML">HTML</label><input type="radio" id="formatHTML" name="format" value="html"/>
            <label for="formatXML">XML</label><input type="radio" id="formatXML" name="format" value="xml" title="<?= $SLANG['ChooseLogFormat'] ?>" class="validate-one-required"/>
          </fieldset>
          <div id="exportError">
            <?= $SLANG['ExportFailed'] ?>: <span id="exportErrorText"></span>
          </div>
          <input type="button" id="exportButton" value="<?= $SLANG['Export'] ?>" onclick="exportRequest()"/>
          <input type="button" value="<?= $SLANG['Cancel'] ?>" class="overlayCloser"/>
        </form>
      </div>
    </div>
		</div>
  </div>
</body>
</html>
