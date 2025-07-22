<?php
//
// ZoneMinder HTML configuration file, $Date$, $Revision$
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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

$RTSP2WebTypes = array(
  'HLS' => 'HLS',
  'MSE' => 'MSE',
  'WebRTC' => 'WebRTC',
);
$rates = array(
  -1600 => '-16x',
  -1000 => '-10x',
  -500  => '-5x',
  -200  => '-2x',
  -100  => '-1x',
  -50   => '-1/2x',
  -25   => '-1/4x',
  0     => translate('Stop'),
  25    => '1/4x',
  50    => '1/2x',
  100   => '1x',
  200   => '2x',
  500   => '5x',
  1000  => '10x',
  1600  => '16x', // Max that Chrome will support
);

$scales = array(
  # We use 0 instead of words because we are saving this in the monitor
  # and use this array to populate the default scale option
  '0' => translate('Auto'),
  #  '400' => '4x',
  #  '300' => '3x',
  #  '200' => '2x',
  #  '150' => '1.5x',
  '100' => translate('Actual'),
  #  '75' => '3/4x',
  #  '50' => '1/2x',
  #  '33' => '1/3x',
  #  '25' => '1/4x',
  #  '12.5' => '1/8x',
  'fit_to_width' => translate('Fit to width'),
  '480px' => translate('Max 480px'),
  '640px' => translate('Max 640px'),
  '800px' => translate('Max 800px'),
  '1024px' => translate('Max 1024px'),
  '1280px' => translate('Max 1280px'),
  '1600px' => translate('Max 1600px'),
);

$streamQuality = array(
  # In %
  '+50' => '+50%',
  '+40' => '+40%',
  '+30' => '+30%',
  '+20' => '+20%',
  '+10' => '+10%',
  '0' => translate('Optimal'),
  '-10' => '-10%',
  '-20' => '-20%',
  '-30' => '-30%',
  '-40' => '-40%',
  '-50' => '-50%',
);

if ( isset($_REQUEST['view']) && ($_REQUEST['view'] == 'montage') ) {
  unset($scales['auto']); //Remove auto on montage, use everywhere else
}

$bandwidth_options = array(
    'high' => translate('High'),
    'medium' => translate('Medium'),
    'low' => translate('Low')
);

