<?php
//
// ZoneMinder HTML interface file, $Date: 2008-07-08 16:06:45 +0100 (Tue, 08 Jul 2008) $, $Revision: 2484 $
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

$_COOKIE['zmBandwidth'] = "phone";

//ini_set( "magic_quotes_gpc", "Off" );

// Uncomment if there are language overrides
//if ( $skinLangFile = loadLanguage( ZM_SKIN_PATH ) )
    //require_once( $skinLangFile );

if ( isset($_GET['devWidth']) )
{
    $device['width'] = $_GET['devWidth'];
}
if ( isset($_GET['devHeight']) )
{
    $device['height'] = $_GET['devHeight'];
}

if ( isset($device) )
    $_SESSION['device'] = $device;

$wurflFile = "wurfl/wurfl_class.php";
if ( file_exists( $wurflFile ) )
{
    require_once( $wurflFile );
    $wurfl = new wurfl_class( $wurfl, $wurfl_agents );
    // Set the user agent
    $wurfl->GetDeviceCapabilitiesFromAgent($_SERVER['HTTP_USER_AGENT']);

    //print_r( $wurfl->wurfl_agent );
    if ( $wurfl->wurfl_agent )
    {
        if ( $wurfl->getDeviceCapability( 'html_wi_oma_xhtmlmp_1_0' ) )
        {
            $device['width'] = $wurfl->getDeviceCapability( 'resolution_width' );
            $device['height'] = $wurfl->getDeviceCapability( 'resolution_height' );
        }
    }
}
else
{
    // This is an example of using fixed device strings to just match your phone etc
    $devices = array(
        array( 'name'=>"Motorola V600", 'ua_match'=>"MOT-V600", 'skin'=>"mobile", 'cookies'=>false, 'width'=>176, 'height'=>220 ),
    );

    foreach ( $devices as $tempDevice )
    {
        if ( preg_match( '/'.$tempDevice['ua_match'].'/', $_SERVER['HTTP_USER_AGENT'] ) )
        {
            $skin = $tempDevice['skin'];
            $cookies = $tempDevice['cookies'];
            break;
        }
    }
}

foreach ( getSkinIncludes( 'includes/config.php' ) as $includeFile )
    require_once $includeFile;

foreach ( getSkinIncludes( 'includes/functions.php' ) as $includeFile )
    require_once $includeFile;

if ( empty($view) )
    $view = isset($user)?'console':'login';

if ( !isset($user) && ZM_OPT_USE_AUTH )
{
    if ( ZM_AUTH_TYPE == "remote" && !empty( $_SERVER['REMOTE_USER'] ) )
    {
        $view = "postlogin";
        $action = "login";
        $_REQUEST['username'] = $_SERVER['REMOTE_USER'];
    }
    else
    {
        $view = "login";
    }
}

// If there are additional actions
foreach ( getSkinIncludes( 'includes/actions.php' ) as $includeFile )
    require_once $includeFile; 

?>
