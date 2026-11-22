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
#include "zm_user.h"

// zmLoadUser() refuses the password zm_create.sql seeds the admin account
// with, so zms, zmu and the RTSP server don't accept it as a credential.
TEST_CASE("isDefaultPassword") {
  REQUIRE(isDefaultPassword("admin"));

  REQUIRE_FALSE(isDefaultPassword(""));
  REQUIRE_FALSE(isDefaultPassword("Admin"));
  REQUIRE_FALSE(isDefaultPassword("admin "));
  REQUIRE_FALSE(isDefaultPassword(" admin"));
  REQUIRE_FALSE(isDefaultPassword("admin1"));
  REQUIRE_FALSE(isDefaultPassword(std::string("admin\0x", 7)));
}

// zms and zmu take the auth hash the web side hands out, so they must refuse
// the secret the web side refuses.
TEST_CASE("isUsableAuthHashSecret") {
  REQUIRE(isUsableAuthHashSecret("test-secret-0123456789abcdef0123456789ab"));

  REQUIRE_FALSE(isUsableAuthHashSecret(nullptr));
  REQUIRE_FALSE(isUsableAuthHashSecret(""));
  REQUIRE_FALSE(isUsableAuthHashSecret("...Change me to something unique..."));
}

TEST_CASE("zmLoadAuthUser refuses an unusable secret before looking anything up") {
  const char *saved = config.auth_hash_secret;

  config.auth_hash_secret = "";
  REQUIRE(zmLoadAuthUser("0123456789abcdef0123456789abcdef", "admin", false) == nullptr);
  config.auth_hash_secret = "...Change me to something unique...";
  REQUIRE(zmLoadAuthUser("0123456789abcdef0123456789abcdef", "admin", false) == nullptr);

  config.auth_hash_secret = saved;
}
