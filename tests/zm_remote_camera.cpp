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

#include "zm_regexp.h"
#include "zm_remote_camera.h"
#include <cstring>

TEST_CASE("SplitUserInfo") {
  std::string host = "bob:secret@cam.local";
  std::string user, pass;
  REQUIRE(SplitUserInfo(host, user, pass));
  REQUIRE(host == "cam.local");
  REQUIRE(user == "bob");
  REQUIRE(pass == "secret");

  host = "bob@cam.local";
  user = ""; pass = "";
  REQUIRE(SplitUserInfo(host, user, pass));
  REQUIRE(host == "cam.local");
  REQUIRE(user == "bob");
  REQUIRE(pass.empty());

  host = "bob:pa:ss@cam.local";
  user = ""; pass = "";
  REQUIRE(SplitUserInfo(host, user, pass));
  REQUIRE(host == "cam.local");
  REQUIRE(user == "bob");
  REQUIRE(pass == "pa:ss");

  host = "cam.local";
  user = "u"; pass = "p";
  REQUIRE_FALSE(SplitUserInfo(host, user, pass));
  REQUIRE(host == "cam.local");
  REQUIRE(user == "u");
  REQUIRE(pass == "p");
}

#if HAVE_LIBPCRE
TEST_CASE("RegExpr match indexes") {
  RegExpr re("^(a)(b)?(c)?");
  const char *s = "ab";
  REQUIRE(re.Match(s, strlen(s)) == 3);
  REQUIRE(std::string(re.MatchString(1)) == "a");
  REQUIRE(std::string(re.MatchString(2)) == "b");
  // index equal to the match count is not a participating group
  REQUIRE(re.MatchString(3) == nullptr);
  REQUIRE(re.MatchLength(3) == 0);
  REQUIRE(re.MatchString(-1) == nullptr);
  REQUIRE(re.MatchString(1000) == nullptr);
}

TEST_CASE("RegExpr failed match leaves no stale data") {
  RegExpr re("(x+)");
  const char *s = "axxb";
  REQUIRE(re.Match(s, strlen(s)) == 2);
  REQUIRE(std::string(re.MatchString(0)) == "xx");
  const char *t = "none";
  REQUIRE(re.Match(t, strlen(t)) == 0);
  REQUIRE(re.MatchString(0) == nullptr);
  REQUIRE(re.MatchLength(0) == 0);
  REQUIRE(re.MatchString(1) == nullptr);
}
#endif
