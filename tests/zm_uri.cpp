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

#include "zm_uri.h"

TEST_CASE("Uri parses bracketed IPv6 hosts", "[Uri]") {
  // The colons inside the brackets belong to the address. Splitting the host
  // at the first ':' gave Host="[" and Port=":1]:8080".
  Uri u("http://[::1]:8080/a?b=1");

  REQUIRE(u.Protocol == "http");
  REQUIRE(u.Host == "[::1]");
  REQUIRE(u.Port == "8080");
  REQUIRE(u.Path == "/a");
  REQUIRE(u.QueryString == "?b=1");
}

TEST_CASE("Uri parses a bracketed IPv6 host without a port", "[Uri]") {
  // The userinfo is skipped first, so the '[' is what starts the host.
  Uri u("rtsp://u:p@[fe80::1]/x");

  REQUIRE(u.Host == "[fe80::1]");
  REQUIRE(u.Port == "");
  REQUIRE(u.Path == "/x");
}

TEST_CASE("Uri keeps IPv4 host and port", "[Uri]") {
  Uri u("http://h:80");

  REQUIRE(u.Host == "h");
  REQUIRE(u.Port == "80");

  Uri ipv4("http://192.168.1.2:554/s");

  REQUIRE(ipv4.Host == "192.168.1.2");
  REQUIRE(ipv4.Port == "554");
  REQUIRE(ipv4.Path == "/s");
}

TEST_CASE("Uri does not take a :// in the query for the scheme", "[Uri]") {
  // With no scheme, the first ':' found was the one in the query's "http://",
  // which put the host start past the query and threw std::length_error.
  Uri u("192.168.1.5/live?url=http://x");

  REQUIRE(u.Protocol == "");
  REQUIRE(u.Host == "192.168.1.5");
  REQUIRE(u.Path == "/live");
  REQUIRE(u.QueryString == "?url=http://x");

  Uri bare("h?u=rtsp://x");

  REQUIRE(bare.Protocol == "");
  REQUIRE(bare.Host == "h");
  REQUIRE(bare.QueryString == "?u=rtsp://x");

  Uri with_scheme("rtsp://h/s?u=http://x");

  REQUIRE(with_scheme.Protocol == "rtsp");
  REQUIRE(with_scheme.Host == "h");
  REQUIRE(with_scheme.Path == "/s");
}
