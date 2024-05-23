<?php
//
// ZoneMinder web options view file, $Date$, $Revision$
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

if (!canView('System')) {
  $view = 'error';
  return;
}

$canEdit = canEdit('System');

if ((!defined('ZM_OPT_USE_API')) or ZM_OPT_USE_API != '1') {
  echo '<div class="errorText">APIs are disabled. To enable, please turn on OPT_USE_API in Options->System</div>';
  return;
}
?>

<form name="userForm" method="post" action="?">
  <button class="float-left" type="submit" name="updateSelected" id="updateSelected"><?php echo translate('Update')?></button>
  <button class="float-left" type="button" id="btnNewToken"><?php echo translate('New Token')?></button>
  <button class="btn-danger float-right" type="submit" name="revokeAllTokens" id="revokeAllTokens"><?php echo translate('RevokeAllTokens')?></button>
  <br/>
<?php
  function revokeAllTokens() {
    $minTokenTime = time();
    dbQuery('UPDATE `Users` SET `TokenMinExpiry`=?', array($minTokenTime));
    echo '<span class="timedSuccessBox">'.translate('AllTokensRevoked').'</span>';
  }

  function updateSelected() {
    # Turn them all off, then selectively turn the checked ones back on
    dbQuery('UPDATE `Users` SET `APIEnabled`=0');

    if (isset($_REQUEST['tokenUids'])) {
      $minTime = time();
      foreach ($_REQUEST['tokenUids'] as $markUid) {
        dbQuery('UPDATE `Users` SET `TokenMinExpiry`=? WHERE `Id`=?', array($minTime, $markUid));
      }
    }
    if (isset($_REQUEST['apiUids'])) {
      foreach ($_REQUEST['apiUids'] as $markUid) {
        dbQuery('UPDATE `Users` SET `APIEnabled`=1 WHERE `Id`=?', array($markUid));
      }
    }
    echo '<span class="timedSuccessBox">'.translate('Updated').'</span>';
  }

  if (array_key_exists('revokeAllTokens', $_POST)) {
    revokeAllTokens();
  }

  if (array_key_exists('updateSelected', $_POST)) {
    updateSelected();
  }
?>
  <br/><br/>
  <input type="hidden" name="view" value="<?php echo $view ?>"/>
  <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
  <input type="hidden" name="action" value="delete"/>
  <table id="contentTable" class="table table-striped">
    <thead class="thead-highlight">
      <tr>
        <th class="colUsername"><?php echo translate('Username') ?></th>
        <th class="colMark"><?php echo translate('Revoke Token') ?></th>
        <th class="colMark"><?php echo translate('API Enabled') ?></th>
      </tr>
    </thead>
    <tbody>
<?php
foreach (ZM\User::find([], ['order'=>'Username']) as $u) {
?>
      <tr>
        <td class="colUsername"><?php echo validHtmlStr($u->Username()) ?></td>
        <td class="colMark"><input type="checkbox" name="tokenUids[]" value="<?php echo $u->Id() ?>" /></td>
        <td class="colMark"><input type="checkbox" name="apiUids[]" value="<?php echo $u->Id() ?>" <?php echo $u->APIEnabled()?'checked':''?> /></td>
      </tr>
<?php
}
?>
    </tbody>
  </table>
</form>
