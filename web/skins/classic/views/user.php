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
	if ( !($User = new ZM\User(validCardinal($_REQUEST['uid']))) ) {
		$view = 'error';
		return;
	}
} else {
  $User = new ZM\User();
}

$yesno = array( 0=>translate('No'), 1=>translate('Yes') );
$nv = array( 'None'=>translate('None'), 'View'=>translate('View') );
$nve = array( 'None'=>translate('None'), 'View'=>translate('View'), 'Edit'=>translate('Edit') );
$nvec = array( 'None'=>translate('None'), 'View'=>translate('View'), 'Edit'=>translate('Edit'), 'Create'=>translate('Create') );
$inve = array( 'Inherit'=>translate('Inherit'),'None'=>translate('None'), 'View'=>translate('View'), 'Edit'=>translate('Edit') );
$bandwidths = array_merge( array( ''=>'' ), $bandwidth_options );
$langs = array_merge( array( ''=>'' ), getLanguages() );


$focusWindow = true;

xhtmlHeaders(__FILE__, translate('User').' - '.$User->Username());
echo getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page">
    <div id="content">
      <form id="contentForm" name="contentForm" method="post" action="?view=user">
        <input type="hidden" name="redirect" value="<?php echo isset($_REQUEST['prev']) ? htmlspecialchars($_REQUEST['prev']) : 'options&tab=users' ?>"/>
        <input type="hidden" name="uid" value="<?php echo validHtmlStr($User->Id()) ?>"/>
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
          <div class="row">
            <div class="col-lg-6">
              <fieldset>
                <legend><?php echo translate('Account Details') ?></legend>
                <table class="table table-sm">
                  <tbody>
