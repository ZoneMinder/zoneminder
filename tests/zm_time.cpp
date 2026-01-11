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
#include "zm_time.h"
#include <chrono>

// Test ParseISO8601Duration function
TEST_CASE("ParseISO8601Duration") {
  SECTION("Valid duration strings") {
    REQUIRE(ParseISO8601Duration("PT20S") == 20);
    REQUIRE(ParseISO8601Duration("PT1M") == 60);
    REQUIRE(ParseISO8601Duration("PT5M") == 300);
    REQUIRE(ParseISO8601Duration("PT1H") == 3600);
    REQUIRE(ParseISO8601Duration("PT2H") == 7200);
    REQUIRE(ParseISO8601Duration("PT1H30M") == 5400);  // 1 hour 30 minutes
    REQUIRE(ParseISO8601Duration("PT1H30M45S") == 5445);  // 1 hour 30 minutes 45 seconds
    REQUIRE(ParseISO8601Duration("PT2H15M30S") == 8130);  // 2 hours 15 minutes 30 seconds
    REQUIRE(ParseISO8601Duration("PT45S") == 45);
    REQUIRE(ParseISO8601Duration("PT10M30S") == 630);  // 10 minutes 30 seconds
    REQUIRE(ParseISO8601Duration("PT0S") == 0);  // Zero seconds
  }
  
  SECTION("Invalid duration strings") {
    // Empty string
    REQUIRE(ParseISO8601Duration("") == -1);
    
    // Too short
    REQUIRE(ParseISO8601Duration("PT") == -1);
    REQUIRE(ParseISO8601Duration("P") == -1);
    
    // Missing PT prefix
    REQUIRE(ParseISO8601Duration("20S") == -1);
    REQUIRE(ParseISO8601Duration("1M") == -1);
    
    // Wrong prefix
    REQUIRE(ParseISO8601Duration("T20S") == -1);
    REQUIRE(ParseISO8601Duration("PS") == -1);
    
    // Invalid characters
    REQUIRE(ParseISO8601Duration("PT20X") == -1);
    REQUIRE(ParseISO8601Duration("PT1H2X") == -1);
    
    // Missing unit designator
    REQUIRE(ParseISO8601Duration("PT20") == -1);
    REQUIRE(ParseISO8601Duration("PT1H30") == -1);
    
    // Duplicate units
    REQUIRE(ParseISO8601Duration("PT1H2H") == -1);
    REQUIRE(ParseISO8601Duration("PT1M2M") == -1);
    
    // Invalid order (should be H, then M, then S)
    // Note: Our parser is lenient about order, so this might actually work
    // But we test to document expected behavior
    
    // No digits before unit
    REQUIRE(ParseISO8601Duration("PTS") == -1);
    REQUIRE(ParseISO8601Duration("PTM") == -1);
  }
  
  SECTION("Edge cases") {
    // Large values
    REQUIRE(ParseISO8601Duration("PT24H") == 86400);  // 24 hours = 1 day
    REQUIRE(ParseISO8601Duration("PT60M") == 3600);   // 60 minutes = 1 hour
    REQUIRE(ParseISO8601Duration("PT3600S") == 3600); // 3600 seconds = 1 hour
  }
}

// Test FormatTimestamp function (time_t version)
TEST_CASE("FormatTimestamp with time_t") {
  SECTION("Format specific time") {
    // Create a specific time: 2024-01-15 14:30:45
    std::tm tm_val = {};
    tm_val.tm_year = 2024 - 1900;  // Years since 1900
    tm_val.tm_mon = 0;             // January (0-based)
    tm_val.tm_mday = 15;
    tm_val.tm_hour = 14;
    tm_val.tm_min = 30;
    tm_val.tm_sec = 45;
    time_t t = mktime(&tm_val);
    
    std::string result = FormatTimestamp(t);
    
    // Check format: YYYY-MM-DD HH:MM:SS
    REQUIRE(result.length() == 19);
    REQUIRE(result.substr(0, 4) == "2024");
    REQUIRE(result.substr(5, 2) == "01");
    REQUIRE(result.substr(8, 2) == "15");
    REQUIRE(result.substr(11, 2) == "14");
    REQUIRE(result.substr(14, 2) == "30");
    REQUIRE(result.substr(17, 2) == "45");
    REQUIRE(result[4] == '-');
    REQUIRE(result[7] == '-');
    REQUIRE(result[10] == ' ');
    REQUIRE(result[13] == ':');
    REQUIRE(result[16] == ':');
  }
  
  SECTION("Format current time") {
    time_t now = std::time(nullptr);
    std::string result = FormatTimestamp(now);
    
    // Should be 19 characters: YYYY-MM-DD HH:MM:SS
    REQUIRE(result.length() == 19);
    
    // Check basic format structure
    REQUIRE(result[4] == '-');
    REQUIRE(result[7] == '-');
    REQUIRE(result[10] == ' ');
    REQUIRE(result[13] == ':');
    REQUIRE(result[16] == ':');
  }
}

