//
// ZoneMinder Monitor Class Implementation, $Date$, $Revision$
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

#include "zm_monitor.h"

#include "zm_group.h"
#include "zm_eventstream.h"
#include "zm_ffmpeg_camera.h"
#include "zm_fifo.h"
#include "zm_file_camera.h"
#include "zm_monitorlink_expression.h"
#include "zm_remote_camera.h"
#include "zm_remote_camera_http.h"
#include "zm_remote_camera_nvsocket.h"
#include "zm_remote_camera_rtsp.h"
#include "zm_signal.h"
#include "zm_time.h"
#include "zm_utils.h"
#include "zm_zone.h"


#if ZM_HAS_V4L2
#include "zm_local_camera.h"
#endif // ZM_HAS_V4L2

#if HAVE_LIBVLC
#include "zm_libvlc_camera.h"
#endif // HAVE_LIBVLC

#if HAVE_LIBCURL
#include "zm_curl_camera.h"
#endif // HAVE_LIBCURL

#if HAVE_LIBVNC
#include "zm_libvnc_camera.h"
#endif // HAVE_LIBVNC

#include <algorithm>
#include <cstring>
#include <sys/types.h>
#include <sys/stat.h>
#include <chrono>
#include <regex>

#if ZM_MEM_MAPPED
#include <sys/mman.h>
#include <fcntl.h>
#else // ZM_MEM_MAPPED
#include <sys/ipc.h>
#include <sys/shm.h>
#endif // ZM_MEM_MAPPED

// SOLARIS - we don't have MAP_LOCKED on openSolaris/illumos
#ifndef MAP_LOCKED
#define MAP_LOCKED 0
#endif

#ifdef WITH_GSOAP
//Workaround for the gsoap library on RHEL
struct Namespace namespaces[] =
{
   {NULL, NULL, NULL, NULL} // end of table
};
#endif

std::string CameraType_Strings[] = {
  "Unknown",
  "Local",
  "Remote",
  "File",
  "Ffmpeg",
  "LibVLC",
  "NVSOCKET",
  "CURL",
  "VNC"
};

std::string State_Strings[] = {
  "Unknown",
  "IDLE",
  "PREALARM",
  "ALARM",
  "ALERT",
  "TAPE"
};

std::string Capturing_Strings[] = {
  "Unknown",
  "None",
  "On demand",
  "Always"
};

std::string Analysing_Strings[] = {
  "Unknown",
  "None",
  "Always"
};

std::string Recording_Strings[] = {
  "Unknown",
  "None",
  "On motion",
  "Always"
};

std::string Decoding_Strings[] = {
  "Unknown",
  "None",
  "On demand",
  "Keyframes",
  "Keyframes + Ondemand",
  "Always"
};

std::string TriggerState_Strings[] = {
  "Cancel", "On", "Off"
};

Monitor::Monitor()
 : id(0),
  name(""),
  server_id(0),
  storage_id(0),
  type(LOCAL),
  capturing(CAPTURING_ALWAYS),
  analysing(ANALYSING_ALWAYS),
  recording(RECORDING_ALWAYS),
  decoding(DECODING_ALWAYS),
  janus_enabled(false),
  janus_audio_enabled(false),
  janus_profile_override(""),
  janus_use_rtsp_restream(false),
  janus_rtsp_user(0),
  //protocol
  //method
  //options
  //host
  //port
  user(),
  pass(),
  //path
  //device
  palette(0),
  channel(0),
  format(0),

  width(0),
  height(0),
  //v4l_multi_buffer
  //v4l_captures_per_frame
  orientation(ROTATE_0),
  deinterlacing(0),
  deinterlacing_value(0),
  decoder_hwaccel_name(""),
  decoder_hwaccel_device(""),
  videoRecording(false),
  rtsp_describe(false),

  savejpegs(0),
  colours(0),
  videowriter(DISABLED),
  encoderparams(""),
  output_codec(0),
  encoder(""),
  output_container(""),
  imagePixFormat(AV_PIX_FMT_NONE),
  record_audio(false),
//event_prefix
//label_format
  label_coord(Vector2(0,0)),
  label_size(0),
  image_buffer_count(0),
  max_image_buffer_count(0),
  warmup_count(0),
  pre_event_count(0),
  post_event_count(0),
  stream_replay_buffer(0),
  section_length(0),
  section_length_warn(true),
  min_section_length(0),
  adaptive_skip(false),
  frame_skip(0),
  motion_frame_skip(0),
  analysis_fps_limit(0),
  analysis_update_delay(0),
  capture_delay(0),
  alarm_capture_delay(0),
  alarm_frame_count(0),
  alert_to_alarm_frame_count(0),
  fps_report_interval(0),
  ref_blend_perc(0),
  alarm_ref_blend_perc(0),
  track_motion(false),
  signal_check_points(0),
  signal_check_colour(0),
  embed_exif(false),
  rtsp_server(false),
  rtsp_streamname(""),
  onvif_alarm_txt(""),
  importance(0),
  zone_count(0),
  capture_max_fps(0),
  purpose(QUERY),
  last_camera_bytes(0),
  event_count(0),
  image_count(0),
  last_capture_image_count(0),
  analysis_image_count(0),
  motion_frame_count(0),
  last_motion_frame_count(0),
  ready_count(0),
  first_alarm_count(0),
  last_alarm_count(0),
  last_signal(false),
  buffer_count(0),
  state(IDLE),
  last_motion_score(0),
  event_close_mode(CLOSE_IDLE),
#if ZM_MEM_MAPPED
  map_fd(-1),
  mem_file(""),
#else // ZM_MEM_MAPPED
  shm_id(-1),
#endif // ZM_MEM_MAPPED
  mem_size(0),
  mem_ptr(nullptr),
  shared_data(nullptr),
  trigger_data(nullptr),
  video_store_data(nullptr),
  shared_timestamps(nullptr),
  shared_images(nullptr),
  video_stream_id(-1),
  audio_stream_id(-1),
  video_fifo(nullptr),
  audio_fifo(nullptr),
  camera(nullptr),
  event(nullptr),
  storage(nullptr),
  videoStore(nullptr),
  analysis_it(nullptr),
  analysis_thread(nullptr),
  decoder_it(nullptr),
  decoder(nullptr),
  convert_context(nullptr),
  //zones(nullptr),
#if MOSQUITTOPP_FOUND
  mqtt_enabled(false),
  // mqtt_subscriptions,
  mqtt(nullptr),
#endif
  privacy_bitmask(nullptr),
  //linked_monitors_string
  n_linked_monitors(0),
  linked_monitors(nullptr),
  Poll_Trigger_State(false),
  Event_Poller_Healthy(false),
  Event_Poller_Closes_Event(false),
  Janus_Manager(nullptr),
  Amcrest_Manager(nullptr),
#ifdef WITH_GSOAP
  soap(nullptr),
#endif
  red_val(0),
  green_val(0),
  blue_val(0),
  grayscale_val(0),
  colour_val(0)
{
  if (strcmp(config.event_close_mode, "time") == 0) {
    event_close_mode = CLOSE_TIME;
  } else if (strcmp(config.event_close_mode, "alarm") == 0) {
    event_close_mode = CLOSE_ALARM;
  } else {
    event_close_mode = CLOSE_IDLE;
  }

  event = nullptr;

  adaptive_skip = true;

  videoStore = nullptr;
}  // Monitor::Monitor

/*
   std::string load_monitor_sql =
   "SELECT `Id`, `Name`, `ServerId`, `StorageId`, `Type`, `Capturing`+0, `Analysing`+0, `AnalysisSource`+0, `AnalysisImage`+0,"
   "`Recording`+0, `RecordingSource`+0, `Decoding`+0, JanusEnabled, JanusAudioEnabled, Janus_Profile_Override, Janus_Use_RTSP_Restream, Janus_RTSP_User, "
   "LinkedMonitors, `EventStartCommand`, `EventEndCommand`, "
   "AnalysisFPSLimit, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS,"
   "Device, Channel, Format, V4LMultiBuffer, V4LCapturesPerFrame, " // V4L Settings
   "Protocol, Method, Options, User, Pass, Host, Port, Path, SecondPath, Width, Height, Colours, Palette, Orientation+0, Deinterlacing, RTSPDescribe, "
   "SaveJPEGs, VideoWriter, EncoderParameters,
   "OutputCodec, Encoder, OutputContainer, RecordAudio, "
   "Brightness, Contrast, Hue, Colour, "
   "EventPrefix, LabelFormat, LabelX, LabelY, LabelSize,"
   "ImageBufferCount, `MaxImageBufferCount`, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, "
   "SectionLength, MinSectionLength, FrameSkip, MotionFrameSkip, "
   "FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, Exif,"
   "`RTSPServer`,`RTSPStreamName`,
   "`ONVIF_URL`, `ONVIF_Username`, `ONVIF_Password`, `ONVIF_Options`, `ONVIF_Event_Listener`, `use_Amcrest_API`, "
   "SignalCheckPoints, SignalCheckColour, Importance-1, ZoneCount, `MQTT_Enabled`, `MQTT_Subscriptions` FROM Monitors";
*/

void Monitor::Load(zmDbQuery& dbrow, bool load_zones=true, Purpose p = QUERY) {
  purpose = p;

  id = dbrow.get<long long>("Id");
  name = dbrow.get<std::string>("Name");
  server_id = dbrow.has("ServerId") ? dbrow.get<long long>("ServerId") : 0;

  storage_id = dbrow.get<int>("StorageId");
  delete storage;
  storage = new Storage(storage_id);

  std::string typeDb = dbrow.get<std::string>("Type");

  if ( typeDb.compare("Local") == 0 ) {
    type = LOCAL;
  } else if ( typeDb.compare("Ffmpeg") == 0 ) {
    type = FFMPEG;
  } else if ( typeDb.compare("Remote") == 0 ) {
    type = REMOTE;
  } else if ( typeDb.compare("File") == 0 ) {
    type = FILE;
  } else if ( typeDb.compare("NVSocket") == 0 ) {
    type = NVSOCKET;
  } else if ( typeDb.compare("Libvlc") == 0 ) {
    type = LIBVLC;
  } else if ( typeDb.compare("cURL") == 0 ) {
    type = LIBCURL;
  } else if ( typeDb.compare("VNC") == 0 ) {
    type = VNC;
  } else {
    Fatal("Bogus monitor type '%s' for monitor %d", typeDb.c_str(), id);
  }
  Debug(1, "Have camera type %s", CameraType_Strings[type].c_str());

  capturing = dbrow.get<CapturingOption>("Capturing");
  analysing = dbrow.get<AnalysingOption>("Analysing");
  analysis_source = dbrow.get<AnalysisSourceOption>("AnalysisSource");
  analysis_image = dbrow.get<AnalysisImageOption>("AnalysisImage");
  recording = dbrow.get<RecordingOption>("Recording");
  recording_source = dbrow.get<RecordingSourceOption>("RecordingSource");

  decoding = dbrow.get<DecodingOption>("Decoding");

  // See below after save_jpegs for a recalculation of decoding_enabled
  janus_enabled = dbrow.has("JanusEnabled") ? (dbrow.get<int>("JanusEnabled")==1) : false;
  janus_audio_enabled = dbrow.has("JanusAudioEnabled") ? (dbrow.get<int>("JanusAudioEnabled")==1) : false;
  janus_profile_override = dbrow.has("Janus_Profile_Override") ? dbrow.get<std::string>("Janus_Profile_Override") : "";
  janus_use_rtsp_restream = dbrow.has("Janus_Use_RTSP_Restream") ? (dbrow.get<int>("Janus_Use_RTSP_Restream")==1) : false;

  janus_rtsp_user =  dbrow.has("Janus_RTSP_User") ? dbrow.get<int>("Janus_RTSP_User") : 0;

  linked_monitors_string = dbrow.has("LinkedMonitors") ? dbrow.get<std::string>("LinkedMonitors") : "";
  event_start_command = dbrow.has("EventStartCommand") ? dbrow.get<std::string>("EventStartCommand") : "";
  event_end_command = dbrow.has("EventEndCommand") ? dbrow.get<std::string>("EventEndCommand") : "";

  /* "AnalysisFPSLimit, AnalysisUpdateDelay, MaxFPS, AlarmMaxFPS," */
  analysis_fps_limit = dbrow.has("AnalysisFPSLimit") ? dbrow.get<zmDecimal>("AnalysisFPSLimit").toValue() : 0.0;
  analysis_update_delay = Seconds( dbrow.get<int>("AnalysisUpdateDelay") );

  capture_delay = Microseconds(0);
  if( dbrow.has("MaxFPS") ) {
    double tmp = dbrow.get<double>("MaxFPS");
    if( tmp > 0.0 ) {
      capture_delay = std::chrono::duration_cast<Microseconds>(FPSeconds(1 / tmp));
    }
  }
  alarm_capture_delay = Microseconds(0);
  if( dbrow.has("AlarmMaxFPS") ) {
    double tmp = dbrow.get<double>("AlarmMaxFPS");
    if( tmp > 0.0 ) {
      alarm_capture_delay = std::chrono::duration_cast<Microseconds>(FPSeconds(1 / tmp));
    }
  }

  /* "Device, Channel, Format, V4LMultiBuffer, V4LCapturesPerFrame, " // V4L Settings */
  device = dbrow.has("Device") ? dbrow.get<std::string>("Device") : "";
  channel = dbrow.get<int>("Channel");
  format = dbrow.get<long long>("Format");

  v4l_multi_buffer = config.v4l_multi_buffer;
  if ( dbrow.has("V4LMultiBuffer") ) {
    int tmp = dbrow.get<int>("V4LMultiBuffer");
    if ( tmp == 0 ) {
      v4l_multi_buffer = false;
    } else if ( tmp == 1 ) {
      v4l_multi_buffer = true;
    }
  }

  v4l_captures_per_frame = config.captures_per_frame;
  if ( dbrow.has("V4LCapturesPerFrame") ) {
    v4l_captures_per_frame = dbrow.get<int>("V4LCapturesPerFrame");
  }

  /* "Protocol, Method, Options, User, Pass, Host, Port, Path, SecondPath, Width, Height, Colours, Palette, Orientation+0, Deinterlacing, " */
  protocol = dbrow.has("Protocol") ? dbrow.get<std::string>("Protocol") : "";
  method = dbrow.has("Method") ? dbrow.get<std::string>("Method") : "";
  options = dbrow.has("Options") ? dbrow.get<std::string>("Options") : "";
  user = dbrow.has("User") ? dbrow.get<std::string>("User") : "";
  pass = dbrow.has("Pass") ? dbrow.get<std::string>("Pass") : "";
  host = dbrow.has("Host") ? dbrow.get<std::string>("Host") : "";
  port = dbrow.has("Port") ? dbrow.get<std::string>("Port") : "";
  path = dbrow.has("Path") ? dbrow.get<std::string>("Path") : "";
  second_path = dbrow.has("SecondPath") ? dbrow.get<std::string>("SecondPath") : "";

  camera_width = dbrow.get<int>("Width");
  camera_height = dbrow.get<int>("Height");
  colours = dbrow.get<int>("Colours");
  palette = dbrow.get<long long>("Palette");
  orientation = dbrow.get<Orientation>("Orientation");
  width = (orientation==ROTATE_90||orientation==ROTATE_270) ? camera_height : camera_width;
  height = (orientation==ROTATE_90||orientation==ROTATE_270) ? camera_width : camera_height;
  deinterlacing = dbrow.get<long long>("Deinterlacing");
  deinterlacing_value = deinterlacing & 0xff;

/*"`DecoderHWAccelName`, `DecoderHWAccelDevice`, `RTSPDescribe`, " */
  decoder_hwaccel_name = dbrow.has("DecoderHWAccelName") ? dbrow.get<std::string>("DecoderHWAccelName") : "";
  decoder_hwaccel_device = dbrow.has("DecoderHWAccelDevice") ? dbrow.get<std::string>("DecoderHWAccelDevice") : "";
  rtsp_describe = dbrow.has("RTSPDescribe") ? dbrow.get<int>("RTSPDescribe") != 0 : false;


/* "`SaveJPEGs`, `VideoWriter`, `EncoderParameters`, " */
  savejpegs = dbrow.get<int>("SaveJPEGs");
  videowriter = dbrow.get<VideoWriter>("VideoWriter");
  encoderparams = dbrow.has("EncoderParameters") ? dbrow.get<std::string>("EncoderParameters") : "";

  Debug(3, "Decoding: %d savejpegs %d videowriter %d", decoding, savejpegs, videowriter);

/*"`OutputCodec`, `Encoder`, `OutputContainer`, " */
  output_codec = dbrow.has("OutputCodec") ? dbrow.get<long long>("OutputCodec") : 0;
  encoder = dbrow.has("Encoder") ? dbrow.get<std::string>("Encoder") : "";
  output_container = dbrow.has("OutputContainer") ? dbrow.get<std::string>("OutputContainer") : "";
  record_audio = dbrow.get<long long>("OutputCodec") != 0;

 /* "Brightness, Contrast, Hue, Colour, " */
  brightness = dbrow.get<int>("Brightness");
  contrast = dbrow.get<int>("Contrast");
  hue = dbrow.get<int>("Hue");
  colour = dbrow.get<int>("Colour");

  /* "EventPrefix, LabelFormat, LabelX, LabelY, LabelSize," */
  event_prefix = dbrow.has("EventPrefix") ? dbrow.get<std::string>("EventPrefix") : "";
  //label_format = dbrow[col] ? ReplaceAll(dbrow[col], "\\n", "\n") : ""; col++;
  label_format = dbrow.has("LabelFormat") ? dbrow.get<std::string>("LabelFormat") : "";
  label_format = std::regex_replace( label_format, std::regex("\\n"), "\n" );

  label_coord = Vector2(dbrow.get<int>("LabelX"), dbrow.get<int>("LabelY"));
  label_size = dbrow.get<int>("LabelSize");

  /* "ImageBufferCount, `MaxImageBufferCount`, WarmupCount, PreEventCount, PostEventCount, StreamReplayBuffer, AlarmFrameCount, " */
  image_buffer_count = dbrow.get<int>("ImageBufferCount");
  max_image_buffer_count = dbrow.get<int>("MaxImageBufferCount");
  warmup_count = dbrow.get<int>("WarmupCount");
  pre_event_count = dbrow.get<int>("PreEventCount");
  packetqueue.setPreEventVideoPackets(pre_event_count);
  packetqueue.setMaxVideoPackets(max_image_buffer_count);
  packetqueue.setKeepKeyframes(videowriter == PASSTHROUGH || recording != RECORDING_NONE);
  post_event_count = dbrow.get<int>("PostEventCount");
  stream_replay_buffer = dbrow.get<long long>("StreamReplayBuffer");
  alarm_frame_count = dbrow.get<int>("AlarmFrameCount");
  if (alarm_frame_count < 1) alarm_frame_count = 1;
  else if (alarm_frame_count > MAX_PRE_ALARM_FRAMES) alarm_frame_count = MAX_PRE_ALARM_FRAMES;

 /* "SectionLength, MinSectionLength, FrameSkip, MotionFrameSkip, " */
  section_length = Seconds(dbrow.get<long long>("SectionLength"));
  min_section_length = Seconds(dbrow.get<long long>("MinSectionLength"));
  frame_skip = dbrow.get<int>("FrameSkip");
  motion_frame_skip = dbrow.get<int>("MotionFrameSkip");

 /* "FPSReportInterval, RefBlendPerc, AlarmRefBlendPerc, TrackMotion, Exif," */
  fps_report_interval = dbrow.get<int>("FPSReportInterval");
  ref_blend_perc = dbrow.get<int>("RefBlendPerc");
  alarm_ref_blend_perc = dbrow.get<int>("AlarmRefBlendPerc");
  track_motion = dbrow.get<int>("TrackMotion");
  embed_exif = (dbrow.get<int>("Exif") != 0);

 /* "`RTSPServer`,`RTSPStreamName`,`ONVIF_Alarm_Text`, */
  rtsp_server = (dbrow.get<int>("RTSPServer") != 0);
  rtsp_streamname = dbrow.get<std::string>("RTSPStreamName");
  // get alarm text from table.
  onvif_alarm_txt = dbrow.has("ONVIF_Alarm_Text") ? dbrow.get<std::string>("ONVIF_Alarm_Text") : "";


   /* "`ONVIF_URL`, `ONVIF_Username`, `ONVIF_Password`, `ONVIF_Options`, `ONVIF_Event_Listener`, `use_Amcrest_API`, " */
  onvif_url = dbrow.has("ONVIF_URL") ? dbrow.get<std::string>("ONVIF_URL") : "";
  onvif_username = dbrow.has("ONVIF_Username") ? dbrow.get<std::string>("ONVIF_Username") : "";
  onvif_password = dbrow.has("ONVIF_Password") ? dbrow.get<std::string>("ONVIF_Password") : "";
  onvif_options = dbrow.has("ONVIF_Options") ? dbrow.get<std::string>("ONVIF_Options") : "";
  onvif_event_listener = (dbrow.get<int>("ONVIF_Event_Listener") != 0);
  use_Amcrest_API = (dbrow.get<int>("use_Amcrest_API") != 0);

 /*"SignalCheckPoints, SignalCheckColour, Importance-1 FROM Monitors"; */
  signal_check_points = dbrow.get<long long>("SignalCheckPoints");

  std::string check_colour_str = dbrow.get<std::string>("SignalCheckColour");
  if( check_colour_str.length() > 0 ) {
    const char* check_colour = check_colour_str.c_str();
    signal_check_colour = strtol(check_colour[0] == '#' ? check_colour+1 : check_colour, 0, 16);
  }

  colour_val = rgb_convert(signal_check_colour, ZM_SUBPIX_ORDER_BGR); /* HTML colour code is actually BGR in memory, we want RGB */
  colour_val = rgb_convert(colour_val, palette);
  red_val = RED_VAL_BGRA(signal_check_colour);
  green_val = GREEN_VAL_BGRA(signal_check_colour);
  blue_val = BLUE_VAL_BGRA(signal_check_colour);
  grayscale_val = signal_check_colour & 0xff; /* Clear all bytes but lowest byte */

  importance = dbrow.has("Importance") ? dbrow.get<ImportanceType>("Importance") : 0;
  if (importance < 0) importance = 0; // Should only be >= 0
  zone_count = dbrow.has("ZoneCount") ? dbrow.get<int>("ZoneCount") : 0;

#if MOSQUITTOPP_FOUND
  mqtt_enabled = (*dbrow[col] != '0'); col++;
  std::string mqtt_subscriptions_string = std::string(dbrow[col] ? dbrow[col] : "");
  mqtt_subscriptions = Split(mqtt_subscriptions_string, ','); col++;
  Error("MQTT enabled ? %d, subs %s", mqtt_enabled, mqtt_subscriptions_string.c_str());
#endif

#if MOSQUITTOPP_FOUND
  mqtt_enabled = (*dbrow[col] != '0'); col++;
  std::string mqtt_subscriptions_string = std::string(dbrow[col] ? dbrow[col] : "");
  mqtt_subscriptions = Split(mqtt_subscriptions_string, ','); col++;
  Error("MQTT enabled ? %d, subs %s", mqtt_enabled, mqtt_subscriptions_string.c_str());
#endif

  // How many frames we need to have before we start analysing
  ready_count = std::max(warmup_count, pre_event_count);

  last_alarm_count = 0;
  state = IDLE;
  last_signal = true;   // Defaulting to having signal so that we don't get a signal change on the first frame.
                        // Instead initial failure to capture will cause a loss of signal change which I think makes more sense.

  if ( strcmp(config.event_close_mode, "time") == 0 )
    event_close_mode = CLOSE_TIME;
  else if ( strcmp(config.event_close_mode, "alarm") == 0 )
    event_close_mode = CLOSE_ALARM;
  else
    event_close_mode = CLOSE_IDLE;

  // Should maybe store this for later use
  std::string monitor_dir = stringtf("%s/%u", storage->Path(), id);

  if ( purpose != QUERY ) {
    LoadCamera();

    if ( mkdir(monitor_dir.c_str(), 0755) && ( errno != EEXIST ) ) {
      Error("Can't mkdir %s: %s", monitor_dir.c_str(), strerror(errno));
    }

    if ( config.record_diag_images ) {
      if ( config.record_diag_images_fifo ) {
        diag_path_ref = stringtf("%s/diagpipe-r-%d.jpg", staticConfig.PATH_SOCKS.c_str(), id);
        diag_path_delta = stringtf("%s/diagpipe-d-%d.jpg", staticConfig.PATH_SOCKS.c_str(), id);
        Fifo::fifo_create_if_missing(diag_path_ref.c_str());
        Fifo::fifo_create_if_missing(diag_path_delta.c_str());
      } else {
        diag_path_ref = stringtf("%s/%d/diag-r.jpg", storage->Path(), id);
        diag_path_delta = stringtf("%s/%d/diag-d.jpg", storage->Path(), id);
      }
    }
  }  // end if purpose

} // Monitor::Load(MYSQL_ROW dbrow, bool load_zones=true, Purpose p = QUERY)

