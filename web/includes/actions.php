<?php
//
// ZoneMinder web action file, $Date$, $Revision$
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

function getAffectedIds( $name )
{
    $names = $name."s";
    $ids = array();
    if ( isset($_REQUEST[$names]) || isset($_REQUEST[$name]) )
    {
        if ( isset($_REQUEST[$names]) )
            $ids = $_REQUEST[$names];
        else if ( isset($_REQUEST[$name]) )
            $ids[] = $_REQUEST[$name];
    }
    return( $ids );
}

if ( !empty($_REQUEST['action']) )
{
    // General scope actions
    if ( $_REQUEST['action'] == "login" && $_REQUEST['username'] && ( ZM_AUTH_TYPE == "remote" || $_REQUEST['password'] ) )
    {
        userLogin( $_REQUEST['username'], $_REQUEST['password'] );
    }
    elseif ( $_REQUEST['action'] == "logout" )
    {
        userLogout();
        $GLOBALS['refreshParent'] = true;
        $_REQUEST['view'] = 'none';
    }
    elseif ( $_REQUEST['action'] == "bandwidth" && $_REQUEST['newBandwidth'] )
    {
        $_COOKIE['zmBandwidth'] = $_REQUEST['newBandwidth'];
        setcookie( "zmBandwidth", $_REQUEST['newBandwidth'], time()+3600*24*30*12*10 );
        $GLOBALS['refreshParent'] = true;
    }

    // Event scope actions, view permissions only required
    if ( canView( 'Events' ) )
    {
        if ( $_REQUEST['action'] == "filter" )
        {
            if ( $_REQUEST['subaction'] == "addterm" )
            {
                $_REQUEST['filter'] = addFilterTerm( $_REQUEST['filter'], $_REQUEST['line'] );
            }
            elseif ( $_REQUEST['subaction'] == "delterm" )
            {
                $_REQUEST['filter'] = delFilterTerm( $_REQUEST['filter'], $_REQUEST['line'] );
            }
        }
    }

    // Event scope actions, edit permissions required
    if ( canEdit( 'Events' ) )
    {
        if ( $_REQUEST['action'] == "rename" && $_REQUEST['eventName'] && !empty($_REQUEST['eid']) )
        {
            dbQuery( "update Events set Name = '".dbEscape($_REQUEST['eventName'])."' where Id = '".dbEscape($_REQUEST['eid'])."'" );
        }
        else if ( $_REQUEST['action'] == "eventdetail" )
        {
            if ( !empty($_REQUEST['eid']) )
            {
                dbQuery( "update Events set Cause = '".dbEscape($_REQUEST['newEvent']['Cause'])."', Notes = '".dbEscape($_REQUEST['newEvent']['Notes'])."' where Id = '".dbEscape($_REQUEST['eid'])."'" );
                $GLOBALS['refreshParent'] = true;
            }
            else
            {
                foreach( getAffectedIds( 'markEid' ) as $markEid )
                {
                    dbQuery( "update Events set Cause = '".dbEscape($_REQUEST['newEvent']['Cause'])."', Notes = '".dbEscape($_REQUEST['newEvent']['Notes'])."' where Id = '".dbEscape($markEid)."'" );
                    $GLOBALS['refreshParent'] = true;
                }
            }
        }
        elseif ( $_REQUEST['action'] == "archive" || $_REQUEST['action'] == "unarchive" )
        {
            $archiveVal = ($_REQUEST['action'] == "archive")?1:0;
            if ( !empty($_REQUEST['eid']) )
            {
                dbQuery( "update Events set Archived = $archiveVal where Id = '".dbEscape($_REQUEST['eid'])."'" );
            }
            else
            {
                foreach( getAffectedIds( 'markEid' ) as $markEid )
                {
                    dbQuery( "update Events set Archived = $archiveVal where Id = '".dbEscape($markEid)."'" );
                    $GLOBALS['refreshParent'] = true;
                }
            }
        }
        elseif ( $_REQUEST['action'] == "filter" && empty($_REQUEST['subaction']) )
        {
            if ( !empty($_REQUEST['execute']) )
                $tempFilterName = "_TempFilter".time();
            if ( $tempFilterName )
                $_REQUEST['filterName'] = $tempFilterName;
            elseif ( $newFilterName )
                $_REQUEST['filterName'] = $newFilterName;
            if ( !empty($_REQUEST['filterName']) )
            {
                $_REQUEST['filter']['sort_field'] = $_REQUEST['sort_field'];
                $_REQUEST['filter']['sort_asc'] .= $_REQUEST['sort_asc'];
                $_REQUEST['filter']['limit'] = $_REQUEST['limit'];
                dbQuery( "replace into Filters set Name = '".dbEscape($_REQUEST['filterName'])."', Query = '".dbEscape(serialize($_REQUEST['filter']))."', AutoArchive = '".dbEscape($_REQUEST['autoArchive'])."', AutoVideo = '".dbEscape($_REQUEST['autoVideo'])."', AutoUpload = '".dbEscape($_REQUEST['autoUpload'])."', AutoEmail = '".dbEscape($_REQUEST['autoEmail'])."', AutoMessage = '".dbEscape($_REQUEST['autoMessage'])."', AutoExecute = '".dbEscape($_REQUEST['autoExecute'])."', AutoExecuteCmd = '".dbEscape($_REQUEST['autoExecuteCmd'])."', AutoDelete = '".dbEscape($_REQUEST['autoDelete'])."', Background = '".dbEscape($_REQUEST['background'])."'" );
                $GLOBALS['refreshParent'] = true;
            }
        }
        elseif ( $_REQUEST['action'] == "delete" )
        {
            foreach( getAffectedIds( 'markEid' ) as $markEid )
            {
                deleteEvent( $markEid );
                $GLOBALS['refreshParent'] = true;
            }
            if ( !empty($_REQUEST['fid']) )
            {
                dbQuery( "delete from Filters where Name = '".$_REQUEST['fid']."'" );
                //$GLOBALS['refreshParent'] = true;
            }
        }
    }

    // Monitor control actions, require a monitor id and control view permissions for that monitor
    if ( !empty($_REQUEST['mid']) && canView( 'Control', $_REQUEST['mid'] ) )
    {
        if ( $_REQUEST['action'] == "control" )
        {
            $monitor = dbFetchOne( "select C.*,M.* from Monitors as M inner join Controls as C on (M.ControlId = C.Id) where M.Id = '".$_REQUEST['mid']."'" );

            $ctrlCommand = ZM_PATH_BIN."/zmcontrol.pl";

            if ( isset($_REQUEST['x']) && isset($_REQUEST['y']) )
            {
                if ( $_REQUEST['control'] == "moveMap" )
                {
                    $_REQUEST['x'] = deScale( $_REQUEST['x'], $_REQUEST['scale'] );
                    $_REQUEST['y'] = deScale( $_REQUEST['y'], $_REQUEST['scale'] );
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
                            $tempY = $_REQUEST['y'];
                            $_REQUEST['y'] = $height - $_REQUEST['x'];
                            $_REQUEST['x'] = $tempY;
                            break;
                        case '180' :
                            $_REQUEST['x'] = $width - $_REQUEST['x'];
                            $_REQUEST['y'] = $height - $_REQUEST['y'];
                            break;
                        case '270' :
                            $tempX = $_REQUEST['x'];
                            $_REQUEST['x'] = $width - $_REQUEST['y'];
                            $_REQUEST['y'] = $tempX;
                            break;
                        case 'hori' :
                            $_REQUEST['x'] = $width - $_REQUEST['x'];
                            break;
                        case 'vert' :
                            $_REQUEST['y'] = $height - $_REQUEST['y'];
                            break;
                    }
                    $ctrlCommand .= " --xcoord=".$_REQUEST['x']." --ycoord=".$_REQUEST['y']." --width=".$width." --height=".$height;
                }
                elseif ( $_REQUEST['control'] == "movePseudoMap" )
                {
                    $_REQUEST['x'] = deScale( $_REQUEST['x'], $_REQUEST['scale'] );
                    $_REQUEST['y'] = deScale( $_REQUEST['y'], $_REQUEST['scale'] );
                    
                    $halfWidth = $monitor['Width'] / 2;
                    $halfHeight = $monitor['Height'] / 2;
                    $xFactor = ($_REQUEST['x'] - $halfWidth)/$halfWidth;
                    $yFactor = ($_REQUEST['y'] - $halfHeight)/$halfHeight;

                    switch ( $monitor['Orientation'] )
                    {
                        case '90' :
                            $tempYFactor = $_REQUEST['y'];
                            $yFactor = -$xFactor;
                            $xFactor = $tempYFactor;
                            break;
                        case '180' :
                            $xFactor = -$xFactor;
                            $yFactor = -$yFactor;
                            break;
                        case '270' :
                            $tempXFactor = $_REQUEST['x'];
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
                    $_REQUEST['x'] = deScale( $_REQUEST['x'], $_REQUEST['scale'] );
                    $_REQUEST['y'] = deScale( $_REQUEST['y'], $_REQUEST['scale'] );
                    
                    $halfWidth = $monitor['Width'] / 2;
                    $halfHeight = $monitor['Height'] / 2;
                    $xFactor = ($_REQUEST['x'] - $halfWidth)/$halfWidth;
                    $yFactor = ($_REQUEST['y'] - $halfHeight)/$halfHeight;

                    switch ( $monitor['Orientation'] )
                    {
                        case '90' :
                            $tempYFactor = $_REQUEST['y'];
                            $yFactor = -$xFactor;
                            $xFactor = $tempYFactor;
                            break;
                        case '180' :
                            $xFactor = -$xFactor;
                            $yFactor = -$yFactor;
                            break;
                        case '270' :
                            $tempXFactor = $_REQUEST['x'];
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
                    $longY = 48;
                    $shortX = 32;
                    $shortY = 32;

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
                                        $factor = ($longY-($_REQUEST['y']+1))/$longY;
                                        break;
                                    }
                                    case 'Far' :
                                    {
                                        $factor = ($_REQUEST['y']+1)/$longY;
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
                                        $factor = ($longY-($_REQUEST['y']+1))/$longY;
                                        break;
                                    }
                                    case 'Wide' :
                                    {
                                        $factor = ($_REQUEST['y']+1)/$longY;
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
                                        $factor = ($longY-($_REQUEST['y']+1))/$longY;
                                        break;
                                    }
                                    case 'Close' :
                                    {
                                        $factor = ($_REQUEST['y']+1)/$longY;
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
                                        $factor = ($longY-($_REQUEST['y']+1))/$longY;
                                        break;
                                    }
                                    case 'Out' :
                                    {
                                        $factor = ($_REQUEST['y']+1)/$longY;
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
                                        $factor = ($longY-($_REQUEST['y']+1))/$longY;
                                        break;
                                    }
                                    case 'Down' :
                                    {
                                        $factor = ($_REQUEST['y']+1)/$longY;
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
                                    $yFactor = ($shortY-($_REQUEST['y']+1))/$shortY;
                                }
                                elseif ( preg_match( '/^Down/', $dirn ) )
                                {
                                    $yFactor = ($_REQUEST['y']+1)/$shortY;
                                }
                                if ( preg_match( '/Left$/', $dirn ) )
                                {
                                    $xFactor = ($shortX-($_REQUEST['x']+1))/$shortX;
                                }
                                elseif ( preg_match( '/Right$/', $dirn ) )
                                {
                                    $xFactor = ($_REQUEST['x']+1)/$shortX;
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
                                    $newDirn = $conversions[$monitor['Orientation']][$dirn];
                                    $_REQUEST['control'] = preg_replace( "/_$dirn\$/", "_$newDirn", $_REQUEST['control'] );
                                    $dirn = $newDirn;
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
                        $row = dbFetchOne( "select * from ControlPresets where MonitorId = '".$monitor['Id']."' and Preset = '".$preset."'" );
                        if ( $newLabel != $row['Label'] )
                        {
                            if ( $newLabel )
                                $sql = "replace into ControlPresets ( MonitorId, Preset, Label ) values ( '".$monitor['Id']."', '".$preset."', '".dbEscape($newLabel)."' )";
                            else
                                $sql = "delete from ControlPresets where MonitorId = '".$monitor['Id']."' and Preset = '".$preset."'";
                            dbQuery( $sql );
                            $GLOBALS['refreshParent'] = true;
                        }
                    }
                    $ctrlCommand .= " --preset=".$preset;
                    $_REQUEST['view'] = 'none';
                }
                elseif ( $_REQUEST['control'] == "moveMap" )
                {
                    $ctrlCommand .= " --xcoord=".$_REQUEST['x']." --ycoord=".$_REQUEST['y'];
                }
            }
            if ( $_REQUEST['control'] != 'null' )
            {
                $ctrlCommand .= " --command=".$_REQUEST['control'];
                $socket = socket_create( AF_UNIX, SOCK_STREAM, 0 );
                if ( $socket < 0 )
                {
                    die( "socket_create() failed: ".socket_strerror($socket) );
                }
                $sockFile = ZM_PATH_SOCKS.'/zmcontrol-'.$monitor[Id].'.sock';
                if ( @socket_connect( $socket, $sockFile ) )
                {
                    $options = array();
                    foreach ( split( " ", $ctrlCommand ) as $option )
                    {
                        if ( preg_match( '/--([^=]+)(?:=(.+))?/', $option, $matches ) )
                        {
                            $options[$matches[1]] = $matches[2]?$matches[2]:1;
                        }
                    }
                    $optionString = serialize( $options );
                    if ( !socket_write( $socket, $optionString ) )
                    {
                        die( "Can't write to control socket: ".socket_strerror(socket_last_error($socket)) );
                    }
                    socket_close( $socket );
                }
                else
                {
                    $ctrlCommand .= " --id=".$monitor['Id'];

                    // Can't connect so use script
                    $ctrlOutput = exec( escapeshellcmd( $ctrlCommand ) );
                }
            }
        }
        elseif ( $_REQUEST['action'] == "settings" )
        {
            $zmuCommand = getZmuCommand( " -m ".$_REQUEST['mid']." -B".$_REQUEST['newBrightness']." -C".$_REQUEST['newContrast']." -H".$_REQUEST['newHue']." -O".$_REQUEST['newColour'] );
            $zmuOutput = exec( escapeshellcmd( $zmuCommand ) );
            list( $brightness, $contrast, $hue, $colour ) = split( ' ', $zmuOutput );
            dbQuery( "update Monitors set Brightness = '".$brightness."', Contrast = '".$contrast."', Hue = '".$hue."', Colour = '".$colour."' where Id = '".$_REQUEST['mid']."'" );
        }
    }

    // Control capability actions, require control edit permissions
    if ( canEdit( 'Control' ) )
    {
        if ( $_REQUEST['action'] == "controlcap" )
        {
            if ( !empty($_REQUEST['cid']) )
            {
                $control = dbFetchOne( "select * from Controls where Id = '".dbEscape($_REQUEST['cid'])."'" );
            }
            else
            {
                $control = array();
            }

            // Define a field type for anything that's not simple text equivalent
            $types = array(
                // Empty
            );

            $columns = getTableColumns( 'Controls' );
            foreach ( $columns as $name=>$type )
            {
                if ( preg_match( '/^(Can|Has)/', $name ) )
                {
                    $types[$name] = 'toggle';
                }
            }
            $changes = getFormChanges( $control, $_REQUEST['newControl'], $types, $columns );

            if ( count( $changes ) )
            {
                if ( !empty($_REQUEST['cid']) )
                {
                    dbQuery( "update Controls set ".implode( ", ", $changes )." where Id = '".dbEscape($_REQUEST['cid'])."'" );
                    $GLOBALS['refreshParent'] = true;
                }
                else
                {
                    dbQuery( "insert into Controls set ".implode( ", ", $changes ) );
                    //$_REQUEST['cid'] = dbInsertId();
                }
                $GLOBALS['refreshParent'] = true;
            }
        }
        elseif ( $_REQUEST['action'] == "delete" )
        {
            if ( isset($_REQUEST['markCids']) )
            {
                foreach( $_REQUEST['markCids'] as $markCid )
                {
                    dbQuery( "delete from Controls where Id = '".dbEscape($markCid)."'" );
                    dbQuery( "update Monitors set Controllable = 0, ControlId = 0 where ControlId = '".dbEscape($markCid)."'" );
                    $GLOBALS['refreshParent'] = true;
                }
            }
        }
    }

    // Monitor edit actions, require a monitor id and edit permissions for that monitor
    if ( !empty($_REQUEST['mid']) && canEdit( 'Monitors', $_REQUEST['mid'] ) )
    {
        if ( $_REQUEST['action'] == "function" )
        {
            $monitor = dbFetchOne( "select * from Monitors where Id = '".$_REQUEST['mid']."'" );

            $oldFunction = $monitor['Function'];
            $oldEnabled = $monitor['Enabled'];
            if ( $_REQUEST['newFunction'] != $oldFunction || $_REQUEST['newEnabled'] != $oldEnabled )
            {
                dbQuery( "update Monitors set Function = '".dbEscape($_REQUEST['newFunction'])."', Enabled = '".$_REQUEST['newEnabled']."' where Id = '".dbEscape($_REQUEST['mid'])."'" );

                $monitor['Function'] = $_REQUEST['newFunction'];
                $monitor['Enabled'] = $_REQUEST['newEnabled'];
                //if ( $cookies ) session_write_close();
                if ( daemonCheck() )
                {
                    $GLOBALS['restart'] = ($oldFunction == 'None') || ($_REQUEST['newFunction'] == 'None') || ($_REQUEST['newEnabled'] != $oldEnabled);
                    zmcControl( $monitor, $GLOBALS['restart']?"restart":"" );
                    zmaControl( $monitor, "reload" );
                }
                $GLOBALS['refreshParent'] = true;
            }
        }
        elseif ( $_REQUEST['action'] == "zone" && isset( $_REQUEST['zid'] ) )
        {
            $monitor = dbFetchOne( "select * from Monitors where Id = '".dbEscape($_REQUEST['mid'])."'" );

            if ( !empty($_REQUEST['zid']) )
            {
                $zone = dbFetchOne( "select * from Zones where MonitorId = '".dbEscape($_REQUEST['mid'])."' and Id = '".dbEscape($_REQUEST['zid'])."'" );
            }
            else
            {
                $zone = array();
            }

            if ( $_REQUEST['newZone']['Units'] == 'Percent' )
            {
                $_REQUEST['newZone']['MinAlarmPixels'] = intval(($_REQUEST['newZone']['MinAlarmPixels']*$_REQUEST['newZone']['Area'])/100);
                $_REQUEST['newZone']['MaxAlarmPixels'] = intval(($_REQUEST['newZone']['MaxAlarmPixels']*$_REQUEST['newZone']['Area'])/100);
                if ( isset($_REQUEST['newZone']['MinFilterPixels']) )
                    $_REQUEST['newZone']['MinFilterPixels'] = intval(($_REQUEST['newZone']['MinFilterPixels']*$_REQUEST['newZone']['Area'])/100);
                if ( isset($_REQUEST['newZone']['MaxFilterPixels']) )
                    $_REQUEST['newZone']['MaxFilterPixels'] = intval(($_REQUEST['newZone']['MaxFilterPixels']*$_REQUEST['newZone']['Area'])/100);
                if ( isset($_REQUEST['newZone']['MinBlobPixels']) )
                    $_REQUEST['newZone']['MinBlobPixels'] = intval(($_REQUEST['newZone']['MinBlobPixels']*$_REQUEST['newZone']['Area'])/100);
                if ( isset($_REQUEST['newZone']['MaxBlobPixels']) )
                    $_REQUEST['newZone']['MaxBlobPixels'] = intval(($_REQUEST['newZone']['MaxBlobPixels']*$_REQUEST['newZone']['Area'])/100);
            }

            unset( $_REQUEST['newZone']['Points'] );
            $types = array();
            $changes = getFormChanges( $zone, $_REQUEST['newZone'], $types );

            if ( count( $changes ) )
            {
                if ( $_REQUEST['zid'] > 0 )
                {
                    $sql = "update Zones set ".implode( ", ", $changes )." where MonitorId = '".dbEscape($_REQUEST['mid'])."' and Id = '".dbEscape($_REQUEST['zid'])."'";
                }
                else
                {
                    $sql = "insert into Zones set MonitorId = '".dbEscape($_REQUEST['mid'])."', ".implode( ", ", $changes );
                }
                dbQuery( $sql );
                //if ( $cookies ) session_write_close();
                if ( daemonCheck() )
                {
                    zmaControl( $_REQUEST['mid'], "restart" );
                }
                $GLOBALS['refreshParent'] = true;
            }
            $_REQUEST['view'] = 'none';
        }
        elseif ( $_REQUEST['action'] == "sequence" && isset($_REQUEST['smid']) )
        {
            $monitor = dbFetchOne( "select * from Monitors where Id = '".dbEscape($_REQUEST['mid'])."'" );
            $smonitor = dbFetchOne( "select * from Monitors where Id = '".dbEscape($_REQUEST['smid'])."'" );

            dbQuery( "update Monitors set Sequence = '".$smonitor['Sequence']."' where Id = '".$monitor['Id']."'" );
            dbQuery( "update Monitors set Sequence = '".$monitor['Sequence']."' where Id = '".$smonitor['Id']."'" );

            $GLOBALS['refreshParent'] = true;
            fixSequences();
        }
        if ( $_REQUEST['action'] == "delete" )
        {
            if ( isset($_REQUEST['markZids']) )
            {
                $deletedZid = 0;
                foreach( $_REQUEST['markZids'] as $markZid )
                {
                    dbQuery( "delete from Zones where MonitorId = '".dbEscape($_REQUEST['mid'])."' && Id = '".dbEscape($markZid)."'" );
                    $deletedZid = 1;
                }
                if ( $deletedZid )
                {
                    //if ( $cookies )
                        //session_write_close();
                    if ( daemonCheck() )
                        zmaControl( $_REQUEST['mid'], "restart" );
                    $GLOBALS['refreshParent'] = true;
                }
            }
        }
    }

    // Monitor edit actions, monitor id derived, require edit permissions for that monitor
    if ( canEdit( 'Monitors' ) )
    {
        if ( $_REQUEST['action'] == "monitor" )
        {
            if ( !empty($_REQUEST['mid']) )
            {
                $monitor = dbFetchOne( "select * from Monitors where Id = '".dbEscape($_REQUEST['mid'])."'" );

                if ( ZM_OPT_X10 )
                {
                    $x10Monitor = dbFetchOne( "select * from TriggersX10 where MonitorId = '".dbEscape($_REQUEST['mid'])."'" );
                    if ( !$x10Monitor )
                        $x10Monitor = array();
                }
            }
            else
            {
                $monitor = array();
                if ( ZM_OPT_X10 )
                {
                    $x10Monitor = array();
                }
            }

            // Define a field type for anything that's not simple text equivalent
            $types = array(
                'Triggers' => 'set',
                'Controllable' => 'toggle',
                'TrackMotion' => 'toggle',
            );

            $columns = getTableColumns( 'Monitors' );
            $changes = getFormChanges( $monitor, $_REQUEST['newMonitor'], $types, $columns );

            if ( count( $changes ) )
            {
                if ( !empty($_REQUEST['mid']) )
                {
                    $sql = "update Monitors set ".implode( ", ", $changes )." where Id = '".dbEscape($_REQUEST['mid'])."'";
                    dbQuery( $sql );
                    if ( $changes['Name'] )
                    {
                        exec( escapeshellcmd( "mv ".ZM_DIR_EVENTS."/".$monitor['Name']." ".ZM_DIR_EVENTS."/".$_REQUEST['newMonitor']['Name'] ) );
                    }
                    if ( $changes['Width'] || $changes['Height'] )
                    {
                        $newW = $_REQUEST['newMonitor']['Width'];
                        $newH = $_REQUEST['newMonitor']['Height'];
                        $newA = $newW * $newH;
                        $oldW = $monitor['Width'];
                        $oldH = $monitor['Height'];
                        $oldA = $oldW * $oldH;

                        $zones = dbFetchAll( "select * from Zones where MonitorId = '".dbEscape($_REQUEST['mid'])."'" );
                        foreach ( $zones as $zone )
                        {
                            $newZone = $zone;
                            $points = coordsToPoints( $zone['Coords'] );
                            for ( $i = 0; $i < count($points); $i++ )
                            {
                                $points[$i]['x'] = intval(($points[$i]['x']*($newW-1))/($oldW-1));
                                $points[$i]['y'] = intval(($points[$i]['y']*($newH-1))/($oldH-1));
                            }
                            $newZone['Coords'] = pointsToCoords( $points );
                            $newZone['Area'] = intval(round(($zone['Area']*$newA)/$oldA));
                            $newZone['MinAlarmPixels'] = intval(round(($newZone['MinAlarmPixels']*$newA)/$oldA));
                            $newZone['MaxAlarmPixels'] = intval(round(($newZone['MaxAlarmPixels']*$newA)/$oldA));
                            $newZone['MinFilterPixels'] = intval(round(($newZone['MinFilterPixels']*$newA)/$oldA));
                            $newZone['MaxFilterPixels'] = intval(round(($newZone['MaxFilterPixels']*$newA)/$oldA));
                            $newZone['MinBlobPixels'] = intval(round(($newZone['MinBlobPixels']*$newA)/$oldA));
                            $newZone['MaxBlobPixels'] = intval(round(($newZone['MaxBlobPixels']*$newA)/$oldA));

                            $changes = getFormChanges( $zone, $newZone, $types );

                            if ( count( $changes ) )
                            {
                                dbQuery( "update Zones set ".implode( ", ", $changes )." where MonitorId = '".dbEscape($_REQUEST['mid'])."' and Id = '".$zone['Id']."'" );
                            }
                        }
                    }
                }
                elseif ( !$user['MonitorIds'] )
                {
                    $maxSeq = dbFetchOne( "select max(Sequence) as MaxSequence from Monitors", "MaxSequence" );
                    $changes[] = "Sequence = ".($maxSeq+1);

                    dbQuery( "insert into Monitors set ".implode( ", ", $changes ) );
                    $_REQUEST['mid'] = dbInsertId();
                    $zoneArea = $_REQUEST['newMonitor']['Width'] * $_REQUEST['newMonitor']['Height'];
                    dbQuery( "insert into Zones set MonitorId = ".dbEscape($_REQUEST['mid']).", Name = 'All', Type = 'Active', Units = 'Percent', NumCoords = 4, Coords = '".sprintf( "%d,%d %d,%d %d,%d %d,%d", 0, 0, $_REQUEST['newMonitor']['Width']-1, 0, $_REQUEST['newMonitor']['Width']-1, $_REQUEST['newMonitor']['Height']-1, 0, $_REQUEST['newMonitor']['Height']-1 )."', Area = ".$zoneArea.", AlarmRGB = 0xff0000, CheckMethod = 'Blobs', MinPixelThreshold = 25, MinAlarmPixels = ".intval(($zoneArea*3)/100).", MaxAlarmPixels = ".intval(($zoneArea*75)/100).", FilterX = 3, FilterY = 3, MinFilterPixels = ".intval(($zoneArea*3)/100).", MaxFilterPixels = ".intval(($zoneArea*75)/100).", MinBlobPixels = ".intval(($zoneArea*2)/100).", MinBlobs = 1" );
                    //$_REQUEST['view'] = 'none';
                    mkdir( ZM_DIR_EVENTS."/".$_REQUEST['mid'], 0755 );
                    chdir( ZM_DIR_EVENTS );
                    symlink( $_REQUEST['mid'], $_REQUEST['newMonitor']['Name'] );
                    chdir( ".." );
                }
                $GLOBALS['restart'] = true;
            }

            if ( ZM_OPT_X10 )
            {
                $x10Changes = getFormChanges( $x10Monitor, $_REQUEST['newX10Monitor'] );

                if ( count( $x10Changes ) )
                {
                    if ( $x10Monitor && isset($_REQUEST['newX10Monitor']) )
                    {
                        dbQuery( "update TriggersX10 set ".implode( ", ", $x10Changes )." where MonitorId = '".dbEscape($_REQUEST['mid'])."'" );
                    }
                    elseif ( !$user['MonitorIds'] )
                    {
                        if ( !$x10Monitor )
                        {
                            dbQuery( "insert into TriggersX10 set MonitorId = '".dbEscape($_REQUEST['mid'])."', ".implode( ", ", $x10Changes ) );
                        }
                        else
                        {
                            dbQuery( "delete from TriggersX10 where MonitorId = '".dbEscape($_REQUEST['mid'])."'" );
                        }
                    }
                    $GLOBALS['restart'] = true;
                }
            }

            if ( $GLOBALS['restart'] )
            {
                $monitor = dbFetchOne( "select * from Monitors where Id = '".dbEscape($_REQUEST['mid'])."'" );
                fixDevices();
                //if ( $cookies )
                    //session_write_close();
                if ( daemonCheck() )
                {
                    zmcControl( $monitor, "restart" );
                    zmaControl( $monitor, "restart" );
                }
                //daemonControl( 'restart', 'zmwatch.pl' );
                $GLOBALS['refreshParent'] = true;
            }
            $_REQUEST['view'] = 'none';
        }
        if ( $_REQUEST['action'] == "delete" )
        {
            if ( isset($_REQUEST['markMids']) && !$user['MonitorIds'] )
            {
                foreach( $_REQUEST['markMids'] as $markMid )
                {
                    if ( canEdit( 'Monitors', $markMid ) )
                    {
                        zmaControl( $monitor, "stop" );
                        zmcControl( $monitor, "stop" );

                        $sql = "select * from Monitors where Id = '".dbEscape($markMid)."'";
                        if ( !($monitor = dbFetchOne( $sql )) )
                            continue;

                        $sql = "select Id from Events where MonitorId = '".dbEscape($markMid)."'";
                        $markEids = dbFetchAll( $sql, 'Id' );
                        foreach( $markEids as $markEid )
                            deleteEvent( $markEid );

                        unlink( ZM_DIR_EVENTS."/".$monitor['Name'] );
                        system( "rm -rf ".ZM_DIR_EVENTS."/".$monitor['Id'] );

                        dbQuery( "delete from Zones where MonitorId = '".dbEscape($markMid)."'" );
                        if ( ZM_OPT_X10 )
                            dbQuery( "delete from TriggersX10 where MonitorId = '".dbEscape($markMid)."'" );
                        dbQuery( "delete from Monitors where Id = '".dbEscape($markMid)."'" );

                        fixSequences();
                    }
                }
            }
        }
    }

    // Device view actions
    if ( canEdit( 'Devices' ) )
    {
        if ( $_REQUEST['action'] == "device" )
        {
            if ( !empty($_REQUEST['command']) )
            {
                setDeviceStatusX10( $_REQUEST['key'], $_REQUEST['command'] );
            }
            elseif ( isset( $_REQUEST['newDevice'] ) )
            {
                if ( isset($_REQUEST['did']) )
                {
                    dbQuery( "update Devices set Name = '".dbEscape($_REQUEST['newDevice']['Name'])."', KeyString = '".dbEscape($_REQUEST['newDevice']['KeyString'])."' where Id = '".dbEscape($_REQUEST['did'])."'" );
                }
                else
                {
                    dbQuery( "insert into Devices set Name = '".dbEscape($_REQUEST['newDevice']['Name'])."', KeyString = '".dbEscape($_REQUEST['newDevice']['KeyString'])."'" );
                }
                $GLOBALS['refreshParent'] = true;
                $_REQUEST['view'] = 'none';
            }
        }
        elseif ( $_REQUEST['action'] == "delete" )
        {
            if ( isset($_REQUEST['markDids']) )
            {
                foreach( $_REQUEST['markDids'] as $markDid )
                {
                    dbQuery( "delete from Devices where Id = '".dbEscape($markDid)."'" );
                    $GLOBALS['refreshParent'] = true;
                }
            }
        }
    }

    // System view actions
    if ( canView( 'System' ) )
    {
        if ( $_REQUEST['action'] == "setgroup" )
        {
            if ( !empty($_REQUEST['gid']) )
            {
                setcookie( "zmGroup", $_REQUEST['gid'], time()+3600*24*30*12*10 );
            }
            else
            {
                setcookie( "zmGroup", "", time()-3600*24*2 );
            }
            $GLOBALS['refreshParent'] = true;
        }
    }

    // System edit actions
    if ( canEdit( 'System' ) )
    {
        if ( $_REQUEST['action'] == "version" && isset($option) )
        {
            switch( $option )
            {
                case 'go' :
                {
                    // Ignore this, the caller will open the page itself
                    break;
                }
                case 'ignore' :
                {
                    dbQuery( "update Config set Value = '".ZM_DYN_LAST_VERSION."' where Name = 'ZM_DYN_CURR_VERSION'" );
                    break;
                }
                case 'hour' :
                case 'day' :
                case 'week' :
                {
                    $nextReminder = time();
                    if ( $option == 'hour' )
                    {
                        $nextReminder += 60*60;
                    }
                    elseif ( $option == 'day' )
                    {
                        $nextReminder += 24*60*60;
                    }
                    elseif ( $option == 'week' )
                    {
                        $nextReminder += 7*24*60*60;
                    }
                    dbQuery( "update Config set Value = '".$nextReminder."' where Name = 'ZM_DYN_NEXT_REMINDER'" );
                    break;
                }
                case 'never' :
                {
                    dbQuery( "update Config set Value = '0' where Name = 'ZM_CHECK_FOR_UPDATES'" );
                    break;
                }
            }
        }
        if ( $_REQUEST['action'] == "donate" && isset($option) )
        {
            switch( $option )
            {
                case 'go' :
                {
                    // Ignore this, the caller will open the page itself
                    break;
                }
                case 'hour' :
                case 'day' :
                case 'week' :
                case 'month' :
                {
                    $nextReminder = time();
                    if ( $option == 'hour' )
                    {
                        $nextReminder += 60*60;
                    }
                    elseif ( $option == 'day' )
                    {
                        $nextReminder += 24*60*60;
                    }
                    elseif ( $option == 'week' )
                    {
                        $nextReminder += 7*24*60*60;
                    }
                    elseif ( $option == 'month' )
                    {
                        $nextReminder += 30*24*60*60;
                    }
                    dbQuery( "update Config set Value = '".$nextReminder."' where Name = 'ZM_DYN_DONATE_REMINDER_TIME'" );
                    break;
                }
                case 'never' :
                case 'already' :
                {
                    dbQuery( "update Config set Value = '0' where Name = 'ZM_DYN_SHOW_DONATE_REMINDER'" );
                    break;
                }
            }
        }
        if ( $_REQUEST['action'] == "options" && isset( $_REQUEST['tab'] ) )
        {
            $configCat = $configCats[$_REQUEST['tab']];
            $changed = false;
            foreach ( $configCat as $name=>$value )
            {
                unset( $newValue );
                if ( $value['Type'] == "boolean" && empty($_REQUEST['newConfig'][$name]) )
                     $newValue = 0;
                elseif ( isset($_REQUEST['newConfig'][$name]) )
                     $newValue = preg_replace( "/\r\n/", "\n", stripslashes( $_REQUEST['newConfig'][$name] ) );

                if ( isset($newValue) && ($newValue != $value['Value']) )
                {
                    dbQuery( "update Config set Value = '".$newValue."' where Name = '".$name."'" );
                    $changed = true;
                }
            }
            if ( $changed )
            {
                switch( $_REQUEST['tab'] )
                {
                    case "system" :
                    case "config" :
                    case "paths" :
                        $GLOBALS['restart'] = true;
                        break;
                    case "web" :
                    case "tools" :
                        break;
                    case "debug" :
                    case "network" :
                    case "mail" :
                    case "ftp" :
                        $GLOBALS['restart'] = true;
                        break;
                    case "highband" :
                    case "medband" :
                    case "lowband" :
                    case "phoneband" :
                        break;
                }
            }
            loadConfig( false );
        }
        elseif ( $_REQUEST['action'] == "user" )
        {
            if ( !empty($_REQUEST['uid']) )
                $dbUser = dbFetchOne( "select * from Users where Id = '".dbEscape($_REQUEST['uid'])."'" );
            else
                $dbUser = array();

            $types = array();
            $changes = getFormChanges( $dbUser, $_REQUEST['newUser'], $types );

            if ( $_REQUEST['newUser']['Password'] )
                $changes['Password'] = "Password = password('".dbEscape($_REQUEST['newUser']['Password'])."')";
            else
                unset( $changes['Password'] );

            if ( count( $changes ) )
            {
                if ( !empty($_REQUEST['uid']) )
                {
                    $sql = "update Users set ".implode( ", ", $changes )." where Id = '".dbEscape($_REQUEST['uid'])."'";
                }
                else
                {
                    $sql = "insert into Users set ".implode( ", ", $changes );
                }
                dbQuery( $sql );
                $GLOBALS['refreshParent'] = true;
                if ( $dbUser['Username'] == $user['Username'] )
                    userLogin( $dbUser['Username'], $dbUser['Password'] );
            }
            $_REQUEST['view'] = 'none';
        }
        elseif ( $_REQUEST['action'] == "state" )
        {
            if ( !empty($_REQUEST['runState']) )
            {
                //if ( $cookies ) session_write_close();
                packageControl( $_REQUEST['runState'] );
                $GLOBALS['refreshParent'] = true;
            }
        }
        elseif ( $_REQUEST['action'] == "save" )
        {
            if ( !empty($_REQUEST['runState']) || !empty($_REQUEST['newState']) )
            {
                $sql = "select Id,Function,Enabled from Monitors order by Id";
                $definitions = array();
                foreach( dbFetchAll( $sql ) as $monitor )
                {
                    $definitions[] = $monitor['Id'].":".$monitor['Function'].":".$monitor['Enabled'];
                }
                $definition = join( ',', $definitions );
                if ( $_REQUEST['newState'] )
                    $_REQUEST['runState'] = $_REQUEST['newState'];
                dbQuery( "replace into States set Name = '".dbEscape($_REQUEST['runState'])."', Definition = '".dbEscape($definition)."'" );
            }
        }
        elseif ( $_REQUEST['action'] == "group" )
        {
            if ( !empty($_REQUEST['gid']) )
            {
                $sql = "update Groups set Name = '".dbEscape($_REQUEST['newGroup']['Name'])."', MonitorIds = '".dbEscape(join(',',$_REQUEST['newGroup']['MonitorIds']))."' where Id = '".dbEscape($_REQUEST['gid'])."'";
            }
            else
            {
                $sql = "insert into Groups set Name = '".dbEscape($_REQUEST['newGroup']['Name'])."', MonitorIds = '".dbEscape(join(',',$_REQUEST['newGroup']['MonitorIds']))."'";
            }
            dbQuery( $sql );
            $GLOBALS['refreshParent'] = true;
            $_REQUEST['view'] = 'none';
        }
        elseif ( $_REQUEST['action'] == "delete" )
        {
            if ( isset($_REQUEST['runState']) )
                dbQuery( "delete from States where Name = '".dbEscape($_REQUEST['runState'])."'" );

            if ( isset($_REQUEST['markUids']) )
            {
                foreach( $_REQUEST['markUids'] as $markUid )
                    dbQuery( "delete from Users where Id = '".dbEscape($markUid)."'" );
                if ( $markUid == $user['Id'] )
                    userLogout();
            }
            if ( !empty($_REQUEST['gid']) )
            {
                dbQuery( "delete from Groups where Id = '".dbEscape($_REQUEST['gid'])."'" );
                if ( $_REQUEST['gid'] == $_COOKIE['zmGroup'] )
                {
                    unset( $_COOKIE['zmGroup'] );
                    setcookie( "zmGroup", "", time()-3600*24*2 );
                    $GLOBALS['refreshParent'] = true;
                }
            }
        }
    }
    else
    {
        if ( ZM_USER_SELF_EDIT && $_REQUEST['action'] == "user" )
        {
            $uid = $user['Id'];

            $dbUser = dbFetchOne( "select Id, Password, Language from Users where Id = '".dbEscape($uid)."'" );

            $types = array();
            $changes = getFormChanges( $dbUser, $_REQUEST['newUser'], $types );

            if ( $_REQUEST['newUser']['Password'] )
                $changes['Password'] = "Password = password('".$_REQUEST['newUser']['Password']."')";
            else
                unset( $changes['Password'] );
            if ( count( $changes ) )
            {
                $sql = "update Users set ".implode( ", ", $changes )." where Id = '".dbEscape($uid)."'";
                dbQuery( $sql );
                $GLOBALS['refreshParent'] = true;
            }
            $_REQUEST['view'] = 'none';
        }
    }

    if ( $_REQUEST['action'] == "reset" )
    {
        $_SESSION['zmEventResetTime'] = strftime( STRF_FMT_DATETIME_DB );
        setcookie( "zmEventResetTime", $_SESSION['zmEventResetTime'], time()+3600*24*30*12*10 );
        //if ( $cookies ) session_write_close();
    }
}

?>
