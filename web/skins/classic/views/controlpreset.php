<?php
//
// ZoneMinder web run state view file, $Date$, $Revision$
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

if ( !canEdit('Monitors') ) {
  $view = 'error';
  return;
}

$monitor = dbFetchOne('SELECT C.*,M.* FROM Monitors AS M INNER JOIN Controls AS C ON (M.ControlId = C.Id ) WHERE M.Id = ?', NULL, array( $_REQUEST['mid']) );

$labels = array();
foreach( dbFetchAll( 'SELECT * FROM ControlPresets WHERE MonitorId = ?', NULL, array( $monitor['Id'] ) ) as $row ) {
  $labels[$row['Preset']] = $row['Label'];
}

$presets = array();
for ( $i = 1; $i <= $monitor['NumPresets']; $i++ ) {
  $presets[$i] = translate('Preset').' '.$i;
  if ( !empty($labels[$i]) ) {
    $presets[$i] .= ' ('.validHtmlStr($labels[$i]).')';
  }
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('SetPreset') );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('SetPreset') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="?">
        <input type="hidden" name="view" value="control"/>
        <input type="hidden" name="mid" value="<?php echo $monitor['Id'] ?>"/>
        <input type="hidden" name="action" value="control"/>
        <input type="hidden" name="control" value="presetSet"/>
        <input type="hidden" name="showControls" value="1"/>
        <p><?php echo buildSelect('preset', $presets, 'updateLabel()' ) ?></p>
        <p>
          <label for="newLabel"><?php echo translate('NewLabel') ?></label>
          <input type="text" name="newLabel" id="newLabel" value=""/>
        </p>
        <div id="contentButtons">
          <button type="submit" value="Save"><?php echo translate('Save') ?></button>
          <button type="button" data-on-click="closeWindow"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
