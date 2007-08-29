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

	$cmds['PresetSet'] = "preset_set";
	$cmds['PresetGoto'] = "preset_goto_";
	$cmds['PresetHome'] = "preset_home";

	if ( $monitor['CanZoomCon'] )
		$cmds['ZoomRoot'] = "zoom_con_";
	elseif ( $monitor['CanZoomRel'] )
		$cmds['ZoomRoot'] = "zoom_rel_";
	elseif ( $monitor['CanZoomAbs'] )
		$cmds['ZoomRoot'] = "zoom_abs_";
	$cmds['ZoomTele'] = $cmds['ZoomRoot']."tele";
	$cmds['ZoomWide'] = $cmds['ZoomRoot']."wide";
	$cmds['ZoomStop'] = "zoom_stop";
	$cmds['ZoomAuto'] = "zoom_auto";
	$cmds['ZoomMan'] = "zoom_man";

	if ( $monitor['CanFocusCon'] )
		$cmds['FocusRoot'] = "focus_con_";
	elseif ( $monitor['CanFocusRel'] )
		$cmds['FocusRoot'] = "focus_rel_";
	elseif ( $monitor['CanFocusAbs'] )
		$cmds['FocusRoot'] = "focus_abs_";
	$cmds['FocusFar'] = $cmds['FocusRoot']."far";
	$cmds['FocusNear'] = $cmds['FocusRoot']."near";
	$cmds['FocusStop'] = "focus_stop";
	$cmds['FocusAuto'] = "focus_auto";
	$cmds['FocusMan'] = "focus_man";

	if ( $monitor['CanIrisCon'] )
		$cmds['IrisRoot'] = "iris_con_";
	elseif ( $monitor['CanIrisRel'] )
		$cmds['IrisRoot'] = "iris_rel_";
	elseif ( $monitor['CanIrisAbs'] )
		$cmds['IrisRoot'] = "iris_abs_";
	$cmds['IrisOpen'] = $cmds['IrisRoot']."open";
	$cmds['IrisClose'] = $cmds['IrisRoot']."close";
	$cmds['IrisStop'] = "iris_stop";
	$cmds['IrisAuto'] = "iris_auto";
	$cmds['IrisMan'] = "iris_man";

	if ( $monitor['CanWhiteCon'] )
		$cmds['WhiteRoot'] = "white_con_";
	elseif ( $monitor['CanWhiteRel'] )
		$cmds['WhiteRoot'] = "white_rel_";
	elseif ( $monitor['CanWhiteAbs'] )
		$cmds['WhiteRoot'] = "white_abs_";
	$cmds['WhiteIn'] = $cmds['WhiteRoot']."in";
	$cmds['WhiteOut'] = $cmds['WhiteRoot']."out";
	$cmds['WhiteAuto'] = "white_auto";
	$cmds['WhiteMan'] = "white_man";

	if ( $monitor['CanGainCon'] )
		$cmds['GainRoot'] = "gain_con_";
	elseif ( $monitor['CanGainRel'] )
		$cmds['GainRoot'] = "gain_rel_";
	elseif ( $monitor['CanGainAbs'] )
		$cmds['GainRoot'] = "gain_abs_";
	$cmds['GainUp'] = $cmds['GainRoot']."up";
	$cmds['GainDown'] = $cmds['GainRoot']."down";
	$cmds['GainAuto'] = "gain_auto";
	$cmds['GainMan'] = "gain_man";

	if ( $monitor['CanMoveCon'] )
	{
		$cmds['MoveRoot'] = "move_con_";
		$cmds['Center'] = "move_stop";
	}
	elseif ( $monitor['CanMoveRel'] )
	{
		$cmds['MoveRoot'] = "move_rel_";
		$cmds['Center'] = $cmds['PresetHome'];
	}
	elseif ( $monitor['CanMoveAbs'] )
	{
		$cmds['MoveRoot'] = "move_abs_";
		$cmds['Center'] = $cmds['PresetHome'];
	}

	$cmds['MoveUp'] = $cmds['MoveRoot']."up";
	$cmds['MoveDown'] = $cmds['MoveRoot']."down";
	$cmds['MoveLeft'] = $cmds['MoveRoot']."left";
	$cmds['MoveRight'] = $cmds['MoveRoot']."right";
	$cmds['MoveUpLeft'] = $cmds['MoveRoot']."upleft";
	$cmds['MoveUpRight'] = $cmds['MoveRoot']."upright";
	$cmds['MoveDownLeft'] = $cmds['MoveRoot']."downleft";
	$cmds['MoveDownRight'] = $cmds['MoveRoot']."downright";

	return( $cmds );
}

