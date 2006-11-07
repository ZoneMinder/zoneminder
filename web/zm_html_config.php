<?php
//
// ZoneMinder HTML configuration file, $Date$, $Revision$
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

$bw_array = array(
	"high"=>$zmSlangHigh,
	"medium"=>$zmSlangMedium,
	"low"=>$zmSlangLow
);

switch ( $bandwidth )
{
	case "high" :
	{
		define( "ZM_WEB_REFRESH_MAIN", ZM_WEB_H_REFRESH_MAIN );			// How often (in seconds) the main console window refreshes
		define( "ZM_WEB_REFRESH_CYCLE", ZM_WEB_H_REFRESH_CYCLE );		// How often the cycle watch windows swaps to the next monitor
		define( "ZM_WEB_REFRESH_IMAGE", ZM_WEB_H_REFRESH_IMAGE );		// How often the watched image is refreshed (if not streaming)
		define( "ZM_WEB_REFRESH_STATUS", ZM_WEB_H_REFRESH_STATUS );		// How often the little status frame refreshes itself in the watch window
		define( "ZM_WEB_REFRESH_EVENTS", ZM_WEB_H_REFRESH_EVENTS );		// How often the event listing is refreshed in the watch window, only for recent events
		define( "ZM_WEB_DEFAULT_SCALE", ZM_WEB_H_DEFAULT_SCALE );		// What the default scaling factor applied to 'live' or 'event' views is (%)
		define( "ZM_WEB_DEFAULT_RATE", ZM_WEB_H_DEFAULT_RATE );			// What the default replay rate factor applied to 'event' views is (%)
		define( "ZM_WEB_VIDEO_BITRATE", ZM_WEB_H_VIDEO_BITRATE );		// What the bitrate of any streamed video should be
		define( "ZM_WEB_VIDEO_MAXFPS", ZM_WEB_H_VIDEO_MAXFPS );			// What the maximum frame rate of any streamed video should be
		define( "ZM_WEB_SCALE_THUMBS", ZM_WEB_H_SCALE_THUMBS );			// Image scaling for thumbnails, bandwidth versus cpu in rescaling
		define( "ZM_WEB_USE_STREAMS", ZM_WEB_H_USE_STREAMS );			// Whether to use streaming or stills for live and events views
		define( "ZM_WEB_EVENTS_VIEW", ZM_WEB_H_EVENTS_VIEW );			// What the default view of multiple events should be.
		define( "ZM_WEB_SHOW_PROGRESS", ZM_WEB_H_SHOW_PROGRESS );		// Whether to show the progress of replay in event view.
		break;
	}
	case "medium" : 
	{
		define( "ZM_WEB_REFRESH_MAIN", ZM_WEB_M_REFRESH_MAIN );			// How often (in seconds) the main console window refreshes
		define( "ZM_WEB_REFRESH_CYCLE", ZM_WEB_M_REFRESH_CYCLE );		// How often the cycle watch windows swaps to the next monitor
		define( "ZM_WEB_REFRESH_IMAGE", ZM_WEB_M_REFRESH_IMAGE );		// How often the watched image is refreshed (if not streaming)
		define( "ZM_WEB_REFRESH_STATUS", ZM_WEB_M_REFRESH_STATUS );		// How often the little status frame refreshes itself in the watch window
		define( "ZM_WEB_REFRESH_EVENTS", ZM_WEB_M_REFRESH_EVENTS );		// How often the event listing is refreshed in the watch window, only for recent events
		define( "ZM_WEB_DEFAULT_SCALE", ZM_WEB_M_DEFAULT_SCALE );		// What the default scaling factor applied to 'live' or 'event' views is (%)
		define( "ZM_WEB_DEFAULT_RATE", ZM_WEB_M_DEFAULT_RATE );			// What the default replay rate factor applied to 'event' views is (%)
		define( "ZM_WEB_VIDEO_BITRATE", ZM_WEB_M_VIDEO_BITRATE );		// What the bitrate of any streamed video should be
		define( "ZM_WEB_VIDEO_MAXFPS", ZM_WEB_M_VIDEO_MAXFPS );			// What the maximum frame rate of any streamed video should be
		define( "ZM_WEB_SCALE_THUMBS", ZM_WEB_M_SCALE_THUMBS );			// Image scaling for thumbnails, bandwidth versus cpu in rescaling
		define( "ZM_WEB_USE_STREAMS", ZM_WEB_M_USE_STREAMS );			// Whether to use streaming or stills for live and events views
		define( "ZM_WEB_EVENTS_VIEW", ZM_WEB_M_EVENTS_VIEW );			// What the default view of multiple events should be.
		define( "ZM_WEB_SHOW_PROGRESS", ZM_WEB_M_SHOW_PROGRESS );		// Whether to show the progress of replay in event view.
		break;
	}
	case "low" :
	{
		define( "ZM_WEB_REFRESH_MAIN", ZM_WEB_L_REFRESH_MAIN );			// How often (in seconds) the main console window refreshes
		define( "ZM_WEB_REFRESH_CYCLE", ZM_WEB_L_REFRESH_CYCLE );		// How often the cycle watch windows swaps to the next monitor
		define( "ZM_WEB_REFRESH_IMAGE", ZM_WEB_L_REFRESH_IMAGE );		// How often the watched image is refreshed (if not streaming)
		define( "ZM_WEB_REFRESH_STATUS", ZM_WEB_L_REFRESH_STATUS );		// How often the little status frame refreshes itself in the watch window
		define( "ZM_WEB_REFRESH_EVENTS", ZM_WEB_L_REFRESH_EVENTS );		// How often the event listing is refreshed in the watch window, only for recent events
		define( "ZM_WEB_DEFAULT_SCALE", ZM_WEB_L_DEFAULT_SCALE );		// What the default scaling factor applied to 'live' or 'event' views is (%)
		define( "ZM_WEB_DEFAULT_RATE", ZM_WEB_L_DEFAULT_RATE );			// What the default replay rate factor applied to 'event' views is (%)
		define( "ZM_WEB_VIDEO_BITRATE", ZM_WEB_L_VIDEO_BITRATE );		// What the bitrate of any streamed video should be
		define( "ZM_WEB_VIDEO_MAXFPS", ZM_WEB_L_VIDEO_MAXFPS );			// What the maximum frame rate of any streamed video should be
		define( "ZM_WEB_SCALE_THUMBS", ZM_WEB_L_SCALE_THUMBS );			// Image scaling for thumbnails, bandwidth versus cpu in rescaling
		define( "ZM_WEB_USE_STREAMS", ZM_WEB_L_USE_STREAMS );			// Whether to use streaming or stills for live and events views
		define( "ZM_WEB_EVENTS_VIEW", ZM_WEB_L_EVENTS_VIEW );			// What the default view of multiple events should be.
		define( "ZM_WEB_SHOW_PROGRESS", ZM_WEB_L_SHOW_PROGRESS );		// Whether to show the progress of replay in event view.
		break;
	}
}

