#include "zm_websocket.h"

#include "zm_crypt.h"
#include "zm_monitor.h"
#include "zm_user.h"
#include "zm_utils.h"

#include <algorithm>
#include <array>
#include <cerrno>
#include <cinttypes>
#include <climits>
#include <cstdint>
#include <cstring>
#include <arpa/inet.h>
#include <fcntl.h>
#include <netdb.h>
#include <netinet/in.h>
#include <sstream>
#include <poll.h>
#include <sys/socket.h>
#include <unistd.h>

namespace {

static constexpr const char *kWebSocketMagic = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
static constexpr size_t kMaxHandshakeSize = 16384;
static constexpr size_t kMaxMessageSize = 1024 * 1024;
static constexpr size_t kMaxQueuedBytesPerClient = 8 * 1024 * 1024;
static constexpr size_t kMaxClientsPerMonitor = 32;
static constexpr int kPollTimeoutMs = 100;
// Shortest gap between change-driven status pushes to one client, so rapid
// state flapping cannot flood it. A client's keepalive interval shorter than
// this caps the floor instead (refs #2875).
static constexpr Milliseconds kStatusCoalesceFloor = Milliseconds(100);
// A peer that opens a socket and then says nothing would otherwise hold its slot
// against kMaxClientsPerMonitor forever, so 32 idle connections could deny the
// service to everyone. Generous enough for a slow TLS handshake over a bad link.
static constexpr Seconds kHandshakeTimeout = Seconds(30);

bool setNonBlocking(int fd) {
  int flags = fcntl(fd, F_GETFL, 0);
  if (flags < 0) {
    Error("fcntl(F_GETFL) failed for websocket socket %d: %s", fd, strerror(errno));
    return false;
  }

  if (fcntl(fd, F_SETFL, flags | O_NONBLOCK) < 0) {
    Error("fcntl(F_SETFL) failed for websocket socket %d: %s", fd, strerror(errno));
    return false;
  }

  return true;
}
bool writeFully(int fd, const std::string &payload) {
  size_t offset = 0;
  while (offset < payload.size()) {
    const ssize_t bytes_sent = ::send(fd, payload.data() + offset, payload.size() - offset, MSG_NOSIGNAL);
    if (bytes_sent < 0) {
      if (errno == EINTR) {
        continue;
      }
      if ((errno == EAGAIN) || (errno == EWOULDBLOCK)) {
        // Socket is non-blocking; wait briefly for it to drain. This path is
        // only used for short error/close responses on a connection that is
        // about to be closed, so a bounded wait is acceptable.
        struct pollfd pfd = {fd, POLLOUT, 0};
        if (poll(&pfd, 1, 200) <= 0) {
          return false;
        }
        continue;
      }
      return false;
    }
    if (bytes_sent == 0) {
      return false;
    }
    offset += static_cast<size_t>(bytes_sent);
  }
  return true;
}

std::string statusAckJson(const std::string &topic, int interval_ms) {
  return stringtf(
      "{\"type\":\"ack\",\"topic\":\"%s\",\"interval_ms\":%d}",
      escape_json_string(topic).c_str(),
      interval_ms);
}

std::string metadataJson(unsigned int monitor_id, const Monitor::WebSocketPayload &payload, const std::string &request_id) {
  return stringtf(
      "{\"type\":\"%s\",\"request_id\":\"%s\",\"format\":\"%s\",\"content_type\":\"%s\","
      "\"monitor_id\":%u,\"width\":%u,\"height\":%u,\"line_size\":%u,\"colours\":%u,\"subpixel_order\":%u,"
      "\"image_count\":%u,\"sequence\":%" PRIu64 ",\"keyframe\":%s,\"payload_bytes\":%zu}",
      escape_json_string(payload.type).c_str(),
      escape_json_string(request_id).c_str(),
      escape_json_string(payload.format).c_str(),
      escape_json_string(payload.content_type).c_str(),
      monitor_id,
      payload.width,
      payload.height,
      payload.line_size,
      payload.colours,
      payload.subpixel_order,
      payload.image_count,
      payload.sequence,
      payload.keyframe ? "true" : "false",
      payload.payload.size());
}

std::string errorJson(const std::string &message) {
  return stringtf(
      "{\"type\":\"error\",\"message\":\"%s\"}",
      escape_json_string(message).c_str());
}

std::string httpResponse(const char *status_line, const char *body = nullptr) {
  if (!body || !*body) {
    return std::string(status_line) + "\r\n\r\n";
  }

  return stringtf(
      "%s\r\nContent-Type: text/plain\r\nContent-Length: %zu\r\n\r\n%s",
      status_line,
      strlen(body),
      body);
}

bool validateStreamAccess(User *user, unsigned int monitor_id) {
  if (user->getStream() < User::PERM_VIEW) {
    Warning(
        "Insufficient websocket privileges for user %d %s on monitor %u: stream permission required",
        user->Id(),
        user->getUsername(),
        monitor_id);
    return false;
  }

  if (!user->canAccess(monitor_id)) {
    Warning(
        "Insufficient websocket privileges for user %d %s on monitor %u: monitor access denied",
        user->Id(),
        user->getUsername(),
        monitor_id);
    return false;
  }

  return true;
}

User *authenticateWebSocketRequest(const std::string &request, unsigned int monitor_id,
                                   const std::string &peer_addr, int *http_status) {
  *http_status = 401;
  if (!config.opt_use_auth) {
    return nullptr;
  }

  std::string target;
  if (!zm::websocket::ExtractHandshakeRequestTarget(request, &target)) {
    *http_status = 400;
    return nullptr;
  }

  User *user = nullptr;
  std::string token;
  if (zm::websocket::ExtractAuthorizationBearerToken(request, &token)) {
    user = zmLoadTokenUser(token, false);
  } else {
    const size_t query_pos = target.find('?');
    std::string query_string;
    if (query_pos != std::string::npos) {
      query_string = target.substr(query_pos + 1);
    }

    std::istringstream request_stream(query_string);
    QueryString query(request_stream);

    if (query.has("jwt_token")) {
      user = zmLoadTokenUser(query.get("jwt_token")->firstValue(), false);
    } else if (query.has("token")) {
      user = zmLoadTokenUser(query.get("token")->firstValue(), false);
    } else {
      // Deliberately no AUTH_RELAY=none shortcut here. zms and zmu accept a bare
      // username under that relay mode because the web server has already
      // authenticated the request before proxying to them. This listener is a
      // plain socket on its own port with nothing in front of it, so the
      // assumption that makes the shortcut safe does not hold, and honouring it
      // would let anyone who can reach the port name any account they like.
      if (query.has("auth")) {
        const std::string auth_hash = query.get("auth")->firstValue();
        const std::string username = query.has("username") ? query.get("username")->firstValue() : "";
        if (!auth_hash.empty()) {
          // zmc has no CGI REMOTE_ADDR, so pass the peer address accept() gave
          // us (and any X-Forwarded-For from the handshake, resolved against the
          // trusted proxies) for an IP-bound hash to validate against (refs #2875).
          std::string forwarded_for;
          ExtractHeaderValue(request, "x-forwarded-for", &forwarded_for);
          user = zmLoadAuthUser(auth_hash, username, config.auth_hash_ips, peer_addr, forwarded_for);
        }
      }

      if ((!user) && query.has("username") && query.has("password")) {
        const std::string username = query.get("username")->firstValue();
        const std::string password = query.get("password")->firstValue();
        // The password must be non-empty and actually verified. zmLoadUser
        // returns the user for an empty password whenever AUTH_RELAY is none,
        // which is the same relayed-trust assumption, so an empty one would be
        // a bypass rather than a failed login.
        if (checkUser(username) && !password.empty()) {
          user = zmLoadUser(username, password);
        }
      }
    }
  }

  if (!user) {
    return nullptr;
  }

  if (!validateStreamAccess(user, monitor_id)) {
    delete user;
    *http_status = 403;
    return nullptr;
  }

  *http_status = 101;
  return user;
}

}  // namespace

