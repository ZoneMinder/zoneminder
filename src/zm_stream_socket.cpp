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

#include "zm_stream_socket.h"

#include "zm_config.h"
#include "zm_logger.h"
#include "zm_utils.h"

#include <algorithm>
#include <cinttypes>
#include <cstring>
#include <grp.h>
#include <poll.h>
#include <sys/socket.h>
#include <sys/stat.h>
#include <sys/uio.h>
#include <unistd.h>

using namespace zm::stream_socket;

StreamSocket::StreamSocket(unsigned int monitor_id, std::string path) :
  StreamSocket(monitor_id, std::move(path), Config()) {
}

StreamSocket::StreamSocket(unsigned int monitor_id, std::string path, Config config) :
  monitor_id_(monitor_id),
  path_(std::move(path)),
  config_(std::move(config)) {
}

StreamSocket::~StreamSocket() {
  Stop();
}

bool StreamSocket::Start() {
  if (thread_.joinable())
    return true;

  ::unlink(path_.c_str());
  if (!listener_.bind(path_.c_str())) {
    Error("StreamSocket: failed to bind %s", path_.c_str());
    return false;
  }
  ApplyPermissions();
  if (!listener_.listen()) {
    Error("StreamSocket: failed to listen on %s", path_.c_str());
    return false;
  }
  listener_.setBlocking(false);

  if (!wake_.open()) {
    Error("StreamSocket: failed to open wake pipe");
    listener_.close();
    ::unlink(path_.c_str());
    return false;
  }
  wake_.setBlocking(false);

  terminate_ = false;
  thread_ = std::thread(&StreamSocket::Run, this);
  Debug(1, "StreamSocket: monitor %u listening on %s", monitor_id_, path_.c_str());
  return true;
}

void StreamSocket::Stop() {
  if (thread_.joinable()) {
    terminate_ = true;
    Wake();
    thread_.join();
  }
  if (listener_.isOpen()) {
    listener_.close();
    ::unlink(path_.c_str());
  }
  wake_.close();
}

void StreamSocket::ApplyPermissions() {
  if (chmod(path_.c_str(), 0660) < 0)
    Warning("StreamSocket: chmod 0660 %s failed: %s", path_.c_str(), strerror(errno));

  if (config_.group.empty())
    return;

  long size = sysconf(_SC_GETGR_R_SIZE_MAX);
  std::vector<char> buffer(size > 0 ? size : 1024);
  group grp = {};
  group *result = nullptr;
  if (getgrnam_r(config_.group.c_str(), &grp, buffer.data(), buffer.size(), &result) != 0
      or !result) {
    Warning("StreamSocket: group %s not found, leaving default group on %s",
            config_.group.c_str(), path_.c_str());
    return;
  }
  if (chown(path_.c_str(), static_cast<uid_t>(-1), grp.gr_gid) < 0)
    Warning("StreamSocket: chown group %s on %s failed: %s",
            config_.group.c_str(), path_.c_str(), strerror(errno));
}

StreamSocket::Config StreamSocket::ConfigFromStatic() {
  Config config;
  config.group = staticConfig.STREAM_SOCKET_GROUP;
  config.allowed_uids = ParseAllowedUids(staticConfig.STREAM_SOCKET_ALLOWED_UIDS);
  if (staticConfig.STREAM_SOCKET_MAX_CLIENTS > 0)
    config.max_clients = staticConfig.STREAM_SOCKET_MAX_CLIENTS;
  if (staticConfig.STREAM_SOCKET_QUEUE_BYTES > 0)
    config.queue_max_bytes = staticConfig.STREAM_SOCKET_QUEUE_BYTES;
  if (staticConfig.STREAM_SOCKET_QUEUE_MSGS > 0)
    config.queue_max_msgs = staticConfig.STREAM_SOCKET_QUEUE_MSGS;
  if (staticConfig.STREAM_SOCKET_STALL_SECS > 0)
    config.stall_timeout = std::chrono::seconds(staticConfig.STREAM_SOCKET_STALL_SECS);
  return config;
}

