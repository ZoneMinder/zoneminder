<?php
//
// ZoneMinder web monitor groups file, $Date$, $Revision$
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

if ( !canView( 'Groups' ) ) {
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

xhtmlHeaders(__FILE__, translate('Groups') );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Groups') ?></h2>
    </div>
    <div id="content">
      <form name="groupsForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="setgroup"/>
        <table id="contentTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th class="colName"><?php echo translate('Name') ?></th>
              <th class="colIds"><?php echo translate('MonitorIds') ?></th>
              <th class="colSelect"><?php echo translate('Select') ?></th>
            </tr>
          </thead>
          <tbody>
            <tr class="highlight">
              <td class="colName"><?php echo translate('NoGroup') ?></td>
              <td class="colIds"><?php echo translate('All') ?></td>
              <td class="colSelect"><input type="radio" name="gid" value="0"<?php echo !$selected?' checked="checked"':'' ?> onclick="configureButtons( this );"/></td>
            </tr>
<?php foreach ( $groups as $group ) { ?>
            <tr>
              <td class="colName"><?php echo validHtmlStr($group['Name']) ?></td>
              <td class="colIds"><?php echo monitorIdsToNames( $group['MonitorIds'], 30 ) ?></td>
              <td class="colSelect"><input type="radio" name="gid" value="<?php echo $group['Id'] ?>"<?php echo $group['selected']?' checked="checked"':'' ?> onclick="configureButtons( this );"/></td>
            </tr>
<?php } ?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?php echo translate('Apply') ?>"/>
          <input type="button" value="<?php echo translate('New') ?>" onclick="newGroup()"<?php echo canEdit('Groups')?'':' disabled="disabled"' ?>/>
          <input type="button" name="editBtn" value="<?php echo translate('Edit') ?>" onclick="editGroup( this )"<?php echo $selected&&canEdit('Groups')?'':' disabled="disabled"' ?>/>
          <input type="button" name="deleteBtn" value="<?php echo translate('Delete') ?>" onclick="deleteGroup( this )"<?php echo $selected&&canEdit('Groups')?'':' disabled="disabled"' ?>/>
          <input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow();"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