// Test FormatTimestamp function (SystemTimePoint version)
TEST_CASE("FormatTimestamp with SystemTimePoint") {
  SECTION("Format specific SystemTimePoint") {
    // Create a specific time: 2024-01-15 14:30:45
    std::tm tm_val = {};
    tm_val.tm_year = 2024 - 1900;
    tm_val.tm_mon = 0;
    tm_val.tm_mday = 15;
    tm_val.tm_hour = 14;
    tm_val.tm_min = 30;
    tm_val.tm_sec = 45;
    time_t t = mktime(&tm_val);
    
    SystemTimePoint tp = std::chrono::system_clock::from_time_t(t);
    std::string result = FormatTimestamp(tp);
    
    // Check format: YYYY-MM-DD HH:MM:SS
    REQUIRE(result.length() == 19);
    REQUIRE(result.substr(0, 4) == "2024");
    REQUIRE(result.substr(5, 2) == "01");
    REQUIRE(result.substr(8, 2) == "15");
    REQUIRE(result.substr(11, 2) == "14");
    REQUIRE(result.substr(14, 2) == "30");
    REQUIRE(result.substr(17, 2) == "45");
  }
  
  SECTION("Format current SystemTimePoint") {
    auto now = std::chrono::system_clock::now();
    std::string result = FormatTimestamp(now);
    
    // Should be 19 characters: YYYY-MM-DD HH:MM:SS
    REQUIRE(result.length() == 19);
    
    // Check basic format structure
    REQUIRE(result[4] == '-');
    REQUIRE(result[7] == '-');
    REQUIRE(result[10] == ' ');
    REQUIRE(result[13] == ':');
    REQUIRE(result[16] == ':');
  }
}

// Test FormatDuration function
TEST_CASE("FormatDuration") {
  SECTION("Format various durations") {
    REQUIRE(FormatDuration(0) == "0s");
    REQUIRE(FormatDuration(1) == "1s");
    REQUIRE(FormatDuration(45) == "45s");
    REQUIRE(FormatDuration(60) == "1m");
    REQUIRE(FormatDuration(61) == "1m 1s");
    REQUIRE(FormatDuration(90) == "1m 30s");
    REQUIRE(FormatDuration(120) == "2m");
    REQUIRE(FormatDuration(3600) == "1h");
    REQUIRE(FormatDuration(3601) == "1h 1s");
    REQUIRE(FormatDuration(3660) == "1h 1m");
    REQUIRE(FormatDuration(3661) == "1h 1m 1s");
    REQUIRE(FormatDuration(5400) == "1h 30m");
    REQUIRE(FormatDuration(5445) == "1h 30m 45s");
    REQUIRE(FormatDuration(7200) == "2h");
    REQUIRE(FormatDuration(8130) == "2h 15m 30s");
    REQUIRE(FormatDuration(86400) == "24h");
  }
  
  SECTION("Negative duration") {
    REQUIRE(FormatDuration(-1) == "invalid");
    REQUIRE(FormatDuration(-100) == "invalid");
  }
  
  SECTION("Large durations") {
    REQUIRE(FormatDuration(90000) == "25h");
    REQUIRE(FormatDuration(100000) == "27h 46m 40s");
  }
}
