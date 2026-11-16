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

// Authenticator::authHandleHeader() parses a Digest WWW-Authenticate challenge by
// splitting each comma-separated directive on "=" and reading kvPair[0]/kvPair[1].
// A directive that is a bare keyword with no "=" (realm, nonce or qop) yields a
// one-element vector, so reading kvPair[1] runs off the end. The header comes from a
// camera's 401 response, which on an HTTP source is processed before authentication,
// so a malformed directive would crash the capture daemon (an unauthenticated DoS).
// Under ASAN the pre-fix code faults on the out-of-bounds read; without ASAN the
// garbage read leaves realm()/nonce() holding junk instead of staying empty.
// refs GHSA-r8cq-jgx6-gr5p

TEST_CASE("Authenticator::authHandleHeader parses a well-formed Digest challenge", "[Authenticator]") {
  zm::Authenticator auth("user", "pass");
  auth.authHandleHeader("Digest realm=\"testrealm\", nonce=\"abc123\", qop=\"auth\"");
  REQUIRE(auth.auth_method() == zm::AUTH_DIGEST);
  REQUIRE(auth.realm() == "testrealm");
  REQUIRE(auth.nonce() == "abc123");
}

TEST_CASE("Authenticator::authHandleHeader skips malformed directives without reading past the vector", "[Authenticator]") {
  SECTION("a lone key with no '=' (the exploit shape: realm has no value)") {
    zm::Authenticator auth("user", "pass");
    REQUIRE_NOTHROW(auth.authHandleHeader("Digest realm"));
    // Pre-fix this read kvPair[1] out of bounds; post-fix the directive is skipped.
    REQUIRE(auth.realm().empty());
    REQUIRE(auth.nonce().empty());
  }

  SECTION("a bare nonce keyword after a valid realm") {
    zm::Authenticator auth("user", "pass");
    REQUIRE_NOTHROW(auth.authHandleHeader("Digest realm=\"r1\", nonce, qop=\"auth\""));
    REQUIRE(auth.realm() == "r1");
    REQUIRE(auth.nonce().empty());
  }

  SECTION("empty directives between valid ones") {
    zm::Authenticator auth("user", "pass");
    REQUIRE_NOTHROW(auth.authHandleHeader("Digest realm=\"r2\",,nonce=\"n2\""));
    REQUIRE(auth.realm() == "r2");
    REQUIRE(auth.nonce() == "n2");
  }

  SECTION("an unrecognised bare token does not disturb later directives") {
    zm::Authenticator auth("user", "pass");
    REQUIRE_NOTHROW(auth.authHandleHeader("Digest stale, realm=\"r3\""));
    REQUIRE(auth.realm() == "r3");
  }

  SECTION("a challenge that is Digest with nothing else") {
    zm::Authenticator auth("user", "pass");
    REQUIRE_NOTHROW(auth.authHandleHeader("Digest "));
    REQUIRE(auth.auth_method() == zm::AUTH_DIGEST);
    REQUIRE(auth.realm().empty());
  }
}
