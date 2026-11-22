/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 */

#include "zm_catch2.h"

#include "zm_config.h"

#if HAVE_RTSP_SERVER

// The helpers live in zm_rtsp_server.cpp; build it here with its main() renamed.
#define main zm_rtsp_server_main
#include "zm_rtsp_server.cpp"
#undef main

namespace {
class IdMonitor : public Monitor {
 public:
  explicit IdMonitor(unsigned int monitor_id) : Monitor() { id = monitor_id; }
};
}  // namespace

// zm_rtsp_server keeps the Monitor it loaded first. After zmc restarts with a
// different image buffer count the old copy's shared memory size is wrong and
// connect() fails on every pass, so the viewer is never recorded and an
// on-demand monitor stays paused.
TEST_CASE("zm_rtsp_server finds the monitor loaded in the current pass", "[zm_rtsp_server]") {
  std::vector<std::shared_ptr<Monitor>> loaded;
  loaded.push_back(std::make_shared<IdMonitor>(3));
  loaded.push_back(std::make_shared<IdMonitor>(7));

  REQUIRE(FindLoadedMonitor(loaded, 7) == loaded[1]);
  REQUIRE(FindLoadedMonitor(loaded, 3) == loaded[0]);
  REQUIRE(FindLoadedMonitor(loaded, 9) == nullptr);
}

// An empty RTSPStreamName must not take the "" session slot.
#include "net/EventLoop.h"
#include "zm_stream_socket.h"

#include <chrono>
#include <thread>
#include <unistd.h>

namespace {

class NamedMonitor : public Monitor {
 public:
  NamedMonitor(unsigned int monitor_id, const std::string &stream_name) : Monitor() {
    id = monitor_id;
    name = "Named";
    rtsp_streamname = stream_name;
  }
};

// Feeds one H264 HELLO to a MonitorRtspStream and reports whether a session
// showed up under stream_name on the rtsp server.
bool SessionServed(const std::string &stream_name) {
  std::string old_socks = staticConfig.PATH_SOCKS;
  staticConfig.PATH_SOCKS = "/tmp";
  unsigned int monitor_id = 900000 + static_cast<unsigned int>(getpid() % 100000);
  std::string path = stringtf("/tmp/stream_%u.sock", monitor_id);

  xop::EventLoop loop;
  std::this_thread::sleep_for(std::chrono::milliseconds(100));
  std::shared_ptr<xop::RtspServer> server = xop::RtspServer::Create(&loop);

  bool served = false;
  {
    StreamSocket stream_socket(monitor_id, path);
    REQUIRE(stream_socket.Start());
    AVCodecParameters *par = avcodec_parameters_alloc();
    par->codec_type = AVMEDIA_TYPE_VIDEO;
    par->codec_id = AV_CODEC_ID_H264;
    par->width = 640;
    par->height = 480;
    stream_socket.SetVideoParams(par, {10, 1});
    avcodec_parameters_free(&par);

    auto monitor = std::make_shared<NamedMonitor>(monitor_id, stream_name);
    MonitorRtspStream stream(server, monitor);
    auto deadline = std::chrono::steady_clock::now() + std::chrono::seconds(2);
    while (!served and std::chrono::steady_clock::now() < deadline) {
      stream.Update();
      // a probe session is refused when the name is already taken
      xop::MediaSession *probe = xop::MediaSession::CreateNew(stream_name);
      xop::MediaSessionId probe_id = server->AddSession(probe);
      served = probe_id == 0;
      if (served)
        delete probe;
      else
        server->RemoveSession(probe_id);
      if (!served)
        std::this_thread::sleep_for(std::chrono::milliseconds(50));
    }
    stream.RequestStop();
  }
  staticConfig.PATH_SOCKS = old_socks;
  return served;
}

}  // namespace

TEST_CASE("RTSP monitor with a stream name is served", "[rtsp_empty_name]") {
  CHECK(SessionServed("cam"));
}

TEST_CASE("RTSP monitor with an empty stream name is not served", "[rtsp_empty_name]") {
  CHECK_FALSE(SessionServed(""));
}

#endif  // HAVE_RTSP_SERVER
