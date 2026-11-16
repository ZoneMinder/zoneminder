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
