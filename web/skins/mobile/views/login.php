<?php
//
// ZoneMinder web login view file, $Date$, $Revision$
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


xhtmlHeaders( __FILE__, $SLANG['Login'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h1>ZoneMinder <?= $SLANG['Login'] ?></h1>
    </div>
    <div id="content">
      <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <div class="hidden">
          <fieldset>
            <input type="hidden" name="action" value="login"/>
            <input type="hidden" name="view" value="console"/>
          </fieldset>
        </div>
        <table id="contentTable" class="minor">
          <tr>
            <th scope="row" class="colLeft"><?= $SLANG['Username'] ?></th>
            <td class="colRight"><input type="text" name="username" value="<?= isset($username)?$username:"" ?>" size="12"/></td>
          </tr>
          <tr>
            <th scope="row" class="colLeft"><?= $SLANG['Password'] ?></th>
            <td class="colRight"><input type="password" name="password" value="" size="12"/></td>
          </tr>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Login'] ?>"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
