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

#ifndef ZM_STREAM_SOCKET_H
#define ZM_STREAM_SOCKET_H

#include "zm_comms.h"
#include "zm_ffmpeg.h"
#include "zm_stream_socket_protocol.h"

#include <array>
#include <atomic>
#include <chrono>
#include <deque>
#include <memory>
#include <mutex>
#include <string>
#include <thread>
#include <vector>

// Per-monitor media stream server: one listening unix socket
// (PATH_SOCKS/stream_{monitor_id}.sock) serving the wire protocol defined in
// zm_stream_socket_protocol.h to multiple consumers at once.
//
// The producer (capture thread) never blocks on consumers: each client has a
// bounded outgoing queue; on overflow the oldest non-control messages are
// dropped and the loss is observable through sequence gaps and STATS.
// Media payloads are reference-counted (av_packet_clone), serialized once and
// shared by every client queue - no per-client payload copies are made.
class StreamSocket {
 public:
  struct Config {
    std::string group;                  // socket group, chmod 0660; empty = leave default
    std::vector<uid_t> allowed_uids;    // empty = allow all (filesystem perms still apply)
    size_t max_clients = 8;
    size_t queue_max_bytes = 8 * 1024 * 1024;
    size_t queue_max_msgs = 256;
    std::chrono::milliseconds stall_timeout = std::chrono::seconds(10);
    std::chrono::milliseconds stats_interval = std::chrono::seconds(5);
  };

  StreamSocket(unsigned int monitor_id, std::string path);
  StreamSocket(unsigned int monitor_id, std::string path, Config config);
  ~StreamSocket();
  StreamSocket(const StreamSocket &) = delete;
  StreamSocket &operator=(const StreamSocket &) = delete;

  bool Start();   // unlink stale path, bind, apply perms, listen, spawn thread
  void Stop();    // send BYE to connected clients, close everything, unlink

  bool IsRunning() const { return thread_.joinable(); }
  const std::string &Path() const { return path_; }

  // Apply the monitor's complete stream parameter set in one step. Either
  // pointer may be null (or carry AV_CODEC_ID_NONE) for "this source has no
  // such stream". Compared against what is currently announced:
  //  - nothing changed: no-op
  //  - a change after a video HELLO has gone out (parameters differ, a stream
  //    appeared or disappeared): exactly one generation bump, sequences
  //    restart, the cached keyframe is dropped and every remaining stream is
  //    re-announced
  //  - the initial announcement: generation stays 0
  // Within a generation the audio HELLO is always sent before the video
  // HELLO, so a consumer can treat the video HELLO as completing that
  // generation's parameter set; a generation that ends without a video HELLO
  // means the source currently has no video. Because both streams are applied
  // under one lock, a consumer never sees (and a connecting consumer is never
  // handed) a generation that mixes new audio with old video or vice versa.
  // frame_rate is video-only; pass {0, 0} when unknown.
  void SetStreams(const AVCodecParameters *video, AVRational frame_rate,
                  const AVCodecParameters *audio);

  // Single-stream conveniences over the same logic, leaving the other stream
  // as it is. A monitor re-prime should use SetStreams so a change to both
  // streams costs one generation, not two.
  void SetVideoParams(const AVCodecParameters *par, AVRational frame_rate);
  void SetAudioParams(const AVCodecParameters *par);
  void ClearVideoParams();
  void ClearAudioParams();

  // Queue one access unit / audio packet to every connected client.
  // pts_us must be in AV_TIME_BASE_Q. The packet's payload buffer is
  // reference-counted, not copied. Never blocks.
  void SendMedia(const AVPacket *packet, zm::stream_socket::StreamId stream,
                 bool keyframe, int64_t pts_us);

  // Broadcast a monitor lifecycle EVENT to every connected client. payload is a
  // pre-built EVENT body (see BuildEvent); the header is framed here with the
  // next per-monitor event sequence and the current media generation. Events
  // are control messages - never dropped from a client queue. Never blocks.
  void SendMonitorEvent(std::vector<uint8_t> payload);

  // Cache the current-status snapshot replayed to each new consumer on connect
  // (the events analogue of the cached keyframe). payload is a pre-built EVENT
  // body of code kEventSnapshot. Caching only; does not broadcast. The header
  // is framed when a consumer connects, so it always carries the generation
  // and event sequence in effect at that moment.
  void SetSnapshotEvent(std::vector<uint8_t> payload);