namespace zm {
namespace websocket {

std::string ComputeAcceptKey(const std::string &client_key) {
  const std::string input = client_key + kWebSocketMagic;
  const zm::crypto::SHA1::Digest digest = zm::crypto::SHA1::GetDigestOf(input);
  return Base64Encode(nonstd::span<const uint8>(digest.data(), digest.size()));
}

bool ExtractHandshakeKey(const std::string &request, std::string *client_key) {
  std::string upgrade_value;
  std::string connection_value;
  std::string version_value;
  if (!ExtractHeaderValue(request, "sec-websocket-key", client_key)) {
    return false;
  }
  if (!ExtractHeaderValue(request, "upgrade", &upgrade_value) ||
      (StringToLower(upgrade_value) != "websocket")) {
    return false;
  }
  if (!ExtractHeaderValue(request, "connection", &connection_value) ||
      !HeaderContainsToken(connection_value, "upgrade")) {
    return false;
  }
  if (!ExtractHeaderValue(request, "sec-websocket-version", &version_value) ||
      (version_value != "13")) {
    return false;
  }
  return !client_key->empty();
}

bool ExtractHandshakeRequestTarget(const std::string &request, std::string *target) {
  const size_t line_end = request.find("\r\n");
  const std::string request_line = request.substr(0, line_end);
  std::istringstream line_stream(request_line);
  std::string method;
  std::string version;
  if (!(line_stream >> method >> *target >> version)) {
    return false;
  }

  return (method == "GET") && StartsWith(version, "HTTP/");
}

bool ExtractAuthorizationBearerToken(const std::string &request, std::string *token) {
  std::string authorization;
  if (!ExtractHeaderValue(request, "authorization", &authorization)) {
    return false;
  }

  const std::string prefix = "bearer ";
  std::string lower = StringToLower(authorization);
  if (!StartsWith(lower, prefix)) {
    return false;
  }

  *token = Trim(authorization.substr(prefix.length()), " \t");
  return !token->empty();
}

std::string BuildHandshakeResponse(const std::string &client_key) {
  return
      "HTTP/1.1 101 Switching Protocols\r\n"
      "Upgrade: websocket\r\n"
      "Connection: Upgrade\r\n"
      "Sec-WebSocket-Accept: " + ComputeAcceptKey(client_key) + "\r\n"
      "\r\n";
}

std::string EncodeFrame(Opcode opcode, const std::string &payload, bool fin) {
  std::string frame;
  frame.reserve(payload.size() + 16);
  frame.push_back(static_cast<char>((fin ? 0x80 : 0x00) | static_cast<uint8_t>(opcode)));

  const uint64_t payload_size = payload.size();
  if (payload_size < 126) {
    frame.push_back(static_cast<char>(payload_size));
  } else if (payload_size <= 0xffff) {
    frame.push_back(126);
    frame.push_back(static_cast<char>((payload_size >> 8) & 0xff));
    frame.push_back(static_cast<char>(payload_size & 0xff));
  } else {
    frame.push_back(127);
    for (int shift = 56; shift >= 0; shift -= 8) {
      frame.push_back(static_cast<char>((payload_size >> shift) & 0xff));
    }
  }

  frame.append(payload);
  return frame;
}

DecodeResult DecodeFrame(const std::string &buffer, Frame *frame, size_t *consumed) {
  if (buffer.size() < 2) {
    return DecodeResult::INCOMPLETE;
  }

  const uint8_t first = static_cast<uint8_t>(buffer[0]);
  const uint8_t second = static_cast<uint8_t>(buffer[1]);
  const uint8_t opcode = first & 0x0f;
  const bool masked = (second & 0x80) != 0;
  uint64_t payload_len = (second & 0x7f);
  size_t pos = 2;

  if (payload_len == 126) {
    if (buffer.size() < pos + 2) {
      return DecodeResult::INCOMPLETE;
    }
    payload_len = (static_cast<uint8_t>(buffer[pos]) << 8) | static_cast<uint8_t>(buffer[pos + 1]);
    pos += 2;
  } else if (payload_len == 127) {
    if (buffer.size() < pos + 8) {
      return DecodeResult::INCOMPLETE;
    }
    payload_len = 0;
    for (size_t i = 0; i < 8; ++i) {
      payload_len = (payload_len << 8) | static_cast<uint8_t>(buffer[pos + i]);
    }
    pos += 8;
  }

  if (payload_len > kMaxMessageSize) {
      Error("Websocket payload too large: %" PRIu64, payload_len);
    return DecodeResult::ERROR;
  }

  if ((opcode & 0x08) != 0) {
    if ((first & 0x80) == 0) {
      Warning("Rejecting fragmented websocket control frame");
      return DecodeResult::ERROR;
    }
    if (payload_len > 125) {
      Warning("Rejecting oversized websocket control frame payload: %" PRIu64, payload_len);
      return DecodeResult::ERROR;
    }
  }

  if (!masked) {
    Warning("Rejecting unmasked websocket client frame");
    return DecodeResult::ERROR;
  }

  std::array<uint8_t, 4> mask = {0, 0, 0, 0};
  if (buffer.size() < pos + mask.size()) {
    return DecodeResult::INCOMPLETE;
  }
  for (size_t i = 0; i < mask.size(); ++i) {
    mask[i] = static_cast<uint8_t>(buffer[pos + i]);
  }
  pos += mask.size();

  if (buffer.size() < pos + payload_len) {
    return DecodeResult::INCOMPLETE;
  }

  frame->fin = (first & 0x80) != 0;
  frame->masked = masked;
  frame->opcode = static_cast<Opcode>(opcode);
  frame->payload.assign(buffer.data() + pos, buffer.data() + pos + payload_len);

  if (masked) {
    for (size_t i = 0; i < frame->payload.size(); ++i) {
      frame->payload[i] ^= mask[i % mask.size()];
    }
  }

  *consumed = pos + payload_len;
  return DecodeResult::OK;
}

unsigned int MonitorWebSocketPort(int base_port, unsigned int monitor_id) {
  if (base_port <= 0) {
    return 0;
  }
  if (monitor_id > static_cast<unsigned int>(INT_MAX - base_port)) {
    return 0;
  }
  return static_cast<unsigned int>(base_port) + monitor_id;
}

std::string PeerAddressString(const sockaddr_storage &addr, socklen_t addr_len) {
  if ((addr.ss_family != AF_INET) && (addr.ss_family != AF_INET6)) {
    return "";
  }

  char host[NI_MAXHOST] = {};
  const int rc = getnameinfo(reinterpret_cast<const sockaddr *>(&addr), addr_len,
                             host, sizeof(host), nullptr, 0, NI_NUMERICHOST);
  if (rc != 0) {
    return "";
  }

  std::string result(host);
  // getnameinfo keeps the scope id on a link-local address (fe80::1%eth0); the
  // hash was bound to the bare address, so drop it.
  const size_t scope = result.find('%');
  if (scope != std::string::npos) {
    result.erase(scope);
  }
  // Reduce an IPv4-mapped IPv6 address (::ffff:a.b.c.d) to its dotted form, so
  // it matches the REMOTE_ADDR the web server recorded for the same client.
  const std::string mapped_prefix = "::ffff:";
  if ((result.compare(0, mapped_prefix.size(), mapped_prefix) == 0) &&
      (result.find(':', mapped_prefix.size()) == std::string::npos) &&
      (result.find('.', mapped_prefix.size()) != std::string::npos)) {
    result.erase(0, mapped_prefix.size());
  }
  return result;
}

void VideoStreamInfo::Update(const AVCodecParameters *codecpar,
                             uint32_t fallback_width, uint32_t fallback_height) {
  Snapshot next;
  bool valid = false;
  if (codecpar && codecpar->codec_id != AV_CODEC_ID_NONE) {
    valid = true;
    next.codec_id = codecpar->codec_id;
    next.width = codecpar->width > 0 ? static_cast<uint32_t>(codecpar->width) : fallback_width;
    next.height = codecpar->height > 0 ? static_cast<uint32_t>(codecpar->height) : fallback_height;
    if (codecpar->extradata && codecpar->extradata_size > 0) {
      next.extradata.assign(reinterpret_cast<const char *>(codecpar->extradata),
                            codecpar->extradata_size);
    }
  }

  std::lock_guard<std::mutex> lock(mutex_);
  valid_ = valid;
  snapshot_ = std::move(next);
}

void VideoStreamInfo::Invalidate() {
  std::lock_guard<std::mutex> lock(mutex_);
  valid_ = false;
  snapshot_ = Snapshot();
}

bool VideoStreamInfo::Get(AVCodecID requested_codec, Snapshot *out) const {
  std::lock_guard<std::mutex> lock(mutex_);
  if (!valid_ || snapshot_.codec_id != requested_codec) {
    return false;
  }
  *out = snapshot_;
  return true;
}

bool VideoStreamInfo::Valid() const {
  std::lock_guard<std::mutex> lock(mutex_);
  return valid_;
}

}  // namespace websocket

MonitorWebSocketServer::MonitorWebSocketServer(Monitor *p_monitor) :
  monitor(p_monitor),
  port(0),
  running(false) {
}

MonitorWebSocketServer::~MonitorWebSocketServer() {
  Stop();
}

bool MonitorWebSocketServer::EnableTLS(const std::string &certificate_path, const std::string &key_path) {
  std::unique_ptr<zm::tls::ServerContext> context(new zm::tls::ServerContext());
  if (!context->Load(certificate_path, key_path)) {
    return false;
  }
  tls_context = std::move(context);
  return true;
}

bool MonitorWebSocketServer::Start(int p_port) {
  if (running) {
    return true;
  }

  port = p_port;
  if (!server.bind(port) || !server.listen() || !server.setBlocking(false)) {
    Error("Unable to start websocket server for monitor %u on port %d", monitor->Id(), port);
    server.close();
    return false;
  }

  running = true;
  server_thread = std::thread(&MonitorWebSocketServer::run, this);
  Info("Started websocket server for monitor %u on port %d", monitor->Id(), port);
  return true;
}

void MonitorWebSocketServer::Stop() {
  if (!running) {
    return;
  }

  running = false;
  server.close();

  if (server_thread.joinable()) {
    server_thread.join();
  }
}

void MonitorWebSocketServer::run() {
  std::vector<Client> clients;
  clients.reserve(8);

  while (running) {
    std::vector<pollfd> pollfds;
    pollfds.reserve(clients.size() + 1);
    pollfds.push_back({server.getReadDesc(), POLLIN, 0});

    for (const Client &client : clients) {
      // Always ready to read inbound frames and commands. Add POLLOUT only for
      // a direction that actually needs to write: an inbound TLS op waiting to
      // write, or a queued write that is not itself waiting for readability (if
      // it is, the always-on POLLIN wakes us to re-drive it - requesting POLLOUT
      // there would spin, since the socket stays writable).
      short events = POLLIN;
      if (client.tls_in_wants_write) {
        events |= POLLOUT;
      }
      if (!client.send_queue.empty() && !client.tls_out_wants_read) {
        events |= POLLOUT;
      }
      pollfds.push_back({client.fd, events, 0});
    }

    int poll_result = poll(pollfds.data(), pollfds.size(), kPollTimeoutMs);
    if (poll_result < 0) {
      if (errno == EINTR) {
        continue;
      }
      if (!running) {
        break;
      }
      Error("poll() failed in websocket server for monitor %u: %s", monitor->Id(), strerror(errno));
      break;
    }

    if (running && !pollfds.empty() && (pollfds[0].revents & POLLIN)) {
      acceptClients(&clients);
    }

    const size_t polled_client_count = pollfds.size() - 1;
    for (size_t i = 0; i < polled_client_count; ++i) {
      const short revents = pollfds[i + 1].revents;
      if (revents & (POLLERR | POLLHUP | POLLNVAL)) {
        closeClient(&clients[i]);
        continue;
      }
      Client &client = clients[i];
      // Drive the inbound side (handshake, then read) when readable, or when an
      // inbound TLS op that was waiting to write finally can: handleRead
      // re-attempts whichever operation stalled.
      if ((revents & POLLIN) || ((revents & POLLOUT) && client.tls_in_wants_write)) {
        if (!handleRead(&client)) {
          closeClient(&client);
          continue;
        }
      }
      // Drive the outbound queue when writable, or when a queued write that was
      // waiting to read finally can.
      if (!client.send_queue.empty() &&
          ((revents & POLLOUT) || ((revents & POLLIN) && client.tls_out_wants_read))) {
        if (!flushWrites(&client)) {
          closeClient(&client);
        }
      }
    }

    const TimePoint now = std::chrono::steady_clock::now();
    broadcastStatus(&clients, now);
    broadcastStreams(&clients, now);
    broadcastEvents(&clients);

    for (Client &client : clients) {
      if (client.fd >= 0 && !client.send_queue.empty()) {
        flushWrites(&client);
      }
    }

    dropStalledHandshakes(&clients, now);
    removeClosedClients(&clients);
  }

  for (Client &client : clients) {
    if (client.fd >= 0) {
      closeClient(&client);
    }
  }
}

bool MonitorWebSocketServer::acceptClients(std::vector<Client> *clients) {
  while (running) {
    sockaddr_storage addr = {};
    socklen_t addr_len = sizeof(addr);
    const int fd = ::accept(server.getReadDesc(), reinterpret_cast<sockaddr *>(&addr), &addr_len);
    if (fd < 0) {
      if ((errno == EAGAIN) || (errno == EWOULDBLOCK)) {
        return true;
      }
      Error("accept() failed in websocket server for monitor %u: %s", monitor->Id(), strerror(errno));
      return false;
    }

    if (clients->size() >= kMaxClientsPerMonitor) {
      Warning(
          "Rejecting websocket connection for monitor %u: client limit %zu reached",
          monitor->Id(),
          kMaxClientsPerMonitor);
      // With TLS there is no session yet to answer through, and a plaintext
      // reply to a client expecting a ServerHello is worse than silence.
      if (!tls_context) {
        writeFully(fd, httpResponse("HTTP/1.1 503 Service Unavailable", "Too many websocket clients"));
      }
      ::close(fd);
      continue;
    }

    if (!setNonBlocking(fd)) {
      ::close(fd);
      continue;
    }

    clients->emplace_back(fd);
    clients->back().peer_addr = websocket::PeerAddressString(addr, addr_len);
    if (tls_context) {
      clients->back().tls.reset(new zm::tls::Session(*tls_context, fd));
    }
  }

  return true;
}

bool MonitorWebSocketServer::handleTlsHandshake(Client *client, bool *done) {
  *done = false;
  const zm::tls::Result result = client->tls->Handshake();
  switch (result) {
  case zm::tls::Result::kOk:
    client->tls_in_wants_write = false;
    *done = true;
    return true;
  case zm::tls::Result::kWantRead:
    client->tls_in_wants_write = false;
    return true;
  case zm::tls::Result::kWantWrite:
    client->tls_in_wants_write = true;
    return true;
  default:
    Debug(1, "Dropping websocket client for monitor %u: TLS handshake failed", monitor->Id());
    return false;
  }
}

bool MonitorWebSocketServer::readAvailable(Client *client, bool *closed) {
  char buffer[4096];
  *closed = false;

  while (true) {
    size_t bytes_read = 0;

    if (client->tls) {
      const zm::tls::Result result = client->tls->Read(buffer, sizeof(buffer), &bytes_read);
      if (result == zm::tls::Result::kWantRead) {
        client->tls_in_wants_write = false;
        break;
      }
      if (result == zm::tls::Result::kWantWrite) {
        // A renegotiation can make a read need the socket to be writable.
        client->tls_in_wants_write = true;
        break;
      }
      if (result == zm::tls::Result::kClosed) {
        *closed = true;
        return true;
      }
      if (result != zm::tls::Result::kOk) {
        return false;
      }
      client->tls_in_wants_write = false;
    } else {
      const ssize_t raw = ::recv(client->fd, buffer, sizeof(buffer), 0);
      if (raw == 0) {
        *closed = true;
        return true;
      }
      if (raw < 0) {
        if ((errno == EAGAIN) || (errno == EWOULDBLOCK)) {
          break;
        }
        Error("recv() failed in websocket server for monitor %u: %s", monitor->Id(), strerror(errno));
        return false;
      }
      bytes_read = static_cast<size_t>(raw);
    }

    if (bytes_read == 0) {
      break;
    }

    client->recv_buffer.append(buffer, bytes_read);
    if (client->recv_buffer.size() > kMaxMessageSize) {
      Warning("Closing websocket client for monitor %u due to oversized message", monitor->Id());
      return false;
    }
  }

  return true;
}

bool MonitorWebSocketServer::handleRead(Client *client) {
  // TLS has to be established before there is any HTTP to look at.
  if (client->tls && !client->tls->HandshakeComplete()) {
    bool done = false;
    if (!handleTlsHandshake(client, &done)) {
      return false;
    }
    if (!done) {
      return true;
    }
  }

  bool closed = false;
  if (!readAvailable(client, &closed)) {
    return false;
  }
  if (closed) {
    return false;
  }

  if (!client->handshake_complete) {
    if (!handleHandshake(client)) {
      return false;
    }
    // handleHandshake keeps any bytes the client pipelined after the request.
    // If the handshake is now complete, fall through to decode those frames in
    // this same call; a client that sent its first frame in the same segment
    // and then waits for our reply would otherwise stall until more data
    // arrives (which it will not).
    if (!client->handshake_complete) {
      return true;
    }
  }

  while (!client->recv_buffer.empty()) {
    websocket::Frame frame;
    size_t consumed = 0;
    const websocket::DecodeResult result = websocket::DecodeFrame(client->recv_buffer, &frame, &consumed);
    if (result == websocket::DecodeResult::INCOMPLETE) {
      break;
    }
    if (result == websocket::DecodeResult::ERROR) {
      return false;
    }

    client->recv_buffer.erase(0, consumed);
    if (!handleFrame(client, frame)) {
      return false;
    }
  }

  return true;
}

bool MonitorWebSocketServer::handleHandshake(Client *client) {
  if (client->recv_buffer.size() > kMaxHandshakeSize) {
    return false;
  }

  const size_t request_end = client->recv_buffer.find("\r\n\r\n");
  if (request_end == std::string::npos) {
    return true;
  }

  const std::string request = client->recv_buffer.substr(0, request_end + 4);
  client->recv_buffer.erase(0, request_end + 4);

  std::string client_key;
  if (!websocket::ExtractHandshakeKey(request, &client_key)) {
    writeImmediate(client, httpResponse("HTTP/1.1 400 Bad Request", "Bad websocket handshake"));
    return false;
  }

  if (config.opt_use_auth) {
    int http_status = 401;
    User *user = authenticateWebSocketRequest(request, monitor->Id(), client->peer_addr, &http_status);
    if (!user) {
      if (http_status == 403) {
        writeImmediate(client, httpResponse("HTTP/1.1 403 Forbidden", "Forbidden"));
      } else if (http_status == 400) {
        writeImmediate(client, httpResponse("HTTP/1.1 400 Bad Request", "Malformed HTTP request line"));
      } else {
        writeImmediate(client, httpResponse("HTTP/1.1 401 Unauthorized", "Authentication required"));
      }
      return false;
    }
    delete user;
  }

  queueRaw(client, websocket::BuildHandshakeResponse(client_key));
  client->handshake_complete = true;
  client->next_status_at = std::chrono::steady_clock::now();
  client->next_stream_at = std::chrono::steady_clock::now();
  return true;
}

bool MonitorWebSocketServer::handleFrame(Client *client, const websocket::Frame &frame) {
  if (!frame.fin || (frame.opcode == websocket::Opcode::CONTINUATION)) {
    Warning("Rejecting fragmented websocket frame for monitor %u", monitor->Id());
    return false;
  }

  switch (frame.opcode) {
  case websocket::Opcode::TEXT: {
    std::string command;
    std::string request_id;
    JsonExtractQuotedField(frame.payload, "request_id", &request_id);
    if (!JsonExtractQuotedField(frame.payload, "command", &command)) {
      if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Missing command"))) {
        return false;
      }
      return true;
    }

    if (command == "status") {
      if (!queueFrame(client, websocket::Opcode::TEXT, monitor->GetWebSocketStatusJson())) {
        return false;
      }
      return true;
    }

    if (command == "image") {
      std::string format;
      if (!JsonExtractQuotedField(frame.payload, "format", &format)) {
        format = "jpeg";
      }
      monitor->setLastViewed();
      Monitor::WebSocketPayload payload;
      if (!monitor->GetWebSocketImagePayload(format, &payload)) {
        if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Unable to fetch image payload"))) {
          return false;
        }
        return true;
      }
      if (!sendImagePayload(client, payload, request_id)) {
        return false;
      }
      return true;
    }

    if (command == "stream") {
      std::string codec;
      if (!JsonExtractQuotedField(frame.payload, "codec", &codec)) {
        codec = "mjpeg";
      }
      monitor->setLastViewed();

      Monitor::WebSocketPayload payload;
      if (codec == "mjpeg") {
        if (!monitor->GetWebSocketImagePayload("jpeg", &payload)) {
          if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Unable to fetch mjpeg stream payload"))) {
            return false;
          }
          return true;
        }
        payload.type = "stream";
        payload.format = "mjpeg";
      } else {
        packetqueue_iterator *it = monitor->CreateWebSocketVideoIterator(codec);
        if (!it) {
          if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported stream codec or payload unavailable"))) {
            return false;
          }
          return true;
        }
        const bool ok = monitor->GetNextWebSocketVideoPayload(it, codec, &payload);
        monitor->FreeWebSocketIterator(it);
        if (!ok) {
          if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported stream codec or payload unavailable"))) {
            return false;
          }
          return true;
        }
      }

      if (!sendImagePayload(client, payload, request_id)) {
        return false;
      }
      return true;
    }

    std::string topic;
    if (!JsonExtractQuotedField(frame.payload, "topic", &topic)) {
      if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Missing topic"))) {
        return false;
      }
      return true;
    }

    if (command == "subscribe") {
      if (topic == "status") {
        int interval_ms = 1000;
        if (JsonExtractIntegerField(frame.payload, "interval_ms", &interval_ms)) {
          interval_ms = std::max(100, std::min(interval_ms, 60000));
          client->status_interval = Milliseconds(interval_ms);
        }
        client->subscribe_status = true;
        // The first status goes out here with the ack, so record the generation
        // it reflects and schedule the keepalive a full interval out; the
        // coalesce floor also starts now so broadcastStatus does not send a
        // duplicate this same loop iteration (refs #2875).
        const TimePoint subscribe_now = std::chrono::steady_clock::now();
        client->status_generation = monitor->GetWebSocketStatusGeneration();
        client->next_status_at = subscribe_now + client->status_interval;
        client->status_coalesce_until =
            subscribe_now + std::min(client->status_interval, kStatusCoalesceFloor);
        if (!queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, client->status_interval.count())) ||
            !queueFrame(client, websocket::Opcode::TEXT, monitor->GetWebSocketStatusJson())) {
          return false;
        }
      } else if (topic == "stream") {
        int interval_ms = 1000;
        if (JsonExtractIntegerField(frame.payload, "interval_ms", &interval_ms)) {
          interval_ms = std::max(100, std::min(interval_ms, 60000));
        }
        if (!JsonExtractQuotedField(frame.payload, "codec", &client->stream_codec)) {
          client->stream_codec = "mjpeg";
        }
        freeClientResources(client);
        client->subscribe_stream = true;
        monitor->setLastViewed();
        client->next_stream_at = std::chrono::steady_clock::now();
        if (client->stream_codec == "mjpeg") {
          client->stream_interval = Milliseconds(interval_ms);
          client->last_stream_sequence = 0;
          if (!queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, client->stream_interval.count()))) {
            return false;
          }

          Monitor::WebSocketPayload payload;
          if (monitor->GetWebSocketImagePayload("jpeg", &payload)) {
            payload.type = "stream";
            payload.format = "mjpeg";
            client->last_stream_sequence = payload.sequence;
            if (!sendImagePayload(client, payload, request_id)) {
              return false;
            }
          }
          client->next_stream_at = std::chrono::steady_clock::now() + client->stream_interval;
        } else {
          client->stream_interval = Milliseconds(0);
          client->stream_it = monitor->CreateWebSocketVideoIterator(client->stream_codec);
          if (!client->stream_it) {
            client->subscribe_stream = false;
            if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported stream codec or payload unavailable"))) {
              return false;
            }
            return true;
          }
          client->last_stream_sequence = 0;
          if (!queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, 0))) {
            return false;
          }
        }
      } else if (topic == "events") {
        client->subscribe_events = true;
        if (!queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, 0))) {
          return false;
        }
      } else {
        if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported topic"))) {
          return false;
        }
      }
      return true;
    }

    if (command == "unsubscribe") {
      if (topic == "status") {
        client->subscribe_status = false;
      } else if (topic == "stream") {
        client->subscribe_stream = false;
        client->last_stream_sequence = 0;
        freeClientResources(client);
      } else if (topic == "events") {
        client->subscribe_events = false;
      } else {
        if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported topic"))) {
          return false;
        }
        return true;
      }
      if (!queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, 0))) {
        return false;
      }
      return true;
    }

    if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported command"))) {
      return false;
    }
    return true;
  }
  case websocket::Opcode::PING:
    return queueFrame(client, websocket::Opcode::PONG, frame.payload);
  case websocket::Opcode::CLOSE:
    writeImmediate(client, websocket::EncodeFrame(websocket::Opcode::CLOSE, frame.payload));
    return false;
  default:
    return true;
  }
}