void Monitor::LoadCamera() {
  if (camera)
    return;

  switch (type) {
    case LOCAL: {
#if ZM_HAS_V4L2
      int extras = (deinterlacing >> 24) & 0xff;

      camera = zm::make_unique<LocalCamera>(this,
                                            device,
                                            channel,
                                            format,
                                            v4l_multi_buffer,
                                            v4l_captures_per_frame,
                                            method,
                                            camera_width,
                                            camera_height,
                                            colours,
                                            palette,
                                            brightness,
                                            contrast,
                                            hue,
                                            colour,
                                            purpose == CAPTURE,
                                            record_audio,
                                            extras
      );
#else
      Fatal("Not compiled with local v4l camera support");
#endif
      break;
    }
    case REMOTE: {
      if (protocol == "http") {
        camera = zm::make_unique<RemoteCameraHttp>(this,
                                                   method,
                                                   host,
                                                   port,
                                                   path,
                                                   user,
                                                   pass,
                                                   camera_width,
                                                   camera_height,
                                                   colours,
                                                   brightness,
                                                   contrast,
                                                   hue,
                                                   colour,
                                                   purpose == CAPTURE,
                                                   record_audio
        );
      }
      else if (protocol == "rtsp") {
        camera = zm::make_unique<RemoteCameraRtsp>(this,
                                                   method,
                                                   host, // Host
                                                   port, // Port
                                                   path, // Path
                                                   user,
                                                   pass,
                                                   camera_width,
                                                   camera_height,
                                                   rtsp_describe,
                                                   colours,
                                                   brightness,
                                                   contrast,
                                                   hue,
                                                   colour,
                                                   purpose == CAPTURE,
                                                   record_audio
        );
      }
      else {
        Error("Unexpected remote camera protocol '%s'", protocol.c_str());
      }
      break;
    }
    case FILE: {
      camera = zm::make_unique<FileCamera>(this,
                                           path.c_str(),
                                           camera_width,
                                           camera_height,
                                           colours,
                                           brightness,
                                           contrast,
                                           hue,
                                           colour,
                                           purpose == CAPTURE,
                                           record_audio
      );
      break;
    }
    case FFMPEG: {
      camera = zm::make_unique<FfmpegCamera>(this,
                                             path,
                                             second_path,
                                             user,
                                             pass,
                                             method,
                                             options,
                                             camera_width,
                                             camera_height,
                                             colours,
                                             brightness,
                                             contrast,
                                             hue,
                                             colour,
                                             purpose == CAPTURE,
                                             record_audio,
                                             decoder_hwaccel_name,
                                             decoder_hwaccel_device
      );
      break;
    }
    case NVSOCKET: {
      camera = zm::make_unique<RemoteCameraNVSocket>(this,
                                                     host.c_str(),
                                                     port.c_str(),
                                                     path.c_str(),
                                                     camera_width,
                                                     camera_height,
                                                     colours,
                                                     brightness,
                                                     contrast,
                                                     hue,
                                                     colour,
                                                     purpose == CAPTURE,
                                                     record_audio
      );
      break;
    }
    case LIBVLC: {
#if HAVE_LIBVLC
      camera = zm::make_unique<LibvlcCamera>(this,
                                             path.c_str(),
                                             user,
                                             pass,
                                             method,
                                             options,
                                             camera_width,
                                             camera_height,
                                             colours,
                                             brightness,
                                             contrast,
                                             hue,
                                             colour,
                                             purpose == CAPTURE,
                                             record_audio
      );
#else // HAVE_LIBVLC
      Error("You must have vlc libraries installed to use vlc cameras for monitor %d", id);
#endif // HAVE_LIBVLC
      break;
    }
    case LIBCURL: {
#if HAVE_LIBCURL
      camera = zm::make_unique<cURLCamera>(this,
                                           path.c_str(),
                                           user.c_str(),
                                           pass.c_str(),
                                           camera_width,
                                           camera_height,
                                           colours,
                                           brightness,
                                           contrast,
                                           hue,
                                           colour,
                                           purpose == CAPTURE,
                                           record_audio
      );
#else // HAVE_LIBCURL
      Error("You must have libcurl installed to use ffmpeg cameras for monitor %d", id);
#endif // HAVE_LIBCURL
      break;
    }
    case VNC: {
#if HAVE_LIBVNC
      camera = zm::make_unique<VncCamera>(this,
                                          host.c_str(),
                                          port.c_str(),
                                          user.c_str(),
                                          pass.c_str(),
                                          width,
                                          height,
                                          colours,
                                          brightness,
                                          contrast,
                                          hue,
                                          colour,
                                          purpose == CAPTURE,
                                          record_audio
      );
#else // HAVE_LIBVNC
      Fatal("You must have libvnc installed to use VNC cameras for monitor id %d", id);
#endif // HAVE_LIBVNC
      break;
    }
    default: {
      Fatal("Tried to load unsupported camera type %d for monitor %u", int(type), id);
      break;
    }
  }
}

std::shared_ptr<Monitor> Monitor::Load(unsigned int p_id, bool load_zones, Purpose purpose) {

  zmDbQuery query = zmDbQuery( SELECT_MONITOR_WITH_ID );
  query.bind( "id", p_id );
  query.fetchOne();

  if( query.affectedRows() != 1 ) {
    Error("Can't use query result");
    return nullptr;
  }

  std::shared_ptr<Monitor> monitor = std::make_shared<Monitor>();
  monitor->Load(query, load_zones, purpose);

  return monitor;
}

