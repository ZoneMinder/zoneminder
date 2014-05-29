<?php
//
// ZoneMinder web control function library, $Date$, $Revision$
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

function getControlCommands( $monitor )
{
    $cmds = array();

    $cmds['Wake'] = "wake";
    $cmds['Sleep'] = "sleep";
    $cmds['Reset'] = "reset";

    $cmds['PresetSet'] = "presetSet";
    $cmds['PresetGoto'] = "presetGoto";
    $cmds['PresetHome'] = "presetHome";

    if ( !empty($monitor['CanZoom']) )
    {
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
    }

    if ( !empty($monitor['CanFocus']) )
    {
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
    }

    if ( !empty($monitor['CanIris']) )
    {
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
    }

    if ( !empty($monitor['CanWhite']) )
    {
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
    }

    if ( !empty($monitor['CanGain']) )
    {
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
    }

    if ( !empty($monitor['CanMove']) )
    {
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
    }
    return( $cmds );
}

function controlFocus( $monitor, $cmds )
{
    global $SLANG;

    ob_start();
?>
<div class="arrowControl focusControls">
  <div class="arrowLabel"><?= $SLANG['Near'] ?></div>
  <div class="longArrowBtn upBtn" onclick="controlCmd('<?= $cmds['FocusNear'] ?>',event,0,-1)"></div>
  <div class="arrowCenter"<?php if ( $monitor['CanFocusCon'] ) { ?> onclick="controlCmd('<?= $cmds['FocusStop'] ?>')"<?php } ?>><?= $SLANG['Focus'] ?></div>
  <div class="longArrowBtn downBtn" onclick="controlCmd('<?= $cmds['FocusFar'] ?>',event,0,1)"></div>
  <div class="arrowLabel"><?= $SLANG['Far'] ?></div>
<?php
    if ( $monitor['CanAutoFocus'] )
    {
?>
  <input type="button" class="ptzTextBtn" value="<?= $SLANG['Auto'] ?>" onclick="controlCmd('<?= $cmds['FocusAuto'] ?>')"/>
  <input type="button" class="ptzTextBtn" value="<?= $SLANG['Man'] ?>" onclick="controlCmd('<?= $cmds['FocusMan'] ?>')"/>
<?php
    }
?>
</div>
<?php
    return( ob_get_clean() );
}

function controlZoom( $monitor, $cmds )
{
    global $SLANG;

    ob_start();
?>
<div class="arrowControl zoomControls">
  <div class="arrowLabel"><?= $SLANG['Tele'] ?></div>
  <div class="longArrowBtn upBtn" onclick="controlCmd('<?= $cmds['ZoomTele'] ?>',event,0,-1)"></div>
  <div class="arrowCenter"<?php if ( $monitor['CanZoomCon'] ) { ?> onclick="controlCmd('<?= $cmds['ZoomStop'] ?>')"<?php } ?>><?= $SLANG['Zoom'] ?></div>
  <div class="longArrowBtn downBtn" onclick="controlCmd('<?= $cmds['ZoomWide'] ?>',event,0,1)"></div>
  <div class="arrowLabel"><?= $SLANG['Wide'] ?></div>
<?php
    if ( $monitor['CanAutoZoom'] )
    {
?>
  <input type="button" class="ptzTextBtn" value="<?= $SLANG['Auto'] ?>" onclick="controlCmd('<?= $cmds['ZoomAuto'] ?>')"/>
  <input type="button" class="ptzTextBtn" value="<?= $SLANG['Man'] ?>" onclick="controlCmd('<?= $cmds['ZoomMan'] ?>')"/>
<?php
    }
?>
</div><?php
    return( ob_get_clean() );
}

