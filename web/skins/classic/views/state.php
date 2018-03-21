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

if ( !canEdit( 'System' ) ) {
  $view = 'error';
  return;
}
$running = daemonCheck();

$states = dbFetchAll( 'SELECT * FROM States' );
$focusWindow = true;

xhtmlHeaders(__FILE__, translate('RunState') );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('RunState') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
<?php
if ( empty($_REQUEST['apply']) ) {
?>
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value=""/>
        <input type="hidden" name="apply" value="1"/>
        <p>
          <select name="runState" onchange="checkState(this);">
<?php
    if ( $running ) {
?>
            <option value="stop" selected="selected"><?php echo translate('Stop') ?></option>
            <option value="restart"><?php echo translate('Restart') ?></option>
<?php
    } else {
?>
            <option value="start" selected="selected"><?php echo translate('Start') ?></option>
<?php
    }
?>
<?php
    foreach ( $states as $state ) {
?>
            <option value="<?php echo $state['Name'] ?>"><?php echo $state['Name'] ?></option>
<?php
    }
?>
          </select>
        </p>
        <table id="contentTable" class="minor" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('NewState') ?></th>
              <td><input type="text" name="newState" value="" size="16" oninput="checkState(this);" onchange="checkState(this);"/></td>
            </tr>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?php echo translate('Apply') ?>"/>
          <input type="button" name="saveBtn" value="<?php echo translate('Save') ?>" disabled="disabled" onclick="saveState( this );"/>
          <input type="button" name="deleteBtn" value="<?php echo translate('Delete') ?>" disabled="disabled" onclick="deleteState( this );"/> 
          <input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow()"/>
        </div>
<?php
} else {
?>
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="state"/>
        <input type="hidden" name="runState" value="<?php echo validHtmlStr($_REQUEST['runState']) ?>"/>
        <p><?php echo translate('ApplyingStateChange') ?></p>
        <p><?php echo translate('PleaseWait') ?></p>
<?php
}
?>
      </form>
    </div>
  </div>
</body>
</html>
