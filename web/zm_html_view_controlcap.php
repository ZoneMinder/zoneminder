<?php
//
// ZoneMinder web control capabilities view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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
$tabs["main"] = $zmSlangMain;
$tabs["move"] = $zmSlangMove;
$tabs["pan"] = $zmSlangPan;
$tabs["tilt"] = $zmSlangTilt;
$tabs["zoom"] = $zmSlangZoom;
$tabs["focus"] = $zmSlangFocus;
$tabs["white"] = $zmSlangWhite;
$tabs["iris"] = $zmSlangIris;
$tabs["presets"] = $zmSlangPresets;

if ( !isset($tab) )
	$tab = "main";

if ( !empty($cid) )
{
	$result = mysql_query( "select * from Controls where Id = '$cid'" );
	if ( !$result )
		die( mysql_error() );
	$control = mysql_fetch_assoc( $result );
	mysql_free_result( $result );
}
else
{
	$control = array();
	$control['Name'] = $zmSlangNew;
	$control['Type'] = "Local";
	$control['Command'] = "";
}
if ( !isset( $new_control ) )
{
	$new_control = $control;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangControl ?> - <?= $control['Name'] ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
<?php
if ( !empty($refresh_parent) )
{
?>
opener.location.reload(true);
<?php
}
?>
window.focus();
function validateForm(Form)
{
	var errors = new Array();

	if ( Form.elements['new_control[Name]'].value.search( /[^\w-]/ ) >= 0 )
	{
		errors[errors.length] = "<?= $zmSlangBadNameChars ?>";
	}
	if ( errors.length )
	{
		alert( errors.join( "\n" ) );
		return( false );
	}
	return( true );
}

function submitTab(Form,Tab)
{
	Form.action.value = "";
	Form.tab.value = Tab;
	Form.submit();
}

function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td align="left" class="head"><?= $zmSlangControlCap ?> - <?= $control['Name'] ?></td>
</tr>
<tr>
<td>&nbsp;</td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<?php
foreach ( $tabs as $name=>$value )
{
	if ( $tab == $name )
	{
?>
<td width="10" class="activetab"><?= $value ?></td>
<?php
	}
	else
	{
?>
<td width="10" class="passivetab"><a href="javascript: submitTab( document.control_form, '<?= $name ?>' );"><?= $value ?></a></td>
<?php
	}
}
?>
<td class="nontab">&nbsp;</td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="2" width="100%">
<form name="control_form" method="post" action="<?= $PHP_SELF ?>" onsubmit="return validateForm( document.control_form )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="tab" value="<?= $tab ?>">
<input type="hidden" name="action" value="controlcap">
<input type="hidden" name="cid" value="<?= $cid ?>">
<?php
if ( $tab != 'main' )
{
?>
<input type="hidden" name="new_control[Name]" value="<?= $new_control['Name'] ?>">
<input type="hidden" name="new_control[Type]" value="<?= $new_control['Type'] ?>">
<input type="hidden" name="new_control[Command]" value="<?= $new_control['Command'] ?>">
<input type="hidden" name="new_control[CanWake]" value="<?= $new_control['CanWake'] ?>">
<input type="hidden" name="new_control[CanSleep]" value="<?= $new_control['CanSleep'] ?>">
<input type="hidden" name="new_control[CanReset]" value="<?= $new_control['CanReset'] ?>">
<?php
}
if ( $tab != 'move' )
{
?>
<input type="hidden" name="new_control[CanMove]" value="<?= $new_control['CanMove'] ?>">
<input type="hidden" name="new_control[CanMoveDiag]" value="<?= $new_control['CanMoveDiag'] ?>">
<input type="hidden" name="new_control[CanMoveMap]" value="<?= $new_control['CanMoveMap'] ?>">
<input type="hidden" name="new_control[CanMoveAbs]" value="<?= $new_control['CanMoveAbs'] ?>">
<input type="hidden" name="new_control[CanMoveRel]" value="<?= $new_control['CanMoveRel'] ?>">
<input type="hidden" name="new_control[CanMoveCon]" value="<?= $new_control['CanMoveCon'] ?>">
<?php
}
if ( $tab != 'pan' )
{
?>
<input type="hidden" name="new_control[CanPan]" value="<?= $new_control['CanPan'] ?>">
<input type="hidden" name="new_control[MinPanRange]" value="<?= $new_control['MinPanRange'] ?>">
<input type="hidden" name="new_control[MaxPanRange]" value="<?= $new_control['MaxPanRange'] ?>">
<input type="hidden" name="new_control[MinPanStep]" value="<?= $new_control['MinPanStep'] ?>">
<input type="hidden" name="new_control[MaxPanStep]" value="<?= $new_control['MaxPanStep'] ?>">
<input type="hidden" name="new_control[HasPanSpeed]" value="<?= $new_control['HasPanSpeed'] ?>">
<input type="hidden" name="new_control[MinPanSpeed]" value="<?= $new_control['MinPanSpeed'] ?>">
<input type="hidden" name="new_control[MaxPanSpeed]" value="<?= $new_control['MaxPanSpeed'] ?>">
<input type="hidden" name="new_control[HasTurboPan]" value="<?= $new_control['HasTurboPan'] ?>">
<input type="hidden" name="new_control[TurboPanSpeed]" value="<?= $new_control['TurboPanSpeed'] ?>">
<?php
}
if ( $tab != 'tilt' )
{
?>
<input type="hidden" name="new_control[CanTilt]" value="<?= $new_control['CanTilt'] ?>">
<input type="hidden" name="new_control[MinTiltRange]" value="<?= $new_control['MinTiltRange'] ?>">
<input type="hidden" name="new_control[MaxTiltRange]" value="<?= $new_control['MaxTiltRange'] ?>">
<input type="hidden" name="new_control[MinTiltStep]" value="<?= $new_control['MinTiltStep'] ?>">
<input type="hidden" name="new_control[MaxTiltStep]" value="<?= $new_control['MaxTiltStep'] ?>">
<input type="hidden" name="new_control[HasTiltSpeed]" value="<?= $new_control['HasTiltSpeed'] ?>">
<input type="hidden" name="new_control[MinTiltSpeed]" value="<?= $new_control['MinTiltSpeed'] ?>">
<input type="hidden" name="new_control[MaxTiltSpeed]" value="<?= $new_control['MaxTiltSpeed'] ?>">
<input type="hidden" name="new_control[HasTurboTilt]" value="<?= $new_control['HasTurboTilt'] ?>">
<input type="hidden" name="new_control[TurboTiltSpeed]" value="<?= $new_control['TurboTiltSpeed'] ?>">
<?php
}
if ( $tab != 'zoom' )
{
?>
<input type="hidden" name="new_control[CanZoom]" value="<?= $new_control['CanZoom'] ?>">
<!--<input type="hidden" name="new_control[CanAutoZoom]" value="<?= $new_control['CanAutoZoom'] ?>">-->
<input type="hidden" name="new_control[CanZoomAbs]" value="<?= $new_control['CanZoomAbs'] ?>">
<input type="hidden" name="new_control[CanZoomRel]" value="<?= $new_control['CanZoomRel'] ?>">
<input type="hidden" name="new_control[CanZoomCon]" value="<?= $new_control['CanZoomCon'] ?>">
<input type="hidden" name="new_control[MinZoomRange]" value="<?= $new_control['MinZoomRange'] ?>">
<input type="hidden" name="new_control[MaxZoomRange]" value="<?= $new_control['MaxZoomRange'] ?>">
<input type="hidden" name="new_control[MinZoomStep]" value="<?= $new_control['MinZoomStep'] ?>">
<input type="hidden" name="new_control[MaxZoomStep]" value="<?= $new_control['MaxZoomStep'] ?>">
<input type="hidden" name="new_control[HasZoomSpeed]" value="<?= $new_control['HasZoomSpeed'] ?>">
<input type="hidden" name="new_control[MinZoomSpeed]" value="<?= $new_control['MinZoomSpeed'] ?>">
<input type="hidden" name="new_control[MaxZoomSpeed]" value="<?= $new_control['MaxZoomSpeed'] ?>">
<?php
}
if ( $tab != 'focus' )
{
?>
<input type="hidden" name="new_control[CanFocus]" value="<?= $new_control['CanFocus'] ?>">
<input type="hidden" name="new_control[CanAutoFocus]" value="<?= $new_control['CanAutoFocus'] ?>">
<input type="hidden" name="new_control[CanFocusAbs]" value="<?= $new_control['CanFocusAbs'] ?>">
<input type="hidden" name="new_control[CanFocusRel]" value="<?= $new_control['CanFocusRel'] ?>">
<input type="hidden" name="new_control[CanFocusCon]" value="<?= $new_control['CanFocusCon'] ?>">
<input type="hidden" name="new_control[MinFocusRange]" value="<?= $new_control['MinFocusRange'] ?>">
<input type="hidden" name="new_control[MaxFocusRange]" value="<?= $new_control['MaxFocusRange'] ?>">
<input type="hidden" name="new_control[MinFocusStep]" value="<?= $new_control['MinFocusStep'] ?>">
<input type="hidden" name="new_control[MaxFocusStep]" value="<?= $new_control['MaxFocusStep'] ?>">
<input type="hidden" name="new_control[HasFocusSpeed]" value="<?= $new_control['HasFocusSpeed'] ?>">
<input type="hidden" name="new_control[MinFocusSpeed]" value="<?= $new_control['MinFocusSpeed'] ?>">
<input type="hidden" name="new_control[MaxFocusSpeed]" value="<?= $new_control['MaxFocusSpeed'] ?>">
<?php
}
if ( $tab != 'iris' )
{
?>
<input type="hidden" name="new_control[CanIris]" value="<?= $new_control['CanIris'] ?>">
<input type="hidden" name="new_control[CanAutoIris]" value="<?= $new_control['CanAutoIris'] ?>">
<input type="hidden" name="new_control[CanIrisAbs]" value="<?= $new_control['CanIrisAbs'] ?>">
<input type="hidden" name="new_control[CanIrisRel]" value="<?= $new_control['CanIrisRel'] ?>">
<input type="hidden" name="new_control[CanIrisCon]" value="<?= $new_control['CanIrisCon'] ?>">
<input type="hidden" name="new_control[MinIrisRange]" value="<?= $new_control['MinIrisRange'] ?>">
<input type="hidden" name="new_control[MaxIrisRange]" value="<?= $new_control['MaxIrisRange'] ?>">
<input type="hidden" name="new_control[MinIrisStep]" value="<?= $new_control['MinIrisStep'] ?>">
<input type="hidden" name="new_control[MaxIrisStep]" value="<?= $new_control['MaxIrisStep'] ?>">
<input type="hidden" name="new_control[HasIrisSpeed]" value="<?= $new_control['HasIrisSpeed'] ?>">
<input type="hidden" name="new_control[MinIrisSpeed]" value="<?= $new_control['MinIrisSpeed'] ?>">
<input type="hidden" name="new_control[MaxIrisSpeed]" value="<?= $new_control['MaxIrisSpeed'] ?>">
<?php
}
if ( $tab != 'gain' )
{
?>
<input type="hidden" name="new_control[CanGain]" value="<?= $new_control['CanGain'] ?>">
<input type="hidden" name="new_control[CanAutoGain]" value="<?= $new_control['CanAutoGain'] ?>">
<input type="hidden" name="new_control[CanGainAbs]" value="<?= $new_control['CanGainAbs'] ?>">
<input type="hidden" name="new_control[CanGainRel]" value="<?= $new_control['CanGainRel'] ?>">
<input type="hidden" name="new_control[CanGainCon]" value="<?= $new_control['CanGainCon'] ?>">
<input type="hidden" name="new_control[MinGainRange]" value="<?= $new_control['MinGainRange'] ?>">
<input type="hidden" name="new_control[MaxGainRange]" value="<?= $new_control['MaxGainRange'] ?>">
<input type="hidden" name="new_control[MinGainStep]" value="<?= $new_control['MinGainStep'] ?>">
<input type="hidden" name="new_control[MaxGainStep]" value="<?= $new_control['MaxGainStep'] ?>">
<input type="hidden" name="new_control[HasGainSpeed]" value="<?= $new_control['HasGainSpeed'] ?>">
<input type="hidden" name="new_control[MinGainSpeed]" value="<?= $new_control['MinGainSpeed'] ?>">
<input type="hidden" name="new_control[MaxGainSpeed]" value="<?= $new_control['MaxGainSpeed'] ?>">
<?php
}
if ( $tab != 'white' )
{
?>
<input type="hidden" name="new_control[CanWhite]" value="<?= $new_control['CanWhite'] ?>">
<input type="hidden" name="new_control[CanAutoWhite]" value="<?= $new_control['CanAutoWhite'] ?>">
<input type="hidden" name="new_control[CanWhiteAbs]" value="<?= $new_control['CanWhiteAbs'] ?>">
<input type="hidden" name="new_control[CanWhiteRel]" value="<?= $new_control['CanWhiteRel'] ?>">
<input type="hidden" name="new_control[CanWhiteCon]" value="<?= $new_control['CanWhiteCon'] ?>">
<input type="hidden" name="new_control[MinWhiteRange]" value="<?= $new_control['MinWhiteRange'] ?>">
<input type="hidden" name="new_control[MaxWhiteRange]" value="<?= $new_control['MaxWhiteRange'] ?>">
<input type="hidden" name="new_control[MinWhiteStep]" value="<?= $new_control['MinWhiteStep'] ?>">
<input type="hidden" name="new_control[MaxWhiteStep]" value="<?= $new_control['MaxWhiteStep'] ?>">
<input type="hidden" name="new_control[HasWhiteSpeed]" value="<?= $new_control['HasWhiteSpeed'] ?>">
<input type="hidden" name="new_control[MinWhiteSpeed]" value="<?= $new_control['MinWhiteSpeed'] ?>">
<input type="hidden" name="new_control[MaxWhiteSpeed]" value="<?= $new_control['MaxWhiteSpeed'] ?>">
<?php
}
if ( $tab != 'presets' )
{
?>
<input type="hidden" name="new_control[HasPresets]" value="<?= $new_control['HasPresets'] ?>">
<input type="hidden" name="new_control[NumPresets]" value="<?= $new_control['NumPresets'] ?>">
<input type="hidden" name="new_control[HasHomePreset]" value="<?= $new_control['HasHomePreset'] ?>">
<input type="hidden" name="new_control[CanSetPresets]" value="<?= $new_control['CanSetPresets'] ?>">
<?php
}
?>
<tr>
<td align="left" class="smallhead" width="50%"><?= $zmSlangParameter ?></td><td align="left" class="smallhead" width="50%"><?= $zmSlangValue ?></td>
</tr>
<?php
switch ( $tab )
{
	case 'main' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangName ?></td><td align="left" class="text"><input type="text" name="new_control[Name]" value="<?= $new_control['Name'] ?>" size="16" class="form"></td></tr>
<?php
		$types = array( 'Local'=>$zmSlangLocal, 'Remote'=>$zmSlangRemote );
?>
<tr><td align="left" class="text"><?= $zmSlangType ?></td><td><?= buildSelect( "new_control[Type]", $types ); ?></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCommand ?></td><td align="left" class="text"><input type="text" name="new_control[Command]" value="<?= $new_control['Command'] ?>" size="40" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanWake ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanWake]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanWake']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanSleep ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanSleep]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanSleep']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanReset ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanReset]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanReset']) ) { ?> checked<?php } ?>></td></tr>
<?php
		break;
	}
	case 'move' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangCanMove ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanMove]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanMove']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanMoveDiag ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanMoveDiag]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanMoveDiag']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanMoveMap ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanMoveMap]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanMoveMap']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanMoveAbs ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanMoveAbs]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanMoveAbs']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanMoveRel ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanMoveRel]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanMoveRel']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanMoveCon ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanMoveCon]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanMoveCon']) ) { ?> checked<?php } ?>></td></tr>
