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

#ifndef ZM_RTSP_SERVER_SESSION_TRACKER_H
#define ZM_RTSP_SERVER_SESSION_TRACKER_H

#include "zm_stream_socket_protocol.h"

#include <cstdint>

// Decides, for one monitor, when zm_rtsp_server's RTSP session must be
// rebuilt and whether a media packet may be fed to the current packers.
//
// It follows the stream socket's HELLO handshake: a generation's parameter set
// is complete when its video HELLO arrives (audio, if any, comes first). The
// reader thread reports HELLOs and disconnects; the main thread asks Plan(),
// acts on it and reports back with Confirm(). Media is accepted only for a
// session the main thread has confirmed against the latest HELLO set, which
// covers the window between a HELLO and the rebuild it requires - including a
// restarted producer that reuses the generation number the session already has.
//
// No locking and no RTSP types: the owner serialises calls with its own mutex,
// and the class is unit-tested on its own.
class RtspSessionTracker {
 public:
  using HelloInfo = zm::stream_socket::HelloInfo;
  using StreamId = zm::stream_socket::StreamId;

  enum class Action {
    None,     // nothing to do (no complete HELLO set, already current, or the set cannot be served)
    Adopt,    // same parameters as the built session: keep it, accept the new generation
    Rebuild,  // tear down and build from PendingVideo()/PendingAudio()
  };

  void OnHello(StreamId stream, const HelloInfo &info, uint32_t generation) {
    // A HELLO from a new generation starts a fresh parameter set: forget the
    // previous generation's HELLOs so a stream that is not re-announced (the
    // source dropped its audio) does not linger.
    if (!have_generation_ or generation != latest_generation_) {
      latest_generation_ = generation;
      have_generation_ = true;
      have_pending_video_ = false;
      have_pending_audio_ = false;
    }
    if (stream == StreamId::Video) {
      pending_video_ = info;
      have_pending_video_ = true;
    } else if (stream == StreamId::Audio) {
      pending_audio_ = info;
      have_pending_audio_ = true;
    } else {
      return;
    }
    // Whatever was confirmed or refused before, this HELLO may change it
    confirmed_ = false;
    build_failed_ = false;
  }

  // The producer went away. The next one re-announces everything and may
  // restart its generations at 0, so nothing recorded so far may pass as its.
  void OnDisconnect() {
    have_generation_ = false;
    have_pending_video_ = false;
    have_pending_audio_ = false;
    confirmed_ = false;
  }

  Action Plan(bool have_session) const {
    if (!have_pending_video_)
      return Action::None;  // set incomplete: between a generation's HELLOs, or no video
    if (have_session and have_built_ and PendingMatchesBuilt())
      return confirmed_ ? Action::None : Action::Adopt;
    // A set that already failed to build (unsupported codec) is not retried
    // until a new HELLO arrives.
    return build_failed_ ? Action::None : Action::Rebuild;
  }

  // Outcome of the action Plan() asked for. session_ok says whether a session
  // serving the pending set now exists.
  void Confirm(Action action, bool session_ok) {
    if (action == Action::None)
      return;
    if (!session_ok) {
      have_built_ = false;
      confirmed_ = false;
      build_failed_ = true;
      return;
    }
    if (action == Action::Rebuild) {
      built_video_ = pending_video_;
      built_audio_ = pending_audio_;
      built_with_audio_ = have_pending_audio_;
      have_built_ = true;
    }
    built_generation_ = latest_generation_;
    confirmed_ = true;
  }

  // The session was torn down by the owner for its own reasons
  void SessionGone() {
    have_built_ = false;
    confirmed_ = false;
  }

  bool Accepts(uint32_t generation) const {
    return confirmed_ and have_built_ and generation == built_generation_;
  }

  const HelloInfo &PendingVideo() const { return pending_video_; }
  const HelloInfo &PendingAudio() const { return pending_audio_; }
  bool HavePendingAudio() const { return have_pending_audio_; }
  uint32_t LatestGeneration() const { return latest_generation_; }

  static bool HelloEqual(const HelloInfo &a, const HelloInfo &b) {
    return a.codec_id == b.codec_id
           and a.extradata == b.extradata
           and a.width == b.width and a.height == b.height
           and a.fps_num == b.fps_num and a.fps_den == b.fps_den
           and a.sample_rate == b.sample_rate and a.channels == b.channels;
  }

 private:
  bool PendingMatchesBuilt() const {
    // Audio is compared by whether it was announced, not by whether a packer
    // exists for it: an unsupported audio codec must not force a rebuild on
    // every pass.
    return HelloEqual(built_video_, pending_video_)
           and have_pending_audio_ == built_with_audio_
           and (!have_pending_audio_ or HelloEqual(built_audio_, pending_audio_));
  }

  HelloInfo pending_video_, pending_audio_;
  bool have_pending_video_ = false;
  bool have_pending_audio_ = false;
  bool have_generation_ = false;    // latest_generation_ is valid
  uint32_t latest_generation_ = 0;  // generation of the most recent HELLO

  HelloInfo built_video_, built_audio_;
  bool built_with_audio_ = false;
  bool have_built_ = false;
  uint32_t built_generation_ = 0;   // generation the session was last confirmed for

  bool confirmed_ = false;          // main thread has reconciled the latest HELLO set
  bool build_failed_ = false;       // the latest set could not be served
};

#endif  // ZM_RTSP_SERVER_SESSION_TRACKER_H
