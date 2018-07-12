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

?>
var AJAX_TIMEOUT = <?php echo ZM_WEB_AJAX_TIMEOUT ?>;

var navBarRefresh = <?php echo 1000*ZM_WEB_REFRESH_NAVBAR ?>;

var currentView = '<?php echo $view ?>';
var thisUrl = "<?php echo ZM_BASE_URL.$_SERVER['PHP_SELF'] ?>";
var skinPath = "<?php echo ZM_SKIN_PATH ?>";

var canEditSystem = <?php echo canEdit('System' )?'true':'false' ?>;
var canViewSystem = <?php echo canView('System' )?'true':'false' ?>;
var canEditEvents = <?php echo canEdit('Events' )?'true':'false' ?>;
var canViewEvents = <?php echo canView('Events' )?'true':'false' ?>;

var canEditGroups = <?php echo canEdit('Groups' )?'true':'false' ?>;

var refreshParent = <?php
if ( ! empty($refreshParent) ) {
  if ( $refreshParent == true ) {
    echo 'true';
  } else if ( $refreshParent ) {
    # This is to tell the parent to refresh to a specific URL
    echo "'$refreshParent'";
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

var imagePrefix = "<?php echo "?view=image&eid=" ?>";

var auth_hash;
<?php if ( ZM_OPT_USE_AUTH && ZM_AUTH_HASH_LOGINS ) { ?>
auth_hash = '<?php echo isset($_SESSION['AuthHash']) ? $_SESSION['AuthHash'] : ''; ?>';
<?php } ?>
