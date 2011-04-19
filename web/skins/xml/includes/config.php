<?php
/* 
 * config.php is created by Jai Dhar, FPS-Tech, for use with eyeZm
 * iPhone application. This is not intended for use with any other applications,
 * although source-code is provided under GPL.
 *
 * For questions, please email jdhar@eyezm.com (http://www.eyezm.com)
 *
 */

/* Static defines, these shouldn't change */
define ( "ZM_EYEZM_PROTOCOL_VERSION", "2");
define ( "ZM_EYEZM_FEATURE_SET", "3");

/* Dynamic defines, check if they are already defined.
 * To change a particular parameter default value, without using the
 * Options Console, change the 2nd parameter of the define() block. */

/* Parm: ZM_EYEZM_EVENT_FPS: Sets the default FPS of the output videos for events */
if (!defined("ZM_EYEZM_EVENT_FPS")) define ( "ZM_EYEZM_EVENT_FPS", "10");
/* Parm: ZM_EYEZM_EVENT_VCODEC: Default video codec for generating event video. Can be mpeg4 or h264 */
if (!defined("ZM_EYEZM_EVENT_VCODEC")) define ( "ZM_EYEZM_EVENT_VCODEC", "mpeg4");
/* Parm: ZM_EYEZM_FEED_VCODEC: Default video codec of live feeds. Can be mjpeg or h264 */
if (!defined("ZM_EYEZM_FEED_VCODEC")) define ( "ZM_EYEZM_FEED_VCODEC", "mjpeg");
/* Parm: ZM_EYEZM_SEG_DURATION: H264 Live-streaming segment duration in seconds.
 * Increase to improve feed smooth-ness, but will increase feed latency */
if (!defined("ZM_EYEZM_SEG_DURATION")) define ( "ZM_EYEZM_SEG_DURATION", "3");
/* Parm: ZM_EYEZM_DEBUG: Set to 1 to enable XML Debugging */
if (!defined("ZM_EYEZM_DEBUG")) define ( "ZM_EYEZM_DEBUG", "0" );
/* Parm: ZM_EYEZM_H264_MAX_DURATION: Maximum duration in seconds allowed for viewing H264 Streams.
 * This is useful for systems that crash or stall when viewing H264 streams. After the timeout
 * expires, the H264 stream will be killed if it has not by the user already */
if (!defined("ZM_EYEZM_H264_MAX_DURATION")) define ( "ZM_EYEZM_H264_MAX_DURATION", "120" );
/* Parm: ZM_EYEZM_DEFAULT_BR: Default bitrate of H264 live-feed (when selected).
 * This parameter can be changed to anything FFMPEG supports. 64k is a good lower bound, and 392k
 * a good upper */
if (!defined("ZM_EYEZM_H264_DEFAULT_BR")) define ( "ZM_EYEZM_H264_DEFAULT_BR", "96k" );
/* Parm: ZM_EYEZM_H264_TIMEOUT: How long to wait for H264 stream to be created. Increase
 * this value for streams that take a while to create, or for slow systems that time-out frequently */
if (!defined("ZM_EYEZM_H264_TIMEOUT")) define ( "ZM_EYEZM_H264_TIMEOUT", "20" );
/* Parm: ZM_EYEZM_H264_DEFAULT_EVBR: Default bit-rate when creasing H264 Event videos */
if (!defined("ZM_EYEZM_H264_DEFAULT_EVBR")) define ( "ZM_EYEZM_H264_DEFAULT_EVBR", "128k" );
/* Logging defines */
/* Parm: ZM_EYEZM_LOG_TO_FILE: Set to 1 to log XML Debug output to a separate file, when
 * ZM_EYEZM_DEBUG is set to 1. If set to 0, XML Logging will be directed to Apache error log */
if (!defined("ZM_EYEZM_LOG_TO_FILE")) define ( "ZM_EYEZM_LOG_TO_FILE", "1" );
/* Parm: ZM_EYEZM_LOG_FILE: Path to filename when LOG_TO_FILE is enabled */
if (!defined("ZM_EYEZM_LOG_FILE")) define ( "ZM_EYEZM_LOG_FILE", "/tmp/zm_xml.log" );
/* Parm: How many lines to display when viewing log from eyeZm */
if (!defined("ZM_EYEZM_LOG_LINES")) define ( "ZM_EYEZM_LOG_LINES", "50" );

