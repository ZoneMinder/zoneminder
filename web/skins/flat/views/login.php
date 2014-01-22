<?php
//
// ZoneMinder web login view file, $Date: 2008-09-26 10:47:20 +0100 (Fri, 26 Sep 2008) $, $Revision: 2632 $
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

xhtmlHeaders(__FILE__, $SLANG['Login'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h1>ZoneMinder <?= $SLANG['Login'] ?></h1>
    </div>
    <div id="content">
      <form name="loginForm" id="loginForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="action" value="login"/>
        <input type="hidden" name="view" value="postlogin"/>
        <table id="loginTable" class="minor" cellspacing="0">
          <tbody>
            <tr>
              <td class="colLeft"><?= $SLANG['Username'] ?></td>
              <td class="colRight"><input type="text" name="username" value="<?= isset($_REQUEST['username'])?validHtmlStr($_REQUEST['username']):"" ?>" size="12"/></td>
            </tr>
            <tr>
              <td class="colLeft"><?= $SLANG['Password'] ?></td>
              <td class="colRight"><input type="password" name="password" value="" size="12"/></td>
            </tr>
          </tbody>
        </table>
        <input type="submit" value="<?= $SLANG['Login'] ?>"/>
      </form>
    </div>
  </div>
</body>
</html>
