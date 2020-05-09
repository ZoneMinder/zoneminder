<?php
//
// ZoneMinder web zone view file, $Date$, $Revision$
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

if ( !canView('Monitors') ) {
  $view = 'error';
  return;
}

$mid = validInt($_REQUEST['mid']);
$zid = (!empty($_REQUEST['zid'])) ? validInt($_REQUEST['zid']) : 0;

$scale = SCALE_BASE;

$hicolor = '0x00ff00'; // Green

$presets = array();
$presetNames = array();
$presetNames[0] = translate('ChoosePreset');
$sql = 'SELECT *, Units-1 AS UnitsIndex, CheckMethod-1 AS CheckMethodIndex FROM ZonePresets ORDER BY Id ASC';
foreach ( dbFetchAll($sql) as $preset ) {
  $presetNames[$preset['Id']] = $preset['Name'];
  $presets[] = $preset;
}

$optTypes = array();
foreach ( getEnumValues('Zones', 'Type') as $optType ) {
  $optTypes[$optType] = $optType;
}

$optUnits = array();
foreach ( getEnumValues( 'Zones', 'Units' ) as $optUnit ) {
  $optUnits[$optUnit] = $optUnit;
}

$optCheckMethods = array();
foreach ( getEnumValues( 'Zones', 'CheckMethod' ) as $optCheckMethod ) {
  $optCheckMethods[$optCheckMethod] = $optCheckMethod;
}

$monitor = new ZM\Monitor( $mid );

$minX = 0;
$maxX = $monitor->ViewWidth()-1;
$minY = 0;
$maxY = $monitor->ViewHeight()-1;

if ( !isset($newZone) ) {
  if ( $zid > 0 ) {
    $zone = dbFetchOne('SELECT * FROM Zones WHERE MonitorId = ? AND Id=?', NULL, array($monitor->Id(), $zid));
  } else {
    $zone = array(
      'Id' => 0,
      'Name' => translate('New'),
      'Type'  =>  'Active',
			'Units'	=>	'Pixels',
      'MonitorId' => $monitor->Id(),
      'NumCoords' => 4,
      'Coords' => sprintf('%d,%d %d,%d, %d,%d %d,%d', $minX, $minY, $maxX, $minY, $maxX, $maxY, $minX, $maxY),
      'Area' => $monitor->ViewWidth() * $monitor->ViewHeight(),
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
      'ExtendAlarmFrames' => '',
    );
  }
  $zone['Points'] = coordsToPoints($zone['Coords']);
  $zone['AreaCoords'] = preg_replace('/\s+/', ',', $zone['Coords']);

  $newZone = $zone;
} # end if new Zone

# Ensure Zone fits within the limits of the Monitor
limitPoints($newZone['Points'], $minX, $minY, $maxX, $maxY);

ksort($newZone['Points'], SORT_NUMERIC);

$newZone['Coords'] = pointsToCoords($newZone['Points']);
$newZone['Area'] = getPolyArea($newZone['Points']);
$newZone['AreaCoords'] = preg_replace('/\s+/', ',', $newZone['Coords']);
$selfIntersecting = isSelfIntersecting($newZone['Points']);

$focusWindow = true;
$connkey = generateConnKey();
$streamSrc = '';
$streamMode = '';
# Have to do this here, because the .js.php references somethings figured out when generating the streamHTML
$StreamHTML = getStreamHTML($monitor, array('scale'=>$scale));