function controlIris( $monitor, $cmds )
{
    global $SLANG;

    ob_start();
?>
<div class="arrowControl irisControls">
  <div class="arrowLabel"><?= $SLANG['Open'] ?></div>
  <div class="longArrowBtn upBtn" onclick="controlCmd('<?= $cmds['IrisOpen'] ?>',event,0,-1)"></div>
  <div class="arrowCenter"<?php if ( $monitor['CanIrisCon'] ) { ?> onclick="controlCmd('<?= $cmds['IrisStop'] ?>')"<?php } ?>><?= $SLANG['Iris'] ?></div>
  <div class="longArrowBtn downBtn" onclick="controlCmd('<?= $cmds['IrisClose'] ?>',event,0,1)"></div>
  <div class="arrowLabel"><?= $SLANG['Close'] ?></div>
<?php
    if ( $monitor['CanAutoIris'] )
    {
?>
  <input type="button" class="ptzTextBtn" value="<?= $SLANG['Auto'] ?>" onclick="controlCmd('<?= $cmds['IrisAuto'] ?>')"/>
  <input type="button" class="ptzTextBtn" value="<?= $SLANG['Man'] ?>" onclick="controlCmd('<?= $cmds['IrisMan'] ?>')"/>
<?php
    }
?>
</div>
<?php
    return( ob_get_clean() );
}

function controlWhite( $monitor, $cmds )
{
    global $SLANG;

    ob_start();
?>
<div class="arrowControl whiteControls">
  <div class="arrowLabel"><?= $SLANG['In'] ?></div>
  <div class="longArrowBtn upBtn" onclick="controlCmd('<?= $cmds['WhiteIn'] ?>',event,0,-1)"></div>
  <div class="arrowCenter"<?php if ( $monitor['CanWhiteCon'] ) { ?> onclick="controlCmd('<?= $cmds['WhiteStop'] ?>')"<?php } ?>><?= $SLANG['White'] ?></div>
  <div class="longArrowBtn downBtn" onclick="controlCmd('<?= $cmds['WhiteOut'] ?>',event,0,1)"></div>
  <div class="arrowLabel"><?= $SLANG['Out'] ?></div>
<?php
    if ( $monitor['CanAutoWhite'] )
    {
?>
  <input type="button" class="ptzTextBtn" value="<?= $SLANG['Auto'] ?>" onclick="controlCmd('<?= $cmds['WhiteAuto'] ?>')"/>
  <input type="button" class="ptzTextBtn" value="<?= $SLANG['Man'] ?>" onclick="controlCmd('<?= $cmds['WhiteMan'] ?>')"/>
<?php
    }
?>
</div>
<?php
    return( ob_get_clean() );
}

function controlPanTilt( $monitor, $cmds )
{
    global $SLANG;

    ob_start();
?>
<div class="pantiltControls">
  <div class="pantilLabel"><?= $SLANG['PanTilt'] ?></div>
  <div class="pantiltButtons">
<?php
    $hasPan = $monitor['CanPan'];
    $hasTilt = $monitor['CanTilt'];
    $hasDiag = $hasPan && $hasTilt && $monitor['CanMoveDiag'];
?>
      <div class="arrowBtn upLeftBtn<?= $hasDiag?'':' invisible' ?>" onclick="controlCmd('<?= $cmds['MoveUpLeft'] ?>',event,-1,-1)"></div>
      <div class="arrowBtn upBtn<?= $hasTilt?'':' invisible' ?>" onclick="controlCmd('<?= $cmds['MoveUp'] ?>',event,0,-1)"></div>
      <div class="arrowBtn upRightBtn<?= $hasDiag?'':' invisible' ?>" onclick="controlCmd('<?= $cmds['MoveUpRight'] ?>',event,1,-1)"></div>
      <div class="arrowBtn leftBtn<?= $hasPan?'':' invisible' ?>" onclick="controlCmd('<?= $cmds['MoveLeft'] ?>',event,1,0)"></div>
      <div class="arrowBtn centerBtn" onclick="controlCmd('<?= $cmds['Center'] ?>')"></div>
      <div class="arrowBtn rightBtn<?= $hasPan?'':' invisible' ?>" onclick="controlCmd('<?= $cmds['MoveRight'] ?>',event,1,0)"></div>
      <div class="arrowBtn downLeftBtn<?= $hasDiag?'':' invisible' ?>" onclick="controlCmd('<?= $cmds['MoveDownLeft'] ?>',event,-1,1)"></div>
      <div class="arrowBtn downBtn<?= $hasTilt?'':' invisible' ?>" onclick="controlCmd('<?= $cmds['MoveDown'] ?>',event,0,1)"></div>
      <div class="arrowBtn downRightBtn<?= $hasDiag?'':' invisible' ?>" onclick="controlCmd('<?= $cmds['MoveDownRight'] ?>',event,1,1)"></div>
  </div>
</div>
<?php
    return( ob_get_clean() );
}

