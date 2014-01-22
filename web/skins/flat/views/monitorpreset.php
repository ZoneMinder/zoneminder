<?php
//
// ZoneMinder web monitor preset view file, $Date: 2008-09-26 10:47:20 +0100 (Fri, 26 Sep 2008) $, $Revision: 2632 $
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
$sql = "select Id,Name from MonitorPresets";
$presets = array();
$presets[0] = $SLANG['ChoosePreset'];
foreach( dbFetchAll( $sql ) as $preset )
{
    $presets[$preset['Id']] = htmlentities( $preset['Name'] );
}

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['MonitorPreset'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['MonitorPreset'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="mid" value="<?= validNum($_REQUEST['mid']) ?>"/>
        <p>
          <?= $SLANG['MonitorPresetIntro'] ?>
        </p>
        <p>
          <label for="preset"><?= $SLANG['Preset'] ?></label><?= buildSelect( "preset", $presets, 'configureButtons( this )' ); ?>
        </p>
        <div id="contentButtons">
          <input type="submit" name="saveBtn" value="<?= $SLANG['Save'] ?>" onclick="submitPreset( this )" disabled="disabled"/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
