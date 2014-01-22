<?php
//
// ZoneMinder web bandwidth view file, $Date: 2008-07-25 10:48:16 +0100 (Fri, 25 Jul 2008) $, $Revision: 2612 $
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

$newBandwidth = $_COOKIE['zmBandwidth'];

if ( $user && !empty($user['MaxBandwidth']) )
{
    if ( $user['MaxBandwidth'] == "low" )
    {
        unset( $bwArray['high'] );
        unset( $bwArray['medium'] );
    }
    elseif ( $user['MaxBandwidth'] == "medium" )
    {
        unset( $bwArray['high'] );
    }
}

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Bandwidth'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['Bandwidth'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="bandwidth"/>
        <p><?= $SLANG['SetNewBandwidth'] ?></p>
        <p><?= buildSelect( "newBandwidth", $bwArray ) ?></p>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Save'] ?>"/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow();"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
