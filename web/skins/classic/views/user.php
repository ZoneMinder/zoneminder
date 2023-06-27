<?php
//
// ZoneMinder web user view file, $Date$, $Revision$
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

$selfEdit = ZM_USER_SELF_EDIT && ($_REQUEST['uid'] == $user->Id());

if (!canEdit('System') && !$selfEdit) {
  $view = 'error';
  return;
}

require_once('includes/User.php');
require_once('includes/Group.php');
require_once('includes/Group_Permission.php');

if (isset($_REQUEST['uid']) and $_REQUEST['uid']) {
	if ( !($User = new ZM\User($_REQUEST['uid'])) ) {
		$view = 'error';
		return;
	}
} else {
  $User = new ZM\User();
	$User->Username(translate('NewUser'));
}

$yesno = array( 0=>translate('No'), 1=>translate('Yes') );
$nv = array( 'None'=>translate('None'), 'View'=>translate('View') );
$nve = array( 'None'=>translate('None'), 'View'=>translate('View'), 'Edit'=>translate('Edit') );
$inve = array( 'Inherit'=>translate('Inherit'),'None'=>translate('None'), 'View'=>translate('View'), 'Edit'=>translate('Edit') );
$bandwidths = array_merge( array( ''=>'' ), $bandwidth_options );
$langs = array_merge( array( ''=>'' ), getLanguages() );

$sql = 'SELECT Id, Name FROM Monitors ORDER BY Sequence ASC';
$monitors = array();
foreach ( dbFetchAll($sql) as $monitor ) {
  if ( visibleMonitor($monitor['Id']) ) {
    $monitors[$monitor['Id']] = $monitor;
  }
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('User').' - '.$User->Username());
echo getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page">
    <div id="content">
      <form id="contentForm" name="contentForm" method="post" action="?view=user">
        <input type="hidden" name="redirect" value="<?php echo isset($_REQUEST['prev']) ? $_REQUEST['prev'] : 'options&tab=users' ?>"/>
        <input type="hidden" name="uid" value="<?php echo validHtmlStr($_REQUEST['uid']) ?>"/>
        <div id="header">
          <div class="float-left pl-3 pt-1">
            <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
            <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
          </div>
          <h2><?php echo translate('User').' - '.validHtmlStr($User->Username()); ?></h2>
          <div id="contentButtons">
            <button type="submit" name="action" value="Save"><?php echo translate('Save') ?></button>
            <button type="button" data-on-click="backWindow"><?php echo translate('Cancel') ?></button>
          </div>
        </div><!--header-->
        <div id="content-inner">
          <div class="BasicInformation">
            <table id="contentTable" class="table">
              <tbody>
  <?php
  if (canEdit('System')) {
  ?>
              <tr class="Username">
                <th scope="row"><?php echo translate('Username') ?></th>
                <td><input type="text" name="user[Username]" pattern="[A-Za-z0-9 .@]+" value="<?php echo validHtmlStr($User->Username()); ?>"<?php echo $User->Username() == 'admin' ? ' readonly="readonly"':''?>/></td>
              </tr>
  <?php
  }
  ?>
              <tr class="Password">
                <th scope="row"><?php echo translate('NewPassword') ?></th>
                <td><input type="password" name="user[Password]" autocomplete="new-password"/></td>
              </tr>
              <tr class="ConfirmPassword">
                <th scope="row"><?php echo translate('ConfirmPassword') ?></th>
                <td><input type="password" name="conf_password" autocomplete="new-password"/></td>
              </tr>
              <tr class="Name">
                <th scope="row"><?php echo translate('Full Name') ?></th>
                <td><input type="text" name="user[Name]" value="<?php echo $User->Name() ?>"/></td>
              </tr>
              <tr class="Email">
                <th scope="row"><?php echo translate('Email Address') ?></th>
                <td><input type="email" name="user[Email]" value="<?php echo $User->Email() ?>"/></td>
              </tr>
              <tr class="Phone">
                <th scope="row"><?php echo translate('Phone') ?></th>
                <td><input type="tel" name="user[Phone]" value="<?php echo $User->Phone() ?>"/></td>
              </tr>
              <tr class="Language">
                <th scope="row"><?php echo translate('Language') ?></th>
                <td><?php echo htmlSelect('user[Language]', $langs, $User->Language()) ?></td>
              </tr>
              <tr class="HomeView">
                <th scope="row"><?php echo translate('Home View') ?></th>
                <td>
<?php
    $homeview_options = [
      'console'=>translate('Console'),
      'events'=>'Events',
      'map'   =>  'Map',
      'montage'=>'Montage',
      'montagereview'=>'Montage Review',
      'watch' => 'Watch',
    ];
echo htmlSelect('user[HomeView]', $homeview_options, $User->HomeView());
?></td>
              </tr>
  <?php
  if (canEdit('System')) {
  ?>
              <tr class="Enabled">
                <th scope="row"><?php echo translate('Enabled') ?></th>
                <td><?php echo htmlSelect('user[Enabled]', $yesno, $User->Enabled()) ?></td>
              </tr>
              <tr class="MaxBandwidth">
                <th scope="row"><?php echo translate('MaxBandwidth') ?></th>
                <td><?php echo htmlSelect('user[MaxBandwidth]', $bandwidths, $User->MaxBandwidth()) ?></td>
              </tr>
  <?php if (ZM_OPT_USE_API) { ?>
              <tr class="APIEnabled">
                <th scope="row"><?php echo translate('APIEnabled')?></th>
                <td><?php echo htmlSelect('user[APIEnabled]', $yesno, $User->APIEnabled()) ?></td>
              </tr>

  <?php
        } // end if ZM_OPT_USE_API
  }
  ?>
            </tbody>
          </table>
        </div><!--end basic information-->
