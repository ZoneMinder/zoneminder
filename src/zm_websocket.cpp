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
  const std::string key_name = "Sec-WebSocket-Key:";
  size_t key_pos = request.find(key_name);
  if (key_pos == std::string::npos) {
    return false;
  }

  size_t value_start = request.find_first_not_of(" \t", key_pos + key_name.size());
  if (value_start == std::string::npos) {
    return false;
  }

  size_t value_end = request.find("\r\n", value_start);
  if (value_end == std::string::npos) {
    value_end = request.find('\n', value_start);
  }
  if (value_end == std::string::npos) {
    return false;
  }

  *client_key = Trim(request.substr(value_start, value_end - value_start), " \t");
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

  std::array<uint8_t, 4> mask = {0, 0, 0, 0};
  if (masked) {
    if (buffer.size() < pos + mask.size()) {
      return DecodeResult::INCOMPLETE;
    }
    for (size_t i = 0; i < mask.size(); ++i) {
      mask[i] = static_cast<uint8_t>(buffer[pos + i]);
    }
    pos += mask.size();
  }

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
      Error("poll() failed in websocket server for monitor %u: %s", monitor->Id(), strerror(errno));
      break;
    }

    if (running && !pollfds.empty() && (pollfds[0].revents & POLLIN)) {
      acceptClients(&clients);
    }

    for (size_t i = 0; i < clients.size(); ++i) {
      const short revents = pollfds[i + 1].revents;
      if (revents & (POLLERR | POLLHUP | POLLNVAL)) {
        freeClientResources(&clients[i]);
        ::close(clients[i].fd);
        clients[i].fd = -1;
        continue;
      }
      if ((revents & POLLIN) && !handleRead(&clients[i])) {
        freeClientResources(&clients[i]);
        ::close(clients[i].fd);
        clients[i].fd = -1;
        continue;
      }
      if ((revents & POLLOUT) && !flushWrites(&clients[i])) {
        freeClientResources(&clients[i]);
        ::close(clients[i].fd);
        clients[i].fd = -1;
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
      freeClientResources(&client);
      ::close(client.fd);
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
    queueRaw(client, "HTTP/1.1 400 Bad Request\r\n\r\n");
    return false;
  }

  queueRaw(client, websocket::BuildHandshakeResponse(client_key));
  client->handshake_complete = true;
  client->next_status_at = std::chrono::steady_clock::now();
  client->next_image_at = std::chrono::steady_clock::now();
  return true;
}

bool MonitorWebSocketServer::handleFrame(Client *client, const websocket::Frame &frame) {
  switch (frame.opcode) {
  case websocket::Opcode::TEXT: {
    std::string command;
    std::string request_id;
    extractQuotedField(frame.payload, "request_id", &request_id);
    if (!extractQuotedField(frame.payload, "command", &command)) {
      queueFrame(client, websocket::Opcode::TEXT, errorJson("Missing command"));
      return true;
    }

    if (command == "status") {
      queueFrame(client, websocket::Opcode::TEXT, monitor->GetWebSocketStatusJson());
      return true;
    }

    if (command == "image") {
      std::string format;
      if (!extractQuotedField(frame.payload, "format", &format)) {
        format = "jpeg";
      }
      Monitor::WebSocketPayload payload;
      if (!monitor->GetWebSocketPayload(format, &payload)) {
        queueFrame(client, websocket::Opcode::TEXT, errorJson("Unable to fetch image payload"));
        return true;
      }
      sendImagePayload(client, payload, request_id);
      return true;
    }

    std::string topic;
    if (!extractQuotedField(frame.payload, "topic", &topic)) {
      queueFrame(client, websocket::Opcode::TEXT, errorJson("Missing topic"));
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
        queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, client->status_interval.count()));
        queueFrame(client, websocket::Opcode::TEXT, monitor->GetWebSocketStatusJson());
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
            queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported image format or payload unavailable"));
            return true;
          }
          client->last_image_sequence = 0;
          queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, 0));
        } else {
          client->image_interval = Milliseconds(interval_ms);
          Monitor::WebSocketPayload payload;
          if (!monitor->GetWebSocketPayload(client->image_format, &payload)) {
            client->subscribe_image = false;
            queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported image format or payload unavailable"));
            return true;
          }
          client->last_image_sequence = payload.sequence;
          queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, client->image_interval.count()));
          sendImagePayload(client, payload, request_id);
          client->next_image_at = std::chrono::steady_clock::now() + client->image_interval;
        }
      } else if (topic == "events") {
        client->subscribe_events = true;
        queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, 0));
      } else {
        queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported topic"));
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
        queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported topic"));
        return true;
      }
      queueFrame(client, websocket::Opcode::TEXT, statusAckJson(topic, 0));
      return true;
    }

    queueFrame(client, websocket::Opcode::TEXT, errorJson("Unsupported command"));
    return true;
  }
  case websocket::Opcode::PING:
    queueFrame(client, websocket::Opcode::PONG, frame.payload);
    return true;
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
      client->send_queue.erase(client->send_queue.begin());
    }
  }

  return true;
}

bool MonitorWebSocketServer::sendImagePayload(
    Client *client,
    const Monitor::WebSocketPayload &payload,
    const std::string &request_id) {
  queueFrame(client, websocket::Opcode::TEXT, metadataJson(monitor->Id(), payload, request_id));
  queueFrame(client, websocket::Opcode::BINARY, payload.payload);
  return true;
}

void MonitorWebSocketServer::freeClientResources(Client *client) {
  if (client->h264_it) {
    monitor->FreeWebSocketIterator(client->h264_it);
    client->h264_it = nullptr;
  }
}

void MonitorWebSocketServer::queueRaw(Client *client, const std::string &payload) {
  client->send_queue.push_back({payload, 0});
}

void MonitorWebSocketServer::queueFrame(Client *client, websocket::Opcode opcode, const std::string &payload) {
  queueRaw(client, websocket::EncodeFrame(opcode, payload));
}

void MonitorWebSocketServer::broadcastStatus(std::vector<Client> *clients, TimePoint now) {
  const std::string status = monitor->GetWebSocketStatusJson();
  for (Client &client : *clients) {
    if ((client.fd < 0) || !client.handshake_complete || !client.subscribe_status) {
      continue;
    }
    if ((client.next_status_at.time_since_epoch().count() == 0) || (now >= client.next_status_at)) {
      queueFrame(&client, websocket::Opcode::TEXT, status);
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
        sendImagePayload(&client, payload, "");
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
      sendImagePayload(&client, payload, "");
      client.last_image_sequence = payload.sequence;
    }
    client.next_image_at = now + client.image_interval;
  }
}

void MonitorWebSocketServer::broadcastEvents(std::vector<Client> *clients) {
  const std::vector<std::string> events = monitor->DrainWebSocketMessages();
  if (events.empty()) {
    return;
  }

  for (Client &client : *clients) {
    if ((client.fd < 0) || !client.handshake_complete || !client.subscribe_events) {
      continue;
    }
    for (const std::string &event : events) {
      queueFrame(&client, websocket::Opcode::TEXT, event);
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
