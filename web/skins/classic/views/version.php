<?php
//
// ZoneMinder web version view file, $Date$, $Revision$
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
    "go" => $SLANG['GoToZoneMinder']
);

if ( verNum( ZM_DYN_CURR_VERSION ) != verNum( ZM_DYN_LAST_VERSION ) )
{
    $options = array_merge( $options, array(
        "ignore" => $SLANG['VersionIgnore'],
        "hour"   => $SLANG['VersionRemindHour'],
        "day"    => $SLANG['VersionRemindDay'],
        "week"   => $SLANG['VersionRemindWeek'],
        "never"  => $SLANG['VersionRemindNever']
    ) );
}

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Version'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['Version'] ?></h2>
    </div>
    <div id="content">
<?php
if ( ZM_DYN_DB_VERSION && (ZM_DYN_DB_VERSION != ZM_VERSION) )
{
?>
      <p class="errorText"><?= sprintf( $CLANG['VersionMismatch'], ZM_VERSION, ZM_DYN_DB_VERSION ) ?></p>
      <p><?= $SLANG['RunLocalUpdate'] ?></p>
      <div id="contentButtons">
        <input type="button" value="<?= $SLANG['Close'] ?>" onclick="closeWindow()"/>
      </div>
<?php
}
elseif ( verNum( ZM_DYN_LAST_VERSION ) <= verNum( ZM_VERSION ) )
{
?>
      <p><?= sprintf( $CLANG['RunningRecentVer'], ZM_VERSION ) ?></p>
      <p><?= $SLANG['UpdateNotNecessary'] ?></p>
      <p><input type="button" value="<?= $SLANG['GoToZoneMinder'] ?>" onclick="zmWindow()"/></p>
      <div id="contentButtons">
        <input type="button" value="<?= $SLANG['Close'] ?>" onclick="closeWindow()"/>
      </div>
<?php
}
else
{
?>
      <form name="contentForm" id="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="version"/>
        <p><?= $SLANG['UpdateAvailable'] ?></p>
        <p><?= sprintf( $CLANG['LatestRelease'], ZM_DYN_LAST_VERSION, ZM_VERSION ) ?></p>
        <p><?= buildSelect( "option", $options ); ?></p>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Apply'] ?>" onclick="submitForm( this )"/>
          <input type="button" value="<?= $SLANG['Close'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
<?php
}
?>
    </div>
  </div>
</body>
</html>
