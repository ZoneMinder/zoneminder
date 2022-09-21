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

$selfEdit = ZM_USER_SELF_EDIT && ($_REQUEST['uid'] == $user['Id']);

if (!canEdit('System') && !$selfEdit) {
  $view = 'error';
  return;
}

require_once('includes/User.php');
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
    <div class="w-100">
      <div class="float-left pl-3 pt-1">
        <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      </div>
      <div class="w-100 pt-2">
        <h2><?php echo translate('User').' - '.validHtmlStr($User->Username()); ?></h2>
      </div>
    </div>
    <div id="content" class="row justify-content-center">
      <form id="contentForm" name="contentForm" method="post" action="?view=user">
        <input type="hidden" name="redirect" value="<?php echo isset($_REQUEST['prev']) ? $_REQUEST['prev'] : 'options&tab=users' ?>"/>
        <input type="hidden" name="uid" value="<?php echo validHtmlStr($_REQUEST['uid']) ?>"/>
        <div class="BasicInformation">

        <table id="contentTable" class="table">
          <tbody>
<?php
if (canEdit('System')) {
?>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Username') ?></th>
              <td><input type="text" name="user[Username]" pattern="[A-Za-z0-9 .@]+" value="<?php echo validHtmlStr($User->Username()); ?>"<?php echo $User->Username() == 'admin' ? ' readonly="readonly"':''?>/></td>
            </tr>
<?php
}
?>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('NewPassword') ?></th>
              <td><input type="password" name="user[Password]" autocomplete="new-password"/></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('ConfirmPassword') ?></th>
              <td><input type="password" name="conf_password" autocomplete="new-password"/></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Language') ?></th>
              <td><?php echo htmlSelect('user[Language]', $langs, $User->Language()) ?></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Home View') ?></th>
              <td><input type="text" name="user[HomeView]" value="<?php echo validHtmlStr($User->HomeView()); ?>"/></td>
            </tr>
<?php
if (canEdit('System')) {
?>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Enabled') ?></th>
              <td><?php echo htmlSelect('user[Enabled]', $yesno, $User->Enabled()) ?></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('MaxBandwidth') ?></th>
              <td><?php echo htmlSelect('user[MaxBandwidth]', $bandwidths, $User->MaxBandwidth()) ?></td>
            </tr>
<?php
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
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Stream') ?></th>
              <td><?php echo htmlSelect('user[Stream]', $nv, $User->Stream()) ?></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Events') ?></th>
              <td><?php echo htmlSelect('user[Events]', $nve, $User->Events()) ?></td>
            </tr>
<?php if (defined('ZM_FEATURES_SNAPSHOTS') and ZM_FEATURES_SNAPSHOTS) { ?>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Snapshots') ?></th>
              <td><?php echo htmlSelect('user[Snapshots]', $nve, $User->Snapshots()) ?></td>
            </tr>
<?php } ?>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Control') ?></th>
              <td><?php echo htmlSelect('user[Control]', $nve, $User->Control()) ?></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Monitors') ?></th>
              <td><?php echo htmlSelect('user[Monitors]', $nve, $User->Monitors()) ?></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Groups') ?></th>
              <td><?php echo htmlSelect('user[Groups]', $nve, $User->Groups()) ?></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('System') ?></th>
              <td><?php echo htmlSelect('user[System]', $nve, $User->System()) ?></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('Devices') ?></th>
              <td><?php echo htmlSelect('user[Devices]', $nve, $User->Devices()) ?></td>
            </tr>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('RestrictedMonitors') ?></th>
              <td>
<?php
  // explode returns an array with an empty element, so test for a value first
  echo htmlSelect('user[MonitorIds][]', $monitors,
    ($User->MonitorIds() ? explode(',', $User->MonitorIds()) : array()),
    array('multiple'=>'multiple'));
?>
              </td>
            </tr>
<?php if (ZM_OPT_USE_API) { ?>
            <tr>
              <th class="text-right" scope="row"><?php echo translate('APIEnabled')?></th>
              <td><?php echo htmlSelect('user[APIEnabled]', $yesno, $User->APIEnabled()) ?></td>
            </tr>

<?php
      } // end if ZM_OPT_USE_API
?>
          </tbody>
        </table>
      </div><!--Permissions-->
<?php
if (canEdit('Groups')) {
  $Groups = array();
  foreach ( ZM\Group::find() as $Group ) {
    $Groups[$Group->Id()] = $Group;
  }

  $max_depth = 0;
  # This  array is indexed by parent_id
  $children = array();
  foreach ( $Groups as $id=>$Group ) {
    if ( ! isset( $children[$Group->ParentId()] ) )
      $children[$Group->ParentId()] = array();
    $children[$Group->ParentId()][] = $Group;
    if ( $max_depth < $Group->depth() )
      $max_depth = $Group->depth();
  }

  $permissions = array();
  foreach (ZM\Group_Permission::find(array('UserId'=>$User->Id())) as $P) {
    $permissions[$p->GroupId()] = $P;
  }
?>
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
  function group_line($Group) {
    global $children;
    global $max_depth;
    global $nve;
    global $permissions;
    $html = '<tr>';
    $html .= str_repeat('<td class="name">&nbsp;</td>', $Group->depth());
    $html .= '<td class="name" colspan="'.($max_depth-($Group->depth()-1)).'">';
    $html .= '<a href="#" data-on-click-this="editGroup" data-group-id="'.$Group->Id().'">'.validHtmlStr($Group->Id().' '.$Group->Name()).'</a>';
    $html .= '</td><td class="monitors">'. validHtmlStr(monitorIdsToNames($Group->MonitorIds(), 30)).'</td>';
    $html .= '<td class="permission">'.html_radio('group['.$Group->Id().']', $nve, isset($permissions[$Group->Id()]) ? $permissions[$Group->Id()] : 'None').'</td>';
    $html .= '</tr>';
    if (isset($children[$Group->Id()])) {
      foreach ($children[$Group->Id()] as $G) {
        $html .= group_line($G);
      }
    }
    return $html;
  }
  if (isset($children[null])) {
    foreach ($children[null] as $Group) {
      echo group_line($Group);
    }
  }
?>
          </tbody>
        </table>
<?php
  } // end if canEdit(Groups)
} // end if canEdit(System)
?>
      <div id="contentButtons">
        <button type="submit" name="action" value="Save"><?php echo translate('Save') ?></button>
        <button type="button" data-on-click="backWindow"><?php echo translate('Cancel') ?></button>
      </div>
    </form>
  </div>
</div>
<?php xhtmlFooter() ?>
