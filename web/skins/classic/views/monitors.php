<?php
//
// ZoneMinder web function view file, $Date$, $Revision$
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
  $ServersById[$S->Id()] = $S;
}
$storage_areas = ZM\Storage::find();
$StorageById = array();
foreach ( $storage_areas as $S ) {
  $StorageById[$S->Id()] = $S;
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Function'));
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Function') ?></h2>
    </div>
    <div id="content">
The following monitors will have these settings update when you click Save:<br/><br/>
      <?php echo implode('<br/>', array_map(function($m){return $m->Id().' ' .$m->Name();}, $monitors)); ?>
      <form name="contentForm" id="contentForm" method="post" action="?" onsubmit="$j('#contentButtons').hide();return true;">
        <input type="hidden" name="view" value="monitors"/>
        <input type="hidden" name="action" value="save"/>
        <input type="hidden" name="object" value="Monitor"/>
<?php
  echo implode(
    "\n",
    array_map(function($m){
      return '<input type="hidden" name="mids[]" value="'.$m->Id().'"/>';
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
        <p class="Function"><label><?php echo translate('Function') ?></label>
<?php
  $options = array();
  foreach ( getEnumValues('Monitors', 'Function') as $opt ) {
    $options[$opt] = translate('Fn'.$opt);
  }
  echo htmlSelect('newMonitor[Function]', $options, $monitor->Function());
?>
        </p>
        <p>
          <label for="newMonitor[Enabled]"><?php echo translate('Enabled') ?></label>
          <input type="checkbox" name="newMonitor[Enabled]" id="newMonitor[Enabled]" value="1"<?php if ( !empty($monitors[0]->Enabled()) ) { ?> checked="checked"<?php } ?>/>
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
