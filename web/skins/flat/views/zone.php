<?php
//
// ZoneMinder web zone view file, $Date: 2008-09-26 10:47:20 +0100 (Fri, 26 Sep 2008) $, $Revision: 2632 $
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

if ( !canView( 'Monitors' ) )
{
    $view = "error";
    return;
}

$mid = validInt($_REQUEST['mid']);
$zid = !empty($_REQUEST['zid'])?validInt($_REQUEST['zid']):0;

$scale = SCALE_BASE;

$hicolor = "0x00ff00"; // Green

$presets = array();
$presetNames = array();
$presetNames[0] = $SLANG['ChoosePreset'];
$sql = "select *, Units-1 as UnitsIndex, CheckMethod-1 as CheckMethodIndex from ZonePresets order by Id asc";
foreach( dbFetchAll( $sql ) as $preset )
{
    $presetNames[$preset['Id']] = $preset['Name'];
    $presets[] = $preset;
}

$optTypes = array();
foreach ( getEnumValues( 'Zones', 'Type' ) as $optType )
{
    $optTypes[$optType] = $optType;
}

$optUnits = array();
foreach ( getEnumValues( 'Zones', 'Units' ) as $optUnit )
{
    $optUnits[$optUnit] = $optUnit;
}

$optCheckMethods = array();
foreach ( getEnumValues( 'Zones', 'CheckMethod' ) as $optCheckMethod )
{
    $optCheckMethods[$optCheckMethod] = $optCheckMethod;
}

$monitor = dbFetchMonitor ( $mid );

$minX = 0;
$maxX = $monitor['Width']-1;
$minY = 0;
$maxY = $monitor['Height']-1;

if ( !isset($newZone) )
{
    if ( $zid > 0 )
    {
        $zone = dbFetchOne( "select * from Zones where MonitorId = '".dbEscape($monitor['Id'])."' and Id = '".dbEscape($zid)."'" );
    }
    else
    {
        $zone = array(
            'Name' => $SLANG['New'],
            'Id' => 0,
            'MonitorId' => $monitor['Id'],
            'NumCoords' => 4,
            'Coords' => sprintf( "%d,%d %d,%d, %d,%d %d,%d", $minX, $minY, $maxX, $minY, $maxX, $maxY, $minX, $maxY ),
            'Area' => $monitor['Width'] * $monitor['Height'],
            'AlarmRGB' => 0xff0000,
            'CheckMethod' => 'Blobs',
            'MinPixelThreshold' => '',
            'MaxPixelThreshold' => '',
            'MinAlarmPixels' => '',
            'MaxAlarmPixels' => '',
            'FilterX' => '',
            'FilterY' => '',
            'MinFilterPixels' => '',
            'MaxFilterPixels' => '',
            'MinBlobPixels' => '',
            'MaxBlobPixels' => '',
            'MinBlobs' => '',
            'MaxBlobs' => '',
            'OverloadFrames' => '',
        );
    }
    $zone['Points'] = coordsToPoints( $zone['Coords'] );

    $newZone = $zone;
}

//if ( !$points )
//{
    //$points = $zone['Points'];
//}

ksort( $newZone['Points'], SORT_NUMERIC );

$newZone['Coords'] = pointsToCoords( $newZone['Points'] );
$newZone['Area'] = getPolyArea( $newZone['Points'] );
$selfIntersecting = isSelfIntersecting( $newZone['Points'] );

$wd = getcwd();
chdir( ZM_DIR_IMAGES );
$command = getZmuCommand( " -m ".$mid." -z" );
$command .= '"'.$zid.' '.$hicolor.' '.$newZone['Coords'].'"';
$status = exec( escapeshellcmd( $command ) );
chdir( $wd );

