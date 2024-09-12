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

$mid = empty($_REQUEST['mid']) ? 0 : validInt($_REQUEST['mid']);
if ( !($mid and canEdit('Monitors', $mid)) ) {
  $view = 'error';
  return;
}

$zid = (!empty($_REQUEST['zid'])) ? validInt($_REQUEST['zid']) : 0;

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
foreach ( getEnumValues('Zones', 'Units') as $optUnit ) {
  $optUnits[$optUnit] = $optUnit;
}

$optCheckMethods = array();
foreach ( getEnumValues('Zones', 'CheckMethod') as $optCheckMethod ) {
  $optCheckMethods[$optCheckMethod] = $optCheckMethod;
}

$monitor = new ZM\Monitor($mid);

$minX = 0;
$maxX = $monitor->ViewWidth()-1;
$minY = 0;
$maxY = $monitor->ViewHeight()-1;

if ( !isset($newZone) ) {
  if ( $zid > 0 ) {
    $zone = dbFetchOne('SELECT * FROM Zones WHERE MonitorId=? AND Id=?', NULL, array($monitor->Id(), $zid));
  } else {
    $zone = array(
      'Id' => 0,
      'Name' => translate('New'),
      'Type'  =>  'Active',
			'Units'	=>	'Pixels',
      'MonitorId' => $monitor->Id(),
      'NumCoords' => 4,
      'Coords' => sprintf('%d,%d %d,%d %d,%d %d,%d', $minX, $minY, $maxX, $minY, $maxX, $maxY, $minX, $maxY),
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
# Have to do this here, because the .js.php references somethings figured out when generating the streamHTML
$monitor->connKey();
$StreamHTML = getStreamHTML($monitor, array('mode'=>'single'));

# So I'm thinking now that 50% of screen real-estate with a minimum of 640px. 
# scale should be floor(whatever that width is/actual width)
# So we need javascript to figure out browser width, figure out scale and then activate the stream.  

xhtmlHeaders(__FILE__, translate('Zone'));
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page">
    <div class="w-100">
      <div class="float-left pl-3 pt-1">
        <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
        <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      </div>
      <div class="w-100 pt-2">
        <h2><?php echo translate('Monitor').' '.$monitor->Name().' - '.translate('Zone').' '.$newZone['Name'] ?></h2>
      </div>
    </div>
    <div id="content">
      <form name="zoneForm" id="zoneForm" method="post" action="?">
        <input type="hidden" name="REFERER" value="<?php echo isset($_SERVER['HTTP_REFERER']) ? validHtmlStr($_SERVER['HTTP_REFERER']) : '' ?>"/> 
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="zone"/>
        <input type="hidden" name="mid" value="<?php echo $mid ?>"/>
        <input type="hidden" name="zid" value="<?php echo $zid ?>"/>
        <input type="hidden" name="newZone[NumCoords]" value="<?php echo count($newZone['Points']) ?>"/>
        <input type="hidden" name="newZone[Coords]" value="<?php echo $newZone['Coords'] ?>"/>
        <input type="hidden" name="newZone[Area]" value="<?php echo $newZone['Area'] ?>"/>
        <input type="hidden" name="newZone[AlarmRGB]"/>

        <div id="definitionPanel">
				  <div id="imagePanel">
            <div class="Monitor">
              <div id="monitor<?php echo $monitor->Id() ?>">
            <div id="imageFrame<?php echo $monitor->Id()?>" class="imageFrame">
              <?php echo $StreamHTML; ?>
              <svg id="zoneSVG" class="zones" viewBox="0 0 <?php echo $monitor->ViewWidth().' '.$monitor->ViewHeight() ?>">
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
            </div><?php # imageFrame?>
            <div id="monitorState">
              <?php echo translate('State') ?>:&nbsp;<span id="stateValue<?php echo $monitor->Id() ?>"></span>&nbsp;-&nbsp;<span id="fpsValue<?php echo $monitor->Id() ?>"></span>&nbsp;fps
            </div>
            <div id="StreamControlButtons">
              <button type="button" id="pauseBtn" title="<?php echo translate('Pause') ?>">
                <i class="material-icons md-18">pause</i>
              </button>
              <button type="button" id="playBtn" title="<?php echo translate('Play') ?>">
                <i class="material-icons md-18">play_arrow</i>
              </button>
            </div>
            </div>
            </div>
          </div><!--imagePanel-->

					<div id="settingsPanel">
						<table id="zoneSettings">
							<tbody>
								<tr>
									<th scope="row"><?php echo translate('Name') ?></th>
									<td colspan="2"><input type="text" name="newZone[Name]" value="<?php echo validHtmlStr($newZone['Name']) ?>"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('Type') ?></th>
									<td colspan="2"><?php echo htmlSelect('newZone[Type]', $optTypes, $newZone['Type'],
											array('data-on-change'=>'applyZoneType', 'id'=>'newZone[Type]')); ?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('Preset') ?></th>
									<td colspan="2">
										<?php echo htmlSelect('presetSelector', $presetNames,
												( isset($_REQUEST['presetSelector']) ? $_REQUEST['presetSelector'] : null),
												array('data-on-change'=>'applyPreset', 'id'=>'presetSelector') )
										?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('Units') ?></th>
                  <td colspan="2">
<?php
                        echo htmlSelect('newZone[Units]', $optUnits, $newZone['Units'],
                        array('data-on-change'=>'applyZoneUnits', 'id'=>'newZone[Units]')
                        );
                        # Used later for number inputs
                        $step = $newZone['Units'] == 'Percent' ? ' step="any" max="100"' : '';
?>
                  </td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneAlarmColour') ?></th>
									<td colspan="2">
										<input type="number" name="newAlarmRgbR" value="<?php echo ($newZone['AlarmRGB']>>16)&0xff ?>" min="0" max="255"/>
										/
										<input type="number" name="newAlarmRgbG" value="<?php echo ($newZone['AlarmRGB']>>8)&0xff ?>" min="0" max="255"/>
										/
										<input type="number" name="newAlarmRgbB" value="<?php echo $newZone['AlarmRGB']&0xff ?>" min="0" max="255"/>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('CheckMethod') ?></th>
									<td colspan="2"><?php echo htmlSelect('newZone[CheckMethod]', $optCheckMethods, $newZone['CheckMethod']); ?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxPixelThres') ?></th>
									<td><input type="number" name="newZone[MinPixelThreshold]" value="<?php echo $newZone['MinPixelThreshold'] ?>" min="0" max="255"/></td>
									<td><input type="number" name="newZone[MaxPixelThreshold]" value="<?php echo $newZone['MaxPixelThreshold'] ?>" min="0" max="255"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneFilterSize') ?></th>
									<td><input type="number" name="newZone[FilterX]" value="<?php echo $newZone['FilterX'] ?>" min="0"/></td>
									<td><input type="number" name="newZone[FilterY]" value="<?php echo $newZone['FilterY'] ?>" min="0"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneArea') ?></th>
									<td colspan="2"><input type="number" name="newZone[TempArea]" value="<?php echo $newZone['Area'] ?>" disabled="disabled"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxAlarmArea') ?></th>
                  <td><input type="number" name="newZone[MinAlarmPixels]" value="<?php echo $newZone['MinAlarmPixels'] ?>"<?php echo $step ?> min="0"/></td>
									<td><input type="number" name="newZone[MaxAlarmPixels]" value="<?php echo $newZone['MaxAlarmPixels'] ?>"<?php echo $step ?> min="0"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxFiltArea') ?></th>
									<td><input type="number" name="newZone[MinFilterPixels]" value="<?php echo $newZone['MinFilterPixels'] ?>"<?php echo $step ?> min="0"/></td>
									<td><input type="number" name="newZone[MaxFilterPixels]" value="<?php echo $newZone['MaxFilterPixels'] ?>"<?php echo $step ?> min="0"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxBlobArea') ?></th>
									<td><input type="number" name="newZone[MinBlobPixels]" value="<?php echo $newZone['MinBlobPixels'] ?>"<?php echo $step ?> min="0"/></td>
									<td><input type="number" name="newZone[MaxBlobPixels]" value="<?php echo $newZone['MaxBlobPixels'] ?>"<?php echo $step ?> min="0"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneMinMaxBlobs') ?></th>
									<td><input type="number" name="newZone[MinBlobs]" value="<?php echo $newZone['MinBlobs'] ?>" min="0"/></td>
									<td><input type="number" name="newZone[MaxBlobs]" value="<?php echo $newZone['MaxBlobs'] ?>" min="0"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneOverloadFrames') ?></th>
									<td colspan="2"><input type="number" name="newZone[OverloadFrames]" value="<?php echo $newZone['OverloadFrames'] ?>" min="0"/></td>
								</tr>
								<tr>
									<th scope="row"><?php echo translate('ZoneExtendAlarmFrames') ?></th>
									<td colspan="2"><input type="number" name="newZone[ExtendAlarmFrames]" value="<?php echo $newZone['ExtendAlarmFrames'] ?>" min="0"/></td>
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
  <script src="<?php echo cache_bust('js/MonitorStream.js') ?>"></script>
<?php xhtmlFooter() ?>
