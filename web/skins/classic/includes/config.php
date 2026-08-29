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

$whatDisplay = array(
  'OnlyVideo' => translate('Only video'),
  'OnlyAudioVisualization' => translate('Only audio visualization'),
  'VideoAudioVisualization' => translate('Video and audio visualization'),
);

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

// The skin has one set of settings per bandwidth profile, named
// ZM_WEB_<H|M|L>_<SETTING>. Alias the selected profile's settings to the plain
// ZM_WEB_<SETTING> names the rest of the skin reads, so nothing downstream has
// to know which profile is active.
//
// This used to be a switch with one arm per profile, each repeating the whole
// list of define() calls. Deriving the names from the prefix keeps the profiles
// from drifting apart, and means an unrecognised profile can no longer leave
// every one of these constants undefined.
$bandwidth_prefixes = array('high' => 'H', 'medium' => 'M', 'low' => 'L');

$bandwidth_settings = array(
    'REFRESH_MAIN',      // How often (in seconds) the main console window refreshes
    'REFRESH_NAVBAR',    // How often (in seconds) the nav header refreshes
    'REFRESH_CYCLE',     // How often the cycle watch windows swaps to the next monitor
    'REFRESH_IMAGE',     // How often the watched image is refreshed (if not streaming)
    'REFRESH_STATUS',    // How often the little status frame refreshes itself in the watch window
    'REFRESH_EVENTS',    // How often the event listing is refreshed in the watch window, only for recent events
    'CAN_STREAM',        // Override the automatic detection of browser streaming capability
    'STREAM_METHOD',     // Which method should be used to send video streams to your browser
    'DEFAULT_SCALE',     // What the default scaling factor applied to 'live' or 'event' views is (%)
    'DEFAULT_RATE',      // What the default replay rate factor applied to 'event' views is (%)
    'VIDEO_BITRATE',     // What the bitrate of any streamed video should be
    'VIDEO_MAXFPS',      // What the maximum frame rate of any streamed video should be
    'SCALE_THUMBS',      // Image scaling for thumbnails, bandwidth versus cpu in rescaling
    'EVENTS_VIEW',       // What the default view of multiple events should be.
    'SHOW_PROGRESS',     // Whether to show the progress of replay in event view.
    'AJAX_TIMEOUT',      // Timeout to use for Ajax requests, no timeout used if unset
);

// Settings a profile may not have a value for, with what to use when it does
// not. Unlike the list above, a missing one of these is not an error.
$bandwidth_optional_settings = array(
    'REFRESH_LOGS' => 0,    // How often (in seconds) the listing is refreshed in the log window
    'VIEWING_TIMEOUT' => 0, // How long a view may go unwatched before it stops streaming
);

// skin.php validates the cookie before including this, so the profile is
// normally known. Fall back to the least demanding one if it ever is not,
// rather than leaving every alias below undefined.
$bandwidth_profile = $_COOKIE['zmBandwidth'] ?? '';
$bandwidth_prefix = $bandwidth_prefixes[$bandwidth_profile] ?? 'L';

foreach ( $bandwidth_settings as $setting ) {
  define('ZM_WEB_'.$setting, constant('ZM_WEB_'.$bandwidth_prefix.'_'.$setting));
}
foreach ( $bandwidth_optional_settings as $setting => $fallback ) {
  $source = 'ZM_WEB_'.$bandwidth_prefix.'_'.$setting;
  define('ZM_WEB_'.$setting, defined($source) ? constant($source) : $fallback);
}

?>
