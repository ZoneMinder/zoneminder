<?php
//
// ZoneMinder web control function library, $Date: 2008-07-25 10:48:16 +0100 (Fri, 25 Jul 2008) $, $Revision: 2612 $
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

function controlPresets( $monitor, $cmds )
{
    global $SLANG;

    define( "MAX_PRESETS", "10" );

    $sql = "select * from ControlPresets where MonitorId = '".$monitor['Id']."'";
    $labels = array();
    foreach( dbFetchAll( $sql ) as $row )
    {
        $labels[$row['Preset']] = $row['Label'];
    }

    ob_start();
?>
<div class="presetControls">
  <div>
    <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
      <div class="hidden">
        <fieldset>
          <input type="hidden" name="view" value="<?= $_REQUEST['view'] ?>"/>
          <input type="hidden" name="action" value="control"/>
          <input type="hidden" name="mid" value="<?= $monitor['Id'] ?>"/>
          <input type="hidden" name="control" value="<?= $cmds['PresetGoto'] ?>"/>
        </fieldset>
      </div>
      <div>
<?php
    for ( $i = 1; $i <= min($monitor['NumPresets'],MAX_PRESETS); $i++ )
    {
?>
        <input type="submit" class="ptzNumBtn" title="<?= isset($labels[$i])?$labels[$i]:"" ?>" name="preset" value="<?= $i ?>"/>
<?php
    }
?>
      </div>
    </form>
  </div>
</div>
<?php
    return( ob_get_clean() );
}

function ptzControls( $monitor )
{
    $cmds = getControlCommands( $monitor );
    if ( $monitor['HasPresets'] )
        echo controlPresets( $monitor, $cmds );
    return( ob_get_clean() );
}
?>
