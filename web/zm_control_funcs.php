<?php
//
// ZoneMinder web control function library, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

function getControlCommands( $monitor )
{
	$cmds = array();

	$cmds['Wake'] = "wake";
	$cmds['Sleep'] = "sleep";
	$cmds['Reset'] = "reset";

	$cmds['PresetSet'] = "presetSet";
	$cmds['PresetGoto'] = "presetGoto";
	$cmds['PresetHome'] = "presetHome";

	if ( $monitor['CanZoomCon'] )
		$cmds['ZoomRoot'] = "zoomCon";
	elseif ( $monitor['CanZoomRel'] )
		$cmds['ZoomRoot'] = "zoomRel";
	elseif ( $monitor['CanZoomAbs'] )
		$cmds['ZoomRoot'] = "zoomAbs";
	$cmds['ZoomTele'] = $cmds['ZoomRoot']."Tele";
	$cmds['ZoomWide'] = $cmds['ZoomRoot']."Wide";
	$cmds['ZoomStop'] = "zoomStop";
	$cmds['ZoomAuto'] = "zoomAuto";
	$cmds['ZoomMan'] = "zoomMan";

	if ( $monitor['CanFocusCon'] )
		$cmds['FocusRoot'] = "focusCon";
	elseif ( $monitor['CanFocusRel'] )
		$cmds['FocusRoot'] = "focusRel";
	elseif ( $monitor['CanFocusAbs'] )
		$cmds['FocusRoot'] = "focusAbs";
	$cmds['FocusFar'] = $cmds['FocusRoot']."Far";
	$cmds['FocusNear'] = $cmds['FocusRoot']."Near";
	$cmds['FocusStop'] = "focusStop";
	$cmds['FocusAuto'] = "focusAuto";
	$cmds['FocusMan'] = "focusMan";

	if ( $monitor['CanIrisCon'] )
		$cmds['IrisRoot'] = "irisCon";
	elseif ( $monitor['CanIrisRel'] )
		$cmds['IrisRoot'] = "irisRel";
	elseif ( $monitor['CanIrisAbs'] )
		$cmds['IrisRoot'] = "irisAbs";
	$cmds['IrisOpen'] = $cmds['IrisRoot']."Open";
	$cmds['IrisClose'] = $cmds['IrisRoot']."Close";
	$cmds['IrisStop'] = "irisStop";
	$cmds['IrisAuto'] = "irisAuto";
	$cmds['IrisMan'] = "irisMan";

	if ( $monitor['CanWhiteCon'] )
		$cmds['WhiteRoot'] = "whiteCon";
	elseif ( $monitor['CanWhiteRel'] )
		$cmds['WhiteRoot'] = "whiteRel";
	elseif ( $monitor['CanWhiteAbs'] )
		$cmds['WhiteRoot'] = "whiteAbs";
	$cmds['WhiteIn'] = $cmds['WhiteRoot']."In";
	$cmds['WhiteOut'] = $cmds['WhiteRoot']."Out";
	$cmds['WhiteAuto'] = "whiteAuto";
	$cmds['WhiteMan'] = "whiteMan";

	if ( $monitor['CanGainCon'] )
		$cmds['GainRoot'] = "gainCon";
	elseif ( $monitor['CanGainRel'] )
		$cmds['GainRoot'] = "gainRel";
	elseif ( $monitor['CanGainAbs'] )
		$cmds['GainRoot'] = "gainAbs";
	$cmds['GainUp'] = $cmds['GainRoot']."Up";
	$cmds['GainDown'] = $cmds['GainRoot']."Down";
	$cmds['GainAuto'] = "gainAuto";
	$cmds['GainMan'] = "gainMan";

	if ( $monitor['CanMoveCon'] )
	{
		$cmds['MoveRoot'] = "moveCon";
		$cmds['Center'] = "moveStop";
	}
	elseif ( $monitor['CanMoveRel'] )
	{
		$cmds['MoveRoot'] = "moveRel";
		$cmds['Center'] = $cmds['PresetHome'];
	}
	elseif ( $monitor['CanMoveAbs'] )
	{
		$cmds['MoveRoot'] = "moveAbs";
		$cmds['Center'] = $cmds['PresetHome'];
	}

	$cmds['MoveUp'] = $cmds['MoveRoot']."Up";
	$cmds['MoveDown'] = $cmds['MoveRoot']."Down";
	$cmds['MoveLeft'] = $cmds['MoveRoot']."Left";
	$cmds['MoveRight'] = $cmds['MoveRoot']."Right";
	$cmds['MoveUpLeft'] = $cmds['MoveRoot']."UpLeft";
	$cmds['MoveUpRight'] = $cmds['MoveRoot']."UpRight";
	$cmds['MoveDownLeft'] = $cmds['MoveRoot']."DownLeft";
	$cmds['MoveDownRight'] = $cmds['MoveRoot']."DownRight";

	return( $cmds );
}

