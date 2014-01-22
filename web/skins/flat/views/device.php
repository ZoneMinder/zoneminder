<?php
//
// ZoneMinder web device detail view file, $Date: 2008-09-26 10:47:20 +0100 (Fri, 26 Sep 2008) $, $Revision: 2632 $
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

if ( !canEdit( 'Devices' ) )
{
    $view = "error";
    return;
}
if ( !empty($_REQUEST['did']) )
{
    $sql = "select * from Devices where Id = '".dbEscape($_REQUEST['did'])."'";
    $newDevice = dbFetchOne( $sql );
}
else
{
    $newDevice = array(
        "Id" => "",
        "Name" => "New Device",
        "KeyString" => ""
    );
}

xhtmlHeaders( __FILE__, $SLANG['Device']." - ".$newDevice['Name'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['Device']." - ".validHtmlStr($newDevice['Name']) ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="action" value="device"/>
        <input type="hidden" name="did" value="<?= $newDevice['Id'] ?>"/>
        <table id="contentTable" class="major" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row"><?= $SLANG['Name'] ?></th>
              <td><input type="text" name="newDevice[Name]" value="<?= validHtmlStr($newDevice['Name']) ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['KeyString'] ?></th>
              <td><input type="text" name="newDevice[KeyString]" value="<?= validHtmlStr($newDevice['KeyString']) ?>"/></td>
            </tr>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Save'] ?>"<?php if ( !canEdit( 'Devices' ) ) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
