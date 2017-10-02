<?php
//
// ZoneMinder web group detail view file, $Date$, $Revision$
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

if ( !canEdit( 'Groups' ) ) {
  $view = 'error';
  return;
}

if ( !empty($_REQUEST['gid']) ) {
  $newGroup = dbFetchGroup( $_REQUEST['gid'] );
} else {
  $newGroup = array(
    'Id' => '',
    'Name' => 'New Group',
    'ParentId'  =>  '',
    'MonitorIds' => ''
  );
}

xhtmlHeaders( __FILE__, translate('Group').' - '.$newGroup['Name'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Group') ?> - <?php echo $newGroup['Name'] ?></h2>
    </div>
    <div id="content">
      <form name="groupForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="group"/>
        <input type="hidden" name="gid" value="<?php echo $newGroup['Id'] ?>"/>
        <table id="contentTable" class="major">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('Name') ?></th>
              <td><input type="text" name="newGroup[Name]" value="<?php echo validHtmlStr($newGroup['Name']) ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('ParentGroup') ?></th>
              <td>
                <select name="newGroup[ParentId]" onchange="configureButtons(this);"><option value="">None</option>
<?php
  $groups = dbFetchAll( 'SELECT Id,Name from Groups WHERE Id!=? ORDER BY Name', null, array($newGroup['Id']) );
  foreach ( $groups as $group ) {
?>
                  <option value="<?php echo $group['Id'] ?>"<?php if ( $group['Id'] == $newGroup['ParentId'] ) { ?> selected="selected"<?php } ?>><?php echo validHtmlStr($group['Name']) ?></option>
<?php
  }
?>
                </select>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Monitor') ?></th>
              <td>
                <select name="newGroup[MonitorIds][]" size="4" multiple="multiple" onchange="configureButtons(this);">
<?php
  $monitors = dbFetchAll( 'SELECT Id,Name FROM Monitors ORDER BY Sequence ASC' );
  $monitorIds = array_flip( explode( ',', $newGroup['MonitorIds'] ) );
  foreach ( $monitors as $monitor ) {
    if ( visibleMonitor( $monitor['Id'] ) ) {
?>
                  <option value="<?php echo $monitor['Id'] ?>"<?php if ( array_key_exists( $monitor['Id'], $monitorIds ) ) { ?> selected="selected"<?php } ?>><?php echo validHtmlStr($monitor['Name']) ?></option>
<?php
    }
  }
?>
                </select>
              </td>
            </tr>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" name="saveBtn" value="<?php echo translate('Save') ?>" disabled="disabled" />
          <input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
