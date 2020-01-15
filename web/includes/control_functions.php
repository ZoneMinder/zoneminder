<?php

function buildControlCommand($monitor) {
  $ctrlCommand = '';
  $control = $monitor->Control();

  if ( isset($_REQUEST['xge']) || isset($_REQUEST['yge']) ) {
    $slow = 0.9; // Threshold for slow speed/timeouts
    $turbo = 0.9; // Threshold for turbo speed

    if ( preg_match('/^([a-z]+)([A-Z][a-z]+)([A-Za-z]+)+$/', $_REQUEST['control'], $matches) ) {
      $command = $matches[1];
      $mode = $matches[2];
      $dirn = $matches[3];

      switch( $command ) {
      case 'focus' :
      {
        $factor = $_REQUEST['yge']/100;
        if ( $control->HasFocusSpeed() ) {
          $speed = intval(round($control->MinFocusSpeed()+(($control->MaxFocusSpeed()-$control->MinFocusSpeed())*$factor)));
          $ctrlCommand .= ' --speed='.$speed;
        }
        switch( $mode ) {
        case 'Abs' :
        case 'Rel' :
        {
          $step = intval(round($control->MinFocusStep()+(($control->MaxFocusStep()-$control->MinFocusStep())*$factor)));
          $ctrlCommand .= ' --step='.$step;
          break;
        }
        case 'Con' :
        {
          if ( $monitor->AutoStopTimeout() ) {
            $slowSpeed = intval(round($control->MinFocusSpeed()+(($control->MaxFocusSpeed()-$control->MinFocusSpeed())*$slow)));
            if ( $speed < $slowSpeed ) {
              $ctrlCommand .= ' --autostop';
            }
          }
          break;
        }
        }
        break;
      }
      case 'zoom' :
        $factor = $_REQUEST['yge']/100;
        if ( $control->HasZoomSpeed() ) {
          $speed = intval(round($control->MinZoomSpeed()+(($control->MaxZoomSpeed()-$control->MinZoomSpeed())*$factor)));
          $ctrlCommand .= ' --speed='.$speed;
        }
        switch( $mode ) {
        case 'Abs' :
        case 'Rel' :
          $step = intval(round($control->MinZoomStep()+(($control->MaxZoomStep()-$control->MinZoomStep())*$factor)));
          $ctrlCommand .= ' --step='.$step;
          break;
        case 'Con' :
          if ( $monitor->AutoStopTimeout() ) {
            $slowSpeed = intval(round($control->MinZoomSpeed()+(($control->MaxZoomSpeed()-$control->MinZoomSpeed())*$slow)));
            if ( $speed < $slowSpeed ) {
              $ctrlCommand .= ' --autostop';
            }
          }
          break;
        }
        break;
      case 'iris' :
        $factor = $_REQUEST['yge']/100;
        if ( $control->HasIrisSpeed() ) {
          $speed = intval(round($control->MinIrisSpeed()+(($control->MaxIrisSpeed()-$control->MinIrisSpeed())*$factor)));
          $ctrlCommand .= ' --speed='.$speed;
        }
        switch( $mode ) {
        case 'Abs' :
        case 'Rel' :
          $step = intval(round($control->MinIrisStep()+(($control->MaxIrisStep()-$control->MinIrisStep())*$factor)));
          $ctrlCommand .= ' --step='.$step;
          break;
        }
        break;
      case 'white' :
        $factor = $_REQUEST['yge']/100;
        if ( $control->HasWhiteSpeed() ) {
          $speed = intval(round($control->MinWhiteSpeed()+(($control->MaxWhiteSpeed()-$control->MinWhiteSpeed())*$factor)));
          $ctrlCommand .= ' --speed='.$speed;
        }
        switch( $mode ) {
        case 'Abs' :
        case 'Rel' :
          $step = intval(round($control->MinWhiteStep()+(($control->MaxWhiteStep()-$control->MinWhiteStep())*$factor)));
          $ctrlCommand .= ' --step='.$step;
          break;
        }
        break;
      case 'gain' :
        $factor = $_REQUEST['yge']/100;
        if ( $control->HasGainSpeed() ) {
          $speed = intval(round($control->MinGainSpeed()+(($control->MaxGainSpeed()-$control->MinGainSpeed())*$factor)));
          $ctrlCommand .= ' --speed='.$speed;
        }
        switch( $mode ) {
        case 'Abs' :
        case 'Rel' :
          $step = intval(round($control->MinGainStep()+(($control->MaxGainStep()-$control->MinGainStep())*$factor)));
          $ctrlCommand .= ' --step='.$step;
          break;
        }
        break;
      case 'move' :
        $xFactor = empty($_REQUEST['xge'])?0:$_REQUEST['xge']/100;
        $yFactor = empty($_REQUEST['yge'])?0:$_REQUEST['yge']/100;

        if ( $monitor->Orientation() != 'ROTATE_0' ) {
          $conversions = array(
            'ROTATE_90' => array(
              'Up' => 'Left',
              'Down' => 'Right',
              'Left' => 'Down',
              'Right' => 'Up',
              'UpLeft' => 'DownLeft',
              'UpRight' => 'UpLeft',
              'DownLeft' => 'DownRight',
              'DownRight' => 'UpRight',
            ),
            'ROTATE_180' => array(
              'Up' => 'Down',
              'Down' => 'Up',
              'Left' => 'Right',
              'Right' => 'Left',
              'UpLeft' => 'DownRight',
              'UpRight' => 'DownLeft',
              'DownLeft' => 'UpRight',
              'DownRight' => 'UpLeft',
            ),
            'ROTATE_270' => array(
              'Up' => 'Right',
              'Down' => 'Left',
              'Left' => 'Up',
              'Right' => 'Down',
              'UpLeft' => 'UpRight',
              'UpRight' => 'DownRight',
              'DownLeft' => 'UpLeft',
              'DownRight' => 'DownLeft',
            ),
            'FLIP_HORI' => array(
              'Up' => 'Up',
              'Down' => 'Down',
              'Left' => 'Right',
              'Right' => 'Left',
              'UpLeft' => 'UpRight',
              'UpRight' => 'UpLeft',
              'DownLeft' => 'DownRight',
              'DownRight' => 'DownLeft',
            ),
            'FLIP_VERT' => array(
              'Up' => 'Down',
              'Down' => 'Up',
              'Left' => 'Left',
              'Right' => 'Right',
              'UpLeft' => 'DownLeft',
              'UpRight' => 'DownRight',
              'DownLeft' => 'UpLeft',
              'DownRight' => 'UpRight',
            ),
          );
          $new_dirn = $conversions[$monitor->Orientation()][$dirn];
          $_REQUEST['control'] = preg_replace( "/_$dirn\$/", "_$new_dirn", $_REQUEST['control'] );
          $dirn = $new_dirn;
        }

        if ( $control->HasPanSpeed() && $xFactor ) {
          if ( $control->HasTurboPan() ) {
            if ( $xFactor >= $turbo ) {
              $panSpeed = $control->TurboPanSpeed();
            } else {
              $xFactor = $xFactor/$turbo;
              $panSpeed = intval(round($control->MinPanSpeed()+(($control->MaxPanSpeed()-$control->MinPanSpeed())*$xFactor)));
            }
          } else {
            $panSpeed = intval(round($control->MinPanSpeed()+(($control->MaxPanSpeed()-$control->MinPanSpeed())*$xFactor)));
          }
          $ctrlCommand .= ' --panspeed='.$panSpeed;
        }
        if ( $control->HasTiltSpeed() && $yFactor ) {
          if ( $control->HasTurboTilt() ) {
            if ( $yFactor >= $turbo ) {
              $tiltSpeed = $control->TurboTiltSpeed();
            } else {
              $yFactor = $yFactor/$turbo;
              $tiltSpeed = intval(round($control->MinTiltSpeed()+(($control->MaxTiltSpeed()-$control->MinTiltSpeed())*$yFactor)));
            }
          } else {
            $tiltSpeed = intval(round($control->MinTiltSpeed()+(($control->MaxTiltSpeed()-$control->MinTiltSpeed())*$yFactor)));
          }
          $ctrlCommand .= ' --tiltspeed='.$tiltSpeed;
        }
        switch( $mode ) {
        case 'Rel' :
        case 'Abs' :
          if ( preg_match( '/(Left|Right)$/', $dirn ) ) {
            $panStep = intval(round($control->MinPanStep()+(($control->MaxPanStep()-$control->MinPanStep())*$xFactor)));
            $ctrlCommand .= ' --panstep='.$panStep;
          }
          if ( preg_match( '/^(Up|Down)/', $dirn ) ) {
            $tiltStep = intval(round($control->MinTiltStep()+(($control->MaxTiltStep()-$control->MinTiltStep())*$yFactor)));
            $ctrlCommand .= ' --tiltstep='.$tiltStep;
          }
          break;
        case 'Con' :
          if ( $monitor->AutoStopTimeout() ) {
            $slowPanSpeed = intval(round($control->MinPanSpeed()+(($control->MaxPanSpeed()-$control->MinPanSpeed())*$slow)));
            $slowTiltSpeed = intval(round($control->MinTiltSpeed()+(($control->MaxTiltSpeed()-$control->MinTiltSpeed())*$slow)));
            if ( (!isset($panSpeed) || ($panSpeed < $slowPanSpeed)) && (!isset($tiltSpeed) || ($tiltSpeed < $slowTiltSpeed)) ) {
              $ctrlCommand .= ' --autostop';
            }
          }
          break;
        }
      }
    } else {
      Error('Invalid control parameter: ' . $_REQUEST['control'] );
    }
  } elseif ( isset($_REQUEST['x']) && isset($_REQUEST['y']) ) {
    if ( $_REQUEST['control'] == 'moveMap' ) {
      $x = deScale( $_REQUEST['x'], $_REQUEST['scale'] );
      $y = deScale( $_REQUEST['y'], $_REQUEST['scale'] );
      switch ( $monitor->Orientation() ) {
      case 'ROTATE_0' :
      case 'ROTATE_180' :
      case 'FLIP_HORI' :
      case 'FLIP_VERT' :
        $width = $monitor->Width();
        $height = $monitor->Height();
        break;
      case 'ROTATE_90' :
      case 'ROTATE_270' :
        $width = $monitor->Height();
        $height = $monitor->Width();
        break;
      }
      switch ( $monitor->Orientation() ) {
      case 'ROTATE_90' :
        $tempY = $y;
        $y = $height - $x;
        $x = $tempY;
        break;
      case 'ROTATE_180' :
        $x = $width - $x;
        $y = $height - $y;
        break;
      case 'ROTATE_270' :
        $tempX = $x;
        $x = $width - $y;
        $y = $tempX;
        break;
      case 'FLIP_HORI' :
        $x = $width - $x;
        break;
      case 'FLIP_VERT' :
        $y = $height - $y;
        break;
      }
      //$ctrlCommand .= " --xcoord=$x --ycoord=$y --width=$width --height=$height";
      $ctrlCommand .= " --xcoord=$x --ycoord=$y";
    } elseif ( $_REQUEST['control'] == 'movePseudoMap' ) {
      $x = deScale( $_REQUEST['x'], $_REQUEST['scale'] );
      $y = deScale( $_REQUEST['y'], $_REQUEST['scale'] );

      $halfWidth = $monitor->Width() / 2;
      $halfHeight = $monitor->Height() / 2;
      $xFactor = ($x - $halfWidth)/$halfWidth;
      $yFactor = ($y - $halfHeight)/$halfHeight;

      switch ( $monitor->Orientation() ) {
      case 'ROTATE_90' :
        $tempYFactor = $y;
        $yFactor = -$xFactor;
        $xFactor = $tempYFactor;
        break;
      case 'ROTATE_180' :
        $xFactor = -$xFactor;
        $yFactor = -$yFactor;
        break;
      case 'ROTATE_270' :
        $tempXFactor = $x;
        $xFactor = -$yFactor;
        $yFactor = $tempXFactor;
        break;
      case 'FLIP_HORI' :
        $xFactor = -$xFactor;
        break;
      case 'FLIP_VERT' :
        $yFactor = -$yFactor;
        break;
      }

      $turbo = 0.9; // Threshold for turbo speed
      $blind = 0.1; // Threshold for blind spot

      $panControl = '';
      $tiltControl = '';
      if ( $xFactor > $blind ) {
        $panControl = 'Right';
      } elseif ( $xFactor < -$blind ) {
        $panControl = 'Left';
      }
      if ( $yFactor > $blind ) {
        $tiltControl = 'Down';
      } elseif ( $yFactor < -$blind ) {
        $tiltControl = 'Up';
      }

      $dirn = $tiltControl.$panControl;
      if ( !$dirn ) {
        // No command, probably in blind spot in middle
        $_REQUEST['control'] = 'null';
        return( false );
      } else {
        $_REQUEST['control'] = 'moveRel'.$dirn;
        $xFactor = abs($xFactor);
        $yFactor = abs($yFactor);

        if ( $control->HasPanSpeed() && $xFactor ) {
          if ( $control->HasTurboPan() ) {
            if ( $xFactor >= $turbo ) {
              $panSpeed = $control->TurboPanSpeed();
            } else {
              $xFactor = $xFactor/$turbo;
              $panSpeed = intval(round($control->MinPanSpeed()+(($control->MaxPanSpeed()-$control->MinPanSpeed())*$xFactor)));
            }
          } else {
            $panSpeed = intval(round($control->MinPanSpeed()+(($control->MaxPanSpeed()-$control->MinPanSpeed())*$xFactor)));
          }
        }
        if ( $control->HasTiltSpeed() && $yFactor ) {
          if ( $control->HasTurboTilt() ) {
            if ( $yFactor >= $turbo ) {
              $tiltSpeed = $control->TurboTiltSpeed();
            } else {
              $yFactor = $yFactor/$turbo;
              $tiltSpeed = intval(round($control->MinTiltSpeed()+(($control->MaxTiltSpeed()-$control->MinTiltSpeed())*$yFactor)));
            }
          } else {
            $tiltSpeed = intval(round($control->MinTiltSpeed()+(($control->MaxTiltSpeed()-$control->MinTiltSpeed())*$yFactor)));
          }
        }
        if ( preg_match( '/(Left|Right)$/', $dirn ) ) {
          $panStep = intval(round($control->MinPanStep()+(($control->MaxPanStep()-$control->MinPanStep())*$xFactor)));
          $ctrlCommand .= ' --panstep='.$panStep.' --panspeed='.$panSpeed;
        }
        if ( preg_match( '/^(Up|Down)/', $dirn ) ) {
          $tiltStep = intval(round($control->MinTiltStep()+(($control->MaxTiltStep()-$control->MinTiltStep())*$yFactor)));
          $ctrlCommand .= ' --tiltstep='.$tiltStep.' --tiltspeed='.$tiltSpeed;
        }
      }
    } elseif ( $_REQUEST['control'] == 'moveConMap' ) {
      $x = deScale( $_REQUEST['x'], $_REQUEST['scale'] );
      $y = deScale( $_REQUEST['y'], $_REQUEST['scale'] );

      $halfWidth = $monitor->Width() / 2;
      $halfHeight = $monitor->Height() / 2;
      $xFactor = ($x - $halfWidth)/$halfWidth;
      $yFactor = ($y - $halfHeight)/$halfHeight;

      switch ( $monitor->Orientation() ) {
      case 'ROTATE_90' :
        $tempYFactor = $y;
        $yFactor = -$xFactor;
        $xFactor = $tempYFactor;
        break;
      case 'ROTATE_180' :
        $xFactor = -$xFactor;
        $yFactor = -$yFactor;
        break;
      case 'ROTATE_270' :
        $tempXFactor = $x;
        $xFactor = -$yFactor;
        $yFactor = $tempXFactor;
        break;
      case 'FLIP_HORI' :
        $xFactor = -$xFactor;
        break;
      case 'FLIP_VERT' :
        $yFactor = -$yFactor;
        break;
      }

      $slow = 0.9; // Threshold for slow speed/timeouts
      $turbo = 0.9; // Threshold for turbo speed
      $blind = 0.1; // Threshold for blind spot

      $panControl = '';
      $tiltControl = '';
      if ( $xFactor > $blind ) {
        $panControl = 'Right';
      } elseif ( $xFactor < -$blind ) {
        $panControl = 'Left';
      }
      if ( $yFactor > $blind ) {
        $tiltControl = 'Down';
      } elseif ( $yFactor < -$blind ) {
        $tiltControl = 'Up';
      }

      $dirn = $tiltControl.$panControl;
      if ( !$dirn ) {
        // No command, probably in blind spot in middle
        $_REQUEST['control'] = 'moveStop';
      } else {
        $_REQUEST['control'] = 'moveCon'.$dirn;
        $xFactor = abs($xFactor);
        $yFactor = abs($yFactor);

        if ( $control->HasPanSpeed() && $xFactor ) {
          if ( $control->HasTurboPan() ) {
            if ( $xFactor >= $turbo ) {
              $panSpeed = $control->TurboPanSpeed();
            } else {
              $xFactor = $xFactor/$turbo;
              $panSpeed = intval(round($control->MinPanSpeed()+(($control->MaxPanSpeed()-$control->MinPanSpeed())*$xFactor)));
            }
          } else {
            $panSpeed = intval(round($control->MinPanSpeed()+(($control->MaxPanSpeed()-$control->MinPanSpeed())*$xFactor)));
          }
        }
        if ( $control->HasTiltSpeed() && $yFactor ) {
          if ( $control->HasTurboTilt() ) {
            if ( $yFactor >= $turbo ) {
              $tiltSpeed = $control->TurboTiltSpeed();
            } else {
              $yFactor = $yFactor/$turbo;
              $tiltSpeed = intval(round($control->MinTiltSpeed()+(($control->MaxTiltSpeed()-$control->MinTiltSpeed())*$yFactor)));
            }
          } else {
            $tiltSpeed = intval(round($control->MinTiltSpeed()+(($control->MaxTiltSpeed()-$control->MinTiltSpeed())*$yFactor)));
          }
        }
        if ( preg_match( '/(Left|Right)$/', $dirn ) ) {
          $ctrlCommand .= ' --panspeed='.$panSpeed;
        }
        if ( preg_match( '/^(Up|Down)/', $dirn ) ) {
          $ctrlCommand .= ' --tiltspeed='.$tiltSpeed;
        }
        if ( $monitor->AutoStopTimeout() ) {
          $slowPanSpeed = intval(round($control->MinPanSpeed()+(($control->MaxPanSpeed()-$control->MinPanSpeed())*$slow)));
          $slowTiltSpeed = intval(round($control->MinTiltSpeed()+(($control->MaxTiltSpeed()-$control->MinTiltSpeed())*$slow)));
          if ( (!isset($panSpeed) || ($panSpeed < $slowPanSpeed)) && (!isset($tiltSpeed) || ($tiltSpeed < $slowTiltSpeed)) ) {
            $ctrlCommand .= ' --autostop';
          }
        }
      }
    } else {
      $slow = 0.9; // Threshold for slow speed/timeouts
      $turbo = 0.9; // Threshold for turbo speed
      $long_y = 48;
      $short_x = 32;
      $short_y = 32;

      if ( preg_match( '/^([a-z]+)([A-Z][a-z]+)([A-Z][a-z]+)$/', $_REQUEST['control'], $matches ) ) {
        $command = $matches[1];
        $mode = $matches[2];
        $dirn = $matches[3];

        switch( $command ) {
        case 'focus' :
          switch( $dirn ) {
          case 'Near' :
            $factor = ($long_y-($y+1))/$long_y;
            break;
          case 'Far' :
            $factor = ($y+1)/$long_y;
            break;
          }
          if ( $control->HasFocusSpeed() ) {
            $speed = intval(round($control->MinFocusSpeed()+(($control->MaxFocusSpeed()-$control->MinFocusSpeed())*$factor)));
            $ctrlCommand .= ' --speed='.$speed;
          }
          switch( $mode ) {
          case 'Abs' :
          case 'Rel' :
            $step = intval(round($control->MinFocusStep()+(($control->MaxFocusStep()-$control->MinFocusStep())*$factor)));
            $ctrlCommand .= ' --step='.$step;
            break;
          case 'Con' :
            if ( $monitor->AutoStopTimeout() ) {
              $slowSpeed = intval(round($control->MinFocusSpeed()+(($control->MaxFocusSpeed()-$control->MinFocusSpeed())*$slow)));
              if ( $speed < $slowSpeed ) {
                $ctrlCommand .= ' --autostop';
              }
            }
            break;
          }
          break;
        case 'zoom' :
          switch( $dirn ) {
          case 'Tele' :
            $factor = ($long_y-($y+1))/$long_y;
            break;
          case 'Wide' :
            $factor = ($y+1)/$long_y;
            break;
          }
          if ( $control->HasZoomSpeed() ) {
            $speed = intval(round($control->MinZoomSpeed()+(($control->MaxZoomSpeed()-$control->MinZoomSpeed())*$factor)));
            $ctrlCommand .= ' --speed='.$speed;
          }
          switch( $mode ) {
          case 'Abs' :
          case 'Rel' :
            $step = intval(round($control->MinZoomStep()+(($control->MaxZoomStep()-$control->MinZoomStep())*$factor)));
            $ctrlCommand .= ' --step='.$step;
            break;
          case 'Con' :
            if ( $monitor->AutoStopTimeout() ) {
              $slowSpeed = intval(round($control->MinZoomSpeed()+(($control->MaxZoomSpeed()-$control->MinZoomSpeed())*$slow)));
              if ( $speed < $slowSpeed ) {
                $ctrlCommand .= ' --autostop';
              }
            }
            break;
          }
          break;
        case 'iris' :
          switch( $dirn ) {
          case 'Open' :
            $factor = ($long_y-($y+1))/$long_y;
            break;
          case 'Close' :
            $factor = ($y+1)/$long_y;
            break;
          }
          if ( $control->HasIrisSpeed() ) {
            $speed = intval(round($control->MinIrisSpeed()+(($control->MaxIrisSpeed()-$control->MinIrisSpeed())*$factor)));
            $ctrlCommand .= ' --speed='.$speed;
          }
          switch( $mode ) {
          case 'Abs' :
          case 'Rel' :
            $step = intval(round($control->MinIrisStep()+(($control->MaxIrisStep()-$control->MinIrisStep())*$factor)));
            $ctrlCommand .= ' --step='.$step;
            break;
          }
          break;
        case 'white' :
          switch( $dirn ) {
          case 'In' :
            $factor = ($long_y-($y+1))/$long_y;
            break;
          case 'Out' :
            $factor = ($y+1)/$long_y;
            break;
          }
          if ( $control->HasWhiteSpeed() ) {
            $speed = intval(round($control->MinWhiteSpeed()+(($control->MaxWhiteSpeed()-$control->MinWhiteSpeed())*$factor)));
            $ctrlCommand .= ' --speed='.$speed;
          }
          switch( $mode ) {
          case 'Abs' :
          case 'Rel' :
            $step = intval(round($control->MinWhiteStep()+(($control->MaxWhiteStep()-$control->MinWhiteStep())*$factor)));
            $ctrlCommand .= ' --step='.$step;
            break;
          }
          break;
        case 'gain' :
          switch( $dirn ) {
          case 'Up' :
            $factor = ($long_y-($y+1))/$long_y;
            break;
          case 'Down' :
            $factor = ($y+1)/$long_y;
            break;
          }
          if ( $control->HasGainSpeed() ) {
            $speed = intval(round($control->MinGainSpeed()+(($control->MaxGainSpeed()-$control->MinGainSpeed())*$factor)));
            $ctrlCommand .= ' --speed='.$speed;
          }
          switch( $mode ) {
          case 'Abs' :
          case 'Rel' :
            $step = intval(round($control->MinGainStep()+(($control->MaxGainStep()-$control->MinGainStep())*$factor)));
            $ctrlCommand .= ' --step='.$step;
            break;
          }
          break;
        case 'move' :
          $xFactor = 0;
          $yFactor = 0;

          if ( preg_match( '/^Up/', $dirn ) ) {
            $yFactor = ($short_y-($y+1))/$short_y;
          } elseif ( preg_match( '/^Down/', $dirn ) ) {
            $yFactor = ($y+1)/$short_y;
          }
          if ( preg_match( '/Left$/', $dirn ) ) {
            $xFactor = ($short_x-($x+1))/$short_x;
          } elseif ( preg_match( '/Right$/', $dirn ) ) {
            $xFactor = ($x+1)/$short_x;
          }

          if ( $monitor->Orientation() != 'ROTATE_0' ) {
            $conversions = array(
              'ROTATE_90' => array(
                'Up' => 'Left',
                'Down' => 'Right',
                'Left' => 'Down',
                'Right' => 'Up',
                'UpLeft' => 'DownLeft',
                'UpRight' => 'UpLeft',
                'DownLeft' => 'DownRight',
                'DownRight' => 'UpRight',
              ),
              'ROTATE_180' => array(
                'Up' => 'Down',
                'Down' => 'Up',
                'Left' => 'Right',
                'Right' => 'Left',
                'UpLeft' => 'DownRight',
                'UpRight' => 'DownLeft',
                'DownLeft' => 'UpRight',
                'DownRight' => 'UpLeft',
              ),
              'ROTATE_270' => array(
                'Up' => 'Right',
                'Down' => 'Left',
                'Left' => 'Up',
                'Right' => 'Down',
                'UpLeft' => 'UpRight',
                'UpRight' => 'DownRight',
                'DownLeft' => 'UpLeft',
                'DownRight' => 'DownLeft',
              ),
              'FLIP_HORI' => array(
                'Up' => 'Up',
                'Down' => 'Down',
                'Left' => 'Right',
                'Right' => 'Left',
                'UpLeft' => 'UpRight',
                'UpRight' => 'UpLeft',
                'DownLeft' => 'DownRight',
                'DownRight' => 'DownLeft',
              ),
              'FLIP_VERT' => array(
                'Up' => 'Down',
                'Down' => 'Up',
                'Left' => 'Left',
                'Right' => 'Right',
                'UpLeft' => 'DownLeft',
                'UpRight' => 'DownRight',
                'DownLeft' => 'UpLeft',
                'DownRight' => 'UpRight',
              ),
            );
            $new_dirn = $conversions[$monitor->Orientation()][$dirn];
            $_REQUEST['control'] = preg_replace( "/_$dirn\$/", "_$new_dirn", $_REQUEST['control'] );
            $dirn = $new_dirn;
          }

          if ( $control->HasPanSpeed() && $xFactor ) {
            if ( $control->HasTurboPan() ) {
              if ( $xFactor >= $turbo ) {
                $panSpeed = $control->TurboPanSpeed();
              } else {
                $xFactor = $xFactor/$turbo;
                $panSpeed = intval(round($control->MinPanSpeed()+(($control->MaxPanSpeed()-$control->MinPanSpeed())*$xFactor)));
              }
            } else {
              $panSpeed = intval(round($control->MinPanSpeed()+(($control->MaxPanSpeed()-$control->MinPanSpeed())*$xFactor)));
            }
            $ctrlCommand .= ' --panspeed='.$panSpeed;
          }
          if ( $control->HasTiltSpeed() && $yFactor ) {
            if ( $control->HasTurboTilt() ) {
              if ( $yFactor >= $turbo ) {
                $tiltSpeed = $control->TurboTiltSpeed();
              } else {
                $yFactor = $yFactor/$turbo;
                $tiltSpeed = intval(round($control->MinTiltSpeed()+(($control->MaxTiltSpeed()-$control->MinTiltSpeed())*$yFactor)));
              }
            } else {
              $tiltSpeed = intval(round($control->MinTiltSpeed()+(($control->MaxTiltSpeed()-$control->MinTiltSpeed())*$yFactor)));
            }
            $ctrlCommand .= ' --tiltspeed='.$tiltSpeed;
          }
          switch( $mode ) {
          case 'Rel' :
          case 'Abs' :
            if ( preg_match( '/(Left|Right)$/', $dirn ) ) {
              $panStep = intval(round($control->MinPanStep()+(($control->MaxPanStep()-$control->MinPanStep())*$xFactor)));
              $ctrlCommand .= ' --panstep='.$panStep;
            }
            if ( preg_match( '/^(Up|Down)/', $dirn ) ) {
              $tiltStep = intval(round($control->MinTiltStep()+(($control->MaxTiltStep()-$control->MinTiltStep())*$yFactor)));
              $ctrlCommand .= ' --tiltstep='.$tiltStep;
            }
            break;
          case 'Con' :
            if ( $monitor->AutoStopTimeout() ) {
              $slowPanSpeed = intval(round($control->MinPanSpeed()+(($control->MaxPanSpeed()-$control->MinPanSpeed())*$slow)));
              $slowTiltSpeed = intval(round($control->MinTiltSpeed()+(($control->MaxTiltSpeed()-$control->MinTiltSpeed())*$slow)));
              if ( (!isset($panSpeed) || ($panSpeed < $slowPanSpeed)) && (!isset($tiltSpeed) || ($tiltSpeed < $slowTiltSpeed)) ) {
                $ctrlCommand .= ' --autostop';
              }
            }
            break;
          }
        }
      }
    }
  } else {
    if ( preg_match( '/^presetGoto(\d+)$/', $_REQUEST['control'], $matches ) ) {
      $_REQUEST['control'] = 'presetGoto';
      $ctrlCommand .= ' --preset='.$matches[1];
    } elseif ( $_REQUEST['control'] == 'presetGoto' && !empty($_REQUEST['preset']) ) {
      $ctrlCommand .= ' --preset='.$_REQUEST['preset'];
    } elseif ( $_REQUEST['control'] == 'presetSet' ) {
      if ( canEdit( 'Control' ) ) {
        $preset = validInt($_REQUEST['preset']);
        $newLabel = validJsStr($_REQUEST['newLabel']);
        $row = dbFetchOne(
          'SELECT * FROM `ControlPresets` WHERE `MonitorId` = ? AND `Preset`=?',
          NULL, array($monitor->Id(), $preset));
        if ( $newLabel != $row['Label'] ) {
          if ( $newLabel ) {
            dbQuery('REPLACE INTO `ControlPresets` (`MonitorId`, `Preset`, `Label`) VALUES ( ?, ?, ? )',
              array($monitor->Id(), $preset, $newLabel));
          } else {
            dbQuery('DELETE FROM `ControlPresets` WHERE `MonitorId`=? AND `Preset`=?',
              array($monitor->Id(), $preset));
          }
        }
        $ctrlCommand .= ' --preset='.$preset;
      }
      $ctrlCommand .= ' --preset='.$preset;
    } elseif ( $_REQUEST['control'] == 'moveMap' ) {
      $ctrlCommand .= " --xcoord=$x --ycoord=$y";
    }
  }
  $ctrlCommand .= ' --command='.$_REQUEST['control'];
  return $ctrlCommand;
}

