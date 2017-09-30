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
  $view = 'error';
  return;
}

$sql = 'SELECT * FROM Groups ORDER BY Name';
$root_groups = array();
$sub_groups = array();
$selected = false;
foreach( dbFetchAll( $sql ) as $row ) {
  if ( !empty($_COOKIE['zmGroup']) && ($row['Id'] == $_COOKIE['zmGroup']) ) {
    $row['selected'] = true;
    $selected = true;
  } else {
    $row['selected'] = false;
  }
  if ( $row['ParentId'] ) {
    if ( ! isset( $sub_groups[$row['ParentId']] ) ) {
      $sub_groups[$row['ParentId']] = array();
    }
    $sub_groups[$row['ParentId']][] = $row;
  } else {
    $root_groups[] = $row;
  }
}

xhtmlHeaders(__FILE__, translate('Groups') );
?>
<body>
  <div id="page">
    <?php echo $navbar = getNavBarHTML(); ?>
    <div id="content">
      <form name="groupsForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="setgroup"/>
        <table id="contentTable" class="major" cellspacing="0">
          <thead>
            <tr>
              <th class="colName"><?php echo translate('Name') ?></th>
              <th class="colSubGroups"><?php echo translate('Subgroup') ?></th>
              <th class="colIds"><?php echo translate('Monitors') ?></th>
              <th class="colSelect"><?php echo translate('Mark') ?></th>
            </tr>
          </thead>
          <tbody>
<?php
foreach ( $root_groups as $group ) {
?>
            <tr>
              <td class="colName" colspan="2">
<?php
  if ( canEdit('Groups') ) {
    echo '<a href="#" onclick="editGroup('.$group['Id'].');">'. validHtmlStr($group['Name']).'</a>';
  } else {
    echo validHtmlStr($group['Name']);
  }
?></td>
              <td class="colIds"><?php echo monitorIdsToNames( $group['MonitorIds'], 30 ) ?></td>
              <td class="colSelect"><input type="checkbox" name="gid" value="<?php echo $group['Id'] ?>"<?php echo $group['selected']?' checked="checked"':'' ?> onclick="configureButtons(this);"/></td>
            </tr>
<?php 
  if ( isset( $sub_groups[$group['Id']] ) ) {
    foreach ( $sub_groups[$group['Id']] as $group ) {
?>
            <tr>
              <td>&nbsp;</td>
              <td class="colName">
<?php
  if ( canEdit('Groups') ) {
    echo '<a href="#" onclick="editGroup(this);">'. validHtmlStr($group['Name']).'</a>';
  } else {
    echo validHtmlStr($group['Name']);
  }
?></td>
              <td class="colIds"><?php echo monitorIdsToNames( $group['MonitorIds'], 30 ) ?></td>
              <td class="colSelect"><input type="checkbox" name="gid" value="<?php echo $group['Id'] ?>"<?php echo $group['selected']?' checked="checked"':'' ?> onclick="configureButtons(this);"/></td>
            </tr>
<?php 
    } # end foreach subgroup
  } # end if has subgroups
} # end foreach root group
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="button" value="<?php echo translate('New') ?>" onclick="newGroup();"<?php echo canEdit('Groups')?'':' disabled="disabled"' ?>/>
          <input type="button" name="deleteBtn" value="<?php echo translate('Delete') ?>" onclick="deleteGroup(this);"<?php echo $selected&&canEdit('Groups')?'':' disabled="disabled"' ?>/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