function controlFocus( $monitor )
{
	global $cmds, $zmSlangFocus, $zmSlangNear, $zmSlangFar, $zmSlangAuto, $zmSlangMan;

	ob_start();
?>
<div id="focusControls">
  <div><?= $zmSlangNear ?></div>
  <div class="longArrowBtn upBtn" onclick="controlCmd( '<?= $cmds['FocusNear'] ?>')"></div>
  <div<?php if ( $monitor['CanFocusCon'] ) { ?> onclick="controlCmd( '<?= $cmds['FocusStop'] ?>')"<?php } ?>><?= $zmSlangFocus ?></div>
  <div class="longArrowBtn downBtn" onclick="controlCmd( '<?= $cmds['FocusFar'] ?>')"></div>
  <div><?= $zmSlangFar ?></div>
<?php
	if ( $monitor['CanAutoFocus'] )
	{
?>
  <div><input type="button" class="textbutton" value="<?= $zmSlangAuto ?>" onclick="controlCmd( '<?= $cmds['FocusAuto'] ?>')"/></div>
  <div><input type="button" class="textbutton" value="<?= $zmSlangMan ?>" onclick="controlCmd( '<?= $cmds['FocusMan'] ?>')"/></div>
<?php
	}
?>
</div>
<?php
	return( ob_get_clean() );
}

function controlZoom( $monitor )
{
	global $cmds, $zmSlangZoom, $zmSlangTele, $zmSlangWide, $zmSlangAuto, $zmSlangMan;

	ob_start();
?>
<div id="zoomControls">
  <div><?= $zmSlangTele ?></div>
  <div class="longArrowBtn upBtn" onclick="controlCmd( '<?= $cmds['ZoomTele'] ?>')"></div>
  <div<?php if ( $monitor['CanZoomCon'] ) { ?> onclick="controlCmd( '<?= $cmds['ZoomStop'] ?>')"<?php } ?>><?= $zmSlangZoom ?></div>
  <div class="longArrowBtn downBtn" onclick="controlCmd( '<?= $cmds['ZoomWide'] ?>')"></div>
  <div><?= $zmSlangWide ?></div>
<?php
	if ( $monitor['CanAutoZoom'] )
	{
?>
  <div><input type="button" class="textbutton" value="<?= $zmSlangAuto ?>" onclick="controlCmd( '<?= $cmds['ZoomAuto'] ?>')"/></div>
  <div><input type="button" class="textbutton" value="<?= $zmSlangMan ?>" onclick="controlCmd( '<?= $cmds['ZoomMan'] ?>')"/></div>
<?php
	}
?>
</div><?php
	return( ob_get_clean() );
}