function controlFocus( $monitor )
{
	global $cmds, $zmSlangFocus, $zmSlangNear, $zmSlangFar, $zmSlangAuto, $zmSlangMan;

	ob_start();
?><table border="0" cellspacing="0" cellpadding="1">
<tr>
<td class="tinytext" align="center"><?= $zmSlangNear ?></td>
</tr>
<tr>
<td align="center"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['FocusNear'] ?>'; ctrl_form.submit();" src="graphics/arrow-l-u.gif" width="32" height="48" border="0"></td>
</tr>
<tr>
<td class="text" align="center"><?php if ( $monitor['CanFocusCon'] ) { ?><input type="button" class="flatbutton" value="<?= $zmSlangFocus ?>" onClick="ctrl_form.control.value='<?= $cmds['FocusStop'] ?>'; ctrl_form.submit();"><?php } else { ?><?= $zmSlangFocus ?><?php } ?></td>
</tr>
<tr>
<td align="center"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['FocusFar'] ?>'; ctrl_form.submit();" src="graphics/arrow-l-d.gif" width="32" height="48" border="0"></td>
</tr>
<tr>
<td class="tinytext" align="center"><?= $zmSlangFar ?></td>
</tr>
<?php
	if ( $monitor['CanAutoFocus'] )
	{
?>
<tr>
<td class="tinytext" align="center"><input type="button" class="smallbutton" value="<?= $zmSlangAuto ?>" onClick="ctrl_form.control.value='<?= $cmds['FocusAuto'] ?>'; ctrl_form.submit();"></td>
</tr>
<tr>
<td class="tinytext" align="center"><input type="button" class="smallbutton" value="<?= $zmSlangMan ?>" onClick="ctrl_form.control.value='<?= $cmds['FocusMan'] ?>'; ctrl_form.submit();"></td>
</tr>
<?php
	}
?>
</table><?php
	return( ob_get_clean() );
}

function controlZoom( $monitor )
{
	global $cmds, $zmSlangZoom, $zmSlangTele, $zmSlangWide, $zmSlangAuto, $zmSlangMan;

	ob_start();
?><table border="0" cellspacing="0" cellpadding="1">
<tr>
<td class="tinytext" align="center"><?= $zmSlangTele ?></td>
</tr>
<tr>
<td align="center"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['ZoomTele'] ?>'; ctrl_form.submit();" src="graphics/arrow-l-u.gif" width="32" height="48" border="0"></td>
</tr>
<tr>
<td class="text" align="center"><?php if ( $monitor['CanZoomCon'] ) { ?><input type="button" class="flatbutton" value="<?= $zmSlangZoom ?>" onClick="ctrl_form.control.value='<?= $cmds['ZoomStop'] ?>'; ctrl_form.submit();"><?php } else { ?><?= $zmSlangZoom ?><?php } ?></td>
</tr>
<tr>
<td align="center"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['ZoomWide'] ?>'; ctrl_form.submit();" src="graphics/arrow-l-d.gif" width="32" height="48" border="0"></td>
</tr>
<tr>
<td class="tinytext" align="center"><?= $zmSlangWide ?></td>
</tr>
<?php
	if ( $monitor['CanAutoZoom'] )
	{
?>
<tr>
<td class="tinytext" align="center"><input type="button" class="smallbutton" value="<?= $zmSlangAuto ?>" onClick="ctrl_form.control.value='<?= $cmds['ZoomAuto'] ?>'; ctrl_form.submit();"></td>
</tr>
<tr>
<td class="tinytext" align="center"><input type="button" class="smallbutton" value="<?= $zmSlangMan ?>" onClick="ctrl_form.control.value='<?= $cmds['ZoomMan'] ?>'; ctrl_form.submit();"></td>
</tr>
<?php
	}
?>
</table><?php
	return( ob_get_clean() );
}

