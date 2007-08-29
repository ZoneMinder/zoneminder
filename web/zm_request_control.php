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

$ctrl_command = $monitor['Command'];
if ( !preg_match( '/^\//', $ctrl_command ) )
    $ctrl_command = ZM_PATH_BIN.'/'.$ctrl_command;
if ( $monitor['ControlDevice'] )
    $ctrl_command .= " --device=".$monitor['ControlDevice'];
if ( $monitor['ControlAddress'] )
    $ctrl_command .= " --address=".$monitor['ControlAddress'];

$control = $_REQUEST['control'];
if ( isset($_REQUEST['x']) && isset($_REQUEST['y']) )
{
    $x = $_REQUEST['x'];
    $y = $_REQUEST['y'];
    $scale = $_REQUEST['scale'];
    if ( $control == "move_map" )
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
        $ctrl_command .= " -xcoord $x -ycoord $y -width $width -height $height";
    }
    elseif ( $control == "move_pseudo_map" )
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
            $pan_control = 'right';
        }
        elseif ( $x_factor < -$blind )
        {
            $pan_control = 'left';
        }
        if ( $y_factor > $blind )
        {
            $tilt_control = 'down';
        }
        elseif ( $y_factor < -$blind )
        {
            $tilt_control = 'up';
        }

        $dirn = $tilt_control.$pan_control;
        if ( !$dirn )
        {
            // No command, probably in blind spot in middle
            $control = 'null';
        }
        else
        {
            $control = 'move_rel_'.$dirn;
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
            if ( preg_match( '/(left|right)$/', $dirn ) )
            {
                $pan_step = intval(round($monitor['MinPanStep']+(($monitor['MaxPanStep']-$monitor['MinPanStep'])*$x_factor)));
                $ctrl_command .= " --panstep=".$pan_step." --panspeed=".$pan_speed;
            }
            if ( preg_match( '/^(up|down)/', $dirn ) )
            {
                $tilt_step = intval(round($monitor['MinTiltStep']+(($monitor['MaxTiltStep']-$monitor['MinTiltStep'])*$y_factor)));
                $ctrl_command .= " --tiltstep=".$tilt_step." --tiltspeed=".$tilt_speed;
            }
        }
    }
    elseif ( $control == "move_con_map" )
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
            $pan_control = 'right';
        }
        elseif ( $x_factor < -$blind )
        {
            $pan_control = 'left';
        }
        if ( $y_factor > $blind )
        {
            $tilt_control = 'down';
        }
        elseif ( $y_factor < -$blind )
        {
            $tilt_control = 'up';
        }

        $dirn = $tilt_control.$pan_control;
        if ( !$dirn )
        {
            // No command, probably in blind spot in middle
            $control = 'move_stop';
        }
        else
        {
            $control = 'move_con_'.$dirn;
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
            if ( preg_match( '/(left|right)$/', $dirn ) )
            {
                $ctrl_command .= " --panspeed=".$pan_speed;
            }
            if ( preg_match( '/^(up|down)/', $dirn ) )
            {
                $ctrl_command .= " --tiltspeed=".$tilt_speed;
            }
            if ( $monitor['AutoStopTimeout'] )
            {
                $slow_pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$slow)));
                $slow_tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$slow)));
                if ( (!isset($pan_speed) || ($pan_speed < $slow_pan_speed)) && (!isset($tilt_speed) || ($tilt_speed < $slow_tilt_speed)) )
                {
                    $ctrl_command .= " --autostop=".$monitor['AutoStopTimeout'];
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

        if ( preg_match( '/^([^_]+)_([^_]+)_([^_]+)$/', $control, $matches ) )
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
                        case 'near' :
                        {
                            $factor = ($long_y-($y+1))/$long_y;
                            break;
                        }
                        case 'far' :
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
                        case 'abs' :
                        case 'rel' :
                        {
                            $step = intval(round($monitor['MinFocusStep']+(($monitor['MaxFocusStep']-$monitor['MinFocusStep'])*$factor)));
                            $ctrl_command .= " --step=".$step;
                            break;
                        }
                        case 'con' :
                        {
                            if ( $monitor['AutoStopTimeout'] )
                            {
                                $slow_speed = intval(round($monitor['MinFocusSpeed']+(($monitor['MaxFocusSpeed']-$monitor['MinFocusSpeed'])*$slow)));
                                if ( $speed < $slow_speed )
                                {
                                    $ctrl_command .= " --autostop=".$monitor['AutoStopTimeout'];
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
                        case 'tele' :
                        {
                            $factor = ($long_y-($y+1))/$long_y;
                            break;
                        }
                        case 'wide' :
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
                        case 'abs' :
                        case 'rel' :
                        {
                            $step = intval(round($monitor['MinZoomStep']+(($monitor['MaxZoomStep']-$monitor['MinZoomStep'])*$factor)));
                            $ctrl_command .= " --step=".$step;
                            break;
                        }
                        case 'con' :
                        {
                            if ( $monitor['AutoStopTimeout'] )
                            {
                                $slow_speed = intval(round($monitor['MinZoomSpeed']+(($monitor['MaxZoomSpeed']-$monitor['MinZoomSpeed'])*$slow)));
                                if ( $speed < $slow_speed )
                                {
                                    $ctrl_command .= " --autostop=".$monitor['AutoStopTimeout'];
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
                        case 'open' :
                        {
                            $factor = ($long_y-($y+1))/$long_y;
                            break;
                        }
                        case 'close' :
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
                        case 'abs' :
                        case 'rel' :
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
                        case 'in' :
                        {
                            $factor = ($long_y-($y+1))/$long_y;
                            break;
                        }
                        case 'out' :
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
                        case 'abs' :
                        case 'rel' :
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
                        case 'up' :
                        {
                            $factor = ($long_y-($y+1))/$long_y;
                            break;
                        }
                        case 'down' :
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
                        case 'abs' :
                        case 'rel' :
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

                    if ( preg_match( '/^up/', $dirn ) )
                    {
                        $y_factor = ($short_y-($y+1))/$short_y;
                    }
                    elseif ( preg_match( '/^down/', $dirn ) )
                    {
                        $y_factor = ($y+1)/$short_y;
                    }
                    if ( preg_match( '/left$/', $dirn ) )
                    {
                        $x_factor = ($short_x-($x+1))/$short_x;
                    }
                    elseif ( preg_match( '/right$/', $dirn ) )
                    {
                        $x_factor = ($x+1)/$short_x;
                    }

                    if ( $monitor['Orientation'] != '0' )
                    {
                        $conversions = array(
                            '90' => array(
                                'up' => 'left',
                                'down' => 'right',
                                'left' => 'down',
                                'right' => 'up',
                                'upleft' => 'downleft',
                                'upright' => 'upleft',
                                'downleft' => 'downright',
                                'downright' => 'upright',
                            ),
                            '180' => array(
                                'up' => 'down',
                                'down' => 'up',
                                'left' => 'right',
                                'right' => 'left',
                                'upleft' => 'downright',
                                'upright' => 'downleft',
                                'downleft' => 'upright',
                                'downright' => 'upleft',
                            ),
                            '270' => array(
                                'up' => 'right',
                                'down' => 'left',
                                'left' => 'up',
                                'right' => 'down',
                                'upleft' => 'upright',
                                'upright' => 'downright',
                                'downleft' => 'upleft',
                                'downright' => 'downleft',
                            ),
                            'hori' => array(
                                'up' => 'up',
                                'down' => 'down',
                                'left' => 'right',
                                'right' => 'left',
                                'upleft' => 'upright',
                                'upright' => 'upleft',
                                'downleft' => 'downright',
                                'downright' => 'downleft',
                            ),
                            'vert' => array(
                                'up' => 'down',
                                'down' => 'up',
                                'left' => 'left',
                                'right' => 'right',
                                'upleft' => 'downleft',
                                'upright' => 'downright',
                                'downleft' => 'upleft',
                                'downright' => 'upright',
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
                        case 'rel' :
                        case 'abs' :
                        {
                            if ( preg_match( '/(left|right)$/', $dirn ) )
                            {
                                $pan_step = intval(round($monitor['MinPanStep']+(($monitor['MaxPanStep']-$monitor['MinPanStep'])*$x_factor)));
                                $ctrl_command .= " --panstep=".$pan_step;
                            }
                            if ( preg_match( '/^(up|down)/', $dirn ) )
                            {
                                $tilt_step = intval(round($monitor['MinTiltStep']+(($monitor['MaxTiltStep']-$monitor['MinTiltStep'])*$y_factor)));
                                $ctrl_command .= " --tiltstep=".$tilt_step;
                            }
                            break;
                        }
                        case 'con' :
                        {
                            if ( $monitor['AutoStopTimeout'] )
                            {
                                $slow_pan_speed = intval(round($monitor['MinPanSpeed']+(($monitor['MaxPanSpeed']-$monitor['MinPanSpeed'])*$slow)));
                                $slow_tilt_speed = intval(round($monitor['MinTiltSpeed']+(($monitor['MaxTiltSpeed']-$monitor['MinTiltSpeed'])*$slow)));
                                if ( (!isset($pan_speed) || ($pan_speed < $slow_pan_speed)) && (!isset($tilt_speed) || ($tilt_speed < $slow_tilt_speed)) )
                                {
                                    $ctrl_command .= " --autostop=".$monitor['AutoStopTimeout'];
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
    if ( preg_match( '/^preset_goto_(\d+)$/', $control, $matches ) )
    {
        $control = 'preset_goto';
        $ctrl_command .= " --preset ".$matches[1];
    }
    elseif ( $control == "preset_set" )
    {
        $preset = $_REQUEST['preset'];
        if ( canEdit( 'Control' ) )
        {
            $new_label = $_REQUEST['new_label'];
            $row = dbFetchOne( "select * from ControlPresets where MonitorId = '".$monitor['Id']."' and Preset = '".$preset."'" );
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
        $ctrl_command .= " --preset ".$preset;
        $view = 'none';
    }
}
if ( $control != 'null' )
{
    $ctrl_command .= " --command=".$control;
    error_log( "Command: $ctrl_command" );
    $ctrl_status = '';
    $ctrl_output = array();
    exec( escapeshellcmd( $ctrl_command, $ctrl_output, $ctrl_status ) );
    error_log( "Status: $ctrl_status" );
    error_log( "Output: ".join( "\n", $ctrl_output ) );
    if ( !$ctrl_status )
        $response = array( 'result' => "Ok" );
    else
        $response = array( 'result' => "Error", 'status' => $ctrl_status, 'message' => join( "\n", $ctrl_output ) );
}
else
{
    $response = array( 'result' => "Error", 'status' => 0, 'message' => "No command given" );
}

header("Content-type: text/plain" );
echo jsValue( $response );

?>
