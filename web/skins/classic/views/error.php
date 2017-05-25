<?php
//
// ZoneMinder web error view file, $Date$, $Revision$
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

xhtmlHeaders(__FILE__, translate('Error') );
?>
<body>
  <div id="page">
    <div id="header">
      <h1>ZoneMinder <?php echo translate('Error') ?></h1>
    </div>
    <div id="content">
      <p>
        <?php echo translate('YouNoPerms') ?>
      </p>
      <p>
        <?php echo translate('ContactAdmin') ?>
      </p>
      <p>
        <a href="#" onclick="closeWindow();"><?php echo translate('Close') ?></a>
      </p>
    </div>
  </div>
</body>
</html>
