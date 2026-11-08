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

#include "zm_websocket.h"

#include <arpa/inet.h>
#include <netinet/in.h>
#include <sys/socket.h>

// zm::websocket::PeerAddressString turns the sockaddr accept() fills in into the
// numeric client IP the per-monitor websocket listener needs to validate an
// IP-bound auth hash (ZM_AUTH_HASH_IPS). zmc has no CGI REMOTE_ADDR, so this is
// the only source of the peer address (refs #2875).

namespace {

sockaddr_storage makeV4(const char *ip, uint16_t port) {
  sockaddr_storage ss;
  memset(&ss, 0, sizeof(ss));
  sockaddr_in *in = reinterpret_cast<sockaddr_in *>(&ss);
  in->sin_family = AF_INET;
  in->sin_port = htons(port);
  REQUIRE(inet_pton(AF_INET, ip, &in->sin_addr) == 1);
  return ss;
}

sockaddr_storage makeV6(const char *ip, uint16_t port) {
  sockaddr_storage ss;
  memset(&ss, 0, sizeof(ss));
  sockaddr_in6 *in6 = reinterpret_cast<sockaddr_in6 *>(&ss);
  in6->sin6_family = AF_INET6;
  in6->sin6_port = htons(port);
  REQUIRE(inet_pton(AF_INET6, ip, &in6->sin6_addr) == 1);
  return ss;
}

}  // namespace

TEST_CASE("peer address formats an IPv4 client", "[websocket]") {
  sockaddr_storage ss = makeV4("192.0.2.5", 54321);
  REQUIRE(zm::websocket::PeerAddressString(ss, sizeof(sockaddr_in)) == "192.0.2.5");
}

TEST_CASE("peer address formats an IPv6 client", "[websocket]") {
  sockaddr_storage ss = makeV6("2001:db8::1", 54321);
  REQUIRE(zm::websocket::PeerAddressString(ss, sizeof(sockaddr_in6)) == "2001:db8::1");
}

TEST_CASE("peer address unwraps an IPv4-mapped IPv6 client", "[websocket]") {
  // A listener bound to an IPv6 socket reports IPv4 peers as ::ffff:a.b.c.d.
  // The browser's hash was bound to the plain dotted address PHP saw, so the
  // mapped form must be reduced to it or the hash never matches.
  sockaddr_storage ss = makeV6("::ffff:192.0.2.5", 54321);
  REQUIRE(zm::websocket::PeerAddressString(ss, sizeof(sockaddr_in6)) == "192.0.2.5");
}

TEST_CASE("peer address is empty for an unset sockaddr", "[websocket]") {
  sockaddr_storage ss;
  memset(&ss, 0, sizeof(ss));
  ss.ss_family = AF_UNSPEC;
  REQUIRE(zm::websocket::PeerAddressString(ss, sizeof(ss)).empty());
}
