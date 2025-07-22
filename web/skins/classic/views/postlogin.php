<?php
//
// ZoneMinder web logging in view file
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

xhtmlHeaders(__FILE__, translate('LoggingIn'));
getBodyTopHTML();
echo getNavBarHTML();
?>
  <div id="page">
    <div id="header">
      <h1><?php echo validHtmlStr(ZM_WEB_TITLE) . ' ' . translate('Login') ?></h1>
    </div>
    <div id="content">
      <h2><?php echo translate('LoggingIn') ?></h2>
    </div>
  </div>
<?php xhtmlFooter() ?>
