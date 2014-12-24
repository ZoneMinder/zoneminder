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
  <div id="page">
    <div id="header">
      <div id="headerButtons">
          <input type="button" value="<?php echo $SLANG['More'] ?>" onclick="expandLog()"/>
          <input type="button" value="<?php echo $SLANG['Clear'] ?>" onclick="clearLog()"/>
          <input type="button" value="<?php echo $SLANG['Refresh'] ?>" onclick="refreshLog()"/>
          <input type="button" value="<?php echo $SLANG['Export'] ?>" onclick="exportLog()"/>
          <input type="button" value="<?php echo $SLANG['Close'] ?>" onclick="closeWindow()"/>
      </div>
      <h2 class="floating"><?php echo $SLANG['SystemLog'] ?></h2>
      <div id="headerControl">
        <table id="logSummary" cellspacing="0">
          <tr>
            <td><?php echo $SLANG['Updated'] ?>: <span id="lastUpdate"></span></td>
            <td><?php echo $SLANG['State'] ?>: <span id="logState"></span></td>
            <td><?php echo $SLANG['Total'] ?>: <span id="totalLogs"></span></td>
            <td><?php echo $SLANG['Available'] ?>: <span id="availLogs"></span></td>
            <td><?php echo $SLANG['Displaying'] ?>: <span id="displayLogs"></span></td>
          </tr>
        </table>
      </div>
    </div>
    <div id="content">
      <div id="filters">Filter log -
        Component <select id="filter[Component]" onchange="filterLog(this)"><option value="">-----</option></select>
        PID <select id="filter[Pid]" onchange="filterLog(this)"><option value="">-----</option></select>
        Level <select id="filter[Level]" onchange="filterLog(this)"><option value="">---</option></select>
        File <select id="filter[File]" onchange="filterLog(this)"><option value="">------</option></select>
        Line <select id="filter[Line]" onchange="filterLog(this)"><option value="">----</option></select>
        <input type="reset" value="<?php echo $SLANG['Reset'] ?>" onclick="resetLog()"/>
      </div>
      <form name="logForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <table id="logTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th><?php echo $SLANG['DateTime'] ?></th>
              <th class="table-th-nosort"><?php echo $SLANG['Component'] ?></th>
              <th class="table-th-nosort"><?php echo $SLANG['Pid'] ?></th>
              <th class="table-th-nosort"><?php echo $SLANG['Level'] ?></th>
              <th class="table-th-nosort"><?php echo $SLANG['Message'] ?></th>
              <th class="table-th-nosort"><?php echo $SLANG['File'] ?></th>
              <th class="table-th-nosort"><?php echo $SLANG['Line'] ?></th>
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
      <div class="overlayTitle"><?php echo $SLANG['ExportLog'] ?></div>
    </div>
    <div class="overlayBody">
      <div class="overlayContent">
        <form id="exportForm" action="" method="post">
          <fieldset>
            <legend><?php echo $SLANG['SelectLog'] ?></legend>
            <label for="selectorAll">All</label><input type="radio" id="selectorAll" name="selector" value="all"/>
            <label for="selectorFilter">Filter</label><input type="radio" id="selectorFilter" name="selector" value="filter"/>
            <label for="selectorCurrent">Current</label><input type="radio" id="selectorCurrent" name="selector" value="current" title="<?php echo $SLANG['ChooseLogSelection'] ?>" data-validators="validate-one-required"/>
          </fieldset>
          <fieldset>
            <legend><?php echo $SLANG['SelectFormat'] ?></legend>
            <label for="formatText">Text</label><input type="radio" id="formatText" name="format" value="text"/>
            <label for="formatTSV">TSV</label><input type="radio" id="formatTSV" name="format" value="tsv"/>
            <label for="formatXML">HTML</label><input type="radio" id="formatHTML" name="format" value="html"/>
            <label for="formatXML">XML</label><input type="radio" id="formatXML" name="format" value="xml" title="<?php echo $SLANG['ChooseLogFormat'] ?>" class="validate-one-required"/>
          </fieldset>
          <div id="exportError">
            <?php echo $SLANG['ExportFailed'] ?>: <span id="exportErrorText"></span>
          </div>
          <input type="button" id="exportButton" value="<?php echo $SLANG['Export'] ?>" onclick="exportRequest()"/>
          <input type="button" value="<?php echo $SLANG['Cancel'] ?>" class="overlayCloser"/>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
