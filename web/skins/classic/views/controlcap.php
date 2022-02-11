<?php
//
// ZoneMinder web control capabilities view file, $Date$, $Revision$
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

if ( !canEdit('Control') ) {
  $view = 'error';
  return;
}

$tabs = array();
$tabs['main'] = translate('Main');
$tabs['move'] = translate('Move');
$tabs['pan'] = translate('Pan');
$tabs['tilt'] = translate('Tilt');
$tabs['zoom'] = translate('Zoom');
$tabs['focus'] = translate('Focus');
$tabs['gain'] = translate('Gain');
$tabs['white'] = translate('White');
$tabs['iris'] = translate('Iris');
$tabs['presets'] = translate('Presets');

$tab = isset($_REQUEST['tab']) ? validHtmlStr($_REQUEST['tab']) :'main';

if ( isset($_REQUEST['Control']) ) {
  $Control = $_REQUEST['Control'];
} else {
  if ( !empty($_REQUEST['cid']) ) {
    $Control = dbFetchOne('SELECT * FROM Controls WHERE Id = ?', NULL, array($_REQUEST['cid']));
  } else {
    $Control = array(
      'Name' => translate('New'),
      'Type' => 'Local',
      'Protocol' => '',
      'CanWake' => '',
      'CanSleep' => '',
      'CanReset' => '',
      'CanReboot' => '',
      'CanMove' => '',
      'CanMoveDiag' => '',
      'CanMoveMap' => '',
      'CanMoveAbs' => '',
      'CanMoveRel' => '',
      'CanMoveCon' => '',
      'CanPan' => '',
      'MinPanRange' => '',
      'MaxPanRange' => '',
      'MinPanStep' => '',
      'MaxPanStep' => '',
      'HasPanSpeed' => '',
      'MinPanSpeed' => '',
      'MaxPanSpeed' => '',
      'HasTurboPan' => '',
      'TurboPanSpeed' => '',
      'CanTilt' => '',
      'MinTiltRange' => '',
      'MaxTiltRange' => '',
      'MinTiltStep' => '',
      'MaxTiltStep' => '',
      'HasTiltSpeed' => '',
      'MinTiltSpeed' => '',
      'MaxTiltSpeed' => '',
      'HasTurboTilt' => '',
      'TurboTiltSpeed' => '',
      'CanZoom' => '',
      'CanZoomAbs' => '',
      'CanZoomRel' => '',
      'CanZoomCon' => '',
      'MinZoomRange' => '',
      'MaxZoomRange' => '',
      'MinZoomStep' => '',
      'MaxZoomStep' => '',
      'HasZoomSpeed' => '',
      'MinZoomSpeed' => '',
      'MaxZoomSpeed' => '',
      'CanFocus' => '',
      'CanAutoFocus' => '',
      'CanFocusAbs' => '',
      'CanFocusRel' => '',
      'CanFocusCon' => '',
      'MinFocusRange' => '',
      'MaxFocusRange' => '',
      'MinFocusStep' => '',
      'MaxFocusStep' => '',
      'HasFocusSpeed' => '',
      'MinFocusSpeed' => '',
      'MaxFocusSpeed' => '',
      'CanIris' => '',
      'CanAutoIris' => '',
      'CanIrisAbs' => '',
      'CanIrisRel' => '',
      'CanIrisCon' => '',
      'MinIrisRange' => '',
      'MaxIrisRange' => '',
      'MinIrisStep' => '',
      'MaxIrisStep' => '',
      'HasIrisSpeed' => '',
      'MinIrisSpeed' => '',
      'MaxIrisSpeed' => '',
      'CanGain' => '',
      'CanAutoGain' => '',
      'CanGainAbs' => '',
      'CanGainRel' => '',
      'CanGainCon' => '',
      'MinGainRange' => '',
      'MaxGainRange' => '',
      'MinGainStep' => '',
      'MaxGainStep' => '',
      'HasGainSpeed' => '',
      'MinGainSpeed' => '',
      'MaxGainSpeed' => '',
      'CanWhite' => '',
      'CanAutoWhite' => '',
      'CanWhiteAbs' => '',
      'CanWhiteRel' => '',
      'CanWhiteCon' => '',
      'MinWhiteRange' => '',
      'MaxWhiteRange' => '',
      'MinWhiteStep' => '',
      'MaxWhiteStep' => '',
      'HasWhiteSpeed' => '',
      'MinWhiteSpeed' => '',
      'MaxWhiteSpeed' => '',
      'HasPresets' => '',
      'NumPresets' => '',
      'HasHomePreset' => '',
      'CanSetPresets' => '',
    );
  }
}