xhtmlHeaders(__FILE__, translate('Zone'));
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Monitor') ?> <?php echo $monitor->Name() ?> - <?php echo translate('Zone') ?> <?php echo $newZone['Name'] ?></h2>
    </div>
    <div id="content">
      <form name="zoneForm" id="zoneForm" method="post" action="?" onkeypress="return event.keyCode != 13;">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="zone"/>
        <input type="hidden" name="mid" value="<?php echo $mid ?>"/>
        <input type="hidden" name="zid" value="<?php echo $zid ?>"/>
        <input type="hidden" name="newZone[NumCoords]" value="<?php echo count($newZone['Points']) ?>"/>
        <input type="hidden" name="newZone[Coords]" value="<?php echo $newZone['Coords'] ?>"/>
        <input type="hidden" name="newZone[Area]" value="<?php echo $newZone['Area'] ?>"/>
        <input type="hidden" name="newZone[AlarmRGB]"/>

        <div id="definitionPanel">
					<div class="monitor">
						<div id="imagePanel">
							<div id="imageFrame" style="position: relative; width: <?php echo reScale($monitor->ViewWidth(), $scale) ?>px; height: <?php echo reScale($monitor->ViewHeight(), $scale) ?>px;">
								<?php echo $StreamHTML; ?>
								<svg id="zoneSVG" class="zones" style="position: absolute; top: 0; left: 0; width: <?php echo reScale($monitor->ViewWidth(), $scale) ?>px; height: <?php echo reScale($monitor->ViewHeight(), $scale) ?>px; background: none;">
	<?php
	if ( $zone['Id'] ) {
		$other_zones = dbFetchAll('SELECT * FROM Zones WHERE MonitorId = ? AND Id != ?', NULL, array($monitor->Id(), $zone['Id']));
	} else {
		$other_zones = dbFetchAll('SELECT * FROM Zones WHERE MonitorId = ?', NULL, array($monitor->Id()));
	}
	if ( count($other_zones) ) {
		$html = '';
		foreach ( $other_zones as $other_zone ) {
			$other_zone['AreaCoords'] = preg_replace('/\s+/', ',', $other_zone['Coords']);
			$html .= '<polygon id="zonePoly'.$other_zone['Id'].'" points="'. $other_zone['AreaCoords'] .'" class="'. $other_zone['Type'] .'"/>';
		}
		echo $html;
	}
	?>
									<polygon id="zonePoly" points="<?php echo $zone['AreaCoords'] ?>" class="Editing <?php echo $zone['Type'] ?>"/>
									Sorry, your browser does not support inline SVG
								</svg>
							</div>
						</div>
						<div id="monitorState">
							<?php echo translate('State') ?>:&nbsp;<span id="stateValue"></span>&nbsp;-&nbsp;<span id="fpsValue"></span>&nbsp;fps
						</div>
					</div>

					<div id="settingsPanel">
						<table id="zoneSettings">
							<tbody>
								<tr>
									<th scope="row"><?php echo translate('Name') ?></th>
									<td colspan="2"><input type="text" name="newZone[Name]" value="<?php echo $newZone['Name'] ?>"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('Type') ?></th>
									<td colspan="2"><?php echo htmlSelect('newZone[Type]', $optTypes, $newZone['Type'],
											array('onchange'=>'applyZoneType()')); ?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('Preset') ?></th>
									<td colspan="2">
										<?php echo htmlSelect('presetSelector', $presetNames,
												( isset($_REQUEST['presetSelector']) ? $_REQUEST['presetSelector'] : null),
												array('onchange'=>'applyPreset()', 'onblur'=>'this.selectedIndex=0') )
										?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('Units') ?></th>
									<td colspan="2"><?php echo htmlSelect('newZone[Units]', $optUnits, $newZone['Units'],
											array('onchange'=>'applyZoneUnits()') ) ?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneAlarmColour') ?></th>
									<td colspan="2">
										<input type="number" name="newAlarmRgbR" value="<?php echo ($newZone['AlarmRGB']>>16)&0xff ?>" size="3"/>
										/
										<input type="number" name="newAlarmRgbG" value="<?php echo ($newZone['AlarmRGB']>>8)&0xff ?>" size="3"/>
										/
										<input type="number" name="newAlarmRgbB" value="<?php echo $newZone['AlarmRGB']&0xff ?>" size="3"/>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('CheckMethod') ?></th>
									<td colspan="2"><?php echo htmlSelect('newZone[CheckMethod]', $optCheckMethods, $newZone['CheckMethod']); ?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxPixelThres') ?></th>
									<td><input type="number" name="newZone[MinPixelThreshold]" value="<?php echo $newZone['MinPixelThreshold'] ?>" size="4"/></td>
									<td><input type="number" name="newZone[MaxPixelThreshold]" value="<?php echo $newZone['MaxPixelThreshold'] ?>" size="4"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneFilterSize') ?></th>
									<td><input type="number" name="newZone[FilterX]" value="<?php echo $newZone['FilterX'] ?>" size="4"/></td>
									<td><input type="number" name="newZone[FilterY]" value="<?php echo $newZone['FilterY'] ?>" size="4"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneArea') ?></th>
									<td colspan="2"><input type="number" name="newZone[TempArea]" value="<?php echo $newZone['Area'] ?>" size="7" disabled="disabled"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxAlarmArea') ?></th>
									<td><input type="number" name="newZone[MinAlarmPixels]" value="<?php echo $newZone['MinAlarmPixels'] ?>" size="6"/></td>
									<td><input type="number" name="newZone[MaxAlarmPixels]" value="<?php echo $newZone['MaxAlarmPixels'] ?>" size="6"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxFiltArea') ?></th>
									<td><input type="number" name="newZone[MinFilterPixels]" value="<?php echo $newZone['MinFilterPixels'] ?>" size="6"/></td>
									<td><input type="number" name="newZone[MaxFilterPixels]" value="<?php echo $newZone['MaxFilterPixels'] ?>" size="6"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxBlobArea') ?></th>
									<td><input type="number" name="newZone[MinBlobPixels]" value="<?php echo $newZone['MinBlobPixels'] ?>" size="6"/></td>
									<td><input type="number" name="newZone[MaxBlobPixels]" value="<?php echo $newZone['MaxBlobPixels'] ?>" size="6"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxBlobs') ?></th>
									<td><input type="number" name="newZone[MinBlobs]" value="<?php echo $newZone['MinBlobs'] ?>" size="4"/></td>
									<td><input type="number" name="newZone[MaxBlobs]" value="<?php echo $newZone['MaxBlobs'] ?>" size="4"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneOverloadFrames') ?></th>
									<td colspan="2"><input type="number" name="newZone[OverloadFrames]" value="<?php echo $newZone['OverloadFrames'] ?>" size="4"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneExtendAlarmFrames') ?></th>
									<td colspan="2"><input type="number" name="newZone[ExtendAlarmFrames]" value="<?php echo $newZone['ExtendAlarmFrames'] ?>" size="4"/></td>
								</tr>
							</tbody>
						</table>
					</div>

					<div id="zonePoints">
					  <table>
 					 	  <tbody>
					 		  <tr>
