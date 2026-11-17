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

#endif  // HAVE_RTSP_SERVER
