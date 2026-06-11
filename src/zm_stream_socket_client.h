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

#ifndef ZM_STREAM_SOCKET_CLIENT_H
#define ZM_STREAM_SOCKET_CLIENT_H

#include "zm_stream_socket_protocol.h"

#include <atomic>
#include <functional>
#include <string>
#include <thread>
#include <vector>

// Consumer side of the per-monitor media stream socket: connects to
// PATH_SOCKS/stream_{monitor_id}.sock (retrying until the producer is up),
// decodes the length-prefixed protocol and dispatches messages through
// callbacks on the reader thread. Reconnects automatically on EOF or error;
// a fresh HELLO arrives after every (re)connect.
//
// Callbacks run on the client's reader thread. Payload pointers passed to
// on_media are only valid for the duration of the call.
class StreamSocketClient {
 public:
  struct Callbacks {
    std::function<void(zm::stream_socket::StreamId stream,
                       const zm::stream_socket::HelloInfo &info,
                       uint32_t generation)> on_hello;
    // MEDIA and KEYFRAME messages; distinguish via header.type if needed
    std::function<void(const zm::stream_socket::Header &header,
                       const uint8_t *data, size_t size)> on_media;
    std::function<void(uint64_t sent, uint64_t dropped)> on_stats;
    std::function<void()> on_bye;
    std::function<void()> on_disconnect;  // EOF or read error; reconnect follows
  };

  // Connect (with retry) to a stream socket path.
  StreamSocketClient(std::string path, Callbacks callbacks);
  // Adopt an already-connected descriptor; no reconnect on EOF (used by tests).
  StreamSocketClient(int connected_fd, Callbacks callbacks);
  ~StreamSocketClient();
  StreamSocketClient(const StreamSocketClient &) = delete;
  StreamSocketClient &operator=(const StreamSocketClient &) = delete;

  void Stop();
  bool IsConnected() const { return connected_; }

 private:
  void Run();
  // Reads and dispatches messages until error/EOF/stop. Returns false on a
  // protocol violation or socket error (caller closes and reconnects).
  bool ReadLoop(int fd);
  bool ReadExact(int fd, uint8_t *out, size_t len);
  void Dispatch(const zm::stream_socket::Header &header,
                const uint8_t *payload, size_t size);

  std::string path_;
  Callbacks callbacks_;
  int adopted_fd_ = -1;
  std::thread thread_;
  std::atomic<bool> terminate_{false};
  std::atomic<bool> connected_{false};
  std::vector<uint8_t> payload_;  // reused across messages; grows to peak size
};

#endif  // ZM_STREAM_SOCKET_CLIENT_H
