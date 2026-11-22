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

#include "zm_config.h"
#include "zm_remote_camera_http.h"

TEST_CASE("RemoteCameraHttp::ResponseTooLarge cuts off a response that never ends", "[RemoteCameraHttp]") {
  const unsigned long long image_size = 640ull * 480 * 3;

  SECTION("a frame with its headers is accepted") {
    REQUIRE_FALSE(RemoteCameraHttp::ResponseTooLarge(0, image_size));
    REQUIRE_FALSE(RemoteCameraHttp::ResponseTooLarge(2 * image_size + ZM_NETWORK_BUFSIZ, image_size));
  }

  SECTION("a frame and part of the next one is accepted") {
    REQUIRE_FALSE(RemoteCameraHttp::ResponseTooLarge(3 * image_size, image_size));
  }

  SECTION("several frames worth of data with no end marker is refused") {
    REQUIRE(RemoteCameraHttp::ResponseTooLarge(5 * image_size, image_size));
    REQUIRE(RemoteCameraHttp::ResponseTooLarge(300ull * 1024 * 1024, image_size));
  }

  SECTION("nothing above 1 GiB is accepted whatever the frame size") {
    REQUIRE(RemoteCameraHttp::ResponseTooLarge((1ull << 30) + 1, 1ull << 40));
    REQUIRE_FALSE(RemoteCameraHttp::ResponseTooLarge(1ull << 30, 1ull << 40));
  }
}
