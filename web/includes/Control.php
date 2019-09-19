<?php
namespace ZM;

require_once('database.php');
require_once('Object.php');

class Control extends ZM_Object {
  protected static $table = 'Controls';

  protected $defaults = array(
    'Id'  =>  null,
    'CanMove' => 0,
    'CanMoveDiag' => 0,
    'CanMoveMap' => 0,
    'CanMoveAbs' => 0,
    'CanMoveRel' => 0,
    'CanMoveCon' => 0,
    'CanPan' => 0,
    'CanReset' => 0,
    'CanReboot' =>  0,
    'CanSleep' => 0,
    'CanWake' => 0,
    'MinPanRange' => NULL,
    'MaxPanRange' => NULL,
    'MinPanStep' => NULL,
    'MaxPanStep' => NULL,
    'HasPanSpeed' => 0,
    'MinPanSpeed' => NULL,
    'MaxPanSpeed' => NULL,
    'HasTurboPan' => 0,
    'TurboPanSpeed' => NULL,
    'CanTilt' => 0,
    'MinTiltRange' => NULL,
    'MaxTiltRange' => NULL,
    'MinTiltStep' => NULL,
    'MaxTiltStep' => NULL,
    'HasTiltSpeed' => 0,
    'MinTiltSpeed' => NULL,
    'MaxTiltSpeed' => NULL,
    'HasTurboTilt' => 0,
    'TurboTiltSpeed' => NULL,
    'CanZoom' => 0,
    'CanZoomAbs' => 0,
    'CanZoomRel' => 0,
    'CanZoomCon' => 0,
    'MinZoomRange' => NULL,
    'MaxZoomRange' => NULL,
    'MinZoomStep' => NULL,
    'MaxZoomStep' => NULL,
    'HasZoomSpeed' => 0,
    'MinZoomSpeed' => NULL,
    'MaxZoomSpeed' => NULL,
    'CanFocus' => 0,
    'CanAutoFocus' => 0,
    'CanFocusAbs' => 0,
    'CanFocusRel' => 0,
    'CanFocusCon' => 0,
    'MinFocusRange' => NULL,
    'MaxFocusRange' => NULL,
    'MinFocusStep' => NULL,
    'MaxFocusStep' => NULL,
    'HasFocusSpeed' => 0,
    'MinFocusSpeed' => NULL,
    'MaxFocusSpeed' => NULL,
    'CanIris' => 0,
    'CanAutoIris' => 0,
    'CanIrisAbs' => 0,
    'CanIrisRel' => 0,
    'CanIrisCon' => 0,
    'MinIrisRange' => NULL,
    'MaxIrisRange' => NULL,
    'MinIrisStep' => NULL,
    'MaxIrisStep' => NULL,
    'HasIrisSpeed' => 0,
    'MinIrisSpeed' => NULL,
    'MaxIrisSpeed' => NULL, 
    'CanGain' => 0,
    'CanAutoGain' => 0,
    'CanGainAbs' => 0,
    'CanGainRel' => 0,
    'CanGainCon' => 0,
    'MinGainRange' => NULL,
    'MaxGainRange' => NULL, 
    'MinGainStep' => NULL,
    'MaxGainStep' => NULL,
    'HasGainSpeed' => 0,
    'MinGainSpeed' => NULL,
    'MaxGainSpeed' => NULL,
    'CanWhite' => 0,
    'CanAutoWhite' => 0,
    'CanWhiteAbs' => 0,
    'CanWhiteRel' => 0,
    'CanWhiteCon' => 0, 
    'MinWhiteRange' => NULL,
    'MaxWhiteRange' => NULL,
    'MinWhiteStep' => NULL,
    'MaxWhiteStep' => NULL,
    'HasWhiteSpeed' => 0, 
    'MinWhiteSpeed' => NULL,
    'MaxWhiteSpeed' => NULL,
    'HasPresets' => 0,
    'NumPresets' => 0,
    'HasHomePreset' => 0,
    'CanSetPresets' => 0,
    'Name' => 'New',
    'Type' => 'Local',
    'Protocol' => NULL
    );

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function commands() {
    $cmds = array();

    $cmds['Wake'] = 'wake';
    $cmds['Sleep'] = 'sleep';
    $cmds['Reset'] = 'reset';
    $cmds['Reboot'] = 'reboot';

    $cmds['PresetSet'] = 'presetSet';
    $cmds['PresetGoto'] = 'presetGoto';
    $cmds['PresetHome'] = 'presetHome';

    if ( $this->CanZoom() ) {
      if ( $this->CanZoomCon() )
        $cmds['ZoomRoot'] = 'zoomCon';
      elseif ( $this->CanZoomRel() )
        $cmds['ZoomRoot'] = 'zoomRel';
      elseif ( $this->CanZoomAbs() )
        $cmds['ZoomRoot'] = 'zoomAbs';
      $cmds['ZoomTele'] = $cmds['ZoomRoot'].'Tele';
      $cmds['ZoomWide'] = $cmds['ZoomRoot'].'Wide';
      $cmds['ZoomStop'] = 'zoomStop';
      $cmds['ZoomAuto'] = 'zoomAuto';
      $cmds['ZoomMan'] = 'zoomMan';
    }

    if ( $this->CanFocus() ) {
      if ( $this->CanFocusCon() )
        $cmds['FocusRoot'] = 'focusCon';
      elseif ( $this->CanFocusRel() )
        $cmds['FocusRoot'] = 'focusRel';
      elseif ( $this->CanFocusAbs() )
        $cmds['FocusRoot'] = 'focusAbs';
      $cmds['FocusFar'] = $cmds['FocusRoot'].'Far';
      $cmds['FocusNear'] = $cmds['FocusRoot'].'Near';
      $cmds['FocusStop'] = 'focusStop';
      $cmds['FocusAuto'] = 'focusAuto';
      $cmds['FocusMan'] = 'focusMan';
    }

    if ( $this->CanIris() ) {
      if ( $this->CanIrisCon() )
        $cmds['IrisRoot'] = 'irisCon';
      elseif ( $this->CanIrisRel() )
        $cmds['IrisRoot'] = 'irisRel';
      elseif ( $this->CanIrisAbs() )
        $cmds['IrisRoot'] = 'irisAbs';
      $cmds['IrisOpen'] = $cmds['IrisRoot'].'Open';
      $cmds['IrisClose'] = $cmds['IrisRoot'].'Close';
      $cmds['IrisStop'] = 'irisStop';
      $cmds['IrisAuto'] = 'irisAuto';
      $cmds['IrisMan'] = 'irisMan';
    }

    if ( $this->CanWhite() ) {
      if ( $this->CanWhiteCon() )
        $cmds['WhiteRoot'] = 'whiteCon';
      elseif ( $this->CanWhiteRel() )
        $cmds['WhiteRoot'] = 'whiteRel';
      elseif ( $this->CanWhiteAbs() )
        $cmds['WhiteRoot'] = 'whiteAbs';
      $cmds['WhiteIn'] = $cmds['WhiteRoot'].'In';
      $cmds['WhiteOut'] = $cmds['WhiteRoot'].'Out';
      $cmds['WhiteAuto'] = 'whiteAuto';
      $cmds['WhiteMan'] = 'whiteMan';
    }

    if ( $this->CanGain() ) {
      if ( $this->CanGainCon() )
        $cmds['GainRoot'] = 'gainCon';
      elseif ( $this->CanGainRel() )
        $cmds['GainRoot'] = 'gainRel';
      elseif ( $this->CanGainAbs() )
        $cmds['GainRoot'] = 'gainAbs';
      $cmds['GainUp'] = $cmds['GainRoot'].'Up';
      $cmds['GainDown'] = $cmds['GainRoot'].'Down';
      $cmds['GainAuto'] = 'gainAuto';
      $cmds['GainMan'] = 'gainMan';
    }

    if ( $this->CanMove() ) {
      if ( $this->CanMoveCon() ) {
        $cmds['MoveRoot'] = 'moveCon';
        $cmds['Center'] = 'moveStop';
      } elseif ( $this->CanMoveRel() ) {
        $cmds['MoveRoot'] = 'moveRel';
        $cmds['Center'] = $cmds['PresetHome'];
      } elseif ( $this->CanMoveAbs() ) {
        $cmds['MoveRoot'] = 'moveAbs';
        $cmds['Center'] = $cmds['PresetHome'];
      } else {
        $cmds['MoveRoot'] = '';
      }

      $cmds['MoveUp'] = $cmds['MoveRoot'].'Up';
      $cmds['MoveDown'] = $cmds['MoveRoot'].'Down';
      $cmds['MoveLeft'] = $cmds['MoveRoot'].'Left';
      $cmds['MoveRight'] = $cmds['MoveRoot'].'Right';
      $cmds['MoveUpLeft'] = $cmds['MoveRoot'].'UpLeft';
      $cmds['MoveUpRight'] = $cmds['MoveRoot'].'UpRight';
      $cmds['MoveDownLeft'] = $cmds['MoveRoot'].'DownLeft';
      $cmds['MoveDownRight'] = $cmds['MoveRoot'].'DownRight';
    }
    return $cmds;
  } // end public function commands
} // end class Control
?>
