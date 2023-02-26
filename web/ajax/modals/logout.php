<?php
//
// ZoneMinder web logout view file, $Date$, $Revision$
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
global $CLANG;
?>
<div id="modalLogout" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo validHtmlStr(ZM_WEB_TITLE) . ' ' . translate('Logout') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><?php echo sprintf( $CLANG['CurrentLogin'], validHtmlStr($user['Username']) ) ?></p>
<?php if ( canView('System') ) { ?>
        <p>Other logged in users:<br/>
<table class="table table-striped">
  <thead>
  <tr>
    <th class="text-left"><?php echo translate('Username') ?></th>
    <th class="text-left"><?php echo translate('IPAddress') ?></th>
    <th class="text-left"><?php echo translate('Last Access') ?></th>
  </tr>
  </thead>
  <tbody>
<?php
require_once('includes/User.php');
$result = dbQuery('SELECT * FROM Sessions WHERE access > ? ORDER BY access DESC LIMIT 100',
array(time() - ZM_COOKIE_LIFETIME));
if (!$result) return;

$current_session = $_SESSION;
zm_session_start();

$user_cache = array();
while ( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
  $_SESSION = array();
  if (!session_decode($row['data'])) {
    ZM\Warning('Failed to decode '.$row['data']);
    continue;
  }
  if (isset($_SESSION['last_time']))  {
    # This is a dead session
    continue;
  }
  if (!isset($_SESSION['username'])) {
    # Not logged in
    continue;
  }
  if (isset($user_cache[$_SESSION['username']])) {
    $user = $user_cache[$_SESSION['username']];
  } else {
    $user = ZM\User::find_one(array('Username'=>$_SESSION['username']));
    if (!$user) {
      ZM\Debug('User not found for '.$_SESSION['username']);
      continue;
    }
    $user_cache[$_SESSION['username']] = $user;
  }

  global $dateTimeFormatter;
  echo '
  <tr>
    <td>'.validHtmlStr($user->Username()).'</td>
    <td>'.validHtmlStr($_SESSION['remoteAddr']).'</td>
    <td>'.$dateTimeFormatter->format($row['access']).'</td>
  </tr>
';
} # end while
session_abort();
$_SESSION = $current_session;
?>
          </tbody>
        </table>
<?php } # end if canView(System) ?>
      </div>
      <div class="modal-footer">
        <form name="logoutForm" id="logoutForm" method="post" action="?view=logout">
          <?php
          // We have to manually insert the csrf key into the form when using a modal generated via ajax call
          echo getCSRFinputHTML();
          ?>
          <button type="submit" name="action" value="logout"><?php echo translate('Logout') ?></button>
          <?php if ( ZM_USER_SELF_EDIT ) echo '<button type="submit" name="action" value="config">'.translate('Config').'</button>'.PHP_EOL; ?>
          <button type="button" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        </form>
      </div>
    </div>
  </div>
</div>
