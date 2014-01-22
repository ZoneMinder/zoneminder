<?php
//
// ZoneMinder web monitor groups file, $Date: 2008-09-26 10:47:20 +0100 (Fri, 26 Sep 2008) $, $Revision: 2632 $
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

if ( !canView( 'System' ) )
{
    $view = "error";
    return;
}

$sql = "select * from Groups order by Name";
$groups = array();
$selected = false;
foreach( dbFetchAll( $sql ) as $row )
{
    if ( !empty($_COOKIE['zmGroup']) && ($row['Id'] == $_COOKIE['zmGroup']) )
    {
        $row['selected'] = true;
        $selected = true;
    }
    else
    {
        $row['selected'] = false;
    }
    $groups[] = $row;
}

xhtmlHeaders(__FILE__, $SLANG['Groups'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['Groups'] ?></h2>
    </div>
    <div id="content">
      <form name="groupsForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="setgroup"/>
        <table id="contentTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th class="colName"><?= $SLANG['Name'] ?></th>
              <th class="colIds"><?= $SLANG['MonitorIds'] ?></th>
              <th class="colSelect"><?= $SLANG['Select'] ?></th>
            </tr>
          </thead>
          <tbody>
            <tr class="highlight">
              <td class="colName"><?= $SLANG['NoGroup'] ?></td>
              <td class="colIds"><?= $SLANG['All'] ?></td>
              <td class="colSelect"><input type="radio" name="gid" value="0"<?= !$selected?' checked="checked"':'' ?> onclick="configureButtons( this );"/></td>
            </tr>
<?php
foreach ( $groups as $group )
{
?>
            <tr>
              <td class="colName"><?= validHtmlStr($group['Name']) ?></td>
              <td class="colIds"><?= monitorIdsToNames( $group['MonitorIds'], 30 ) ?></td>
              <td class="colSelect"><input type="radio" name="gid" value="<?= $group['Id'] ?>"<?= $group['selected']?' checked="checked"':'' ?> onclick="configureButtons( this );"/></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Apply'] ?>"/>
          <input type="button" value="<?= $SLANG['New'] ?>" onclick="newGroup()"<?= canEdit('System')?'':' disabled="disabled"' ?>/>
          <input type="button" name="editBtn" value="<?= $SLANG['Edit'] ?>" onclick="editGroup( this )"<?= $selected&&canEdit('System')?'':' disabled="disabled"' ?>/>
          <input type="button" name="deleteBtn" value="<?= $SLANG['Delete'] ?>" onclick="deleteGroup( this )"<?= $selected&&canEdit('System')?'':' disabled="disabled"' ?>/>
          <input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow();"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