function controlIris( $monitor )
{
	global $cmds, $zmSlangIris, $zmSlangOpen, $zmSlangClose, $zmSlangAuto, $zmSlangMan;

	ob_start();
?><table border="0" cellspacing="0" cellpadding="1">
<tr>
<td class="tinytext" align="center"><?= $zmSlangOpen ?></td>
</tr>
<tr>
<td align="center"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['IrisOpen'] ?>'; ctrl_form.submit();" src="graphics/arrow-l-u.gif" width="32" height="48" border="0"></td>
</tr>
<tr>
<td class="text" align="center"><?php if ( $monitor['CanIrisCon'] ) { ?><input type="button" class="flatbutton" value="<?= $zmSlangIris ?>" onClick="ctrl_form.control.value='<?= $cmds['IrisStop'] ?>'; ctrl_form.submit();"><?php } else { ?><?= $zmSlangIris ?><?php } ?></td>
</tr>
<tr>
<td align="center"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['IrisClose'] ?>'; ctrl_form.submit();" src="graphics/arrow-l-d.gif" width="32" height="48" border="0"></td>
</tr>
<tr>
<td class="tinytext" align="center"><?= $zmSlangClose ?></td>
</tr>
<?php
	if ( $monitor['CanAutoIris'] )
	{
?>
<tr>
<td class="tinytext" align="center"><input type="button" class="smallbutton" value="<?= $zmSlangAuto ?>" onClick="ctrl_form.control.value='<?= $cmds['IrisAuto'] ?>'; ctrl_form.submit();"></td>
</tr>
<tr>
<td class="tinytext" align="center"><input type="button" class="smallbutton" value="<?= $zmSlangMan ?>" onClick="ctrl_form.control.value='<?= $cmds['IrisMan'] ?>'; ctrl_form.submit();"></td>
</tr>
<?php
	}
?>
</table><?php
	return( ob_get_clean() );
}

function controlWhite( $monitor )
{
	global $cmds, $zmSlangWhite, $zmSlangIn, $zmSlangOut, $zmSlangAuto, $zmSlangMan;

	ob_start();
?><table border="0" cellspacing="0" cellpadding="1">
<tr>
<td class="tinytext" align="center"><?= $zmSlangIn ?></td>
</tr>
<tr>
<td align="center"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['WhiteIn'] ?>'; ctrl_form.submit();" src="graphics/arrow-l-u.gif" width="32" height="48" border="0"></td>
</tr>
<tr>
<td class="text" align="center"><?php if ( $monitor['CanWhiteCon'] ) { ?><input type="button" class="flatbutton" value="<?= $zmSlangWhite ?>" onClick="ctrl_form.control.value='<?= $cmds['WhiteStop'] ?>'; ctrl_form.submit();"><?php } else { ?><?= $zmSlangWhite ?><?php } ?></td>
</tr>
<tr>
<td align="center"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['WhiteOut'] ?>'; ctrl_form.submit();" src="graphics/arrow-l-d.gif" width="32" height="48" border="0"></td>
</tr>
<tr>
<td class="tinytext" align="center"><?= $zmSlangOut ?></td>
</tr>
<?php
	if ( $monitor['CanAutoWhite'] )
	{
?>
<tr>
<td class="tinytext" align="center"><input type="button" class="smallbutton" value="<?= $zmSlangAuto ?>" onClick="ctrl_form.control.value='<?= $cmds['WhiteAuto'] ?>'; ctrl_form.submit();"></td>
</tr>
<tr>
<td class="tinytext" align="center"><input type="button" class="smallbutton" value="<?= $zmSlangMan ?>" onClick="ctrl_form.control.value='<?= $cmds['WhiteMan'] ?>'; ctrl_form.submit();"></td>
</tr>
<?php
	}
?>
</table><?php
	return( ob_get_clean() );
}