function controlIris( $monitor )
{
	global $cmds, $zmSlangIris, $zmSlangOpen, $zmSlangClose, $zmSlangAuto, $zmSlangMan;

	ob_start();
?>
<div id="irisControls">
  <div><?= $zmSlangOpen ?></div>
  <div class="longArrowBtn upBtn" onclick="controlCmd( '<?= $cmds['IrisOpen'] ?>')"></div>
  <div<?php if ( $monitor['CanIrisCon'] ) { ?> onclick="controlCmd( '<?= $cmds['IrisStop'] ?>')"<?php } ?>><?= $zmSlangIris ?></div>
  <div class="longArrowBtn downBtn" onclick="controlCmd( '<?= $cmds['IrisClose'] ?>')"></div>
  <div><?= $zmSlangClose ?></div>
<?php
	if ( $monitor['CanAutoIris'] )
	{
?>
  <div><input type="button" class="textbutton" value="<?= $zmSlangAuto ?>" onclick="controlCmd( '<?= $cmds['IrisAuto'] ?>')"/></div>
  <div><input type="button" class="textbutton" value="<?= $zmSlangMan ?>" onclick="controlCmd( '<?= $cmds['IrisMan'] ?>')"/></div>
<?php
	}
?>
</div>
<?php
	return( ob_get_clean() );
}

function controlWhite( $monitor )
{
	global $cmds, $zmSlangWhite, $zmSlangIn, $zmSlangOut, $zmSlangAuto, $zmSlangMan;

	ob_start();
?>
<div id="whiteControls">
  <div><?= $zmSlangIn ?></div>
  <div class="longArrowBtn upBtn" onclick="controlCmd( '<?= $cmds['WhiteIn'] ?>')"></div>
  <div<?php if ( $monitor['CanWhiteCon'] ) { ?> onclick="controlCmd( '<?= $cmds['WhiteStop'] ?>')"<?php } ?>><?= $zmSlangWhite ?></div>
  <div class="longArrowBtn downBtn" onclick="controlCmd( '<?= $cmds['WhiteOut'] ?>')"></div>
  <div><?= $zmSlangOut ?></div>
<?php
	if ( $monitor['CanAutoWhite'] )
	{
?>
  <div><input type="button" class="textbutton" value="<?= $zmSlangAuto ?>" onclick="controlCmd( '<?= $cmds['WhiteAuto'] ?>')"/></div>
  <div><input type="button" class="textbutton" value="<?= $zmSlangMan ?>" onclick="controlCmd( '<?= $cmds['WhiteMan'] ?>')"/></div>
<?php
	}
?>
</div>
<?php
	return( ob_get_clean() );
}

function controlPanTilt( $monitor )
{
	global $cmds, $zmSlangPanTilt;

	ob_start();
?>
<div id="pantiltControls">
  <div><?= $zmSlangPanTilt ?></div>
  <div id="pantiltButtons">
<?php
	if ( $monitor['CanTilt'] )
	{
		if ( $monitor['CanPan'] )
		{
			if ( $monitor['CanMoveDiag'] )
			{
?>
      <div id="upLeftBtn" class="arrowBtn" onclick="controlCmd( '<?= $cmds['MoveUpLeft'] ?>')"></div>
<?php
			}
		}
?>
      <div id="upBtn" class="arrowBtn" onclick="controlCmd( '<?= $cmds['MoveUp'] ?>')"></div>
<?php
		if ( $monitor['CanPan'] )
		{
			if ( $monitor['CanMoveDiag'] )
			{
?>
      <div id="upRightBtn" class="arrowBtn" onclick="controlCmd( '<?= $cmds['MoveUpRight'] ?>')"></div>
<?php
			}
		}
    }
	if ( $monitor['CanPan'] )
	{
?>
      <div id="leftBtn" class="arrowBtn" onclick="controlCmd( '<?= $cmds['MoveLeft'] ?>')"></div>
<?php
	}
?>
      <div id="centerBtn" class="arrowBtn" onclick="controlCmd( '<?= $cmds['Center'] ?>')"></div>
<?php
	if ( $monitor['CanPan'] )
	{
?>
      <div id="rightBtn" class="arrowBtn" onclick="controlCmd( '<?= $cmds['MoveRight'] ?>')"></div>
<?php
	}
	if ( $monitor['CanTilt'] )
	{
		if ( $monitor['CanPan'] )
		{
			if ( $monitor['CanMoveDiag'] )
			{
?>
      <div id="downLeftBtn" class="arrowBtn" onclick="controlCmd( '<?= $cmds['MoveDownLeft'] ?>')"></div>
<?php
			}
		}
?>
      <div id="downBtn" class="arrowBtn" onclick="controlCmd( '<?= $cmds['MoveDown'] ?>')"></div>
<?php
		if ( $monitor['CanPan'] )
		{
			if ( $monitor['CanMoveDiag'] )
			{
?>
      <div id="downRightBtn" class="arrowBtn" onclick="controlCmd( '<?= $cmds['MoveDownRight'] ?>')"></div>
<?php
			}
		}
	}
?>
  </div>
</div>
<?php
	return( ob_get_clean() );
}

