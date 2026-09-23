//
// ZoneMinder RTSP Daemon
// Copyright (C) 2021 Isaac Connor
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

/*

=head1 NAME

zm_rtsp_server - The ZoneMinder Server

=head1 SYNOPSIS

 zmc -m <monitor_id>
 zmc --monitor <monitor_id>
 zmc -h
 zmc --help
 zmc -v
 zmc --version

=head1 DESCRIPTION

This binary's job is to connect to the media stream sockets provided by local
zmc processes and provide those streams over rtsp

=head1 OPTIONS

 -m, --monitor_id           - ID of a monitor to stream
 -h, --help                 - Display usage information
 -v, --version              - Print the installed version of ZoneMinder

=cut

*/

#include "zm.h"
#include "zm_db.h"
#include "zm_define.h"
#include "zm_monitor.h"
#include "zm_rtsp_server_authenticator.h"
#include "zm_rtsp_server_session_tracker.h"
#include "zm_rtsp_server_stream_h264_source.h"
#include "zm_rtsp_server_stream_av1_source.h"
#include "zm_rtsp_server_stream_adts_source.h"
#include "zm_signal.h"
#include "zm_stream_socket_client.h"
#include "zm_stream_socket_protocol.h"
#include "zm_time.h"
#include "zm_utils.h"

#include "xop/G711USource.h"
#include "xop/RtspServer.h"

#include <condition_variable>
#include <getopt.h>
#include <iostream>
#include <mutex>
#include <unordered_map>
#include <vector>

using zm::stream_socket::HelloInfo;
using zm::stream_socket::Header;
using zm::stream_socket::StreamId;

namespace {

// Wakes the main loop when a HELLO requires (re)building a session
std::mutex rebuild_mutex;
std::condition_variable rebuild_cv;
bool rebuild_pending = false;  // guarded by rebuild_mutex

// One monitor's path from its stream socket to an xop RTSP session.
//
// The StreamSocketClient reader thread records HELLOs and forwards media to
// the packing sources; the main thread (re)builds the xop session from the
// recorded HELLOs - on first connect and whenever the parameters change
// (camera reconfigure, zmc restart with a different codec).
class MonitorRtspStream {
 public:
  MonitorRtspStream(std::shared_ptr<xop::RtspServer> rtsp_server,
                    std::shared_ptr<Monitor> monitor) :
    rtsp_server_(std::move(rtsp_server)),
    monitor_(std::move(monitor)),
    stream_name_(monitor_->GetRTSPStreamName()) {
    StreamSocketClient::Callbacks callbacks;
    callbacks.on_hello = [this](StreamId stream, const HelloInfo &info, uint32_t generation) {
      {
        std::lock_guard<std::mutex> lock(mutex_);
        tracker_.OnHello(stream, info, generation);
      }
      // Flag under the wake mutex so a HELLO arriving between the main
      // loop's Update() pass and its wait cannot be lost until the timeout
      {
        std::lock_guard<std::mutex> wake_lock(rebuild_mutex);
        rebuild_pending = true;
      }
      rebuild_cv.notify_all();
    };
    callbacks.on_media = [this](const Header &header, const uint8_t *data, size_t size) {
      std::lock_guard<std::mutex> lock(mutex_);
      // Feed the packers only once the main thread has reconciled the session
      // with the latest HELLO set (see RtspSessionTracker). After a parameter
      // change, or a producer restart that reuses a generation number, the
      // reader thread sees the new HELLO before the session is rebuilt; media
      // sent to the old packer in that window is codec-mismatch garbage.
      if (!session_ or !tracker_.Accepts(header.generation))
        return;
      if (header.stream == static_cast<uint8_t>(StreamId::Video)) {
        if (video_source_)
          video_source_->OnPacket(data, size, header.pts_us);
      } else {
        if (audio_source_)
          audio_source_->OnPacket(data, size, header.pts_us);
      }
    };
    // The built session is kept across a disconnect; if the next producer
    // announces identical parameters it is adopted, clients and all.
    callbacks.on_disconnect = [this]() {
      std::lock_guard<std::mutex> lock(mutex_);
      tracker_.OnDisconnect();
    };
    std::string path = stringtf("%s/stream_%u.sock", staticConfig.PATH_SOCKS.c_str(), monitor_->Id());
    Info("Monitor %u: consuming stream socket %s", monitor_->Id(), path.c_str());
    client_ = zm::make_unique<StreamSocketClient>(std::move(path), std::move(callbacks));
  }

  ~MonitorRtspStream() {
    client_.reset();  // stop callbacks before tearing the session down
    std::lock_guard<std::mutex> lock(mutex_);
    TeardownLocked();
  }

