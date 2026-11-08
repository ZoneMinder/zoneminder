/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_monitor.h"
#include "zm_utils.h"

#include <algorithm>

// The Remote/RTSP capture method is deprecated in favour of Ffmpeg. The
// warning it logs names the Source Path the operator should switch to, so that
// URL has to be correct and has to be safe to print.

TEST_CASE("Remote to Ffmpeg RTSP URL") {
  SECTION("the ordinary case") {
    REQUIRE(Monitor::RtspUrlFromRemote("10.0.0.5", "554", "/live/main", "admin", "secret")
            == "rtsp://admin:secret@10.0.0.5:554/live/main");
  }

  SECTION("no credentials means no userinfo, not an empty one") {
    // "rtsp://@host" is accepted by some parsers and rejected by others.
    REQUIRE(Monitor::RtspUrlFromRemote("10.0.0.5", "554", "/live", "", "")
            == "rtsp://10.0.0.5:554/live");
  }

  SECTION("a user with no password keeps the user") {
    REQUIRE(Monitor::RtspUrlFromRemote("10.0.0.5", "554", "/live", "admin", "")
            == "rtsp://admin@10.0.0.5:554/live");
  }

  SECTION("a password containing @ or : cannot split the authority") {
    // This is the case that makes naive concatenation produce a URL pointing at
    // the wrong host entirely.
    const std::string url =
        Monitor::RtspUrlFromRemote("10.0.0.5", "554", "/live", "admin", "p@ss:w0rd");
    REQUIRE(url == "rtsp://admin:p%40ss%3Aw0rd@10.0.0.5:554/live");
    // Exactly one @ separates userinfo from host.
    REQUIRE(std::count(url.begin(), url.end(), '@') == 1);
    REQUIRE(url.find("@10.0.0.5") != std::string::npos);
  }

  SECTION("other awkward password characters are escaped") {
    REQUIRE(Monitor::RtspUrlFromRemote("h", "", "/p", "u", "a/b?c#d")
            == "rtsp://u:a%2Fb%3Fc%23d@h/p");
    REQUIRE(Monitor::RtspUrlFromRemote("h", "", "/p", "u", "a b")
            == "rtsp://u:a%20b@h/p");
  }

  SECTION("a username needing escaping is escaped too") {
    REQUIRE(Monitor::RtspUrlFromRemote("h", "", "/p", "dom\\user", "pw")
            == "rtsp://dom%5Cuser:pw@h/p");
  }

  SECTION("a path without a leading slash gains one") {
    REQUIRE(Monitor::RtspUrlFromRemote("h", "554", "live/main", "", "")
            == "rtsp://h:554/live/main");
  }

  SECTION("an empty path still yields a slash") {
    REQUIRE(Monitor::RtspUrlFromRemote("h", "554", "", "", "") == "rtsp://h:554/");
  }

  SECTION("an absent port is omitted rather than left dangling") {
    REQUIRE(Monitor::RtspUrlFromRemote("h", "", "/p", "", "") == "rtsp://h/p");
  }

  SECTION("a query string in the path survives") {
    // Plenty of cameras carry ?channel=1&subtype=0; the path is copied through.
    REQUIRE(Monitor::RtspUrlFromRemote("h", "554", "/cam?channel=1&subtype=0", "", "")
            == "rtsp://h:554/cam?channel=1&subtype=0");
  }
}

TEST_CASE("The deprecation warning does not leak the password") {
  // The warning is logged, so it must go through remove_authentication first.
  const std::string url =
      Monitor::RtspUrlFromRemote("10.0.0.5", "554", "/live", "admin", "hunter2");
  REQUIRE(url.find("hunter2") != std::string::npos);

  const std::string masked = remove_authentication(url);
  REQUIRE(masked.find("hunter2") == std::string::npos);
  REQUIRE(masked.find("10.0.0.5") != std::string::npos);
}