function controlPresets( $monitor )
{
	global $cmds, $jws, $zmSlangPresets, $zmSlangHome, $zmSlangSet;

	define( "MAX_PRESETS", "12" );

    $sql = "select * from ControlPresets where MonitorId = '".$monitor['Id']."'";
    $result = mysql_query( $sql );
    if ( !$result )
        die( mysql_error() );
    $labels = array();
    while( $row = mysql_fetch_assoc( $result ) )
    {
        $labels[$row['Preset']] = $row['Label'];
    }
    mysql_free_result( $result );

	$preset_break = (int)(($monitor['NumPresets']+1)/((int)(($monitor['NumPresets']-1)/MAX_PRESETS)+1));

	ob_start();
?>
<div id="presetControls">
  <div><?= $zmSlangPresets ?></div>
  <div>
<?php
	for ( $i = 1; $i <= $monitor['NumPresets']; $i++ )
	{
?><input type="button" class="numbutton" title="<?= $labels[$i]?$labels[$i]:"" ?>" value="<?= $i ?>" onclick="controlCmd( '<?= $cmds['PresetGoto'] ?><?=$i?>' );"/><?php (($i%$preset_break)==0)?"<br/>":"&nbsp;&nbsp;" ?><?php
		if ( $i && (($i%$preset_break) == 0) )
		{
?><br/><?php
		}
	}
?>
  </div>
  <div>
<?php
	if ( $monitor['HasHomePreset'] )
	{
?>
    <span><input type="button" class="textbutton" value="<?= $zmSlangHome ?>" onclick="controlCmd( '<?= $cmds['PresetHome'] ?>' );"/></span>
<?php
	}
	if ( canEdit( 'Monitors' ) && $monitor['CanSetPresets'] )
	{
?>
    <span><input type="button" class="textbutton" value="<?= $zmSlangSet ?>" onclick="newWindow( '<?= $PHP_SELF ?>?view=controlpreset&mid=<?= $monitor['Id'] ?>', 'zmPreset', <?= $jws['preset']['w'] ?>, <?= $jws['preset']['h'] ?> );"/></span>
<?php
	}
?>
  </div>
</div>
<?php
	return( ob_get_clean() );
}

function controlPower( $monitor )
{
	global $cmds, $zmSlangControl, $zmSlangWake, $zmSlangSleep, $zmSlangReset;

	ob_start();
?>
<div id="powerControls">
  <div><?= $zmSlangControl ?></div>
  <div>
<?php
	if ( $monitor['CanWake'] )
	{
?>
    <span><input type="button" class="textbutton" value="<?= $zmSlangWake ?>" onclick="controlCmd( '<?= $cmds['Wake'] ?>')"/></span>
<?php
	}
	if ( $monitor['CanSleep'] )
	{
?>
    <span><input type="button" class="textbutton" value="<?= $zmSlangSleep ?>" onclick="controlCmd( '<?= $cmds['Sleep'] ?>')"/></span>
<?php
	}
	if ( $monitor['CanReset'] )
	{
?>
    <span><input type="button" class="textbutton" value="<?= $zmSlangReset ?>" onclick="controlCmd( '<?= $cmds['Reset'] ?>')"/></span>
<?php
	}
?>
  </div>
</div>
<?php
	return( ob_get_clean() );
}

