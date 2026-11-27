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

#include "zm_event.h"

TEST_CASE("EventMaxScoreForDb") {
  // An event closed before its first frame still has max_score -1; the
  // unsigned Events.MaxScore column must get 0.
  REQUIRE(EventMaxScoreForDb(-1) == 0);
  REQUIRE(EventMaxScoreForDb(0) == 0);
  REQUIRE(EventMaxScoreForDb(42) == 42);
}

TEST_CASE("EventFallbackId") {
  // When the Events insert keeps failing the event is still recorded to disk
  // under this id, for zmrecover.pl to add to the database later. It has to be
  // nonzero, distinct per monitor at the same moment, and grow with time.
  REQUIRE(EventFallbackId(1795517294000, 1) != 0);
  REQUIRE(EventFallbackId(1795517294000, 1) != EventFallbackId(1795517294000, 2));
  REQUIRE(EventFallbackId(1795517294001, 1) > EventFallbackId(1795517294000, 999));
  // Far above any id the Events table has handed out by auto increment.
  REQUIRE(EventFallbackId(1795517294000, 1) > 1000000000000000ULL);
}

TEST_CASE("ClampSectionLength") {
  // SectionLength=0 is accepted by the monitor form and used as a modulo.
  REQUIRE(ClampSectionLength(Seconds(0)) == Seconds(1));
  REQUIRE(ClampSectionLength(Seconds(1)) == Seconds(1));
  REQUIRE(ClampSectionLength(Seconds(600)) == Seconds(600));
}

TEST_CASE("Event::BuildNotes") {
  Event::StringSetMap notes;
  REQUIRE(Event::BuildNotes(notes) == "");

  notes["Motion"] = {"Zone1", "Zone2"};
  REQUIRE(Event::BuildNotes(notes) == "Motion: Zone1, Zone2");

  // Groups must not run together, e.g. "Motion: Zone2Signal: Lost".
  notes["Signal"] = {"Lost"};
  REQUIRE(Event::BuildNotes(notes) == "Motion: Zone1, Zone2; Signal: Lost");
}

TEST_CASE("EventNamePrefixLength") {
  // Events.Name is varchar(64) and the id suffix must survive truncation.
  REQUIRE(EventNamePrefixLength(0) == 63);
  REQUIRE(EventNamePrefixLength(9) == 63);
  REQUIRE(EventNamePrefixLength(10) == 62);
  REQUIRE(EventNamePrefixLength(995000206) == 55);
  REQUIRE(EventNamePrefixLength(UINT64_MAX) == 44);
}

TEST_CASE("EventCountsFrameStats") {
  const auto t0 = std::chrono::system_clock::time_point(std::chrono::seconds(1000));
  const auto boundary = t0 + std::chrono::seconds(5);

  SECTION("no boundary counts everything") {
    CHECK(EventCountsFrameStats(t0, SystemTimePoint()));
    CHECK(EventCountsFrameStats(boundary, SystemTimePoint()));
    CHECK(EventCountsFrameStats(boundary + std::chrono::seconds(1), SystemTimePoint()));
  }

  SECTION("earlier than boundary is not counted") {
    CHECK_FALSE(EventCountsFrameStats(t0, boundary));
    CHECK_FALSE(EventCountsFrameStats(boundary - std::chrono::milliseconds(1), boundary));
  }

  SECTION("equal to and later than boundary are counted") {
    CHECK(EventCountsFrameStats(boundary, boundary));
    CHECK(EventCountsFrameStats(boundary + std::chrono::milliseconds(1), boundary));
    CHECK(EventCountsFrameStats(boundary + std::chrono::seconds(10), boundary));
  }
}

TEST_CASE("EventKeepsDraining") {
  const auto t0 = std::chrono::system_clock::time_point(std::chrono::seconds(1000));
  const auto boundary = t0 + std::chrono::seconds(5);

  ZMPacket packet;
  packet.decoded = true;
  packet.analyzed = true;
  packet.timestamp = t0;

  SECTION("not terminated keeps running") {
    CHECK(EventKeepsDraining(false, SystemTimePoint(), nullptr));
    CHECK(EventKeepsDraining(false, boundary, nullptr));
    CHECK(EventKeepsDraining(false, boundary, &packet));
  }

  SECTION("terminated with zero drain_until stops immediately") {
    CHECK_FALSE(EventKeepsDraining(true, SystemTimePoint(), nullptr));
    CHECK_FALSE(EventKeepsDraining(true, SystemTimePoint(), &packet));
  }

  SECTION("terminated with non-zero drain_until stops on null or unanalyzed or boundary") {
    CHECK_FALSE(EventKeepsDraining(true, boundary, nullptr));

    packet.decoded = false;
    CHECK_FALSE(EventKeepsDraining(true, boundary, &packet));
    packet.decoded = true;

    packet.analyzed = false;
    CHECK_FALSE(EventKeepsDraining(true, boundary, &packet));
    packet.analyzed = true;

    packet.timestamp = boundary;
    CHECK_FALSE(EventKeepsDraining(true, boundary, &packet));

    packet.timestamp = boundary + std::chrono::seconds(1);
    CHECK_FALSE(EventKeepsDraining(true, boundary, &packet));
  }

  SECTION("terminated with non-zero drain_until keeps draining earlier analyzed packet") {
    packet.timestamp = t0;
    CHECK(EventKeepsDraining(true, boundary, &packet));

    packet.timestamp = boundary - std::chrono::milliseconds(1);
    CHECK(EventKeepsDraining(true, boundary, &packet));
  }
}

TEST_CASE("EventNameOnCloseSql escapes the event prefix") {
  // The prefix is the expanded EventPrefix, so it can hold the monitor name or trigger text.
  REQUIRE(EventNameOnCloseSql("Front-", 12) ==
          "IF(Name='New Event', CONCAT(LEFT('Front-', 62), '12'), Name)");
  REQUIRE(EventNameOnCloseSql("Joe's-", 12) ==
          "IF(Name='New Event', CONCAT(LEFT('Joe\\'s-', 62), '12'), Name)");
  REQUIRE(EventNameOnCloseSql("',Name),Cause=USER(),Name=IF(1,'", 7) ==
          "IF(Name='New Event', CONCAT(LEFT('\\',Name),Cause=USER(),Name=IF(1,\\'', 63), '7'), Name)");
  REQUIRE(EventNameOnCloseSql("a\\", 7) ==
          "IF(Name='New Event', CONCAT(LEFT('a\\\\', 63), '7'), Name)");
}
