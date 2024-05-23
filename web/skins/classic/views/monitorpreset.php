<?php
//
// ZoneMinder web monitor preset view file, $Date$, $Revision$
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

if (!canEdit('Monitors')) {
  $view = 'error';
  return;
}
$mid = isset($_REQUEST['mid']) ? validInt($_REQUEST['mid']) : 0;

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('MonitorPreset') );
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page">
    <h2><?php echo translate('MonitorPreset') ?></h2>
    <div id="content">
      <form name="contentForm" id="monitorPresetForm" method="post" action="?">
        <input type="hidden" name="view" value="monitor"/>
        <input type="hidden" name="mid" value="<?php echo $mid ?>"/>
        <p>
          <?php echo translate('MonitorPresetIntro') ?>
        </p>
        <p>
          <label for="preset"><?php echo translate('Preset') ?></label>
          <?php
$presets = array();
$presets[0] = translate('ChoosePreset');
foreach (dbFetchAll('SELECT Id,Name FROM MonitorPresets ORDER BY Name') as $preset) {
  $presets[$preset['Id']] = htmlentities( $preset['Name'] );
}
 echo buildSelect('preset', $presets); ?>
        </p>
        <div id="contentButtons">
          <button type="submit" name="saveBtn" value="preset" disabled="disabled"><?php echo translate('Save') ?></button>
          <button type="button" data-on-click="backWindow"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
<?php xhtmlFooter() ?>