$rates = array(
    "10000" => "100x",
    "5000" => "50x",
    "2500" => "25x",
    "1000" => "10x",
    "400" => "4x",
    "200" => "2x",
    "100" => $SLANG['Real'],
    "50" => "1/2x",
    "25" => "1/4x",
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

$bwArray = array(
    "high" => $SLANG['High'],
    "medium" => $SLANG['Medium'],
    "low" => $SLANG['Low']
);

/* Check if ZM_WEB_L_CAN_STREAM and ZM_WEB_L_STREAM_METHOD are defined */
if (!defined("ZM_WEB_L_CAN_STREAM")) {
	define ("ZM_WEB_L_CAN_STREAM", 1);
	define ("ZM_WEB_M_CAN_STREAM", 1);
	define ("ZM_WEB_H_CAN_STREAM", 1);
}
if (!defined("ZM_WEB_L_STREAM_METHOD")) {
	define ("ZM_WEB_L_STREAM_METHOD", "jpeg");
	define ("ZM_WEB_M_STREAM_METHOD", "jpeg");
	define ("ZM_WEB_H_STREAM_METHOD", "jpeg");
}

switch ( $_COOKIE['zmBandwidth'] )
{
    case "high" :
    {
        define( "ZM_WEB_REFRESH_MAIN", ZM_WEB_H_REFRESH_MAIN );         // How often (in seconds) the main console window refreshes
        define( "ZM_WEB_REFRESH_CYCLE", ZM_WEB_H_REFRESH_CYCLE );       // How often the cycle watch windows swaps to the next monitor
        define( "ZM_WEB_REFRESH_IMAGE", ZM_WEB_H_REFRESH_IMAGE );       // How often the watched image is refreshed (if not streaming)
        define( "ZM_WEB_REFRESH_STATUS", ZM_WEB_H_REFRESH_STATUS );     // How often the little status frame refreshes itself in the watch window
        define( "ZM_WEB_REFRESH_EVENTS", ZM_WEB_H_REFRESH_EVENTS );     // How often the event listing is refreshed in the watch window, only for recent events
        define( "ZM_WEB_CAN_STREAM", ZM_WEB_H_CAN_STREAM );             // Override the automatic detection of browser streaming capability
        define( "ZM_WEB_STREAM_METHOD", ZM_WEB_H_STREAM_METHOD );       // Which method should be used to send video streams to your browser
        define( "ZM_WEB_DEFAULT_SCALE", ZM_WEB_H_DEFAULT_SCALE );       // What the default scaling factor applied to 'live' or 'event' views is (%)
        define( "ZM_WEB_DEFAULT_RATE", ZM_WEB_H_DEFAULT_RATE );         // What the default replay rate factor applied to 'event' views is (%)
        define( "ZM_WEB_VIDEO_BITRATE", ZM_WEB_H_VIDEO_BITRATE );       // What the bitrate of any streamed video should be
        define( "ZM_WEB_VIDEO_MAXFPS", ZM_WEB_H_VIDEO_MAXFPS );         // What the maximum frame rate of any streamed video should be
        define( "ZM_WEB_SCALE_THUMBS", ZM_WEB_H_SCALE_THUMBS );         // Image scaling for thumbnails, bandwidth versus cpu in rescaling
        define( "ZM_WEB_EVENTS_VIEW", ZM_WEB_H_EVENTS_VIEW );           // What the default view of multiple events should be.
        define( "ZM_WEB_SHOW_PROGRESS", ZM_WEB_H_SHOW_PROGRESS );       // Whether to show the progress of replay in event view.
        define( "ZM_WEB_AJAX_TIMEOUT", ZM_WEB_H_AJAX_TIMEOUT );         // Timeout to use for Ajax requests, no timeout used if unset
        break;
    }
    case "medium" : 
    {
        define( "ZM_WEB_REFRESH_MAIN", ZM_WEB_M_REFRESH_MAIN );         // How often (in seconds) the main console window refreshes
        define( "ZM_WEB_REFRESH_CYCLE", ZM_WEB_M_REFRESH_CYCLE );       // How often the cycle watch windows swaps to the next monitor
        define( "ZM_WEB_REFRESH_IMAGE", ZM_WEB_M_REFRESH_IMAGE );       // How often the watched image is refreshed (if not streaming)
        define( "ZM_WEB_REFRESH_STATUS", ZM_WEB_M_REFRESH_STATUS );     // How often the little status frame refreshes itself in the watch window
        define( "ZM_WEB_REFRESH_EVENTS", ZM_WEB_M_REFRESH_EVENTS );     // How often the event listing is refreshed in the watch window, only for recent events
        define( "ZM_WEB_CAN_STREAM", ZM_WEB_M_CAN_STREAM );             // Override the automatic detection of browser streaming capability
        define( "ZM_WEB_STREAM_METHOD", ZM_WEB_M_STREAM_METHOD );       // Which method should be used to send video streams to your browser
        define( "ZM_WEB_DEFAULT_SCALE", ZM_WEB_M_DEFAULT_SCALE );       // What the default scaling factor applied to 'live' or 'event' views is (%)
        define( "ZM_WEB_DEFAULT_RATE", ZM_WEB_M_DEFAULT_RATE );         // What the default replay rate factor applied to 'event' views is (%)
        define( "ZM_WEB_VIDEO_BITRATE", ZM_WEB_M_VIDEO_BITRATE );       // What the bitrate of any streamed video should be
        define( "ZM_WEB_VIDEO_MAXFPS", ZM_WEB_M_VIDEO_MAXFPS );         // What the maximum frame rate of any streamed video should be
        define( "ZM_WEB_SCALE_THUMBS", ZM_WEB_M_SCALE_THUMBS );         // Image scaling for thumbnails, bandwidth versus cpu in rescaling
        define( "ZM_WEB_EVENTS_VIEW", ZM_WEB_M_EVENTS_VIEW );           // What the default view of multiple events should be.
        define( "ZM_WEB_SHOW_PROGRESS", ZM_WEB_M_SHOW_PROGRESS );       // Whether to show the progress of replay in event view.
        define( "ZM_WEB_AJAX_TIMEOUT", ZM_WEB_M_AJAX_TIMEOUT );         // Timeout to use for Ajax requests, no timeout used if unset
        break;
    }
    case "low" :
    {
        define( "ZM_WEB_REFRESH_MAIN", ZM_WEB_L_REFRESH_MAIN );         // How often (in seconds) the main console window refreshes
        define( "ZM_WEB_REFRESH_CYCLE", ZM_WEB_L_REFRESH_CYCLE );       // How often the cycle watch windows swaps to the next monitor
        define( "ZM_WEB_REFRESH_IMAGE", ZM_WEB_L_REFRESH_IMAGE );       // How often the watched image is refreshed (if not streaming)
        define( "ZM_WEB_REFRESH_STATUS", ZM_WEB_L_REFRESH_STATUS );     // How often the little status frame refreshes itself in the watch window
	define( "ZM_WEB_REFRESH_EVENTS", ZM_WEB_L_REFRESH_EVENTS );     // How often the event listing is refreshed in the watch window, only for recent events
        define( "ZM_WEB_CAN_STREAM", ZM_WEB_L_CAN_STREAM );             // Override the automatic detection of browser streaming capability
        define( "ZM_WEB_STREAM_METHOD", ZM_WEB_L_STREAM_METHOD );       // Which method should be used to send video streams to your browser
        define( "ZM_WEB_DEFAULT_SCALE", ZM_WEB_L_DEFAULT_SCALE );       // What the default scaling factor applied to 'live' or 'event' views is (%)
        define( "ZM_WEB_DEFAULT_RATE", ZM_WEB_L_DEFAULT_RATE );         // What the default replay rate factor applied to 'event' views is (%)
        define( "ZM_WEB_VIDEO_BITRATE", ZM_WEB_L_VIDEO_BITRATE );       // What the bitrate of any streamed video should be
        define( "ZM_WEB_VIDEO_MAXFPS", ZM_WEB_L_VIDEO_MAXFPS );         // What the maximum frame rate of any streamed video should be
        define( "ZM_WEB_SCALE_THUMBS", ZM_WEB_L_SCALE_THUMBS );         // Image scaling for thumbnails, bandwidth versus cpu in rescaling
        define( "ZM_WEB_EVENTS_VIEW", ZM_WEB_L_EVENTS_VIEW );           // What the default view of multiple events should be.
        define( "ZM_WEB_SHOW_PROGRESS", ZM_WEB_L_SHOW_PROGRESS );       // Whether to show the progress of replay in event view.
        define( "ZM_WEB_AJAX_TIMEOUT", ZM_WEB_L_AJAX_TIMEOUT );         // Timeout to use for Ajax requests, no timeout used if unset
        break;
    }
}

?>