bool Monitor::connect() {
  ReloadZones();
  if (zones.size() != zone_count) {
    Warning("Monitor %d has incorrect zone_count %d != %zu", id, zone_count, zones.size());
    zone_count = zones.size();
  }
  if (mem_ptr != nullptr) {
    Warning("Already connected. Please call disconnect first.");
  }
  if (!camera) LoadCamera();
  uint64_t image_size = camera->ImageSize();
  mem_size = sizeof(SharedData)
       + sizeof(TriggerData)
       + (zone_count * sizeof(int)) // Per zone scores
       + sizeof(VideoStoreData) //Information to pass back to the capture process
       + (image_buffer_count*sizeof(struct timeval))
       + (image_buffer_count*image_size)
       + image_size // alarm_image
       + 64; /* Padding used to permit aligning the images buffer to 64 byte boundary */

  Debug(1,
        "SharedData=%zu TriggerData=%zu zone_count %d * sizeof int %zu VideoStoreData=%zu timestamps=%zu images=%dx%" PRIi64 " = %" PRId64 " total=%jd",
        sizeof(SharedData),
        sizeof(TriggerData),
        zone_count,
        sizeof(int),
        sizeof(VideoStoreData),
        (image_buffer_count * sizeof(struct timeval)),
        image_buffer_count,
        image_size,
        (image_buffer_count * image_size),
        static_cast<intmax_t>(mem_size));
#if ZM_MEM_MAPPED
  mem_file = stringtf("%s/zm.mmap.%u", staticConfig.PATH_MAP.c_str(), id);
  if (purpose != CAPTURE) {
    map_fd = open(mem_file.c_str(), O_RDWR);
  } else {
    umask(0);
    map_fd = open(mem_file.c_str(), O_RDWR|O_CREAT, (mode_t)0666);
  }

  if (map_fd < 0) {
    Error("Can't open memory map file %s: %s", mem_file.c_str(), strerror(errno));
    return false;
  } else {
    Debug(3, "Success opening mmap file at (%s)", mem_file.c_str());
  }

  struct stat map_stat;
  if (fstat(map_fd, &map_stat) < 0) {
    Error("Can't stat memory map file %s: %s, is the zmc process for this monitor running?", mem_file.c_str(), strerror(errno));
    close(map_fd);
    map_fd = -1;
    return false;
  }

  if (map_stat.st_size != mem_size) {
    if (purpose == CAPTURE) {
      // Allocate the size
      if (ftruncate(map_fd, mem_size) < 0) {
        Error("Can't extend memory map file %s to %jd bytes: %s", mem_file.c_str(), static_cast<intmax_t>(mem_size), strerror(errno));
        close(map_fd);
        map_fd = -1;
        return false;
      }
    } else if (map_stat.st_size == 0) {
      Error("Got empty memory map file size %jd, is the zmc process for this monitor running?", static_cast<intmax_t>(map_stat.st_size));
      close(map_fd);
      map_fd = -1;
      return false;
    } else {
      Error("Got unexpected memory map file size %jd, expected %jd", static_cast<intmax_t>(map_stat.st_size), static_cast<intmax_t>(mem_size));
      close(map_fd);
      map_fd = -1;
      return false;
    }
  }  // end if map_stat.st_size != mem_size

  Debug(3, "MMap file size is %jd", static_cast<intmax_t>(map_stat.st_size));
#ifdef MAP_LOCKED
  mem_ptr = (unsigned char *)mmap(nullptr, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED|MAP_LOCKED, map_fd, 0);
  if (mem_ptr == MAP_FAILED) {
    if (errno == EAGAIN) {
      Debug(1, "Unable to map file %s (%jd bytes) to locked memory, trying unlocked", mem_file.c_str(), static_cast<intmax_t>(mem_size));
#endif
      mem_ptr = (unsigned char *)mmap(nullptr, mem_size, PROT_READ|PROT_WRITE, MAP_SHARED, map_fd, 0);
      Debug(1, "Mapped file %s (%jd bytes) to unlocked memory", mem_file.c_str(), static_cast<intmax_t>(mem_size));
#ifdef MAP_LOCKED
    } else {
      Error("Unable to map file %s (%jd bytes) to locked memory (%s)", mem_file.c_str(), static_cast<intmax_t>(mem_size), strerror(errno));
    }
  }
#endif
  if ((mem_ptr == MAP_FAILED) or (mem_ptr == nullptr)) {
    Error("Can't map file %s (%jd bytes) to memory: %s(%d)", mem_file.c_str(), static_cast<intmax_t>(mem_size), strerror(errno), errno);
    close(map_fd);
    map_fd = -1;
    mem_ptr = nullptr;
    return false;
  }
#else // ZM_MEM_MAPPED
  shm_id = shmget((config.shm_key&0xffff0000)|id, mem_size, IPC_CREAT|0700);
  if (shm_id < 0) {
    Fatal("Can't shmget, probably not enough shared memory space free: %s", strerror(errno));
  }
  mem_ptr = (unsigned char *)shmat(shm_id, 0, 0);
  if ((int)mem_ptr == -1) {
    Fatal("Can't shmat: %s", strerror(errno));
  }
#endif // ZM_MEM_MAPPED

  shared_data = (SharedData *)mem_ptr;
  trigger_data = (TriggerData *)((char *)shared_data + sizeof(SharedData));
  zone_scores = (int *)((unsigned long)trigger_data + sizeof(TriggerData));
  video_store_data = (VideoStoreData *)((unsigned long)zone_scores + (zone_count*sizeof(int)));
  shared_timestamps = (struct timeval *)((char *)video_store_data + sizeof(VideoStoreData));
  shared_images = (unsigned char *)((char *)shared_timestamps + (image_buffer_count*sizeof(struct timeval)));

  if (((unsigned long)shared_images % 64) != 0) {
    /* Align images buffer to nearest 64 byte boundary */
    unsigned char * aligned_shared_images = (unsigned char *)((unsigned long)shared_images + (64 - ((unsigned long)shared_images % 64)));
    Debug(3, "Aligning shared memory images to the next 64 byte boundary %p to %p moved %lu bytes",
        shared_images, aligned_shared_images,
        (unsigned long)(aligned_shared_images - shared_images)
        );
    shared_images = (unsigned char *)aligned_shared_images;
  }

  image_buffer.resize(image_buffer_count);
  for (int32_t i = 0; i < image_buffer_count; i++) {
    image_buffer[i] = new Image(width, height, camera->Colours(), camera->SubpixelOrder(), &(shared_images[i*image_size]));
    image_buffer[i]->HoldBuffer(true); /* Don't release the internal buffer or replace it with another */
  }
  alarm_image.AssignDirect(width, height, camera->Colours(), camera->SubpixelOrder(),
      &(shared_images[image_buffer_count*image_size]),
        image_size,
        ZM_BUFTYPE_DONTFREE
        );
  alarm_image.HoldBuffer(true); /* Don't release the internal buffer or replace it with another */
  if (alarm_image.Buffer() + image_size > mem_ptr + mem_size) {
    Warning("We will exceed memsize by %td bytes!", (alarm_image.Buffer() + image_size) - (mem_ptr + mem_size));
  }

  if (purpose == CAPTURE) {
    memset(mem_ptr, 0, mem_size);
    shared_data->size = sizeof(SharedData);
    shared_data->analysing = analysing;
    shared_data->capturing = capturing;
    shared_data->recording = recording;
    shared_data->signal = false;
    shared_data->capture_fps = 0.0;
    shared_data->analysis_fps = 0.0;
    shared_data->state = state = IDLE;
    shared_data->last_write_index = image_buffer_count;
    shared_data->last_read_index = image_buffer_count;
    shared_data->last_write_time = 0;
    shared_data->last_event_id = 0;
    shared_data->action = (Action)0;
    shared_data->brightness = -1;
    shared_data->hue = -1;
    shared_data->colour = -1;
    shared_data->contrast = -1;
    shared_data->alarm_x = -1;
    shared_data->alarm_y = -1;
    shared_data->format = camera->SubpixelOrder();
    shared_data->imagesize = camera->ImageSize();
    shared_data->alarm_cause[0] = 0;
    shared_data->video_fifo_path[0] = 0;
    shared_data->audio_fifo_path[0] = 0;
    shared_data->janus_pin[0] = 0;
    shared_data->last_frame_score = 0;
    shared_data->audio_frequency = -1;
    shared_data->audio_channels = -1;
    trigger_data->size = sizeof(TriggerData);
    trigger_data->trigger_state = TriggerState::TRIGGER_CANCEL;
    trigger_data->trigger_score = 0;
    trigger_data->trigger_cause[0] = 0;
    trigger_data->trigger_text[0] = 0;
    trigger_data->trigger_showtext[0] = 0;
    video_store_data->recording = {};
    // Uh, why nothing?  Why not nullptr?
    snprintf(video_store_data->event_file, sizeof(video_store_data->event_file), "nothing");
    video_store_data->size = sizeof(VideoStoreData);
    usedsubpixorder = camera->SubpixelOrder();  // Used in CheckSignal
    shared_data->valid = true;

    ReloadLinkedMonitors();

    //ONVIF and Amcrest Setup
    //For now, only support one event type per camera, so share some state.
    Poll_Trigger_State = false;
    if (onvif_event_listener) { //
      Debug(1, "Starting ONVIF");
      Event_Poller_Healthy = false;
      if (onvif_options.find("closes_event") != std::string::npos) { //Option to indicate that ONVIF will send a close event message
        Event_Poller_Closes_Event = TRUE;
      }
      if (use_Amcrest_API) {
        Amcrest_Manager = new AmcrestAPI(this);
      } else { //using GSOAP
#ifdef WITH_GSOAP
        tev__PullMessages.Timeout = "PT600S";
        tev__PullMessages.MessageLimit = 100;
        soap = soap_new();
        soap->connect_timeout = 5;
        soap->recv_timeout = 5;
        soap->send_timeout = 5;
        soap_register_plugin(soap, soap_wsse);
        proxyEvent = PullPointSubscriptionBindingProxy(soap);
        std::string full_url = onvif_url + "/Events";
        proxyEvent.soap_endpoint = full_url.c_str();

        set_credentials(soap);
        Debug(1, "ONVIF Endpoint: %s", proxyEvent.soap_endpoint);
        if (proxyEvent.CreatePullPointSubscription(&request, response) != SOAP_OK) {
          Error("Couldn't create subscription! %s, %s", soap_fault_string(soap), soap_fault_detail(soap));
        } else {
          //Empty the stored messages
          set_credentials(soap);
          if ((proxyEvent.PullMessages(response.SubscriptionReference.Address, NULL, &tev__PullMessages, tev__PullMessagesResponse) != SOAP_OK) &&
              ( soap->error != SOAP_EOF)) { //SOAP_EOF could indicate no messages to pull.
            Error("Couldn't do initial event pull! Error %i %s, %s", soap->error, soap_fault_string(soap), soap_fault_detail(soap));
          } else {
            Debug(1, "Good Initial ONVIF Pull");
            Event_Poller_Healthy = TRUE;
          }
        }
#else
        Error("zmc not compiled with GSOAP. ONVIF support not built in!");
#endif
      }
    } else {
      Debug(1, "Not Starting ONVIF");
    }
    //End ONVIF Setup

    if (janus_enabled) {
      Janus_Manager = new JanusManager(this);
    }

#if MOSQUITTOPP_FOUND
    if (mqtt_enabled) {
      mqtt = zm::make_unique<MQTT>(this);
      for (const std::string &subscription : mqtt_subscriptions) {
        if (!subscription.empty())
          mqtt->add_subscription(subscription);
      }
    }
#endif
  } else if (!shared_data->valid) {
    Error("Shared data not initialised by capture daemon for monitor %s", name.c_str());
    return false;
  }

  // We set these here because otherwise the first fps calc is meaningless
  last_fps_time = std::chrono::system_clock::now();
  last_analysis_fps_time = std::chrono::system_clock::now();

  Debug(3, "Success connecting");
  return true;
} // Monitor::connect

bool Monitor::disconnect() {
  zones.clear();
  delete linked_monitors;
  linked_monitors = nullptr;
  if (mem_ptr == nullptr) {
    Debug(1, "Already disconnected");
    return true;
  }

  alarm_image.HoldBuffer(false); /* Allow to reset buffer when we connect */
  if (purpose == CAPTURE) {
    if (unlink(mem_file.c_str()) < 0) {
      Warning("Can't unlink '%s': %s", mem_file.c_str(), strerror(errno));
    }
    Debug(1, "Setting shared_data->valid = false");
    shared_data->valid = false;
  }
#if ZM_MEM_MAPPED
  msync(mem_ptr, mem_size, MS_ASYNC);
  munmap(mem_ptr, mem_size);
  if (map_fd >= 0) close(map_fd);

  map_fd = -1;
  mem_ptr = nullptr;
  shared_data = nullptr;

#else // ZM_MEM_MAPPED
  struct shmid_ds shm_data;
  if (shmctl(shm_id, IPC_STAT, &shm_data) < 0) {
    Debug(3, "Can't shmctl: %s", strerror(errno));
    return false;
  }

  shm_id = 0;

  if ((shm_data.shm_nattch <= 1) and (shmctl(shm_id, IPC_RMID, 0) < 0)) {
    Debug(3, "Can't shmctl: %s", strerror(errno));
    return false;
  }

  if (shmdt(mem_ptr) < 0) {
    Debug(3, "Can't shmdt: %s", strerror(errno));
    return false;
  }
#endif // ZM_MEM_MAPPED

  for (int32_t i = 0; i < image_buffer_count; i++) {
    // We delete the image because it is an object pointing to space that won't be free'd.
    delete image_buffer[i];
    image_buffer[i] = nullptr;
  }

  return true;
}  // end bool Monitor::disconnect()

Monitor::~Monitor() {
#if MOSQUITTOPP_FOUND
  if (mqtt) {
    mqtt->send("offline");
  }
#endif
  Close();

  if (mem_ptr != nullptr) {
    if (purpose != QUERY) {
      memset(mem_ptr, 0, mem_size);
    }  // end if purpose != query
    disconnect();
  }  // end if mem_ptr

  // Will be free by packetqueue destructor
  analysis_it = nullptr;
  decoder_it = nullptr;

  delete storage;
  delete linked_monitors;
  linked_monitors = nullptr;

  if (video_fifo) delete video_fifo;
  if (audio_fifo) delete audio_fifo;
  if (convert_context) {
    sws_freeContext(convert_context);
    convert_context = nullptr;
  }
  if (Amcrest_Manager != nullptr) {
    delete Amcrest_Manager;
  } else {
  }
}  // end Monitor::~Monitor()

void Monitor::AddPrivacyBitmask() {
  if (privacy_bitmask) {
    delete[] privacy_bitmask;
    privacy_bitmask = nullptr;
  }
  Image *privacy_image = nullptr;

  for (const Zone &zone : zones) {
    if (zone.IsPrivacy()) {
      if (!privacy_image) {
        privacy_image = new Image(width, height, 1, ZM_SUBPIX_ORDER_NONE);
        privacy_image->Clear();
      }
      privacy_image->Fill(0xff, zone.GetPolygon());
      privacy_image->Outline(0xff, zone.GetPolygon());
    }
  } // end foreach zone
  if (privacy_image)
    privacy_bitmask = privacy_image->Buffer();
}

Image *Monitor::GetAlarmImage() {
  return &alarm_image;
}

int Monitor::GetImage(int32_t index, int scale) {
  if (index < 0 || index > image_buffer_count) {
    Debug(1, "Invalid index %d passed. image_buffer_count = %d", index, image_buffer_count);
    index = shared_data->last_write_index;
  }
  if (!image_buffer.size() or static_cast<size_t>(index) >= image_buffer.size()) {
    Error("Image Buffer has not been allocated");
    return -1;
  }
  if ( index == image_buffer_count ) {
    Error("Unable to generate image, no images in buffer");
    return 0;
  }

  std::string filename = stringtf("Monitor%u.jpg", id);
  // If we are going to be modifying the snapshot before writing, then we need to copy it
  if ((scale != ZM_SCALE_BASE) || (!config.timestamp_on_capture)) {
    Image image;
    image.Assign(*image_buffer[index]);

    if (scale != ZM_SCALE_BASE) {
      image.Scale(scale);
    }

    if (!config.timestamp_on_capture) {
      TimestampImage(&image, SystemTimePoint(zm::chrono::duration_cast<Microseconds>(shared_timestamps[index])));
    }
    return image.WriteJpeg(filename);
  } else {
    return image_buffer[index]->WriteJpeg(filename);
  }
}

ZMPacket *Monitor::getSnapshot(int index) const {
  if ((index < 0) || (index >= image_buffer_count)) {
    index = shared_data->last_write_index;
  }
  if (!image_buffer.size() or static_cast<size_t>(index) >= image_buffer.size()) {
    Error("Image Buffer has not been allocated");
    return nullptr;
  }
  if (index != image_buffer_count) {
    return new ZMPacket(image_buffer[index],
                        SystemTimePoint(zm::chrono::duration_cast<Microseconds>(shared_timestamps[index])));
  } else {
    Error("Unable to generate image, no images in buffer");
  }
  return nullptr;
}

SystemTimePoint Monitor::GetTimestamp(int index) const {
  ZMPacket *packet = getSnapshot(index);
  if (packet) 
    return packet->timestamp;

  return {};
}

unsigned int Monitor::GetLastReadIndex() const {
  return ( shared_data->last_read_index != image_buffer_count ? shared_data->last_read_index : -1 );
}

unsigned int Monitor::GetLastWriteIndex() const {
  return ( shared_data->last_write_index != image_buffer_count ? shared_data->last_write_index : -1 );
}

uint64_t Monitor::GetLastEventId() const {
  return shared_data->last_event_id;
}

// This function is crap.
double Monitor::GetFPS() const {
  return get_capture_fps();
}

/* I think this returns the # of micro seconds that we should sleep in order to maintain the desired analysis rate */
useconds_t Monitor::GetAnalysisRate() {
  double capture_fps = get_capture_fps();
  if ( !analysis_fps_limit ) {
    return 0;
  } else if ( analysis_fps_limit > capture_fps ) {
    if ( last_fps_time != last_analysis_fps_time ) {
      // At startup they are equal, should never be equal again
      Warning("Analysis fps (%.2f) is greater than capturing fps (%.2f)", analysis_fps_limit, capture_fps);
    }
    return 0;
  } else if ( capture_fps ) {
    return( ( 1000000 / analysis_fps_limit ) - ( 1000000 / capture_fps ) );
  }
  return 0;
}

void Monitor::UpdateAdaptiveSkip() {
  if ( config.opt_adaptive_skip ) {
    double capturing_fps = get_capture_fps();
    double analysis_fps = get_analysis_fps();
    if ( adaptive_skip && analysis_fps && ( analysis_fps < capturing_fps ) ) {
      Info("Analysis fps (%.2f) is lower than capturing fps (%.2f), disabling adaptive skip feature", analysis_fps, capturing_fps);
      adaptive_skip = false;
    } else if ( !adaptive_skip && ( !analysis_fps || ( analysis_fps >= capturing_fps ) ) ) {
      Info("Enabling adaptive skip feature");
      adaptive_skip = true;
    }
  } else {
    adaptive_skip = false;
  }
}

void Monitor::ForceAlarmOn( int force_score, const char *force_cause, const char *force_text ) {
  trigger_data->trigger_state = TriggerState::TRIGGER_ON;
  trigger_data->trigger_score = force_score;
  strncpy(trigger_data->trigger_cause, force_cause, sizeof(trigger_data->trigger_cause)-1);
  strncpy(trigger_data->trigger_text, force_text, sizeof(trigger_data->trigger_text)-1);
}

void Monitor::ForceAlarmOff() {
  trigger_data->trigger_state = TriggerState::TRIGGER_OFF;
}

void Monitor::CancelForced() {
  trigger_data->trigger_state = TriggerState::TRIGGER_CANCEL;
}

void Monitor::actionReload() {
  shared_data->action |= RELOAD;
}

void Monitor::actionEnable() {
  shared_data->action |= RELOAD;
  shared_data->capturing = true;
}

void Monitor::actionDisable() {
  shared_data->action |= RELOAD;
  shared_data->capturing = false;
}

void Monitor::actionSuspend() {
  shared_data->action |= SUSPEND;
}

void Monitor::actionResume() {
  shared_data->action |= RESUME;
}

int Monitor::actionBrightness(int p_brightness) {
  if (purpose == CAPTURE) {
    // We are the capture process, so take the action
    return camera->Brightness(p_brightness);
  }

  // If we are an outside actor, sending the command
  shared_data->brightness = p_brightness;
  shared_data->action |= SET_SETTINGS;
  int wait_loops = 10;
  while (shared_data->action & SET_SETTINGS) {
    if (wait_loops--) {
      std::this_thread::sleep_for(Milliseconds(100));
    } else {
      Warning("Timed out waiting to set brightness");
      return -1;
    }
  }
  return shared_data->brightness;
}