std::vector<uid_t> StreamSocket::ParseAllowedUids(const std::string &value) {
  std::vector<uid_t> uids;
  for (const std::string &token : Split(value, ',')) {
    std::string trimmed = Trim(token, " \t");
    if (trimmed.empty())
      continue;
    char *end = nullptr;
    unsigned long uid = strtoul(trimmed.c_str(), &end, 10);
    if (end and *end == '\0') {
      uids.push_back(static_cast<uid_t>(uid));
    } else {
      Warning("StreamSocket: ignoring malformed uid '%s' in allowed uids", trimmed.c_str());
    }
  }
  return uids;
}

bool StreamSocket::CheckPeer(int fd, uid_t &uid, pid_t &pid) const {
#ifdef SO_PEERCRED
  ucred cred = {};
  socklen_t len = sizeof(cred);
  if (getsockopt(fd, SOL_SOCKET, SO_PEERCRED, &cred, &len) != 0) {
    Warning("StreamSocket: SO_PEERCRED failed on %s: %s", path_.c_str(), strerror(errno));
    return config_.allowed_uids.empty();
  }
  uid = cred.uid;
  pid = cred.pid;
  if (config_.allowed_uids.empty() or uid == geteuid())
    return true;
  return std::find(config_.allowed_uids.begin(), config_.allowed_uids.end(), uid)
         != config_.allowed_uids.end();
#else
  return true;
#endif
}

void StreamSocket::SetVideoParams(const AVCodecParameters *par, AVRational frame_rate) {
  std::vector<uint8_t> payload = BuildHello(par, frame_rate);
  {
    std::lock_guard<std::mutex> lock(mutex_);
    if (payload == hello_video_payload_)
      return;
    bool reconfigure = !hello_video_payload_.empty();
    if (reconfigure) {
      ++generation_;
      sequence_[0] = sequence_[1] = 0;
      keyframe_.reset();
      Info("StreamSocket: monitor %u video parameters changed, generation now %u",
           monitor_id_, generation_);
    }
    hello_video_payload_ = payload;
    hello_video_ = MakeMessage(MessageType::Hello, StreamId::Video, 0, 0, 0,
                               std::move(payload), true);
    BroadcastLocked(hello_video_);
    if (reconfigure and !hello_audio_payload_.empty()) {
      // Re-issue the audio HELLO so both streams carry the new generation
      hello_audio_ = MakeMessage(MessageType::Hello, StreamId::Audio, 0, 0, 0,
                                 std::vector<uint8_t>(hello_audio_payload_), true);
      BroadcastLocked(hello_audio_);
    }
  }
  Wake();
}

void StreamSocket::SetAudioParams(const AVCodecParameters *par) {
  std::vector<uint8_t> payload = BuildHello(par, {0, 0});
  {
    std::lock_guard<std::mutex> lock(mutex_);
    if (payload == hello_audio_payload_)
      return;
    bool reconfigure = !hello_audio_payload_.empty();
    if (reconfigure) {
      ++generation_;
      sequence_[0] = sequence_[1] = 0;
      keyframe_.reset();
      Info("StreamSocket: monitor %u audio parameters changed, generation now %u",
           monitor_id_, generation_);
    }
    hello_audio_payload_ = payload;
    hello_audio_ = MakeMessage(MessageType::Hello, StreamId::Audio, 0, 0, 0,
                               std::move(payload), true);
    BroadcastLocked(hello_audio_);
    if (reconfigure and !hello_video_payload_.empty()) {
      hello_video_ = MakeMessage(MessageType::Hello, StreamId::Video, 0, 0, 0,
                                 std::vector<uint8_t>(hello_video_payload_), true);
      BroadcastLocked(hello_video_);
    }
  }
  Wake();
}

