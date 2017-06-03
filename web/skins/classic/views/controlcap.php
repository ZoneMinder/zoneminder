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

if ( !canEdit( 'Control' ) )
{
    $view = "error";
    return;
}

$tabs = array();
$tabs["main"] = translate('Main');
$tabs["move"] = translate('Move');
$tabs["pan"] = translate('Pan');
$tabs["tilt"] = translate('Tilt');
$tabs["zoom"] = translate('Zoom');
$tabs["focus"] = translate('Focus');
$tabs["white"] = translate('White');
$tabs["iris"] = translate('Iris');
$tabs["presets"] = translate('Presets');

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
        $control = dbFetchOne( 'SELECT * FROM Controls WHERE Id = ?', NULL, array($_REQUEST['cid'] ) );
    }
    else
    {
        $control = array(
            'Name' => translate('New'),
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

xhtmlHeaders(__FILE__, translate('ControlCap')." - ".$newControl['Name'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('ControlCap') ?> - <?php echo $newControl['Name'] ?></h2>
    </div>
    <div id="content">
      <ul class="tabList">
<?php
foreach ( $tabs as $name=>$value )
{
    if ( $tab == $name )
    {
?>
        <li class="active"><?php echo $value ?></li>
<?php
    }
    else
    {
?>
        <li><a href="#" onclick="submitTab( '<?php echo $name ?>' ); return( false );"><?php echo $value ?></a></li>
<?php
    }
}
?>
      </ul>
      <div class="clear"></div>
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>" onsubmit="return validateForm( this )">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
        <input type="hidden" name="action" value="controlcap"/>
        <input type="hidden" name="cid" value="<?php echo requestVar('cid') ?>"/>
<?php
if ( $tab != 'main' )
{
?>
        <input type="hidden" name="newControl[Name]" value="<?php echo validHtmlStr($newControl['Name']) ?>"/>
        <input type="hidden" name="newControl[Type]" value="<?php echo validHtmlStr($newControl['Type']) ?>"/>
        <input type="hidden" name="newControl[Protocol]" value="<?php echo validHtmlStr($newControl['Protocol']) ?>"/>
        <input type="hidden" name="newControl[CanWake]" value="<?php echo !empty($newControl['CanWake']) ?>"/>
        <input type="hidden" name="newControl[CanSleep]" value="<?php echo !empty($newControl['CanSleep']) ?>"/>
        <input type="hidden" name="newControl[CanReset]" value="<?php echo !empty($newControl['CanReset']) ?>"/>
<?php
}
if ( $tab != 'move' )
{
?>
        <input type="hidden" name="newControl[CanMove]" value="<?php echo !empty($newControl['CanMove']) ?>"/>
        <input type="hidden" name="newControl[CanMoveDiag]" value="<?php echo !empty($newControl['CanMoveDiag']) ?>"/>
        <input type="hidden" name="newControl[CanMoveMap]" value="<?php echo !empty($newControl['CanMoveMap']) ?>"/>
        <input type="hidden" name="newControl[CanMoveAbs]" value="<?php echo !empty($newControl['CanMoveAbs']) ?>"/>
        <input type="hidden" name="newControl[CanMoveRel]" value="<?php echo !empty($newControl['CanMoveRel']) ?>"/>
        <input type="hidden" name="newControl[CanMoveCon]" value="<?php echo !empty($newControl['CanMoveCon']) ?>"/>
<?php
}
if ( $tab != 'pan' )
{
?>
        <input type="hidden" name="newControl[CanPan]" value="<?php echo !empty($newControl['CanPan']) ?>"/>
        <input type="hidden" name="newControl[MinPanRange]" value="<?php echo $newControl['MinPanRange'] ?>"/>
        <input type="hidden" name="newControl[MaxPanRange]" value="<?php echo $newControl['MaxPanRange'] ?>"/>
        <input type="hidden" name="newControl[MinPanStep]" value="<?php echo $newControl['MinPanStep'] ?>"/>
        <input type="hidden" name="newControl[MaxPanStep]" value="<?php echo $newControl['MaxPanStep'] ?>"/>
        <input type="hidden" name="newControl[HasPanSpeed]" value="<?php echo !empty($newControl['HasPanSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinPanSpeed]" value="<?php echo $newControl['MinPanSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxPanSpeed]" value="<?php echo $newControl['MaxPanSpeed'] ?>"/>
        <input type="hidden" name="newControl[HasTurboPan]" value="<?php echo !empty($newControl['HasTurboPan']) ?>"/>
        <input type="hidden" name="newControl[TurboPanSpeed]" value="<?php echo $newControl['TurboPanSpeed'] ?>"/>
<?php
}
if ( $tab != 'tilt' )
{
?>
        <input type="hidden" name="newControl[CanTilt]" value="<?php echo !empty($newControl['CanTilt']) ?>"/>
        <input type="hidden" name="newControl[MinTiltRange]" value="<?php echo $newControl['MinTiltRange'] ?>"/>
        <input type="hidden" name="newControl[MaxTiltRange]" value="<?php echo $newControl['MaxTiltRange'] ?>"/>
        <input type="hidden" name="newControl[MinTiltStep]" value="<?php echo $newControl['MinTiltStep'] ?>"/>
        <input type="hidden" name="newControl[MaxTiltStep]" value="<?php echo $newControl['MaxTiltStep'] ?>"/>
        <input type="hidden" name="newControl[HasTiltSpeed]" value="<?php echo !empty($newControl['HasTiltSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinTiltSpeed]" value="<?php echo $newControl['MinTiltSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxTiltSpeed]" value="<?php echo $newControl['MaxTiltSpeed'] ?>"/>
        <input type="hidden" name="newControl[HasTurboTilt]" value="<?php echo !empty($newControl['HasTurboTilt']) ?>"/>
        <input type="hidden" name="newControl[TurboTiltSpeed]" value="<?php echo $newControl['TurboTiltSpeed'] ?>"/>
<?php
}
if ( $tab != 'zoom' )
{
?>
        <input type="hidden" name="newControl[CanZoom]" value="<?php echo !empty($newControl['CanZoom']) ?>"/>
        <input type="hidden" name="newControl[CanZoomAbs]" value="<?php echo !empty($newControl['CanZoomAbs']) ?>"/>
        <input type="hidden" name="newControl[CanZoomRel]" value="<?php echo !empty($newControl['CanZoomRel']) ?>"/>
        <input type="hidden" name="newControl[CanZoomCon]" value="<?php echo !empty($newControl['CanZoomCon']) ?>"/>
        <input type="hidden" name="newControl[MinZoomRange]" value="<?php echo $newControl['MinZoomRange'] ?>"/>
        <input type="hidden" name="newControl[MaxZoomRange]" value="<?php echo $newControl['MaxZoomRange'] ?>"/>
        <input type="hidden" name="newControl[MinZoomStep]" value="<?php echo $newControl['MinZoomStep'] ?>"/>
        <input type="hidden" name="newControl[MaxZoomStep]" value="<?php echo $newControl['MaxZoomStep'] ?>"/>
        <input type="hidden" name="newControl[HasZoomSpeed]" value="<?php echo !empty($newControl['HasZoomSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinZoomSpeed]" value="<?php echo $newControl['MinZoomSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxZoomSpeed]" value="<?php echo $newControl['MaxZoomSpeed'] ?>"/>
<?php
}
if ( $tab != 'focus' )
{
?>
        <input type="hidden" name="newControl[CanFocus]" value="<?php echo !empty($newControl['CanFocus']) ?>"/>
        <input type="hidden" name="newControl[CanAutoFocus]" value="<?php echo !empty($newControl['CanAutoFocus']) ?>"/>
        <input type="hidden" name="newControl[CanFocusAbs]" value="<?php echo !empty($newControl['CanFocusAbs']) ?>"/>
        <input type="hidden" name="newControl[CanFocusRel]" value="<?php echo !empty($newControl['CanFocusRel']) ?>"/>
        <input type="hidden" name="newControl[CanFocusCon]" value="<?php echo !empty($newControl['CanFocusCon']) ?>"/>
        <input type="hidden" name="newControl[MinFocusRange]" value="<?php echo $newControl['MinFocusRange'] ?>"/>
        <input type="hidden" name="newControl[MaxFocusRange]" value="<?php echo $newControl['MaxFocusRange'] ?>"/>
        <input type="hidden" name="newControl[MinFocusStep]" value="<?php echo $newControl['MinFocusStep'] ?>"/>
        <input type="hidden" name="newControl[MaxFocusStep]" value="<?php echo $newControl['MaxFocusStep'] ?>"/>
        <input type="hidden" name="newControl[HasFocusSpeed]" value="<?php echo !empty($newControl['HasFocusSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinFocusSpeed]" value="<?php echo $newControl['MinFocusSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxFocusSpeed]" value="<?php echo $newControl['MaxFocusSpeed'] ?>"/>
<?php
}
if ( $tab != 'iris' )
{
?>
        <input type="hidden" name="newControl[CanIris]" value="<?php echo !empty($newControl['CanIris']) ?>"/>
        <input type="hidden" name="newControl[CanAutoIris]" value="<?php echo !empty($newControl['CanAutoIris']) ?>"/>
        <input type="hidden" name="newControl[CanIrisAbs]" value="<?php echo !empty($newControl['CanIrisAbs']) ?>"/>
        <input type="hidden" name="newControl[CanIrisRel]" value="<?php echo !empty($newControl['CanIrisRel']) ?>"/>
        <input type="hidden" name="newControl[CanIrisCon]" value="<?php echo !empty($newControl['CanIrisCon']) ?>"/>
        <input type="hidden" name="newControl[MinIrisRange]" value="<?php echo $newControl['MinIrisRange'] ?>"/>
        <input type="hidden" name="newControl[MaxIrisRange]" value="<?php echo $newControl['MaxIrisRange'] ?>"/>
        <input type="hidden" name="newControl[MinIrisStep]" value="<?php echo $newControl['MinIrisStep'] ?>"/>
        <input type="hidden" name="newControl[MaxIrisStep]" value="<?php echo $newControl['MaxIrisStep'] ?>"/>
        <input type="hidden" name="newControl[HasIrisSpeed]" value="<?php echo !empty($newControl['HasIrisSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinIrisSpeed]" value="<?php echo $newControl['MinIrisSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxIrisSpeed]" value="<?php echo $newControl['MaxIrisSpeed'] ?>"/>
<?php
}
if ( $tab != 'gain' )
{
?>
        <input type="hidden" name="newControl[CanGain]" value="<?php echo !empty($newControl['CanGain']) ?>"/>
        <input type="hidden" name="newControl[CanAutoGain]" value="<?php echo !empty($newControl['CanAutoGain']) ?>"/>
        <input type="hidden" name="newControl[CanGainAbs]" value="<?php echo !empty($newControl['CanGainAbs']) ?>"/>
        <input type="hidden" name="newControl[CanGainRel]" value="<?php echo !empty($newControl['CanGainRel']) ?>"/>
        <input type="hidden" name="newControl[CanGainCon]" value="<?php echo !empty($newControl['CanGainCon']) ?>"/>
        <input type="hidden" name="newControl[MinGainRange]" value="<?php echo $newControl['MinGainRange'] ?>"/>
        <input type="hidden" name="newControl[MaxGainRange]" value="<?php echo $newControl['MaxGainRange'] ?>"/>
        <input type="hidden" name="newControl[MinGainStep]" value="<?php echo $newControl['MinGainStep'] ?>"/>
        <input type="hidden" name="newControl[MaxGainStep]" value="<?php echo $newControl['MaxGainStep'] ?>"/>
        <input type="hidden" name="newControl[HasGainSpeed]" value="<?php echo !empty($newControl['HasGainSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinGainSpeed]" value="<?php echo $newControl['MinGainSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxGainSpeed]" value="<?php echo $newControl['MaxGainSpeed'] ?>"/>
<?php
}
if ( $tab != 'white' )
{
?>
        <input type="hidden" name="newControl[CanWhite]" value="<?php echo !empty($newControl['CanWhite']) ?>"/>
        <input type="hidden" name="newControl[CanAutoWhite]" value="<?php echo !empty($newControl['CanAutoWhite']) ?>"/>
        <input type="hidden" name="newControl[CanWhiteAbs]" value="<?php echo !empty($newControl['CanWhiteAbs']) ?>"/>
        <input type="hidden" name="newControl[CanWhiteRel]" value="<?php echo !empty($newControl['CanWhiteRel']) ?>"/>
        <input type="hidden" name="newControl[CanWhiteCon]" value="<?php echo !empty($newControl['CanWhiteCon']) ?>"/>
        <input type="hidden" name="newControl[MinWhiteRange]" value="<?php echo $newControl['MinWhiteRange'] ?>"/>
        <input type="hidden" name="newControl[MaxWhiteRange]" value="<?php echo $newControl['MaxWhiteRange'] ?>"/>
        <input type="hidden" name="newControl[MinWhiteStep]" value="<?php echo $newControl['MinWhiteStep'] ?>"/>
        <input type="hidden" name="newControl[MaxWhiteStep]" value="<?php echo $newControl['MaxWhiteStep'] ?>"/>
        <input type="hidden" name="newControl[HasWhiteSpeed]" value="<?php echo !empty($newControl['HasWhiteSpeed']) ?>"/>
        <input type="hidden" name="newControl[MinWhiteSpeed]" value="<?php echo $newControl['MinWhiteSpeed'] ?>"/>
        <input type="hidden" name="newControl[MaxWhiteSpeed]" value="<?php echo $newControl['MaxWhiteSpeed'] ?>"/>
<?php
}
if ( $tab != 'presets' )
{
?>
        <input type="hidden" name="newControl[HasPresets]" value="<?php echo !empty($newControl['HasPresets']) ?>"/>
        <input type="hidden" name="newControl[NumPresets]" value="<?php echo $newControl['NumPresets'] ?>"/>
        <input type="hidden" name="newControl[HasHomePreset]" value="<?php echo !empty($newControl['HasHomePreset']) ?>"/>
        <input type="hidden" name="newControl[CanSetPresets]" value="<?php echo !empty($newControl['CanSetPresets']) ?>"/>
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
            <tr><th scope="row"><?php echo translate('Name') ?></th><td><input type="text" name="newControl[Name]" value="<?php echo validHtmlStr($newControl['Name']) ?>" size="24"/></td></tr>
<?php
        $types = array( 'Local'=>translate('Local'), 'Remote'=>translate('Remote'), 'Ffmpeg'=>translate('Ffmpeg'), 'Libvlc'=>translate('Libvlc'), 'cURL'=>"cURL");
?>
            <tr><th scope="row"><?php echo translate('Type') ?></th><td><?php echo buildSelect( "newControl[Type]", $types ); ?></td></tr>
            <tr><th scope="row"><?php echo translate('Protocol') ?></th><td><input type="text" name="newControl[Protocol]" value="<?php echo validHtmlStr($newControl['Protocol']) ?>" size="24"/></td></tr>
            <tr><th scope="row"><?php echo translate('CanWake') ?></th><td><input type="checkbox" name="newControl[CanWake]" value="1"<?php if ( !empty($newControl['CanWake']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanSleep') ?></th><td><input type="checkbox" name="newControl[CanSleep]" value="1"<?php if ( !empty($newControl['CanSleep']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanReset') ?></th><td><input type="checkbox" name="newControl[CanReset]" value="1"<?php if ( !empty($newControl['CanReset']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        break;
    }
    case 'move' :
    {
?>
            <tr><th scope="row"><?php echo translate('CanMove') ?></th><td><input type="checkbox" name="newControl[CanMove]" value="1"<?php if ( !empty($newControl['CanMove']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanMoveDiag') ?></th><td><input type="checkbox" name="newControl[CanMoveDiag]" value="1"<?php if ( !empty($newControl['CanMoveDiag']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanMoveMap') ?></th><td><input type="checkbox" name="newControl[CanMoveMap]" value="1"<?php if ( !empty($newControl['CanMoveMap']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanMoveAbs') ?></th><td><input type="checkbox" name="newControl[CanMoveAbs]" value="1"<?php if ( !empty($newControl['CanMoveAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanMoveRel') ?></th><td><input type="checkbox" name="newControl[CanMoveRel]" value="1"<?php if ( !empty($newControl['CanMoveRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanMoveCon') ?></th><td><input type="checkbox" name="newControl[CanMoveCon]" value="1"<?php if ( !empty($newControl['CanMoveCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        break;
    }
    case 'pan' :
    {
?>
            <tr><th scope="row"><?php echo translate('CanPan') ?></th><td><input type="checkbox" name="newControl[CanPan]" value="1"<?php if ( !empty($newControl['CanPan']) ) { ?> checked="checked"<?php } ?>></td></tr>
            <tr><th scope="row"><?php echo translate('MinPanRange') ?></th><td><input type="text" name="newControl[MinPanRange]" value="<?php echo $newControl['MinPanRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxPanRange') ?></th><td><input type="text" name="newControl[MaxPanRange]" value="<?php echo $newControl['MaxPanRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MinPanStep') ?></th><td><input type="text" name="newControl[MinPanStep]" value="<?php echo $newControl['MinPanStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxPanStep') ?></th><td><input type="text" name="newControl[MaxPanStep]" value="<?php echo $newControl['MaxPanStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('HasPanSpeed') ?></th><td><input type="checkbox" name="newControl[HasPanSpeed]" value="1"<?php if ( !empty($newControl['HasPanSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinPanSpeed') ?></th><td><input type="text" name="newControl[MinPanSpeed]" value="<?php echo $newControl['MinPanSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxPanSpeed') ?></th><td><input type="text" name="newControl[MaxPanSpeed]" value="<?php echo $newControl['MaxPanSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('HasTurboPan') ?></th><td><input type="checkbox" name="newControl[HasTurboPan]" value="1"<?php if ( !empty($newControl['HasTurboPan']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('TurboPanSpeed') ?></th><td><input type="text" name="newControl[TurboPanSpeed]" value="<?php echo $newControl['TurboPanSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'tilt' :
    {
?>
            <tr><th scope="row"><?php echo translate('CanTilt') ?></th><td><input type="checkbox" name="newControl[CanTilt]" value="1"<?php if ( !empty($newControl['CanTilt']) ) { ?> checked="checked"<?php } ?>></td></tr>
            <tr><th scope="row"><?php echo translate('MinTiltRange') ?></th><td><input type="text" name="newControl[MinTiltRange]" value="<?php echo $newControl['MinTiltRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxTiltRange') ?></th><td><input type="text" name="newControl[MaxTiltRange]" value="<?php echo $newControl['MaxTiltRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MinTiltStep') ?></th><td><input type="text" name="newControl[MinTiltStep]" value="<?php echo $newControl['MinTiltStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxTiltStep') ?></th><td><input type="text" name="newControl[MaxTiltStep]" value="<?php echo $newControl['MaxTiltStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('HasTiltSpeed') ?></th><td><input type="checkbox" name="newControl[HasTiltSpeed]" value="1"<?php if ( !empty($newControl['HasTiltSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinTiltSpeed') ?></th><td><input type="text" name="newControl[MinTiltSpeed]" value="<?php echo $newControl['MinTiltSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxTiltSpeed') ?></th><td><input type="text" name="newControl[MaxTiltSpeed]" value="<?php echo $newControl['MaxTiltSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('HasTurboTilt') ?></th><td><input type="checkbox" name="newControl[HasTurboTilt]" value="1"<?php if ( !empty($newControl['HasTurboTilt']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('TurboTiltSpeed') ?></th><td><input type="text" name="newControl[TurboTiltSpeed]" value="<?php echo $newControl['TurboTiltSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'zoom' :
    {
?>
            <tr><th scope="row"><?php echo translate('CanZoom') ?></th><td><input type="checkbox" name="newControl[CanZoom]" value="1"<?php if ( !empty($newControl['CanZoom']) ) { ?> checked="checked"<?php } ?>></td></tr>
            <tr><th scope="row"><?php echo translate('CanZoomAbs') ?></th><td><input type="checkbox" name="newControl[CanZoomAbs]" value="1"<?php if ( !empty($newControl['CanZoomAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanZoomRel') ?></th><td><input type="checkbox" name="newControl[CanZoomRel]" value="1"<?php if ( !empty($newControl['CanZoomRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanZoomCon') ?></th><td><input type="checkbox" name="newControl[CanZoomCon]" value="1"<?php if ( !empty($newControl['CanZoomCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinZoomRange') ?></th><td><input type="text" name="newControl[MinZoomRange]" value="<?php echo $newControl['MinZoomRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxZoomRange') ?></th><td><input type="text" name="newControl[MaxZoomRange]" value="<?php echo $newControl['MaxZoomRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MinZoomStep') ?></th><td><input type="text" name="newControl[MinZoomStep]" value="<?php echo $newControl['MinZoomStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxZoomStep') ?></th><td><input type="text" name="newControl[MaxZoomStep]" value="<?php echo $newControl['MaxZoomStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('HasZoomSpeed') ?></th><td><input type="checkbox" name="newControl[HasZoomSpeed]" value="1"<?php if ( !empty($newControl['HasZoomSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinZoomSpeed') ?></th><td><input type="text" name="newControl[MinZoomSpeed]" value="<?php echo $newControl['MinZoomSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxZoomSpeed') ?></th><td><input type="text" name="newControl[MaxZoomSpeed]" value="<?php echo $newControl['MaxZoomSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'focus' :
    {
?>
            <tr><th scope="row"><?php echo translate('CanFocus') ?></th><td><input type="checkbox" name="newControl[CanFocus]" value="1"<?php if ( !empty($newControl['CanFocus']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanAutoFocus') ?></th><td><input type="checkbox" name="newControl[CanAutoFocus]" value="1"<?php if ( !empty($newControl['CanAutoFocus']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanFocusAbs') ?></th><td><input type="checkbox" name="newControl[CanFocusAbs]" value="1"<?php if ( !empty($newControl['CanFocusAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanFocusRel') ?></th><td><input type="checkbox" name="newControl[CanFocusRel]" value="1"<?php if ( !empty($newControl['CanFocusRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanFocusCon') ?></th><td><input type="checkbox" name="newControl[CanFocusCon]" value="1"<?php if ( !empty($newControl['CanFocusCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinFocusRange') ?></th><td><input type="text" name="newControl[MinFocusRange]" value="<?php echo $newControl['MinFocusRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxFocusRange') ?></th><td><input type="text" name="newControl[MaxFocusRange]" value="<?php echo $newControl['MaxFocusRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MinFocusStep') ?></th><td><input type="text" name="newControl[MinFocusStep]" value="<?php echo $newControl['MinFocusStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxFocusStep') ?></th><td><input type="text" name="newControl[MaxFocusStep]" value="<?php echo $newControl['MaxFocusStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('HasFocusSpeed') ?></th><td><input type="checkbox" name="newControl[HasFocusSpeed]" value="1"<?php if ( !empty($newControl['HasFocusSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinFocusSpeed') ?></th><td><input type="text" name="newControl[MinFocusSpeed]" value="<?php echo $newControl['MinFocusSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxFocusSpeed') ?></th><td><input type="text" name="newControl[MaxFocusSpeed]" value="<?php echo $newControl['MaxFocusSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'iris' :
    {
?>
            <tr><th scope="row"><?php echo translate('CanIris') ?></th><td><input type="checkbox" name="newControl[CanIris]" value="1"<?php if ( !empty($newControl['CanIris']) ) { ?> checked="checked"<?php } ?>></td></tr>
            <tr><th scope="row"><?php echo translate('CanAutoIris') ?></th><td><input type="checkbox" name="newControl[CanAutoIris]" value="1"<?php if ( !empty($newControl['CanAutoIris']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanIrisAbs') ?></th><td><input type="checkbox" name="newControl[CanIrisAbs]" value="1"<?php if ( !empty($newControl['CanIrisAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanIrisRel') ?></th><td><input type="checkbox" name="newControl[CanIrisRel]" value="1"<?php if ( !empty($newControl['CanIrisRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanIrisCon') ?></th><td><input type="checkbox" name="newControl[CanIrisCon]" value="1"<?php if ( !empty($newControl['CanIrisCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinIrisRange') ?></th><td><input type="text" name="newControl[MinIrisRange]" value="<?php echo $newControl['MinIrisRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxIrisRange') ?></th><td><input type="text" name="newControl[MaxIrisRange]" value="<?php echo $newControl['MaxIrisRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MinIrisStep') ?></th><td><input type="text" name="newControl[MinIrisStep]" value="<?php echo $newControl['MinIrisStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxIrisStep') ?></th><td><input type="text" name="newControl[MaxIrisStep]" value="<?php echo $newControl['MaxIrisStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('HasIrisSpeed') ?></th><td><input type="checkbox" name="newControl[HasIrisSpeed]" value="1"<?php if ( !empty($newControl['HasIrisSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinIrisSpeed') ?></th><td><input type="text" name="newControl[MinIrisSpeed]" value="<?php echo $newControl['MinIrisSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxIrisSpeed') ?></th><td><input type="text" name="newControl[MaxIrisSpeed]" value="<?php echo $newControl['MaxIrisSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'gain' :
    {
?>
            <tr><th scope="row"><?php echo translate('CanGain') ?></th><td><input type="checkbox" name="newControl[CanGain]" value="1"<?php if ( !empty($newControl['CanGain']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanAutoGain') ?></th><td><input type="checkbox" name="newControl[CanAutoGain]" value="1"<?php if ( !empty($newControl['CanAutoGain']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanGainAbs') ?></th><td><input type="checkbox" name="newControl[CanGainAbs]" value="1"<?php if ( !empty($newControl['CanGainAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanGainRel') ?></th><td><input type="checkbox" name="newControl[CanGainRel]" value="1"<?php if ( !empty($newControl['CanGainRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanGainCon') ?></th><td><input type="checkbox" name="newControl[CanGainCon]" value="1"<?php if ( !empty($newControl['CanGainCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinGainRange') ?></th><td><input type="text" name="newControl[MinGainRange]" value="<?php echo $newControl['MinGainRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxGainRange') ?></th><td><input type="text" name="newControl[MaxGainRange]" value="<?php echo $newControl['MaxGainRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MinGainStep') ?></th><td><input type="text" name="newControl[MinGainStep]" value="<?php echo $newControl['MinGainStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxGainStep') ?></th><td><input type="text" name="newControl[MaxGainStep]" value="<?php echo $newControl['MaxGainStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('HasGainSpeed') ?></th><td><input type="checkbox" name="newControl[HasGainSpeed]" value="1"<?php if ( !empty($newControl['HasGainSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinGainSpeed') ?></th><td><input type="text" name="newControl[MinGainSpeed]" value="<?php echo $newControl['MinGainSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxGainSpeed') ?></th><td><input type="text" name="newControl[MaxGainSpeed]" value="<?php echo $newControl['MaxGainSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'white' :
    {
?>
            <tr><th scope="row"><?php echo translate('CanWhite') ?></th><td><input type="checkbox" name="newControl[CanWhite]" value="1"<?php if ( !empty($newControl['CanWhite']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanAutoWhite') ?></th><td><input type="checkbox" name="newControl[CanAutoWhite]" value="1"<?php if ( !empty($newControl['CanAutoWhite']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanWhiteAbs') ?></th><td><input type="checkbox" name="newControl[CanWhiteAbs]" value="1"<?php if ( !empty($newControl['CanWhiteAbs']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanWhiteRel') ?></th><td><input type="checkbox" name="newControl[CanWhiteRel]" value="1"<?php if ( !empty($newControl['CanWhiteRel']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanWhiteCon') ?></th><td><input type="checkbox" name="newControl[CanWhiteCon]" value="1"<?php if ( !empty($newControl['CanWhiteCon']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinWhiteRange') ?></th><td><input type="text" name="newControl[MinWhiteRange]" value="<?php echo $newControl['MinWhiteRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxWhiteRange') ?></th><td><input type="text" name="newControl[MaxWhiteRange]" value="<?php echo $newControl['MaxWhiteRange'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MinWhiteStep') ?></th><td><input type="text" name="newControl[MinWhiteStep]" value="<?php echo $newControl['MinWhiteStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxWhiteStep') ?></th><td><input type="text" name="newControl[MaxWhiteStep]" value="<?php echo $newControl['MaxWhiteStep'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('HasWhiteSpeed') ?></th><td><input type="checkbox" name="newControl[HasWhiteSpeed]" value="1"<?php if ( !empty($newControl['HasWhiteSpeed']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('MinWhiteSpeed') ?></th><td><input type="text" name="newControl[MinWhiteSpeed]" value="<?php echo $newControl['MinWhiteSpeed'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('MaxWhiteSpeed') ?></th><td><input type="text" name="newControl[MaxWhiteSpeed]" value="<?php echo $newControl['MaxWhiteSpeed'] ?>" size="8"/></td></tr>
<?php
        break;
    }
    case 'presets' :
    {
?>
            <tr><th scope="row"><?php echo translate('HasPresets') ?></th><td><input type="checkbox" name="newControl[HasPresets]" value="1"<?php if ( !empty($newControl['HasPresets']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('NumPresets') ?></th><td><input type="text" name="newControl[NumPresets]" value="<?php echo $newControl['NumPresets'] ?>" size="8"/></td></tr>
            <tr><th scope="row"><?php echo translate('HasHomePreset') ?></th><td><input type="checkbox" name="newControl[HasHomePreset]" value="1"<?php if ( !empty($newControl['HasHomePreset']) ) { ?> checked="checked"<?php } ?>/></td></tr>
            <tr><th scope="row"><?php echo translate('CanSetPresets') ?></th><td><input type="checkbox" name="newControl[CanSetPresets]" value="1"<?php if ( !empty($newControl['CanSetPresets']) ) { ?> checked="checked"<?php } ?>/></td></tr>
<?php
        break;
    }
}
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?php echo translate('Save') ?>"<?php if ( !canEdit( 'Control' ) ) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