void MonitorWebSocketServer::writeImmediate(Client *client, const std::string &payload) {
  if (!client->tls) {
    writeFully(client->fd, payload);
    return;
  }

  // The socket is non-blocking, so a bounded wait per chunk. This only runs on
  // paths that close the connection immediately afterwards, so giving up early
  // costs an error response, not correctness.
  size_t offset = 0;
  const int kDeadlineMs = 200;
  while (offset < payload.size()) {
    size_t written = 0;
    const zm::tls::Result result = client->tls->Write(payload.data() + offset, payload.size() - offset, &written);
    if (result == zm::tls::Result::kOk) {
      offset += written;
      continue;
    }
    if ((result == zm::tls::Result::kWantRead) || (result == zm::tls::Result::kWantWrite)) {
      struct pollfd pfd = {client->fd, static_cast<short>(result == zm::tls::Result::kWantWrite ? POLLOUT : POLLIN), 0};
      if (poll(&pfd, 1, kDeadlineMs) <= 0) {
        return;
      }
      continue;
    }
    return;
  }
}

bool MonitorWebSocketServer::flushWrites(Client *client) {
  while (!client->send_queue.empty()) {
    PendingBuffer &pending = client->send_queue.front();
    size_t bytes_sent = 0;

    if (client->tls) {
      const zm::tls::Result result = client->tls->Write(
          pending.data.data() + pending.offset, pending.data.size() - pending.offset, &bytes_sent);
      if (result == zm::tls::Result::kWantWrite) {
        client->tls_out_wants_read = false;
        return true;
      }
      if (result == zm::tls::Result::kWantRead) {
        // Mid-renegotiation the write needs readable data first; wait for
        // POLLIN rather than spinning on a writable socket.
        client->tls_out_wants_read = true;
        return true;
      }
      if (result == zm::tls::Result::kClosed) {
        return false;
      }
      if (result != zm::tls::Result::kOk) {
        return false;
      }
      client->tls_out_wants_read = false;
    } else {
      const ssize_t raw =
        ::send(client->fd, pending.data.data() + pending.offset, pending.data.size() - pending.offset, MSG_NOSIGNAL);
      if (raw < 0) {
        if ((errno == EAGAIN) || (errno == EWOULDBLOCK)) {
          return true;
        }
        Error("send() failed in websocket server for monitor %u: %s", monitor->Id(), strerror(errno));
        return false;
      }
      bytes_sent = static_cast<size_t>(raw);
    }

    if (bytes_sent == 0) {
      return true;
    }

    pending.offset += bytes_sent;
    if (pending.offset >= pending.data.size()) {
      client->queued_bytes -= pending.data.size();
      client->send_queue.pop_front();
    }
  }

  return true;
}

