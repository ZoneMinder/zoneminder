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

var currentView = '<?php echo $view ?>';
var thisUrl = "<?php echo ZM_BASE_URL.$_SERVER['PHP_SELF'] ?>";
var skinPath = "<?php echo ZM_SKIN_PATH ?>";

var canEditSystem = <?php echo canEdit('System' )?'true':'false' ?>;
var canViewSystem = <?php echo canView('System' )?'true':'false' ?>;

var canEditGroups = <?php echo canEdit('Groups' )?'true':'false' ?>;

var refreshParent = <?php echo !empty($refreshParent)?'true':'false' ?>;

var focusWindow = <?php echo !empty($focusWindow)?'true':'false' ?>;

var imagePrefix = "<?php echo "?view=image&eid=" ?>";