  bool HasClients() {
    std::lock_guard<std::mutex> lock(mutex_);
    return session_ and session_->GetNumClient() > 0;
  }

  // Called from the main loop: bring the xop session in line with the latest
  // complete HELLO set - keep it when the parameters are unchanged (a zmc
  // restart, where generations start again at 0), rebuild it otherwise.
  void Update() {
    std::lock_guard<std::mutex> lock(mutex_);
    RtspSessionTracker::Action action = tracker_.Plan(session_ != nullptr);
    if (action == RtspSessionTracker::Action::Rebuild) {
      TeardownLocked();
      BuildLocked(tracker_.HavePendingAudio());
    }
    tracker_.Confirm(action, session_ != nullptr);
  }

 private:
  // mutex_ must be held
  void TeardownLocked() {
    // Stop and join each packer's write thread while the object is still the
    // most-derived type: it calls the virtual PushFrame, which must not run
    // once destruction has devolved the vtable to the pure base.
    if (video_source_) {
      video_source_->StopAndJoin();
      delete video_source_;
      video_source_ = nullptr;
    }
    if (audio_source_) {
      audio_source_->StopAndJoin();
      delete audio_source_;
      audio_source_ = nullptr;
    }
    if (session_) {
      rtsp_server_->RemoveSession(session_->GetMediaSessionId());
      session_ = nullptr;
    }
    tracker_.SessionGone();
  }

  // mutex_ must be held; the tracker has a complete HELLO set. build_audio says whether
  // the pending audio HELLO is current (see Update) and should be served.
  void BuildLocked(bool build_audio) {
    xop::MediaSession *session = xop::MediaSession::CreateNew(stream_name_);
    if (!session) {
      Error("Unable to create session for %s", stream_name_.c_str());
      return;
    }
    session->AddNotifyConnectedCallback(
        [](xop::MediaSessionId, const std::string &peer_ip, uint16_t peer_port) {
          Debug(1, "RTSP client connect, ip=%s, port=%hu", peer_ip.c_str(), peer_port);
        });
    session->AddNotifyDisconnectedCallback(
        [](xop::MediaSessionId, const std::string &peer_ip, uint16_t peer_port) {
          Debug(1, "RTSP client disconnect, ip=%s, port=%hu", peer_ip.c_str(), peer_port);
        });
    rtsp_server_->AddSession(session);
    xop::MediaSessionId session_id = session->GetMediaSessionId();

    const HelloInfo &video = tracker_.PendingVideo();
    int width = video.width ? video.width : monitor_->Width();
    int height = video.height ? video.height : monitor_->Height();

    switch (video.codec_id) {
      case AV_CODEC_ID_H264: {
        xop::H264Source *h264Source = xop::H264Source::CreateNew();
        h264Source->SetResolution(width, height);
        session->AddSource(xop::channel_0, h264Source);
        auto *source = new H264_ZoneMinderStreamSource(rtsp_server_, session_id, xop::channel_0);
        source->setH264Source(h264Source);  // Allow stream source to set SPS/PPS
        source->setWidth(width);
        source->setHeight(height);
        video_source_ = source;
        break;
      }
      case AV_CODEC_ID_HEVC: {
        xop::H265Source *h265Source = xop::H265Source::CreateNew();
        h265Source->SetResolution(width, height);
        session->AddSource(xop::channel_0, h265Source);
        auto *source = new H265_ZoneMinderStreamSource(rtsp_server_, session_id, xop::channel_0);
        source->setH265Source(h265Source);  // Allow stream source to set VPS/SPS/PPS
        source->setWidth(width);
        source->setHeight(height);
        video_source_ = source;
        break;
      }
      case AV_CODEC_ID_AV1: {
        xop::AV1Source *av1Source = xop::AV1Source::CreateNew();
        av1Source->SetResolution(width, height);
        session->AddSource(xop::channel_0, av1Source);
        auto *source = new AV1_ZoneMinderStreamSource(rtsp_server_, session_id, xop::channel_0);
        source->setAV1Source(av1Source);  // Allow stream source to set sequence header
        source->setWidth(width);
        source->setHeight(height);
        video_source_ = source;
        break;
      }
      default:
        Warning("Monitor %u: unsupported video codec %s for rtsp",
                monitor_->Id(), avcodec_get_name(video.codec_id));
        break;
    }
    if (!video_source_) {
      rtsp_server_->RemoveSession(session_id);
      return;
    }
    // Parameter sets from the HELLO prime the packer the same way the FIFO's
    // in-band extradata used to (Annex B for H.26x, sequence OBU for AV1)
    if (!video.extradata.empty())
      video_source_->OnPacket(video.extradata.data(), video.extradata.size(), 0);

    if (build_audio) {
      const HelloInfo &audio = tracker_.PendingAudio();
      bool supported = true;
      switch (audio.codec_id) {
        case AV_CODEC_ID_AAC:
          Debug(1, "Adding aac source at %uHz %u channels", audio.sample_rate, audio.channels);
          session->AddSource(xop::channel_1, xop::AACSource::CreateNew(
                                 audio.sample_rate, audio.channels, false /* has_adts */));
          break;
        case AV_CODEC_ID_PCM_ALAW:
          Debug(1, "Adding G711A source at %uHz %u channels", audio.sample_rate, audio.channels);
          session->AddSource(xop::channel_1, xop::G711ASource::CreateNew());
          break;
        case AV_CODEC_ID_PCM_MULAW:
          Debug(1, "Adding G711U source at %uHz %u channels", audio.sample_rate, audio.channels);
          session->AddSource(xop::channel_1, xop::G711USource::CreateNew());
          break;
        default:
          Warning("Monitor %u: unsupported audio codec %s for rtsp",
                  monitor_->Id(), avcodec_get_name(audio.codec_id));
          supported = false;
          break;
      }
      if (supported) {
        auto *source = new ADTS_ZoneMinderStreamSource(rtsp_server_, session_id, xop::channel_1);
        // G.711 is always 8 kHz; use that as the fallback when the HELLO
        // omitted the sample rate, rather than the AAC-oriented 44.1 kHz
        // default in setFrequency (a wrong rate skews the RTP timestamps).
        int frequency = audio.sample_rate;
        if (frequency <= 0
            and (audio.codec_id == AV_CODEC_ID_PCM_ALAW
                 or audio.codec_id == AV_CODEC_ID_PCM_MULAW))
          frequency = 8000;
        source->setFrequency(frequency);
        source->setChannels(audio.channels);
        audio_source_ = source;
      }
    }

    session_ = session;
    Info("Monitor %u: rtsp session %s serving %s%s%s (generation %u)", monitor_->Id(),
         stream_name_.c_str(), avcodec_get_name(video.codec_id),
         audio_source_ ? " + " : "",
         audio_source_ ? avcodec_get_name(tracker_.PendingAudio().codec_id) : "",
         tracker_.LatestGeneration());
  }

