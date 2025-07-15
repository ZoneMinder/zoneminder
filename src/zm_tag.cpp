/*
 * ZoneMinder Tag class implementation
 * Copyright (C) 2025 ZoneMinder Inc
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

#include "zm_tag.h"

#include "zm_logger.h"
#include "zm_signal.h"
#include "zm_utils.h"
#include <cstring>

Tag::Tag() : id(0) {
  Warning("Instantiating default Tag Object. Should not happen.");
}

Tag::Tag(const MYSQL_ROW &dbrow) {
  unsigned int index = 0;
  id = atoll(dbrow[index++]);
  name = std::string(dbrow[index++]);
  created_on = StringToSystemTimePoint(dbrow ? dbrow[index] : ""); index++;
  created_by = dbrow[index] ? atoi(dbrow[index]) : 0; index++;
  last_assigned_on = StringToSystemTimePoint(dbrow[index] ? dbrow[index] : ""); index++;
}

/* If a zero or invalid p_id is passed, then the old default path will be assumed.  */
Tag::Tag(uint64_t p_id) : id(p_id) {
  if (id) {
    std::string sql = stringtf("SELECT `Id`, `Name`, `CreateDate`, `CreatedBy`, `LastAssignedOn` FROM `Tags` WHERE `Id`=%" PRIu64, id);
    Debug(4, "Loading Tag for %" PRIu64 " using %s", id, sql.c_str());
    zmDbRow dbrow;
    if (!dbrow.fetch(sql)) {
      Error("Unable to load tag for id %" PRIu64 ": %s", id, mysql_error(&dbconn));
    } else {
      unsigned int index = 0;
      id = atoll(dbrow[index++]);
      name = std::string(dbrow[index++]);
      created_on = StringToSystemTimePoint(dbrow[index] ? dbrow[index] : ""); index++;
      created_by = atoi(dbrow[index++]);
      last_assigned_on = StringToSystemTimePoint(dbrow[index] ? dbrow[index] : ""); index++;
      //created_on
      Debug(4, "Loaded Tag area %" PRIu64 " '%s'", id, name.c_str());
    }
  }
}

Tag::~Tag() {
}

Tag *Tag::find(const std::string &name) {
  std::string sql = stringtf("SELECT `Id`, `Name`, `CreateDate`, `CreatedBy`, `LastAssignedDate` FROM `Tags` WHERE `Name`='%s'",
      name.c_str());
  Debug(4, "Loading Tag using %s", sql.c_str());
  zmDbRow dbrow;
  if (!dbrow.fetch(sql)) {
    Error("Unable to load tag using %s: %s", sql.c_str(), mysql_error(&dbconn));
    return nullptr;
  }
  return new Tag(dbrow.mysql_row());
}

uint64_t Tag::save() {
  if (!id) {
    if (created_on.time_since_epoch() == Seconds(0)) created_on = std::chrono::system_clock::now();
    if (last_assigned_on.time_since_epoch() == Seconds(0)) last_assigned_on = created_on;

    std::string sql = stringtf("INSERT INTO `Tags` (`Name`, `CreateDate`, `LastAssignedDate`)"
       " VALUES ('%s', from_unixtime(%" PRId64 "), from_unixtime(%" PRId64 "))",
        name.c_str(),
        static_cast<int64>(std::chrono::system_clock::to_time_t(created_on)),
        static_cast<int64>(std::chrono::system_clock::to_time_t(last_assigned_on))
        );
    //do {
      id = zmDbDoInsert(sql);
    //} while (!id and !zm_terminate);
    return id;
  }
  if (last_assigned_on.time_since_epoch() == Seconds(0)) last_assigned_on = std::chrono::system_clock::now();
  std::string sql = stringtf("UPDATE `Tags` SET (`LastAssignedOn`) VALUES ('from_unixtime(%" PRId64 ")) WHERE Id=%" PRIu64,
        static_cast<int64>(std::chrono::system_clock::to_time_t(last_assigned_on)), id);
  return zmDbDo(sql); 
}
