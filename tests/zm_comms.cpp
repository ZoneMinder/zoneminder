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

#include "catch2/catch.hpp"

#include "zm_comms.h"
#include <array>

TEST_CASE("ZM::Pipe basics") {
  ZM::Pipe pipe;

  SECTION("setBlocking on non-opened") {
    REQUIRE(pipe.setBlocking(true) == false);
    REQUIRE(pipe.setBlocking(false) == false);
  }

  REQUIRE(pipe.open() == true);

  REQUIRE(pipe.isOpen() == true);
  REQUIRE(pipe.isClosed() == false);
  REQUIRE(pipe.getReadDesc() != -1);
  REQUIRE(pipe.getWriteDesc() != -1);

  SECTION("double open") {
    REQUIRE(pipe.open() == true); // is this expected?
  }

  SECTION("close") {
    REQUIRE(pipe.close() == true);

    REQUIRE(pipe.isOpen() == false);
    REQUIRE(pipe.isClosed() == true);
    REQUIRE(pipe.getReadDesc() == -1);
    REQUIRE(pipe.getWriteDesc() == -1);

    SECTION("double close") {
      REQUIRE(pipe.close() == true);
    }

    SECTION("setBlocking on closed") {
      REQUIRE(pipe.setBlocking(true) == false);
      REQUIRE(pipe.setBlocking(false) == false);
    }
  }

  SECTION("setBlocking") {
    REQUIRE(pipe.setBlocking(true) == true);
    REQUIRE(pipe.setBlocking(false) == true);
  }
}

TEST_CASE("ZM::Pipe read/write") {
  ZM::Pipe pipe;

  std::array<char, 3> msg = {'a', 'b', 'c'};
  std::array<char, msg.size()> rcv{};

  SECTION("read/write on non-opened pipe") {
    REQUIRE(pipe.write(msg.data(), msg.size()) == -1);
    REQUIRE(pipe.read(rcv.data(), rcv.size()) == -1);
  }

  SECTION("read/write on opened pipe") {
    REQUIRE(pipe.open() == true);

    REQUIRE(pipe.write(msg.data(), msg.size()) == msg.size());
    REQUIRE(pipe.read(rcv.data(), rcv.size()) == msg.size());

    REQUIRE(rcv == msg);
  }

  SECTION("read/write on closed pipe") {
    REQUIRE(pipe.open() == true);
    REQUIRE(pipe.close() == true);

    REQUIRE(pipe.write(msg.data(), msg.size()) == -1);
    REQUIRE(pipe.read(rcv.data(), rcv.size()) == -1);
  }
}

TEST_CASE("ZM::SockAddrInet") {
  ZM::SockAddrInet addr;
  REQUIRE(addr.getAddrSize() == sizeof(sockaddr_in));

  SECTION("resolve") {
    addr.resolve(80, "");
    REQUIRE(addr.getDomain() == AF_INET);

    SECTION("newSockAddr from resolved addr") {
      ZM::SockAddr *addr2 = ZM::SockAddr::newSockAddr(&addr);
      REQUIRE(addr2->getDomain() == AF_INET);
      REQUIRE(addr2->getAddrSize() == sizeof(sockaddr_in));
    }
  }
}

TEST_CASE("ZM::SockAddrUnix") {
  ZM::SockAddrUnix addr;
  REQUIRE(addr.getAddrSize() == sizeof(sockaddr_un));

  SECTION("resovle") {
    addr.resolve("/", "");
    REQUIRE(addr.getDomain() == AF_UNIX);

    SECTION("newSockAddr from resolved addr") {
      ZM::SockAddr *addr2 = ZM::SockAddr::newSockAddr(&addr);
      REQUIRE(addr2->getDomain() == AF_UNIX);
      REQUIRE(addr2->getAddrSize() == sizeof(sockaddr_un));
    }
  }
}
