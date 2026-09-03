#ifndef ZM_WEBSOCKET_H
#define ZM_WEBSOCKET_H

#include "zm_comms.h"
#include "zm_monitor.h"
#include "zm_tls.h"
#include "zm_time.h"

#include <atomic>
#include <cstddef>
#include <cstdint>
#include <deque>
#include <memory>
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
bool ExtractHandshakeRequestTarget(const std::string &request, std::string *target);
bool ExtractAuthorizationBearerToken(const std::string &request, std::string *token);
std::string BuildHandshakeResponse(const std::string &client_key);
std::string EncodeFrame(Opcode opcode, const std::string &payload, bool fin = true);
DecodeResult DecodeFrame(const std::string &buffer, Frame *frame, size_t *consumed);
unsigned int MonitorWebSocketPort(int base_port, unsigned int monitor_id);

}  // namespace websocket

class MonitorWebSocketServer {
 public:
  explicit MonitorWebSocketServer(Monitor *monitor);
  ~MonitorWebSocketServer();

  bool Start(int port);
  void Stop();

  // Enables TLS for subsequently accepted clients. Must be called before
  // Start(). Returns false if the certificate or key cannot be used, in which
  // case the caller should not start the listener at all rather than fall back
  // to plaintext.
  bool EnableTLS(const std::string &certificate_path, const std::string &key_path);

 private:
  struct PendingBuffer {
    std::string data;
    size_t offset = 0;
  };

  struct Client {
    explicit Client(int p_fd) : fd(p_fd) {}

    int fd;
    // When the connection was accepted, so a peer that never finishes the TLS
    // or HTTP handshake can be reaped instead of occupying a slot forever.
    TimePoint accepted_at = std::chrono::steady_clock::now();
    // Null when the listener is plaintext. When set, every byte to and from
    // this client goes through it, and tls_want_write records which direction
    // the last call wanted so poll() can be told.
    std::unique_ptr<zm::tls::Session> tls;
    bool tls_want_write = false;
    bool handshake_complete = false;
    bool subscribe_status = false;
    bool subscribe_events = false;
    bool subscribe_stream = false;
    Milliseconds status_interval = Milliseconds(1000);
    Milliseconds stream_interval = Milliseconds(1000);
    TimePoint next_status_at = {};
    TimePoint next_stream_at = {};
    std::string stream_codec = "mjpeg";
    uint64_t last_stream_sequence = 0;
    packetqueue_iterator *stream_it = nullptr;
    std::string recv_buffer;
    std::deque<PendingBuffer> send_queue;
    size_t queued_bytes = 0;
  };

  Monitor *monitor;
  int port;
  std::unique_ptr<zm::tls::ServerContext> tls_context;
  std::atomic<bool> running;
  TcpInetServer server;
  std::thread server_thread;

  void run();
  bool acceptClients(std::vector<Client> *clients);
  bool handleRead(Client *client);
  // Drives the TLS handshake. Returns false if the client should be dropped.
  // Sets *done when the session is ready for application data.
  bool handleTlsHandshake(Client *client, bool *done);
  // Reads through TLS or the raw socket as appropriate. *closed is set when the
  // peer went away.
  bool readAvailable(Client *client, bool *closed);
  // Writes a short payload immediately, through TLS when the client has it.
  // Only for responses on connections that are about to be closed.
  void writeImmediate(Client *client, const std::string &payload);
  bool handleHandshake(Client *client);
  bool handleFrame(Client *client, const websocket::Frame &frame);
  bool flushWrites(Client *client);
  void closeClient(Client *client);
  bool sendImagePayload(Client *client, const Monitor::WebSocketPayload &payload, const std::string &request_id);
  void freeClientResources(Client *client);
  bool queueRaw(Client *client, std::string payload);
  bool queueFrame(Client *client, websocket::Opcode opcode, const std::string &payload);
  void broadcastStatus(std::vector<Client> *clients, TimePoint now);
  void broadcastStreams(std::vector<Client> *clients, TimePoint now);
  void broadcastEvents(std::vector<Client> *clients);
  void removeClosedClients(std::vector<Client> *clients);
  // Closes clients that connected but never completed the websocket handshake.
  void dropStalledHandshakes(std::vector<Client> *clients, TimePoint now);
};

}  // namespace zm

#endif  // ZM_WEBSOCKET_H