void MonitorWebSocketServer::closeClient(Client *client) {
  if (client->tls) {
    client->tls->Shutdown();
    client->tls.reset();
  }
  freeClientResources(client);
  client->send_queue.clear();
  client->queued_bytes = 0;
  if (client->fd >= 0) {
    ::close(client->fd);
  }
  client->fd = -1;
}

bool MonitorWebSocketServer::sendImagePayload(
    Client *client,
    const Monitor::WebSocketPayload &payload,
    const std::string &request_id) {
  return
      queueFrame(client, websocket::Opcode::TEXT, metadataJson(monitor->Id(), payload, request_id)) &&
      queueFrame(client, websocket::Opcode::BINARY, payload.payload);
}

void MonitorWebSocketServer::freeClientResources(Client *client) {
  if (client->stream_it) {
    monitor->FreeWebSocketIterator(client->stream_it);
    client->stream_it = nullptr;
  }
}

bool MonitorWebSocketServer::queueRaw(Client *client, std::string payload) {
  if ((client->queued_bytes + payload.size()) > kMaxQueuedBytesPerClient) {
    Warning(
        "Closing websocket client for monitor %u after queue exceeded %zu bytes",
        monitor->Id(),
        kMaxQueuedBytesPerClient);
    return false;
  }
  client->queued_bytes += payload.size();
  client->send_queue.push_back({std::move(payload), 0});
  return true;
}

