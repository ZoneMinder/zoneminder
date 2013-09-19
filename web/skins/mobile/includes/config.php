<?php
//
// ZoneMinder xHTML configuration file, $Date$, $Revision$
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

define( 'DEVICE_WIDTH', 240 );                          // Default device width for phones and handhelds
define( 'DEVICE_HEIGHT', 320 );                         // Default device height for phones and handhelds
define( 'DEVICE_LINES', 10 );                           // Default device lines for phones and handhelds

$rates = array(
    "5000" => "50x",
    "2000" => "20x",
    "500" => "5x",
    "200" => "2x",
    "100" => $SLANG['Real'],
    "50" => "1/2x",
);

$scales = array(
    "400" => "4x",
    "300" => "3x",
    "200" => "2x",
    "150" => "1.5x",
    "100" => $SLANG['Actual'],
    "75" => "3/4x",
    "50" => "1/2x",
    "33" => "1/3x",
    "25" => "1/4x",
);

switch ( $_COOKIE['zmBandwidth'] )
{
    case "phone" : // Very incomplete at present
    {
        define( "ZM_WEB_CAN_STREAM", ZM_WEB_P_CAN_STREAM );             // Override the automatic detection of browser streaming capability
        define( "ZM_WEB_STREAM_METHOD", ZM_WEB_P_STREAM_METHOD );       // Which method should be used to send video streams to your brow
        define( "ZM_WEB_DEFAULT_SCALE", ZM_WEB_P_DEFAULT_SCALE );       // What the default scaling factor applied to 'live' or 'event' views is (%)
        define( "ZM_WEB_DEFAULT_RATE", ZM_WEB_P_DEFAULT_RATE );         // What the default replay rate factor applied to 'event' views is (%)
        define( "ZM_WEB_VIDEO_BITRATE", ZM_WEB_P_VIDEO_BITRATE );       // What the bitrate of any streamed video should be
        define( "ZM_WEB_VIDEO_MAXFPS", ZM_WEB_P_VIDEO_MAXFPS );         // What the maximum frame rate of any streamed video should be
        define( "ZM_WEB_SCALE_THUMBS", ZM_WEB_P_SCALE_THUMBS );         // Image scaling for thumbnails, bandwidth versus cpu in rescaling
        define( "ZM_WEB_AJAX_TIMEOUT", ZM_WEB_P_AJAX_TIMEOUT );         // Timeout to use for Ajax requests, no timeout used if unset

        break;
    }
}

?>