void StreamSocket::SendMedia(const AVPacket *packet, StreamId stream,
                             bool keyframe, int64_t pts_us) {
  if (!packet or packet->size <= 0)
    return;

  bool video_keyframe = keyframe and stream == StreamId::Video;

  std::unique_lock<std::mutex> lock(mutex_);
  uint32_t sequence = sequence_[static_cast<size_t>(stream)]++;
  bool have_clients = !clients_.empty();

  // With no consumers the message only counts against the sequence; skip the
  // packet reference and fan-out entirely. Keyframes are still cached so the
  // first consumer to connect gets an immediate first frame.
  if (!have_clients and !video_keyframe)
    return;

  Header header = {};
  header.length = kHeaderLengthBytes + packet->size;
  header.version = kProtocolVersion;
  header.type = static_cast<uint8_t>(MessageType::Media);
  header.stream = static_cast<uint8_t>(stream);
  header.flags = video_keyframe ? kFlagKeyframe : 0;
  header.sequence = sequence;
  header.generation = generation_;
  header.pts_us = static_cast<uint64_t>(pts_us);

  if (video_keyframe) {
    // Cache the keyframe for fast-start of late joiners; the cache references
    // the packet's buffer, no copy is made.
    av_packet_ptr cache_clone{av_packet_clone(packet)};
    if (cache_clone) {
      auto cache = std::make_shared<Message>();
      Header cache_header = header;
      cache_header.type = static_cast<uint8_t>(MessageType::Keyframe);
      SerializeHeader(cache_header, cache->header.data());
      cache->av_payload = std::move(cache_clone);
      keyframe_ = std::move(cache);
    }
  }

  if (!have_clients)
    return;

  // Reference the payload buffer; no copy of the media data is made.
  av_packet_ptr clone{av_packet_clone(packet)};
  if (!clone) {
    Error("StreamSocket: av_packet_clone failed for monitor %u", monitor_id_);
    return;
  }
  auto message = std::make_shared<Message>();
  SerializeHeader(header, message->header.data());
  message->av_payload = std::move(clone);
  BroadcastLocked(message);
  lock.unlock();
  Wake();
}

StreamSocket::MessagePtr StreamSocket::MakeMessage(
    MessageType type, StreamId stream, uint8_t flags, uint32_t sequence,
    int64_t pts_us, std::vector<uint8_t> payload, bool control) const {
  auto message = std::make_shared<Message>();
  Header header = {};
  header.length = kHeaderLengthBytes + payload.size();
  header.version = kProtocolVersion;
  header.type = static_cast<uint8_t>(type);
  header.stream = static_cast<uint8_t>(stream);
  header.flags = flags;
  header.sequence = sequence;
  header.generation = generation_;
  header.pts_us = static_cast<uint64_t>(pts_us);
  SerializeHeader(header, message->header.data());
  message->blob_payload = std::move(payload);
  message->control = control;
  return message;
}

void StreamSocket::BroadcastLocked(const MessagePtr &message) {
  for (std::unique_ptr<Client> &client : clients_)
    EnqueueLocked(*client, message);
}

void StreamSocket::EnqueueLocked(Client &client, const MessagePtr &message) {
  while (!client.queue.empty()
         and (client.queued_bytes + message->size() > config_.queue_max_bytes
              or client.queue.size() + 1 > config_.queue_max_msgs)) {
    // Drop the oldest non-control message. The front message is unsafe to
    // drop once partially written - removing it would desync the framing.
    auto begin = client.queue.begin() + (client.front_offset > 0 ? 1 : 0);
    auto victim = std::find_if(begin, client.queue.end(),
                               [](const MessagePtr &m) { return !m->control; });
    if (victim == client.queue.end())
      break;
    client.queued_bytes -= (*victim)->size();
    client.queue.erase(victim);
    ++client.dropped;
  }

  if (client.queued_bytes + message->size() > config_.queue_max_bytes
      or client.queue.size() + 1 > config_.queue_max_msgs) {
    if (!message->control) {
      // Nothing droppable left; drop the incoming message instead.
      ++client.dropped;
      return;
    }
    // Control messages (HELLO/BYE) always go through, even above the limit.
  }

  client.queue.push_back(message);
  client.queued_bytes += message->size();
}