int Monitor::actionBrightness() {
  if (purpose == CAPTURE) {
    // We are the capture process, so take the action
    return camera->Brightness();
  }

  // If we are an outside actor, sending the command
  shared_data->action |= GET_SETTINGS;
  int wait_loops = 10;
  while (shared_data->action & GET_SETTINGS) {
    if (wait_loops--) {
      std::this_thread::sleep_for(Milliseconds(100));
    } else {
      Warning("Timed out waiting to get brightness");
      return -1;
    }
  }
  return shared_data->brightness;
} // end int Monitor::actionBrightness()

int Monitor::actionContrast(int p_contrast) {
  if (purpose == CAPTURE) {
    return camera->Contrast(p_contrast);
  }

  shared_data->contrast = p_contrast;
  shared_data->action |= SET_SETTINGS;
  int wait_loops = 10;
  while (shared_data->action & SET_SETTINGS) {
    if (wait_loops--) {
      std::this_thread::sleep_for(Milliseconds(100));
    } else {
      Warning("Timed out waiting to set contrast");
      return -1;
    }
  }
  return shared_data->contrast;
}

int Monitor::actionContrast() {
  if (purpose == CAPTURE) {
    // We are the capture process, so take the action
    return camera->Contrast();
  }

  shared_data->action |= GET_SETTINGS;
  int wait_loops = 10;
  while (shared_data->action & GET_SETTINGS) {
    if (wait_loops--) {
      std::this_thread::sleep_for(Milliseconds(100));
    } else {
      Warning("Timed out waiting to get contrast");
      return -1;
    }
  }
  return shared_data->contrast;
} // end int Monitor::actionContrast()

int Monitor::actionHue(int p_hue) {
  if (purpose == CAPTURE) {
    return camera->Hue(p_hue);
  }

  shared_data->hue = p_hue;
  shared_data->action |= SET_SETTINGS;
  int wait_loops = 10;
  while (shared_data->action & SET_SETTINGS) {
    if (wait_loops--) {
      std::this_thread::sleep_for(Milliseconds(100));
    } else {
      Warning("Timed out waiting to set hue");
      return -1;
    }
  }
  return shared_data->hue;
}

int Monitor::actionHue() {
  if (purpose == CAPTURE) {
    return camera->Hue();
  }
  shared_data->action |= GET_SETTINGS;
  int wait_loops = 10;
  while (shared_data->action & GET_SETTINGS) {
    if (wait_loops--) {
      std::this_thread::sleep_for(Milliseconds(100));
    } else {
      Warning("Timed out waiting to get hue");
      return -1;
    }
  }
  return shared_data->hue;
} // end int Monitor::actionHue(int p_hue)

int Monitor::actionColour(int p_colour) {
  if (purpose == CAPTURE) {
    return camera->Colour(p_colour);
  }
  shared_data->colour = p_colour;
  shared_data->action |= SET_SETTINGS;
  int wait_loops = 10;
  while (shared_data->action & SET_SETTINGS) {
    if (wait_loops--) {
      std::this_thread::sleep_for(Milliseconds(100));
    } else {
      Warning("Timed out waiting to set colour");
      return -1;
    }
  }
  return shared_data->colour;
} 

int Monitor::actionColour() {
  if (purpose == CAPTURE) {
    return camera->Colour();
  }
  shared_data->action |= GET_SETTINGS;
  int wait_loops = 10;
  while (shared_data->action & GET_SETTINGS) {
    if (wait_loops--) {
      std::this_thread::sleep_for(Milliseconds(100));
    } else {
      Warning("Timed out waiting to get colour");
      return -1;
    }
  }
  return shared_data->colour;
} // end int Monitor::actionColour(int p_colour)

