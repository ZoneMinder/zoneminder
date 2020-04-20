<?php
//
// ZoneMinder web control view file, $Date$, $Revision$
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

if ( !canView('Control') ) {
  $view = 'error';
  return;
}

$params = array();
$groupSql = '';
if ( !empty($_REQUEST['group']) ) {
  $groupSql = ' AND (m.Id IN (SELECT MonitorID FROM Groups_Monitors WHERE GroupId = :groupid))';
  $params[':groupid'] = $_REQUEST['group'];
}

$mid = !empty($_REQUEST['mid']) ? validInt($_REQUEST['mid']) : 0;

$sql = "SELECT m.* FROM Monitors m WHERE m.Function != 'None' AND m.Controllable = 1$groupSql ORDER BY Sequence";
$mids = array();
foreach ( dbFetchAll($sql, false, $params) as $row ) {
  if ( !visibleMonitor($row['Id']) ) {
    continue;
  }
  if ( empty($mid) )
    $mid = $row['Id'];
  $mids[$row['Id']] = $row['Name'];
}

foreach ( getSkinIncludes('includes/control_functions.php') as $includeFile )
  require_once $includeFile;

$monitor = new ZM\Monitor($mid);

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Control'));
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" data-on-click="closeWindow"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('Control') ?></h2>
      <div id="headerControl">
        <form name="contentForm" id="contentForm" method="get" action="?">
          <input type="hidden" name="view" value="<?php echo $view ?>"/>
          <?php echo buildSelect('mid', $mids, 'this.form.submit();'); ?>
        </form>
      </div>
    </div>
    <div id="content">
      <div id="ptzControls" class="ptzControls">
      <?php echo ptzControls($monitor) ?>
      </div>
    </div>
  </div>
</body>
</html>
