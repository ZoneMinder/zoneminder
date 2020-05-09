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

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Logout') );
?>
<body>
  <div id="page">
    <div id="header">
      <h1><?php echo validHtmlStr(ZM_WEB_TITLE) . ' ' . translate('Logout') ?></h1>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="?">
        <input type="hidden" name="action" value="logout"/>
        <input type="hidden" name="view" value="logout"/>
        <p><?php echo sprintf( $CLANG['CurrentLogin'], $user['Username'] ) ?></p>
        <p>
          <input type="submit" value="<?php echo translate('Logout') ?>"/>
<?php
if ( ZM_USER_SELF_EDIT ) {
  echo makePopupButton('?view=user&uid=' . $user['Id'], 'zmUser', 'user', translate('Config'));
}
?>
          <input type="button" value="<?php echo translate('Cancel') ?>" data-on-click="closeWindow"/>
        </p>
      </form>
    </div>
  </div>
</body>
</html>