$zoneImage = ZM_DIR_IMAGES.'/Zones'.$monitor['Id'].'.jpg?'.time();

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Zone'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['Monitor'] ?> <?= $monitor['Name'] ?> - <?= $SLANG['Zone'] ?> <?= $newZone['Name'] ?></h2>
    </div>
    <div id="content">
      <form name="zoneForm" id="zoneForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="action" value="zone"/>
        <input type="hidden" name="mid" value="<?= $mid ?>"/>
        <input type="hidden" name="zid" value="<?= $zid ?>"/>
        <input type="hidden" name="newZone[NumCoords]" value="<?= count($newZone['Points']) ?>"/>
        <input type="hidden" name="newZone[Coords]" value="<?= $newZone['Coords'] ?>"/>
        <input type="hidden" name="newZone[Area]" value="<?= $newZone['Area'] ?>"/>
        <input type="hidden" name="newZone[AlarmRGB]" value=""/>
        <div id="settingsPanel">
          <table id="zoneSettings" cellspacing="0">
            <tbody>
              <tr>
                <th scope="row"><?= $SLANG['Name'] ?></th>
                <td colspan="2"><input type="text" name="newZone[Name]" value="<?= $newZone['Name'] ?>" size="12"/></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['Type'] ?></th>
                <td colspan="2"><?= buildSelect( "newZone[Type]", $optTypes, 'applyZoneType()' ) ?></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['Preset'] ?></th>
                <td colspan="2"><?= buildSelect( "presetSelector", $presetNames, array( "onchange"=>"applyPreset()", "onblur"=>"this.selectedIndex=0" ) ) ?></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['Units'] ?></th>
                <td colspan="2"><?= buildSelect( "newZone[Units]", $optUnits, 'applyZoneUnits()' ) ?></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['ZoneAlarmColour'] ?></th>
                <td colspan="2"><input type="text" name="newAlarmRgbR" value="<?= ($newZone['AlarmRGB']>>16)&0xff ?>" size="3" onchange="limitRange( this, 0, 255 )"/>&nbsp;/&nbsp;<input type="text" name="newAlarmRgbG" value="<?= ($newZone['AlarmRGB']>>8)&0xff ?>" size="3" onchange="limitRange( this, 0, 255 )"/>&nbsp;/&nbsp;<input type="text" name="newAlarmRgbB" value="<?= $newZone['AlarmRGB']&0xff ?>" size="3" onchange="limitRange( this, 0, 255 )"/></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['CheckMethod'] ?></th>
                <td colspan="2"><?= buildSelect( "newZone[CheckMethod]", $optCheckMethods, 'applyCheckMethod()' ) ?></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['ZoneMinMaxPixelThres'] ?></th>
                <td><input type="text" name="newZone[MinPixelThreshold]" value="<?= $newZone['MinPixelThreshold'] ?>" size="4" onchange="limitRange( this, 0, 255 )"/></td>
                <td><input type="text" name="newZone[MaxPixelThreshold]" value="<?= $newZone['MaxPixelThreshold'] ?>" size="4" onchange="limitRange( this, 0, 255 )"/></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['ZoneFilterSize'] ?></th>
                <td><input type="text" name="newZone[FilterX]" value="<?= $newZone['FilterX'] ?>" size="4" onchange="limitFilter( this )"/></td>
                <td><input type="text" name="newZone[FilterY]" value="<?= $newZone['FilterY'] ?>" size="4" onchange="limitFilter( this )"/></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['ZoneArea'] ?></th>
                <td colspan="2"><input type="text" name="newZone[TempArea]" value="<?= $newZone['Area'] ?>" size="7" disabled="disabled"/></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['ZoneMinMaxAlarmArea'] ?></th>
                <td><input type="text" name="newZone[MinAlarmPixels]" value="<?= $newZone['MinAlarmPixels'] ?>" size="6" onchange="limitArea(this)"/></td>
                <td><input type="text" name="newZone[MaxAlarmPixels]" value="<?= $newZone['MaxAlarmPixels'] ?>" size="6" onchange="limitArea(this)"/></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['ZoneMinMaxFiltArea'] ?></th>
                <td><input type="text" name="newZone[MinFilterPixels]" value="<?= $newZone['MinFilterPixels'] ?>" size="6" onchange="limitArea(this)"/></td>
                <td><input type="text" name="newZone[MaxFilterPixels]" value="<?= $newZone['MaxFilterPixels'] ?>" size="6" onchange="limitArea(this)"/></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['ZoneMinMaxBlobArea'] ?></th>
                <td><input type="text" name="newZone[MinBlobPixels]" value="<?= $newZone['MinBlobPixels'] ?>" size="6"/></td>
                <td><input type="text" name="newZone[MaxBlobPixels]" value="<?= $newZone['MaxBlobPixels'] ?>" size="6"/></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['ZoneMinMaxBlobs'] ?></th>
                <td><input type="text" name="newZone[MinBlobs]" value="<?= $newZone['MinBlobs'] ?>" size="4"/></td>
                <td><input type="text" name="newZone[MaxBlobs]" value="<?= $newZone['MaxBlobs'] ?>" size="4"/></td>
              </tr>
              <tr>
                <th scope="row"><?= $SLANG['ZoneOverloadFrames'] ?></th>
                <td colspan="2"><input type="text" name="newZone[OverloadFrames]" value="<?= $newZone['OverloadFrames'] ?>" size="4"/></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div id="definitionPanel">
          <div id="imagePanel">
            <div id="imageFrame" style="width: <?= reScale( $monitor['Width'], $scale ) ?>px; height: <?= reScale( $monitor['Height'], $scale ) ?>px;">
              <img name="zoneImage" id="zoneImage" src="<?= $zoneImage ?>" width="<?= reScale( $monitor['Width'], $scale ) ?>" height="<?= reScale( $monitor['Height'], $scale ) ?>" alt="Zone Image"/>
            </div>
          </div>
          <table id="zonePoints" cellspacing="0">
            <tbody>
              <tr>
