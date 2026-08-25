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

#include "zm_monitor.h"

#include <filesystem>
#include <fstream>
#include <sstream>
#include <string>

namespace {

std::string ReadFile(const std::filesystem::path &path) {
  std::ifstream input(path);
  REQUIRE(input.is_open());

  std::ostringstream buffer;
  buffer << input.rdbuf();
  return buffer.str();
}

Monitor::EventAction MakeAction(const std::string &type, int audio_file = -1) {
  Monitor::EventAction action;
  action.trigger = Monitor::EventAction::EVENT_START;
  action.action_type = type;
  action.target_monitor_id = 2;
  action.audio_file = audio_file;
  return action;
}

}  // namespace

TEST_CASE("Monitor action type maps onto a control command") {
  SECTION("every stored action type has a command") {
    REQUIRE(std::string(Monitor::ActionCommandName("LightOn")) == "lightOn");
    REQUIRE(std::string(Monitor::ActionCommandName("LightOff")) == "lightOff");
    REQUIRE(std::string(Monitor::ActionCommandName("IndicatorLightOn")) == "indicatorLightOn");
    REQUIRE(std::string(Monitor::ActionCommandName("IndicatorLightOff")) == "indicatorLightOff");
    REQUIRE(std::string(Monitor::ActionCommandName("AudioPlay")) == "audioPlay");
    REQUIRE(std::string(Monitor::ActionCommandName("AudioStop")) == "audioStop");
  }

  SECTION("anything not on the whitelist is refused") {
    // The mapping is a whitelist precisely so a value that reached the column
    // by some other route cannot become a method call.
    REQUIRE(Monitor::ActionCommandName("") == nullptr);
    REQUIRE(Monitor::ActionCommandName("lightOn") == nullptr);
    REQUIRE(Monitor::ActionCommandName("reboot") == nullptr);
    REQUIRE(Monitor::ActionCommandName("AudioPlay; rm -rf /") == nullptr);
  }
}

TEST_CASE("Monitor action message is the zmcontrol wire format") {
  SECTION("a sound carries its file id") {
    REQUIRE(Monitor::ActionMessage(MakeAction("AudioPlay", 12)) ==
            "{\"command\":\"audioPlay\",\"file\":12}");
  }

  SECTION("file id 0 is a legitimate id, not an absent one") {
    REQUIRE(Monitor::ActionMessage(MakeAction("AudioPlay", 0)) ==
            "{\"command\":\"audioPlay\",\"file\":0}");
  }

  SECTION("a sound with no file id configured omits the argument") {
    REQUIRE(Monitor::ActionMessage(MakeAction("AudioPlay")) ==
            "{\"command\":\"audioPlay\"}");
  }

  SECTION("actions that take no file never carry one") {
    // A row may keep a stale AudioFile after its type is changed; the message
    // must not pass it to a command that does not understand it.
    REQUIRE(Monitor::ActionMessage(MakeAction("LightOn", 12)) ==
            "{\"command\":\"lightOn\"}");
    REQUIRE(Monitor::ActionMessage(MakeAction("AudioStop", 12)) ==
            "{\"command\":\"audioStop\"}");
  }

  SECTION("an unknown type yields no message at all") {
    REQUIRE(Monitor::ActionMessage(MakeAction("Nonsense", 1)).empty());
  }
}

TEST_CASE("Monitor action trigger names round trip") {
  REQUIRE(std::string(Monitor::ActionTriggerName(Monitor::EventAction::EVENT_START)) == "EventStart");
  REQUIRE(std::string(Monitor::ActionTriggerName(Monitor::EventAction::EVENT_END)) == "EventEnd");
  REQUIRE(std::string(Monitor::ActionTriggerName(Monitor::EventAction::ALARM)) == "Alarm");
  REQUIRE(std::string(Monitor::ActionTriggerName(Monitor::EventAction::MANUAL)) == "Manual");
}

TEST_CASE("Monitor action schema") {
  const auto repo_root = std::filesystem::path(ZM_SOURCE_DIR);

  SECTION("fresh schema carries DeviceClass and MonitorActions") {
    const auto schema = ReadFile(repo_root / "db" / "zm_create.sql.in");

    REQUIRE(schema.find("`DeviceClass` enum('Camera','Speaker') NOT NULL default 'Camera'") != std::string::npos);
    REQUIRE(schema.find("CREATE TABLE `MonitorActions`") != std::string::npos);
    REQUIRE(schema.find("`TargetMonitorId` int(10) unsigned NOT NULL") != std::string::npos);
  }

  SECTION("upgrade migration adds both") {
    const auto migration = ReadFile(repo_root / "db" / "zm_update-1.39.30.sql");

    REQUIRE(migration.find("ADD COLUMN `DeviceClass` enum('Camera','Speaker')") != std::string::npos);
    REQUIRE(migration.find("CREATE TABLE `MonitorActions`") != std::string::npos);
    // Re-running an upgrade must not duplicate either object.
    REQUIRE(migration.find("INFORMATION_SCHEMA.COLUMNS") != std::string::npos);
    REQUIRE(migration.find("INFORMATION_SCHEMA.TABLES") != std::string::npos);
  }

  SECTION("the action enum and the C++ whitelist agree") {
    // The DB enum is the only source of ActionType values, so every one of them
    // must map to a command or the row would silently never fire.
    const auto schema = ReadFile(repo_root / "db" / "zm_create.sql.in");
    const auto enum_start = schema.find("`ActionType` enum(");
    REQUIRE(enum_start != std::string::npos);
    const auto enum_end = schema.find(')', enum_start);
    const auto enum_body = schema.substr(enum_start, enum_end - enum_start);

    for (const char *type : {"LightOn", "LightOff", "IndicatorLightOn",
                             "IndicatorLightOff", "AudioPlay", "AudioStop"}) {
      REQUIRE(enum_body.find(std::string("'") + type + "'") != std::string::npos);
      REQUIRE(Monitor::ActionCommandName(type) != nullptr);
    }
  }
}