xhtmlHeaders(__FILE__, translate('ControlCap').' - '.$Control['Name']);
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page" class="container-fluid">
    <div class="row flex-nowrap">
      <nav> <!-- BEGIN PILL LIST -->
        <ul class="nav nav-pills flex-column h-100" id="pills-tab" role="tabList" aria-orientation="vertical">
<?php
foreach ( $tabs as $name=>$value ) {
?>
    <li class="nav-item form-control-sm my-1">
      <a
        id="<?php echo $name ?>-tab"
        data-toggle="pill"
        class="nav-link<?php echo $tab == $name ? ' active' : '' ?>"
        href="#pills-<?php echo $name?>"
        role="tab"
        aria-controls="pills-<?php echo $name?>"
        aria-selected="<?php echo $tab == $name ? 'true':'false'?>"
      ><?php echo $value ?></a>
    </li>
<?php
}
?>
        </ul>
      </nav> <!-- END PILL LIST -->

      <div class="d-flex flex-column col-sm-offset-2 container-fluid">
        <!-- BEGIN MINI HEADER -->
        <div class="w-100 py-1">
          <div class="float-left pl-3">
            <button type="button" id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
            <button type="button" id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
          </div>
          <div class="w-100 pt-2">
            <h2><?php echo translate('ControlCap') ?> - <?php echo validHtmlStr($Control['Name']) ?></h2>
          </div>
        </div>

        <!-- BEGIN ITEM LIST -->
        <div class="d-flex flex-row container-fluid pr-0">
          <form name="contentForm" id="contentForm" method="post" action="?" class="validateFormOnSubmit">
            <input type="hidden" name="view" value="<?php echo $view ?>"/>
            <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
            <input type="hidden" name="cid" value="<?php echo requestVar('cid') ?>"/>

            <div class="tab-content" id="pills-tabContent">
