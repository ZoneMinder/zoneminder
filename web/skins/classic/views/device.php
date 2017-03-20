<?php
//
// ZoneMinder web device detail view file, $Date$, $Revision$
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

if ( !canEdit( 'Devices' ) )
{
    $view = "error";
    return;
}
if ( !empty($_REQUEST['did']) ) {
    $newDevice = dbFetchOne( 'SELECT * FROM Devices WHERE Id = ?', NULL, array($_REQUEST['did']) );
} else {
    $newDevice = array(
        "Id" => "",
        "Name" => "New Device",
        "KeyString" => ""
    );
}

xhtmlHeaders( __FILE__, translate('Device')." - ".$newDevice['Name'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Device')." - ".validHtmlStr($newDevice['Name']) ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="device"/>
        <input type="hidden" name="did" value="<?php echo $newDevice['Id'] ?>"/>
        <table id="contentTable" class="major" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('Name') ?></th>
              <td><input type="text" name="newDevice[Name]" value="<?php echo validHtmlStr($newDevice['Name']) ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('KeyString') ?></th>
              <td><input type="text" name="newDevice[KeyString]" value="<?php echo validHtmlStr($newDevice['KeyString']) ?>"/></td>
            </tr>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?php echo translate('Save') ?>"<?php if ( !canEdit( 'Devices' ) ) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