function controlPanTilt( $monitor )
{
	global $cmds, $zmSlangPanTilt;

	ob_start();
?>
<table border="0" cellspacing="0" cellpadding="1">
<tr>
<td class="tinytext" align="center"><?= $zmSlangPanTilt ?></td>
</tr>
<tr>
<td valign="top" align="center"><table border="0" cellspacing="0" cellpadding="4">
<?php
	if ( $monitor['CanTilt'] )
	{
?>
<tr>
<?php
		if ( $monitor['CanPan'] )
		{
			if ( $monitor['CanMoveDiag'] )
			{
?>
<td align="right"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['MoveUpLeft'] ?>'; ctrl_form.submit();" src="graphics/arrow-ul.gif" width="32" height="32" border="0"></td>
<?php
			}
			else
			{
?>
<td align="center">&nbsp;</td>
<?php
			}
		}
?>
<td align="center"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['MoveUp'] ?>'; ctrl_form.submit();" src="graphics/arrow-u.gif" width="32" height="32" border="0"></td>
<?php
		if ( $monitor['CanPan'] )
		{
			if ( $monitor['CanMoveDiag'] )
			{
?>
<td align="left"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['MoveUpRight'] ?>'; ctrl_form.submit();" src="graphics/arrow-ur.gif" width="32" height="32" border="0"></td>
<?php
			}
			else
			{
?>
<td align="center">&nbsp;</td>
<?php
			}
		}
?>
</tr>
<?php
}
?>
<tr>
<?php
	if ( $monitor['CanPan'] )
	{
?>
<td align="right"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['MoveLeft'] ?>'; ctrl_form.submit();" src="graphics/arrow-l.gif" width="32" height="32" border="0"></td>
<?php
	}
?>
<td align="center"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['Center'] ?>'; ctrl_form.submit();" src="graphics/center.gif" width="32" height="32" border="0"></td>
<?php
	if ( $monitor['CanPan'] )
	{
?>
<td align="left"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['MoveRight'] ?>'; ctrl_form.submit();" src="graphics/arrow-r.gif" width="32" height="32" border="0"></td>
<?php
	}
?>
</tr>
<?php
	if ( $monitor['CanTilt'] )
	{
?>
<tr>
<?php
		if ( $monitor['CanPan'] )
		{
			if ( $monitor['CanMoveDiag'] )
			{
?>
<td align="right"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['MoveDownLeft'] ?>'; ctrl_form.submit();" src="graphics/arrow-dl.gif" width="32" height="32" border="0"></td>
<?php
			}
			else
			{
?>
<td align="center">&nbsp;</td>
<?php
			}
		}
?>
<td align="center"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['MoveDown'] ?>'; ctrl_form.submit();" src="graphics/arrow-d.gif" width="32" height="32" border="0"></td>
<?php
		if ( $monitor['CanPan'] )
		{
			if ( $monitor['CanMoveDiag'] )
			{
?>
<td align="left"><input type="image" onClick="ctrl_form.control.value='<?= $cmds['MoveDownRight'] ?>'; ctrl_form.submit();" src="graphics/arrow-dr.gif" width="32" height="32" border="0"></td>
<?php
			}
			else
			{
?>
<td align="center">&nbsp;</td>
<?php
			}
		}
?>
</tr>
<?php
	}
?>
</table></td>
</tr>
</table><?php
	return( ob_get_clean() );
}