void Monitor::DumpZoneImage(const char *zone_string) {
  unsigned int exclude_id = 0;
  int extra_colour = 0;
  Polygon extra_zone;

  if ( zone_string ) {
    if ( !Zone::ParseZoneString(zone_string, exclude_id, extra_colour, extra_zone) ) {
      Error("Failed to parse zone string, ignoring");
    }
  }

  Image *zone_image = nullptr;
  if ( ( (!staticConfig.SERVER_ID) || ( staticConfig.SERVER_ID == server_id ) ) && mem_ptr ) {
    Debug(3, "Trying to load from local zmc");
    int index = shared_data->last_write_index;
    ZMPacket *snap = getSnapshot(index);
    zone_image = new Image(*snap->image);
  } else {
    Debug(3, "Trying to load from event");

    // Grab the most revent event image
    zmDbQuery query = zmDbQuery( SELECT_MAX_EVENTS_ID_WITH_MONITORID_AND_FRAMES_NOT_ZERO );
    query.bind( "id", id );
    query.fetchOne();

    if( query.affectedRows() != 1 ) {
      Error("Unable to load an event for monitor %d", id);
      return;
    }

    uint64_t event_id = query.get<uint64_t>( 0 );

    Debug(3, "Got event %" PRIu64, event_id);
    EventStream *stream = new EventStream();
    stream->setStreamStart(event_id, (unsigned int)1);
    zone_image = stream->getImage();
    delete stream;
    stream = nullptr;
  }

  if ( zone_image->Colours() == ZM_COLOUR_GRAY8 ) {
    zone_image->Colourise(ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  }

  extra_zone.Clip(Box(
      {0, 0},
      {static_cast<int32>(zone_image->Width()), static_cast<int32>(zone_image->Height())}
  ));

  for (const Zone &zone : zones) {
    if (exclude_id && (!extra_colour || !extra_zone.GetVertices().empty()) && zone.Id() == exclude_id) {
      continue;
    }

    Rgb colour;
    if (exclude_id && extra_zone.GetVertices().empty() && zone.Id() == exclude_id) {
      colour = extra_colour;
    } else {
      if (zone.IsActive()) {
        colour = kRGBRed;
      } else if (zone.IsInclusive()) {
        colour = kRGBOrange;
      } else if (zone.IsExclusive()) {
        colour = kRGBPurple;
      } else if (zone.IsPreclusive()) {
        colour = kRGBBlue;
      } else {
        colour = kRGBWhite;
      }
    }
    zone_image->Fill(colour, 2, zone.GetPolygon());
    zone_image->Outline(colour, zone.GetPolygon());
  }

  if (!extra_zone.GetVertices().empty()) {
    zone_image->Fill(extra_colour, 2, extra_zone);
    zone_image->Outline(extra_colour, extra_zone);
  }

  std::string filename = stringtf("Zones%u.jpg", id);
  zone_image->WriteJpeg(filename);
  delete zone_image;
} // end void Monitor::DumpZoneImage(const char *zone_string)

void Monitor::DumpImage(Image *dump_image) const {
  if (image_count && !(image_count % 10)) {

    std::string filename = stringtf("Monitor%u.jpg", id);
    std::string new_filename = stringtf("Monitor%u-new.jpg", id);

    if (dump_image->WriteJpeg(new_filename)) {
      rename(new_filename.c_str(), filename.c_str());
    }
  }
} // end void Monitor::DumpImage(Image *dump_image)

bool Monitor::CheckSignal(const Image *image) {
  if (signal_check_points <= 0)
    return true;

  const uint8_t *buffer = image->Buffer();
  int pixels = image->Pixels();
  int width = image->Width();
  int colours = image->Colours();

  int index = 0;
  for (int i = 0; i < signal_check_points; i++) {
    while (true) {
      // Why the casting to long long? also note that on a 64bit cpu, long long is 128bits
      index = (int)(((long long)rand()*(long long)(pixels-1))/RAND_MAX);
      if (!config.timestamp_on_capture || !label_format[0])
        break;
      // Avoid sampling the rows with timestamp in
      if (
          index < (label_coord.y_ * width)
          ||
          index >= (label_coord.y_ + Image::LINE_HEIGHT) * width
          ) {
        break;
      }
    }

    if (colours == ZM_COLOUR_GRAY8) {
      if (*(buffer+index) != grayscale_val)
        return true;

    } else if (colours == ZM_COLOUR_RGB24) {
      const uint8_t *ptr = buffer+(index*colours);

      if (usedsubpixorder == ZM_SUBPIX_ORDER_BGR) {
        if ((RED_PTR_BGRA(ptr) != red_val) || (GREEN_PTR_BGRA(ptr) != green_val) || (BLUE_PTR_BGRA(ptr) != blue_val))
          return true;
      } else {
        /* Assume RGB */
        if ((RED_PTR_RGBA(ptr) != red_val) || (GREEN_PTR_RGBA(ptr) != green_val) || (BLUE_PTR_RGBA(ptr) != blue_val))
          return true;
      }

    } else if (colours == ZM_COLOUR_RGB32) {
      if (usedsubpixorder == ZM_SUBPIX_ORDER_ARGB || usedsubpixorder == ZM_SUBPIX_ORDER_ABGR) {
        if (ARGB_ABGR_ZEROALPHA(*(((const Rgb*)buffer)+index)) != ARGB_ABGR_ZEROALPHA(colour_val))
          return true;
      } else {
        /* Assume RGBA or BGRA */
        if (RGBA_BGRA_ZEROALPHA(*(((const Rgb*)buffer)+index)) != RGBA_BGRA_ZEROALPHA(colour_val))
          return true;
      }
    }
  } // end for < signal_check_points
  Debug(1, "SignalCheck: %d points, colour_val(%d)", signal_check_points, colour_val);
  return false;
} // end bool Monitor::CheckSignal(const Image *image)

void Monitor::CheckAction() {

  if (shared_data->action) {
    // Can there be more than 1 bit set in the action?  Shouldn't these be elseifs?
    if (shared_data->action & RELOAD) {
      Info("Received reload indication at count %d", image_count);
      shared_data->action &= ~RELOAD;
      Reload();
    }
    if (shared_data->action & SUSPEND) {
      if (Active()) {
        Info("Received suspend indication at count %d", image_count);
        shared_data->analysing = ANALYSING_NONE;
      } else {
        Info("Received suspend indication at count %d, but wasn't active", image_count);
      }
      if (config.max_suspend_time) {
        SystemTimePoint now = std::chrono::system_clock::now();
        auto_resume_time = now + Seconds(config.max_suspend_time);
      }
      shared_data->action &= ~SUSPEND;
    } else if (shared_data->action & RESUME) {
      if ( Enabled() && !Active() ) {
        Info("Received resume indication at count %d", image_count);
        shared_data->analysing = analysing;
        ref_image.DumpImgBuffer(); // Will get re-assigned by analysis thread
        shared_data->alarm_x = shared_data->alarm_y = -1;
      }
      shared_data->action &= ~RESUME;
    }
  } // end if shared_data->action

  if (auto_resume_time.time_since_epoch() != Seconds(0)) {
    SystemTimePoint now = std::chrono::system_clock::now();
    if (now >= auto_resume_time) {
      Info("Auto resuming at count %d", image_count);
      auto_resume_time = {};
      shared_data->analysing = analysing;
      ref_image.DumpImgBuffer(); // Will get re-assigned by analysis thread
    }
  }
}

void Monitor::UpdateFPS() {
  if ( fps_report_interval and
      (
       !(image_count%fps_report_interval)
      or
     ( (image_count < fps_report_interval) and !(image_count%10) )
     )
     ) {
    SystemTimePoint now = std::chrono::system_clock::now();
    FPSeconds elapsed = now - last_fps_time;

    // If we are too fast, we get div by zero. This seems to happen in the case of audio packets.
    // Also only do the update at most 1/sec
    if (elapsed > Seconds(1)) {
      // # of images per interval / the amount of time it took
      double new_capture_fps = (image_count - last_capture_image_count) / elapsed.count();
      uint32 new_camera_bytes = camera->Bytes();
      uint32 new_capture_bandwidth =
          static_cast<uint32>((new_camera_bytes - last_camera_bytes) / elapsed.count());
      double new_analysis_fps = (motion_frame_count - last_motion_frame_count) / elapsed.count();

      Debug(4, "FPS: capture count %d - last capture count %d = %d now:%lf, last %lf, elapsed %lf = capture: %lf fps analysis: %lf fps",
            image_count,
            last_capture_image_count,
            image_count - last_capture_image_count,
            FPSeconds(now.time_since_epoch()).count(),
            FPSeconds(last_fps_time.time_since_epoch()).count(),
            elapsed.count(),
            new_capture_fps,
            new_analysis_fps);

      Info("%s: %d - Capturing at %.2lf fps, capturing bandwidth %ubytes/sec Analysing at %.2lf fps",
          name.c_str(), image_count, new_capture_fps, new_capture_bandwidth, new_analysis_fps);

#if MOSQUITTOPP_FOUND
      if (mqtt) mqtt->send(stringtf("Capturing at %.2lf fps, capturing bandwidth %ubytes/sec Analysing at %.2lf fps",
          new_capture_fps, new_capture_bandwidth, new_analysis_fps));
#endif

      shared_data->capture_fps = new_capture_fps;
      last_fps_time = now;
      last_capture_image_count = image_count;
      shared_data->analysis_fps = new_analysis_fps;
      last_motion_frame_count = motion_frame_count;
      last_camera_bytes = new_camera_bytes;

      zmDbQuery query = zmDbQuery( UPDATE_MONITORSTATUS_WITH_MONITORID_SET_CAPTUREFPS );
      query.bind( "capture_fps", new_capture_fps );
      query.bind( "capture_bandwitdh", new_capture_bandwidth );
      query.bind( "analysis_fps", new_analysis_fps );
      query.bind( "id", id );
      query.update();
    } // now != last_fps_time
  } // end if report fps
}  // void Monitor::UpdateFPS()

//Thread where ONVIF polling, and other similar status polling can happen.
//Since these can be blocking, run here to avoid intefering with other processing
bool Monitor::Poll() {

  //We want to trigger every 5 seconds or so. so grab the time at the beginning of the loop, and sleep at the end.
  std::chrono::system_clock::time_point loop_start_time = std::chrono::system_clock::now();

  if (Event_Poller_Healthy) {
    if(use_Amcrest_API) {
      Amcrest_Manager->WaitForMessage();
    } else {

#ifdef WITH_GSOAP
      set_credentials(soap);
      int result = proxyEvent.PullMessages(response.SubscriptionReference.Address, NULL, &tev__PullMessages, tev__PullMessagesResponse);
      if (result != SOAP_OK) {
        if (result != SOAP_EOF) { //Ignore the timeout error
          Error("Failed to get ONVIF messages! %s", soap_fault_string(soap));
          Event_Poller_Healthy = false;
        }
      } else {
        Debug(1, "Got Good Response! %i", result);
        for (auto msg : tev__PullMessagesResponse.wsnt__NotificationMessage) {
          if (msg->Topic->__any.text != NULL &&
          std::strstr(msg->Topic->__any.text, onvif_alarm_txt.c_str()) &&
          msg->Message.__any.elts != NULL &&
          msg->Message.__any.elts->next != NULL &&
          msg->Message.__any.elts->next->elts != NULL &&
          msg->Message.__any.elts->next->elts->atts != NULL &&
          msg->Message.__any.elts->next->elts->atts->next != NULL &&
          msg->Message.__any.elts->next->elts->atts->next->text != NULL) {
          Debug(1,"Got Motion Alarm!");
            if (strcmp(msg->Message.__any.elts->next->elts->atts->next->text, "true") == 0) {
            //Event Start
              Debug(1,"Triggered on ONVIF");
              if (!Poll_Trigger_State) {
                Debug(1,"Triggered Event");
                Poll_Trigger_State = TRUE;
                std::this_thread::sleep_for (std::chrono::seconds(1)); //thread sleep
              }
            } else {
              Debug(1, "Triggered off ONVIF");
              Poll_Trigger_State = false;
              if (!Event_Poller_Closes_Event) { //If we get a close event, then we know to expect them.
                Event_Poller_Closes_Event = TRUE;
                Debug(1,"Setting ClosesEvent");
              }
            }
          }
        }
      }
#endif
    }
  }
  if (janus_enabled) {
    if (Janus_Manager->check_janus() == 0) {
      Janus_Manager->add_to_janus();
    }
  }
  std::this_thread::sleep_until(loop_start_time + std::chrono::seconds(5));
  return TRUE;
} //end Poll

// Would be nice if this JUST did analysis
// This idea is that we should be analysing as close to the capture frame as possible.
// This method should process as much as possible before returning
//
// If there is an event, the we should do our best to empty the queue.
// If there isn't then we keep pre-event + alarm frames. = pre_event_count
bool Monitor::Analyse() {
  // if have event, send frames until we find a video packet, at which point do analysis. Adaptive skip should only affect which frames we do analysis on.

  // get_analysis_packet will lock the packet and may wait if analysis_it is at the end
  ZMLockedPacket *packet_lock = packetqueue.get_packet(analysis_it);
  if (!packet_lock) return false;
  std::shared_ptr<ZMPacket> snap = packet_lock->packet_;

  // Is it possible for snap->score to be ! -1 ? Not if everything is working correctly
  if (snap->score != -1) {
    Error("skipping because score was %d at index %d", snap->score, snap->image_index);
    delete packet_lock;
    packetqueue.increment_it(analysis_it);
    return false;
  }
  // Ready means that we have captured the warmup # of frames
  if (!Ready()) {
    Debug(3, "Not ready?");
    delete packet_lock;
    return false;
  }

  // signal is set by capture
  bool signal = shared_data->signal;
  bool signal_change = (signal != last_signal);

  Debug(3, "Motion detection is enabled?(%d) signal(%d) signal_change(%d) trigger state(%s) image index %d",
      shared_data->analysing, signal, signal_change, TriggerState_Strings[trigger_data->trigger_state].c_str(), snap->image_index);

  { // scope for event lock
    // Need to guard around event creation/deletion from Reload()
    std::lock_guard<std::mutex> lck(event_mutex);

    // if we have been told to be OFF, then we are off and don't do any processing.
    if (trigger_data->trigger_state != TriggerState::TRIGGER_OFF) {
      Debug(4, "Trigger not OFF state is (%d)", int(trigger_data->trigger_state));

      int score = 0;
      std::string cause;
      Event::StringSetMap noteSetMap;

#ifdef WITH_GSOAP
      if (onvif_event_listener && Event_Poller_Healthy) {
        if (Poll_Trigger_State) {
          score += 9;
          Debug(1, "Triggered on ONVIF");
          Event::StringSet noteSet;
          noteSet.insert("ONVIF2");
          noteSetMap[MOTION_CAUSE] = noteSet;
          cause += "ONVIF";
          //If the camera isn't going to send an event close, we need to close it here, but only after it has actually triggered an alarm.
          if (!Event_Poller_Closes_Event && state == ALARM)
            Poll_Trigger_State = false;
        }  // end ONVIF_Trigger
      }  // end if (onvif_event_listener  && Event_Poller_Healthy)
#endif

      // Specifically told to be on.  Setting the score here is not enough to trigger the alarm. Must jump directly to ALARM
      if (trigger_data->trigger_state == TriggerState::TRIGGER_ON) {
        score += trigger_data->trigger_score;
        Debug(1, "Triggered on score += %d => %d", trigger_data->trigger_score, score);
        if (!cause.empty()) cause += ", ";
        cause += trigger_data->trigger_cause;
        Event::StringSet noteSet;
        noteSet.insert(trigger_data->trigger_text);
        noteSetMap[trigger_data->trigger_cause] = noteSet;
        shared_data->state = state = ALARM;
      }  // end if trigger_on

      // FIXME this snap might not be the one that caused the signal change.  Need to store that in the packet.
      if (signal_change) {
        Debug(2, "Signal change, new signal is %d", signal);
        if (!signal) {
          if (event) {
            event->addNote(SIGNAL_CAUSE, "Lost");
            Info("%s: %03d - Closing event %" PRIu64 ", signal loss", name.c_str(), analysis_image_count, event->Id());
            closeEvent();
          }
        } else if (shared_data->recording == RECORDING_ALWAYS) {
          if (!event) {
            if (!cause.empty()) cause += ", ";
            cause += SIGNAL_CAUSE + std::string(": Reacquired");
          } else {
            event->addNote(SIGNAL_CAUSE, "Reacquired");
          }
          if ((analysis_image == ANALYSISIMAGE_YCHANNEL) && snap->y_image) {
            Debug(1, "assigning refimage from y-channel");
            ref_image.Assign(*(snap->y_image));
          } else if (snap->image) {
            Debug(1, "assigning refimage from snap->image");
            ref_image.Assign(*(snap->image));
          }
        }
        shared_data->state = state = IDLE;
      }  // end if signal change

      if (signal) {
        if (snap->codec_type == AVMEDIA_TYPE_VIDEO) {
          // Check to see if linked monitors are triggering.
# if 0
          if (n_linked_monitors > 0) {
            Debug(1, "Checking linked monitors");
            // FIXME improve logic here
            bool first_link = true;
            Event::StringSet noteSet;
            for (int i = 0; i < n_linked_monitors; i++) {
              // TODO: Shouldn't we try to connect?
              if (linked_monitors[i]->isConnected()) {
                Debug(1, "Linked monitor %d %s is connected",
                    linked_monitors[i]->Id(), linked_monitors[i]->Name());
                if (linked_monitors[i]->hasAlarmed()) {
                  Debug(1, "Linked monitor %d %s is alarmed score will be %d",
                      linked_monitors[i]->Id(), linked_monitors[i]->Name(), linked_monitors[i]->lastFrameScore());
                  if (!event) {
                    if (first_link) {
                      if (cause.length())
                        cause += ", ";
                      cause += LINKED_CAUSE;
                      first_link = false;
                    }
                  }
                  noteSet.insert(linked_monitors[i]->Name());
                  score += linked_monitors[i]->lastFrameScore(); // 50;
                } else {
                  Debug(1, "Linked monitor %d %s is not alarmed",
                      linked_monitors[i]->Id(), linked_monitors[i]->Name());
                }
              } else {
                Debug(1, "Linked monitor %d %d is not connected. Connecting.", i, linked_monitors[i]->Id());
                linked_monitors[i]->connect();
              }
            } // end foreach linked_monitor
            if (noteSet.size() > 0)
              noteSetMap[LINKED_CAUSE] = noteSet;
          } // end if linked_monitors
#else
          if (linked_monitors) {
            bool eval = linked_monitors->evaluate();
            Debug(1, "evaluate %d", eval ? 1 : 0);
            if (eval) {
              if (!event) {
                if (cause.length())
                  cause += ", ";
                cause += LINKED_CAUSE;
              }
              //Event::StringSet noteSet;
              //noteSet.insert(linked_monitors[i]->Name());
              //score += linked_monitors->score();
              score += 20;
            }
          } else {
            Debug(1, "Not linked_monitors");
          }
#endif

          /* try to stay behind the decoder. */
          if (decoding != DECODING_NONE) {
            if (!snap->decoded) {
              // We no longer wait because we need to be checking the triggers and other inputs.
              // Also the logic is too hairy.  capture process can delete the packet that we have here.
              delete packet_lock;
              return false;
            }
          }  // end if decoding enabled

          if (shared_data->analysing > ANALYSING_NONE) {
            Debug(3, "signal and capturing and doing motion detection %d", shared_data->analysing);

            if (analysis_fps_limit) {
              double capture_fps = get_capture_fps();
              motion_frame_skip = capture_fps / analysis_fps_limit;
              Debug(1, "Recalculating motion_frame_skip (%d) = capture_fps(%f) / analysis_fps(%f)",
                  motion_frame_skip, capture_fps, analysis_fps_limit);
            }

            Event::StringSet zoneSet;

            if (snap->image) {
              // decoder may not have been able to provide an image
              if (!ref_image.Buffer()) {
                Debug(1, "Assigning instead of Detecting");
                if ((analysis_image == ANALYSISIMAGE_YCHANNEL) && snap->y_image) {
                  Debug(1, "assigning refimage from y-channel");
                  ref_image.Assign(*(snap->y_image));
                } else {
                  Debug(1, "assigning refimage from snap->image");
                  ref_image.Assign(*(snap->image));
                }
                alarm_image.Assign(*(snap->image));
              } else {
                // didn't assign, do motion detection maybe and blending definitely
                if (!(analysis_image_count % (motion_frame_skip+1))) {
                  Debug(1, "Detecting motion on image %d, image %p", snap->image_index, snap->image);
                  // Get new score.
                  if ((analysis_image == ANALYSISIMAGE_YCHANNEL) && snap->y_image) {
                    snap->score = DetectMotion(*(snap->y_image), zoneSet);
                  } else {
                    snap->score = DetectMotion(*(snap->image), zoneSet);
                  }

                  if (!snap->analysis_image)
                    snap->analysis_image = new Image(*(snap->image));
                  // lets construct alarm cause. It will contain cause + names of zones alarmed
                  snap->zone_stats.reserve(zones.size());
                  int zone_index = 0;
                  for (const Zone &zone : zones) {
                    const ZoneStats &stats = zone.GetStats();
                    stats.DumpToLog("After detect motion");
                    snap->zone_stats.push_back(stats);
                    if (zone.Alarmed()) {
                      if (!snap->alarm_cause.empty()) snap->alarm_cause += ",";
                      snap->alarm_cause += std::string(zone.Label());
                      if (zone.AlarmImage())
                        snap->analysis_image->Overlay(*(zone.AlarmImage()));
                    }
                    Debug(1, "Setting zone score %d to %d", zone_index, zone.Score());
                    zone_scores[zone_index] = zone.Score(); zone_index ++;
                  }
                  alarm_image.Assign(*(snap->analysis_image));
                  Debug(3, "After motion detection, score:%d last_motion_score(%d), new motion score(%d)",
                      score, last_motion_score, snap->score);
                  motion_frame_count += 1;
                  last_motion_score = snap->score;

                  if (snap->score) {
                    if (cause.length()) cause += ", ";
                    cause += MOTION_CAUSE + std::string(":") + snap->alarm_cause;
                    noteSetMap[MOTION_CAUSE] = zoneSet;
                    score += snap->score;
                  } // end if motion_score
                } else {
                  Debug(1, "Skipped motion detection last motion score was %d", last_motion_score);
                  alarm_image.Assign(*(snap->image));
                }

                if ((analysis_image == ANALYSISIMAGE_YCHANNEL) && snap->y_image) {
                  Debug(1, "Blending from y-channel");
                  ref_image.Blend(*(snap->y_image), ( state==ALARM ? alarm_ref_blend_perc : ref_blend_perc ));
                } else if (snap->image) {
                  Debug(1, "Blending full colour image because analysis_image = %d, in_frame=%p and format %d != %d, %d",
                      analysis_image, snap->in_frame.get(),
                      (snap->in_frame ? snap->in_frame->format : -1),
                      AV_PIX_FMT_YUV420P,
                      AV_PIX_FMT_YUVJ420P
                      );
                  ref_image.Blend(*(snap->image), ( state==ALARM ? alarm_ref_blend_perc : ref_blend_perc ));
                  Debug(1, "Done Blending");
                } else {
                  Debug(1, "Not able to blend");
                }
              } // end if had ref_image_buffer or not
            } else {
              Debug(1, "no image so skipping motion detection");
            }  // end if has image
          } else {
            Debug(1, "Not analysing %d", shared_data->analysing);
          } // end if active and doing motion detection


          // Set this before any state changes so that it's value is picked up immediately by linked monitors
          shared_data->last_frame_score = score;

          // If motion detecting, score will be > 0 on motion, but if skipping frames, might not be. So also test snap->score
          if ((score > 0) or (snap->score > 0)) {
            if ((state == IDLE) || (state == TAPE) || (state == PREALARM)) {

              if ((!pre_event_count) || (Event::PreAlarmCount() >= alarm_frame_count-1)) {
                Info("%s: %03d - ExtAlm - Gone into alarm state PreAlarmCount: %u > AlarmFrameCount:%u Cause:%s",
                    name.c_str(), snap->image_index, Event::PreAlarmCount(), alarm_frame_count, cause.c_str());
                shared_data->state = state = ALARM;

                // If we should end then previous continuous event and start a new non-continuous event
                if (event && event->Frames()
                    && (event->AlarmFrames() < alarm_frame_count)
                    && (event_close_mode == CLOSE_ALARM)
                    && (event->Duration() >= min_section_length)
                    ) {
                  Info("%s: %03d - Closing event %" PRIu64 ", continuous end, alarm begins",
                      name.c_str(), snap->image_index, event->Id());
                  closeEvent();
                } else if (event) {
                  // This is so if we need more than 1 alarm frame before going into alarm, so it is basically if we have enough alarm frames
                  Debug(3,
                        "pre_alarm_count in event %d of %d, event frames %d, alarm frames %d event length %" PRIi64 " >=? %" PRIi64 " min close mode is ALARM? %d",
                        Event::PreAlarmCount(), pre_event_count,
                        event->Frames(),
                        event->AlarmFrames(),
                        static_cast<int64>(std::chrono::duration_cast<Seconds>(event->Duration()).count()),
                        static_cast<int64>(Seconds(min_section_length).count()),
                        (event_close_mode == CLOSE_ALARM));
                }

              } else if (state != PREALARM) {
                Info("%s: %03d - Gone into prealarm state", name.c_str(), analysis_image_count);
                shared_data->state = state = PREALARM;
              }
            } else if (state == ALERT) {
              alert_to_alarm_frame_count--;
              Debug(1, "%s: %03d - Alarmed frame while in alert state. Consecutive alarmed frames left to return to alarm state: %03d",
                  name.c_str(), analysis_image_count, alert_to_alarm_frame_count);
              if (alert_to_alarm_frame_count == 0) {
                Info("%s: %03d - ExtAlm - Gone back into alarm state", name.c_str(), analysis_image_count);
                shared_data->state = state = ALARM;
              }
            } else if (state == TAPE) {
              // Already recording, but IDLE so switch to ALARM
              shared_data->state = state = ALARM;
              Debug(1, "Was in TAPE, going into ALARM");
            } else {
              Debug(1, "Staying in %s", State_Strings[state].c_str());
            }
            if (state == ALARM) {
              last_alarm_count = analysis_image_count;
            } // This is needed so post_event_count counts after last alarmed frames while in ALARM not single alarmed frames while ALERT

          // snap->score -1 means didn't do motion detection so don't do state transition
          // In Nodect, we may still have a triggered event, so need this code to run to end the event.
          } else if (!score and ((snap->score == 0) or (shared_data->analysing == ANALYSING_NONE))) {
            Debug(1, "!score %s", State_Strings[state].c_str());
            alert_to_alarm_frame_count = alarm_frame_count; // load same value configured for alarm_frame_count

            if (state == ALARM) {
              Info("%s: %03d - Gone into alert state", name.c_str(), analysis_image_count);
              shared_data->state = state = ALERT;
            } else if (state == ALERT) {
              if ( event &&
                  ((analysis_image_count - last_alarm_count) > post_event_count)
                  &&
                  (event->Duration() >= min_section_length)) {
                Info("%s: %03d - Left alarm state (%" PRIu64 ") - %d(%d) images",
                    name.c_str(), analysis_image_count, event->Id(), event->Frames(), event->AlarmFrames());
                if (
                    (shared_data->recording == RECORDING_ONMOTION)
                    ||
                    (event_close_mode == CLOSE_ALARM || event_close_mode==CLOSE_IDLE)
                    ) {
                  shared_data->state = state = IDLE;
                  Info("%s: %03d - Closing event %" PRIu64 ", alarm end%s",
                      name.c_str(), analysis_image_count, event->Id(), (shared_data->recording==RECORDING_ALWAYS)?", section truncated":"" );

                  closeEvent();
                } else {
                  shared_data->state = state = TAPE;
                }
              }
            } else if (state == PREALARM) {
              // Back to IDLE
              shared_data->state = state = ((shared_data->recording == RECORDING_ALWAYS) ? IDLE : TAPE);
            } else {
              Debug(1,
                    "State %d %s because analysis_image_count(%d)-last_alarm_count(%d) = %d > post_event_count(%d) and timestamp.tv_sec(%" PRIi64 ") - recording.tv_src(%" PRIi64 ") >= min_section_length(%" PRIi64 ")",
                    state,
                    State_Strings[state].c_str(),
                    analysis_image_count,
                    last_alarm_count,
                    analysis_image_count - last_alarm_count,
                    post_event_count,
                    static_cast<int64>(std::chrono::duration_cast<Seconds>(snap->timestamp.time_since_epoch()).count()),
                    static_cast<int64>(std::chrono::duration_cast<Seconds>(GetVideoWriterStartTime().time_since_epoch()).count()),
                    static_cast<int64>(Seconds(min_section_length).count()));
            }
            if (Event::PreAlarmCount())
              Event::EmptyPreAlarmFrames();
          } // end if score or not

          // At this point, snap ONLY has motion score, so this adds other sources
          if (score > snap->score)
            snap->score = score;

          if (state == PREALARM) {
            // incremement pre alarm image count
            Event::AddPreAlarmFrame(snap->image, snap->timestamp, score, nullptr);
          } else if (state == ALARM) {
            if (event) {
              if (section_length >= Seconds(min_section_length) && (event->Duration() >= section_length)) {
                if (section_length_warn)
                  Warning("%s: %03d - event %" PRIu64 ", has exceeded desired section length. %" PRIi64 " - %" PRIi64 " = %" PRIi64 " >= %" PRIi64,
                      name.c_str(), analysis_image_count, event->Id(),
                      static_cast<int64>(std::chrono::duration_cast<Seconds>(snap->timestamp.time_since_epoch()).count()),
                      static_cast<int64>(std::chrono::duration_cast<Seconds>(event->StartTime().time_since_epoch()).count()),
                      static_cast<int64>(std::chrono::duration_cast<Seconds>(event->Duration()).count()),
                      static_cast<int64>(Seconds(section_length).count()));
                closeEvent();
                event = openEvent(snap, cause, noteSetMap);
              } else if (noteSetMap.size() > 0) {
                event->updateNotes(noteSetMap);
              }
            } else if (shared_data->recording != RECORDING_NONE) {
              if ((event = openEvent(snap, cause, noteSetMap)) != nullptr) {
                Info("%s: %03d - Opening new event %" PRIu64 ", alarm start", name.c_str(), analysis_image_count, event->Id());
                if (alarm_frame_count) {
                  Debug(1, "alarm frame count so SavePreAlarmFrames");
                  event->SavePreAlarmFrames();
                }
              }
            }
          } else if (state == ALERT) {
            // Alert means this frame has no motion, but we were alarmed and are still recording.
            if ((noteSetMap.size() > 0) and event)
              event->updateNotes(noteSetMap);
          } else if (state == TAPE || state == IDLE) {
            if (event) {
              if (section_length >= Seconds(min_section_length) && (event->Duration() >= section_length)) {
                Debug(1, "%s: event %" PRIu64 ", has exceeded desired section length. %" PRIi64 " - %" PRIi64 " = %" PRIi64 " >= %" PRIi64,
                        name.c_str(), event->Id(),
                        static_cast<int64>(std::chrono::duration_cast<Seconds>(snap->timestamp.time_since_epoch()).count()),
                        static_cast<int64>(std::chrono::duration_cast<Seconds>(event->StartTime().time_since_epoch()).count()),
                        static_cast<int64>(std::chrono::duration_cast<Seconds>(event->Duration()).count()),
                        static_cast<int64>(Seconds(section_length).count()));
                closeEvent();
              }
            }
          } // end if state machine

          if (shared_data->recording > RECORDING_NONE) {
            // If doing record, check to see if we need to close the event or not.
            if (event && (section_length >= Seconds(min_section_length)) && (event->Duration() > section_length)) {
              if (
                  ((shared_data->recording == RECORDING_ONMOTION) && (event_close_mode != CLOSE_TIME))
                  || ((shared_data->recording == RECORDING_ALWAYS) && (event_close_mode == CLOSE_TIME))
                  || std::chrono::duration_cast<Seconds>(snap->timestamp.time_since_epoch()) % section_length == Seconds(0)) {

                Info("%s: %03d - Closing event %" PRIu64 ", section end forced %" PRIi64 " - %" PRIi64 " = %" PRIi64 " >= %" PRIi64 ,
                    name.c_str(),
                    snap->image_index,
                    event->Id(),
                    static_cast<int64>(std::chrono::duration_cast<Seconds>(snap->timestamp.time_since_epoch()).count()),
                    static_cast<int64>(std::chrono::duration_cast<Seconds>(event->StartTime().time_since_epoch()).count()),
                    static_cast<int64>(std::chrono::duration_cast<Seconds>(snap->timestamp - event->StartTime()).count()),
                    static_cast<int64>(Seconds(section_length).count()));
                closeEvent();
              }  // end if section_length
            }  // end if event

            if (!event and (shared_data->recording == RECORDING_ALWAYS)) {
              if ((event = openEvent(snap, cause.empty() ? "Continuous" : cause, noteSetMap)) != nullptr) {
                Info("%s: %03d - Opened new event %" PRIu64 ", continuous section start",
                    name.c_str(), analysis_image_count, event->Id());
                /* To prevent cancelling out an existing alert\prealarm\alarm state */
                // This ignores current score status.  This should all come after the state machine calculations
                if (state == IDLE) {
                  shared_data->state = state = TAPE;
                }
              }
            } // end if ! event
          } // end if RECORDING

          last_signal = signal;
        } // end if videostream
      } // end if signal
    } else {
      Debug(3, "trigger == off");
      if (event) {
        Info("%s: %03d - Closing event %" PRIu64 ", trigger off", name.c_str(), analysis_image_count, event->Id());
        closeEvent();
      }
      shared_data->state = state = IDLE;
    } // end if ( trigger_data->trigger_state != TRIGGER_OFF )


    if (snap->codec_type == AVMEDIA_TYPE_VIDEO) {
      packetqueue.clearPackets(snap);
      // Only do these if it's a video packet.
      shared_data->last_read_index = snap->image_index;
      analysis_image_count++;
    }

    if (event) {
      event->AddPacket(packet_lock);
    } else {
      // In the case where people have pre-alarm frames, the web ui will generate the frame images
      // from the mp4. So no one will notice anyways.
      if (snap->image and ((videowriter == PASSTHROUGH) || shared_data->recording == RECORDING_NONE)) {
        if (!savejpegs) {
          Debug(1, "Deleting image data for %d", snap->image_index);
          // Don't need raw images anymore
          delete snap->image;
          snap->image = nullptr;
        }
        if (snap->analysis_image and !(savejpegs & 2)) {
          Debug(1, "Deleting analysis image data for %d", snap->image_index);
          delete snap->analysis_image;
          snap->analysis_image = nullptr;
        }
      }
      // Free up the decoded frame as well, we won't be using it for anything at this time.
      snap->out_frame = nullptr;

      delete packet_lock;
    }
  }  // end scope for event_lock

  packetqueue.increment_it(analysis_it);
  shared_data->last_read_time = std::chrono::system_clock::to_time_t(std::chrono::system_clock::now());

  return true;
} // end Monitor::Analyse

void Monitor::Reload() {
  Debug(1, "Reloading monitor %s", name.c_str());

  // Access to the event needs to be protected.  Either thread could call Reload.  Either thread could close the event. 
  // Need a mutex on it I guess.  FIXME
  // Need to guard around event creation/deletion This will prevent event creation until new settings are loaded
  {
    std::lock_guard<std::mutex> lck(event_mutex);
    if (event) {
      Info("%s: %03d - Closing event %" PRIu64 ", reloading", name.c_str(), image_count, event->Id());
      closeEvent();
    }
  }

  zmDbQuery query = zmDbQuery( SELECT_MONITOR_WITH_ID );
  query.bind( "id", id );
  query.fetchOne();

  if( query.affectedRows() != 1 ) {
    Error("Can't run query");
    return;
  }

  Load(query, true /*load zones */, purpose);

}  // end void Monitor::Reload()

void Monitor::ReloadZones() {
  Debug(3, "Reloading zones for monitor %s have %zu", name.c_str(), zones.size());
  zones = Zone::Load(shared_from_this());
  Debug(1, "Reloading zones for monitor %s have %zu", name.c_str(), zones.size());
  this->AddPrivacyBitmask();
  //DumpZoneImage();
} // end void Monitor::ReloadZones()

void Monitor::ReloadLinkedMonitors() {
  Debug(1, "Reloading linked monitors for monitor %s, '%s'",
      name.c_str(), linked_monitors_string.c_str());
  delete linked_monitors;
  linked_monitors = nullptr;

  n_linked_monitors = 0;
  if (!linked_monitors_string.empty()) {
#if 0
    StringVector link_strings = Split(linked_monitors_string, ',');
    int n_link_ids = link_strings.size();
    if (n_link_ids > 0) {
      Debug(1, "Linking to %d monitors", n_link_ids);
      linked_monitors = new MonitorLink *[n_link_ids];

      int count = 0;
      for (std::string link : link_strings) {
        auto colon_position = link.find(':');
        unsigned int monitor_id = 0;
        unsigned int zone_id = 0;
        std::string monitor_name;
        std::string zone_name;

        if (colon_position != std::string::npos) {
          // Has a zone specification
          monitor_id = std::stoul(link.substr(0, colon_position));
          zone_id = std::stoul(link.substr(colon_position+1, std::string::npos));
        } else {
          monitor_id = std::stoul(link);
        }
        Debug(1, "Checking linked monitor %d zone %d", monitor_id, zone_id);

        std::shared_ptr<Monitor> linked_monitor = Load(monitor_id, false, QUERY);
        linked_monitors[count++] = new MonitorLink(linked_monitor, zone_id);
      }  // end foreach link_id
      n_linked_monitors = count;
    }  // end if has link_ids
  } else {
      // Logical expression
       booleval::evaluator evaluator { booleval::make_field( "field", &Monitor::MonitorLink::hasAlarmed ) };
    }
#endif
    linked_monitors = new MonitorLinkExpression(linked_monitors_string);
    if (!linked_monitors->parse()) {
      Warning("Failed parsing linked_monitors");
      delete linked_monitors;
      linked_monitors = nullptr;
    }
  }  // end if p_linked_monitors
}  // end void Monitor::ReloadLinkedMonitors()

