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

// The helpers live in zmu.cpp; build it here with its main() renamed.
#define main zmu_main
#include "zmu.cpp"
#undef main
#include <cstring>
#include <sstream>
#include <vector>

static std::vector<std::string> Columns(const std::string &line) {
  std::istringstream in(line);
  std::vector<std::string> cols;
  std::string c;
  while (in >> c) cols.push_back(c);
  return cols;
}

TEST_CASE("zmu: integer option arguments", "[zmu]") {
  int v = 99;
  REQUIRE(zmu_parse_int("50", &v));
  REQUIRE(v == 50);
  REQUIRE(zmu_parse_int("-5", &v));
  REQUIRE(v == -5);
  v = 99;
  // atoi() turned these into 0 and 12, which would set brightness 0
  REQUIRE_FALSE(zmu_parse_int("abc", &v));
  REQUIRE_FALSE(zmu_parse_int("12x", &v));
  REQUIRE_FALSE(zmu_parse_int("", &v));
  REQUIRE_FALSE(zmu_parse_int(nullptr, &v));
  REQUIRE_FALSE(zmu_parse_int("99999999999", &v));
  REQUIRE(v == 99);
}

TEST_CASE("zmu: monitor id argument", "[zmu]") {
  REQUIRE(zmu_parse_monitor_id("3") == 3);
  REQUIRE(zmu_parse_monitor_id("abc") == -1);
  REQUIRE(zmu_parse_monitor_id("3x") == -1);
  REQUIRE(zmu_parse_monitor_id("") == -1);
  REQUIRE(zmu_parse_monitor_id("0") == -1);
  REQUIRE(zmu_parse_monitor_id("-2") == -1);
}

TEST_CASE("zmu: monitor functions need a monitor", "[zmu]") {
  REQUIRE_FALSE(zmu_check_options(ZMU_STATE, 0).empty());
  REQUIRE_FALSE(zmu_check_options(ZMU_ALARM | ZMU_CANCEL, 0).empty());
  REQUIRE(zmu_check_options(ZMU_STATE, 2).empty());
  REQUIRE(zmu_check_options(ZMU_LIST, 0).empty());
  REQUIRE(zmu_check_options(ZMU_QUERY, 0).empty());
  REQUIRE(zmu_check_options(ZMU_BOGUS, 0).empty());
}

TEST_CASE("zmu: alarm and cancel need Monitors edit", "[zmu]") {
  // mon_id 0 skips the per-monitor check, which would read the database
  const char *view_row[] = {"3", "viewer", "x", "1", "3", "3", "3", "2", "3", "0"};
  const char *edit_row[] = {"4", "editor", "x", "1", "3", "3", "3", "3", "3", "0"};
  User viewer(const_cast<char **>(view_row));
  User editor(const_cast<char **>(edit_row));
  REQUIRE_FALSE(ValidateAccess(&viewer, 0, ZMU_ALARM));
  REQUIRE_FALSE(ValidateAccess(&viewer, 0, ZMU_CANCEL));
  REQUIRE(ValidateAccess(&editor, 0, ZMU_ALARM));
  REQUIRE(ValidateAccess(&editor, 0, ZMU_CANCEL));
  REQUIRE(ValidateAccess(&viewer, 0, ZMU_STATE));
}

TEST_CASE("zmu: single line output separator", "[zmu]") {
  REQUIRE(zmu_separator(false).empty());
  REQUIRE(zmu_separator(true) == " ");
}

TEST_CASE("zmu: state names", "[zmu]") {
  REQUIRE(std::string(zmu_state_name(Monitor::IDLE)) == "Idle");
  REQUIRE(std::string(zmu_state_name(Monitor::PREALARM)) == "Prealarm");
  REQUIRE(std::string(zmu_state_name(Monitor::ALARM)) == "Alarm");
  REQUIRE(std::string(zmu_state_name(Monitor::ALERT)) == "Alert");
  REQUIRE(std::string(zmu_state_name(Monitor::UNKNOWN)) == "Unknown");
}

TEST_CASE("zmu: list rows line up with the header", "[zmu]") {
  const std::string header = zmu_list_header();
  std::vector<std::string> header_cols = Columns(header);
  const size_t ncols = header_cols.size();
  REQUIRE(ncols == 12);
  // The existing columns keep their places; Name is added last.
  REQUIRE(header_cols[0] == "Id");
  REQUIRE(header_cols[1] == "Capturing");
  REQUIRE(header_cols[10] == "FrameRate");
  REQUIRE(header_cols[11] == "Name");
  // The fixed-width part of a row is as wide as the header's.
  const size_t name_at = header.size() - strlen("Name");
  SECTION("capturing monitor") {
    std::string row = zmu_list_row(3, "FrontDoor", Monitor::CAPTURING_ALWAYS, 2, 3, 1, 0, 1700000000.5, 4, 5, 123456789, 15.0);
    std::vector<std::string> cols = Columns(row);
    REQUIRE(cols.size() == ncols);
    REQUIRE(cols[0] == "3");
    REQUIRE(cols[11] == "FrontDoor");
    REQUIRE(row.substr(name_at) == "FrontDoor");
  }
  SECTION("large event id keeps the columns apart") {
    std::string row = zmu_list_row(3, "FrontDoor", Monitor::CAPTURING_ALWAYS, 2, 3, 1, 0, 1700000000.5, 4, 5, 1234567890, 15.0);
    REQUIRE(Columns(row)[9] == "1234567890");
    REQUIRE(Columns(row)[10] == "15.00");
  }
  SECTION("monitor with capturing None, as listed by -l -v") {
    std::string row = zmu_list_row(7, "Garage", Monitor::CAPTURING_NONE, 1, 1, 0, 0, 0.0, -1, -1, 0, 0.0);
    std::vector<std::string> cols = Columns(row);
    REQUIRE(cols.size() == ncols);
    REQUIRE(cols[0] == "7");
    REQUIRE(cols[1] == "1");
    REQUIRE(row.substr(name_at) == "Garage");
  }
  SECTION("a name with spaces leaves the other columns where they were") {
    std::string row = zmu_list_row(4, "Front Door Camera", Monitor::CAPTURING_ALWAYS, 2, 3, 1, 0, 1700000000.5, 4, 5, 123456789, 15.0);
    std::vector<std::string> cols = Columns(row);
    REQUIRE(cols[0] == "4");
    REQUIRE(cols[10] == "15.00");
    REQUIRE(row.substr(name_at) == "Front Door Camera");
  }
  SECTION("an empty name still lines up") {
    std::string row = zmu_list_row(5, "", Monitor::CAPTURING_ALWAYS, 2, 3, 1, 0, 1700000000.5, 4, 5, 123456789, 15.0);
    REQUIRE(row.size() == name_at);
    REQUIRE(Columns(row).size() == ncols - 1);
  }
}
