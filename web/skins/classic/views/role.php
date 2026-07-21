<?php
//
// ZoneMinder web role view file
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

if (!canEdit('System')) {
  $view = 'error';
  return;
}

require_once('includes/User_Role.php');
require_once('includes/Group.php');
require_once('includes/Role_Group_Permission.php');

if (isset($_REQUEST['rid']) and $_REQUEST['rid']) {
  if (!($Role = new ZM\User_Role(validCardinal($_REQUEST['rid'])))) {
    $view = 'error';
    return;
  }
} else {
  $Role = new ZM\User_Role();
}

$nv = array('None'=>translate('None'), 'View'=>translate('View'));
$nve = array('None'=>translate('None'), 'View'=>translate('View'), 'Edit'=>translate('Edit'));
$nvec = array('None'=>translate('None'), 'View'=>translate('View'), 'Edit'=>translate('Edit'), 'Create'=>translate('Create'));
$inve = array('Inherit'=>translate('Inherit'), 'None'=>translate('None'), 'View'=>translate('View'), 'Edit'=>translate('Edit'));

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Role').' - '.$Role->Name());
echo getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page">
    <div id="content">
      <form id="contentForm" name="contentForm" method="post" action="?view=role">
        <input type="hidden" name="redirect" value="<?php echo isset($_REQUEST['prev']) ? htmlspecialchars($_REQUEST['prev']) : 'options&tab=roles' ?>"/>
        <input type="hidden" name="rid" value="<?php echo validHtmlStr($Role->Id()) ?>"/>
        <div id="header">
          <div class="float-left pl-3 pt-1">
            <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
            <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>"><i class="fa fa-refresh"></i></button>
          </div>
          <h2><?php echo translate('Role').' - '.validHtmlStr($Role->Name()); ?></h2>
          <div id="contentButtons">
            <button type="submit" name="action" value="Save"><?php echo translate('Save') ?></button>
            <button type="button" data-on-click="backWindow"><?php echo translate('Cancel') ?></button>
          </div>
        </div><!--header-->
        <div id="content-inner">
          <div class="row">
            <div class="col-lg-6">
              <fieldset>
                <legend><?php echo translate('Role Details') ?></legend>
                <table class="table table-sm">
                  <tbody>
                    <tr>
                      <th scope="row"><?php echo translate('Name') ?></th>
                      <td><input type="text" class="form-control form-control-sm" name="role[Name]" placeholder="<?php echo translate('NewRole') ?>" value="<?php echo validHtmlStr($Role->Name()); ?>"/></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Description') ?></th>
                      <td><textarea class="form-control form-control-sm" name="role[Description]" rows="3"><?php echo validHtmlStr($Role->Description()) ?></textarea></td>
                    </tr>
                  </tbody>
                </table>
              </fieldset>
            </div>
            <div class="col-lg-6">
              <fieldset>
                <legend><?php echo translate('Global Permissions') ?></legend>
                <table class="table table-sm">
                  <tbody>
                    <tr>
                      <th scope="row"><?php echo translate('Stream') ?></th>
                      <td><?php echo htmlSelect('role[Stream]', $nv, $Role->Stream(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Events') ?></th>
                      <td><?php echo htmlSelect('role[Events]', $nve, $Role->Events(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
<?php if (defined('ZM_FEATURES_SNAPSHOTS') and ZM_FEATURES_SNAPSHOTS) { ?>
                    <tr>
                      <th scope="row"><?php echo translate('Snapshots') ?></th>
                      <td><?php echo htmlSelect('role[Snapshots]', $nve, $Role->Snapshots(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
<?php } ?>
                    <tr>
                      <th scope="row"><?php echo translate('Control') ?></th>
                      <td><?php echo htmlSelect('role[Control]', $nve, $Role->Control(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Monitors') ?></th>
                      <td><?php echo htmlSelect('role[Monitors]', $nvec, $Role->Monitors(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Groups') ?></th>
                      <td><?php echo htmlSelect('role[Groups]', $nve, $Role->Groups(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('System') ?></th>
                      <td><?php echo htmlSelect('role[System]', $nve, $Role->System(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Devices') ?></th>
                      <td><?php echo htmlSelect('role[Devices]', $nve, $Role->Devices(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                  </tbody>
                </table>
              </fieldset>
            </div>
          </div><!--end row-->

<?php
$groups = array();
if (canEdit('Groups')) {
  foreach (ZM\Group::find() as $group) {
    $groups[$group->Id()] = $group;
  }

  $max_depth = 0;
  # This array is indexed by parent_id
  $children = array();
  foreach ($groups as $id=>$group) {
    if (!isset($children[$group->ParentId()]))
      $children[$group->ParentId()] = array();
    $children[$group->ParentId()][] = $group;
    if ($max_depth < $group->depth())
      $max_depth = $group->depth();
  }
?>
        <div id="GroupPermissions" class="mt-4">
          <fieldset>
            <legend><?php echo translate('Groups Permissions') ?></legend>
            <table class="table table-sm table-striped">
              <thead class="thead-light">
                <tr>
                  <th class="name" colspan="<?php echo $max_depth+1 ?>"><?php echo translate('Name') ?></th>
                  <th class="monitors"><?php echo translate('Monitors') ?></th>
                  <th class="permission"><?php echo translate('Permission') ?></th>
                </tr>
              </thead>
              <tbody>
<?php
  function group_line($group) {
    global $children;
    global $max_depth;
    global $inve;
    global $Role;
    $html = '<tr>';
    $html .= str_repeat('<td class="name">&nbsp;</td>', $group->depth());
    $html .= '<td class="name" colspan="'.($max_depth-($group->depth()-1)).'">';
    $html .= validHtmlStr($group->Id().' '.$group->Name());
    $html .= '</td><td class="monitors"><small class="text-muted">'. validHtmlStr(monitorIdsToNames($group->MonitorIds(), 30)).'</small></td>';
    $html .= '<td class="permission">'.html_radio('group_permission['.$group->Id().']', $inve,
      $Role->Group_Permission($group->Id())->Permission(),
      ['default'=>'Inherit']).'</td>';
    $html .= '</tr>';
    if (isset($children[$group->Id()])) {
      foreach ($children[$group->Id()] as $g) {
        $html .= group_line($g);
      }
    }
    return $html;
  }
  if (isset($children[null])) {
    foreach ($children[null] as $group) {
      echo group_line($group);
    }
  }
?>
              </tbody>
            </table>
          </fieldset>
        </div><!--Group Permissions-->
<?php
  } // end if canEdit(Groups)
?>
        <div id="MonitorPermissions" class="mt-4">
          <fieldset>
            <legend><?php echo translate('Monitor Permissions') ?></legend>
            <table class="table table-sm table-striped">
              <thead class="thead-light">
                <tr>
                  <th class="Id"><?php echo translate('Id') ?></th>
                  <th class="Name"><?php echo translate('Name') ?></th>
                  <th class="permission"><?php echo translate('Permission') ?></th>
                </tr>
              </thead>
              <tbody>
<?php
  $monitors = ZM\Monitor::find(['Deleted'=>0], ['order'=>'Sequence ASC']);
  foreach ($monitors as $monitor) {
    echo '
                <tr>
                  <td class="Id">'.$monitor->Id().'</td>
                  <td class="Name">'.validHtmlStr($monitor->Name()).'</td>
                  <td class="permission">'.html_radio('monitor_permission['.$monitor->Id().']', $inve,
                    $Role->Monitor_Permission($monitor->Id())->Permission(),
                    ['default'=>'Inherit']).'</td>
                </tr>';
  }
?>
              </tbody>
            </table>
          </fieldset>
        </div><!--Monitor Permissions-->

<?php if ($Role->Id()) { ?>
        <div id="AssignedUsers" class="mt-4">
          <fieldset>
            <legend><?php echo translate('Assigned Users') ?></legend>
            <table class="table table-sm table-striped">
              <thead class="thead-light">
                <tr>
                  <th class="Username"><?php echo translate('Username') ?></th>
                  <th class="Name"><?php echo translate('Name') ?></th>
                </tr>
              </thead>
              <tbody>
<?php
  $users = $Role->Users();
  if (count($users) == 0) {
    echo '<tr><td colspan="2" class="text-muted">'.translate('No users assigned to this role').'</td></tr>';
  } else {
    foreach ($users as $role_user) {
      echo '
                <tr>
                  <td class="Username">'.makeLink('?view=user&amp;uid='.$role_user->Id(), validHtmlStr($role_user->Username()), true).'</td>
                  <td class="Name">'.validHtmlStr($role_user->Name()).'</td>
                </tr>';
    }
  }
?>
              </tbody>
            </table>
          </fieldset>
        </div><!--Assigned Users-->
<?php } ?>
        </div><!--inner content-->
      </form>
    </div>
  </div>
<?php xhtmlFooter() ?>
