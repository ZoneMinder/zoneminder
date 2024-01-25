<?php
//
// ZoneMinder web monitors view file, $Date$, $Revision$
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

$monitors = ZM\Monitor::find(array('Id' => $_REQUEST['mids']));
$monitor = $monitors[0];
$servers = ZM\Server::find();
$ServersById = array();
foreach ( $servers as $S ) {
  $ServersById[validCardinal($S->Id())] = $S;
}
$storage_areas = ZM\Storage::find();
$StorageById = array();
foreach ( $storage_areas as $S ) {
  $StorageById[validCardinal($S->Id())] = $S;
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Monitors'));
getBodyTopHTML();
echo getNavBarHTML();

?>
  <div id="page">
    <h2 class="pt-2"><?php echo translate('Monitors') ?></h2>
    <div id="content">
      <div class="Monitors">
        <div class="Instructions">
          The following monitors will have these settings updated when you click Save:<br/><br/>
          <?php echo implode(', ', array_map(function($m){return '<a href="?view=monitor&mid='.validCardinal($m->Id()).'">'.htmlspecialchars($m->Id().' ' .$m->Name()).'</a>';}, $monitors)); ?>
        </div>
        <div class="Settings">

      <form name="contentForm" id="contentForm" method="post" action="?" onsubmit="$j('#contentButtons').hide();return true;">
        <input type="hidden" name="view" value="monitors"/>
        <input type="hidden" name="action" value="save"/>
        <input type="hidden" name="object" value="Monitor"/>
<?php
  echo implode(
    "\n",
    array_map(function($m){
      return '<input type="hidden" name="mids[]" value="'.validCardinal($m->Id()).'"/>';
    }, $monitors)
  );
  if ( count($ServersById) > 0 ) { ?>
        <p class="Server"><label><?php echo translate('Server')?></label>
        <?php echo htmlSelect('newMonitor[ServerId]', array(''=>'None')+$ServersById, $monitor->ServerId()); ?>
        </p>
<?php
  }
  if ( count($StorageById) > 0 ) {
?>
        <p class="Storage"><label><?php echo translate('Storage')?></label>
        <?php echo htmlSelect('newMonitor[StorageId]', array(''=>'All')+$StorageById, $monitor->StorageId()); ?>
        </p>
<?php
  }
?>
        <p class="Capturing"><label><?php echo translate('Capturing') ?></label>
<?php
  echo htmlSelect('newMonitor[Capturing]', ZM\Monitor::getCapturingOptions(), $monitor->Capturing());
?>
        </p>
        <p class="Analysing"><label><?php echo translate('Analysing') ?></label>
<?php
  echo htmlSelect('newMonitor[Analysing]', ZM\Monitor::getAnalysingOptions(), $monitor->Analysing());
?>
        </p>
        <p class="Recording"><label><?php echo translate('Recording') ?></label>
<?php
  echo htmlSelect('newMonitor[Recording]', ZM\Monitor::getRecordingOptions(), $monitor->Recording());
?>
        </p>
<!--
        <p>
          <label for="newMonitor[Enabled]"><?php echo translate('Enabled') ?></label>
          <input type="checkbox" name="newMonitor[Enabled]" id="newMonitor[Enabled]" value="1"<?php if ( !empty($monitors[0]->Enabled()) ) { ?> checked="checked"<?php } ?>/>
        </p>
-->
        <p>
          <label for="newMonitor[Importance]"><?php echo translate('Importance'); echo makeHelpLink('OPTIONS_IMPORTANCE') ?></label>
<?php
      echo htmlselect('newMonitor[Importance]',
              array(
                'Normal'=>translate('Normal'),
                'Less'=>translate('Less important'),
                'Not'=>translate('Not important')
              ), $monitor->Importance());
?>
        </p>
        <div id="contentButtons">
          <button type="submit" value="Save"><?php echo translate('Save') ?></button>
          <button type="button" data-on-click="backWindow"><?php echo translate('Cancel') ?></button>
        </div>
        </div><!--settings-->
      </form>
</div><!--Monitors-->
    </div>
  </div>
<?php xhtmlFooter() ?>
