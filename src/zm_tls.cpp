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

#include "zm_tls.h"

#include "zm_config.h"
#include "zm_logger.h"

#include <cerrno>
#include <cstring>
#include <sys/stat.h>
#include <unistd.h>

#if defined(HAVE_LIBGNUTLS)
#include <gnutls/gnutls.h>
#elif defined(HAVE_LIBOPENSSL)
#include <openssl/err.h>
#include <openssl/ssl.h>
#endif

namespace zm {
namespace tls {

namespace {

// Both backends fail with unhelpful errors when the key is unreadable, which is
// the most common way this is misconfigured: certificates are world readable but
// private keys are not, and zmc does not usually run as root.
bool checkReadable(const std::string &path, const char *what) {
  if (path.empty()) {
    Error("TLS %s path is empty", what);
    return false;
  }
  if (access(path.c_str(), R_OK) != 0) {
    Error("TLS %s '%s' is not readable by uid %d: %s",
          what, path.c_str(), static_cast<int>(getuid()), strerror(errno));
    return false;
  }
  return true;
}

}  // namespace

#if defined(HAVE_LIBGNUTLS)

// ---------------------------------------------------------------- GnuTLS ---

struct ServerContext::Impl {
  gnutls_certificate_credentials_t credentials = nullptr;
  gnutls_priority_t priority = nullptr;

  ~Impl() {
    if (priority) gnutls_priority_deinit(priority);
    if (credentials) gnutls_certificate_free_credentials(credentials);
  }
};

struct Session::Impl {
  gnutls_session_t session = nullptr;

  ~Impl() {
    if (session) gnutls_deinit(session);
  }
};

bool ServerContext::Supported() { return true; }
const char *ServerContext::BackendName() { return "GnuTLS"; }

ServerContext::ServerContext() : impl(new Impl), valid(false) {}
ServerContext::~ServerContext() = default;

bool ServerContext::Load(const std::string &certificate_path, const std::string &key_path) {
  valid = false;

  if (!checkReadable(certificate_path, "certificate") || !checkReadable(key_path, "private key")) {
    return false;
  }

  int rc = gnutls_certificate_allocate_credentials(&impl->credentials);
  if (rc != GNUTLS_E_SUCCESS) {
    Error("Unable to allocate TLS credentials: %s", gnutls_strerror(rc));
    return false;
  }

  rc = gnutls_certificate_set_x509_key_file(impl->credentials,
                                            certificate_path.c_str(),
                                            key_path.c_str(),
                                            GNUTLS_X509_FMT_PEM);
  if (rc != GNUTLS_E_SUCCESS) {
    Error("Unable to load TLS certificate '%s' with key '%s': %s",
          certificate_path.c_str(), key_path.c_str(), gnutls_strerror(rc));
    return false;
  }

  // NORMAL already excludes SSLv3 and the broken ciphersuites; dropping TLS 1.0
  // and 1.1 on top of that matches what a current web server would offer.
  rc = gnutls_priority_init(&impl->priority, "NORMAL:-VERS-TLS1.0:-VERS-TLS1.1", nullptr);
  if (rc != GNUTLS_E_SUCCESS) {
    Error("Unable to set TLS priorities: %s", gnutls_strerror(rc));
    return false;
  }

  valid = true;
  Info("TLS enabled using %s with certificate '%s'", BackendName(), certificate_path.c_str());
  return true;
}

Session::Session(ServerContext &context, int fd) : impl(new Impl), handshake_done(false) {
  ServerContext::Impl *ctx = context.impl_for_session();

  int rc = gnutls_init(&impl->session, GNUTLS_SERVER | GNUTLS_NONBLOCK);
  if (rc != GNUTLS_E_SUCCESS) {
    Error("Unable to initialise TLS session: %s", gnutls_strerror(rc));
    impl->session = nullptr;
    return;
  }

  gnutls_priority_set(impl->session, ctx->priority);
  gnutls_credentials_set(impl->session, GNUTLS_CRD_CERTIFICATE, ctx->credentials);
  gnutls_transport_set_int(impl->session, fd);
  gnutls_handshake_set_timeout(impl->session, GNUTLS_DEFAULT_HANDSHAKE_TIMEOUT);
}

Session::~Session() = default;

Result Session::Handshake() {
  if (!impl->session) return Result::kError;

  const int rc = gnutls_handshake(impl->session);
  if (rc == GNUTLS_E_SUCCESS) {
    handshake_done = true;
    return Result::kOk;
  }
  if (rc == GNUTLS_E_AGAIN || rc == GNUTLS_E_INTERRUPTED) {
    return gnutls_record_get_direction(impl->session) ? Result::kWantWrite : Result::kWantRead;
  }
  if (gnutls_error_is_fatal(rc)) {
    Debug(1, "TLS handshake failed: %s", gnutls_strerror(rc));
    return Result::kError;
  }
  // Non-fatal warnings (an unexpected but recoverable alert, say) just mean go around again.
  return Result::kWantRead;
}

Result Session::Read(void *buffer, size_t len, size_t *bytes_read) {
  *bytes_read = 0;
  if (!impl->session) return Result::kError;

  const ssize_t rc = gnutls_record_recv(impl->session, buffer, len);
  if (rc > 0) {
    *bytes_read = static_cast<size_t>(rc);
    return Result::kOk;
  }
  if (rc == 0) return Result::kClosed;
  if (rc == GNUTLS_E_AGAIN || rc == GNUTLS_E_INTERRUPTED) {
    return gnutls_record_get_direction(impl->session) ? Result::kWantWrite : Result::kWantRead;
  }
  if (rc == GNUTLS_E_PREMATURE_TERMINATION) return Result::kClosed;
  Debug(1, "TLS read failed: %s", gnutls_strerror(static_cast<int>(rc)));
  return Result::kError;
}

Result Session::Write(const void *buffer, size_t len, size_t *bytes_written) {
  *bytes_written = 0;
  if (!impl->session) return Result::kError;

  const ssize_t rc = gnutls_record_send(impl->session, buffer, len);
  if (rc > 0) {
    *bytes_written = static_cast<size_t>(rc);
    return Result::kOk;
  }
  if (rc == GNUTLS_E_AGAIN || rc == GNUTLS_E_INTERRUPTED) {
    return gnutls_record_get_direction(impl->session) ? Result::kWantWrite : Result::kWantRead;
  }
  Debug(1, "TLS write failed: %s", gnutls_strerror(static_cast<int>(rc)));
  return Result::kError;
}

void Session::Shutdown() {
  if (impl->session && handshake_done) {
    gnutls_bye(impl->session, GNUTLS_SHUT_WR);
  }
}

#elif defined(HAVE_LIBOPENSSL)

// --------------------------------------------------------------- OpenSSL ---

struct ServerContext::Impl {
  SSL_CTX *ctx = nullptr;

