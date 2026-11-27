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

#include "zm_rtsp_server_session_tracker.h"

using Action = RtspSessionTracker::Action;
using zm::stream_socket::HelloInfo;
using zm::stream_socket::StreamId;

namespace {

HelloInfo h264(uint32_t width = 1920, uint32_t height = 1080) {
  HelloInfo info;
  info.codec_id = AV_CODEC_ID_H264;
  info.width = width;
  info.height = height;
  info.extradata = {0x00, 0x00, 0x00, 0x01, 0x67};
  return info;
}

HelloInfo hevc() {
  HelloInfo info;
  info.codec_id = AV_CODEC_ID_HEVC;
  info.width = 1920;
  info.height = 1080;
  return info;
}

HelloInfo aac(uint32_t sample_rate = 48000) {
  HelloInfo info;
  info.codec_id = AV_CODEC_ID_AAC;
  info.sample_rate = sample_rate;
  info.channels = 2;
  return info;
}

// Plays the main thread: acts on the plan as if the build succeeded
Action Reconcile(RtspSessionTracker &tracker, bool &have_session, bool build_ok = true) {
  Action action = tracker.Plan(have_session);
  if (action == Action::Rebuild) {
    tracker.SessionGone();
    have_session = build_ok;
  }
  tracker.Confirm(action, have_session);
  return action;
}

}  // namespace

TEST_CASE("RtspSessionTracker builds once the video HELLO completes the set", "[rtsp_session_tracker]") {
  RtspSessionTracker tracker;
  bool have_session = false;

  REQUIRE(tracker.Plan(have_session) == Action::None);
  REQUIRE_FALSE(tracker.Accepts(0));

  // Audio arrives first within a generation; nothing to build on yet
  tracker.OnHello(StreamId::Audio, aac(), 0);
  REQUIRE(tracker.Plan(have_session) == Action::None);

  tracker.OnHello(StreamId::Video, h264(), 0);
  REQUIRE_FALSE(tracker.Accepts(0));  // not before the main thread has built
  REQUIRE(Reconcile(tracker, have_session) == Action::Rebuild);
  REQUIRE(tracker.HavePendingAudio());
  REQUIRE(tracker.Accepts(0));
  REQUIRE_FALSE(tracker.Accepts(1));

  // Nothing further to do on the next pass
  REQUIRE(tracker.Plan(have_session) == Action::None);
}

TEST_CASE("RtspSessionTracker holds media between a new HELLO and the rebuild", "[rtsp_session_tracker]") {
  RtspSessionTracker tracker;
  bool have_session = false;
  tracker.OnHello(StreamId::Video, h264(), 0);
  Reconcile(tracker, have_session);
  REQUIRE(tracker.Accepts(0));

  // Camera reconfigured: generation 1 with a different codec
  tracker.OnHello(StreamId::Video, hevc(), 1);
  REQUIRE_FALSE(tracker.Accepts(0));  // stragglers from the old generation
  REQUIRE_FALSE(tracker.Accepts(1));  // new media, old packer still in place

  REQUIRE(Reconcile(tracker, have_session) == Action::Rebuild);
  REQUIRE(tracker.Accepts(1));
  REQUIRE_FALSE(tracker.Accepts(0));
}

TEST_CASE("RtspSessionTracker does not trust a generation number reused after a producer restart", "[rtsp_session_tracker]") {
  RtspSessionTracker tracker;
  bool have_session = false;
  tracker.OnHello(StreamId::Video, h264(), 0);
  Reconcile(tracker, have_session);
  REQUIRE(tracker.Accepts(0));

  // zmc restarts: generations start again at 0, but the camera now sends HEVC.
  // The replayed keyframe carries generation 0, the number the H.264 session
  // was built for, and must not reach its packer.
  tracker.OnDisconnect();
  REQUIRE_FALSE(tracker.Accepts(0));
  tracker.OnHello(StreamId::Video, hevc(), 0);
  REQUIRE_FALSE(tracker.Accepts(0));

  REQUIRE(Reconcile(tracker, have_session) == Action::Rebuild);
  REQUIRE(tracker.PendingVideo().codec_id == AV_CODEC_ID_HEVC);
  REQUIRE(tracker.Accepts(0));
}