function controlPresets( $monitor, $cmds )
{
    global $SLANG;

    define( "MAX_PRESETS", "12" );

    $sql = "select * from ControlPresets where MonitorId = '".$monitor['Id']."'";
    $labels = array();
    foreach( dbFetchAll( $sql ) as $row )
    {
        $labels[$row['Preset']] = $row['Label'];
    }

    $presetBreak = (int)(($monitor['NumPresets']+1)/((int)(($monitor['NumPresets']-1)/MAX_PRESETS)+1));

    ob_start();
?>
<div class="presetControls">
  <!--<div><?= $SLANG['Presets'] ?></div>-->
  <div>
<?php
    for ( $i = 1; $i <= $monitor['NumPresets']; $i++ )
    {
?><input type="button" class="ptzNumBtn" title="<?= isset($labels[$i])?$labels[$i]:"" ?>" value="<?= $i ?>" onclick="controlCmd('<?= $cmds['PresetGoto'] ?><?= $i ?>');"/><?php
        if ( $i && (($i%$presetBreak) == 0) )
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
    <input type="button" class="ptzTextBtn" value="<?= $SLANG['Home'] ?>" onclick="controlCmd('<?= $cmds['PresetHome'] ?>');"/>
<?php
    }
    if ( canEdit( 'Monitors') && $monitor['CanSetPresets'] )
    {
?>
    <input type="button" class="ptzTextBtn" value="<?= $SLANG['Set'] ?>" onclick="createPopup( '?view=controlpreset&amp;mid=<?= $monitor['Id'] ?>', 'zmPreset', 'preset' );"/>
<?php
    }
?>
  </div>
</div>
<?php
    return( ob_get_clean() );
}

function controlPower( $monitor, $cmds )
{
    global $SLANG;

    ob_start();
?>
<div class="powerControls">
  <div class="powerLabel"><?= $SLANG['Control'] ?></div>
  <div>
<?php
    if ( $monitor['CanWake'] )
    {
?>
    <input type="button" class="ptzTextBtn" value="<?= $SLANG['Wake'] ?>" onclick="controlCmd('<?= $cmds['Wake'] ?>')"/>
<?php
    }
    if ( $monitor['CanSleep'] )
    {
?>
    <input type="button" class="ptzTextBtn" value="<?= $SLANG['Sleep'] ?>" onclick="controlCmd('<?= $cmds['Sleep'] ?>')"/>
<?php
    }
    if ( $monitor['CanReset'] )
    {
?>
    <input type="button" class="ptzTextBtn" value="<?= $SLANG['Reset'] ?>" onclick="controlCmd('<?= $cmds['Reset'] ?>')"/>
<?php
    }
?>
  </div>
</div>
<?php
    return( ob_get_clean() );
}

function ptzControls( $monitor )
{
    $cmds = getControlCommands( $monitor );
    ob_start();
?>
        <div class="controlsPanel">
<?php
        if ( $monitor['CanFocus'] )
            echo controlFocus( $monitor, $cmds );
        if ( $monitor['CanZoom'] )
            echo controlZoom( $monitor, $cmds );
        if ( $monitor['CanIris'] )
            echo controlIris( $monitor, $cmds );
        if ( $monitor['CanWhite'] )
            echo controlWhite( $monitor, $cmds );
        if ( $monitor['CanMove'] || ( $monitor['CanWake'] || $monitor['CanSleep'] || $monitor['CanReset'] ) )
        {
?>
          <div class="pantiltPanel">
<?php
            if ( $monitor['CanMove'] )
                echo controlPanTilt( $monitor, $cmds );
            if ( $monitor['CanWake'] || $monitor['CanSleep'] || $monitor['CanReset'] )
                echo controlPower( $monitor, $cmds );
?>
          </div>
<?php
        }
?>
        </div>
<?php
        if ( $monitor['HasPresets'] )
            echo controlPresets( $monitor, $cmds );
    return( ob_get_clean() );
}
?>