<?php
		break;
	}
	case 'pan' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangCanPan ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanPan]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanPan']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinPanRange ?></td><td align="left" class="text"><input type="text" name="new_control[MinPanRange]" value="<?= $new_control['MinPanRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxPanRange ?></td><td align="left" class="text"><input type="text" name="new_control[MaxPanRange]" value="<?= $new_control['MaxPanRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinPanStep ?></td><td align="left" class="text"><input type="text" name="new_control[MinPanStep]" value="<?= $new_control['MinPanStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxPanStep ?></td><td align="left" class="text"><input type="text" name="new_control[MaxPanStep]" value="<?= $new_control['MaxPanStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangHasPanSpeed ?></td><td align="left" class="text"><input type="checkbox" name="new_control[HasPanSpeed]" value="1" class="form-noborder"<?php if ( !empty($new_control['HasPanSpeed']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinPanSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MinPanSpeed]" value="<?= $new_control['MinPanSpeed'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxPanSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MaxPanSpeed]" value="<?= $new_control['MaxPanSpeed'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangHasTurboPan ?></td><td align="left" class="text"><input type="checkbox" name="new_control[HasTurboPan]" value="1" class="form-noborder"<?php if ( !empty($new_control['HasTurboPan']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangTurboPanSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[TurboPanSpeed]" value="<?= $new_control['TurboPanSpeed'] ?>" size="8" class="form"></td></tr>
<?php
		break;
	}
	case 'tilt' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangCanTilt ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanTilt]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanTilt']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinTiltRange ?></td><td align="left" class="text"><input type="text" name="new_control[MinTiltRange]" value="<?= $new_control['MinTiltRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxTiltRange ?></td><td align="left" class="text"><input type="text" name="new_control[MaxTiltRange]" value="<?= $new_control['MaxTiltRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinTiltStep ?></td><td align="left" class="text"><input type="text" name="new_control[MinTiltStep]" value="<?= $new_control['MinTiltStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxTiltStep ?></td><td align="left" class="text"><input type="text" name="new_control[MaxTiltStep]" value="<?= $new_control['MaxTiltStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangHasTiltSpeed ?></td><td align="left" class="text"><input type="checkbox" name="new_control[HasTiltSpeed]" value="1" class="form-noborder"<?php if ( !empty($new_control['HasTiltSpeed']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinTiltSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MinTiltSpeed]" value="<?= $new_control['MinTiltSpeed'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxTiltSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MaxTiltSpeed]" value="<?= $new_control['MaxTiltSpeed'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangHasTurboTilt ?></td><td align="left" class="text"><input type="checkbox" name="new_control[HasTurboTilt]" value="1" class="form-noborder"<?php if ( !empty($new_control['HasTurboTilt']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangTurboTiltSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[TurboTiltSpeed]" value="<?= $new_control['TurboTiltSpeed'] ?>" size="8" class="form"></td></tr>
<?php
		break;
	}
	case 'zoom' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangCanZoom ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanZoom]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanZoom']) ) { ?> checked<?php } ?>></td></tr>
<!--<tr><td align="left" class="text"><?= $zmSlangCanAutoZoom ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanAutoZoom]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanAutoZoom']) ) { ?> checked<?php } ?>></td></tr>-->
<tr><td align="left" class="text"><?= $zmSlangCanZoomAbs ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanZoomAbs]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanZoomAbs']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanZoomRel ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanZoomRel]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanZoomRel']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanZoomCon ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanZoomCon]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanZoomCon']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinZoomRange ?></td><td align="left" class="text"><input type="text" name="new_control[MinZoomRange]" value="<?= $new_control['MinZoomRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxZoomRange ?></td><td align="left" class="text"><input type="text" name="new_control[MaxZoomRange]" value="<?= $new_control['MaxZoomRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinZoomStep ?></td><td align="left" class="text"><input type="text" name="new_control[MinZoomStep]" value="<?= $new_control['MinZoomStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxZoomStep ?></td><td align="left" class="text"><input type="text" name="new_control[MaxZoomStep]" value="<?= $new_control['MaxZoomStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangHasZoomSpeed ?></td><td align="left" class="text"><input type="checkbox" name="new_control[HasZoomSpeed]" value="1" class="form-noborder"<?php if ( !empty($new_control['HasZoomSpeed']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinZoomSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MinZoomSpeed]" value="<?= $new_control['MinZoomSpeed'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxZoomSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MaxZoomSpeed]" value="<?= $new_control['MaxZoomSpeed'] ?>" size="8" class="form"></td></tr>
<?php
		break;
	}
	case 'focus' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangCanFocus ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanFocus]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanFocus']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanAutoFocus ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanAutoFocus]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanAutoFocus']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanFocusAbs ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanFocusAbs]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanFocusAbs']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanFocusRel ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanFocusRel]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanFocusRel']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanFocusCon ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanFocusCon]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanFocusCon']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinFocusRange ?></td><td align="left" class="text"><input type="text" name="new_control[MinFocusRange]" value="<?= $new_control['MinFocusRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxFocusRange ?></td><td align="left" class="text"><input type="text" name="new_control[MaxFocusRange]" value="<?= $new_control['MaxFocusRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinFocusStep ?></td><td align="left" class="text"><input type="text" name="new_control[MinFocusStep]" value="<?= $new_control['MinFocusStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxFocusStep ?></td><td align="left" class="text"><input type="text" name="new_control[MaxFocusStep]" value="<?= $new_control['MaxFocusStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangHasFocusSpeed ?></td><td align="left" class="text"><input type="checkbox" name="new_control[HasFocusSpeed]" value="1" class="form-noborder"<?php if ( !empty($new_control['HasFocusSpeed']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinFocusSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MinFocusSpeed]" value="<?= $new_control['MinFocusSpeed'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxFocusSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MaxFocusSpeed]" value="<?= $new_control['MaxFocusSpeed'] ?>" size="8" class="form"></td></tr>
<?php
		break;
	}
	case 'iris' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangCanIris ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanIris]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanIris']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanAutoIris ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanAutoIris]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanAutoIris']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanIrisAbs ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanIrisAbs]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanIrisAbs']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanIrisRel ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanIrisRel]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanIrisRel']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanIrisCon ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanIrisCon]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanIrisCon']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinIrisRange ?></td><td align="left" class="text"><input type="text" name="new_control[MinIrisRange]" value="<?= $new_control['MinIrisRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxIrisRange ?></td><td align="left" class="text"><input type="text" name="new_control[MaxIrisRange]" value="<?= $new_control['MaxIrisRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinIrisStep ?></td><td align="left" class="text"><input type="text" name="new_control[MinIrisStep]" value="<?= $new_control['MinIrisStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxIrisStep ?></td><td align="left" class="text"><input type="text" name="new_control[MaxIrisStep]" value="<?= $new_control['MaxIrisStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangHasIrisSpeed ?></td><td align="left" class="text"><input type="checkbox" name="new_control[HasIrisSpeed]" value="1" class="form-noborder"<?php if ( !empty($new_control['HasIrisSpeed']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinIrisSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MinIrisSpeed]" value="<?= $new_control['MinIrisSpeed'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxIrisSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MaxIrisSpeed]" value="<?= $new_control['MaxIrisSpeed'] ?>" size="8" class="form"></td></tr>
<?php
		break;
	}
	case 'gain' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangCanGain ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanGain]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanGain']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanAutoGain ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanAutoGain]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanAutoGain']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanGainAbs ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanGainAbs]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanGainAbs']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanGainRel ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanGainRel]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanGainRel']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanGainCon ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanGainCon]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanGainCon']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinGainRange ?></td><td align="left" class="text"><input type="text" name="new_control[MinGainRange]" value="<?= $new_control['MinGainRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxGainRange ?></td><td align="left" class="text"><input type="text" name="new_control[MaxGainRange]" value="<?= $new_control['MaxGainRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinGainStep ?></td><td align="left" class="text"><input type="text" name="new_control[MinGainStep]" value="<?= $new_control['MinGainStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxGainStep ?></td><td align="left" class="text"><input type="text" name="new_control[MaxGainStep]" value="<?= $new_control['MaxGainStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangHasGainSpeed ?></td><td align="left" class="text"><input type="checkbox" name="new_control[HasGainSpeed]" value="1" class="form-noborder"<?php if ( !empty($new_control['HasGainSpeed']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinGainSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MinGainSpeed]" value="<?= $new_control['MinGainSpeed'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxGainSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MaxGainSpeed]" value="<?= $new_control['MaxGainSpeed'] ?>" size="8" class="form"></td></tr>
<?php
		break;
	}
	case 'white' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangCanWhite ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanWhite]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanWhite']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanAutoWhite ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanAutoWhite]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanAutoWhite']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanWhiteAbs ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanWhiteAbs]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanWhiteAbs']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanWhiteRel ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanWhiteRel]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanWhiteRel']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanWhiteCon ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanWhiteCon]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanWhiteCon']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinWhiteRange ?></td><td align="left" class="text"><input type="text" name="new_control[MinWhiteRange]" value="<?= $new_control['MinWhiteRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxWhiteRange ?></td><td align="left" class="text"><input type="text" name="new_control[MaxWhiteRange]" value="<?= $new_control['MaxWhiteRange'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinWhiteStep ?></td><td align="left" class="text"><input type="text" name="new_control[MinWhiteStep]" value="<?= $new_control['MinWhiteStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxWhiteStep ?></td><td align="left" class="text"><input type="text" name="new_control[MaxWhiteStep]" value="<?= $new_control['MaxWhiteStep'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangHasWhiteSpeed ?></td><td align="left" class="text"><input type="checkbox" name="new_control[HasWhiteSpeed]" value="1" class="form-noborder"<?php if ( !empty($new_control['HasWhiteSpeed']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMinWhiteSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MinWhiteSpeed]" value="<?= $new_control['MinWhiteSpeed'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangMaxWhiteSpeed ?></td><td align="left" class="text"><input type="text" name="new_control[MaxWhiteSpeed]" value="<?= $new_control['MaxWhiteSpeed'] ?>" size="8" class="form"></td></tr>
<?php
		break;
	}
	case 'presets' :
	{
?>
<tr><td align="left" class="text"><?= $zmSlangHasPresets ?></td><td align="left" class="text"><input type="checkbox" name="new_control[HasPresets]" value="1" class="form-noborder"<?php if ( !empty($new_control['HasPresets']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangNumPresets ?></td><td align="left" class="text"><input type="text" name="new_control[NumPresets]" value="<?= $new_control['NumPresets'] ?>" size="8" class="form"></td></tr>
<tr><td align="left" class="text"><?= $zmSlangHasHomePreset ?></td><td align="left" class="text"><input type="checkbox" name="new_control[HasHomePreset]" value="1" class="form-noborder"<?php if ( !empty($new_control['HasHomePreset']) ) { ?> checked<?php } ?>></td></tr>
<tr><td align="left" class="text"><?= $zmSlangCanSetPresets ?></td><td align="left" class="text"><input type="checkbox" name="new_control[CanSetPresets]" value="1" class="form-noborder"<?php if ( !empty($new_control['CanSetPresets']) ) { ?> checked<?php } ?>></td></tr>
<?php
		break;
	}
}
?>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td colspan="2" align="right"><input type="submit" value="<?= $zmSlangSave ?>" class="form"<?php if ( !canEdit( 'Control' ) ) { ?> disabled<?php } ?>>&nbsp;&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td>
</tr>
</form>
</table>
</body>
</html>
