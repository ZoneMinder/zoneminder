<?php
//
// ZoneMinder web donate view file, $Date: 2008-10-07 10:02:17 +0100 (Tue, 07 Oct 2008) $, $Revision: 2651 $
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

if ( !canEdit( 'System' ) )
{
    $view = "error";
    return;
}

$options = array( 
    "go"      => $SLANG['DonateYes'],
    "hour"    => $SLANG['DonateRemindHour'],
    "day"     => $SLANG['DonateRemindDay'],
    "week"    => $SLANG['DonateRemindWeek'],
    "month"   => $SLANG['DonateRemindMonth'],
    "never"   => $SLANG['DonateRemindNever'],
    "already" => $SLANG['DonateAlready'],
);

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Donate'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['Donate'] ?></h2>
      <h1>ZoneMinder - <?= $SLANG['Donate'] ?></h1>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="donate"/>
        <p>
          <?= $SLANG['DonateEnticement'] ?>
        </p>
        <p>
          <?= buildSelect( "option", $options ); ?>
        </p>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Apply'] ?>" onclick="submitForm( this )">
          <input type="button" value="<?= $SLANG['Close'] ?>" onclick="closeWindow()">
        </div>
      </form>
    </div>
  </div>
</body>
</html>
