<?php
//
// ZoneMinder web control capabilities view file, $Date: 2009-01-30 17:43:29 +0000 (Fri, 30 Jan 2009) $, $Revision: 2737 $
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

if ( !canEdit( 'Control' ) )
{
    $view = "error";
    return;
}

$tabs = array();
$tabs["main"] = $SLANG['Main'];
$tabs["move"] = $SLANG['Move'];
$tabs["pan"] = $SLANG['Pan'];
$tabs["tilt"] = $SLANG['Tilt'];
$tabs["zoom"] = $SLANG['Zoom'];
$tabs["focus"] = $SLANG['Focus'];
$tabs["white"] = $SLANG['White'];
$tabs["iris"] = $SLANG['Iris'];
$tabs["presets"] = $SLANG['Presets'];

if ( isset($_REQUEST['tab']) )
    $tab = validHtmlStr($_REQUEST['tab']);
else
    $tab = "main";

if ( isset( $_REQUEST['newControl'] ) )
{
    $newControl = $_REQUEST['newControl'];
}
else
{
    if ( !empty($_REQUEST['cid']) )
    {
        $control = dbFetchOne( "select * from Controls where Id = '".dbEscape($_REQUEST['cid'])."'" );
    }
    else
    {
        $control = array(
            'Name' => $SLANG['New'],
            'Type' => "Local",
            'Protocol' => "",
            'CanWake' => "",
            'CanSleep' => "",
            'CanReset' => "",
            'CanMove' => "",
            'CanMoveDiag' => "",
            'CanMoveMap' => "",
            'CanMoveAbs' => "",
            'CanMoveRel' => "",
            'CanMoveCon' => "",
            'CanPan' => "",
            'MinPanRange' => "",
            'MaxPanRange' => "",
            'MinPanStep' => "",
            'MaxPanStep' => "",
            'HasPanSpeed' => "",
            'MinPanSpeed' => "",
            'MaxPanSpeed' => "",
            'HasTurboPan' => "",
            'TurboPanSpeed' => "",
            'CanTilt' => "",
            'MinTiltRange' => "",
            'MaxTiltRange' => "",
            'MinTiltStep' => "",
            'MaxTiltStep' => "",
            'HasTiltSpeed' => "",
            'MinTiltSpeed' => "",
            'MaxTiltSpeed' => "",
            'HasTurboTilt' => "",
            'TurboTiltSpeed' => "",
            'CanZoom' => "",
            'CanZoomAbs' => "",
            'CanZoomRel' => "",
            'CanZoomCon' => "",
            'MinZoomRange' => "",
            'MaxZoomRange' => "",
            'MinZoomStep' => "",
            'MaxZoomStep' => "",
            'HasZoomSpeed' => "",
            'MinZoomSpeed' => "",
            'MaxZoomSpeed' => "",
            'CanFocus' => "",
            'CanAutoFocus' => "",
            'CanFocusAbs' => "",
            'CanFocusRel' => "",
            'CanFocusCon' => "",
            'MinFocusRange' => "",
            'MaxFocusRange' => "",
            'MinFocusStep' => "",
            'MaxFocusStep' => "",
            'HasFocusSpeed' => "",
            'MinFocusSpeed' => "",
            'MaxFocusSpeed' => "",
            'CanIris' => "",
            'CanAutoIris' => "",
            'CanIrisAbs' => "",
            'CanIrisRel' => "",
            'CanIrisCon' => "",
            'MinIrisRange' => "",
            'MaxIrisRange' => "",
            'MinIrisStep' => "",
            'MaxIrisStep' => "",
            'HasIrisSpeed' => "",
            'MinIrisSpeed' => "",
            'MaxIrisSpeed' => "",
            'CanGain' => "",
            'CanAutoGain' => "",
            'CanGainAbs' => "",
            'CanGainRel' => "",
            'CanGainCon' => "",
            'MinGainRange' => "",
            'MaxGainRange' => "",
            'MinGainStep' => "",
            'MaxGainStep' => "",
            'HasGainSpeed' => "",
            'MinGainSpeed' => "",
            'MaxGainSpeed' => "",
            'CanWhite' => "",
            'CanAutoWhite' => "",
            'CanWhiteAbs' => "",
            'CanWhiteRel' => "",
            'CanWhiteCon' => "",
            'MinWhiteRange' => "",
            'MaxWhiteRange' => "",
            'MinWhiteStep' => "",
            'MaxWhiteStep' => "",
            'HasWhiteSpeed' => "",
            'MinWhiteSpeed' => "",
            'MaxWhiteSpeed' => "",
            'HasPresets' => "",
            'NumPresets' => "",
            'HasHomePreset' => "",
            'CanSetPresets' => "",
        );
    }
    $newControl = $control;
}

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['ControlCap']." - ".$newControl['Name'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['ControlCap'] ?> - <?= $newControl['Name'] ?></h2>
    </div>
    <div id="content">
      <ul class="tabList">
<?php
foreach ( $tabs as $name=>$value )
{
    if ( $tab == $name )
    {
?>
        <li class="active"><?= $value ?></li>
<?php
    }
    else
    {
?>
        <li><a href="#" onclick="submitTab( '<?= $name ?>' ); return( false );"><?= $value ?></a></li>
<?php
    }
}
?>
      </ul>
      <div class="clear"></div>
      <form name="contentForm" id="contentForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="tab" value="<?= $tab ?>"/>
        <input type="hidden" name="action" value="controlcap"/>
        <input type="hidden" name="cid" value="<?= requestVar('cid') ?>"/>
<?php
if ( $tab != 'main' )
{
?>
        <input type="hidden" name="newControl[Name]" value="<?= validHtmlStr($newControl['Name']) ?>"/>
        <input type="hidden" name="newControl[Type]" value="<?= validHtmlStr($newControl['Type']) ?>"/>
        <input type="hidden" name="newControl[Protocol]" value="<?= validHtmlStr($newControl['Protocol']) ?>"/>
        <input type="hidden" name="newControl[CanWake]" value="<?= !empty($newControl['CanWake']) ?>"/>
        <input type="hidden" name="newControl[CanSleep]" value="<?= !empty($newControl['CanSleep']) ?>"/>
        <input type="hidden" name="newControl[CanReset]" value="<?= !empty($newControl['CanReset']) ?>"/>
<?php
}
if ( $tab != 'move' )
{
?>
        <input type="hidden" name="newControl[CanMove]" value="<?= !empty($newControl['CanMove']) ?>"/>
        <input type="hidden" name="newControl[CanMoveDiag]" value="<?= !empty($newControl['CanMoveDiag']) ?>"/>
        <input type="hidden" name="newControl[CanMoveMap]" value="<?= !empty($newControl['CanMoveMap']) ?>"/>
        <input type="hidden" name="newControl[CanMoveAbs]" value="<?= !empty($newControl['CanMoveAbs']) ?>"/>
        <input type="hidden" name="newControl[CanMoveRel]" value="<?= !empty($newControl['CanMoveRel']) ?>"/>
        <input type="hidden" name="newControl[CanMoveCon]" value="<?= !empty($newControl['CanMoveCon']) ?>"/>
<?php
}
if ( $tab != 'pan' )
{
?>
        <input type="hidden" name="newControl[CanPan]" value="<?= !empty($newControl['CanPan']) ?>"/>
        <input type="hidden" name="newControl[MinPanRange]" value="<?= $newControl['MinPanRange'] ?>"/>
        <input type="hidden" name="newControl[MaxPanRange]" value="<?= $newControl['MaxPanRange'] ?>"/>
        <input type="hidden" name="newControl[MinPanStep]" value="<?= $newControl['MinPanStep'] ?>"/>
        <input type="hidden" name="newControl[MaxPanStep]" value="<?= $newControl['MaxPanStep'] ?>"/>
        <input type="hidden" name="newControl[HasPanSpeed]" value="<?= !empty($newControl['HasPanSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinPanSpeed]" value="<?= $newControl['MinPanSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxPanSpeed]" value="<?= $newControl['MaxPanSpeed'] ?>"/>
        <input type="hidden" name="newControl[HasTurboPan]" value="<?= !empty($newControl['HasTurboPan']) ?>"/>
        <input type="hidden" name="newControl[TurboPanSpeed]" value="<?= $newControl['TurboPanSpeed'] ?>"/>
<?php
}
if ( $tab != 'tilt' )
{
?>
        <input type="hidden" name="newControl[CanTilt]" value="<?= !empty($newControl['CanTilt']) ?>"/>
        <input type="hidden" name="newControl[MinTiltRange]" value="<?= $newControl['MinTiltRange'] ?>"/>
        <input type="hidden" name="newControl[MaxTiltRange]" value="<?= $newControl['MaxTiltRange'] ?>"/>
        <input type="hidden" name="newControl[MinTiltStep]" value="<?= $newControl['MinTiltStep'] ?>"/>
        <input type="hidden" name="newControl[MaxTiltStep]" value="<?= $newControl['MaxTiltStep'] ?>"/>
        <input type="hidden" name="newControl[HasTiltSpeed]" value="<?= !empty($newControl['HasTiltSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinTiltSpeed]" value="<?= $newControl['MinTiltSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxTiltSpeed]" value="<?= $newControl['MaxTiltSpeed'] ?>"/>
        <input type="hidden" name="newControl[HasTurboTilt]" value="<?= !empty($newControl['HasTurboTilt']) ?>"/>
        <input type="hidden" name="newControl[TurboTiltSpeed]" value="<?= $newControl['TurboTiltSpeed'] ?>"/>
<?php
}
if ( $tab != 'zoom' )
{
?>
        <input type="hidden" name="newControl[CanZoom]" value="<?= !empty($newControl['CanZoom']) ?>"/>
        <input type="hidden" name="newControl[CanZoomAbs]" value="<?= !empty($newControl['CanZoomAbs']) ?>"/>
        <input type="hidden" name="newControl[CanZoomRel]" value="<?= !empty($newControl['CanZoomRel']) ?>"/>
        <input type="hidden" name="newControl[CanZoomCon]" value="<?= !empty($newControl['CanZoomCon']) ?>"/>
        <input type="hidden" name="newControl[MinZoomRange]" value="<?= $newControl['MinZoomRange'] ?>"/>
        <input type="hidden" name="newControl[MaxZoomRange]" value="<?= $newControl['MaxZoomRange'] ?>"/>
        <input type="hidden" name="newControl[MinZoomStep]" value="<?= $newControl['MinZoomStep'] ?>"/>
        <input type="hidden" name="newControl[MaxZoomStep]" value="<?= $newControl['MaxZoomStep'] ?>"/>
        <input type="hidden" name="newControl[HasZoomSpeed]" value="<?= !empty($newControl['HasZoomSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinZoomSpeed]" value="<?= $newControl['MinZoomSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxZoomSpeed]" value="<?= $newControl['MaxZoomSpeed'] ?>"/>
<?php
}
if ( $tab != 'focus' )
{
?>
        <input type="hidden" name="newControl[CanFocus]" value="<?= !empty($newControl['CanFocus']) ?>"/>
        <input type="hidden" name="newControl[CanAutoFocus]" value="<?= !empty($newControl['CanAutoFocus']) ?>"/>
        <input type="hidden" name="newControl[CanFocusAbs]" value="<?= !empty($newControl['CanFocusAbs']) ?>"/>
        <input type="hidden" name="newControl[CanFocusRel]" value="<?= !empty($newControl['CanFocusRel']) ?>"/>
        <input type="hidden" name="newControl[CanFocusCon]" value="<?= !empty($newControl['CanFocusCon']) ?>"/>
        <input type="hidden" name="newControl[MinFocusRange]" value="<?= $newControl['MinFocusRange'] ?>"/>
        <input type="hidden" name="newControl[MaxFocusRange]" value="<?= $newControl['MaxFocusRange'] ?>"/>
        <input type="hidden" name="newControl[MinFocusStep]" value="<?= $newControl['MinFocusStep'] ?>"/>
        <input type="hidden" name="newControl[MaxFocusStep]" value="<?= $newControl['MaxFocusStep'] ?>"/>
        <input type="hidden" name="newControl[HasFocusSpeed]" value="<?= !empty($newControl['HasFocusSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinFocusSpeed]" value="<?= $newControl['MinFocusSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxFocusSpeed]" value="<?= $newControl['MaxFocusSpeed'] ?>"/>
<?php
}
if ( $tab != 'iris' )
{
?>
        <input type="hidden" name="newControl[CanIris]" value="<?= !empty($newControl['CanIris']) ?>"/>
        <input type="hidden" name="newControl[CanAutoIris]" value="<?= !empty($newControl['CanAutoIris']) ?>"/>
        <input type="hidden" name="newControl[CanIrisAbs]" value="<?= !empty($newControl['CanIrisAbs']) ?>"/>
        <input type="hidden" name="newControl[CanIrisRel]" value="<?= !empty($newControl['CanIrisRel']) ?>"/>
        <input type="hidden" name="newControl[CanIrisCon]" value="<?= !empty($newControl['CanIrisCon']) ?>"/>
        <input type="hidden" name="newControl[MinIrisRange]" value="<?= $newControl['MinIrisRange'] ?>"/>
        <input type="hidden" name="newControl[MaxIrisRange]" value="<?= $newControl['MaxIrisRange'] ?>"/>
        <input type="hidden" name="newControl[MinIrisStep]" value="<?= $newControl['MinIrisStep'] ?>"/>
        <input type="hidden" name="newControl[MaxIrisStep]" value="<?= $newControl['MaxIrisStep'] ?>"/>
        <input type="hidden" name="newControl[HasIrisSpeed]" value="<?= !empty($newControl['HasIrisSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinIrisSpeed]" value="<?= $newControl['MinIrisSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxIrisSpeed]" value="<?= $newControl['MaxIrisSpeed'] ?>"/>
<?php
}
if ( $tab != 'gain' )
{
?>
        <input type="hidden" name="newControl[CanGain]" value="<?= !empty($newControl['CanGain']) ?>"/>
        <input type="hidden" name="newControl[CanAutoGain]" value="<?= !empty($newControl['CanAutoGain']) ?>"/>
        <input type="hidden" name="newControl[CanGainAbs]" value="<?= !empty($newControl['CanGainAbs']) ?>"/>
        <input type="hidden" name="newControl[CanGainRel]" value="<?= !empty($newControl['CanGainRel']) ?>"/>
        <input type="hidden" name="newControl[CanGainCon]" value="<?= !empty($newControl['CanGainCon']) ?>"/>
        <input type="hidden" name="newControl[MinGainRange]" value="<?= $newControl['MinGainRange'] ?>"/>
        <input type="hidden" name="newControl[MaxGainRange]" value="<?= $newControl['MaxGainRange'] ?>"/>
        <input type="hidden" name="newControl[MinGainStep]" value="<?= $newControl['MinGainStep'] ?>"/>
        <input type="hidden" name="newControl[MaxGainStep]" value="<?= $newControl['MaxGainStep'] ?>"/>
        <input type="hidden" name="newControl[HasGainSpeed]" value="<?= !empty($newControl['HasGainSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinGainSpeed]" value="<?= $newControl['MinGainSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxGainSpeed]" value="<?= $newControl['MaxGainSpeed'] ?>"/>
<?php
}
if ( $tab != 'white' )
{
?>
        <input type="hidden" name="newControl[CanWhite]" value="<?= !empty($newControl['CanWhite']) ?>"/>
        <input type="hidden" name="newControl[CanAutoWhite]" value="<?= !empty($newControl['CanAutoWhite']) ?>"/>
        <input type="hidden" name="newControl[CanWhiteAbs]" value="<?= !empty($newControl['CanWhiteAbs']) ?>"/>
        <input type="hidden" name="newControl[CanWhiteRel]" value="<?= !empty($newControl['CanWhiteRel']) ?>"/>
        <input type="hidden" name="newControl[CanWhiteCon]" value="<?= !empty($newControl['CanWhiteCon']) ?>"/>
        <input type="hidden" name="newControl[MinWhiteRange]" value="<?= $newControl['MinWhiteRange'] ?>"/>
        <input type="hidden" name="newControl[MaxWhiteRange]" value="<?= $newControl['MaxWhiteRange'] ?>"/>
        <input type="hidden" name="newControl[MinWhiteStep]" value="<?= $newControl['MinWhiteStep'] ?>"/>
        <input type="hidden" name="newControl[MaxWhiteStep]" value="<?= $newControl['MaxWhiteStep'] ?>"/>
        <input type="hidden" name="newControl[HasWhiteSpeed]" value="<?= !empty($newControl['HasWhiteSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinWhiteSpeed]" value="<?= $newControl['MinWhiteSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxWhiteSpeed]" value="<?= $newControl['MaxWhiteSpeed'] ?>"/>
<?php
}
if ( $tab != 'presets' )
{
?>
        <input type="hidden" name="newControl[HasPresets]" value="<?= !empty($newControl['HasPresets']) ?>"/>
        <input type="hidden" name="newControl[NumPresets]" value="<?= $newControl['NumPresets'] ?>"/>
        <input type="hidden" name="newControl[HasHomePreset]" value="<?= !empty($newControl['HasHomePreset']) ?>"/>
        <input type="hidden" name="newControl[CanSetPresets]" value="<?= !empty($newControl['CanSetPresets']) ?>"/>
<?php
}
?>
        <table id="contentTable" class="major" cellspacing="0">
          <tbody>
<?php
switch ( $tab )
{
    case 'main' :
    {
?>
            <tr><th scope="row"><?= $SLANG['Name'] ?></th><td><input type="text" name="newControl[Name]" value="<?= validHtmlStr($newControl['Name']) ?>" size="24"/></td></tr>
<?php
        $types = array( 'Local'=>$SLANG['Local'], 'Remote'=>$SLANG['Remote'] );
?>
            <tr><th scope="row"><?= $SLANG['Type'] ?></th><td><?= buildSelect( "newControl[Type]", $types ); ?></td></tr>
            <tr><th scope="row"><?= $SLANG['Protocol'] ?></th><td><input type="text" name="newControl[Protocol]" value="<?= validHtmlStr($newControl['Protocol']) ?>" size="24"/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanWake'] ?></th><td><input type="checkbox" name="newControl[CanWake]" value="1"<?php if ( !empty($newControl['CanWake']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanSleep'] ?></th><td><input type="checkbox" name="newControl[CanSleep]" value="1"<?php if ( !empty($newControl['CanSleep']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanReset'] ?></th><td><input type="checkbox" name="newControl[CanReset]" value="1"<?php if ( !empty($newControl['CanReset']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        break;
    }
    case 'move' :
    {
?>
            <tr><th scope="row"><?= $SLANG['CanMove'] ?></th><td><input type="checkbox" name="newControl[CanMove]" value="1"<?php if ( !empty($newControl['CanMove']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanMoveDiag'] ?></th><td><input type="checkbox" name="newControl[CanMoveDiag]" value="1"<?php if ( !empty($newControl['CanMoveDiag']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanMoveMap'] ?></th><td><input type="checkbox" name="newControl[CanMoveMap]" value="1"<?php if ( !empty($newControl['CanMoveMap']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanMoveAbs'] ?></th><td><input type="checkbox" name="newControl[CanMoveAbs]" value="1"<?php if ( !empty($newControl['CanMoveAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanMoveRel'] ?></th><td><input type="checkbox" name="newControl[CanMoveRel]" value="1"<?php if ( !empty($newControl['CanMoveRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanMoveCon'] ?></th><td><input type="checkbox" name="newControl[CanMoveCon]" value="1"<?php if ( !empty($newControl['CanMoveCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        break;
    }
    case 'pan' :
    {
?>
            <tr><th scope="row"><?= $SLANG['CanPan'] ?></th><td><input type="checkbox" name="newControl[CanPan]" value="1"<?php if ( !empty($newControl['CanPan']) ) { ?> checked="checked"<?php } ?>></td></tr>
            <tr><th scope="row"><?= $SLANG['MinPanRange'] ?></th><td><input type="text" name="newControl[MinPanRange]" value="<?= $newControl['MinPanRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxPanRange'] ?></th><td><input type="text" name="newControl[MaxPanRange]" value="<?= $newControl['MaxPanRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinPanStep'] ?></th><td><input type="text" name="newControl[MinPanStep]" value="<?= $newControl['MinPanStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxPanStep'] ?></th><td><input type="text" name="newControl[MaxPanStep]" value="<?= $newControl['MaxPanStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['HasPanSpeed'] ?></th><td><input type="checkbox" name="newControl[HasPanSpeed]" value="1"<?php if ( !empty($newControl['HasPanSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinPanSpeed'] ?></th><td><input type="text" name="newControl[MinPanSpeed]" value="<?= $newControl['MinPanSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxPanSpeed'] ?></th><td><input type="text" name="newControl[MaxPanSpeed]" value="<?= $newControl['MaxPanSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['HasTurboPan'] ?></th><td><input type="checkbox" name="newControl[HasTurboPan]" value="1"<?php if ( !empty($newControl['HasTurboPan']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['TurboPanSpeed'] ?></th><td><input type="text" name="newControl[TurboPanSpeed]" value="<?= $newControl['TurboPanSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'tilt' :
    {
?>
            <tr><th scope="row"><?= $SLANG['CanTilt'] ?></th><td><input type="checkbox" name="newControl[CanTilt]" value="1"<?php if ( !empty($newControl['CanTilt']) ) { ?> checked="checked"<?php } ?>></td></tr>
            <tr><th scope="row"><?= $SLANG['MinTiltRange'] ?></th><td><input type="text" name="newControl[MinTiltRange]" value="<?= $newControl['MinTiltRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxTiltRange'] ?></th><td><input type="text" name="newControl[MaxTiltRange]" value="<?= $newControl['MaxTiltRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinTiltStep'] ?></th><td><input type="text" name="newControl[MinTiltStep]" value="<?= $newControl['MinTiltStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxTiltStep'] ?></th><td><input type="text" name="newControl[MaxTiltStep]" value="<?= $newControl['MaxTiltStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['HasTiltSpeed'] ?></th><td><input type="checkbox" name="newControl[HasTiltSpeed]" value="1"<?php if ( !empty($newControl['HasTiltSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinTiltSpeed'] ?></th><td><input type="text" name="newControl[MinTiltSpeed]" value="<?= $newControl['MinTiltSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxTiltSpeed'] ?></th><td><input type="text" name="newControl[MaxTiltSpeed]" value="<?= $newControl['MaxTiltSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['HasTurboTilt'] ?></th><td><input type="checkbox" name="newControl[HasTurboTilt]" value="1"<?php if ( !empty($newControl['HasTurboTilt']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['TurboTiltSpeed'] ?></th><td><input type="text" name="newControl[TurboTiltSpeed]" value="<?= $newControl['TurboTiltSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'zoom' :
    {
?>
            <tr><th scope="row"><?= $SLANG['CanZoom'] ?></th><td><input type="checkbox" name="newControl[CanZoom]" value="1"<?php if ( !empty($newControl['CanZoom']) ) { ?> checked="checked"<?php } ?>></td></tr>
            <tr><th scope="row"><?= $SLANG['CanZoomAbs'] ?></th><td><input type="checkbox" name="newControl[CanZoomAbs]" value="1"<?php if ( !empty($newControl['CanZoomAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanZoomRel'] ?></th><td><input type="checkbox" name="newControl[CanZoomRel]" value="1"<?php if ( !empty($newControl['CanZoomRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanZoomCon'] ?></th><td><input type="checkbox" name="newControl[CanZoomCon]" value="1"<?php if ( !empty($newControl['CanZoomCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinZoomRange'] ?></th><td><input type="text" name="newControl[MinZoomRange]" value="<?= $newControl['MinZoomRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxZoomRange'] ?></th><td><input type="text" name="newControl[MaxZoomRange]" value="<?= $newControl['MaxZoomRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinZoomStep'] ?></th><td><input type="text" name="newControl[MinZoomStep]" value="<?= $newControl['MinZoomStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxZoomStep'] ?></th><td><input type="text" name="newControl[MaxZoomStep]" value="<?= $newControl['MaxZoomStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['HasZoomSpeed'] ?></th><td><input type="checkbox" name="newControl[HasZoomSpeed]" value="1"<?php if ( !empty($newControl['HasZoomSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinZoomSpeed'] ?></th><td><input type="text" name="newControl[MinZoomSpeed]" value="<?= $newControl['MinZoomSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxZoomSpeed'] ?></th><td><input type="text" name="newControl[MaxZoomSpeed]" value="<?= $newControl['MaxZoomSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'focus' :
    {
?>
            <tr><th scope="row"><?= $SLANG['CanFocus'] ?></th><td><input type="checkbox" name="newControl[CanFocus]" value="1"<?php if ( !empty($newControl['CanFocus']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanAutoFocus'] ?></th><td><input type="checkbox" name="newControl[CanAutoFocus]" value="1"<?php if ( !empty($newControl['CanAutoFocus']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanFocusAbs'] ?></th><td><input type="checkbox" name="newControl[CanFocusAbs]" value="1"<?php if ( !empty($newControl['CanFocusAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanFocusRel'] ?></th><td><input type="checkbox" name="newControl[CanFocusRel]" value="1"<?php if ( !empty($newControl['CanFocusRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanFocusCon'] ?></th><td><input type="checkbox" name="newControl[CanFocusCon]" value="1"<?php if ( !empty($newControl['CanFocusCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinFocusRange'] ?></th><td><input type="text" name="newControl[MinFocusRange]" value="<?= $newControl['MinFocusRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxFocusRange'] ?></th><td><input type="text" name="newControl[MaxFocusRange]" value="<?= $newControl['MaxFocusRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinFocusStep'] ?></th><td><input type="text" name="newControl[MinFocusStep]" value="<?= $newControl['MinFocusStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxFocusStep'] ?></th><td><input type="text" name="newControl[MaxFocusStep]" value="<?= $newControl['MaxFocusStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['HasFocusSpeed'] ?></th><td><input type="checkbox" name="newControl[HasFocusSpeed]" value="1"<?php if ( !empty($newControl['HasFocusSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinFocusSpeed'] ?></th><td><input type="text" name="newControl[MinFocusSpeed]" value="<?= $newControl['MinFocusSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxFocusSpeed'] ?></th><td><input type="text" name="newControl[MaxFocusSpeed]" value="<?= $newControl['MaxFocusSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'iris' :
    {
?>
            <tr><th scope="row"><?= $SLANG['CanIris'] ?></th><td><input type="checkbox" name="newControl[CanIris]" value="1"<?php if ( !empty($newControl['CanIris']) ) { ?> checked="checked"<?php } ?>></td></tr>
            <tr><th scope="row"><?= $SLANG['CanAutoIris'] ?></th><td><input type="checkbox" name="newControl[CanAutoIris]" value="1"<?php if ( !empty($newControl['CanAutoIris']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanIrisAbs'] ?></th><td><input type="checkbox" name="newControl[CanIrisAbs]" value="1"<?php if ( !empty($newControl['CanIrisAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanIrisRel'] ?></th><td><input type="checkbox" name="newControl[CanIrisRel]" value="1"<?php if ( !empty($newControl['CanIrisRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanIrisCon'] ?></th><td><input type="checkbox" name="newControl[CanIrisCon]" value="1"<?php if ( !empty($newControl['CanIrisCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinIrisRange'] ?></th><td><input type="text" name="newControl[MinIrisRange]" value="<?= $newControl['MinIrisRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxIrisRange'] ?></th><td><input type="text" name="newControl[MaxIrisRange]" value="<?= $newControl['MaxIrisRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinIrisStep'] ?></th><td><input type="text" name="newControl[MinIrisStep]" value="<?= $newControl['MinIrisStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxIrisStep'] ?></th><td><input type="text" name="newControl[MaxIrisStep]" value="<?= $newControl['MaxIrisStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['HasIrisSpeed'] ?></th><td><input type="checkbox" name="newControl[HasIrisSpeed]" value="1"<?php if ( !empty($newControl['HasIrisSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinIrisSpeed'] ?></th><td><input type="text" name="newControl[MinIrisSpeed]" value="<?= $newControl['MinIrisSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxIrisSpeed'] ?></th><td><input type="text" name="newControl[MaxIrisSpeed]" value="<?= $newControl['MaxIrisSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'gain' :
    {
?>
            <tr><th scope="row"><?= $SLANG['CanGain'] ?></th><td><input type="checkbox" name="newControl[CanGain]" value="1"<?php if ( !empty($newControl['CanGain']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanAutoGain'] ?></th><td><input type="checkbox" name="newControl[CanAutoGain]" value="1"<?php if ( !empty($newControl['CanAutoGain']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanGainAbs'] ?></th><td><input type="checkbox" name="newControl[CanGainAbs]" value="1"<?php if ( !empty($newControl['CanGainAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanGainRel'] ?></th><td><input type="checkbox" name="newControl[CanGainRel]" value="1"<?php if ( !empty($newControl['CanGainRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanGainCon'] ?></th><td><input type="checkbox" name="newControl[CanGainCon]" value="1"<?php if ( !empty($newControl['CanGainCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinGainRange'] ?></th><td><input type="text" name="newControl[MinGainRange]" value="<?= $newControl['MinGainRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxGainRange'] ?></th><td><input type="text" name="newControl[MaxGainRange]" value="<?= $newControl['MaxGainRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinGainStep'] ?></th><td><input type="text" name="newControl[MinGainStep]" value="<?= $newControl['MinGainStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxGainStep'] ?></th><td><input type="text" name="newControl[MaxGainStep]" value="<?= $newControl['MaxGainStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['HasGainSpeed'] ?></th><td><input type="checkbox" name="newControl[HasGainSpeed]" value="1"<?php if ( !empty($newControl['HasGainSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinGainSpeed'] ?></th><td><input type="text" name="newControl[MinGainSpeed]" value="<?= $newControl['MinGainSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxGainSpeed'] ?></th><td><input type="text" name="newControl[MaxGainSpeed]" value="<?= $newControl['MaxGainSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'white' :
    {
?>
            <tr><th scope="row"><?= $SLANG['CanWhite'] ?></th><td><input type="checkbox" name="newControl[CanWhite]" value="1"<?php if ( !empty($newControl['CanWhite']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanAutoWhite'] ?></th><td><input type="checkbox" name="newControl[CanAutoWhite]" value="1"<?php if ( !empty($newControl['CanAutoWhite']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanWhiteAbs'] ?></th><td><input type="checkbox" name="newControl[CanWhiteAbs]" value="1"<?php if ( !empty($newControl['CanWhiteAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanWhiteRel'] ?></th><td><input type="checkbox" name="newControl[CanWhiteRel]" value="1"<?php if ( !empty($newControl['CanWhiteRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanWhiteCon'] ?></th><td><input type="checkbox" name="newControl[CanWhiteCon]" value="1"<?php if ( !empty($newControl['CanWhiteCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinWhiteRange'] ?></th><td><input type="text" name="newControl[MinWhiteRange]" value="<?= $newControl['MinWhiteRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxWhiteRange'] ?></th><td><input type="text" name="newControl[MaxWhiteRange]" value="<?= $newControl['MaxWhiteRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinWhiteStep'] ?></th><td><input type="text" name="newControl[MinWhiteStep]" value="<?= $newControl['MinWhiteStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxWhiteStep'] ?></th><td><input type="text" name="newControl[MaxWhiteStep]" value="<?= $newControl['MaxWhiteStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['HasWhiteSpeed'] ?></th><td><input type="checkbox" name="newControl[HasWhiteSpeed]" value="1"<?php if ( !empty($newControl['HasWhiteSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['MinWhiteSpeed'] ?></th><td><input type="text" name="newControl[MinWhiteSpeed]" value="<?= $newControl['MinWhiteSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['MaxWhiteSpeed'] ?></th><td><input type="text" name="newControl[MaxWhiteSpeed]" value="<?= $newControl['MaxWhiteSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'presets' :
    {
?>
            <tr><th scope="row"><?= $SLANG['HasPresets'] ?></th><td><input type="checkbox" name="newControl[HasPresets]" value="1"<?php if ( !empty($newControl['HasPresets']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['NumPresets'] ?></th><td><input type="text" name="newControl[NumPresets]" value="<?= $newControl['NumPresets'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?= $SLANG['HasHomePreset'] ?></th><td><input type="checkbox" name="newControl[HasHomePreset]" value="1"<?php if ( !empty($newControl['HasHomePreset']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?= $SLANG['CanSetPresets'] ?></th><td><input type="checkbox" name="newControl[CanSetPresets]" value="1"<?php if ( !empty($newControl['CanSetPresets']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        break;
    }
}
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Save'] ?>"<?php if ( !canEdit( 'Control' ) ) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