bool MonitorWebSocketServer::queueFrame(Client *client, websocket::Opcode opcode, const std::string &payload) {
  return queueRaw(client, websocket::EncodeFrame(opcode, payload));  // moved: temporary binds to by-value param
}

void MonitorWebSocketServer::broadcastStatus(std::vector<Client> *clients, TimePoint now) {
  const uint64_t generation = monitor->GetWebSocketStatusGeneration();
  std::string status;  // built on demand, reused across clients
  for (Client &client : *clients) {
    if ((client.fd < 0) || !client.handshake_complete || !client.subscribe_status) {
      continue;
    }
    // Keepalive: a status frame is due by next_status_at regardless of changes.
    const bool keepalive_due =
        (client.next_status_at.time_since_epoch().count() == 0) || (now >= client.next_status_at);
    // On change: push as soon as the monitor's generation moves past what this
    // client last saw, but no more often than the coalesce floor.
    const bool changed = generation != client.status_generation;
    const bool coalesce_ok = now >= client.status_coalesce_until;
    if (!keepalive_due && !(changed && coalesce_ok)) {
      continue;
    }
    if (status.empty()) {
      status = monitor->GetWebSocketStatusJson();
    }
    if (!queueFrame(&client, websocket::Opcode::TEXT, status)) {
      closeClient(&client);
      continue;
    }
    client.status_generation = generation;
    client.next_status_at = now + client.status_interval;
    const Milliseconds floor = std::min(client.status_interval, kStatusCoalesceFloor);
    client.status_coalesce_until = now + floor;
  }
}