switch ( $_COOKIE['zmBandwidth'] ) {
    case 'high' : {
        define( 'ZM_WEB_REFRESH_MAIN', ZM_WEB_H_REFRESH_MAIN );         // How often (in seconds) the main console window refreshes
        define( 'ZM_WEB_REFRESH_NAVBAR', ZM_WEB_H_REFRESH_NAVBAR );     // How often (in seconds) the nav header refreshes
        define( 'ZM_WEB_REFRESH_CYCLE', ZM_WEB_H_REFRESH_CYCLE );       // How often the cycle watch windows swaps to the next monitor
        define( 'ZM_WEB_REFRESH_IMAGE', ZM_WEB_H_REFRESH_IMAGE );       // How often the watched image is refreshed (if not streaming)
        define( 'ZM_WEB_REFRESH_STATUS', ZM_WEB_H_REFRESH_STATUS );     // How often the little status frame refreshes itself in the watch window
        define( 'ZM_WEB_REFRESH_EVENTS', ZM_WEB_H_REFRESH_EVENTS );     // How often the event listing is refreshed in the watch window, only for recent events
        define( 'ZM_WEB_CAN_STREAM', ZM_WEB_H_CAN_STREAM );             // Override the automatic detection of browser streaming capability
        define( 'ZM_WEB_STREAM_METHOD', ZM_WEB_H_STREAM_METHOD );       // Which method should be used to send video streams to your browser
        define( 'ZM_WEB_DEFAULT_SCALE', ZM_WEB_H_DEFAULT_SCALE );       // What the default scaling factor applied to 'live' or 'event' views is (%)
        define( 'ZM_WEB_DEFAULT_RATE', ZM_WEB_H_DEFAULT_RATE );         // What the default replay rate factor applied to 'event' views is (%)
        define( 'ZM_WEB_VIDEO_BITRATE', ZM_WEB_H_VIDEO_BITRATE );       // What the bitrate of any streamed video should be
        define( 'ZM_WEB_VIDEO_MAXFPS', ZM_WEB_H_VIDEO_MAXFPS );         // What the maximum frame rate of any streamed video should be
        define( 'ZM_WEB_SCALE_THUMBS', ZM_WEB_H_SCALE_THUMBS );         // Image scaling for thumbnails, bandwidth versus cpu in rescaling
        define( 'ZM_WEB_EVENTS_VIEW', ZM_WEB_H_EVENTS_VIEW );           // What the default view of multiple events should be.
        define( 'ZM_WEB_SHOW_PROGRESS', ZM_WEB_H_SHOW_PROGRESS );       // Whether to show the progress of replay in event view.
        define( 'ZM_WEB_AJAX_TIMEOUT', ZM_WEB_H_AJAX_TIMEOUT );         // Timeout to use for Ajax requests, no timeout used if unset
        define( 'ZM_WEB_VIEWING_TIMEOUT', defined('ZM_WEB_H_VIEWING_TIMEOUT') ? ZM_WEB_H_VIEWING_TIMEOUT : 0 );
        break;
    } case 'medium' : {
        define( 'ZM_WEB_REFRESH_MAIN', ZM_WEB_M_REFRESH_MAIN );         // How often (in seconds) the main console window refreshes
        define( 'ZM_WEB_REFRESH_NAVBAR', ZM_WEB_M_REFRESH_NAVBAR );     // How often (in seconds) the nav header refreshes
        define( 'ZM_WEB_REFRESH_CYCLE', ZM_WEB_M_REFRESH_CYCLE );       // How often the cycle watch windows swaps to the next monitor
        define( 'ZM_WEB_REFRESH_IMAGE', ZM_WEB_M_REFRESH_IMAGE );       // How often the watched image is refreshed (if not streaming)
        define( 'ZM_WEB_REFRESH_STATUS', ZM_WEB_M_REFRESH_STATUS );     // How often the little status frame refreshes itself in the watch window
        define( 'ZM_WEB_REFRESH_EVENTS', ZM_WEB_M_REFRESH_EVENTS );     // How often the event listing is refreshed in the watch window, only for recent events
        define( 'ZM_WEB_CAN_STREAM', ZM_WEB_M_CAN_STREAM );             // Override the automatic detection of browser streaming capability
        define( 'ZM_WEB_STREAM_METHOD', ZM_WEB_M_STREAM_METHOD );       // Which method should be used to send video streams to your browser
        define( 'ZM_WEB_DEFAULT_SCALE', ZM_WEB_M_DEFAULT_SCALE );       // What the default scaling factor applied to 'live' or 'event' views is (%)
        define( 'ZM_WEB_DEFAULT_RATE', ZM_WEB_M_DEFAULT_RATE );         // What the default replay rate factor applied to 'event' views is (%)
        define( 'ZM_WEB_VIDEO_BITRATE', ZM_WEB_M_VIDEO_BITRATE );       // What the bitrate of any streamed video should be
        define( 'ZM_WEB_VIDEO_MAXFPS', ZM_WEB_M_VIDEO_MAXFPS );         // What the maximum frame rate of any streamed video should be
        define( 'ZM_WEB_SCALE_THUMBS', ZM_WEB_M_SCALE_THUMBS );         // Image scaling for thumbnails, bandwidth versus cpu in rescaling
        define( 'ZM_WEB_EVENTS_VIEW', ZM_WEB_M_EVENTS_VIEW );           // What the default view of multiple events should be.
        define( 'ZM_WEB_SHOW_PROGRESS', ZM_WEB_M_SHOW_PROGRESS );       // Whether to show the progress of replay in event view.
        define( 'ZM_WEB_AJAX_TIMEOUT', ZM_WEB_M_AJAX_TIMEOUT );         // Timeout to use for Ajax requests, no timeout used if unset
        define( 'ZM_WEB_VIEWING_TIMEOUT', defined('ZM_WEB_M_VIEWING_TIMEOUT') ? ZM_WEB_M_VIEWING_TIMEOUT : 0 );
        break;
    } case 'low' : {
        define( 'ZM_WEB_REFRESH_MAIN', ZM_WEB_L_REFRESH_MAIN );         // How often (in seconds) the main console window refreshes
        define( 'ZM_WEB_REFRESH_NAVBAR', ZM_WEB_L_REFRESH_NAVBAR );     // How often (in seconds) the nav header refreshes
        define( 'ZM_WEB_REFRESH_CYCLE', ZM_WEB_L_REFRESH_CYCLE );       // How often the cycle watch windows swaps to the next monitor
        define( 'ZM_WEB_REFRESH_IMAGE', ZM_WEB_L_REFRESH_IMAGE );       // How often the watched image is refreshed (if not streaming)
        define( 'ZM_WEB_REFRESH_STATUS', ZM_WEB_L_REFRESH_STATUS );     // How often the little status frame refreshes itself in the watch window
        define( 'ZM_WEB_REFRESH_EVENTS', ZM_WEB_L_REFRESH_EVENTS );     // How often the event listing is refreshed in the watch window, only for recent events
        define( 'ZM_WEB_CAN_STREAM', ZM_WEB_L_CAN_STREAM );             // Override the automatic detection of browser streaming capability
        define( 'ZM_WEB_STREAM_METHOD', ZM_WEB_L_STREAM_METHOD );       // Which method should be used to send video streams to your browser
        define( 'ZM_WEB_DEFAULT_SCALE', ZM_WEB_L_DEFAULT_SCALE );       // What the default scaling factor applied to 'live' or 'event' views is (%)
        define( 'ZM_WEB_DEFAULT_RATE', ZM_WEB_L_DEFAULT_RATE );         // What the default replay rate factor applied to 'event' views is (%)
        define( 'ZM_WEB_VIDEO_BITRATE', ZM_WEB_L_VIDEO_BITRATE );       // What the bitrate of any streamed video should be
        define( 'ZM_WEB_VIDEO_MAXFPS', ZM_WEB_L_VIDEO_MAXFPS );         // What the maximum frame rate of any streamed video should be
        define( 'ZM_WEB_SCALE_THUMBS', ZM_WEB_L_SCALE_THUMBS );         // Image scaling for thumbnails, bandwidth versus cpu in rescaling
        define( 'ZM_WEB_EVENTS_VIEW', ZM_WEB_L_EVENTS_VIEW );           // What the default view of multiple events should be.
        define( 'ZM_WEB_SHOW_PROGRESS', ZM_WEB_L_SHOW_PROGRESS );       // Whether to show the progress of replay in event view.
        define( 'ZM_WEB_AJAX_TIMEOUT', ZM_WEB_L_AJAX_TIMEOUT );         // Timeout to use for Ajax requests, no timeout used if unset
        define( 'ZM_WEB_VIEWING_TIMEOUT', defined('ZM_WEB_L_VIEWING_TIMEOUT') ? ZM_WEB_L_VIEWING_TIMEOUT : 0 );
        break;
    }
}

?>
