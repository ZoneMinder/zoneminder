//
// ZoneMinder TLS server support
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

#ifndef ZM_TLS_H
#define ZM_TLS_H

#include <cstddef>
#include <memory>
#include <string>

// Server-side TLS for listeners that are not fronted by a web server.
//
// zms is reached through Apache/nginx, which terminates TLS on its behalf. The
// per-monitor websocket listener in zmc is not, so it has to terminate TLS
// itself or browsers on an https:// page will refuse the ws:// connection as
// mixed content.
//
// The interface is non-blocking throughout: every call can return kWantRead or
// kWantWrite, meaning the caller should wait for that condition on the socket
// and call again with the same arguments. That is what lets a TLS session sit
// inside an existing poll() loop without a thread per client.
//
// Backed by whichever library ZM_CRYPTO_BACKEND selected, following the same
// arrangement as zm_crypt.h.

namespace zm {
namespace tls {

enum class Result {
  kOk,
  kWantRead,
  kWantWrite,
  kClosed,
  kError
};

// Certificate and key, loaded once and shared by every session. Sessions hold
// a reference, so this must outlive them.
class ServerContext {
 public:
  ServerContext();
  ~ServerContext();

  ServerContext(const ServerContext &) = delete;
  ServerContext &operator=(const ServerContext &) = delete;

  // Loads a PEM certificate chain and its private key. Returns false and logs
  // the reason on any failure, including the common operational one of the
  // process lacking read permission on the key.
  bool Load(const std::string &certificate_path, const std::string &key_path);

  bool Valid() const { return valid; }

  // True when this build has a TLS implementation at all.
  static bool Supported();

  // Name of the backing library, for logs.
  static const char *BackendName();

  struct Impl;
  Impl *impl_for_session() { return impl.get(); }

 private:
  std::unique_ptr<Impl> impl;
  bool valid;
};

// One TLS connection. Does not own the file descriptor.
class Session {
 public:
  Session(ServerContext &context, int fd);
  ~Session();

  Session(const Session &) = delete;
  Session &operator=(const Session &) = delete;

  // Drives the handshake. Call until it returns something other than
  // kWantRead/kWantWrite. Must complete before Read/Write.
  Result Handshake();

  bool HandshakeComplete() const { return handshake_done; }

  // Reads at most len bytes. On kOk, *bytes_read is what was decrypted, which
  // may be 0 if the record carried no application data yet.
  Result Read(void *buffer, size_t len, size_t *bytes_read);

  // Writes at most len bytes. On kOk, *bytes_written may be less than len.
  Result Write(const void *buffer, size_t len, size_t *bytes_written);

  // Best-effort close_notify. Does not close the file descriptor.
  void Shutdown();

 private:
  struct Impl;
  std::unique_ptr<Impl> impl;
  bool handshake_done;
};

}  // namespace tls
}  // namespace zm

#endif  // ZM_TLS_H
