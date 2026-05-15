#include "zm_websocket.h"

#include "zm_crypt.h"
#include "zm_monitor.h"
#include "zm_utils.h"

#include <algorithm>
#include <array>
#include <cerrno>
#include <cinttypes>
#include <climits>
#include <cstdint>
#include <cstring>
#include <fcntl.h>
#include <poll.h>
#include <sys/socket.h>
#include <unistd.h>

namespace {

static constexpr const char *kWebSocketMagic = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
static constexpr size_t kMaxHandshakeSize = 16384;
static constexpr size_t kMaxMessageSize = 1024 * 1024;
static constexpr size_t kMaxQueuedBytesPerClient = 8 * 1024 * 1024;
static constexpr int kPollTimeoutMs = 100;
static constexpr char kBase64Alphabet[] =
    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";

std::string base64EncodeBytes(const uint8_t *data, size_t length) {
  std::string out;
  out.reserve(((length + 2) / 3) * 4);

  for (size_t i = 0; i < length; i += 3) {
    const uint32_t octet_a = data[i];
    const uint32_t octet_b = (i + 1 < length) ? data[i + 1] : 0;
    const uint32_t octet_c = (i + 2 < length) ? data[i + 2] : 0;
    const uint32_t triple = (octet_a << 16) | (octet_b << 8) | octet_c;

    out.push_back(kBase64Alphabet[(triple >> 18) & 0x3f]);
    out.push_back(kBase64Alphabet[(triple >> 12) & 0x3f]);
    out.push_back((i + 1 < length) ? kBase64Alphabet[(triple >> 6) & 0x3f] : '=');
    out.push_back((i + 2 < length) ? kBase64Alphabet[triple & 0x3f] : '=');
  }

  return out;
}

bool extractQuotedField(const std::string &json, const std::string &field, std::string *value) {
  std::string needle = "\"" + field + "\"";
  size_t key_pos = json.find(needle);
  if (key_pos == std::string::npos) {
    return false;
  }

  size_t colon_pos = json.find(':', key_pos + needle.size());
  if (colon_pos == std::string::npos) {
    return false;
  }

  size_t quote_start = json.find('"', colon_pos + 1);
  if (quote_start == std::string::npos) {
    return false;
  }

  std::string parsed_value;
  bool escaping = false;
  for (size_t i = quote_start + 1; i < json.size(); ++i) {
    char c = json[i];
    if (escaping) {
      parsed_value.push_back(c);
      escaping = false;
      continue;
    }
    if (c == '\\') {
      escaping = true;
      continue;
    }
    if (c == '"') {
      *value = parsed_value;
      return true;
    }
    parsed_value.push_back(c);
  }

  return false;
}

bool extractIntegerField(const std::string &json, const std::string &field, int *value) {
  std::string needle = "\"" + field + "\"";
  size_t key_pos = json.find(needle);
  if (key_pos == std::string::npos) {
    return false;
  }

  size_t colon_pos = json.find(':', key_pos + needle.size());
  if (colon_pos == std::string::npos) {
    return false;
  }

  size_t value_pos = json.find_first_of("-0123456789", colon_pos + 1);
  if (value_pos == std::string::npos) {
    return false;
  }

  size_t end_pos = value_pos;
  while (end_pos < json.size() && ((json[end_pos] >= '0' && json[end_pos] <= '9') || json[end_pos] == '-')) {
    ++end_pos;
  }

  try {
    *value = std::stoi(json.substr(value_pos, end_pos - value_pos));
  } catch (...) {
    return false;
  }

  return true;
}

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

std::string toLowerAscii(const std::string &input) {
  std::string lowered = input;
  std::transform(lowered.begin(), lowered.end(), lowered.begin(), [](unsigned char c) {
    if (c >= 'A' && c <= 'Z') {
      return static_cast<char>(c - 'A' + 'a');
    }
    return static_cast<char>(c);
  });
  return lowered;
}

bool extractHeaderValue(const std::string &request, const std::string &header_name, std::string *value) {
  size_t line_start = 0;
  while (line_start < request.size()) {
    size_t line_end = request.find('\n', line_start);
    if (line_end == std::string::npos) {
      line_end = request.size();
    }

    std::string line = request.substr(line_start, line_end - line_start);
    if (!line.empty() && line.back() == '\r') {
      line.pop_back();
    }

    size_t colon_pos = line.find(':');
    if (colon_pos != std::string::npos) {
      const std::string name = toLowerAscii(Trim(line.substr(0, colon_pos), " \t"));
      if (name == header_name) {
        *value = Trim(line.substr(colon_pos + 1), " \t");
        return !value->empty();
      }
    }

    line_start = line_end + 1;
  }

  return false;
}

bool headerContainsToken(const std::string &header_value, const std::string &token) {
  size_t start = 0;
  while (start < header_value.size()) {
    size_t end = header_value.find(',', start);
    if (end == std::string::npos) {
      end = header_value.size();
    }
    if (toLowerAscii(Trim(header_value.substr(start, end - start), " \t")) == token) {
      return true;
    }
    start = end + 1;
  }
  return false;
}

bool writeFully(int fd, const std::string &payload) {
  size_t offset = 0;
  while (offset < payload.size()) {
    const ssize_t bytes_sent = ::send(fd, payload.data() + offset, payload.size() - offset, MSG_NOSIGNAL);
    if (bytes_sent < 0) {
      if (errno == EINTR) {
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
      "{\"type\":\"image\",\"request_id\":\"%s\",\"format\":\"%s\",\"content_type\":\"%s\","
      "\"monitor_id\":%u,\"width\":%u,\"height\":%u,\"colours\":%u,\"subpixel_order\":%u,"
      "\"image_count\":%u,\"sequence\":%" PRIu64 ",\"keyframe\":%s,\"payload_bytes\":%zu}",
      escape_json_string(request_id).c_str(),
      escape_json_string(payload.format).c_str(),
      escape_json_string(payload.content_type).c_str(),
      monitor_id,
      payload.width,
      payload.height,
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

}  // namespace

namespace zm {
namespace websocket {

std::string ComputeAcceptKey(const std::string &client_key) {
  const std::string input = client_key + kWebSocketMagic;
  const zm::crypto::SHA1::Digest digest = zm::crypto::SHA1::GetDigestOf(input);
  return base64EncodeBytes(digest.data(), digest.size());
}

bool ExtractHandshakeKey(const std::string &request, std::string *client_key) {
  std::string upgrade_value;
  std::string connection_value;
  std::string version_value;
  if (!extractHeaderValue(request, "sec-websocket-key", client_key)) {
    return false;
  }
  if (!extractHeaderValue(request, "upgrade", &upgrade_value) ||
      (toLowerAscii(upgrade_value) != "websocket")) {
    return false;
  }
  if (!extractHeaderValue(request, "connection", &connection_value) ||
      !headerContainsToken(connection_value, "upgrade")) {
    return false;
  }
  if (!extractHeaderValue(request, "sec-websocket-version", &version_value) ||
      (version_value != "13")) {
    return false;
  }
  return !client_key->empty();
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
  frame->opcode = static_cast<Opcode>(first & 0x0f);
  frame->payload.assign(buffer.data() + pos, buffer.data() + pos + payload_len);

  if (masked) {
    for (size_t i = 0; i < frame->payload.size(); ++i) {
      frame->payload[i] ^= mask[i % mask.size()];
    }
  }

  *consumed = pos + payload_len;
  return DecodeResult::OK;
}

unsigned int MonitorStreamingPort(int base_port, unsigned int monitor_id) {
  if (base_port <= 0) {
    return 0;
  }
  if (monitor_id > static_cast<unsigned int>(INT_MAX - base_port)) {
    return 0;
  }
  return static_cast<unsigned int>(base_port) + monitor_id;
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
      short events = POLLIN;
      if (!client.send_queue.empty()) {
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
      if ((revents & POLLIN) && !handleRead(&clients[i])) {
        closeClient(&clients[i]);
        continue;
      }
      if ((revents & POLLOUT) && !flushWrites(&clients[i])) {
        closeClient(&clients[i]);
      }
    }

    const TimePoint now = std::chrono::steady_clock::now();
    broadcastStatus(&clients, now);
    broadcastImages(&clients, now);
    broadcastEvents(&clients);

    for (Client &client : clients) {
      if (client.fd >= 0 && !client.send_queue.empty()) {
        flushWrites(&client);
      }
    }

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

    if (!setNonBlocking(fd)) {
      ::close(fd);
      continue;
    }

    clients->emplace_back(fd);
  }

  return true;
}

bool MonitorWebSocketServer::handleRead(Client *client) {
  char buffer[4096];
  while (true) {
    const ssize_t bytes_read = ::recv(client->fd, buffer, sizeof(buffer), 0);
    if (bytes_read == 0) {
      return false;
    }
    if (bytes_read < 0) {
      if ((errno == EAGAIN) || (errno == EWOULDBLOCK)) {
        break;
      }
      Error("recv() failed in websocket server for monitor %u: %s", monitor->Id(), strerror(errno));
      return false;
    }

    client->recv_buffer.append(buffer, bytes_read);
    if (client->recv_buffer.size() > kMaxMessageSize) {
      Warning("Closing websocket client for monitor %u due to oversized message", monitor->Id());
      return false;
    }
  }

  if (!client->handshake_complete) {
    return handleHandshake(client);
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
    writeFully(client->fd, "HTTP/1.1 400 Bad Request\r\n\r\n");
    return false;
  }

  queueRaw(client, websocket::BuildHandshakeResponse(client_key));
  client->handshake_complete = true;
  client->next_status_at = std::chrono::steady_clock::now();
  client->next_image_at = std::chrono::steady_clock::now();
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
    extractQuotedField(frame.payload, "request_id", &request_id);
    if (!extractQuotedField(frame.payload, "command", &command)) {
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
      if (!extractQuotedField(frame.payload, "format", &format)) {
        format = "jpeg";
      }
      Monitor::WebSocketPayload payload;
      if (!monitor->GetWebSocketPayload(format, &payload)) {
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

    std::string topic;
    if (!extractQuotedField(frame.payload, "topic", &topic)) {
      if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Missing topic"))) {
        return false;
      }
      return true;
    }

    if (command == "subscribe") {
      if (topic == "status") {
        int interval_ms = 1000;
        if (extractIntegerField(frame.payload, "interval_ms", &interval_ms)) {
          interval_ms = std::max(100, std::min(interval_ms, 60000));
          client->status_interval = Milliseconds(interval_ms);
        }
        client->subscribe_status = true;
        client->next_status_at = std::chrono::steady_clock::now();
        if (!queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, client->status_interval.count())) ||
            !queueFrame(client, websocket::Opcode::TEXT, monitor->GetWebSocketStatusJson())) {
          return false;
        }
      } else if (topic == "image") {
        int interval_ms = 1000;
        if (extractIntegerField(frame.payload, "interval_ms", &interval_ms)) {
          interval_ms = std::max(100, std::min(interval_ms, 60000));
        }
        if (!extractQuotedField(frame.payload, "format", &client->image_format)) {
          client->image_format = "jpeg";
        }
        freeClientResources(client);
        client->subscribe_image = true;
        client->next_image_at = std::chrono::steady_clock::now();
        if (client->image_format == "h264") {
          client->image_interval = Milliseconds(0);
          client->h264_it = monitor->CreateWebSocketH264Iterator();
          if (!client->h264_it) {
            client->subscribe_image = false;
            if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported image format or payload unavailable"))) {
              return false;
            }
            return true;
          }
          client->last_image_sequence = 0;
          if (!queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, 0))) {
            return false;
          }
        } else {
          client->image_interval = Milliseconds(interval_ms);
          Monitor::WebSocketPayload payload;
          if (!monitor->GetWebSocketPayload(client->image_format, &payload)) {
            client->subscribe_image = false;
            if (!queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported image format or payload unavailable"))) {
              return false;
            }
            return true;
          }
          client->last_image_sequence = payload.sequence;
          if (!queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, client->image_interval.count())) ||
              !sendImagePayload(client, payload, request_id)) {
            return false;
          }
          client->next_image_at = std::chrono::steady_clock::now() + client->image_interval;
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
      } else if (topic == "image") {
        client->subscribe_image = false;
        client->last_image_sequence = 0;
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
    return false;
  default:
    return true;
  }
}

bool MonitorWebSocketServer::flushWrites(Client *client) {
  while (!client->send_queue.empty()) {
    PendingBuffer &pending = client->send_queue.front();
    const ssize_t bytes_sent =
      ::send(client->fd, pending.data.data() + pending.offset, pending.data.size() - pending.offset, MSG_NOSIGNAL);
    if (bytes_sent < 0) {
      if ((errno == EAGAIN) || (errno == EWOULDBLOCK)) {
        return true;
      }
      Error("send() failed in websocket server for monitor %u: %s", monitor->Id(), strerror(errno));
      return false;
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
  if (client->h264_it) {
    monitor->FreeWebSocketIterator(client->h264_it);
    client->h264_it = nullptr;
  }
}

bool MonitorWebSocketServer::queueRaw(Client *client, const std::string &payload) {
  if ((client->queued_bytes + payload.size()) > kMaxQueuedBytesPerClient) {
    Warning(
        "Closing websocket client for monitor %u after queue exceeded %zu bytes",
        monitor->Id(),
        kMaxQueuedBytesPerClient);
    return false;
  }
  client->queued_bytes += payload.size();
  client->send_queue.push_back({payload, 0});
  return true;
}

bool MonitorWebSocketServer::queueFrame(Client *client, websocket::Opcode opcode, const std::string &payload) {
  return queueRaw(client, websocket::EncodeFrame(opcode, payload));
}

void MonitorWebSocketServer::broadcastStatus(std::vector<Client> *clients, TimePoint now) {
  const std::string status = monitor->GetWebSocketStatusJson();
  for (Client &client : *clients) {
    if ((client.fd < 0) || !client.handshake_complete || !client.subscribe_status) {
      continue;
    }
    if ((client.next_status_at.time_since_epoch().count() == 0) || (now >= client.next_status_at)) {
      if (!queueFrame(&client, websocket::Opcode::TEXT, status)) {
        closeClient(&client);
        continue;
      }
      client.next_status_at = now + client.status_interval;
    }
  }
}

void MonitorWebSocketServer::broadcastImages(std::vector<Client> *clients, TimePoint now) {
  for (Client &client : *clients) {
    if ((client.fd < 0) || !client.handshake_complete || !client.subscribe_image) {
      continue;
    }
    if (client.image_format == "h264") {
      if (!client.h264_it) {
        client.h264_it = monitor->CreateWebSocketH264Iterator();
        if (!client.h264_it) {
          client.subscribe_image = false;
          continue;
        }
      }

      int packets_sent = 0;
      while (packets_sent < 64) {
        Monitor::WebSocketPayload payload;
        if (!monitor->GetNextWebSocketH264Payload(client.h264_it, &payload)) {
          break;
        }
        if (!sendImagePayload(&client, payload, "")) {
          closeClient(&client);
          break;
        }
        client.last_image_sequence = payload.sequence;
        packets_sent++;
      }
      continue;
    }
    if ((client.next_image_at.time_since_epoch().count() != 0) && (now < client.next_image_at)) {
      continue;
    }

    Monitor::WebSocketPayload payload;
    if (monitor->GetWebSocketPayload(client.image_format, &payload) && (payload.sequence != client.last_image_sequence)) {
      if (!sendImagePayload(&client, payload, "")) {
        closeClient(&client);
        continue;
      }
      client.last_image_sequence = payload.sequence;
    }
    client.next_image_at = now + client.image_interval;
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

void MonitorWebSocketServer::removeClosedClients(std::vector<Client> *clients) {
  clients->erase(
      std::remove_if(
          clients->begin(),
          clients->end(),
          [](const Client &client) { return client.fd < 0; }),
      clients->end());
}

}  // namespace zm