<?php
foreach ( $tabs as $name=>$value ) {
  echo '<div id="pills-'.$name.'" class="tab-pane fade'.($name==$tab ? ' show active' : '').'" role="tabpanel" aria-labelledby="'.$name.'-tab">'.PHP_EOL;
?>
        <table class="major">
          <tbody>
<?php
switch ( $name ) {
  case 'main' :
?>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('Name') ?></th>
              <td><input type="text" name="Control[Name]" value="<?php echo validHtmlStr($Control['Name']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('Type') ?></th>
              <td>
<?php 
    $types = array(
      'Local'=>translate('Local'),
      'Remote'=>translate('Remote'),
      'Ffmpeg'=>translate('Ffmpeg'),
      'Libvlc'=>translate('Libvlc'),
      'cURL'=>'cURL'
    );
    echo buildSelect('Control[Type]', $types);
?>
              </td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('Protocol') ?></th>
              <td><input type="text" name="Control[Protocol]" value="<?php echo validHtmlStr($Control['Protocol']) ?>" size="24"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanWake') ?></th>
              <td><input type="checkbox" name="Control[CanWake]" value="1"<?php if ( !empty($Control['CanWake']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanSleep') ?></th>
              <td><input type="checkbox" name="Control[CanSleep]" value="1"<?php if ( !empty($Control['CanSleep']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanReset') ?></th>
              <td><input type="checkbox" name="Control[CanReset]" value="1"<?php if ( !empty($Control['CanReset']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanReboot') ?></th>
              <td><input type="checkbox" name="Control[CanReboot]" value="1"<?php if ( !empty($Control['CanReboot']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
<?php
        break;
    case 'move' :
?>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanMove') ?></th>
              <td><input type="checkbox" name="Control[CanMove]" value="1"<?php if ( !empty($Control['CanMove']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanMoveDiag') ?></th>
              <td><input type="checkbox" name="Control[CanMoveDiag]" value="1"<?php if ( !empty($Control['CanMoveDiag']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanMoveMap') ?></th>
              <td><input type="checkbox" name="Control[CanMoveMap]" value="1"<?php if ( !empty($Control['CanMoveMap']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanMoveAbs') ?></th>
              <td><input type="checkbox" name="Control[CanMoveAbs]" value="1"<?php if ( !empty($Control['CanMoveAbs']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanMoveRel') ?></th>
              <td><input type="checkbox" name="Control[CanMoveRel]" value="1"<?php if ( !empty($Control['CanMoveRel']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanMoveCon') ?></th>
              <td><input type="checkbox" name="Control[CanMoveCon]" value="1"<?php if ( !empty($Control['CanMoveCon']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
<?php
        break;
    case 'pan' :
?>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanPan') ?></th>
              <td><input type="checkbox" name="Control[CanPan]" value="1"<?php if ( !empty($Control['CanPan']) ) { ?> checked="checked"<?php } ?>></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinPanRange') ?></th>
              <td><input type="number" name="Control[MinPanRange]" value="<?php echo validHtmlStr($Control['MinPanRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxPanRange') ?></th>
              <td><input type="number" name="Control[MaxPanRange]" value="<?php echo validHtmlStr($Control['MaxPanRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinPanStep') ?></th>
              <td><input type="number" name="Control[MinPanStep]" value="<?php echo validHtmlStr($Control['MinPanStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxPanStep') ?></th>
              <td><input type="number" name="Control[MaxPanStep]" value="<?php echo validHtmlStr($Control['MaxPanStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('HasPanSpeed') ?></th>
              <td><input type="checkbox" name="Control[HasPanSpeed]" value="1"<?php if ( !empty($Control['HasPanSpeed']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinPanSpeed') ?></th>
              <td><input type="number" name="Control[MinPanSpeed]" value="<?php echo validHtmlStr($Control['MinPanSpeed']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxPanSpeed') ?></th>
              <td><input type="number" name="Control[MaxPanSpeed]" value="<?php echo validHtmlStr($Control['MaxPanSpeed']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('HasTurboPan') ?></th>
              <td><input type="checkbox" name="Control[HasTurboPan]" value="1"<?php if ( !empty($Control['HasTurboPan']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('TurboPanSpeed') ?></th>
              <td><input type="number" name="Control[TurboPanSpeed]" value="<?php echo validHtmlStr($Control['TurboPanSpeed']) ?>"/></td>
            </tr>
<?php
        break;
    case 'tilt' :
?>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanTilt') ?></th>
              <td><input type="checkbox" name="Control[CanTilt]" value="1"<?php if ( !empty($Control['CanTilt']) ) { ?> checked="checked"<?php } ?>></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinTiltRange') ?></th>
              <td><input type="number" name="Control[MinTiltRange]" value="<?php echo validHtmlStr($Control['MinTiltRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxTiltRange') ?></th>
              <td><input type="number" name="Control[MaxTiltRange]" value="<?php echo validHtmlStr($Control['MaxTiltRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinTiltStep') ?></th>
              <td><input type="number" name="Control[MinTiltStep]" value="<?php echo validHtmlStr($Control['MinTiltStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxTiltStep') ?></th>
              <td><input type="number" name="Control[MaxTiltStep]" value="<?php echo validHtmlStr($Control['MaxTiltStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('HasTiltSpeed') ?></th>
              <td><input type="checkbox" name="Control[HasTiltSpeed]" value="1"<?php if ( !empty($Control['HasTiltSpeed']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinTiltSpeed') ?></th>
              <td><input type="number" name="Control[MinTiltSpeed]" value="<?php echo validHtmlStr($Control['MinTiltSpeed']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxTiltSpeed') ?></th>
              <td><input type="number" name="Control[MaxTiltSpeed]" value="<?php echo validHtmlStr($Control['MaxTiltSpeed']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('HasTurboTilt') ?></th>
              <td><input type="checkbox" name="Control[HasTurboTilt]" value="1"<?php if ( !empty($Control['HasTurboTilt']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('TurboTiltSpeed') ?></th>
              <td><input type="number" name="Control[TurboTiltSpeed]" value="<?php echo validHtmlStr($Control['TurboTiltSpeed']) ?>"/></td>
            </tr>
<?php
        break;
    case 'zoom' :
?>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanZoom') ?></th>
              <td><input type="checkbox" name="Control[CanZoom]" value="1"<?php if ( !empty($Control['CanZoom']) ) { ?> checked="checked"<?php } ?>></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanZoomAbs') ?></th>
              <td><input type="checkbox" name="Control[CanZoomAbs]" value="1"<?php if ( !empty($Control['CanZoomAbs']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanZoomRel') ?></th>
              <td><input type="checkbox" name="Control[CanZoomRel]" value="1"<?php if ( !empty($Control['CanZoomRel']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanZoomCon') ?></th>
              <td><input type="checkbox" name="Control[CanZoomCon]" value="1"<?php if ( !empty($Control['CanZoomCon']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinZoomRange') ?></th>
              <td><input type="number" name="Control[MinZoomRange]" value="<?php echo validHtmlStr($Control['MinZoomRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxZoomRange') ?></th>
              <td><input type="number" name="Control[MaxZoomRange]" value="<?php echo validHtmlStr($Control['MaxZoomRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinZoomStep') ?></th>
              <td><input type="number" name="Control[MinZoomStep]" value="<?php echo validHtmlStr($Control['MinZoomStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxZoomStep') ?></th>
              <td><input type="number" name="Control[MaxZoomStep]" value="<?php echo validHtmlStr($Control['MaxZoomStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('HasZoomSpeed') ?></th>
              <td><input type="checkbox" name="Control[HasZoomSpeed]" value="1"<?php if ( !empty($Control['HasZoomSpeed']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinZoomSpeed') ?></th>
              <td><input type="number" name="Control[MinZoomSpeed]" value="<?php echo validHtmlStr($Control['MinZoomSpeed']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxZoomSpeed') ?></th>
              <td><input type="number" name="Control[MaxZoomSpeed]" value="<?php echo validHtmlStr($Control['MaxZoomSpeed']) ?>"/></td>
            </tr>
<?php
        break;
    case 'focus' :
?>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanFocus') ?></th>
              <td><input type="checkbox" name="Control[CanFocus]" value="1"<?php if ( !empty($Control['CanFocus']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanAutoFocus') ?></th>
              <td><input type="checkbox" name="Control[CanAutoFocus]" value="1"<?php if ( !empty($Control['CanAutoFocus']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanFocusAbs') ?></th>
              <td><input type="checkbox" name="Control[CanFocusAbs]" value="1"<?php if ( !empty($Control['CanFocusAbs']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanFocusRel') ?></th>
              <td><input type="checkbox" name="Control[CanFocusRel]" value="1"<?php if ( !empty($Control['CanFocusRel']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanFocusCon') ?></th>
              <td><input type="checkbox" name="Control[CanFocusCon]" value="1"<?php if ( !empty($Control['CanFocusCon']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinFocusRange') ?></th>
              <td><input type="number" name="Control[MinFocusRange]" value="<?php echo validHtmlStr($Control['MinFocusRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxFocusRange') ?></th>
              <td><input type="number" name="Control[MaxFocusRange]" value="<?php echo validHtmlStr($Control['MaxFocusRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinFocusStep') ?></th>
              <td><input type="number" name="Control[MinFocusStep]" value="<?php echo validHtmlStr($Control['MinFocusStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxFocusStep') ?></th>
              <td><input type="number" name="Control[MaxFocusStep]" value="<?php echo validHtmlStr($Control['MaxFocusStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('HasFocusSpeed') ?></th>
              <td><input type="checkbox" name="Control[HasFocusSpeed]" value="1"<?php if ( !empty($Control['HasFocusSpeed']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinFocusSpeed') ?></th>
              <td><input type="number" name="Control[MinFocusSpeed]" value="<?php echo validHtmlStr($Control['MinFocusSpeed']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxFocusSpeed') ?></th>
              <td><input type="number" name="Control[MaxFocusSpeed]" value="<?php echo validHtmlStr($Control['MaxFocusSpeed']) ?>"/></td>
            </tr>
<?php
        break;
    case 'iris' :
?>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanIris') ?></th>
              <td><input type="checkbox" name="Control[CanIris]" value="1"<?php if ( !empty($Control['CanIris']) ) { ?> checked="checked"<?php } ?>></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanAutoIris') ?></th>
              <td><input type="checkbox" name="Control[CanAutoIris]" value="1"<?php if ( !empty($Control['CanAutoIris']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanIrisAbs') ?></th>
              <td><input type="checkbox" name="Control[CanIrisAbs]" value="1"<?php if ( !empty($Control['CanIrisAbs']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanIrisRel') ?></th>
              <td><input type="checkbox" name="Control[CanIrisRel]" value="1"<?php if ( !empty($Control['CanIrisRel']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanIrisCon') ?></th>
              <td><input type="checkbox" name="Control[CanIrisCon]" value="1"<?php if ( !empty($Control['CanIrisCon']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinIrisRange') ?></th>
              <td><input type="number" name="Control[MinIrisRange]" value="<?php echo validHtmlStr($Control['MinIrisRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxIrisRange') ?></th>
              <td><input type="number" name="Control[MaxIrisRange]" value="<?php echo validHtmlStr($Control['MaxIrisRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinIrisStep') ?></th>
              <td><input type="number" name="Control[MinIrisStep]" value="<?php echo validHtmlStr($Control['MinIrisStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxIrisStep') ?></th>
              <td><input type="number" name="Control[MaxIrisStep]" value="<?php echo validHtmlStr($Control['MaxIrisStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('HasIrisSpeed') ?></th>
              <td><input type="checkbox" name="Control[HasIrisSpeed]" value="1"<?php if ( !empty($Control['HasIrisSpeed']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinIrisSpeed') ?></th>
              <td><input type="number" name="Control[MinIrisSpeed]" value="<?php echo validHtmlStr($Control['MinIrisSpeed']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxIrisSpeed') ?></th>
              <td><input type="number" name="Control[MaxIrisSpeed]" value="<?php echo validHtmlStr($Control['MaxIrisSpeed']) ?>"/></td>
            </tr>
<?php
        break;
    case 'gain' :
?>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanGain') ?></th>
              <td><input type="checkbox" name="Control[CanGain]" value="1"<?php if ( !empty($Control['CanGain']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanAutoGain') ?></th>
              <td><input type="checkbox" name="Control[CanAutoGain]" value="1"<?php if ( !empty($Control['CanAutoGain']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanGainAbs') ?></th>
              <td><input type="checkbox" name="Control[CanGainAbs]" value="1"<?php if ( !empty($Control['CanGainAbs']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanGainRel') ?></th>
              <td><input type="checkbox" name="Control[CanGainRel]" value="1"<?php if ( !empty($Control['CanGainRel']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanGainCon') ?></th>
              <td><input type="checkbox" name="Control[CanGainCon]" value="1"<?php if ( !empty($Control['CanGainCon']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinGainRange') ?></th>
              <td><input type="number" name="Control[MinGainRange]" value="<?php echo validHtmlStr($Control['MinGainRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxGainRange') ?></th>
              <td><input type="number" name="Control[MaxGainRange]" value="<?php echo validHtmlStr($Control['MaxGainRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinGainStep') ?></th>
              <td><input type="number" name="Control[MinGainStep]" value="<?php echo validHtmlStr($Control['MinGainStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxGainStep') ?></th>
              <td><input type="number" name="Control[MaxGainStep]" value="<?php echo validHtmlStr($Control['MaxGainStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('HasGainSpeed') ?></th>
              <td><input type="checkbox" name="Control[HasGainSpeed]" value="1"<?php if ( !empty($Control['HasGainSpeed']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinGainSpeed') ?></th>
              <td><input type="number" name="Control[MinGainSpeed]" value="<?php echo validHtmlStr($Control['MinGainSpeed']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxGainSpeed') ?></th>
              <td><input type="number" name="Control[MaxGainSpeed]" value="<?php echo validHtmlStr($Control['MaxGainSpeed']) ?>"/></td>
            </tr>
<?php
        break;
    case 'white' :
?>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanWhite') ?></th>
              <td><input type="checkbox" name="Control[CanWhite]" value="1"<?php if ( !empty($Control['CanWhite']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanAutoWhite') ?></th>
              <td><input type="checkbox" name="Control[CanAutoWhite]" value="1"<?php if ( !empty($Control['CanAutoWhite']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanWhiteAbs') ?></th>
              <td><input type="checkbox" name="Control[CanWhiteAbs]" value="1"<?php if ( !empty($Control['CanWhiteAbs']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanWhiteRel') ?></th>
              <td><input type="checkbox" name="Control[CanWhiteRel]" value="1"<?php if ( !empty($Control['CanWhiteRel']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanWhiteCon') ?></th>
              <td><input type="checkbox" name="Control[CanWhiteCon]" value="1"<?php if ( !empty($Control['CanWhiteCon']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinWhiteRange') ?></th>
              <td><input type="number" name="Control[MinWhiteRange]" value="<?php echo validHtmlStr($Control['MinWhiteRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxWhiteRange') ?></th>
              <td><input type="number" name="Control[MaxWhiteRange]" value="<?php echo validHtmlStr($Control['MaxWhiteRange']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinWhiteStep') ?></th>
              <td><input type="number" name="Control[MinWhiteStep]" value="<?php echo validHtmlStr($Control['MinWhiteStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxWhiteStep') ?></th>
              <td><input type="number" name="Control[MaxWhiteStep]" value="<?php echo validHtmlStr($Control['MaxWhiteStep']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('HasWhiteSpeed') ?></th>
              <td><input type="checkbox" name="Control[HasWhiteSpeed]" value="1"<?php if ( !empty($Control['HasWhiteSpeed']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MinWhiteSpeed') ?></th>
              <td><input type="number" name="Control[MinWhiteSpeed]" value="<?php echo validHtmlStr($Control['MinWhiteSpeed']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('MaxWhiteSpeed') ?></th>
              <td><input type="number" name="Control[MaxWhiteSpeed]" value="<?php echo validHtmlStr($Control['MaxWhiteSpeed']) ?>"/></td>
            </tr>
<?php
        break;
    case 'presets' :
?>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('HasPresets') ?></th>
              <td><input type="checkbox" name="Control[HasPresets]" value="1"<?php if ( !empty($Control['HasPresets']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('NumPresets') ?></th>
              <td><input type="number" name="Control[NumPresets]" value="<?php echo validHtmlStr($Control['NumPresets']) ?>"/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('HasHomePreset') ?></th>
              <td><input type="checkbox" name="Control[HasHomePreset]" value="1"<?php if ( !empty($Control['HasHomePreset']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
            <tr>
              <th class="text-right pr-3" scope="row"><?php echo translate('CanSetPresets') ?></th>
              <td><input type="checkbox" name="Control[CanSetPresets]" value="1"<?php if ( !empty($Control['CanSetPresets']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
<?php
        break;
} # end switch tab
?>
          </tbody>
        </table>
      </div>
<?php
} # end foreach tab
?>
        </div><!--tab-content-->
        <div id="contentButtons">
          <button type="submit" name="action" value="Save"<?php if ( !canEdit( 'Control' ) ) { ?> disabled="disabled"<?php } ?>><?php echo translate('Save') ?></button>
          <button type="button" id="cancelBtn"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
    </div>
  </div>
</div>
<?php xhtmlFooter() ?>
