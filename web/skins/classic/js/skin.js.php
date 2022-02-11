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
var SCALE_BASE = <?php echo SCALE_BASE ?>;

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

var STATE_UNKNOWN = <?php echo STATE_UNKNOWN ?>;
var STATE_IDLE = <?php echo STATE_IDLE ?>;
var STATE_PREALARM = <?php echo STATE_PREALARM ?>;
var STATE_ALARM = <?php echo STATE_ALARM ?>;
var STATE_ALERT = <?php echo STATE_ALERT ?>;
var STATE_TAPE = <?php echo STATE_TAPE ?>;

var stateStrings = new Array();
stateStrings[STATE_UNKNOWN] = "<?php echo translate('Unknown') ?>";
stateStrings[STATE_IDLE] = "<?php echo translate('Idle') ?>";
stateStrings[STATE_PREALARM] = "<?php echo translate('Prealarm') ?>";
stateStrings[STATE_ALARM] = "<?php echo translate('Alarm') ?>";
stateStrings[STATE_ALERT] = "<?php echo translate('Alert') ?>";
stateStrings[STATE_TAPE] = "<?php echo translate('Record') ?>";

<?php
global $config;
foreach ($config as $name=>$c) {
  if (!$c['Private']) {
    echo 'const '. $name . ' = \''.preg_replace('/(\n\r?)/', '\\\\$1', $c['Value']).'\';'.PHP_EOL;
  }
}
?>