  ~Impl() {
    if (ctx) SSL_CTX_free(ctx);
  }
};

struct Session::Impl {
  SSL *ssl = nullptr;

  ~Impl() {
    if (ssl) SSL_free(ssl);
  }
};

namespace {

std::string opensslError() {
  const unsigned long err = ERR_get_error();
  if (!err) return "no further detail";
  char buf[256] = "";
  ERR_error_string_n(err, buf, sizeof(buf));
  return std::string(buf);
}

}  // namespace

bool ServerContext::Supported() { return true; }
const char *ServerContext::BackendName() { return "OpenSSL"; }

ServerContext::ServerContext() : impl(new Impl), valid(false) {}
ServerContext::~ServerContext() = default;

bool ServerContext::Load(const std::string &certificate_path, const std::string &key_path) {
  valid = false;

  if (!checkReadable(certificate_path, "certificate") || !checkReadable(key_path, "private key")) {
    return false;
  }

  impl->ctx = SSL_CTX_new(TLS_server_method());
  if (!impl->ctx) {
    Error("Unable to create TLS context: %s", opensslError().c_str());
    return false;
  }

  // Match what a current web server would offer.
  SSL_CTX_set_min_proto_version(impl->ctx, TLS1_2_VERSION);
  SSL_CTX_set_options(impl->ctx, SSL_OP_NO_COMPRESSION | SSL_OP_CIPHER_SERVER_PREFERENCE);

  if (SSL_CTX_use_certificate_chain_file(impl->ctx, certificate_path.c_str()) != 1) {
    Error("Unable to load TLS certificate '%s': %s", certificate_path.c_str(), opensslError().c_str());
    return false;
  }
  if (SSL_CTX_use_PrivateKey_file(impl->ctx, key_path.c_str(), SSL_FILETYPE_PEM) != 1) {
    Error("Unable to load TLS private key '%s': %s", key_path.c_str(), opensslError().c_str());
    return false;
  }
  if (SSL_CTX_check_private_key(impl->ctx) != 1) {
    Error("TLS private key '%s' does not match certificate '%s': %s",
          key_path.c_str(), certificate_path.c_str(), opensslError().c_str());
    return false;
  }

  valid = true;
  Info("TLS enabled using %s with certificate '%s'", BackendName(), certificate_path.c_str());
  return true;
}

Session::Session(ServerContext &context, int fd) : impl(new Impl), handshake_done(false) {
  ServerContext::Impl *ctx = context.impl_for_session();

  impl->ssl = SSL_new(ctx->ctx);
  if (!impl->ssl) {
    Error("Unable to create TLS session: %s", opensslError().c_str());
    return;
  }
  if (SSL_set_fd(impl->ssl, fd) != 1) {
    Error("Unable to attach TLS session to socket: %s", opensslError().c_str());
    SSL_free(impl->ssl);
    impl->ssl = nullptr;
    return;
  }
  SSL_set_accept_state(impl->ssl);
}

Session::~Session() = default;

namespace {

// Maps an OpenSSL return code onto our non-blocking result.
Result translate(SSL *ssl, int rc, const char *what) {
  const int err = SSL_get_error(ssl, rc);
  switch (err) {
  case SSL_ERROR_WANT_READ:
    return Result::kWantRead;
  case SSL_ERROR_WANT_WRITE:
    return Result::kWantWrite;
  case SSL_ERROR_ZERO_RETURN:
    return Result::kClosed;
  case SSL_ERROR_SYSCALL:
    if (rc == 0 || errno == 0 || errno == ECONNRESET || errno == EPIPE) return Result::kClosed;
    if (errno == EINTR || errno == EAGAIN || errno == EWOULDBLOCK) return Result::kWantRead;
    Debug(1, "TLS %s failed: %s", what, strerror(errno));
    return Result::kError;
  default:
    Debug(1, "TLS %s failed: %s", what, opensslError().c_str());
    return Result::kError;
  }
}

}  // namespace

Result Session::Handshake() {
  if (!impl->ssl) return Result::kError;

  ERR_clear_error();
  const int rc = SSL_accept(impl->ssl);
  if (rc == 1) {
    handshake_done = true;
    return Result::kOk;
  }
  return translate(impl->ssl, rc, "handshake");
}

Result Session::Read(void *buffer, size_t len, size_t *bytes_read) {
  *bytes_read = 0;
  if (!impl->ssl) return Result::kError;

  ERR_clear_error();
  const int rc = SSL_read(impl->ssl, buffer, static_cast<int>(len));
  if (rc > 0) {
    *bytes_read = static_cast<size_t>(rc);
    return Result::kOk;
  }
  return translate(impl->ssl, rc, "read");
}

Result Session::Write(const void *buffer, size_t len, size_t *bytes_written) {
  *bytes_written = 0;
  if (!impl->ssl) return Result::kError;

  ERR_clear_error();
  const int rc = SSL_write(impl->ssl, buffer, static_cast<int>(len));
  if (rc > 0) {
    *bytes_written = static_cast<size_t>(rc);
    return Result::kOk;
  }
  return translate(impl->ssl, rc, "write");
}

void Session::Shutdown() {
  if (impl->ssl && handshake_done) {
    SSL_shutdown(impl->ssl);
  }
}

#else

// ------------------------------------------------------- no TLS backend ---
//
// ZM_CRYPTO_BACKEND selected neither library. The websocket listener refuses to
// enable TLS rather than falling back to plaintext, so a misconfigured build
// fails loudly instead of quietly serving ws:// to an https:// page.

struct ServerContext::Impl {};
struct Session::Impl {};

bool ServerContext::Supported() { return false; }
const char *ServerContext::BackendName() { return "none"; }

ServerContext::ServerContext() : impl(new Impl), valid(false) {}
ServerContext::~ServerContext() = default;

bool ServerContext::Load(const std::string &, const std::string &) {
  Error("Cannot enable TLS: this build has no crypto backend. "
        "Rebuild with ZM_CRYPTO_BACKEND set to openssl or gnutls.");
  return false;
}

Session::Session(ServerContext &, int) : impl(new Impl), handshake_done(false) {}
Session::~Session() = default;

Result Session::Handshake() { return Result::kError; }
Result Session::Read(void *, size_t, size_t *bytes_read) { *bytes_read = 0; return Result::kError; }
Result Session::Write(const void *, size_t, size_t *bytes_written) { *bytes_written = 0; return Result::kError; }
void Session::Shutdown() {}

#endif

}  // namespace tls
}  // namespace zm