std::vector<std::shared_ptr<Monitor>> Monitor::LoadMonitors(zmDbQuery &query, Purpose purpose) {
  Debug(1, "Loading Monitors");

  query.run(true);

  if( query.affectedRows() == 0 ) {
    Error("Can't load local monitors");
    return {};
  }

  int n_monitors = query.affectedRows();
  Debug(1, "Got %d monitors", n_monitors);

  std::vector<std::shared_ptr<Monitor>> monitors;
  monitors.reserve(n_monitors);

  while( query.next() ) {
    monitors.emplace_back(std::make_shared<Monitor>());
    monitors.back()->Load(query, true, purpose);
  }

  return monitors;
}

#if ZM_HAS_V4L2
std::vector<std::shared_ptr<Monitor>> Monitor::LoadLocalMonitors
(const char *device, Purpose purpose) {

  zmDbQuery query;

  if( !device[0] && !staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE );
    query.bind<std::string>( "type", "Local" );

  } else if ( !device[0] && staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_SERVER );
    query.bind<std::string>( "type", "Local" );
    query.bind( "server_id", staticConfig.SERVER_ID );

  } else if ( device[0] && !staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_DEVICE );
    query.bind<std::string>( "type", "Local" );
    query.bind( "device", std::string(device) );

  } else {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_DEVICE_AND_SERVER );
    query.bind<std::string>( "type", "Local" );
    query.bind( "server_id", staticConfig.SERVER_ID );
    query.bind( "device", std::string(device) );
  }

  return LoadMonitors(query, purpose);
}
#endif // ZM_HAS_V4L2

std::vector<std::shared_ptr<Monitor>> Monitor::LoadRemoteMonitors
(const char *protocol, const char *host, const char *port, const char *path, Purpose purpose) {
  zmDbQuery query;

  if( !protocol && !staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE );
    query.bind<std::string>( "type", "Remote" );

  } else if ( !protocol && staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_SERVER );
    query.bind<std::string>( "type", "Remote" );
    query.bind( "server_id", staticConfig.SERVER_ID );

  } else if ( protocol && !staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_PROTOCOL );
    query.bind<std::string>( "type", "Remote" );
    query.bind( "protocol", std::string(protocol) );
    query.bind( "host", std::string(host) );
    query.bind( "port", std::string(port) );
    query.bind( "path", std::string(path) );

  } else {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_SERVER_AND_PROTOCOL );
    query.bind<std::string>( "type", "Remote" );
    query.bind( "server_id", staticConfig.SERVER_ID );
    query.bind( "protocol", std::string(protocol) );
    query.bind( "host", std::string(host) );
    query.bind( "port", std::string(port) );
    query.bind( "path", std::string(path) );
  }

  return LoadMonitors(query, purpose);
}

std::vector<std::shared_ptr<Monitor>> Monitor::LoadFileMonitors(const char *file, Purpose purpose) {
  zmDbQuery query;

  if( !file[0] && !staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE );
    query.bind<std::string>( "type", "File" );

  } else if ( !file[0] && staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_SERVER );
    query.bind<std::string>( "type", "File" );
    query.bind( "server_id", staticConfig.SERVER_ID );

  } else if ( file[0] && !staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_PATH );
    query.bind<std::string>( "type", "File" );
    query.bind( "path", std::string(file) );

  } else {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_PATH_AND_SERVER );
    query.bind<std::string>( "type", "File" );
    query.bind( "server_id", staticConfig.SERVER_ID );
    query.bind( "path", std::string(file) );
  }

  return LoadMonitors(query, purpose);
}

std::vector<std::shared_ptr<Monitor>> Monitor::LoadFfmpegMonitors(const char *file, Purpose purpose) {
  zmDbQuery query;

  if( !file[0] && !staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE );
    query.bind<std::string>( "type", "Ffmpeg" );

  } else if ( !file[0] && staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_SERVER );
    query.bind<std::string>( "type", "Ffmpeg" );
    query.bind( "server_id", staticConfig.SERVER_ID );

  } else if ( file[0] && !staticConfig.SERVER_ID ) {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_PATH );
    query.bind<std::string>( "type", "Ffmpeg" );
    query.bind( "path", std::string(file) );

  } else {
    query = zmDbQuery( SELECT_MONITOR_TYPE_AND_PATH_AND_SERVER );
    query.bind<std::string>( "type", "Ffmpeg" );
    query.bind( "server_id", staticConfig.SERVER_ID );
    query.bind( "path", std::string(file) );
  }

  return LoadMonitors(query, purpose);
}

/* Returns 0 on success, even if no new images are available (transient error)
 * Returns -1 on failure.
 */
int Monitor::Capture() {
  unsigned int index = image_count % image_buffer_count;
  if (image_buffer.empty() or (index >= image_buffer.size())) {
    Error("Image Buffer is invalid. Check ImageBufferCount. size is %zu", image_buffer.size());
    return -1;
  }

  std::shared_ptr<ZMPacket> packet = std::make_shared<ZMPacket>();
  packet->image_index = image_count;
  packet->timestamp = std::chrono::system_clock::now();
  shared_data->heartbeat_time = std::chrono::system_clock::to_time_t(packet->timestamp);
  int captureResult = camera->Capture(packet);
  Debug(4, "Back from capture result=%d image count %d", captureResult, image_count);

  if (captureResult < 0) {
    // Unable to capture image
    // Fake a signal loss image
    // Not sure what to do here.  We will close monitor and kill analysis_thread but what about rtsp server?
    Rgb signalcolor;
    /* HTML colour code is actually BGR in memory, we want RGB */
    signalcolor = rgb_convert(signal_check_colour, ZM_SUBPIX_ORDER_BGR);
    Image *capture_image = new Image(width, height, camera->Colours(), camera->SubpixelOrder());
    capture_image->Fill(signalcolor);
    shared_data->signal = false;
    shared_data->last_write_index = index;
    shared_data->last_write_time = shared_timestamps[index].tv_sec;
    image_buffer[index]->Assign(*capture_image);
    shared_timestamps[index] = zm::chrono::duration_cast<timeval>(packet->timestamp.time_since_epoch());
    delete capture_image;
    image_count++;
    // What about timestamping it?
    // Don't want to do analysis on it, but we won't due to signal
    return -1;
  } else if (captureResult > 0) {
    shared_data->signal = true;   // Assume if getting packets that we are getting something useful. CheckSignalPoints can correct this later.
    // If we captured, let's assume signal, Decode will detect further
    if (decoding == DECODING_NONE) {
      shared_data->last_write_index = index;
      shared_data->last_write_time = std::chrono::system_clock::to_time_t(packet->timestamp);
    }
    Debug(2, "Have packet stream_index:%d ?= videostream_id: %d q.vpktcount %d event? %d image_count %d",
        packet->packet->stream_index, video_stream_id, packetqueue.packet_count(video_stream_id), ( event ? 1 : 0 ), image_count);

    if (packet->codec_type == AVMEDIA_TYPE_VIDEO) {
      packet->packet->stream_index = video_stream_id; // Convert to packetQueue's index
      if (video_fifo) {
        if (packet->keyframe) {
          // avcodec strips out important nals that describe the stream and
          // stick them in extradata. Need to send them along with keyframes
          AVStream *stream = camera->getVideoStream();
          video_fifo->write(
              static_cast<unsigned char *>(stream->codecpar->extradata),
              stream->codecpar->extradata_size,
              packet->pts);
        }
        video_fifo->writePacket(*packet);
      }
    } else if (packet->codec_type == AVMEDIA_TYPE_AUDIO) {
      if (audio_fifo)
        audio_fifo->writePacket(*packet);

      // Only queue if we have some video packets in there. Should push this logic into packetqueue
      if (record_audio and (packetqueue.packet_count(video_stream_id) or event)) {
        packet->image_index=-1;
        Debug(2, "Queueing audio packet");
        packet->packet->stream_index = audio_stream_id; // Convert to packetQueue's index
        packetqueue.queuePacket(packet);
      } else {
        Debug(4, "Not Queueing audio packet");
      }
      return 1;
    } else {
      Debug(1, "Unknown codec type %d", packet->codec_type);
      return 1;
    } // end if audio

    image_count++;

    // Will only be queued if there are iterators allocated in the queue.
    packetqueue.queuePacket(packet);
  } else { // result == 0
    // Question is, do we update last_write_index etc?
    return 0;
  } // end if result

  // Icon: I'm not sure these should be here. They have nothing to do with capturing
  if ( shared_data->action & GET_SETTINGS ) {
    shared_data->brightness = camera->Brightness();
    shared_data->hue = camera->Hue();
    shared_data->colour = camera->Colour();
    shared_data->contrast = camera->Contrast();
    shared_data->action &= ~GET_SETTINGS;
  }
  if ( shared_data->action & SET_SETTINGS ) {
    camera->Brightness(shared_data->brightness);
    camera->Hue(shared_data->hue);
    camera->Colour(shared_data->colour);
    camera->Contrast(shared_data->contrast);
    shared_data->action &= ~SET_SETTINGS;
  }
  return captureResult;
} // end Monitor::Capture