  std::shared_ptr<xop::RtspServer> rtsp_server_;
  std::shared_ptr<Monitor> monitor_;
  std::string stream_name_;
  std::unique_ptr<StreamSocketClient> client_;

  std::mutex mutex_;
  xop::MediaSession *session_ = nullptr;
  ZoneMinderStreamSource *video_source_ = nullptr;
  ZoneMinderStreamSource *audio_source_ = nullptr;
  RtspSessionTracker tracker_;  // HELLO/generation bookkeeping, guarded by mutex_
};

void Usage() {
  fprintf(stderr, "zm_rtsp_server -m <monitor_id>\n");

  fprintf(stderr, "Options:\n");
  fprintf(stderr, "  -m, --monitor <monitor_id> : We default to all monitors use this to specify just one\n");
  fprintf(stderr, "  -h, --help                 : This screen\n");
  fprintf(stderr, "  -v, --version              : Report the installed version of ZoneMinder\n");
  exit(0);
}

}  // namespace

int main(int argc, char *argv[]) {
  self = argv[0];

  srand(getpid() * time(nullptr));

  int monitor_id = -1;

  static struct option long_options[] = {
    {"monitor", 1, nullptr, 'm'},
    {"help", 0, nullptr, 'h'},
    {"version", 0, nullptr, 'v'},
    {nullptr, 0, nullptr, 0}
  };

  while (1) {
    int option_index = 0;
    int c = getopt_long(argc, argv, "m:h:v", long_options, &option_index);
    if (c == -1)
      break;

    switch (c) {
    case 'm':
      monitor_id = atoi(optarg);
      break;
    case 'h':
    case '?':
      Usage();
      break;
    case 'v':
      std::cout << ZM_VERSION << "\n";
      exit(0);
    default:
      // fprintf(stderr, "?? getopt returned character code 0%o ??\n", c);
      break;
    }
  }

  if (optind < argc) {
    fprintf(stderr, "Extraneous options, ");
    while (optind < argc)
      printf("%s ", argv[optind++]);
    printf("\n");
    Usage();
  }

  const char *log_id_string = "zm_rtsp_server";

  logInit(log_id_string);
  zmLoadStaticConfig();
  zmDbConnect();
  zmLoadDBConfig();
  logInit(log_id_string);
  if (!config.min_rtsp_port) {
    Debug(1, "Not starting RTSP server because min_rtsp_port not set");
    exit(-1);
  }

  HwCapsDetect();

  std::string where = "`Deleted` = 0 AND `Capturing` != 'None' AND `RTSPServer` != false";
  if (staticConfig.SERVER_ID)
    where += stringtf(" AND `ServerId`=%d", staticConfig.SERVER_ID);
  if (monitor_id > 0)
    where += stringtf(" AND `Id`=%d", monitor_id);

  Info("Starting RTSP Server version %s", ZM_VERSION);
  zmSetDefaultHupHandler();
  zmSetDefaultTermHandler();
  zmSetDefaultDieHandler();

  std::shared_ptr<xop::EventLoop> eventLoop(new xop::EventLoop());
  std::shared_ptr<xop::RtspServer> rtspServer = xop::RtspServer::Create(eventLoop.get());
  rtspServer->SetVersion("ZoneMinder RTSP Server");

  if (config.opt_use_auth) {
    std::shared_ptr<ZM_RtspServer_Authenticator> authenticator(new ZM_RtspServer_Authenticator());
    rtspServer->SetAuthenticator(authenticator);
  }

  if (!rtspServer->Start("0.0.0.0", config.min_rtsp_port)) {
    Debug(1, "Failed starting RTSP server on port %d", config.min_rtsp_port);
    exit(-1);
  }

  std::unordered_map<unsigned int, std::unique_ptr<MonitorRtspStream>> streams;
  std::unordered_map<unsigned int, std::shared_ptr<Monitor>> monitors;

  size_t last_monitor_count = SIZE_MAX;
  while (!zm_terminate) {
    std::vector<std::shared_ptr<Monitor>> new_monitors = Monitor::LoadMonitors(where, Monitor::QUERY);
    if (new_monitors.size() != last_monitor_count) {
      Info("Serving %zu monitor(s) matching: %s", new_monitors.size(), where.c_str());
      last_monitor_count = new_monitors.size();
    }

    std::unordered_map<unsigned int, std::shared_ptr<Monitor>> old_monitors = monitors;
    for (const auto &monitor : new_monitors) {
      auto old_it = old_monitors.find(monitor->Id());
      if (old_it != old_monitors.end()
          and old_it->second->GetRTSPStreamName() == monitor->GetRTSPStreamName()) {
        old_monitors.erase(old_it);
      } else {
        Debug(1, "Adding monitor %d to monitors", monitor->Id());
        monitors[monitor->Id()] = monitor;
      }
    }
    // Drop monitors that are gone or whose stream name changed (the latter
    // get rebuilt with the new name on the next pass)
    for (auto it = old_monitors.begin(); it != old_monitors.end(); ++it) {
      Debug(1, "Removing %d %s from monitors", it->second->Id(), it->second->Name());
      streams.erase(it->first);
      monitors.erase(it->first);
    }

    for (auto it = monitors.begin(); it != monitors.end(); ++it) {
      auto &monitor = it->second;

      auto stream_it = streams.find(monitor->Id());
      if (stream_it == streams.end()) {
        Debug(1, "Monitor %d not in streams, connecting to its stream socket", monitor->Id());
        stream_it = streams.emplace(monitor->Id(),
                                    zm::make_unique<MonitorRtspStream>(rtspServer, monitor)).first;
      }
      MonitorRtspStream &stream = *stream_it->second;
      stream.Update();

      if (stream.HasClients()) {
        // The shm connection is only needed for viewer accounting now
        if (monitor->ShmValid() or monitor->connect()) {
          monitor->setLastViewed(std::chrono::system_clock::now());
        }
      }
    }  // end foreach monitor

    {
      // Sleep until the next periodic pass, or earlier if a HELLO arrives
      // that needs a session (re)build
      std::unique_lock<std::mutex> lock(rebuild_mutex);
      rebuild_cv.wait_for(lock, std::chrono::seconds(10), [] { return rebuild_pending; });
      rebuild_pending = false;
    }

    if (zm_reload) {
      Info("Reloading configuration");
      logTerm();
      zmLoadDBConfig();
      logInit(log_id_string);
      zm_reload = false;
    }  // end if zm_reload
  } // end while !zm_terminate

  Info("RTSP Server shutting down");

  streams.clear();
  monitors.clear();
  rtspServer->Stop();

  Image::Deinitialise();
  logTerm();
  // Stop the queue before closing the connection it writes through; every other
  // daemon already does. Outside zmDbClose because that holds db_mutex, and the
  // queue thread wants that same mutex to drain, so joining it from in there
  // would deadlock.
  dbQueue.stop();
  zmDbClose();

  return 0;
}
