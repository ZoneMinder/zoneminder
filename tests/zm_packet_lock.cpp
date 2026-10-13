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

#include "zm_packet.h"

#include <mutex>
#include <thread>

TEST_CASE("ZMPacket::trylock") {
  ZMPacket packet;

  SECTION("a failed try_lock leaves the holder's state alone") {
    std::unique_lock<std::mutex> holder(packet.mutex_, std::defer_lock);
    packet.lock(holder);
    REQUIRE(packet.locked);

    bool got = true;
    std::thread other([&packet, &got]() {
      std::unique_lock<std::mutex> lck(packet.mutex_, std::defer_lock);
      got = packet.trylock(lck);
    });
    other.join();

    REQUIRE_FALSE(got);
    REQUIRE(packet.locked);
    packet.unlock(holder);
    REQUIRE_FALSE(packet.locked);
  }

  SECTION("a successful try_lock reports it from its own result") {
    std::unique_lock<std::mutex> lck(packet.mutex_, std::defer_lock);
    REQUIRE(packet.trylock(lck));
    REQUIRE(lck.owns_lock());
    packet.unlock(lck);
  }
}