<?php if (canEdit('System')) { ?>
                    <tr>
                      <th scope="row"><?php echo translate('Username') ?></th>
                      <td><input type="text" class="form-control form-control-sm" name="user[Username]" pattern="[A-Za-z0-9 .@]+" placeholder="<?php echo translate('NewUser') ?>" value="<?php echo validHtmlStr($User->Username()); ?>"<?php echo $User->Username() == 'admin' ? ' readonly="readonly"':''?>/></td>
                    </tr>
<?php } ?>
                    <tr>
                      <th scope="row"><?php echo translate('NewPassword') ?></th>
                      <td>
                        <div class="input-group input-group-sm">
                          <input type="password" class="form-control" name="user[Password]" id="user[Password]" autocomplete="new-password"/>
                          <div class="input-group-append">
                            <span class="input-group-text material-icons md-18" style="cursor: pointer;" data-on-click-this="toggle_password_visibility" data-password-input="user[Password]">visibility</span>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('ConfirmPassword') ?></th>
                      <td>
                        <div class="input-group input-group-sm">
                          <input type="password" class="form-control" name="conf_password" id="conf_password" autocomplete="new-password"/>
                          <div class="input-group-append">
                            <span class="input-group-text material-icons md-18" style="cursor: pointer;" data-on-click-this="toggle_password_visibility" data-password-input="conf_password">visibility</span>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Full Name') ?></th>
                      <td><input type="text" class="form-control form-control-sm" name="user[Name]" value="<?php echo validHtmlStr($User->Name()) ?>"/></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Email Address') ?></th>
                      <td><input type="email" class="form-control form-control-sm" name="user[Email]" value="<?php echo validHtmlStr($User->Email()) ?>"/></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Phone') ?></th>
                      <td><input type="tel" class="form-control form-control-sm" name="user[Phone]" value="<?php echo validHtmlStr($User->Phone()) ?>"/></td>
                    </tr>
                  </tbody>
                </table>
              </fieldset>
            </div>
            <div class="col-lg-6">
              <fieldset>
                <legend><?php echo translate('Preferences') ?></legend>
                <table class="table table-sm">
                  <tbody>
                    <tr>
                      <th scope="row"><?php echo translate('Language') ?></th>
                      <td><?php echo htmlSelect('user[Language]', $langs, $User->Language(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Home View') ?></th>
                      <td>
<?php
    $homeview_options = [
      'console'=>translate('Console'),
      'events'=> translate('Events'),
      'map'   =>  translate('Map'),
      'montage'=> translate('Montage'),
      'montagereview'=> translate('Montage Review'),
      'watch' => translate('Watch'),
    ];
echo htmlSelect('user[HomeView]', $homeview_options, $User->HomeView(), ['class'=>'form-control form-control-sm']);
?></td>
                    </tr>
<?php if (canEdit('System')) { ?>
                    <tr>
                      <th scope="row"><?php echo translate('Enabled') ?></th>
                      <td><?php echo htmlSelect('user[Enabled]', $yesno, $User->Enabled(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('MaxBandwidth') ?></th>
                      <td><?php echo htmlSelect('user[MaxBandwidth]', $bandwidths, $User->MaxBandwidth(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
<?php   if (ZM_OPT_USE_API) { ?>
                    <tr>
                      <th scope="row"><?php echo translate('APIEnabled')?></th>
                      <td><?php echo htmlSelect('user[APIEnabled]', $yesno, $User->APIEnabled(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
<?php
      } // end if ZM_OPT_USE_API
    } // end if canEdit System
?>
                  </tbody>
                </table>
              </fieldset>
            </div>
          </div><!--end row-->

<?php if (ZM_OPT_USE_API && $User->Id() && $User->APIEnabled()) {
  // Determine profile name: ZM_WEB_TITLE if not default, else ZM_HOME_URL if not default, else 'ZoneMinder'
  $profileName = 'ZoneMinder';
  if (defined('ZM_WEB_TITLE') && ZM_WEB_TITLE !== 'ZoneMinder' && ZM_WEB_TITLE !== '') {
    $profileName = ZM_WEB_TITLE;
  } else if (defined('ZM_HOME_URL') && ZM_HOME_URL !== 'https://zoneminder.com' && ZM_HOME_URL !== '') {
    $profileName = ZM_HOME_URL;
  }

  // Construct portal URL from current request
  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
  $path = dirname($_SERVER['SCRIPT_NAME']);
  $portalUrl = $protocol . '://' . $host . $path;
?>
        <div class="zmNgOnboarding mt-4">
          <fieldset>
            <legend><?php echo translate('zmNg Mobile App QR Code') ?></legend>
            <div id="zmNgQRSection" class="text-center py-3">
              <div id="zmNgQRCode" style="padding: 15px; background: white; display: inline-block; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
              <div id="zmNgQRMessage" class="text-muted mt-2 mb-3">
                <small><?php echo translate('Scan this QR code with the zmNg app to add this profile') ?></small>
              </div>
              <div class="form-inline justify-content-center">
                <label for="zmNgPassword" class="mr-2"><?php echo translate('Password') ?>:</label>
                <div class="input-group input-group-sm" style="width: 200px;">
                  <input type="password" class="form-control" id="zmNgPassword" placeholder="<?php echo translate('Optional') ?>" autocomplete="off"/>
                  <div class="input-group-append">
                    <span class="input-group-text material-icons md-18" style="cursor: pointer;" data-on-click-this="toggle_password_visibility" data-password-input="zmNgPassword">visibility</span>
                  </div>
                </div>
              </div>
              <div class="text-muted mt-2"><small><?php echo translate('Enter password to include it in the QR code') ?></small></div>
            </div>
          </fieldset>
        </div>
        <?php echo output_script('js/qrcode.min.js'); ?>
        <script nonce="<?php echo $cspNonce ?>">
        document.addEventListener('DOMContentLoaded', function() {
          var qrcode = null;
          var profileName = <?php echo json_encode($profileName) ?>;
          var portalUrl = <?php echo json_encode($portalUrl) ?>;
          var username = <?php echo json_encode($User->Username()) ?>;
          var qrContainer = document.getElementById('zmNgQRCode');
          var qrMessage = document.getElementById('zmNgQRMessage');
          var passwordInput = document.getElementById('zmNgPassword');

          function generateQRCode() {
            var password = passwordInput.value;

            // Clear previous QR code
            qrContainer.innerHTML = '';

            // Create QR code data in zmNg format
            var qrData = JSON.stringify({
              n: profileName,
              p: portalUrl,
              u: username,
              pw: password
            });

            // Generate QR code
            qrcode = new QRCode(qrContainer, {
              text: qrData,
              width: 200,
              height: 200,
              colorDark: '#000000',
              colorLight: '#ffffff',
              correctLevel: QRCode.CorrectLevel.M
            });

            qrMessage.innerHTML = '<span class="text-success"><?php echo translate('Scan this QR code with the zmNg app to add this profile') ?></span>';
          }

          // Generate QR code on page load
          generateQRCode();

          // Regenerate when password is entered
          passwordInput.addEventListener('input', generateQRCode);
        });
        </script>
<?php } ?>

<?php
if (canEdit('System')) {
?>
        <div class="Permissions mt-4">
          <fieldset>
            <legend><?php echo translate('Global Permissions') ?></legend>
            <div class="row">
              <div class="col-md-6">
                <table class="table table-sm">
                  <tbody>
                    <tr>
                      <th scope="row"><?php echo translate('Stream') ?></th>
                      <td><?php echo htmlSelect('user[Stream]', $nv, $User->Stream(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Events') ?></th>
                      <td><?php echo htmlSelect('user[Events]', $nve, $User->Events(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
<?php if (defined('ZM_FEATURES_SNAPSHOTS') and ZM_FEATURES_SNAPSHOTS) { ?>
                    <tr>
                      <th scope="row"><?php echo translate('Snapshots') ?></th>
                      <td><?php echo htmlSelect('user[Snapshots]', $nve, $User->Snapshots(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
<?php } ?>
                    <tr>
                      <th scope="row"><?php echo translate('Control') ?></th>
                      <td><?php echo htmlSelect('user[Control]', $nve, $User->Control(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="col-md-6">
                <table class="table table-sm">
                  <tbody>
                    <tr>
                      <th scope="row"><?php echo translate('Monitors') ?></th>
                      <td><?php echo htmlSelect('user[Monitors]', $nvec, $User->Monitors(), ['id'=>'user[Monitors]', 'class'=>'form-control form-control-sm', 'data-on-change'=>'updateEffectivePermissions']) ?></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Groups') ?></th>
                      <td><?php echo htmlSelect('user[Groups]', $nve, $User->Groups(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('System') ?></th>
                      <td><?php echo htmlSelect('user[System]', $nve, $User->System(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                    <tr>
                      <th scope="row"><?php echo translate('Devices') ?></th>
                      <td><?php echo htmlSelect('user[Devices]', $nve, $User->Devices(), ['class'=>'form-control form-control-sm']) ?></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </fieldset>
        </div><!--Permissions-->
<?php
$groups = array();
if (canEdit('Groups')) {
  foreach (ZM\Group::find() as $group) {
    $groups[$group->Id()] = $group;
  }

  $max_depth = 0;
  # This  array is indexed by parent_id
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
    global $User;
    $html = '<tr>';
    $html .= str_repeat('<td class="name">&nbsp;</td>', $group->depth());
    $html .= '<td class="name" colspan="'.($max_depth-($group->depth()-1)).'">';
    $html .= validHtmlStr($group->Id().' '.$group->Name());
    $html .= '</td><td class="monitors"><small class="text-muted">'. validHtmlStr(monitorIdsToNames($group->MonitorIds(), 30)).'</small></td>';
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
                  <th class="effective_permission"><?php echo translate('Effective Permission') ?></th>
                </tr>
              </thead>
              <tbody>
<?php
  $monitors = ZM\Monitor::find(['Deleted'=>0], ['order'=>'Sequence ASC']);
  foreach ($monitors as $monitor) {
    if ($monitor->canView()) {
      echo '
                <tr>
                  <td class="Id">'.$monitor->Id().'</td>
                  <td class="Name">'.validHtmlStr($monitor->Name()).'</td>
                  <td class="permission">'.html_radio('monitor_permission['.$monitor->Id().']', $inve,
                    $User->Monitor_Permission($monitor->Id())->Permission(),
                    ['default'=>'Inherit'],
                    ['data-on-change'=>'updateEffectivePermissions']).'</td>
                  <td class="effective_permission" id="effective_permission'.$monitor->Id().'">'.translate($monitor->effectivePermission($User)).'</td>
                </tr>';
    } else {
      ZM\Debug("Can't view monitor ".$monitor->Id(). ' ' .$monitor->canView());
    }
  }
?>
              </tbody>
            </table>
          </fieldset>
        </div><!--Monitor Permissions-->
<?php
} // end if canEdit(System)
?>
        </div><!--inner content-->
      </form>
    </div>
  </div>
<?php xhtmlFooter() ?>
