<?php
// Monitor control actions, require a monitor id and control view permissions for that monitor
if ( empty($_REQUEST['id']) || !canView( 'Control', $_REQUEST['id'] ) )
{
	$view = "error";
	return;
}

error_reporting( E_ALL );

if ( !$_REQUEST['id'] )
{
    error_log( "No monitor id supplied" );
    return;
}

$monitor = dbFetchOne( "select C.*,M.* from Monitors as M inner join Controls as C on (M.ControlId = C.Id ) where M.Id = '".$_REQUEST['id']."'" );

$ctrl_command = "zmcontrol.pl";
if ( !preg_match( '/^\//', $ctrl_command ) )
    $ctrl_command = ZM_PATH_BIN.'/'.$ctrl_command;

if ( isset($xge) || isset($yge) )
{
    $slow = 0.9; // Threshold for slow speed/timeouts
    $turbo = 0.9; // Threshold for turbo speed

    if ( preg_match( '/^([a-z]+)([A-Z][a-z]+)([A-Z][a-z]+)$/', $control, $matches ) )
    {
        $command = $matches[1];
        $mode = $matches[2];
        $dirn = $matches[3];

        switch( $command )
        {
            case 'focus' :
            {
                $factor = $yge/100;
                if ( $monitor['HasFocusSpeed'] )
                {
                    $speed = intval(round($monitor['MinFocusSpeed']+(($monitor['MaxFocusSpeed']-$monitor['MinFocusSpeed'])*$factor)));
                    $ctrl_command .= " --speed=".$speed;
                }
                switch( $mode )
                {
                    case 'Abs' :
                    case 'Rel' :
                    {
                        $step = intval(round($monitor['MinFocusStep']+(($monitor['MaxFocusStep']-$monitor['MinFocusStep'])*$factor)));
                        $ctrl_command .= " --step=".$step;
                        break;
                    }
                    case 'Con' :
                    {
                        if ( $monitor['AutoStopTimeout'] )
                        {
                            $slow_speed = intval(round($monitor['MinFocusSpeed']+(($monitor['MaxFocusSpeed']-$monitor['MinFocusSpeed'])*$slow)));
                            if ( $speed < $slow_speed )
                            {
                                $ctrl_command .= " --autostop";
                            }
                        }
                        break;
                    }
                }
                break;
            }
            case 'zoom' :
            {
                $factor = $yge/100;
                if ( $monitor['HasZoomSpeed'] )
                {
                    $speed = intval(round($monitor['MinZoomSpeed']+(($monitor['MaxZoomSpeed']-$monitor['MinZoomSpeed'])*$factor)));
                    $ctrl_command .= " --speed=".$speed;
                }
                switch( $mode )
                {
                    case 'Abs' :
                    case 'Rel' :
                    {
                        $step = intval(round($monitor['MinZoomStep']+(($monitor['MaxZoomStep']-$monitor['MinZoomStep'])*$factor)));
                        $ctrl_command .= " --step=".$step;
                        break;
                    }
                    case 'Con' :
                    {
                        if ( $monitor['AutoStopTimeout'] )
                        {
                            $slow_speed = intval(round($monitor['MinZoomSpeed']+(($monitor['MaxZoomSpeed']-$monitor['MinZoomSpeed'])*$slow)));
                            if ( $speed < $slow_speed )
                            {
                                $ctrl_command .= " --autostop";
                            }
                        }
                        break;
                    }
                }
                break;
            }
            case 'iris' :
            {
                $factor = $yge/100;
                if ( $monitor['HasIrisSpeed'] )
                {
                    $speed = intval(round($monitor['MinIrisSpeed']+(($monitor['MaxIrisSpeed']-$monitor['MinIrisSpeed'])*$factor)));
                    $ctrl_command .= " --speed=".$speed;
                }
                switch( $mode )
                {
                    case 'Abs' :
                    case 'Rel' :
                    {
                        $step = intval(round($monitor['MinIrisStep']+(($monitor['MaxIrisStep']-$monitor['MinIrisStep'])*$factor)));
                        $ctrl_command .= " --step=".$step;
                        break;
                    }
                }
                break;
            }
            case 'white' :
            {
                $factor = $yge/100;
                if ( $monitor['HasWhiteSpeed'] )
                {
                    $speed = intval(round($monitor['MinWhiteSpeed']+(($monitor['MaxWhiteSpeed']-$monitor['MinWhiteSpeed'])*$factor)));
                    $ctrl_command .= " --speed=".$speed;
                }
                switch( $mode )
                {
                    case 'Abs' :
                    case 'Rel' :
                    {
                        $step = intval(round($monitor['MinWhiteStep']+(($monitor['MaxWhiteStep']-$monitor['MinWhiteStep'])*$factor)));
                        $ctrl_command .= " --step=".$step;
                        break;
                    }
                }
                break;
            }
            case 'gain' :
            {
                $factor = $yge/100;
                if ( $monitor['HasGainSpeed'] )
                {
                    $speed = intval(round($monitor['MinGainSpeed']+(($monitor['MaxGainSpeed']-$monitor['MinGainSpeed'])*$factor)));
                    $ctrl_command .= " --speed=".$speed;
                }
                switch( $mode )
                {
                    case 'Abs' :
                    case 'Rel' :
                    {
                        $step = intval(round($monitor['MinGainStep']+(($monitor['MaxGainStep']-$monitor['MinGainStep'])*$factor)));
                        $ctrl_command .= " --step=".$step;
                        break;
                    }
                }
                break;
            }
            case 'move' :
            {
                $x_factor = empty($xge)?0:$xge/100;
                $y_factor = empty($yge)?0:$yge/100;

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
                    $control = preg_replace( "/_$dirn\$/", "_$new_dirn", $control );
                    $dirn = $new_dirn;
                }

                if ( $monitor['HasPanSpeed'] && $x_factor )
                {
                    if ( $monitor['HasTurboPan'] )
                    {
                        if ( $x_factor >= $turbo )
                        {
                            $pan_speed = $monitor['TurboPanSpeed'];
                        }
                        else
                        {
                            $x_factor = $x_factor/$turbo;
                            $pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
                        }
                    }
                    else
                    {
                        $pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
                    }
                    $ctrl_command .= " --panspeed=".$pan_speed;
                }
                if ( $monitor['HasTiltSpeed'] && $y_factor )
                {
                    if ( $monitor['HasTurboTilt'] )
                    {
                        if ( $y_factor >= $turbo )
                        {
                            $tilt_speed = $monitor['TurboTiltSpeed'];
                        }
                        else
                        {
                            $y_factor = $y_factor/$turbo;
                            $tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
                        }
                    }
                    else
                    {
                        $tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
                    }
                    $ctrl_command .= " --tiltspeed=".$tilt_speed;
                }
                switch( $mode )
                {
                    case 'Rel' :
                    case 'Abs' :
                    {
                        if ( preg_match( '/(Left|Right)$/', $dirn ) )
                        {
                            $pan_step = intval(round($monitor['MinPanStep']+(($monitor['MaxPanStep']-$monitor['MinPanStep'])*$x_factor)));
                            $ctrl_command .= " --panstep=".$pan_step;
                        }
                        if ( preg_match( '/^(Up|Down)/', $dirn ) )
                        {
                            $tilt_step = intval(round($monitor['MinTiltStep']+(($monitor['MaxTiltStep']-$monitor['MinTiltStep'])*$y_factor)));
                            $ctrl_command .= " --tiltstep=".$tilt_step;
                        }
                        break;
                    }
                    case 'Con' :
                    {
                        if ( $monitor['AutoStopTimeout'] )
                        {
                            $slow_pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$slow)));
                            $slow_tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$slow)));
                            if ( (!isset($pan_speed) || ($pan_speed < $slow_pan_speed)) && (!isset($tilt_speed) || ($tilt_speed < $slow_tilt_speed)) )
                            {
                                $ctrl_command .= " --autostop";
                            }
                        }
                        break;
                    }
                }
            }
        }
    }
}
elseif ( isset($x) && isset($y) )
{
    if ( $control == "moveMap" )
    {
        $x = deScale( $x, $scale );
        $y = deScale( $y, $scale );
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
                $temp_y = $y;
                $y = $height - $x;
                $x = $temp_y;
                break;
            case '180' :
                $x = $width - $x;
                $y = $height - $y;
                break;
            case '270' :
                $temp_x = $x;
                $x = $width - $y;
                $y = $temp_x;
                break;
            case 'hori' :
                $x = $width - $x;
                break;
            case 'vert' :
                $y = $height - $y;
                break;
        }
        //$ctrl_command .= " --xcoord=$x --ycoord=$y --width=$width --height=$height";
        $ctrl_command .= " --xcoord=$x --ycoord=$y";
    }
    elseif ( $control == "movePseudoMap" )
    {
        $x = deScale( $x, $scale );
        $y = deScale( $y, $scale );
        
        $half_width = $monitor['Width'] / 2;
        $half_height = $monitor['Height'] / 2;
        $x_factor = ($x - $half_width)/$half_width;
        $y_factor = ($y - $half_height)/$half_height;

        switch ( $monitor['Orientation'] )
        {
            case '90' :
                $temp_y_factor = $y;
                $y_factor = -$x_factor;
                $x_factor = $temp_y_factor;
                break;
            case '180' :
                $x_factor = -$x_factor;
                $y_factor = -$y_factor;
                break;
            case '270' :
                $temp_x_factor = $x;
                $x_factor = -$y_factor;
                $y_factor = $tenp_x_factor;
                break;
            case 'hori' :
                $x_factor = -$x_factor;
                break;
            case 'vert' :
                $y_factor = -$y_factor;
                break;
        }

        $turbo = 0.9; // Threshold for turbo speed
        $blind = 0.1; // Threshold for blind spot

        $pan_control = '';
        $tilt_control = '';
        if ( $x_factor > $blind )
        {
            $pan_control = 'Right';
        }
        elseif ( $x_factor < -$blind )
        {
            $pan_control = 'Left';
        }
        if ( $y_factor > $blind )
        {
            $tilt_control = 'Down';
        }
        elseif ( $y_factor < -$blind )
        {
            $tilt_control = 'Up';
        }

        $dirn = $tilt_control.$pan_control;
        if ( !$dirn )
        {
            // No command, probably in blind spot in middle
            $control = 'null';
        }
        else
        {
            $control = 'moveRel'.$dirn;
            $x_factor = abs($x_factor);
            $y_factor = abs($y_factor);

            if ( $monitor['HasPanSpeed'] && $x_factor )
            {
                if ( $monitor['HasTurboPan'] )
                {
                    if ( $x_factor >= $turbo )
                    {
                        $pan_speed = $monitor['TurboPanSpeed'];
                    }
                    else
                    {
                        $x_factor = $x_factor/$turbo;
                        $pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
                    }
                }
                else
                {
                    $pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
                }
            }
            if ( $monitor['HasTiltSpeed'] && $y_factor )
            {
                if ( $monitor['HasTurboTilt'] )
                {
                    if ( $y_factor >= $turbo )
                    {
                        $tilt_speed = $monitor['TurboTiltSpeed'];
                    }
                    else
                    {
                        $y_factor = $y_factor/$turbo;
                        $tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
                    }
                }
                else
                {
                    $tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
                }
            }
            if ( preg_match( '/(Left|Right)$/', $dirn ) )
            {
                $pan_step = intval(round($monitor['MinPanStep']+(($monitor['MaxPanStep']-$monitor['MinPanStep'])*$x_factor)));
                $ctrl_command .= " --panstep=".$pan_step." --panspeed=".$pan_speed;
            }
            if ( preg_match( '/^(Up|Down)/', $dirn ) )
            {
                $tilt_step = intval(round($monitor['MinTiltStep']+(($monitor['MaxTiltStep']-$monitor['MinTiltStep'])*$y_factor)));
                $ctrl_command .= " --tiltstep=".$tilt_step." --tiltspeed=".$tilt_speed;
            }
        }
    }
    elseif ( $control == "moveConMap" )
    {
        $x = deScale( $x, $scale );
        $y = deScale( $y, $scale );
        
        $half_width = $monitor['Width'] / 2;
        $half_height = $monitor['Height'] / 2;
        $x_factor = ($x - $half_width)/$half_width;
        $y_factor = ($y - $half_height)/$half_height;

        switch ( $monitor['Orientation'] )
        {
            case '90' :
                $temp_y_factor = $y;
                $y_factor = -$x_factor;
                $x_factor = $temp_y_factor;
                break;
            case '180' :
                $x_factor = -$x_factor;
                $y_factor = -$y_factor;
                break;
            case '270' :
                $temp_x_factor = $x;
                $x_factor = -$y_factor;
                $y_factor = $tenp_x_factor;
                break;
            case 'hori' :
                $x_factor = -$x_factor;
                break;
            case 'vert' :
                $y_factor = -$y_factor;
                break;
        }

        $slow = 0.9; // Threshold for slow speed/timeouts
        $turbo = 0.9; // Threshold for turbo speed
        $blind = 0.1; // Threshold for blind spot

        $pan_control = '';
        $tilt_control = '';
        if ( $x_factor > $blind )
        {
            $pan_control = 'Right';
        }
        elseif ( $x_factor < -$blind )
        {
            $pan_control = 'Left';
        }
        if ( $y_factor > $blind )
        {
            $tilt_control = 'Down';
        }
        elseif ( $y_factor < -$blind )
        {
            $tilt_control = 'Up';
        }

        $dirn = $tilt_control.$pan_control;
        if ( !$dirn )
        {
            // No command, probably in blind spot in middle
            $control = 'moveStop';
        }
        else
        {
            $control = 'moveCon'.$dirn;
            $x_factor = abs($x_factor);
            $y_factor = abs($y_factor);

            if ( $monitor['HasPanSpeed'] && $x_factor )
            {
                if ( $monitor['HasTurboPan'] )
                {
                    if ( $x_factor >= $turbo )
                    {
                        $pan_speed = $monitor['TurboPanSpeed'];
                    }
                    else
                    {
                        $x_factor = $x_factor/$turbo;
                        $pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
                    }
                }
                else
                {
                    $pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
                }
            }
            if ( $monitor['HasTiltSpeed'] && $y_factor )
            {
                if ( $monitor['HasTurboTilt'] )
                {
                    if ( $y_factor >= $turbo )
                    {
                        $tilt_speed = $monitor['TurboTiltSpeed'];
                    }
                    else
                    {
                        $y_factor = $y_factor/$turbo;
                        $tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
                    }
                }
                else
                {
                    $tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
                }
            }
            if ( preg_match( '/(Left|Right)$/', $dirn ) )
            {
                $ctrl_command .= " --panspeed=".$pan_speed;
            }
            if ( preg_match( '/^(Up|Down)/', $dirn ) )
            {
                $ctrl_command .= " --tiltspeed=".$tilt_speed;
            }
            if ( $monitor['AutoStopTimeout'] )
            {
                $slow_pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$slow)));
                $slow_tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$slow)));
                if ( (!isset($pan_speed) || ($pan_speed < $slow_pan_speed)) && (!isset($tilt_speed) || ($tilt_speed < $slow_tilt_speed)) )
                {
                    $ctrl_command .= " --autostop";
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

        if ( preg_match( '/^([a-z]+)([A-Z][a-z]+)([A-Z][a-z]+)$/', $control, $matches ) )
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
                        $ctrl_command .= " --speed=".$speed;
                    }
                    switch( $mode )
                    {
                        case 'Abs' :
                        case 'Rel' :
                        {
                            $step = intval(round($monitor['MinFocusStep']+(($monitor['MaxFocusStep']-$monitor['MinFocusStep'])*$factor)));
                            $ctrl_command .= " --step=".$step;
                            break;
                        }
                        case 'Con' :
                        {
                            if ( $monitor['AutoStopTimeout'] )
                            {
                                $slow_speed = intval(round($monitor['MinFocusSpeed']+(($monitor['MaxFocusSpeed']-$monitor['MinFocusSpeed'])*$slow)));
                                if ( $speed < $slow_speed )
                                {
                                    $ctrl_command .= " --autostop";
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
                        $ctrl_command .= " --speed=".$speed;
                    }
                    switch( $mode )
                    {
                        case 'Abs' :
                        case 'Rel' :
                        {
                            $step = intval(round($monitor['MinZoomStep']+(($monitor['MaxZoomStep']-$monitor['MinZoomStep'])*$factor)));
                            $ctrl_command .= " --step=".$step;
                            break;
                        }
                        case 'Con' :
                        {
                            if ( $monitor['AutoStopTimeout'] )
                            {
                                $slow_speed = intval(round($monitor['MinZoomSpeed']+(($monitor['MaxZoomSpeed']-$monitor['MinZoomSpeed'])*$slow)));
                                if ( $speed < $slow_speed )
                                {
                                    $ctrl_command .= " --autostop";
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
                        $ctrl_command .= " --speed=".$speed;
                    }
                    switch( $mode )
                    {
                        case 'Abs' :
                        case 'Rel' :
                        {
                            $step = intval(round($monitor['MinIrisStep']+(($monitor['MaxIrisStep']-$monitor['MinIrisStep'])*$factor)));
                            $ctrl_command .= " --step=".$step;
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
                        $ctrl_command .= " --speed=".$speed;
                    }
                    switch( $mode )
                    {
                        case 'Abs' :
                        case 'Rel' :
                        {
                            $step = intval(round($monitor['MinWhiteStep']+(($monitor['MaxWhiteStep']-$monitor['MinWhiteStep'])*$factor)));
                            $ctrl_command .= " --step=".$step;
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
                        $ctrl_command .= " --speed=".$speed;
                    }
                    switch( $mode )
                    {
                        case 'Abs' :
                        case 'Rel' :
                        {
                            $step = intval(round($monitor['MinGainStep']+(($monitor['MaxGainStep']-$monitor['MinGainStep'])*$factor)));
                            $ctrl_command .= " --step=".$step;
                            break;
                        }
                    }
                    break;
                }
                case 'move' :
                {
                    $x_factor = 0;
                    $y_factor = 0;

                    if ( preg_match( '/^Up/', $dirn ) )
                    {
                        $y_factor = ($short_y-($y+1))/$short_y;
                    }
                    elseif ( preg_match( '/^Down/', $dirn ) )
                    {
                        $y_factor = ($y+1)/$short_y;
                    }
                    if ( preg_match( '/Left$/', $dirn ) )
                    {
                        $x_factor = ($short_x-($x+1))/$short_x;
                    }
                    elseif ( preg_match( '/Right$/', $dirn ) )
                    {
                        $x_factor = ($x+1)/$short_x;
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
                        $control = preg_replace( "/_$dirn\$/", "_$new_dirn", $control );
                        $dirn = $new_dirn;
                    }

                    if ( $monitor['HasPanSpeed'] && $x_factor )
                    {
                        if ( $monitor['HasTurboPan'] )
                        {
                            if ( $x_factor >= $turbo )
                            {
                                $pan_speed = $monitor['TurboPanSpeed'];
                            }
                            else
                            {
                                $x_factor = $x_factor/$turbo;
                                $pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
                            }
                        }
                        else
                        {
                            $pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$x_factor)));
                        }
                        $ctrl_command .= " --panspeed=".$pan_speed;
                    }
                    if ( $monitor['HasTiltSpeed'] && $y_factor )
                    {
                        if ( $monitor['HasTurboTilt'] )
                        {
                            if ( $y_factor >= $turbo )
                            {
                                $tilt_speed = $monitor['TurboTiltSpeed'];
                            }
                            else
                            {
                                $y_factor = $y_factor/$turbo;
                                $tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
                            }
                        }
                        else
                        {
                            $tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$y_factor)));
                        }
                        $ctrl_command .= " --tiltspeed=".$tilt_speed;
                    }
                    switch( $mode )
                    {
                        case 'Rel' :
                        case 'Abs' :
                        {
                            if ( preg_match( '/(Left|Right)$/', $dirn ) )
                            {
                                $pan_step = intval(round($monitor['MinPanStep']+(($monitor['MaxPanStep']-$monitor['MinPanStep'])*$x_factor)));
                                $ctrl_command .= " --panstep=".$pan_step;
                            }
                            if ( preg_match( '/^(Up|Down)/', $dirn ) )
                            {
                                $tilt_step = intval(round($monitor['MinTiltStep']+(($monitor['MaxTiltStep']-$monitor['MinTiltStep'])*$y_factor)));
                                $ctrl_command .= " --tiltstep=".$tilt_step;
                            }
                            break;
                        }
                        case 'Con' :
                        {
                            if ( $monitor['AutoStopTimeout'] )
                            {
                                $slow_pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$slow)));
                                $slow_tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$slow)));
                                if ( (!isset($pan_speed) || ($pan_speed < $slow_pan_speed)) && (!isset($tilt_speed) || ($tilt_speed < $slow_tilt_speed)) )
                                {
                                    $ctrl_command .= " --autostop";
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
    if ( preg_match( '/^presetGoto(\d+)$/', $control, $matches ) )
    {
        $control = 'presetGoto';
        $ctrl_command .= " --preset=".$matches[1];
    }
    elseif ( $control == "presetSet" )
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
        $ctrl_command .= " --preset=".$preset;
        $view = 'none';
    }
    elseif ( $control == "moveMap" )
    {
        $ctrl_command .= " --xcoord=$x --ycoord=$y";
    }
}
if ( $control != 'null' )
{
    $ctrl_command .= " --command=".$control;
    $socket = socket_create( AF_UNIX, SOCK_STREAM, 0 );
    if ( $socket < 0 )
    {
        die( "socket_create() failed: ".socket_strerror($socket) );
    }
    $sock_file = ZM_PATH_SOCKS.'/zmcontrol-'.$monitor['Id'].'.sock';
    if ( @socket_connect( $socket, $sock_file ) )
    {
        $options = array();
        foreach ( split( " ", $ctrl_command ) as $option )
        {
            if ( preg_match( '/--([^=]+)(?:=(.+))?/', $option, $matches ) )
            {
                $options[$matches[1]] = !empty($matches[2])?$matches[2]:1;
            }
        }
        $option_string = serialize( $options );
        error_log( "Command: $option_string" );
        if ( socket_write( $socket, $option_string ) )
        {
            $response = array( 'result' => "Ok", 'message' => 'Used socket' );
        }
        else
        {
            $response = array( 'result' => "Error", 'status' => 0, 'message' => "Failed to write to control socket" );
        }
        socket_close( $socket );
    }
    else
    {
        $ctrl_command .= " --id=".$monitor['Id'];
        error_log( "Command: $ctrl_command" );

        // Can't connect so use script
        $ctrl_status = '';
        $ctrl_output = array();
        exec( escapeshellcmd( $ctrl_command ), $ctrl_output, $ctrl_status );
        error_log( "Status: $ctrl_status" );
        error_log( "Output: ".join( "\n", $ctrl_output ) );
        if ( !$ctrl_status )
            $response = array( 'result' => "Ok", 'message' => 'Used script' );
        else
            $response = array( 'result' => "Error", 'status' => $ctrl_status, 'message' => join( "\n", $ctrl_output ) );
    }
}
else
{
    $response = array( 'result' => "Error", 'status' => 0, 'message' => "No command given" );
}

header("Content-type: text/plain" );
echo jsValue( $response );

?>
