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

#include "zm_stream_socket_client.h"

#include "zm_logger.h"

#include <cerrno>
#include <chrono>
#include <cstring>
#include <sys/socket.h>
#include <sys/un.h>
#include <unistd.h>

using namespace zm::stream_socket;

namespace {

// Reads block at most this long so Stop() is honoured promptly.
constexpr timeval kReadTimeout = {1, 0};
constexpr std::chrono::seconds kReconnectDelay{1};

}  // namespace

StreamSocketClient::StreamSocketClient(std::string path, Callbacks callbacks) :
  path_(std::move(path)),
  callbacks_(std::move(callbacks)) {
  thread_ = std::thread(&StreamSocketClient::Run, this);
}

StreamSocketClient::StreamSocketClient(int connected_fd, Callbacks callbacks) :
  callbacks_(std::move(callbacks)),
  adopted_fd_(connected_fd) {
  thread_ = std::thread(&StreamSocketClient::Run, this);
}

StreamSocketClient::~StreamSocketClient() {
  Stop();
}

void StreamSocketClient::Stop() {
  terminate_ = true;
  if (thread_.joinable())
    thread_.join();
}

void StreamSocketClient::Run() {
  if (adopted_fd_ >= 0) {
    setsockopt(adopted_fd_, SOL_SOCKET, SO_RCVTIMEO, &kReadTimeout, sizeof(kReadTimeout));
    connected_ = true;
    ReadLoop(adopted_fd_);
    connected_ = false;
    ::close(adopted_fd_);
    adopted_fd_ = -1;
    if (callbacks_.on_disconnect and !terminate_)
      callbacks_.on_disconnect();
    return;
  }

  while (!terminate_) {
    int fd = ::socket(AF_UNIX, SOCK_STREAM, 0);
    if (fd < 0) {
      Error("StreamSocketClient: socket() failed: %s", strerror(errno));
      return;
    }
    sockaddr_un addr = {};
    addr.sun_family = AF_UNIX;
    strncpy(addr.sun_path, path_.c_str(), sizeof(addr.sun_path) - 1);

    if (::connect(fd, reinterpret_cast<sockaddr *>(&addr), sizeof(addr)) != 0) {
      Debug(2, "StreamSocketClient: connect to %s failed: %s, retrying",
            path_.c_str(), strerror(errno));
      ::close(fd);
      // sleep in small steps so Stop() stays responsive
      auto deadline = std::chrono::steady_clock::now() + kReconnectDelay;
      while (!terminate_ and std::chrono::steady_clock::now() < deadline)
        std::this_thread::sleep_for(std::chrono::milliseconds(100));
      continue;
    }

    Debug(1, "StreamSocketClient: connected to %s", path_.c_str());
    setsockopt(fd, SOL_SOCKET, SO_RCVTIMEO, &kReadTimeout, sizeof(kReadTimeout));
    connected_ = true;
    ReadLoop(fd);
    connected_ = false;
    ::close(fd);

    if (terminate_)
      break;
    Debug(1, "StreamSocketClient: disconnected from %s, reconnecting", path_.c_str());
    if (callbacks_.on_disconnect)
      callbacks_.on_disconnect();
  }
}

bool StreamSocketClient::ReadExact(int fd, uint8_t *out, size_t len) {
  size_t done = 0;
  while (done < len) {
    if (terminate_)
      return false;
    ssize_t bytes = ::recv(fd, out + done, len - done, 0);
    if (bytes > 0) {
      done += bytes;
      continue;
    }
    if (bytes < 0 and (errno == EAGAIN or errno == EWOULDBLOCK or errno == EINTR))
      continue;  // read timeout tick or signal; check terminate_ and resume
    return false;  // EOF or hard error
  }
  return true;
}

bool StreamSocketClient::ReadLoop(int fd) {
  uint8_t header_bytes[kHeaderSize];

  while (!terminate_) {
    if (!ReadExact(fd, header_bytes, kHeaderSize))
      return false;

    Header header;
    if (!ParseHeader(header_bytes, header)) {
      Warning("StreamSocketClient: invalid header on %s (version %u length %u),"
              " resyncing via reconnect", path_.c_str(), header.version, header.length);
      return false;
    }

    size_t payload_size = header.payload_size();
    if (payload_size > payload_.size())
      payload_.resize(payload_size);
    if (payload_size > 0 and !ReadExact(fd, payload_.data(), payload_size))
      return false;

    Dispatch(header, payload_.data(), payload_size);
  }
  return true;
}

void StreamSocketClient::Dispatch(const Header &header,
                                  const uint8_t *payload, size_t size) {
  switch (header.type) {
    case static_cast<uint8_t>(MessageType::Hello): {
      HelloInfo info;
      if (!ParseHello(payload, size, info)) {
        Warning("StreamSocketClient: malformed HELLO on %s", path_.c_str());
        return;
      }
      Debug(1, "StreamSocketClient: HELLO stream %u codec %d generation %u on %s",
            header.stream, info.codec_id, header.generation, path_.c_str());
      if (callbacks_.on_hello)
        callbacks_.on_hello(static_cast<StreamId>(header.stream), info, header.generation);
      break;
    }
    case static_cast<uint8_t>(MessageType::Media):
    case static_cast<uint8_t>(MessageType::Keyframe):
      if (callbacks_.on_media)
        callbacks_.on_media(header, payload, size);
      break;
    case static_cast<uint8_t>(MessageType::Stats): {
      uint64_t sent = 0, dropped = 0;
      if (ParseStats(payload, size, sent, dropped) and callbacks_.on_stats)
        callbacks_.on_stats(sent, dropped);
      break;
    }
    case static_cast<uint8_t>(MessageType::Bye):
      Debug(1, "StreamSocketClient: BYE on %s", path_.c_str());
      if (callbacks_.on_bye)
        callbacks_.on_bye();
      break;
    default:
      // Unknown message type: skip for forward compatibility
      Debug(2, "StreamSocketClient: skipping unknown message type 0x%02x on %s",
            header.type, path_.c_str());
      break;
  }
}
