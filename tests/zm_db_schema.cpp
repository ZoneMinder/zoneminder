/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

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

}  // namespace

TEST_CASE("DefaultScale schema supports fit_to_width") {
  const auto repo_root = std::filesystem::path(ZM_SOURCE_DIR);

  SECTION("fresh schema widens monitor scale columns") {
    const auto schema = ReadFile(repo_root / "db" / "zm_create.sql.in");

    REQUIRE(schema.find("`DefaultScale` VARCHAR(16) NOT NULL default '0'") != std::string::npos);
    REQUIRE(schema.find("`DefaultScale` CHAR(6) NOT NULL default '0'") == std::string::npos);
  }

  SECTION("upgrade migration widens columns and repairs truncated values") {
    const auto migration = ReadFile(repo_root / "db" / "zm_update-1.39.10.sql");

    REQUIRE(migration.find("ALTER TABLE Monitors MODIFY DefaultScale VARCHAR(16) NOT NULL default '0';") != std::string::npos);
    REQUIRE(migration.find("ALTER TABLE MonitorPresets MODIFY DefaultScale VARCHAR(16) NOT NULL default '0';") != std::string::npos);
    REQUIRE(migration.find("UPDATE Monitors SET DefaultScale = 'fit_to_width' WHERE DefaultScale = 'fit_to';") != std::string::npos);
    REQUIRE(migration.find("UPDATE MonitorPresets SET DefaultScale = 'fit_to_width' WHERE DefaultScale = 'fit_to';") != std::string::npos);
  }
}

TEST_CASE("Frames carries the audio level the event graph plots") {
  const auto repo_root = std::filesystem::path(ZM_SOURCE_DIR);

  // A fresh install and an upgraded one have to end up with the same column,
  // or the event view draws an audio line on one and not the other.
  const std::string column = "`AudioLevel` tinyint(3) unsigned NOT NULL default '0'";

  SECTION("fresh schema has the column") {
    const auto schema = ReadFile(repo_root / "db" / "zm_create.sql.in");
    REQUIRE(schema.find(column) != std::string::npos);
  }

  SECTION("upgrade migration adds the same column, after Score") {
    const auto migration = ReadFile(repo_root / "db" / "zm_update-1.39.35.sql");
    REQUIRE(migration.find("ALTER TABLE `Frames` ADD COLUMN " + column + " AFTER `Score`")
            != std::string::npos);
  }

  SECTION("the migration is re-runnable") {
    // zmupdate.pl replays every migration above the recorded version, and an
    // operator who has already patched the column in by hand must not have the
    // upgrade die on a duplicate column.
    const auto migration = ReadFile(repo_root / "db" / "zm_update-1.39.35.sql");
    REQUIRE(migration.find("INFORMATION_SCHEMA.COLUMNS") != std::string::npos);
    REQUIRE(migration.find("column_name = 'AudioLevel'") != std::string::npos);
  }

  SECTION("the insert that writes frames names the column") {
    const auto event_cpp = ReadFile(repo_root / "src" / "zm_event.cpp");
    REQUIRE(event_cpp.find("`Score`, `AudioLevel`") != std::string::npos);
  }

  SECTION("the frames ajax returns it, and Score alongside it") {
    // elements is a whitelist. Score was missing from it, which is why the old
    // cue strip could never render a bar height.
    const auto status_php = ReadFile(repo_root / "web" / "ajax" / "status.php");
    const auto frames_at = status_php.find("'frames' => array(");
    REQUIRE(frames_at != std::string::npos);
    const auto block = status_php.substr(frames_at, 600);
    REQUIRE(block.find("'Score' => true") != std::string::npos);
    REQUIRE(block.find("'AudioLevel' => true") != std::string::npos);
  }
}

TEST_CASE("Frames is keyed by (EventId, FrameId)") {
  const auto repo_root = std::filesystem::path(ZM_SOURCE_DIR);
  const auto schema = ReadFile(repo_root / "db" / "zm_create.sql.in");
  const auto frames_at = schema.find("CREATE TABLE `Frames`");
  REQUIRE(frames_at != std::string::npos);
  const auto frames = schema.substr(frames_at, schema.find("ENGINE", frames_at) - frames_at);

  SECTION("fresh schema has the composite primary key and no surrogate Id") {
    REQUIRE(frames.find("PRIMARY KEY (`EventId`,`FrameId`)") != std::string::npos);
    REQUIRE(frames.find("`Id` BIGINT") == std::string::npos);
    REQUIRE(frames.find("AUTO_INCREMENT") == std::string::npos);
    REQUIRE(frames.find("EventId_FrameId_idx") == std::string::npos);
  }

  SECTION("TimeStamp is not rewritten by updates") {
    REQUIRE(frames.find("`TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP,") != std::string::npos);
    REQUIRE(frames.find("on update") == std::string::npos);
  }

  SECTION("nothing references Frames.Id") {
    // A foreign key to Frames.Id makes the migration's DROP COLUMN fail.
    const auto ai_models = ReadFile(repo_root / "db" / "AI_Models.sql");
    REQUIRE(ai_models.find("REFERENCES `Frames`") == std::string::npos);
    REQUIRE(schema.find("REFERENCES `Frames`") == std::string::npos);
  }

  SECTION("upgrade migration converts AI_Detections before dropping Id, and is re-runnable") {
    const auto migration = ReadFile(repo_root / "db" / "zm_update-1.39.36.sql");
    const auto convert = migration.find("SET D.`FrameId` = F.`FrameId`");
    const auto drop = migration.find("DROP COLUMN `Id`");
    REQUIRE(convert != std::string::npos);
    REQUIRE(drop != std::string::npos);
    REQUIRE(convert < drop);
    REQUIRE(migration.find("ADD PRIMARY KEY (`EventId`, `FrameId`)") != std::string::npos);
    REQUIRE(migration.find("column_name = 'Id'") != std::string::npos);
  }
}