<?php
if (canEdit('System')) {
?>
        <div class="Permissions">
          <table id="contentTable" class="table">
            <tbody>
              <tr class="Stream">
                <th scope="row"><?php echo translate('Stream') ?></th>
                <td><?php echo htmlSelect('user[Stream]', $nv, $User->Stream()) ?></td>
              </tr>
              <tr class="Events">
                <th scope="row"><?php echo translate('Events') ?></th>
                <td><?php echo htmlSelect('user[Events]', $nve, $User->Events()) ?></td>
              </tr>
  <?php if (defined('ZM_FEATURES_SNAPSHOTS') and ZM_FEATURES_SNAPSHOTS) { ?>
              <tr class="Snapshots">
                <th scope="row"><?php echo translate('Snapshots') ?></th>
                <td><?php echo htmlSelect('user[Snapshots]', $nve, $User->Snapshots()) ?></td>
              </tr>
  <?php } ?>
              <tr class="Control">
                <th scope="row"><?php echo translate('Control') ?></th>
                <td><?php echo htmlSelect('user[Control]', $nve, $User->Control()) ?></td>
              </tr>
              <tr class="Monitors">
                <th scope="row"><?php echo translate('Monitors') ?></th>
                <td><?php echo htmlSelect('user[Monitors]', $nve, $User->Monitors(), ['id'=>'user[Monitors]', 'data-on-change'=>'updateEffectivePermissions']) ?></td>
              </tr>
              <tr class="Groups">
                <th scope="row"><?php echo translate('Groups') ?></th>
                <td><?php echo htmlSelect('user[Groups]', $nve, $User->Groups()) ?></td>
              </tr>
              <tr class="System">
                <th scope="row"><?php echo translate('System') ?></th>
                <td><?php echo htmlSelect('user[System]', $nve, $User->System()) ?></td>
              </tr>
              <tr class="Devices">
                <th scope="row"><?php echo translate('Devices') ?></th>
                <td><?php echo htmlSelect('user[Devices]', $nve, $User->Devices()) ?></td>
              </tr>
            </tbody>
          </table>
        </div><!--Permissions-->
      <br class="clear"/>
<?php
if (canEdit('Groups')) {
  $groups = array();
  foreach ( ZM\Group::find() as $group ) {
    $groups[$group->Id()] = $group;
  }

  $max_depth = 0;
  # This  array is indexed by parent_id
  $children = array();
  foreach ( $groups as $id=>$group ) {
    if ( ! isset( $children[$group->ParentId()] ) )
      $children[$group->ParentId()] = array();
    $children[$group->ParentId()][] = $group;
    if ( $max_depth < $group->depth() )
      $max_depth = $group->depth();
  }

?>
    <div id="GroupPermissions">
      <fieldset><legend><?php echo translate('Groups Permissions') ?></legend>
        <table id="contentTable" class="major Groups">
          <thead class="thead-highlight">
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
    global $User;
    $html = '<tr>';
    $html .= str_repeat('<td class="name">&nbsp;</td>', $group->depth());
    $html .= '<td class="name" colspan="'.($max_depth-($group->depth()-1)).'">';
    $html .= validHtmlStr($group->Id().' '.$group->Name());
    $html .= '</td><td class="monitors">'. validHtmlStr(monitorIdsToNames($group->MonitorIds(), 30)).'</td>';
    $html .= '<td class="permission">'.html_radio('group_permission['.$group->Id().']', $inve,
      $group->permission($User->Id()),
      ['default'=>'Inherit'],
      ['data-on-change'=>'updateEffectivePermissions']).'</td>';
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
<?php
  } // end if canEdit(Groups)
?>
</div><!--Group Permissions-->
<div id="MonitorPermissions">
  <fieldset><legend><?php echo translate('Monitor Permissions') ?></legend>
    <table id="contentTable" class="major Monitors">
      <thead class="thead-highlight">
        <tr>
          <th class="Id"><?php echo translate('Id') ?></th>
          <th class="Name"><?php echo translate('Name') ?></th>
          <th class="permission"><?php echo translate('Permission') ?></th>
          <th class="effective_permission"><?php echo translate('Effective Permission') ?></th>
        </tr>
      </thead>
      <tbody>
<?php
  foreach ($monitors as $m) {
    $monitor = new ZM\Monitor($m);
    echo '
<tr class="monitor">
  <td class="Id">'.$monitor->Id().'</td>
  <td class="Name">'.validHtmlStr($monitor->Name()).'</td>
  <td class="permission">'.html_radio('monitor_permission['.$monitor->Id().']', $inve,
    $User->Monitor_Permission($monitor->Id())->Permission(),
    ['default'=>'Inherit'],
    ['data-on-change'=>'updateEffectivePermissions']).'</td>
  <td class="effective_permission" id="effective_permission'.$monitor->Id().'">'.translate($monitor->effectivePermission($User)).'</td>
</tr>';
  }
?>
      </tbody>
    </table>
  </fieldset>
</div>
<?php
} // end if canEdit(System)
?>
      </div><!--inner content-->
    </form>
  </div>
</div>
<?php xhtmlFooter() ?>
