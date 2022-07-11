<?php
//
// ZoneMinder base javascript file, $Date: 2008-04-21 14:52:05 +0100 (Mon, 21 Apr 2008) $, $Revision: 2391 $
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

//
// This file should only contain JavaScript that needs preprocessing by php.
// Static JavaScript should go in skin.js
//

global $user;
?>
var AJAX_TIMEOUT = <?php echo ZM_WEB_AJAX_TIMEOUT ?>;
var navBarRefresh = <?php echo 1000*ZM_WEB_REFRESH_NAVBAR ?>;
var currentView = '<?php echo $view ?>';

var exportProgressString = '<?php echo addslashes(translate('Exporting')) ?>';
var exportFailedString = '<?php echo translate('ExportFailed') ?>';
var exportSucceededString = '<?php echo translate('ExportSucceeded') ?>';
var cancelString = '<?php echo translate('Cancel') ?>';
<?php
/* We can't trust PHP_SELF on a path like /index.php/"%3E%3Cimg src=x onerror=prompt('1');%3E which
   will still load index.php but will include the arbitrary payload after `.php/`. To mitigate this,
   try to avoid using PHP_SELF but here I try to replace everything after '.php'. */ ?>
var thisUrl = '<?php echo ZM_BASE_URL.preg_replace('/\.php.*$/i', '.php', $_SERVER['PHP_SELF']) ?>';
var skinPath = '<?php echo ZM_SKIN_PATH ?>';
var serverId = '<?php echo defined('ZM_SERVER_ID') ? ZM_SERVER_ID : '' ?>';

var canView = {};
var canEdit = {};
<?php
$perms = array('Stream', 'Events', 'Control', 'Monitors', 'Groups', 'Snapshots', 'System', 'Devices');
foreach ( $perms as $perm ) {
?>
  canView["<?php echo $perm ?>"] = <?php echo canView($perm)?'true':'false' ?>;
  canEdit["<?php echo $perm ?>"] = <?php echo canEdit($perm)?'true':'false' ?>;
<?php
}
?>

var ANIMATE_THUMBS = <?php echo ZM_WEB_ANIMATE_THUMBS?'true':'false' ?>;

var refreshParent = <?php
if ( ! empty($refreshParent) ) {
  if ( $refreshParent == true ) {
    echo 'true';
  } else if ( $refreshParent ) {
    # This is to tell the parent to refresh to a specific URL
    echo '\''.$refreshParent.'\'';
  } else {
    echo 'false';
  }
} else {
  echo 'false';
}
?>;
var closePopup = <?php
if ( ( ! empty($closePopup) ) and ( $closePopup == true ) ) {
  echo 'true';
} else {
  echo 'false';
}
?>;

var focusWindow = <?php echo !empty($focusWindow)?'true':'false' ?>;

var imagePrefix = "<?php echo '?view=image&eid=' ?>";

var auth_hash = '<?php echo generateAuthHash(ZM_AUTH_HASH_IPS) ?>';
var auth_relay = '<?php echo get_auth_relay() ?>';
var user = <?php
$user_without_password = $user;
unset($user_without_password['Password']);
echo json_encode($user_without_password);
?>;
var running = <?php echo daemonCheck()?'true':'false' ?>;

const STATE_UNKNOWN = <?php echo STATE_UNKNOWN ?>;
const STATE_IDLE = <?php echo STATE_IDLE ?>;
const STATE_PREALARM = <?php echo STATE_PREALARM ?>;
const STATE_ALARM = <?php echo STATE_ALARM ?>;
const STATE_ALERT = <?php echo STATE_ALERT ?>;
const STATE_TAPE = <?php echo STATE_TAPE ?>;

const CMD_NONE = <?php echo CMD_NONE ?>;
const CMD_PAUSE = <?php echo CMD_PAUSE ?>;
const CMD_PLAY = <?php echo CMD_PLAY ?>;
const CMD_VARPLAY = <?php echo CMD_VARPLAY ?>;
const CMD_STOP = <?php echo CMD_STOP ?>;
const CMD_FASTFWD = <?php echo CMD_FASTFWD ?>;
const CMD_SLOWFWD = <?php echo CMD_SLOWFWD ?>;
const CMD_SLOWREV = <?php echo CMD_SLOWREV ?>;
const CMD_FASTREV = <?php echo CMD_FASTREV ?>;
const CMD_ZOOMIN = <?php echo CMD_ZOOMIN ?>;
const CMD_ZOOMOUT = <?php echo CMD_ZOOMOUT ?>;
const CMD_PAN = <?php echo CMD_PAN ?>;
const CMD_SCALE = <?php echo CMD_SCALE ?>;
const CMD_PREV = <?php echo CMD_PREV ?>;
const CMD_NEXT = <?php echo CMD_NEXT ?>;
const CMD_SEEK = <?php echo CMD_SEEK ?>;
const CMD_QUERY = <?php echo CMD_QUERY ?>;
const CMD_QUIT = <?php echo CMD_QUIT ?>;

var stateStrings = new Array();
stateStrings[STATE_UNKNOWN] = "<?php echo translate('Unknown') ?>";
stateStrings[STATE_IDLE] = "<?php echo translate('Idle') ?>";
stateStrings[STATE_PREALARM] = "<?php echo translate('Prealarm') ?>";
stateStrings[STATE_ALARM] = "<?php echo translate('Alarm') ?>";
stateStrings[STATE_ALERT] = "<?php echo translate('Alert') ?>";
stateStrings[STATE_TAPE] = "<?php echo translate('Record') ?>";

<?php
global $user;
if ($user) {
  // Only include config if logged in or auth turned off. The login view doesn't require any config.
  global $config;
  foreach ($config as $name=>$c) {
    if (!(isset($c['Private']) and $c['Private'])) {
      $value = preg_replace('/(\n\r?)/', '\\\\$1', $c['Value']);
      $value = preg_replace('/\'/', '\\\\\'', $value);
      echo 'const '. $name . ' = \''.$value.'\';'.PHP_EOL;
    }
  }
}
?>
