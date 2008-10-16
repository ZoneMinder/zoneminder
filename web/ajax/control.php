<?php
// Monitor control actions, require a monitor id and control view permissions for that monitor
if ( empty($_REQUEST['id']) )
    ajaxError( "No monitor id supplied" );

if ( canView( 'Control', $_REQUEST['id'] ) )
{
    $monitor = dbFetchOne( "select C.*,M.* from Monitors as M inner join Controls as C on (M.ControlId = C.Id ) where M.Id = '".dbEscape($_REQUEST['id'])."'" );

    $ctrlCommand = ZM_PATH_BIN."/zmcontrol.pl";

    if ( isset($_REQUEST['xge']) || isset($_REQUEST['yge']) )
    {
        $slow = 0.9; // Threshold for slow speed/timeouts
        $turbo = 0.9; // Threshold for turbo speed

        if ( preg_match( '/^([a-z]+)([A-Z][a-z]+)([A-Z][a-z]+)+$/', $_REQUEST['control'], $matches ) )
        {
            $command = $matches[1];
            $mode = $matches[2];
            $dirn = $matches[3];

            switch( $command )
            {
                case 'focus' :
                {
                    $factor = $_REQUEST['yge']/100;
                    if ( $monitor['HasFocusSpeed'] )
                    {
                        $speed = intval(round($monitor['MinFocusSpeed']+(($monitor['MaxFocusSpeed']-$monitor['MinFocusSpeed'])*$factor)));
                        $ctrlCommand .= " --speed=".$speed;
                    }
                    switch( $mode )
                    {
                        case 'Abs' :
                        case 'Rel' :
                        {
                            $step = intval(round($monitor['MinFocusStep']+(($monitor['MaxFocusStep']-$monitor['MinFocusStep'])*$factor)));
                            $ctrlCommand .= " --step=".$step;
                            break;
                        }
                        case 'Con' :
                        {
                            if ( $monitor['AutoStopTimeout'] )
                            {
                                $slowSpeed = intval(round($monitor['MinFocusSpeed']+(($monitor['MaxFocusSpeed']-$monitor['MinFocusSpeed'])*$slow)));
                                if ( $speed < $slowSpeed )
                                {
                                    $ctrlCommand .= " --autostop";
                                }
                            }
                            break;
                        }
                    }
                    break;
                }
                case 'zoom' :
                {
                    $factor = $_REQUEST['yge']/100;
                    if ( $monitor['HasZoomSpeed'] )
                    {
                        $speed = intval(round($monitor['MinZoomSpeed']+(($monitor['MaxZoomSpeed']-$monitor['MinZoomSpeed'])*$factor)));
                        $ctrlCommand .= " --speed=".$speed;
                    }
                    switch( $mode )
                    {
                        case 'Abs' :
                        case 'Rel' :
                        {
                            $step = intval(round($monitor['MinZoomStep']+(($monitor['MaxZoomStep']-$monitor['MinZoomStep'])*$factor)));
                            $ctrlCommand .= " --step=".$step;
                            break;
                        }
                        case 'Con' :
                        {
                            if ( $monitor['AutoStopTimeout'] )
                            {
                                $slowSpeed = intval(round($monitor['MinZoomSpeed']+(($monitor['MaxZoomSpeed']-$monitor['MinZoomSpeed'])*$slow)));
                                if ( $speed < $slowSpeed )
                                {
                                    $ctrlCommand .= " --autostop";
                                }
                            }
                            break;
                        }
                    }
                    break;
                }
                case 'iris' :
                {
                    $factor = $_REQUEST['yge']/100;
                    if ( $monitor['HasIrisSpeed'] )
                    {
                        $speed = intval(round($monitor['MinIrisSpeed']+(($monitor['MaxIrisSpeed']-$monitor['MinIrisSpeed'])*$factor)));
                        $ctrlCommand .= " --speed=".$speed;
                    }
                    switch( $mode )
                    {
                        case 'Abs' :
                        case 'Rel' :
                        {
                            $step = intval(round($monitor['MinIrisStep']+(($monitor['MaxIrisStep']-$monitor['MinIrisStep'])*$factor)));
                            $ctrlCommand .= " --step=".$step;
                            break;
                        }
                    }
                    break;
                }
                case 'white' :
                {
                    $factor = $_REQUEST['yge']/100;
                    if ( $monitor['HasWhiteSpeed'] )
                    {
                        $speed = intval(round($monitor['MinWhiteSpeed']+(($monitor['MaxWhiteSpeed']-$monitor['MinWhiteSpeed'])*$factor)));
                        $ctrlCommand .= " --speed=".$speed;
                    }
                    switch( $mode )
                    {
                        case 'Abs' :
                        case 'Rel' :
                        {
                            $step = intval(round($monitor['MinWhiteStep']+(($monitor['MaxWhiteStep']-$monitor['MinWhiteStep'])*$factor)));
                            $ctrlCommand .= " --step=".$step;
                            break;
                        }
                    }
                    break;
                }
                case 'gain' :
                {
                    $factor = $_REQUEST['yge']/100;
                    if ( $monitor['HasGainSpeed'] )
                    {
                        $speed = intval(round($monitor['MinGainSpeed']+(($monitor['MaxGainSpeed']-$monitor['MinGainSpeed'])*$factor)));
                        $ctrlCommand .= " --speed=".$speed;
                    }
                    switch( $mode )
                    {
                        case 'Abs' :
                        case 'Rel' :
                        {
                            $step = intval(round($monitor['MinGainStep']+(($monitor['MaxGainStep']-$monitor['MinGainStep'])*$factor)));
                            $ctrlCommand .= " --step=".$step;
                            break;
                        }
                    }
                    break;
                }
                case 'move' :
                {
                    $xFactor = empty($_REQUEST['xge'])?0:$_REQUEST['xge']/100;
                    $yFactor = empty($_REQUEST['yge'])?0:$_REQUEST['yge']/100;

                    if ( $monitor['Orientation'] != '0' )
                    {
                        $conversions = array(
                            '90' => array(
                                'Up' => 'Left',
                                'Down' => 'Right',
                                'Left' => 'Down',
                                'Right' => 'Up',
                                'UpLeft' => 'DownLeft',
                                'UpRight' => 'UpLeft',
                                'DownLeft' => 'DownRight',
                                'DownRight' => 'UpRight',
                            ),
                            '180' => array(
                                'Up' => 'Down',
                                'Down' => 'Up',
                                'Left' => 'Right',
                                'Right' => 'Left',
                                'UpLeft' => 'DownRight',
                                'UpRight' => 'DownLeft',
                                'DownLeft' => 'UpRight',
                                'DownRight' => 'UpLeft',
                            ),
                            '270' => array(
                                'Up' => 'Right',
                                'Down' => 'Left',
                                'Left' => 'Up',
                                'Right' => 'Down',
                                'UpLeft' => 'UpRight',
                                'UpRight' => 'DownRight',
                                'DownLeft' => 'UpLeft',
                                'DownRight' => 'DownLeft',
                            ),
                            'hori' => array(
                                'Up' => 'Up',
                                'Down' => 'Down',
                                'Left' => 'Right',
                                'Right' => 'Left',
                                'UpLeft' => 'UpRight',
                                'UpRight' => 'UpLeft',
                                'DownLeft' => 'DownRight',
                                'DownRight' => 'DownLeft',
                            ),
                            'vert' => array(
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
                        $new_dirn = $conversions[$monitor['Orientation']][$dirn];
                        $_REQUEST['control'] = preg_replace( "/_$dirn\$/", "_$new_dirn", $_REQUEST['control'] );
                        $dirn = $new_dirn;
                    }

                    if ( $monitor['HasPanSpeed'] && $xFactor )
                    {
                        if ( $monitor['HasTurboPan'] )
                        {
                            if ( $xFactor >= $turbo )
                            {
                                $panSpeed = $monitor['TurboPanSpeed'];
                            }
                            else
                            {
                                $xFactor = $xFactor/$turbo;
                                $panSpeed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$xFactor)));
                            }
                        }
                        else
                        {
                            $panSpeed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$xFactor)));
                        }
                        $ctrlCommand .= " --panspeed=".$panSpeed;
                    }
                    if ( $monitor['HasTiltSpeed'] && $yFactor )
                    {
                        if ( $monitor['HasTurboTilt'] )
                        {
                            if ( $yFactor >= $turbo )
                            {
                                $tiltSpeed = $monitor['TurboTiltSpeed'];
                            }
                            else
                            {
                                $yFactor = $yFactor/$turbo;
                                $tiltSpeed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$yFactor)));
                            }
                        }
                        else
                        {
                            $tiltSpeed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$yFactor)));
                        }
                        $ctrlCommand .= " --tiltspeed=".$tiltSpeed;
                    }
                    switch( $mode )
                    {
                        case 'Rel' :
                        case 'Abs' :
                        {
                            if ( preg_match( '/(Left|Right)$/', $dirn ) )
                            {
                                $panStep = intval(round($monitor['MinPanStep']+(($monitor['MaxPanStep']-$monitor['MinPanStep'])*$xFactor)));
                                $ctrlCommand .= " --panstep=".$panStep;
                            }
                            if ( preg_match( '/^(Up|Down)/', $dirn ) )
                            {
                                $tiltStep = intval(round($monitor['MinTiltStep']+(($monitor['MaxTiltStep']-$monitor['MinTiltStep'])*$yFactor)));
                                $ctrlCommand .= " --tiltstep=".$tiltStep;
                            }
                            break;
                        }
                        case 'Con' :
                        {
                            if ( $monitor['AutoStopTimeout'] )
                            {
                                $slowPanSpeed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$slow)));
                                $slowTiltSpeed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$slow)));
                                if ( (!isset($panSpeed) || ($panSpeed < $slowPanSpeed)) && (!isset($tiltSpeed) || ($tiltSpeed < $slowTiltSpeed)) )
                                {
                                    $ctrlCommand .= " --autostop";
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }
    }
    elseif ( isset($_REQUEST['x']) && isset($_REQUEST['y']) )
    {
        if ( $_REQUEST['control'] == "moveMap" )
        {
            $x = deScale( $_REQUEST['x'], $_REQUEST['scale'] );
            $y = deScale( $_REQUEST['y'], $_REQUEST['scale'] );
            switch ( $monitor['Orientation'] )
            {
                case '0' :
                case '180' :
                case 'hori' :
                case 'vert' :
                    $width = $monitor['Width'];
                    $height = $monitor['Height'];
                    break;
                case '90' :
                case '270' :
                    $width = $monitor['Height'];
                    $height = $monitor['Width'];
                    break;
            }
            switch ( $monitor['Orientation'] )
            {
                case '90' :
                    $tempY = $y;
                    $y = $height - $x;
                    $x = $tempY;
                    break;
                case '180' :
                    $x = $width - $x;
                    $y = $height - $y;
                    break;
                case '270' :
                    $tempX = $x;
                    $x = $width - $y;
                    $y = $tempX;
                    break;
                case 'hori' :
                    $x = $width - $x;
                    break;
                case 'vert' :
                    $y = $height - $y;
                    break;
            }
            //$ctrlCommand .= " --xcoord=$x --ycoord=$y --width=$width --height=$height";
            $ctrlCommand .= " --xcoord=$x --ycoord=$y";
        }
        elseif ( $_REQUEST['control'] == "movePseudoMap" )
        {
            $x = deScale( $_REQUEST['x'], $_REQUEST['scale'] );
            $y = deScale( $_REQUEST['y'], $_REQUEST['scale'] );
            
            $halfWidth = $monitor['Width'] / 2;
            $halfHeight = $monitor['Height'] / 2;
            $xFactor = ($x - $halfWidth)/$halfWidth;
            $yFactor = ($y - $halfHeight)/$halfHeight;

            switch ( $monitor['Orientation'] )
            {
                case '90' :
                    $tempYFactor = $y;
                    $yFactor = -$xFactor;
                    $xFactor = $tempYFactor;
                    break;
                case '180' :
                    $xFactor = -$xFactor;
                    $yFactor = -$yFactor;
                    break;
                case '270' :
                    $tempXFactor = $x;
                    $xFactor = -$yFactor;
                    $yFactor = $tempXFactor;
                    break;
                case 'hori' :
                    $xFactor = -$xFactor;
                    break;
                case 'vert' :
                    $yFactor = -$yFactor;
                    break;
            }

            $turbo = 0.9; // Threshold for turbo speed
            $blind = 0.1; // Threshold for blind spot

            $panControl = '';
            $tiltControl = '';
            if ( $xFactor > $blind )
            {
                $panControl = 'Right';
            }
            elseif ( $xFactor < -$blind )
            {
                $panControl = 'Left';
            }
            if ( $yFactor > $blind )
            {
                $tiltControl = 'Down';
            }
            elseif ( $yFactor < -$blind )
            {
                $tiltControl = 'Up';
            }

            $dirn = $tiltControl.$panControl;
            if ( !$dirn )
            {
                // No command, probably in blind spot in middle
                $_REQUEST['control'] = 'null';
            }
            else
            {
                $_REQUEST['control'] = 'moveRel'.$dirn;
                $xFactor = abs($xFactor);
                $yFactor = abs($yFactor);

                if ( $monitor['HasPanSpeed'] && $xFactor )
                {
                    if ( $monitor['HasTurboPan'] )
                    {
                        if ( $xFactor >= $turbo )
                        {
                            $panSpeed = $monitor['TurboPanSpeed'];
                        }
                        else
                        {
                            $xFactor = $xFactor/$turbo;
                            $panSpeed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$xFactor)));
                        }
                    }
                    else
                    {
                        $panSpeed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$xFactor)));
                    }
                }
                if ( $monitor['HasTiltSpeed'] && $yFactor )
                {
                    if ( $monitor['HasTurboTilt'] )
                    {
                        if ( $yFactor >= $turbo )
                        {
                            $tiltSpeed = $monitor['TurboTiltSpeed'];
                        }
                        else
                        {
                            $yFactor = $yFactor/$turbo;
                            $tiltSpeed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$yFactor)));
                        }
                    }
                    else
                    {
                        $tiltSpeed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$yFactor)));
                    }
                }
                if ( preg_match( '/(Left|Right)$/', $dirn ) )
                {
                    $panStep = intval(round($monitor['MinPanStep']+(($monitor['MaxPanStep']-$monitor['MinPanStep'])*$xFactor)));
                    $ctrlCommand .= " --panstep=".$panStep." --panspeed=".$panSpeed;
                }
                if ( preg_match( '/^(Up|Down)/', $dirn ) )
                {
                    $tiltStep = intval(round($monitor['MinTiltStep']+(($monitor['MaxTiltStep']-$monitor['MinTiltStep'])*$yFactor)));
                    $ctrlCommand .= " --tiltstep=".$tiltStep." --tiltspeed=".$tiltSpeed;
                }
            }
        }
        elseif ( $_REQUEST['control'] == "moveConMap" )
        {
            $x = deScale( $_REQUEST['x'], $_REQUEST['scale'] );
            $y = deScale( $_REQUEST['y'], $_REQUEST['scale'] );
            
            $halfWidth = $monitor['Width'] / 2;
            $halfHeight = $monitor['Height'] / 2;
            $xFactor = ($x - $halfWidth)/$halfWidth;
            $yFactor = ($y - $halfHeight)/$halfHeight;

            switch ( $monitor['Orientation'] )
            {
                case '90' :
                    $tempYFactor = $y;
                    $yFactor = -$xFactor;
                    $xFactor = $tempYFactor;
                    break;
                case '180' :
                    $xFactor = -$xFactor;
                    $yFactor = -$yFactor;
                    break;
                case '270' :
                    $tempXFactor = $x;
                    $xFactor = -$yFactor;
                    $yFactor = $tempXFactor;
                    break;
                case 'hori' :
                    $xFactor = -$xFactor;
                    break;
                case 'vert' :
                    $yFactor = -$yFactor;
                    break;
            }

            $slow = 0.9; // Threshold for slow speed/timeouts
            $turbo = 0.9; // Threshold for turbo speed
            $blind = 0.1; // Threshold for blind spot

            $panControl = '';
            $tiltControl = '';
            if ( $xFactor > $blind )
            {
                $panControl = 'Right';
            }
            elseif ( $xFactor < -$blind )
            {
                $panControl = 'Left';
            }
            if ( $yFactor > $blind )
            {
                $tiltControl = 'Down';
            }
            elseif ( $yFactor < -$blind )
            {
                $tiltControl = 'Up';
            }

            $dirn = $tiltControl.$panControl;
            if ( !$dirn )
            {
                // No command, probably in blind spot in middle
                $_REQUEST['control'] = 'moveStop';
            }
            else
            {
                $_REQUEST['control'] = 'moveCon'.$dirn;
                $xFactor = abs($xFactor);
                $yFactor = abs($yFactor);

                if ( $monitor['HasPanSpeed'] && $xFactor )
                {
                    if ( $monitor['HasTurboPan'] )
                    {
                        if ( $xFactor >= $turbo )
                        {
                            $panSpeed = $monitor['TurboPanSpeed'];
                        }
                        else
                        {
                            $xFactor = $xFactor/$turbo;
                            $panSpeed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$xFactor)));
                        }
                    }
                    else
                    {
                        $panSpeed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$xFactor)));
                    }
                }
                if ( $monitor['HasTiltSpeed'] && $yFactor )
                {
                    if ( $monitor['HasTurboTilt'] )
                    {
                        if ( $yFactor >= $turbo )
                        {
                            $tiltSpeed = $monitor['TurboTiltSpeed'];
                        }
                        else
                        {
                            $yFactor = $yFactor/$turbo;
                            $tiltSpeed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$yFactor)));
                        }
                    }
                    else
                    {
                        $tiltSpeed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$yFactor)));
                    }
                }
                if ( preg_match( '/(Left|Right)$/', $dirn ) )
                {
                    $ctrlCommand .= " --panspeed=".$panSpeed;
                }
                if ( preg_match( '/^(Up|Down)/', $dirn ) )
                {
                    $ctrlCommand .= " --tiltspeed=".$tiltSpeed;
                }
                if ( $monitor['AutoStopTimeout'] )
                {
                    $slowPanSpeed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$slow)));
                    $slowTiltSpeed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$slow)));
                    if ( (!isset($panSpeed) || ($panSpeed < $slowPanSpeed)) && (!isset($tiltSpeed) || ($tiltSpeed < $slowTiltSpeed)) )
                    {
                        $ctrlCommand .= " --autostop";
                    }
                }
            }
        }
        else
        {
            $slow = 0.9; // Threshold for slow speed/timeouts
            $turbo = 0.9; // Threshold for turbo speed
            $long_y = 48;
            $short_x = 32;
            $short_y = 32;

            if ( preg_match( '/^([a-z]+)([A-Z][a-z]+)([A-Z][a-z]+)$/', $_REQUEST['control'], $matches ) )
            {
                $command = $matches[1];
                $mode = $matches[2];
                $dirn = $matches[3];

                switch( $command )
                {
                    case 'focus' :
                    {
                        switch( $dirn )
                        {
                            case 'Near' :
                            {
                                $factor = ($long_y-($y+1))/$long_y;
                                break;
                            }
                            case 'Far' :
                            {
                                $factor = ($y+1)/$long_y;
                                break;
                            }
                        }
                        if ( $monitor['HasFocusSpeed'] )
                        {
                            $speed = intval(round($monitor['MinFocusSpeed']+(($monitor['MaxFocusSpeed']-$monitor['MinFocusSpeed'])*$factor)));
                            $ctrlCommand .= " --speed=".$speed;
                        }
                        switch( $mode )
                        {
                            case 'Abs' :
                            case 'Rel' :
                            {
                                $step = intval(round($monitor['MinFocusStep']+(($monitor['MaxFocusStep']-$monitor['MinFocusStep'])*$factor)));
                                $ctrlCommand .= " --step=".$step;
                                break;
                            }
                            case 'Con' :
                            {
                                if ( $monitor['AutoStopTimeout'] )
                                {
                                    $slowSpeed = intval(round($monitor['MinFocusSpeed']+(($monitor['MaxFocusSpeed']-$monitor['MinFocusSpeed'])*$slow)));
                                    if ( $speed < $slowSpeed )
                                    {
                                        $ctrlCommand .= " --autostop";
                                    }
                                }
                                break;
                            }
                        }
                        break;
                    }
                    case 'zoom' :
                    {
                        switch( $dirn )
                        {
                            case 'Tele' :
                            {
                                $factor = ($long_y-($y+1))/$long_y;
                                break;
                            }
                            case 'Wide' :
                            {
                                $factor = ($y+1)/$long_y;
                                break;
                            }
                        }
                        if ( $monitor['HasZoomSpeed'] )
                        {
                            $speed = intval(round($monitor['MinZoomSpeed']+(($monitor['MaxZoomSpeed']-$monitor['MinZoomSpeed'])*$factor)));
                            $ctrlCommand .= " --speed=".$speed;
                        }
                        switch( $mode )
                        {
                            case 'Abs' :
                            case 'Rel' :
                            {
                                $step = intval(round($monitor['MinZoomStep']+(($monitor['MaxZoomStep']-$monitor['MinZoomStep'])*$factor)));
                                $ctrlCommand .= " --step=".$step;
                                break;
                            }
                            case 'Con' :
                            {
                                if ( $monitor['AutoStopTimeout'] )
                                {
                                    $slowSpeed = intval(round($monitor['MinZoomSpeed']+(($monitor['MaxZoomSpeed']-$monitor['MinZoomSpeed'])*$slow)));
                                    if ( $speed < $slowSpeed )
                                    {
                                        $ctrlCommand .= " --autostop";
                                    }
                                }
                                break;
                            }
                        }
                        break;
                    }
                    case 'iris' :
                    {
                        switch( $dirn )
                        {
                            case 'Open' :
                            {
                                $factor = ($long_y-($y+1))/$long_y;
                                break;
                            }
                            case 'Close' :
                            {
                                $factor = ($y+1)/$long_y;
                                break;
                            }
                        }
                        if ( $monitor['HasIrisSpeed'] )
                        {
                            $speed = intval(round($monitor['MinIrisSpeed']+(($monitor['MaxIrisSpeed']-$monitor['MinIrisSpeed'])*$factor)));
                            $ctrlCommand .= " --speed=".$speed;
                        }
                        switch( $mode )
                        {
                            case 'Abs' :
                            case 'Rel' :
                            {
                                $step = intval(round($monitor['MinIrisStep']+(($monitor['MaxIrisStep']-$monitor['MinIrisStep'])*$factor)));
                                $ctrlCommand .= " --step=".$step;
                                break;
                            }
                        }
                        break;
                    }
                    case 'white' :
                    {
                        switch( $dirn )
                        {
                            case 'In' :
                            {
                                $factor = ($long_y-($y+1))/$long_y;
                                break;
                            }
                            case 'Out' :
                            {
                                $factor = ($y+1)/$long_y;
                                break;
                            }
                        }
                        if ( $monitor['HasWhiteSpeed'] )
                        {
                            $speed = intval(round($monitor['MinWhiteSpeed']+(($monitor['MaxWhiteSpeed']-$monitor['MinWhiteSpeed'])*$factor)));
                            $ctrlCommand .= " --speed=".$speed;
                        }
                        switch( $mode )
                        {
                            case 'Abs' :
                            case 'Rel' :
                            {
                                $step = intval(round($monitor['MinWhiteStep']+(($monitor['MaxWhiteStep']-$monitor['MinWhiteStep'])*$factor)));
                                $ctrlCommand .= " --step=".$step;
                                break;
                            }
                        }
                        break;
                    }
                    case 'gain' :
                    {
                        switch( $dirn )
                        {
                            case 'Up' :
                            {
                                $factor = ($long_y-($y+1))/$long_y;
                                break;
                            }
                            case 'Down' :
                            {
                                $factor = ($y+1)/$long_y;
                                break;
                            }
                        }
                        if ( $monitor['HasGainSpeed'] )
                        {
                            $speed = intval(round($monitor['MinGainSpeed']+(($monitor['MaxGainSpeed']-$monitor['MinGainSpeed'])*$factor)));
                            $ctrlCommand .= " --speed=".$speed;
                        }
                        switch( $mode )
                        {
                            case 'Abs' :
                            case 'Rel' :
                            {
                                $step = intval(round($monitor['MinGainStep']+(($monitor['MaxGainStep']-$monitor['MinGainStep'])*$factor)));
                                $ctrlCommand .= " --step=".$step;
                                break;
                            }
                        }
                        break;
                    }
                    case 'move' :
                    {
                        $xFactor = 0;
                        $yFactor = 0;

                        if ( preg_match( '/^Up/', $dirn ) )
                        {
                            $yFactor = ($short_y-($y+1))/$short_y;
                        }
                        elseif ( preg_match( '/^Down/', $dirn ) )
                        {
                            $yFactor = ($y+1)/$short_y;
                        }
                        if ( preg_match( '/Left$/', $dirn ) )
                        {
                            $xFactor = ($short_x-($x+1))/$short_x;
                        }
                        elseif ( preg_match( '/Right$/', $dirn ) )
                        {
                            $xFactor = ($x+1)/$short_x;
                        }

                        if ( $monitor['Orientation'] != '0' )
                        {
                            $conversions = array(
                                '90' => array(
                                    'Up' => 'Left',
                                    'Down' => 'Right',
                                    'Left' => 'Down',
                                    'Right' => 'Up',
                                    'UpLeft' => 'DownLeft',
                                    'UpRight' => 'UpLeft',
                                    'DownLeft' => 'DownRight',
                                    'DownRight' => 'UpRight',
                                ),
                                '180' => array(
                                    'Up' => 'Down',
                                    'Down' => 'Up',
                                    'Left' => 'Right',
                                    'Right' => 'Left',
                                    'UpLeft' => 'DownRight',
                                    'UpRight' => 'DownLeft',
                                    'DownLeft' => 'UpRight',
                                    'DownRight' => 'UpLeft',
                                ),
                                '270' => array(
                                    'Up' => 'Right',
                                    'Down' => 'Left',
                                    'Left' => 'Up',
                                    'Right' => 'Down',
                                    'UpLeft' => 'UpRight',
                                    'UpRight' => 'DownRight',
                                    'DownLeft' => 'UpLeft',
                                    'DownRight' => 'DownLeft',
                                ),
                                'hori' => array(
                                    'Up' => 'Up',
                                    'Down' => 'Down',
                                    'Left' => 'Right',
                                    'Right' => 'Left',
                                    'UpLeft' => 'UpRight',
                                    'UpRight' => 'UpLeft',
                                    'DownLeft' => 'DownRight',
                                    'DownRight' => 'DownLeft',
                                ),
                                'vert' => array(
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
                            $new_dirn = $conversions[$monitor['Orientation']][$dirn];
                            $_REQUEST['control'] = preg_replace( "/_$dirn\$/", "_$new_dirn", $_REQUEST['control'] );
                            $dirn = $new_dirn;
                        }

                        if ( $monitor['HasPanSpeed'] && $xFactor )
                        {
                            if ( $monitor['HasTurboPan'] )
                            {
                                if ( $xFactor >= $turbo )
                                {
                                    $panSpeed = $monitor['TurboPanSpeed'];
                                }
                                else
                                {
                                    $xFactor = $xFactor/$turbo;
                                    $panSpeed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$xFactor)));
                                }
                            }
                            else
                            {
                                $panSpeed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$xFactor)));
                            }
                            $ctrlCommand .= " --panspeed=".$panSpeed;
                        }
                        if ( $monitor['HasTiltSpeed'] && $yFactor )
                        {
                            if ( $monitor['HasTurboTilt'] )
                            {
                                if ( $yFactor >= $turbo )
                                {
                                    $tiltSpeed = $monitor['TurboTiltSpeed'];
                                }
                                else
                                {
                                    $yFactor = $yFactor/$turbo;
                                    $tiltSpeed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$yFactor)));
                                }
                            }
                            else
                            {
                                $tiltSpeed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$yFactor)));
                            }
                            $ctrlCommand .= " --tiltspeed=".$tiltSpeed;
                        }
                        switch( $mode )
                        {
                            case 'Rel' :
                            case 'Abs' :
                            {
                                if ( preg_match( '/(Left|Right)$/', $dirn ) )
                                {
                                    $panStep = intval(round($monitor['MinPanStep']+(($monitor['MaxPanStep']-$monitor['MinPanStep'])*$xFactor)));
                                    $ctrlCommand .= " --panstep=".$panStep;
                                }
                                if ( preg_match( '/^(Up|Down)/', $dirn ) )
                                {
                                    $tiltStep = intval(round($monitor['MinTiltStep']+(($monitor['MaxTiltStep']-$monitor['MinTiltStep'])*$yFactor)));
                                    $ctrlCommand .= " --tiltstep=".$tiltStep;
                                }
                                break;
                            }
                            case 'Con' :
                            {
                                if ( $monitor['AutoStopTimeout'] )
                                {
                                    $slowPanSpeed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$slow)));
                                    $slowTiltSpeed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$slow)));
                                    if ( (!isset($panSpeed) || ($panSpeed < $slowPanSpeed)) && (!isset($tiltSpeed) || ($tiltSpeed < $slowTiltSpeed)) )
                                    {
                                        $ctrlCommand .= " --autostop";
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
    else
    {
        if ( preg_match( '/^presetGoto(\d+)$/', $_REQUEST['control'], $matches ) )
        {
            $_REQUEST['control'] = 'presetGoto';
            $ctrlCommand .= " --preset=".$matches[1];
        }
        elseif ( $_REQUEST['control'] == "presetSet" )
        {
            if ( canEdit( 'Control' ) )
            {
                $sql = "select * from ControlPresets where MonitorId = '".$monitor['Id']."' and Preset = '".$preset."'";
                $row = dbFetchOne( $sql );
                if ( $new_label != $row['Label'] )
                {
                    if ( $new_label )
                        $sql = "replace into ControlPresets ( MonitorId, Preset, Label ) values ( '".$monitor['Id']."', '".$preset."', '".addslashes($new_label)."' )";
                    else
                        $sql = "delete from ControlPresets where MonitorId = '".$monitor['Id']."' and Preset = '".$preset."'";
                    dbQuery( $sql );
                    $refresh_parent = true;
                }
            }
            $ctrlCommand .= " --preset=".$preset;
            $view = 'none';
        }
        elseif ( $_REQUEST['control'] == "moveMap" )
        {
            $ctrlCommand .= " --xcoord=$x --ycoord=$y";
        }
    }
    if ( $_REQUEST['control'] != 'null' )
    {
        $ctrlCommand .= " --command=".$_REQUEST['control'];
        $socket = socket_create( AF_UNIX, SOCK_STREAM, 0 );
        if ( !$socket )
            ajaxError( "socket_create() failed: ".socket_strerror(socket_last_error()) );

        $sock_file = ZM_PATH_SOCKS.'/zmcontrol-'.$monitor['Id'].'.sock';
        if ( @socket_connect( $socket, $sock_file ) )
        {
            $options = array();
            foreach ( split( " ", $ctrlCommand ) as $option )
            {
                if ( preg_match( '/--([^=]+)(?:=(.+))?/', $option, $matches ) )
                {
                    $options[$matches[1]] = !empty($matches[2])?$matches[2]:1;
                }
            }
            $option_string = serialize( $options );
            if ( !socket_write( $socket, $option_string ) )
                ajaxError( "socket_write() failed: ".socket_strerror(socket_last_error()) );
            ajaxResponse( 'Used socket' );
            //socket_close( $socket );
        }
        else
        {
            $ctrlCommand .= " --id=".$monitor['Id'];

            // Can't connect so use script
            $ctrlStatus = '';
            $ctrlOutput = array();
            exec( escapeshellcmd( $ctrlCommand ), $ctrlOutput, $ctrlStatus );
            if ( $ctrlStatus )
                ajaxError( $ctrlCommand.'=>'.join( ' // ', $ctrlOutput ) );
            ajaxResponse( 'Used script' );
        }
    }
    else
    {
        ajaxError( "No command received" );
    }
}

ajaxError( 'Unrecognised action or insufficient permissions' );

function ajaxCleanup()
{
    global $socket;
    if ( !empty( $socket ) )
        @socket_close( $socket );
}
?>