// Javascript window sizes
$jws = array( 
	'bandwidth' => array( 'w'=>200, 'h'=>90 ),
	'console' => array( 'w'=>750, 'h'=>312 ),
	'control' => array( 'w'=>380, 'h'=>480 ),
	'controlcaps' => array( 'w'=>700, 'h'=>320 ),
	'controlcap' => array( 'w'=>360, 'h'=>440 ),
	'cycle' => array( 'w'=>16, 'h'=>32 ),
	'device' => array( 'w'=>196, 'h'=>164 ),
	'donate' => array( 'w'=>500, 'h'=>280 ),
	'event' => array( 'w'=>96, 'h'=>168 ),
	'eventdetail' => array( 'w'=>400, 'h'=>220 ),
	'events' => array( 'w'=>720, 'h'=>480 ),
	'export' => array( 'w'=>400, 'h'=>340 ),
	'filter' => array( 'w'=>620, 'h'=>360 ),
	'filtersave' => array( 'w'=>560, 'h'=>220 ),
	'frames' => array( 'w'=>500, 'h'=>300 ),
	'function' => array( 'w'=>248, 'h'=>92 ),
	'group' => array( 'w'=>360, 'h'=>150 ),
	'groups' => array( 'w'=>400, 'h'=>220 ),
	'image' => array( 'w'=>48, 'h'=>80 ),
	'login' => array( 'w'=>720, 'h'=>480 ),
	'logout' => array( 'w'=>200, 'h'=>100 ),
	'monitor' => array( 'w'=>360, 'h'=>324 ),
	'monitorpreset' => array( 'w'=>400, 'h'=>200 ),
	'monitorselect' => array( 'w'=>160, 'h'=>200 ),
	'montage' => array( 'w'=>10, 'h'=>20 ),
	'optionhelp' => array( 'w'=>320, 'h'=>284 ),
	'options' => array( 'w'=>780, 'h'=>540 ),
	'preset' => array( 'w'=>400, 'h'=>90 ),
	'restarting' => array( 'w'=>250, 'h'=>150 ),
	'settings' => array( 'w'=>200, 'h'=>225 ),
	'state' => array( 'w'=>300, 'h'=>120 ),
	'stats' => array( 'w'=>740, 'h'=>200 ),
	'timeline' => array( 'w'=>760, 'h'=>500 ),
	'user' => array( 'w'=>280, 'h'=>372 ),
	'version' => array( 'w'=>320, 'h'=>140 ),
	'video' => array( 'w'=>100, 'h'=>80 ),
	'watch' => array( 'w'=>96, 'h'=>384 ),
	'zone' => array( 'w'=>400, 'h'=>450 ),
	'zones' => array( 'w'=>72, 'h'=>232 ),
);

?>
