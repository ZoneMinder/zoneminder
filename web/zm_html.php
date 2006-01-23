<?php
//
// ZoneMinder HTML interface file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

if ( !$bandwidth )
{
	$bandwidth = "low";
}

//ini_set( "magic_quotes_gpc", "Off" );

require_once( 'zm_config.php' );

if ( ZM_OPT_USE_AUTH )
{
	session_start();
	if ( isset( $_SESSION['user'] ) )
	{
		$user = $_SESSION['user'];
	}
	else
	{
		unset( $user );
	}
}
else
{
	$user = array(
		"Username"=>"admin",
		"Password"=>"",
		"Language"=>"",
		"Enabled"=>1,
		"Stream"=>'View',
		"Events"=>'Edit',
		"Control"=>'Edit',
		"Monitors"=>'Edit',
		"System"=>'Edit',
	);
}

require_once( 'zm_lang.php' );

$bw_array = array(
	"high"=>$zmSlangHigh,
	"medium"=>$zmSlangMedium,
	"low"=>$zmSlangLow
);

$rates = array(
	"10000" => "100x",
	"5000" => "50x",
	"2500" => "25x",
	"1000" => "10x",
	"400" => "4x",
	"200" => "2x",
	"100" => $zmSlangReal,
	"50" => "1/2x",
	"25" => "1/4x",
);

$scales = array(
	"400" => "4x",
	"300" => "3x",
	"200" => "2x",
	"150" => "1.5x",
	"100" => $zmSlangActual,
	"75" => "3/4x",
	"50" => "1/2x",
	"33" => "1/3x",
	"25" => "1/4x",
);

if ( !isset($user) && ZM_OPT_USE_AUTH )
{
	if ( ZM_AUTH_TYPE == "remote" && !empty( $_SERVER['REMOTE_USER'] ) )
	{
		$view = "postlogin";
		$action = "login";
		$username = $_SERVER['REMOTE_USER'];
	}
}

require_once( 'zm_funcs.php' );
require_once( 'zm_actions.php' );

if ( !isset($user) )
{
	$view = "login";
}
else
{
	// Bandwidth Limiter
	if ( !empty($user['MaxBandwidth']) )
	{
		if ( $user['MaxBandwidth'] == "low" )
		{
			$bandwidth = "low";
		}
		elseif ( $user['MaxBandwidth'] == "medium" && $bandwidth == "high" )
		{
			$bandwidth = "medium";
		}
	}
}

if ( !isset($view) )
{
	$view = "console";
}

switch( $view )
{
	case "bandwidth" : 
	case "console" :
	case "control" :
	case "controlcap" :
	case "controlcaps" :
	case "controlmenu" :
	case "controlpanel" :
	case "controlpreset" :
	case "cycle" :
	case "donate" :
	case "event" :
	case "eventdetail" :
	case "events" :
	case "export" :
	case "filter" :
	case "filtersave" :
	case "frame" :
	case "frames" :
	case "function" :
	case "groups" :
	case "login" :
	case "logout" :
	case "monitor" :
	case "monitorpreset" :
	case "monitorselect" :
	case "montage" :
	case "montagefeed" :
	case "montageframe" :
	case "montagemenu" :
	case "montagestatus" :
	case "optionhelp" :
	case "options" :
	case "postlogin" :
	case "restarting" :
	case "settings" :
	case "siren" :
	case "state" :
	case "stats" :
	case "timeline" :
	case "user" :
	case "version" :
	case "video" :
	case "watch" :
	case "watchevents" :
	case "watchfeed" :
	case "watchmenu" :
	case "watchstatus" :
	case "zone" :
	case "zones" :
	case "blank" :
	case "none" :
	{
		require_once( "zm_".$format."_view_".$view.".php" );
		break;
	}
	default :
	{
		$view = "error";
	}
}

if ( $view == "error" )
{
	require_once( "zm_".$format."_view_".$view.".php" );
}
?>
