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

#include "zm_rtsp_server_authenticator.h"

namespace {

class PermUser : public User {
 public:
  PermUser(Permission stream_perm, Permission monitors_perm) {
    stream = stream_perm;
    monitors = monitors_perm;
    // No database in the tests: nothing to load, so canAccess() falls back to
    // the user's Monitors permission.
    monitor_permissions_loaded = true;
    group_permissions_loaded = true;
    role_monitor_permissions_loaded = true;
    role_group_permissions_loaded = true;
  }
};

}  // namespace

// zm_rtsp_server accepted any valid user, so an account without Stream permission, or
// without access to the monitor, could view the monitor's rtsp stream.
TEST_CASE("RTSP server user may stream a monitor only with Stream and monitor access", "[zm_rtsp_server]") {
  PermUser no_stream(User::PERM_NONE, User::PERM_VIEW);
  REQUIRE_FALSE(ZM_RtspServer_Authenticator::UserMayStream(&no_stream, 5));

  PermUser no_monitor(User::PERM_VIEW, User::PERM_NONE);
  REQUIRE_FALSE(ZM_RtspServer_Authenticator::UserMayStream(&no_monitor, 5));

  PermUser allowed(User::PERM_VIEW, User::PERM_VIEW);
  REQUIRE(ZM_RtspServer_Authenticator::UserMayStream(&allowed, 5));
}

TEST_CASE("RTSP server finds the monitor behind a stream url", "[zm_rtsp_server]") {
  ZM_RtspServer_Authenticator authenticator;
  authenticator.SetStreams({{"cam", 4}, {"cam1", 3}});

  REQUIRE(authenticator.MonitorIdForUrl("rtsp://host:554/cam1?token=abc") == 3);
  REQUIRE(authenticator.MonitorIdForUrl("rtsp://host:554/cam1/track0?token=abc") == 3);
  REQUIRE(authenticator.MonitorIdForUrl("rtsp://host/cam") == 4);
  REQUIRE(authenticator.MonitorIdForUrl("rtsp://host/cam12") == 0);
  REQUIRE(authenticator.MonitorIdForUrl("rtsp://host/other") == 0);
  REQUIRE(authenticator.MonitorIdForUrl("rtsp://host/") == 0);
}

#endif  // HAVE_RTSP_SERVER
