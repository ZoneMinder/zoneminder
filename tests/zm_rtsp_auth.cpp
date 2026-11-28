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

#include "zm_catch2.h"

#include "zm_rtsp_auth.h"
#include "zm_crypt.h"
#include "zm_utils.h"

#include <set>
#include <string>

TEST_CASE("Authenticator Digest parsing keeps commas inside quoted values", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.authHandleHeader(
      "Digest realm=\"Front Door, Cam 1\", nonce=\"abc123\", qop=\"auth\", opaque=\"a,b\"");

  REQUIRE(auth.auth_method() == zm::AUTH_DIGEST);
  REQUIRE(auth.realm() == "Front Door, Cam 1");
  REQUIRE(auth.nonce() == "abc123");

  std::string header = auth.getAuthHeader("DESCRIBE", "rtsp://cam/live");
  CHECK(header.find("realm=\"Front Door, Cam 1\"") != std::string::npos);
  CHECK(header.find("opaque=\"a,b\"") != std::string::npos);
}

TEST_CASE("Authenticator Digest selects auth from a qop list", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.authHandleHeader("Digest realm=\"Cam\", nonce=\"n1\", qop=\"auth,auth-int\"");

  std::string header = auth.getAuthHeader("DESCRIBE", "rtsp://cam/live");
  CHECK(header.find("qop=auth, ") != std::string::npos);
  CHECK(header.find("auth-int") == std::string::npos);
}

TEST_CASE("Authenticator Digest echoes opaque", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.authHandleHeader("Digest realm=\"Cam\", nonce=\"n1\", qop=\"auth\", opaque=\"5ccc069c403e\"");

  std::string header = auth.getAuthHeader("DESCRIBE", "rtsp://cam/live");
  CHECK(header.find("opaque=\"5ccc069c403e\"") != std::string::npos);
}

TEST_CASE("Authenticator resets nonce count when the server sends a new nonce", "[rtsp_auth]") {
  zm::Authenticator auth("alice", "wonderland");
  auth.authHandleHeader("Digest realm=\"Zone\", nonce=\"nonce_first\", qop=\"auth\"");

  CHECK(auth.getAuthHeader("DESCRIBE", "rtsp://cam/live").find("nc=00000001") != std::string::npos);
  CHECK(auth.getAuthHeader("SETUP", "rtsp://cam/live/track1").find("nc=00000002") != std::string::npos);

  auth.authHandleHeader("Digest realm=\"Zone\", nonce=\"nonce_second\", qop=\"auth\", stale=true");

  std::string h3 = auth.getAuthHeader("SETUP", "rtsp://cam/live/track1");
  CHECK(h3.find("nonce=\"nonce_second\"") != std::string::npos);
  CHECK(h3.find("nc=00000001") != std::string::npos);
}

TEST_CASE("Authenticator Digest response matches the RFC 2617 example", "[rtsp_auth]") {
  zm::Authenticator auth("Mufasa", "Circle Of Life");
  auth.authHandleHeader(
      "Digest realm=\"testrealm@host.com\", qop=\"auth,auth-int\", "
      "nonce=\"dcd98b7102dd2f0e8b11d0f600bfb0c093\", "
      "opaque=\"5ccc069c403ebaf9f0171e9517f40e41\"");

  std::string header = auth.getAuthHeader("GET", "/dir/index.html");
  CHECK(header.find("nc=00000001") != std::string::npos);
  // The cnonce is random now, so rebuild the RFC 2617 response with the one sent
  // (with the RFC's cnonce 0a4f113b this gives 6629fae49393a05397450978507c4ef1).
  const std::string key = "cnonce=\"";
  size_t begin = header.find(key);
  REQUIRE(begin != std::string::npos);
  begin += key.size();
  const std::string cnonce = header.substr(begin, header.find('"', begin) - begin);
  const std::string ha1 = ByteArrayToHexString(
      zm::crypto::MD5::GetDigestOf(std::string("Mufasa:testrealm@host.com:Circle Of Life")));
  const std::string ha2 = ByteArrayToHexString(
      zm::crypto::MD5::GetDigestOf(std::string("GET:/dir/index.html")));
  const std::string response = ByteArrayToHexString(zm::crypto::MD5::GetDigestOf(
      ha1 + ":dcd98b7102dd2f0e8b11d0f600bfb0c093:00000001:" + cnonce + ":auth:" + ha2));
  CHECK(header.find("response=\"" + response + "\"") != std::string::npos);
}

TEST_CASE("Authenticator Digest keeps '=' inside quoted values", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.authHandleHeader(
      "Digest realm=\"a=b\", nonce=\"YWJjZGVmZw==\", qop=\"auth\", opaque=\"c2VjcmV0PQ==\"");

  CHECK(auth.realm() == "a=b");
  CHECK(auth.nonce() == "YWJjZGVmZw==");

  std::string header = auth.getAuthHeader("DESCRIBE", "rtsp://cam/live");
  CHECK(header.find("nonce=\"YWJjZGVmZw==\"") != std::string::npos);
  CHECK(header.find("opaque=\"c2VjcmV0PQ==\"") != std::string::npos);
}