bool Monitor::setupConvertContext(const AVFrame *input_frame, const Image *image) {
  AVPixelFormat imagePixFormat = image->AVPixFormat();
  AVPixelFormat inputPixFormat;
  bool changeColorspaceDetails = false;
  switch (input_frame->format) {
    case AV_PIX_FMT_YUVJ420P:
      inputPixFormat = AV_PIX_FMT_YUV420P;
      changeColorspaceDetails = true;
      break;
    case AV_PIX_FMT_YUVJ422P:
      inputPixFormat = AV_PIX_FMT_YUV422P;
      changeColorspaceDetails = true;
      break;
    case AV_PIX_FMT_YUVJ444P:
      inputPixFormat = AV_PIX_FMT_YUV444P;
      changeColorspaceDetails = true;
      break;
    case AV_PIX_FMT_YUVJ440P:
      inputPixFormat = AV_PIX_FMT_YUV440P;
      changeColorspaceDetails = true;
      break;
    default:
      inputPixFormat = (AVPixelFormat)input_frame->format;
  }

  convert_context = sws_getContext(
      input_frame->width,
      input_frame->height,
      inputPixFormat,
      image->Width(), image->Height(),
      imagePixFormat, SWS_BICUBIC,
      nullptr, nullptr, nullptr);
  if (convert_context == nullptr) {
    Error("Unable to create conversion context from %s to %s",
        av_get_pix_fmt_name(inputPixFormat),
        av_get_pix_fmt_name(imagePixFormat)
        );
  } else {
    Debug(1, "Setup conversion context for %dx%d %s to %dx%d %s",
        input_frame->width, input_frame->height,
        av_get_pix_fmt_name(inputPixFormat),
        image->Width(), image->Height(),
        av_get_pix_fmt_name(imagePixFormat)
        );
    if (changeColorspaceDetails) {
      // change the range of input data by first reading the current color space and then setting it's range as yuvj.
      int dummy[4];
      int srcRange, dstRange;
      int brightness, contrast, saturation;
      sws_getColorspaceDetails(convert_context, (int**)&dummy, &srcRange, (int**)&dummy, &dstRange, &brightness, &contrast, &saturation);
      const int* coefs = sws_getCoefficients(SWS_CS_DEFAULT);
      srcRange = 1; // this marks that values are according to yuvj
      sws_setColorspaceDetails(convert_context, coefs, srcRange, coefs, dstRange,
          brightness, contrast, saturation);
    }
  }
  return (convert_context != nullptr);
}

bool Monitor::Decode() {
  ZMLockedPacket *packet_lock = packetqueue.get_packet_and_increment_it(decoder_it);
  if (!packet_lock) return false;
  std::shared_ptr<ZMPacket> packet = packet_lock->packet_;
  if (packet->codec_type != AVMEDIA_TYPE_VIDEO) {
    Debug(4, "Not video");
    //packetqueue.unlock(packet_lock);
    delete packet_lock;
    return true; // Don't need decode
  }

  if ((!packet->image) and packet->packet->size and !packet->in_frame) {
    if ((decoding == DECODING_ALWAYS)
        or
        ((decoding == DECODING_ONDEMAND) and this->hasViewers() )
        or
        ((decoding == DECODING_KEYFRAMES) and packet->keyframe)
        or
        ((decoding == DECODING_KEYFRAMESONDEMAND) and (this->hasViewers() or packet->keyframe))
       ) {

      // Allocate the image first so that it can be used by hwaccel
      // We don't actually care about camera colours, pixel order etc.  We care about the desired settings
      //
      //capture_image = packet->image = new Image(width, height, camera->Colours(), camera->SubpixelOrder());
      int ret = packet->decode(camera->getVideoCodecContext());
      if (ret > 0 and !zm_terminate) {
        if (packet->in_frame and !packet->image) {
          packet->image = new Image(camera_width, camera_height, camera->Colours(), camera->SubpixelOrder());

          if (convert_context || this->setupConvertContext(packet->in_frame.get(), packet->image)) {
            if (!packet->image->Assign(packet->in_frame.get(), convert_context, dest_frame.get())) {
              delete packet->image;
              packet->image = nullptr;
            }
            av_frame_unref(dest_frame.get());
          } else {
            delete packet->image;
            packet->image = nullptr;
          }  // end if have convert_context
        }  // end if need transfer to image
      } else {
        Debug(1, "Ret from decode %d, zm_terminate %d", ret, zm_terminate);
      }
    } else {
      Debug(1, "Not Decoding ? %s", Decoding_Strings[decoding].c_str());
    } // end if doing decoding
  } else {
    Debug(1, "No packet.size(%d) or packet->in_frame(%p). Not decoding", packet->packet->size, packet->in_frame.get());
  }  // end if need_decoding

  if ((analysis_image == ANALYSISIMAGE_YCHANNEL) && packet->in_frame && (
        ((AVPixelFormat)packet->in_frame->format == AV_PIX_FMT_YUV420P)
        ||
        ((AVPixelFormat)packet->in_frame->format == AV_PIX_FMT_YUVJ420P)
        ) ) {
    packet->y_image = new Image(packet->in_frame->width, packet->in_frame->height, 1, ZM_SUBPIX_ORDER_NONE, packet->in_frame->data[0], 0);
    if (orientation != ROTATE_0) {
      switch (orientation) {
        case ROTATE_0 :
          // No action required
          break;
        case ROTATE_90 :
        case ROTATE_180 :
        case ROTATE_270 :
          packet->y_image->Rotate((orientation-1)*90);
          break;
        case FLIP_HORI :
        case FLIP_VERT :
          packet->y_image->Flip(orientation==FLIP_HORI);
          break;
      }
    } // end if have rotation
  }

  if (packet->image) {
    Image* capture_image = packet->image;

    /* Deinterlacing */
    if (deinterlacing_value) {
      Debug(1, "Doing deinterlacing");
      if (deinterlacing_value == 1) {
        capture_image->Deinterlace_Discard();
      } else if (deinterlacing_value == 2) {
        capture_image->Deinterlace_Linear();
      } else if (deinterlacing_value == 3) {
        capture_image->Deinterlace_Blend();
      } else if (deinterlacing_value == 4) {
        while (!zm_terminate) {
          // ICON FIXME SHould we not clone decoder_it?
          ZMLockedPacket *deinterlace_packet_lock = packetqueue.get_packet(decoder_it);
          if (!deinterlace_packet_lock) {
            delete packet_lock;
            return false;
          }
          if (deinterlace_packet_lock->packet_->codec_type == packet->codec_type) {
            if (!deinterlace_packet_lock->packet_->image) {
              Error("Can't de-interlace when we have to decode.  De-Interlacing should only be useful on old low res local cams");
            } else {
              capture_image->Deinterlace_4Field(deinterlace_packet_lock->packet_->image, (deinterlacing>>8)&0xff);
            }
            delete deinterlace_packet_lock;
            //packetqueue.unlock(deinterlace_packet_lock);
            break;
          }
          delete deinterlace_packet_lock;
          //packetqueue.unlock(deinterlace_packet_lock);
          packetqueue.increment_it(decoder_it);
        }
        if (zm_terminate) {
          delete packet_lock;
          return false;
        }
      } else if (deinterlacing_value == 5) {
        capture_image->Deinterlace_Blend_CustomRatio((deinterlacing>>8)&0xff);
      }
    } // end if deinterlacing_value

    if (orientation != ROTATE_0) {
      Debug(3, "Doing rotation");
      switch (orientation) {
        case ROTATE_0 :
          // No action required
          break;
        case ROTATE_90 :
        case ROTATE_180 :
        case ROTATE_270 : 
          capture_image->Rotate((orientation-1)*90);
          break;
        case FLIP_HORI :
        case FLIP_VERT :
          capture_image->Flip(orientation==FLIP_HORI);
          break;
      }
    } // end if have rotation

    if (privacy_bitmask) {
      capture_image->MaskPrivacy(privacy_bitmask);
    }

    if (config.timestamp_on_capture) {
      TimestampImage(capture_image, packet->timestamp);
    }

    unsigned int index = shared_data->last_write_index;
    index++;
    index = index % image_buffer_count;
    image_buffer[index]->Assign(*(packet->image));
    shared_timestamps[index] = zm::chrono::duration_cast<timeval>(packet->timestamp.time_since_epoch());
    shared_data->signal = (capture_image and signal_check_points) ? CheckSignal(capture_image) : true;
    shared_data->last_write_index = index;
    shared_data->last_write_time = std::chrono::system_clock::to_time_t(std::chrono::system_clock::now());
    if (std::chrono::system_clock::now() - packet->timestamp > Seconds(ZM_WATCH_MAX_DELAY)) {
      Warning("Decoding is not keeping up. We are %.2f seconds behind capture.",
          FPSeconds(std::chrono::system_clock::now() - packet->timestamp).count());
    }
  }  // end if have image
  packet->decoded = true;
  packetqueue.unlock(packet_lock);
  return true;
}  // end bool Monitor::Decode()


std::string Monitor::Substitute(const std::string &format, SystemTimePoint ts_time) const {
  if (format.empty()) return "";

  std::string text;
  text.reserve(1024);

  // Expand the strftime macros first
  char label_time_text[256];
  tm ts_tm = {};
  time_t ts_time_t = std::chrono::system_clock::to_time_t(ts_time);
  strftime(label_time_text, sizeof(label_time_text), format.c_str(), localtime_r(&ts_time_t, &ts_tm));

  const char *s_ptr = label_time_text;
  char *d_ptr = text.data();

  while (*s_ptr && ((unsigned int)(d_ptr - text.data()) < text.capacity())) {
    if (*s_ptr == config.timestamp_code_char[0]) {
      const auto max_len = text.capacity() - (d_ptr - text.data());
      bool found_macro = false;
      int rc = 0;
      switch ( *(s_ptr+1) ) {
        case 'N' :
          rc = snprintf(d_ptr, max_len, "%s", name.c_str());
          found_macro = true;
          break;
        case 'Q' :
          rc = snprintf(d_ptr, max_len, "%s", trigger_data->trigger_showtext);
          found_macro = true;
          break;
        case 'f' :
          typedef std::chrono::duration<int64, std::centi> Centiseconds;
          Centiseconds centi_sec = std::chrono::duration_cast<Centiseconds>(
              ts_time.time_since_epoch() - std::chrono::duration_cast<Seconds>(ts_time.time_since_epoch()));
          rc = snprintf(d_ptr, max_len, "%02lld", static_cast<long long int>(centi_sec.count()));
          found_macro = true;
          break;
      }
      if (rc < 0 || static_cast<size_t>(rc) > max_len) {
        break;
      }
      d_ptr += rc;
      if (found_macro) {
        s_ptr += 2;
        continue;
      }
    }
    *d_ptr++ = *s_ptr++;
  } // end while
  *d_ptr = '\0';
  return text;
}

void Monitor::TimestampImage(Image *ts_image, SystemTimePoint ts_time) const {
  if (!label_format[0])
    return;
  const std::string label_text = Substitute(label_format, ts_time);

  ts_image->Annotate(label_text.c_str(), label_coord, label_size);
  Debug(2, "done annotating %s", label_text.c_str());
} // end void Monitor::TimestampImage

Event * Monitor::openEvent(
    const std::shared_ptr<ZMPacket> &snap,
    const std::string &cause,
    const Event::StringSetMap &noteSetMap) {

  // FIXME this iterator is not protected from invalidation
  packetqueue_iterator *start_it = packetqueue.get_event_start_packet_it(
      *analysis_it,
      (cause == "Continuous" ? 0 : (pre_event_count > alarm_frame_count ? pre_event_count : alarm_frame_count))
      );

  // This gets a lock on the starting packet

  std::shared_ptr<ZMPacket> starting_packet;
  ZMLockedPacket *starting_packet_lock = nullptr;
  if (*start_it != *analysis_it) {
    starting_packet_lock = packetqueue.get_packet(start_it);
    
    if (!starting_packet_lock) {
      Warning("Unable to get starting packet lock");
      return nullptr;
    }
    starting_packet = starting_packet_lock->packet_;
    ZM_DUMP_PACKET(starting_packet->packet, "First packet from start");
  } else {
    starting_packet = snap;
    ZM_DUMP_PACKET(starting_packet->packet, "First packet from alarm");
  }

  event = new Event(this, starting_packet->timestamp, cause, noteSetMap);

  shared_data->last_event_id = event->Id();
  SetVideoWriterStartTime(starting_packet->timestamp);
  strncpy(shared_data->alarm_cause, cause.c_str(), sizeof(shared_data->alarm_cause)-1);

#if MOSQUITTOPP_FOUND
  if (mqtt) mqtt->send(stringtf("event start: %" PRId64, event->Id()));
#endif

  if (!event_start_command.empty()) {
    if (fork() == 0) {
      execlp(event_start_command.c_str(),
		      event_start_command.c_str(),
		      std::to_string(event->Id()).c_str(),
		      std::to_string(event->MonitorId()).c_str(),
		      nullptr);
      Error("Error execing %s", event_start_command.c_str());
    }
  }

  // Write out starting packets, do not modify packetqueue it will garbage collect itself
  while (starting_packet_lock && (*start_it != *analysis_it) && !zm_terminate) {
    ZM_DUMP_PACKET(starting_packet_lock->packet_->packet, "Queuing packet for event");
    event->AddPacket(starting_packet_lock);

    packetqueue.increment_it(start_it);
    if ((*start_it) != *analysis_it) {
      starting_packet_lock = packetqueue.get_packet(start_it);
    }
  }
  packetqueue.free_it(start_it);
  delete start_it;
  start_it = nullptr;

  return event;
}

/* Caller must hold the event lock */
void Monitor::closeEvent() {
  if (!event) return;

  if (close_event_thread.joinable()) {
    Debug(1, "close event thread is joinable");
    close_event_thread.join();
  } else {
    Debug(1, "close event thread is not joinable");
  }
#if MOSQUITTOPP_FOUND
  if (mqtt) mqtt->send(stringtf("event end: %" PRId64, event->Id()));
#endif
  Debug(1, "Starting thread to close event");
  close_event_thread = std::thread([](Event *e, const std::string &command){
        int64_t event_id = e->Id();
        int monitor_id = e->MonitorId();
        delete e;


        if (!command.empty()) {
          if (fork() == 0) {
            execlp(command.c_str(), command.c_str(),
			    std::to_string(event_id).c_str(),
			    std::to_string(monitor_id).c_str(), // monitor id
			    nullptr);
            Error("Error execing %s", command.c_str());
          }
        }
      }, event, event_end_command);
  Debug(1, "Nulling event");
  event = nullptr;
  if (shared_data) video_store_data->recording = {};
} // end bool Monitor::closeEvent()

unsigned int Monitor::DetectMotion(const Image &comp_image, Event::StringSet &zoneSet) {
  bool alarm = false;
  unsigned int score = 0;

  if (zones.empty()) {
    Warning("No zones to check!");
    return alarm;
  }

  ref_image.Delta(comp_image, &delta_image);

  if (config.record_diag_images) {
    ref_image.WriteJpeg(diag_path_ref, config.record_diag_images_fifo);
    delta_image.WriteJpeg(diag_path_delta, config.record_diag_images_fifo);
  }

  // Blank out all exclusion zones
  for (Zone &zone : zones) {
    // need previous alarmed state for preclusive zone, so don't clear just yet
    if (!zone.IsPreclusive())
      zone.ClearAlarm();
    if (!zone.IsInactive())
      continue;
    Debug(3, "Blanking inactive zone %s", zone.Label());
    delta_image.Fill(kRGBBlack, zone.GetPolygon());
  } // end foreach zone

  // Check preclusive zones first
  for (Zone &zone : zones) {
    if (!zone.IsPreclusive())
      continue;
    int old_zone_score = zone.Score();
    bool old_zone_alarmed = zone.Alarmed();
    Debug(3, "Checking preclusive zone %s - old score: %d, state: %s",
        zone.Label(),old_zone_score, zone.Alarmed()?"alarmed":"quiet");
    if (zone.CheckAlarms(&delta_image)) {
      alarm = true;
      score += zone.Score();
      zone.SetAlarm();
      Debug(3, "Zone is alarmed, zone score = %d", zone.Score());
      zoneSet.insert(zone.Label());
    } else {
      // check if end of alarm
      if (old_zone_alarmed) {
        Debug(3, "Preclusive Zone %s alarm Ends. Previous score: %d",
            zone.Label(), old_zone_score);
        if (old_zone_score > 0) {
          zone.SetExtendAlarmCount(zone.GetExtendAlarmFrames());
        }
        if (zone.CheckExtendAlarmCount()) {
          alarm = true;
          zone.SetAlarm();
        } else {
          zone.ClearAlarm();
        }
      }  // end if zone WAS alarmed
    } // end if CheckAlarms
  } // end foreach zone

  Vector2 alarm_centre;
  int top_score = -1;

  if (alarm) {
    alarm = false;
    score = 0;
  } else {
    // Find all alarm pixels in active zones
    for (Zone &zone : zones) {
      if (!zone.IsActive() || zone.IsPreclusive()) {
        continue;
      }
      Debug(3, "Checking active zone %s", zone.Label());
      if (zone.CheckAlarms(&delta_image)) {
        alarm = true;
        score += zone.Score();
        zone.SetAlarm();
        Debug(3, "Zone is alarmed, zone score = %d", zone.Score());
        zoneSet.insert(zone.Label());
        if (config.opt_control && track_motion) {
          if ((int)zone.Score() > top_score) {
            top_score = zone.Score();
            alarm_centre = zone.GetAlarmCentre();
          }
        }
      }
    } // end foreach zone

    if (alarm) {
      for (Zone &zone : zones) {
        if (!zone.IsInclusive()) {
          continue;
        }
        Debug(3, "Checking inclusive zone %s", zone.Label());
        if (zone.CheckAlarms(&delta_image)) {
          score += zone.Score();
          zone.SetAlarm();
          Debug(3, "Zone is alarmed, zone score = %d", zone.Score());
          zoneSet.insert(zone.Label());
          if (config.opt_control && track_motion) {
            if (zone.Score() > (unsigned int)top_score) {
              top_score = zone.Score();
              alarm_centre = zone.GetAlarmCentre();
            }
          }
        } // end if CheckAlarm
      } // end foreach zone
    } else {
      // Find all alarm pixels in exclusive zones
      for (Zone &zone : zones) {
        if (!zone.IsExclusive()) {
          continue;
        }
        Debug(3, "Checking exclusive zone %s", zone.Label());
        if (zone.CheckAlarms(&delta_image)) {
          alarm = true;
          score += zone.Score();
          zone.SetAlarm();
          Debug(3, "Zone is alarmed, zone score = %d", zone.Score());
          zoneSet.insert(zone.Label());
        }
      } // end foreach zone
    } // end if alarm or not
  } // end if alarm

  if (top_score > 0) {
    shared_data->alarm_x = alarm_centre.x_;
    shared_data->alarm_y = alarm_centre.y_;

    Info("Got alarm centre at %d,%d, at count %d",
        shared_data->alarm_x, shared_data->alarm_y, analysis_image_count);
  } else {
    shared_data->alarm_x = shared_data->alarm_y = -1;
  }

  // This is a small and innocent hack to prevent scores of 0 being returned in alarm state
  return score ? score : alarm;
} // end DetectMotion