function controlPresets( $monitor )
{
	global $cmds, $jws, $zmSlangPresets, $zmSlangHome, $zmSlangSet;

	define( "MAX_PRESETS", "12" );

	ob_start();
?>
<script type="text/javascript">
function controlPreset( command )
{
	ctrl_form.control.value=command;
	ctrl_form.submit();
}
</script>
<table border="0" cellspacing="0" cellpadding="2">
<tr>
<td class="text" align="center"><?= $zmSlangPresets ?></td>
</tr>
<tr>
<td valign="top" align="center"><table border="0" cellspacing="3" cellpadding="0">
<tr>
<td valign="top" align="center">&nbsp;</td>
<?php
	if ( $monitor['HasHomePreset'] )
	{
?>
<td class="text" align="left"><input type="button" class="smallbutton" value="<?= $zmSlangHome ?>" onClick="controlPreset( '<?= $cmds['PresetHome'] ?>' );"></td>
<?php
	}
?>
<td valign="top" align="center"><table border="0" cellspacing="0" cellpadding="2">
<tr>
<td align="center">
<?php
    $sql = "select * from ControlPresets where MonitorId = '".$monitor['Id']."'";
    $labels = array();
    foreach( dbFetchAll( $sql ) as $row )
    {
        $labels[$row['Preset']] = $row['Label'];
    }

	$preset_break = (int)(($monitor['NumPresets']+1)/((int)(($monitor['NumPresets']-1)/MAX_PRESETS)+1));
	for ( $i = 1; $i <= $monitor['NumPresets']; $i++ )
	{
?>
<input type="button" class="numbutton" title="<?= $labels[$i]?$labels[$i]:"" ?>" value="<?= $i ?>" onClick="controlPreset( '<?= $cmds['PresetGoto'] ?><?=$i?>' );"><?php (($i%$preset_break)==0)?"<br>":"&nbsp;&nbsp;" ?>
<?php
		if ( $i && (($i%$preset_break) == 0) )
		{
?>
<br>
<?php
		}
	}
?>
</tr>
</table></td>
<?php
	if ( canEdit( 'Monitors' ) && $monitor['CanSetPresets'] )
	{
?>
<td class="text" align="left"><input type="button" class="smallbutton" value="<?= $zmSlangSet ?>" onClick="newWindow( '<?= $PHP_SELF ?>?view=controlpreset&mid=<?= $monitor['Id'] ?>', 'zmPreset', <?= $jws['preset']['w'] ?>, <?= $jws['preset']['h'] ?> );"></td>
<?php
	}
?>
<td valign="top" align="center">&nbsp;</td>
</tr>
</table></td>
</tr>
</table><?php
	return( ob_get_clean() );
}

function controlPower( $monitor )
{
	global $cmds, $zmSlangControl, $zmSlangWake, $zmSlangSleep, $zmSlangReset;

	ob_start();
?>
<table border="0" cellspacing="0" cellpadding="1">
<tr>
<td class="tinytext" align="center"><?= $zmSlangControl ?></td>
</tr>
<tr>
<td valign="top" align="center"><table border="0" cellspacing="0" cellpadding="2">
<tr>
<?php
	if ( $monitor['CanWake'] )
	{
?>
<td class="text" align="center"><input type="button" class="smallbutton" value="<?= $zmSlangWake ?>" onClick="ctrl_form.control.value='<?= $cmds['Wake'] ?>'; ctrl_form.submit();"></td>
<?php
	}
?>
<?php
	if ( $monitor['CanSleep'] )
	{
?>
<td class="text" align="center"><input type="button" class="smallbutton" value="<?= $zmSlangSleep ?>" onClick="ctrl_form.control.value='<?= $cmds['Sleep'] ?>'; ctrl_form.submit();"></td>
<?php
	}
?>
<?php
	if ( $monitor['CanReset'] )
	{
?>
<td class="text" align="center"><input type="button" class="smallbutton" value="<?= $zmSlangReset ?>" onClick="ctrl_form.control.value='<?= $cmds['Reset'] ?>'; ctrl_form.submit();"></td>
<?php
	}
?>
</tr>
</table></td>
</tr>
</table><?php
	return( ob_get_clean() );
}

