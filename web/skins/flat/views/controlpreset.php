<?php
//
// ZoneMinder web run state view file, $Date: 2009-09-28 15:16:17 +0100 (Mon, 28 Sep 2009) $, $Revision: 2958 $
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

if ( !canEdit( 'Monitors' ) )
{
    $view = "error";
    return;
}

$monitor = dbFetchOne( "select C.*,M.* from Monitors as M inner join Controls as C on (M.ControlId = C.Id ) where M.Id = '".dbEscape($_REQUEST['mid'])."'" );

$sql = "select * from ControlPresets where MonitorId = '".$monitor['Id']."'";
$labels = array();
foreach( dbFetchAll( $sql ) as $row )
{
    $labels[$row['Preset']] = $row['Label'];
}

$presets = array();
for ( $i = 1; $i <= $monitor['NumPresets']; $i++ )
{
    $presets[$i] = $SLANG['Preset']." ".$i;
    if ( !empty($labels[$i]) )
    {
        $presets[$i] .= " (".validHtmlStr($labels[$i]).")";
    }
}


$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['SetPreset'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['SetPreset'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="mid" value="<?= $monitor['Id'] ?>"/>
        <input type="hidden" name="action" value="control"/>
        <input type="hidden" name="control" value="presetSet"/>
        <input type="hidden" name="showControls" value="1"/>
        <p><?= buildSelect( "preset", $presets, "updateLabel()" ) ?></p>
        <p><label for="newLabel"><?= $SLANG['NewLabel'] ?></label><input type="text" name="newLabel" id="newLabel" value="" size="16"/></p>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Save'] ?>"/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