// TODO: Move the camera specific things to the camera classes and avoid these casts.
bool Monitor::DumpSettings(char *output, bool verbose) {
  output[0] = 0;

  sprintf( output+strlen(output), "Id : %u\n", id );
  sprintf( output+strlen(output), "Name : %s\n", name.c_str() );
  sprintf( output+strlen(output), "Type : %s\n", camera->IsLocal()?"Local":(camera->IsRemote()?"Remote":"File") );
#if ZM_HAS_V4L2
  if ( camera->IsLocal() ) {
    LocalCamera* cam = static_cast<LocalCamera*>(camera.get());
    sprintf( output+strlen(output), "Device : %s\n", cam->Device().c_str() );
    sprintf( output+strlen(output), "Channel : %d\n", cam->Channel() );
    sprintf( output+strlen(output), "Standard : %d\n", cam->Standard() );
  } else
#endif // ZM_HAS_V4L2
  if ( camera->IsRemote() ) {
    RemoteCamera* cam = static_cast<RemoteCamera*>(camera.get());
    sprintf( output+strlen(output), "Protocol : %s\n", cam->Protocol().c_str() );
    sprintf( output+strlen(output), "Host : %s\n", cam->Host().c_str() );
    sprintf( output+strlen(output), "Port : %s\n", cam->Port().c_str() );
    sprintf( output+strlen(output), "Path : %s\n", cam->Path().c_str() );
  } else if ( camera->IsFile() ) {
    FileCamera* cam = static_cast<FileCamera*>(camera.get());
    sprintf( output+strlen(output), "Path : %s\n", cam->Path().c_str() );
  }
  else if ( camera->IsFfmpeg() ) {
    FfmpegCamera* cam = static_cast<FfmpegCamera*>(camera.get());
    sprintf( output+strlen(output), "Path : %s\n", cam->Path().c_str() );
  }
  sprintf( output+strlen(output), "Width : %u\n", camera->Width() );
  sprintf( output+strlen(output), "Height : %u\n", camera->Height() );
#if ZM_HAS_V4L2
  if ( camera->IsLocal() ) {
    LocalCamera* cam = static_cast<LocalCamera*>(camera.get());
    sprintf( output+strlen(output), "Palette : %d\n", cam->Palette() );
  }
#endif // ZM_HAS_V4L2
  sprintf(output+strlen(output), "Colours : %u\n", camera->Colours() );
  sprintf(output+strlen(output), "Subpixel Order : %u\n", camera->SubpixelOrder() );
  sprintf(output+strlen(output), "Event Prefix : %s\n", event_prefix.c_str() );
  sprintf(output+strlen(output), "Label Format : %s\n", label_format.c_str() );
  sprintf(output+strlen(output), "Label Coord : %d,%d\n", label_coord.x_, label_coord.y_ );
  sprintf(output+strlen(output), "Label Size : %d\n", label_size );
  sprintf(output+strlen(output), "Image Buffer Count : %d\n", image_buffer_count );
  sprintf(output+strlen(output), "Warmup Count : %d\n", warmup_count );
  sprintf(output+strlen(output), "Pre Event Count : %d\n", pre_event_count );
  sprintf(output+strlen(output), "Post Event Count : %d\n", post_event_count );
  sprintf(output+strlen(output), "Stream Replay Buffer : %d\n", stream_replay_buffer );
  sprintf(output+strlen(output), "Alarm Frame Count : %d\n", alarm_frame_count );
  sprintf(output+strlen(output), "Section Length : %" PRIi64 "\n", static_cast<int64>(Seconds(section_length).count()));
  sprintf(output+strlen(output), "Min Section Length : %" PRIi64 "\n", static_cast<int64>(Seconds(min_section_length).count()));
  sprintf(output+strlen(output), "Maximum FPS : %.2f\n", capture_delay != Seconds(0) ? 1 / FPSeconds(capture_delay).count() : 0.0);
  sprintf(output+strlen(output), "Alarm Maximum FPS : %.2f\n", alarm_capture_delay != Seconds(0) ? 1 / FPSeconds(alarm_capture_delay).count() : 0.0);
  sprintf(output+strlen(output), "Reference Blend %%ge : %d\n", ref_blend_perc);
  sprintf(output+strlen(output), "Alarm Reference Blend %%ge : %d\n", alarm_ref_blend_perc);
  sprintf(output+strlen(output), "Track Motion : %d\n", track_motion);
  sprintf(output+strlen(output), "Capturing %d - %s\n", capturing, Capturing_Strings[capturing].c_str());
  sprintf(output+strlen(output), "Analysing %d - %s\n", analysing, Analysing_Strings[analysing].c_str());
  sprintf(output+strlen(output), "Recording %d - %s\n", recording, Recording_Strings[recording].c_str());
  sprintf(output+strlen(output), "Zones : %zu\n", zones.size());
  for (const Zone &zone : zones) {
    zone.DumpSettings(output+strlen(output), verbose);
  }
  sprintf(output+strlen(output), "Capturing Enabled? %s\n",
      (shared_data->capturing != CAPTURING_NONE) ? "enabled" : "disabled");
  sprintf(output+strlen(output), "Motion Detection Enabled? %s\n",
      (shared_data->analysing != ANALYSING_NONE) ? "enabled" : "disabled");
  sprintf(output+strlen(output), "Recording Enabled? %s\n",
      (shared_data->recording != RECORDING_NONE) ? "enabled" : "disabled");
  sprintf(output+strlen(output), "Events Enabled (!TRIGGER_OFF)? %s\n",
      (trigger_data->trigger_state == TRIGGER_OFF) ? "disabled" : "enabled");
  return true;
} // bool Monitor::DumpSettings(char *output, bool verbose)

unsigned int Monitor::Colours() const { return camera ? camera->Colours() : colours; }
unsigned int Monitor::SubpixelOrder() const { return camera ? camera->SubpixelOrder() : 0; }

int Monitor::PrimeCapture() {
  int ret = camera->PrimeCapture();
  if (ret <= 0) return ret;

  if ( -1 != camera->getVideoStreamId() ) {
    video_stream_id = packetqueue.addStream();
  }

  if ( -1 != camera->getAudioStreamId() ) {
    audio_stream_id = packetqueue.addStream();
    packetqueue.addStream();
    shared_data->audio_frequency = camera->getFrequency();
    shared_data->audio_channels = camera->getChannels();
  }

  Debug(2, "Video stream id is %d, audio is %d, minimum_packets to keep in buffer %d",
      video_stream_id, audio_stream_id, pre_event_count);

  if (rtsp_server) {
    if (video_stream_id >= 0) {
      AVStream *videoStream = camera->getVideoStream();
      snprintf(shared_data->video_fifo_path, sizeof(shared_data->video_fifo_path) - 1, "%s/video_fifo_%u.%s",
               staticConfig.PATH_SOCKS.c_str(),
               id,
               avcodec_get_name(videoStream->codecpar->codec_id)
      );
      video_fifo = new Fifo(shared_data->video_fifo_path, true);
    }
    if (record_audio and (audio_stream_id >= 0)) {
      AVStream *audioStream = camera->getAudioStream();
      if (audioStream && CODEC(audioStream)) {
        snprintf(shared_data->audio_fifo_path, sizeof(shared_data->audio_fifo_path) - 1, "%s/audio_fifo_%u.%s",
                 staticConfig.PATH_SOCKS.c_str(), id,
                 avcodec_get_name(audioStream->codecpar->codec_id)
        );
      audio_fifo = new Fifo(shared_data->audio_fifo_path, true);
      } else {
        Warning("No audioStream %p or codec?", audioStream);
      }
    }
  }  // end if rtsp_server

  //Poller Thread
  if (onvif_event_listener || janus_enabled || use_Amcrest_API) {
    if (!Poller) {
      Poller = zm::make_unique<PollThread>(this);
    } else {
      Poller->Start();
    }
  }

  if (decoding != DECODING_NONE) {
    if (!dest_frame) dest_frame = av_frame_ptr{zm_av_frame_alloc()};
    if (!decoder_it) decoder_it = packetqueue.get_video_it(false);
    if (!decoder) {
      Debug(1, "Creating decoder thread");
      decoder = zm::make_unique<DecoderThread>(this);
    } else {
      Debug(1, "Restarting decoder thread");
      decoder->Start();
    }
  }
  Debug(1, "Done restarting decoder");
  if (!analysis_it) {
    Debug(1, "getting analysis_it");
    analysis_it = packetqueue.get_video_it(false);
  } else {
    Debug(1, "haveing analysis_it");
  }
  if (!analysis_thread) {
    Debug(1, "Starting an analysis thread for monitor (%d)", id);
    analysis_thread = zm::make_unique<AnalysisThread>(this);
  } else {
    Debug(1, "Restarting analysis thread for monitor (%d)", id);
    analysis_thread->Start();
  }
  return ret;
}  // end int Monitor::PrimeCapture()

int Monitor::PreCapture() const { return camera->PreCapture(); }
int Monitor::PostCapture() const { return camera->PostCapture(); }
int Monitor::Close() {
  Debug(1, "Stopping packetqueue");
  // Wake everyone up
  packetqueue.stop();
  Debug(1, "Stopped packetqueue");

  // Because the stream indexes may change we have to clear out the packetqueue
  if (decoder) {
    Debug(1, "Decoder stopping");
    decoder->Stop();
    Debug(1, "Decoder stopped");
  }
  if (analysis_thread) {
    analysis_thread->Stop();
    Debug(1, "Analysis stopped");
  }

  //ONVIF Teardown
  if (Poller) {
    Poller->Stop();
    Debug(1, "Poller stopped");
  }
#ifdef WITH_GSOAP
  if (onvif_event_listener && (soap != nullptr)) {
    Debug(1, "Tearing Down Onvif");
    _wsnt__Unsubscribe wsnt__Unsubscribe;
    _wsnt__UnsubscribeResponse wsnt__UnsubscribeResponse;
    proxyEvent.Unsubscribe(response.SubscriptionReference.Address, NULL, &wsnt__Unsubscribe, wsnt__UnsubscribeResponse);
    soap_destroy(soap);
    soap_end(soap);
    soap_free(soap);
    soap = nullptr;
  }  //End ONVIF
#endif
   //Janus Teardown
  if (janus_enabled and (purpose == CAPTURE) and Janus_Manager) {
    delete Janus_Manager;
    Janus_Manager = nullptr;
  }

  if (audio_fifo) {
    delete audio_fifo;
    audio_fifo = nullptr;
    Debug(1, "audio fifo deleted");
  }
  if (video_fifo) {
    delete video_fifo;
    Debug(1, "video fifo deleted");
    video_fifo = nullptr;
  }


  if (close_event_thread.joinable()) {
    Debug(1, "Joining event thread");
    close_event_thread.join();
    Debug(1, "Joined event thread");
  }
  {
    std::lock_guard<std::mutex> lck(event_mutex);
    if (event) {
      Info("%s: image_count:%d - Closing event %" PRIu64 ", shutting down", name.c_str(), image_count, event->Id());
      closeEvent();
      close_event_thread.join();
    }
  }
  packetqueue.clear();
  if (camera) camera->Close();
  return 1;
}

Monitor::Orientation Monitor::getOrientation() const { return orientation; }

// Wait for camera to get an image, and then assign it as the base reference image.
// So this should be done as the first task in the analysis thread startup.
// This function is deprecated.
void Monitor::get_ref_image() {
  ZMLockedPacket *snap_lock = nullptr;
  Warning("get_ref_image is deprecated");

  if ( !analysis_it ) 
    analysis_it = packetqueue.get_video_it(true);

  while ( 
      (
       !( snap_lock = packetqueue.get_packet(analysis_it))
       or 
       ( snap_lock->packet_->codec_type != AVMEDIA_TYPE_VIDEO )
       or
       ! snap_lock->packet_->image
      )
    and !zm_terminate) {

    Debug(1, "Waiting for capture daemon lastwriteindex(%d) lastwritetime(%" PRIi64 ")",
          shared_data->last_write_index, static_cast<int64>(shared_data->last_write_time));
    if (snap_lock and ! snap_lock->packet_->image) {
      delete snap_lock;
      // can't analyse it anyways, incremement
      packetqueue.increment_it(analysis_it);
    }
  }
  if (zm_terminate)
    return;

  std::shared_ptr<ZMPacket> snap = snap_lock->packet_;
  Debug(1, "get_ref_image: packet.stream %d ?= video_stream %d, packet image id %d packet image %p",
      snap->packet->stream_index, video_stream_id, snap->image_index, snap->image );
  // Might not have been decoded yet FIXME
  if (snap->image) {
    ref_image.Assign(width, height, camera->Colours(),
        camera->SubpixelOrder(), snap->image->Buffer(), camera->ImageSize());
    Debug(2, "Have ref image about to unlock");
  } else {
    Debug(2, "Have no ref image about to unlock");
  }
  delete snap_lock;
}  // get_ref_image

std::vector<Group *> Monitor::Groups() {
  // At the moment, only load groups once.
  if (!groups.size()) {
    zmDbQuery query = zmDbQuery( SELECT_GROUPS_PARENT_OF_MONITOR_ID );
    query.bind( "id", id );

    query.run( true );

    int n_groups = query.affectedRows();
    if ( n_groups == 0 ) {
      Error("Can't load groups");
      return groups;
    }
    Debug(1, "Got %d groups", n_groups);
    groups.reserve(n_groups);
    while ( query.next() ) {
      groups.push_back(new Group( query ));
    }
  }
  return groups;
} // end Monitor::Groups()

StringVector Monitor::GroupNames() {
  StringVector groupnames;
  for ( Group * g: Groups() ) {
    groupnames.push_back(std::string(g->Name()));
    Debug(1, "Groups: %s", g->Name());
  }
  return groupnames;
} // end Monitor::GroupNames()

#ifdef WITH_GSOAP
//ONVIF Set Credentials
void Monitor::set_credentials(struct soap *soap)
{
  soap_wsse_delete_Security(soap);
  soap_wsse_add_Timestamp(soap, NULL, 10);
  soap_wsse_add_UsernameTokenDigest(soap, "Auth", onvif_username.c_str(), onvif_password.c_str());
}

//GSOAP boilerplate
int SOAP_ENV__Fault(struct soap *soap, char *faultcode, char *faultstring, char *faultactor, struct SOAP_ENV__Detail *detail, struct SOAP_ENV__Code *SOAP_ENV__Code, struct SOAP_ENV__Reason *SOAP_ENV__Reason, char *SOAP_ENV__Node, char *SOAP_ENV__Role, struct SOAP_ENV__Detail *SOAP_ENV__Detail)
{
  // populate the fault struct from the operation arguments to print it
  soap_fault(soap);
  // SOAP 1.1
  soap->fault->faultcode = faultcode;
  soap->fault->faultstring = faultstring;
  soap->fault->faultactor = faultactor;
  soap->fault->detail = detail;
  // SOAP 1.2
  soap->fault->SOAP_ENV__Code = SOAP_ENV__Code;
  soap->fault->SOAP_ENV__Reason = SOAP_ENV__Reason;
  soap->fault->SOAP_ENV__Node = SOAP_ENV__Node;
  soap->fault->SOAP_ENV__Role = SOAP_ENV__Role;
  soap->fault->SOAP_ENV__Detail = SOAP_ENV__Detail;
  // set error
  soap->error = SOAP_FAULT;
  // handle or display the fault here with soap_stream_fault(soap, std::cerr);
  // return HTTP 202 Accepted
  return soap_send_empty_response(soap, SOAP_OK);
}
#endif