  // Drop the cached keyframe. Called when the capture source closes so a
  // consumer that connects during a reconnect is not primed with a keyframe
  // whose pts belongs to the previous capture session.
  void InvalidateKeyframe();

  // Parses the ZM_STREAM_SOCKET_ALLOWED_UIDS setting (comma-separated uids;
  // whitespace tolerated, malformed entries ignored with a warning).
  static std::vector<uid_t> ParseAllowedUids(const std::string &value);

  // Builds a Config from the ZM_STREAM_SOCKET_* settings in staticConfig.
  static Config ConfigFromStatic();

 private:
  using TimePoint = std::chrono::steady_clock::time_point;

  // One protocol message, serialized once and shared across all client queues.
  struct Message {
    std::array<uint8_t, zm::stream_socket::kHeaderSize> header;
    av_packet_ptr av_payload;            // MEDIA/KEYFRAME: refcounted, no copy
    std::vector<uint8_t> blob_payload;   // HELLO/STATS/BYE: small owned blob
    bool control = false;                // never dropped from a queue

    const uint8_t *payload_data() const {
      return av_payload ? av_payload->data : blob_payload.data();
    }
    size_t payload_size() const {
      return av_payload ? static_cast<size_t>(av_payload->size) : blob_payload.size();
    }
    size_t size() const { return zm::stream_socket::kHeaderSize + payload_size(); }
  };
  using MessagePtr = std::shared_ptr<const Message>;

  struct Client {
    std::unique_ptr<zm::TcpUnixSocket> sock;
    std::deque<MessagePtr> queue;
    size_t queued_bytes = 0;
    size_t front_offset = 0;             // bytes of queue.front() already written
    bool front_in_flight = false;        // queue.front() is being written outside the lock
    uint64_t sent = 0;
    uint64_t dropped = 0;
    TimePoint last_progress;
    TimePoint last_stats;
    uid_t uid = 0;
    pid_t pid = 0;
  };

  void Run();
  void AcceptClient();
  bool CheckPeer(int fd, uid_t &uid, pid_t &pid) const;
  void ApplyPermissions();

  // The following require mutex_ to be held by the caller.
  MessagePtr MakeMessage(zm::stream_socket::MessageType type,
                         zm::stream_socket::StreamId stream, uint8_t flags,
                         uint32_t sequence, int64_t pts_us,
                         std::vector<uint8_t> payload, bool control) const;
  void EnqueueLocked(Client &client, const MessagePtr &message);
  void BroadcastLocked(const MessagePtr &message);
  // Core of SetStreams and its single-stream wrappers; payloads are HELLO
  // bodies, empty meaning "no such stream". Returns true if anything changed.
  bool ApplyStreamsLocked(std::vector<uint8_t> video, std::vector<uint8_t> audio);
  void SendStatsLocked(Client &client, TimePoint now);

  // Writes as much of the client's queue as the socket accepts. Takes mutex_
  // itself, and only for bookkeeping: the kernel copy in sendmsg happens with
  // the lock released so the capture thread's SendMedia never waits on it.
  // Listener thread only. Returns false when the client must be disconnected.
  bool DrainClient(Client &client);

  void Wake();

  unsigned int monitor_id_;
  std::string path_;
  Config config_;

  std::thread thread_;
  std::atomic<bool> terminate_{false};

  zm::TcpUnixServer listener_;
  zm::Pipe wake_;

  std::mutex mutex_;
  std::vector<std::unique_ptr<Client>> clients_;
  MessagePtr hello_video_;
  MessagePtr hello_audio_;
  MessagePtr keyframe_;
  std::vector<uint8_t> snapshot_payload_;  // current-status EVENT body, framed per connect
  std::vector<uint8_t> hello_video_payload_;
  std::vector<uint8_t> hello_audio_payload_;
  uint32_t generation_ = 0;
  uint32_t sequence_[2] = {0, 0};  // indexed by StreamId (Video, Audio)
  uint32_t event_sequence_ = 0;    // per-monitor lifecycle event counter
};

#endif  // ZM_STREAM_SOCKET_H
