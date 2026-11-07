/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for contributors.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_tls.h"

#include <cstdio>
#include <cstdlib>
#include <string>
#include <sys/stat.h>
#include <unistd.h>

namespace {

// Certificates are generated at run time rather than committed, so the repo
// carries no private key material.
class TempCerts {
 public:
  TempCerts() {
    char dir_template[] = "/tmp/zm.tls.test.XXXXXX";
    const char *made = mkdtemp(dir_template);
    if (made) dir = made;
  }

  ~TempCerts() {
    if (dir.empty()) return;
    for (const char *name : {"cert.pem", "key.pem", "other-key.pem", "other-cert.pem"}) {
      ::chmod((dir + "/" + name).c_str(), 0600);
      ::unlink((dir + "/" + name).c_str());
    }
    ::rmdir(dir.c_str());
  }

  bool valid() const { return !dir.empty(); }

  // Returns false if the openssl tool is unavailable, so the test can skip
  // rather than fail on a machine without it.
  bool generate(const std::string &cert_name, const std::string &key_name) const {
    const std::string cmd =
      "openssl req -x509 -newkey rsa:2048 -nodes -days 1 -subj /CN=zm-test"
      " -keyout '" + dir + "/" + key_name + "'"
      " -out '" + dir + "/" + cert_name + "' >/dev/null 2>&1";
    return system(cmd.c_str()) == 0;
  }

  std::string path(const std::string &name) const { return dir + "/" + name; }

 private:
  std::string dir;
};

bool opensslToolAvailable() {
  return system("openssl version >/dev/null 2>&1") == 0;
}

}  // namespace

TEST_CASE("TLS backend reports itself") {
  // A build with neither crypto backend must say so rather than pretend, since
  // the websocket listener refuses to start plaintext when TLS was requested.
  if (zm::tls::ServerContext::Supported()) {
    REQUIRE(std::string(zm::tls::ServerContext::BackendName()) != "none");
  } else {
    REQUIRE(std::string(zm::tls::ServerContext::BackendName()) == "none");
  }
}

TEST_CASE("TLS server context rejects unusable certificates") {
  if (!zm::tls::ServerContext::Supported()) {
    WARN("no crypto backend compiled in, skipping");
    return;
  }
  if (!opensslToolAvailable()) {
    WARN("openssl tool not available, skipping certificate generation");
    return;
  }

  TempCerts certs;
  REQUIRE(certs.valid());
  REQUIRE(certs.generate("cert.pem", "key.pem"));

  SECTION("a matching pair loads") {
    zm::tls::ServerContext context;
    REQUIRE(context.Load(certs.path("cert.pem"), certs.path("key.pem")));
    REQUIRE(context.Valid());
  }

  SECTION("empty paths are rejected") {
    zm::tls::ServerContext context;
    REQUIRE_FALSE(context.Load("", certs.path("key.pem")));
    REQUIRE_FALSE(context.Load(certs.path("cert.pem"), ""));
    REQUIRE_FALSE(context.Valid());
  }

  SECTION("missing files are rejected") {
    zm::tls::ServerContext context;
    REQUIRE_FALSE(context.Load(certs.path("nope.pem"), certs.path("key.pem")));
    REQUIRE_FALSE(context.Load(certs.path("cert.pem"), certs.path("nope.pem")));
    REQUIRE_FALSE(context.Valid());
  }

  SECTION("a key that does not match the certificate is rejected") {
    REQUIRE(certs.generate("other-cert.pem", "other-key.pem"));
    zm::tls::ServerContext context;
    REQUIRE_FALSE(context.Load(certs.path("cert.pem"), certs.path("other-key.pem")));
    REQUIRE_FALSE(context.Valid());
  }

  SECTION("an unreadable key is rejected with a clear failure") {
    // This is the common operational mistake: zmc does not run as root, and web
    // server keys are usually root only. root bypasses the permission check, so
    // skip there rather than assert something untrue.
    if (geteuid() == 0) {
      WARN("running as root, cannot test unreadable key");
      return;
    }
    REQUIRE(::chmod(certs.path("key.pem").c_str(), 0) == 0);
    zm::tls::ServerContext context;
    REQUIRE_FALSE(context.Load(certs.path("cert.pem"), certs.path("key.pem")));
    REQUIRE_FALSE(context.Valid());
    REQUIRE(::chmod(certs.path("key.pem").c_str(), 0600) == 0);
  }
}

TEST_CASE("TLS server context starts invalid") {
  zm::tls::ServerContext context;
  REQUIRE_FALSE(context.Valid());
}
