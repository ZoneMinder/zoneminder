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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

if ( !canEdit( 'System' ) )
{
    $view = "error";
    return;
}

if ( !empty($_REQUEST['gid']) )
{
    $newGroup = dbFetchGroup( $_REQUEST['gid'] );
}
else
{
    $newGroup = array(
        "Id" => "",
        "Name" => "New Group",
        "MonitorIds" => ""
    );
}

xhtmlHeaders( __FILE__, $SLANG['Group']." - ".$newGroup['Name'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['Group'] ?> - <?= $newGroup['Name'] ?></h2>
    </div>
    <div id="content">
      <form name="groupForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="action" value="group"/>
        <input type="hidden" name="gid" value="<?= $newGroup['Id'] ?>"/>
        <table id="contentTable" class="major" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row"><?= $SLANG['Name'] ?></th>
              <td><input type="text" name="newGroup[Name]" value="<?= validHtmlStr($newGroup['Name']) ?>"/></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['MonitorIds'] ?></th>
              <td>
                <select name="newGroup[MonitorIds][]" size="4" multiple="multiple">
<?php
    $monitors = dbFetchAll( "select Id,Name from Monitors order by Sequence asc" );
    $monitorIds = array_flip( explode( ',', $newGroup['MonitorIds'] ) );
    foreach ( $monitors as $monitor )
    {
        if ( visibleMonitor( $monitor['Id'] ) )
        {
?>
                  <option value="<?= $monitor['Id'] ?>"<?php if ( array_key_exists( $monitor['Id'], $monitorIds ) ) { ?> selected="selected"<?php } ?>><?= validHtmlStr($monitor['Name']) ?></option>
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
          <input type="submit" value="<?= $SLANG['Save'] ?>"<?php if ( !canEdit( 'System' ) ) { ?> disabled="disabled"<?php } ?>/>
          <input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