TEST_CASE("Authenticator Digest parameter names are case insensitive", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.authHandleHeader("Digest Realm=\"Cam\", Nonce=\"n1\", QOP=\"auth\", Opaque=\"op\"");

  CHECK(auth.realm() == "Cam");
  CHECK(auth.nonce() == "n1");
  std::string header = auth.getAuthHeader("GET", "/x");
  CHECK(header.find("qop=auth, ") != std::string::npos);
  CHECK(header.find("opaque=\"op\"") != std::string::npos);
}

TEST_CASE("Authenticator Digest tolerates spaces around '='", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.authHandleHeader("Digest realm = \"Cam\", nonce = \"n1\"");

  CHECK(auth.realm() == "Cam");
  CHECK(auth.nonce() == "n1");
}

TEST_CASE("Authenticator prefers Digest over a later Basic challenge", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.checkAuthResponse(
      "HTTP/1.1 401 Unauthorized\r\n"
      "WWW-Authenticate: Digest realm=\"Cam\", nonce=\"n1\", qop=\"auth\"\r\n"
      "WWW-Authenticate: Basic realm=\"Cam\"\r\n"
      "\r\n");

  CHECK(auth.auth_method() == zm::AUTH_DIGEST);
  CHECK(auth.nonce() == "n1");
}

TEST_CASE("Authenticator uses Basic when only Basic is offered", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.checkAuthResponse(
      "HTTP/1.1 401 Unauthorized\r\n"
      "WWW-Authenticate: Basic realm=\"Cam\"\r\n"
      "\r\n");

  CHECK(auth.auth_method() == zm::AUTH_BASIC);
}

TEST_CASE("Authenticator prefers the MD5 Digest challenge when several are offered", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.checkAuthResponse(
      "HTTP/1.1 401 Unauthorized\r\n"
      "WWW-Authenticate: Digest realm=\"Cam\", nonce=\"sha\", algorithm=SHA-256\r\n"
      "WWW-Authenticate: Digest realm=\"Cam\", nonce=\"md5\", algorithm=MD5\r\n"
      "WWW-Authenticate: Basic realm=\"Cam\"\r\n"
      "\r\n");

  CHECK(auth.auth_method() == zm::AUTH_DIGEST);
  CHECK(auth.nonce() == "md5");
}

TEST_CASE("Authenticator falls back to the first Digest challenge without MD5", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.checkAuthResponse(
      "HTTP/1.1 401 Unauthorized\r\n"
      "WWW-Authenticate: Digest realm=\"Cam\", nonce=\"sha\", algorithm=SHA-256\r\n"
      "WWW-Authenticate: Basic realm=\"Cam\"\r\n"
      "\r\n");

  CHECK(auth.auth_method() == zm::AUTH_DIGEST);
  CHECK(auth.nonce() == "sha");
}

TEST_CASE("Authenticator Digest drops qop when a later challenge has none", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.authHandleHeader("Digest realm=\"Cam\", nonce=\"n1\", qop=\"auth\"");
  CHECK(auth.getAuthHeader("GET", "/x").find("qop=auth") != std::string::npos);

  auth.authHandleHeader("Digest realm=\"Cam\", nonce=\"n2\"");
  std::string header = auth.getAuthHeader("GET", "/x");
  CHECK(header.find("nonce=\"n2\"") != std::string::npos);
  CHECK(header.find("qop=") == std::string::npos);
  CHECK(header.find("nc=") == std::string::npos);
  CHECK(header.find("cnonce=") == std::string::npos);
}

TEST_CASE("Authenticator Digest drops qop when a later challenge offers only auth-int", "[rtsp_auth]") {
  zm::Authenticator auth("user1", "pass1");
  auth.authHandleHeader("Digest realm=\"Cam\", nonce=\"n1\", qop=\"auth\"");
  auth.authHandleHeader("Digest realm=\"Cam\", nonce=\"n2\", qop=\"auth-int\"");
  std::string header = auth.getAuthHeader("GET", "/x");
  CHECK(header.find("qop=") == std::string::npos);
  CHECK(header.find("cnonce=") == std::string::npos);
}

TEST_CASE("Authenticator uses a different Digest cnonce per instance", "[rtsp_auth]") {
  auto cnonce_of = []() {
    zm::Authenticator auth("u", "p");
    auth.authHandleHeader("Digest realm=\"Cam\", nonce=\"n1\", qop=\"auth\"");
    std::string header = auth.getAuthHeader("DESCRIBE", "rtsp://cam/live");

    const std::string key = "cnonce=\"";
    size_t begin = header.find(key);
    REQUIRE(begin != std::string::npos);
    begin += key.size();
    size_t end = header.find('"', begin);
    REQUIRE(end != std::string::npos);
    return header.substr(begin, end - begin);
  };

  std::set<std::string> cnonces;
  for (int i = 0; i < 8; i++) {
    cnonces.insert(cnonce_of());
  }

  CHECK(cnonces.size() > 1);
  CHECK(cnonces.count("0a4f113b") == 0);
}