<?php
$pointCols = 2;
for ( $i = 0; $i < $pointCols; $i++ )
{
?>
                <td>
                  <table cellspacing="0">
                    <thead>
                      <tr>
                        <th><?= $SLANG['Point'] ?></th>
                        <th><?= $SLANG['X'] ?></th>
                        <th><?= $SLANG['Y'] ?></th>
                        <th><?= $SLANG['Action'] ?></th>
                      </tr>
                    </thead>
                    <tbody>
<?php
    if ( false )
    for ( $j = $i; $j < count($newZone['Points']); $j += 2 )
    {
?>
                      <tr id="row<?= $j ?>" onmouseover="highlightOn( <?= $j ?> )" onmouseout="highlightOff( <?= $j ?> )" onclick="setActivePoint( <?= $j ?> )">
                        <td><?= $j+1 ?></td>
                        <td><input name="newZone[Points][<?= $j ?>][x]" id="newZone[Points][<?= $j ?>][x]" size="5" value="<?= $newZone['Points'][$j]['x'] ?>" onchange="updateX( this, <?= $j ?> )"<?php if ( canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/></td>
                        <td><input name="newZone[Points][<?= $j ?>][y]" id="newZone[Points][<?= $j ?>][y]" size="5" value="<?= $newZone['Points'][$j]['y'] ?>" onchange="updateY( this, <?= $j ?> )"<?php if ( canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/></td>
                        <td><a href="#" onclick="addPoint( this, <?= $j ?> ); return( false );">+</a><?php if ( count($newZone['Points']) > 3 ) { ?>&nbsp;<a id="delete<?= $j ?>" href="#" onclick="delPoint( this, <?= $j ?> ); return(false);">&ndash;</a><?php } ?>&nbsp;<a id="cancel<?= $j ?>" href="#" onclick="unsetActivePoint( <?= $j ?> ); return( false );">X</a></td>
                      </tr>
<?php
    }
?>
                    </tbody>
                  </table>
                </td>
<?php
    if ( $i < ($pointCols-1) )
    {
?>
                <td>&nbsp;</td>
<?php
    }
}
?>
              </tr>
            </tbody>
          </table>
          <input type="submit" id="submitBtn" name="submitBtn" value="<?= $SLANG['Save'] ?>" onclick="return saveChanges( this )"<?php if (!canEdit( 'Monitors' ) || (false && $selfIntersecting)) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
