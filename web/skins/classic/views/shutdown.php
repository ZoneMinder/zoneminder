<?php
//
// ZoneMinder web shutdown view file
// Copyright (C) 2019 ZoneMinder LLC
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

if ( !canEdit('System') ) {
  $view = 'error';
  return;
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Shutdown').' '.translate('Restart'));
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Shutdown').' '.translate('Restart') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="?">
        <input type="hidden" name="view" value="shutdown"/>
<?php
  if ( isset($output) ) {
    echo '<p>'.implode('<br/>', $output).'</p>';
  }
  if ( isset($_POST['when']) and ($_POST['when'] != 'NOW') and ($action != 'cancel') ) {
    echo '<p>You may cancel this shutdown by clicking '.translate('Cancel').'</p>';
  }
?>
        <p class="warning"><h2>Warning</h2>
          This command will either shutdown or restart all ZoneMinder Servers<br/>
        </p>
        <p>
          <input type="radio" name="when" value="now" id="whennow"/><label for="whennow">Now</label>
          <input type="radio" name="when" value="1min" id="when1min" checked="checked"/><label for="when1min">1 Minute</label>
        </p>
        <div id="contentButtons">
<?php 
  if ( isset($_POST['when']) and ($_POST['when'] != 'NOW') and ($action != 'cancel') ) {
?>
          <button type="submit" name="action" value="cancel"><?php echo translate('Cancel') ?></button>
<?php 
  }
?>
          <button type="submit" name="action" value="restart"><?php echo translate('Restart') ?></button>
          <button type="submit" name="action" value="shutdown"><?php echo translate('Shutdown') ?></button>
          <button type="button" data-on-click="closeWindow"><?php echo translate('Close') ?></button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
