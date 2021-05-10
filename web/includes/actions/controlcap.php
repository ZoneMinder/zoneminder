<?php
//
// ZoneMinder web action file
// Copyright (C) 2019 ZoneMinder LLC
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
  ZM\Warning('Need Control permissions to edit control capabilities');
  return;
} // end if !canEdit Controls

if ( $action == 'controlcap' ) {
  require_once('includes/Control.php');
  $Control = new ZM\Control( !empty($_REQUEST['cid']) ? $_REQUEST['cid'] : null );

  $field_defaults = array(
    'CanWake' =>  0,
    'CanSleep'  =>  0,
    'CanReset'  =>  0,
    'CanReboot' =>  0,
    'CanMove' => 0,
    'CanMoveDiag' => 0,
    'CanMoveMap' => 0,
    'CanMoveRel' => 0,
    'CanMoveAbs' => 0,
    'CanMoveCon' => 0,
    'CanPan'    =>  0,
    'HasPanSpeed' =>  0,
    'HasTurboPan' =>  0,
    'CanTilt'     =>  0,
    'HasTiltSpeed'  =>  0,
    'HasTurboTilt'  =>  0,
    'CanZoom' => 0,
    'CanZoomRel' => 0,
    'CanZoomAbs' => 0,
    'CanZoomCon' => 0,
    'HasZoomSpeed'  =>  0,
    'CanFocus' => 0,
    'CanAutoFocus' => 0,
    'CanFocusRel' => 0,
    'CanFocusAbs' => 0,
    'CanFocusCon' => 0,
    'HasFocusSpeed'  =>  0,
    'CanGain' => 0,
    'CanAutoGain' => 0,
    'CanGainRel' => 0,
    'CanGainAbs' => 0,
    'CanGainCon' => 0,
    'HasGainSpeed'  =>  0,
    'CanWhite' => 0,
    'CanAutoWhite' => 0,
    'CanWhiteRel' => 0,
    'CanWhiteAbs' => 0,
    'CanWhiteCon' => 0,
    'HasWhiteSpeed'  =>  0,
    'CanIris' => 0,
    'CanAutoIris' => 0,
    'CanIrisRel' => 0,
    'CanIrisAbs' => 0,
    'CanIrisCon' => 0,
    'HasIrisSpeed'  =>  0,
    'HasPresets'    =>  0,
    'HasHomePreset' =>  0,
    'CanSetPresets' =>  0,
  );

  # Checkboxes don't return an element in the POST data, so won't be present in newControl.
  # So force a value for these fields
  foreach ( $field_defaults as $field => $value ) {
    if ( ! (isset($_REQUEST['newControl'][$field]) and $_REQUEST['newControl'][$field]) ) {
      $_REQUEST['newControl'][$field] = $value;
    }
  } # end foreach type

  //$changes = getFormChanges( $control, $_REQUEST['newControl'], $types, $columns );
  $Control->save($_REQUEST['newControl']);
  $refreshParent = true;
  $view = 'none';
} // end if action
?>
