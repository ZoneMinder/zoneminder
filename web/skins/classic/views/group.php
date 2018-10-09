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

if ( !canEdit('Groups') ) {
  $view = 'error';
  return;
}

if ( !empty($_REQUEST['gid']) ) {
  $newGroup = new Group($_REQUEST['gid']);
} else {
  $newGroup = new Group();
}

xhtmlHeaders(__FILE__, translate('Group').' - '.$newGroup->Name());
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Group') ?> - <?php echo $newGroup->Name() ?></h2>
    </div>
    <div id="content">
      <form name="groupForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="group"/>
        <input type="hidden" name="gid" value="<?php echo $newGroup->Id() ?>"/>
        <table id="contentTable" class="major">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('Name') ?></th>
              <td><input type="text" name="newGroup[Name]" value="<?php echo validHtmlStr($newGroup->Name()) ?>" oninput="configureButtons(this);"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('ParentGroup') ?></th>
              <td>
<?php
$Groups = array();
foreach ( Group::find() as $Group ) {
  $Groups[$Group->Id()] = $Group;
}

# This  array is indexed by parent_id
$children = array();

foreach ( $Groups as $id=>$Group ) {
  if ( $Group->ParentId() != null ) {
    if ( ! isset( $children[$Group->ParentId()] ) )
      $children[$Group->ParentId()] = array();
    $children[$Group->ParentId()][] = $Group;
  }
}

function get_Id( $G ) {
  return $G->Id();
}

function get_children($Group) {
  global $children;

  $kids = array();
  if ( isset( $children[$Group->Id()] ) ) {
    $kids += array_map('get_Id', $children[$Group->Id()]);
    foreach ( $children[$Group->Id()] as $G ) {
      foreach ( get_children($G) as $id ) {
        $kids[] = $id;
      }
    }
  }
  return $kids;
}

$kids = get_children($newGroup);
if ( $newGroup->Id() )
  $kids[] = $newGroup->Id();
$sql = 'SELECT Id,Name from Groups'.(count($kids)?' WHERE Id NOT IN ('.implode(',',array_map(function(){return '?';}, $kids)).')' : '').' ORDER BY Name';
$options = array(''=>'None');
foreach ( dbFetchAll($sql, null, $kids) as $option ) {
  $options[$option['Id']] = str_repeat('&nbsp;&nbsp;', $Groups[$option['Id']]->depth()) . $option['Name'];
}
echo htmlSelect('newGroup[ParentId]', $options, $newGroup->ParentId(), array('onchange'=>'configureButtons(this);'));
?>
              </td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Monitor') ?></th>
              <td>
                <select name="newGroup[MonitorIds][]" class="chosen" multiple="multiple" onchange="configureButtons(this);">
<?php
  $monitors = dbFetchAll('SELECT Id,Name FROM Monitors ORDER BY Sequence ASC');
  $monitorIds = $newGroup->MonitorIds();
  foreach ( $monitors as $monitor ) {
    if ( visibleMonitor($monitor['Id']) ) {
?>
                  <option value="<?php echo $monitor['Id'] ?>"<?php if ( in_array( $monitor['Id'], $monitorIds ) ) { ?> selected="selected"<?php } ?>><?php echo validHtmlStr($monitor['Name']) ?></option>
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
          <button type="submit" name="saveBtn" value="Save"<?php $newGroup->Id() ? '' : ' disabled="disabled"'?>>
          <?php echo translate('Save') ?>
          </button>
          <input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
  <script type="text/javascript">
  $j('.chosen').chosen();
  </script>
</html>
