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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

//
// This file should only contain JavaScript that needs preprocessing by php.
// Static JavaScript should go in skin.js
//

?>
  var AJAX_TIMEOUT = <?= ZM_WEB_AJAX_TIMEOUT ?>;

  var currentView = '<?= $view ?>';
  var thisUrl = "<?= ZM_BASE_URL.$_SERVER['PHP_SELF'] ?>";
  var skinPath = "<?= ZM_SKIN_PATH ?>";

  var canEditSystem = <?= canEdit('System' )?'true':'false' ?>;
  var canViewSystem = <?= canView('System' )?'true':'false' ?>;

  var refreshParent = <?= !empty($refreshParent)?'true':'false' ?>;

  var focusWindow = <?= !empty($focusWindow)?'true':'false' ?>;