bool StreamSocket::DrainClientLocked(Client &client) {
  while (!client.queue.empty()) {
    const Message &message = *client.queue.front();
    size_t offset = client.front_offset;

    iovec iov[2];
    int iovcnt = 0;
    if (offset < kHeaderSize) {
      iov[iovcnt].iov_base = const_cast<uint8_t *>(message.header.data()) + offset;
      iov[iovcnt].iov_len = kHeaderSize - offset;
      ++iovcnt;
      if (message.payload_size() > 0) {
        iov[iovcnt].iov_base = const_cast<uint8_t *>(message.payload_data());
        iov[iovcnt].iov_len = message.payload_size();
        ++iovcnt;
      }
    } else {
      size_t payload_offset = offset - kHeaderSize;
      iov[iovcnt].iov_base = const_cast<uint8_t *>(message.payload_data()) + payload_offset;
      iov[iovcnt].iov_len = message.payload_size() - payload_offset;
      ++iovcnt;
    }

    ssize_t written = ::writev(client.sock->getDesc(), iov, iovcnt);
    if (written < 0) {
      if (errno == EAGAIN or errno == EWOULDBLOCK)
        return true;
      if (errno == EINTR)
        continue;
      Debug(1, "StreamSocket: writev to client pid %d failed: %s",
            client.pid, strerror(errno));
      return false;
    }

    client.front_offset += written;
    client.last_progress = std::chrono::steady_clock::now();
    if (client.front_offset >= message.size()) {
      client.queued_bytes -= message.size();
      client.queue.pop_front();
      client.front_offset = 0;
      ++client.sent;
    }
  }
  return true;
}

void StreamSocket::SendStatsLocked(Client &client, TimePoint now) {
  if (now - client.last_stats < config_.stats_interval)
    return;
  client.last_stats = now;
  MessagePtr stats = MakeMessage(MessageType::Stats, StreamId::Video, 0, 0, 0,
                                 BuildStats(client.sent, client.dropped), false);
  EnqueueLocked(client, stats);
}

void StreamSocket::AcceptClient() {
  while (true) {
    int fd = ::accept(listener_.getDesc(), nullptr, nullptr);
    if (fd < 0) {
      if (errno != EAGAIN and errno != EWOULDBLOCK and errno != EINTR)
        Warning("StreamSocket: accept on %s failed: %s", path_.c_str(), strerror(errno));
      return;
    }

    auto sock = std::make_unique<zm::TcpUnixSocket>(listener_, fd);

    uid_t uid = 0;
    pid_t pid = 0;
    if (!CheckPeer(fd, uid, pid)) {
      Warning("StreamSocket: rejecting client uid %u pid %d on %s (not in allowed uids)",
              uid, pid, path_.c_str());
      continue;  // sock destructor closes the connection
    }

    std::lock_guard<std::mutex> lock(mutex_);
    if (clients_.size() >= config_.max_clients) {
      Warning("StreamSocket: rejecting client uid %u pid %d on %s (%zu clients connected)",
              uid, pid, path_.c_str(), clients_.size());
      continue;
    }

    sock->setBlocking(false);
    auto client = std::make_unique<Client>();
    client->sock = std::move(sock);
    client->uid = uid;
    client->pid = pid;
    client->last_progress = std::chrono::steady_clock::now();
    client->last_stats = client->last_progress;

    // New consumers get the stream parameters first, then the cached keyframe
    // so they can render immediately instead of waiting for the next GOP.
    if (hello_video_)
      EnqueueLocked(*client, hello_video_);
    if (hello_audio_)
      EnqueueLocked(*client, hello_audio_);
    if (keyframe_)
      EnqueueLocked(*client, keyframe_);

    Info("StreamSocket: monitor %u client connected uid %u pid %d (%zu clients)",
         monitor_id_, uid, pid, clients_.size() + 1);
    clients_.push_back(std::move(client));
  }
}