void MonitorWebSocketServer::broadcastStreams(std::vector<Client> *clients, TimePoint now) {
  for (Client &client : *clients) {
    if ((client.fd < 0) || !client.handshake_complete || !client.subscribe_stream) {
      continue;
    }
    monitor->setLastViewed();
    if (client.stream_codec == "mjpeg") {
      if ((client.next_stream_at.time_since_epoch().count() != 0) && (now < client.next_stream_at)) {
        continue;
      }

      Monitor::WebSocketPayload payload;
      if (monitor->GetWebSocketImagePayload("jpeg", &payload) && (payload.sequence != client.last_stream_sequence)) {
        payload.type = "stream";
        payload.format = "mjpeg";
        if (!sendImagePayload(&client, payload, "")) {
          closeClient(&client);
          continue;
        }
        client.last_stream_sequence = payload.sequence;
      }
      client.next_stream_at = now + client.stream_interval;
      continue;
    }

    if (!client.stream_it) {
      client.stream_it = monitor->CreateWebSocketVideoIterator(client.stream_codec);
      if (!client.stream_it) {
        client.subscribe_stream = false;
        continue;
      }
    }

    int packets_sent = 0;
    while (packets_sent < 64) {
      Monitor::WebSocketPayload payload;
      if (!monitor->GetNextWebSocketVideoPayload(client.stream_it, client.stream_codec, &payload)) {
        break;
      }
      if (!sendImagePayload(&client, payload, "")) {
        closeClient(&client);
        break;
      }
      client.last_stream_sequence = payload.sequence;
      packets_sent++;
    }
  }
}