TEST_CASE("RtspSessionTracker keeps the session across a restart with unchanged parameters", "[rtsp_session_tracker]") {
  RtspSessionTracker tracker;
  bool have_session = false;
  tracker.OnHello(StreamId::Audio, aac(), 3);
  tracker.OnHello(StreamId::Video, h264(), 3);
  Reconcile(tracker, have_session);
  REQUIRE(tracker.Accepts(3));

  tracker.OnDisconnect();
  tracker.OnHello(StreamId::Audio, aac(), 0);
  tracker.OnHello(StreamId::Video, h264(), 0);
  // Same parameters: RTSP clients stay connected, only the generation moves.
  // Media still waits for the main thread to say so.
  REQUIRE_FALSE(tracker.Accepts(0));
  REQUIRE(Reconcile(tracker, have_session) == Action::Adopt);
  REQUIRE(have_session);
  REQUIRE(tracker.Accepts(0));
  REQUIRE_FALSE(tracker.Accepts(3));
}

TEST_CASE("RtspSessionTracker rebuilds when audio appears or disappears", "[rtsp_session_tracker]") {
  RtspSessionTracker tracker;
  bool have_session = false;
  tracker.OnHello(StreamId::Video, h264(), 0);
  Reconcile(tracker, have_session);

  // Audio joins: new generation, audio HELLO then video HELLO
  tracker.OnHello(StreamId::Audio, aac(), 1);
  REQUIRE(tracker.Plan(have_session) == Action::None);  // set not complete yet
  tracker.OnHello(StreamId::Video, h264(), 1);
  REQUIRE(Reconcile(tracker, have_session) == Action::Rebuild);
  REQUIRE(tracker.HavePendingAudio());

  // Audio goes away: the new generation re-announces video only
  tracker.OnHello(StreamId::Video, h264(), 2);
  REQUIRE_FALSE(tracker.HavePendingAudio());
  REQUIRE(Reconcile(tracker, have_session) == Action::Rebuild);
  REQUIRE(tracker.Accepts(2));

  // Audio parameters change
  tracker.OnHello(StreamId::Audio, aac(48000), 3);
  tracker.OnHello(StreamId::Video, h264(), 3);
  Reconcile(tracker, have_session);
  tracker.OnHello(StreamId::Audio, aac(8000), 4);
  tracker.OnHello(StreamId::Video, h264(), 4);
  REQUIRE(Reconcile(tracker, have_session) == Action::Rebuild);
}

TEST_CASE("RtspSessionTracker stops feeding a generation that lost its video", "[rtsp_session_tracker]") {
  RtspSessionTracker tracker;
  bool have_session = false;
  tracker.OnHello(StreamId::Audio, aac(), 0);
  tracker.OnHello(StreamId::Video, h264(), 0);
  Reconcile(tracker, have_session);

  // Generation 1 announces audio only
  tracker.OnHello(StreamId::Audio, aac(), 1);
  REQUIRE(Reconcile(tracker, have_session) == Action::None);
  REQUIRE_FALSE(tracker.Accepts(1));
  REQUIRE_FALSE(tracker.Accepts(0));
}

TEST_CASE("RtspSessionTracker does not retry a set that failed to build until a new HELLO", "[rtsp_session_tracker]") {
  RtspSessionTracker tracker;
  bool have_session = false;
  tracker.OnHello(StreamId::Video, h264(), 0);

  // Unsupported codec, say: the build produces no session
  REQUIRE(Reconcile(tracker, have_session, false) == Action::Rebuild);
  REQUIRE_FALSE(have_session);
  REQUIRE_FALSE(tracker.Accepts(0));
  REQUIRE(tracker.Plan(have_session) == Action::None);  // no rebuild storm every pass

  tracker.OnHello(StreamId::Video, hevc(), 1);
  REQUIRE(Reconcile(tracker, have_session) == Action::Rebuild);
  REQUIRE(tracker.Accepts(1));
}

TEST_CASE("RtspSessionTracker ignores HELLOs for streams it does not serve", "[rtsp_session_tracker]") {
  RtspSessionTracker tracker;
  bool have_session = false;
  tracker.OnHello(StreamId::Video, h264(), 0);
  Reconcile(tracker, have_session);
  REQUIRE(tracker.Accepts(0));

  // Same generation, so nothing about the parameter set has changed
  tracker.OnHello(StreamId::Monitor, HelloInfo(), 0);
  REQUIRE(tracker.Accepts(0));
}