void StreamSocket::Wake() {
  if (wake_.isOpen()) {
    uint8_t byte = 0;
    if (::write(wake_.getWriteDesc(), &byte, 1) < 0 and errno != EAGAIN) {
      Debug(1, "StreamSocket: wake write failed: %s", strerror(errno));
    }
  }
}

void StreamSocket::Run() {
  std::vector<pollfd> fds;

  while (!terminate_) {
    fds.clear();
    fds.push_back({listener_.getDesc(), POLLIN, 0});
    fds.push_back({wake_.getReadDesc(), POLLIN, 0});
    {
      std::lock_guard<std::mutex> lock(mutex_);
      for (std::unique_ptr<Client> &client : clients_) {
        short events = POLLIN;
        if (!client->queue.empty())
          events |= POLLOUT;
        fds.push_back({client->sock->getDesc(), events, 0});
      }
    }

    // With no clients there is nothing to drain or report; sleep until a
    // connection or a wake. Idle monitors cost no periodic wakeups.
    int timeout = fds.size() > 2 ? 100 : -1;
    int ready = ::poll(fds.data(), fds.size(), timeout);
    if (ready < 0) {
      if (errno == EINTR)
        continue;
      Error("StreamSocket: poll failed on %s: %s", path_.c_str(), strerror(errno));
      break;
    }
    if (terminate_)
      break;

    if (fds[1].revents & POLLIN) {
      uint8_t buffer[64];
      while (::read(wake_.getReadDesc(), buffer, sizeof(buffer)) > 0) {}
    }

    if (fds[0].revents & POLLIN)
      AcceptClient();

    TimePoint now = std::chrono::steady_clock::now();
    {
      std::lock_guard<std::mutex> lock(mutex_);
      size_t fd_index = 2;
      for (auto it = clients_.begin(); it != clients_.end();) {
        Client &client = **it;
        short revents = 0;
        if (fd_index < fds.size() and fds[fd_index].fd == client.sock->getDesc()) {
          revents = fds[fd_index].revents;
          ++fd_index;
        }

        bool remove = false;
        if (revents & (POLLERR | POLLHUP | POLLNVAL))
          remove = true;

        if (!remove and (revents & POLLIN)) {
          // Protocol v1 has no client->server messages; discard inbound bytes,
          // a zero read means the peer closed.
          uint8_t buffer[256];
          ssize_t bytes = ::recv(client.sock->getDesc(), buffer, sizeof(buffer), 0);
          if (bytes == 0)
            remove = true;
        }

        if (!remove and !client.queue.empty())
          remove = !DrainClientLocked(client);

        if (!remove)
          SendStatsLocked(client, now);

        if (!remove and client.queued_bytes > 0
            and now - client.last_progress > config_.stall_timeout) {
          Warning("StreamSocket: monitor %u disconnecting stalled client uid %u pid %d"
                  " (%zu bytes queued, %" PRIu64 " dropped)",
                  monitor_id_, client.uid, client.pid, client.queued_bytes, client.dropped);
          remove = true;
        }

        if (remove) {
          Info("StreamSocket: monitor %u client uid %u pid %d disconnected"
               " (sent %" PRIu64 ", dropped %" PRIu64 ")",
               monitor_id_, client.uid, client.pid, client.sent, client.dropped);
          it = clients_.erase(it);
        } else {
          ++it;
        }
      }
    }
  }

  // Best-effort BYE so consumers can tell shutdown from failure
  std::lock_guard<std::mutex> lock(mutex_);
  MessagePtr bye = MakeMessage(MessageType::Bye, StreamId::Video, 0, 0, 0, {}, true);
  for (std::unique_ptr<Client> &client : clients_) {
    EnqueueLocked(*client, bye);
    DrainClientLocked(*client);
  }
  clients_.clear();
}