void MonitorWebSocketServer::broadcastEvents(std::vector<Client> *clients) {
  bool have_subscribers = false;
  for (const Client &client : *clients) {
    if ((client.fd >= 0) && client.handshake_complete && client.subscribe_events) {
      have_subscribers = true;
      break;
    }
  }
  if (!have_subscribers) {
    return;
  }

  const std::vector<std::string> events = monitor->DrainWebSocketMessages();
  if (events.empty()) {
    return;
  }

  for (Client &client : *clients) {
    if ((client.fd < 0) || !client.handshake_complete || !client.subscribe_events) {
      continue;
    }
    for (const std::string &event : events) {
      if (!queueFrame(&client, websocket::Opcode::TEXT, event)) {
        closeClient(&client);
        break;
      }
    }
  }
}

void MonitorWebSocketServer::dropStalledHandshakes(std::vector<Client> *clients, TimePoint now) {
  for (Client &client : *clients) {
    if ((client.fd < 0) || client.handshake_complete) {
      continue;
    }
    if ((now - client.accepted_at) < kHandshakeTimeout) {
      continue;
    }
    Debug(1, "Closing websocket client for monitor %u that never completed its handshake", monitor->Id());
    closeClient(&client);
  }
}

void MonitorWebSocketServer::removeClosedClients(std::vector<Client> *clients) {
  clients->erase(
      std::remove_if(
          clients->begin(),
          clients->end(),
          [](const Client &client) { return client.fd < 0; }),
      clients->end());
}

}  // namespace zm