<?php
$pointCols = 2;
for ( $i = 0; $i < $pointCols; $i++ ) {
?>
									<td>
										<table>
											<thead>
												<tr>
													<th><?php echo translate('Point') ?></th>
													<th><?php echo translate('X') ?></th>
													<th><?php echo translate('Y') ?></th>
													<th><?php echo translate('Action') ?></th>
												</tr>
											</thead>
											<tbody>
											</tbody>
										</table>
									</td>
<?php
# I think this for horizontal filler
    if ( $i < ($pointCols-1) ) {
?>
									<td>&nbsp;</td>
<?php
    }
} # end foreach pointcol
?>
								</tr>
							</tbody>
						</table>
					<div class="buttons">
						<button type="button" id="pauseBtn"><?php echo translate('Pause') ?></button>
						<button type="button" id="saveBtn" value="Save" <?php if (!canEdit('Monitors') || (false && $selfIntersecting)) { ?> disabled="disabled"<?php } ?>>
						<?php echo translate('Save') ?>
						</button>
						<button type="button" id="cancelBtn" value="Cancel"><?php echo translate('Cancel') ?></button>
					</div>
					</div><!--end ZonePoints-->
        </div><!--definitionsPanel-->
      </form>
    </div><!--content-->
  </div><!--page-->
</body>
</html>
