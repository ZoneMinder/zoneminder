#ifndef ZM_WEBSOCKET_H
#define ZM_WEBSOCKET_H

#include "zm_comms.h"
#include "zm_monitor.h"
#include "zm_time.h"

#include <atomic>
#include <cstddef>
#include <cstdint>
#include <deque>
#include <string>
#include <thread>
#include <vector>

namespace zm {
namespace websocket {

enum class Opcode : uint8_t {
  CONTINUATION = 0x0,
  TEXT = 0x1,
  BINARY = 0x2,
  CLOSE = 0x8,
  PING = 0x9,
  PONG = 0xA
};

struct Frame {
  bool fin = true;
  bool masked = false;
  Opcode opcode = Opcode::TEXT;
  std::string payload;
};

enum class DecodeResult {
  OK,
  INCOMPLETE,
  ERROR
};

std::string ComputeAcceptKey(const std::string &client_key);
bool ExtractHandshakeKey(const std::string &request, std::string *client_key);
std::string BuildHandshakeResponse(const std::string &client_key);
std::string EncodeFrame(Opcode opcode, const std::string &payload, bool fin = true);
DecodeResult DecodeFrame(const std::string &buffer, Frame *frame, size_t *consumed);
unsigned int MonitorStreamingPort(int base_port, unsigned int monitor_id);

}  // namespace websocket

class MonitorWebSocketServer {
 public:
  explicit MonitorWebSocketServer(Monitor *monitor);
  ~MonitorWebSocketServer();

  bool Start(int port);
  void Stop();

 private:
  struct PendingBuffer {
    std::string data;
    size_t offset = 0;
  };

  struct Client {
    explicit Client(int p_fd) : fd(p_fd) {}

    int fd;
    bool handshake_complete = false;
    bool subscribe_status = false;
    bool subscribe_events = false;
    bool subscribe_image = false;
    Milliseconds status_interval = Milliseconds(1000);
    Milliseconds image_interval = Milliseconds(1000);
    TimePoint next_status_at = {};
    TimePoint next_image_at = {};
    std::string image_format = "jpeg";
    uint64_t last_image_sequence = 0;
    packetqueue_iterator *h264_it = nullptr;
    std::string recv_buffer;
    std::deque<PendingBuffer> send_queue;
    size_t queued_bytes = 0;
  };

  Monitor *monitor;
  int port;
  std::atomic<bool> running;
  TcpInetServer server;
  std::thread server_thread;

  void run();
  bool acceptClients(std::vector<Client> *clients);
  bool handleRead(Client *client);
  bool handleHandshake(Client *client);
  bool handleFrame(Client *client, const websocket::Frame &frame);
  bool flushWrites(Client *client);
  void closeClient(Client *client);
  bool sendImagePayload(Client *client, const Monitor::WebSocketPayload &payload, const std::string &request_id);
  void freeClientResources(Client *client);
  bool queueRaw(Client *client, const std::string &payload);
  bool queueFrame(Client *client, websocket::Opcode opcode, const std::string &payload);
  void broadcastStatus(std::vector<Client> *clients, TimePoint now);
  void broadcastImages(std::vector<Client> *clients, TimePoint now);
  void broadcastEvents(std::vector<Client> *clients);
  void removeClosedClients(std::vector<Client> *clients);
};

}  // namespace zm

#endif  // ZM_WEBSOCKET_H
