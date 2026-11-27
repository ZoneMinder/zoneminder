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

#include "zm_storage.h"

TEST_CASE("Storage::FallbackSql picks another real Storage row") {
  SECTION("excludes the missing storage and reads the Storage table") {
    std::string sql = Storage::FallbackSql(3, 0);
    REQUIRE(sql.find("FROM `Storage`") != std::string::npos);
    REQUIRE(sql.find("`Id` != 3") != std::string::npos);
    REQUIRE(sql.find("LIMIT 1") != std::string::npos);
  }

  SECTION("only picks an enabled storage area") {
    REQUIRE(Storage::FallbackSql(3, 0).find("`Enabled`") != std::string::npos);
    REQUIRE(Storage::FallbackSql(3, 7).find("`Enabled`") != std::string::npos);
  }

  SECTION("prefers storage on the monitor's server") {
    std::string sql = Storage::FallbackSql(3, 7);
    REQUIRE(sql.find("(`ServerId` = 7) DESC") != std::string::npos);
  }

  SECTION("no server preference without a server id") {
    REQUIRE(Storage::FallbackSql(0, 0).find("ServerId") == std::string::npos);
  }
}

TEST_CASE("Storage::NeedsFallback only triggers for a missing non zero id") {
  REQUIRE_FALSE(Storage::NeedsFallback(0, 0));  // schema default, use the default events path
  REQUIRE(Storage::NeedsFallback(5, 0));        // row missing, constructor reset the id
  REQUIRE_FALSE(Storage::NeedsFallback(5, 5));  // loaded fine
}
