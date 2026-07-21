/*
 * ZoneMinder Event Tag class implementation
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

#include "zm_event_tag.h"

#include "zm_logger.h"
#include "zm_signal.h"
#include "zm_time.h"
#include "zm_utils.h"
#include <cstring>

Event_Tag::Event_Tag() : tag_id(0), event_id(0), assigned_by(0) {
  Warning("Instantiating default Event Tag Object. Should not happen.");
}

/*
CREATE TABLE `Events_Tags` (
  `TagId` bigint(20) unsigned NOT NULL,
  `EventId` bigint(20) unsigned NOT NULL,
  `AssignedDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `AssignedBy` int(10) unsigned,
  PRIMARY KEY (`TagId`, `EventId`),
  CONSTRAINT `Events_Tags_ibfk_1` FOREIGN KEY (`TagId`) REFERENCES `Tags` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `Events_Tags_ibfk_2` FOREIGN KEY (`EventId`) REFERENCES `Events` (`Id`) ON DELETE CASCADE
) ENGINE=@ZM_MYSQL_ENGINE@;
*/

Event_Tag::Event_Tag(const MYSQL_ROW &dbrow) {
  unsigned int index = 0;
  tag_id = atoll(dbrow[index++]);
  event_id = atoll(dbrow[index++]);
  assigned_on = StringToSystemTimePoint(dbrow[index++]);
  assigned_by = dbrow[index] ? atoi(dbrow[index]) : 0;
  index++;
}

Event_Tag::Event_Tag(uint64_t p_tag_id, uint64_t p_event_id, SystemTimePoint p_assigned_on, unsigned int p_assigned_by ) :
  tag_id(p_tag_id),
  event_id(p_event_id),
  assigned_on(p_assigned_on),
  assigned_by(p_assigned_by)
{
  /*
  if (tag_id and event_id) {
    std::string sql = stringtf("SELECT `TagId`, `EventId`, `AssignedDate`, `AssignedBy`"
       " FROM `Event_Tags` WHERE `TagId`=%" PRIu64 " AND EventId=%" PRIu64, tag_id, event_id);
    Debug(4, "Loading Tag using %s", sql.c_str());
    zmDbRow dbrow;
    if (!dbrow.fetch(sql)) {
      Error("Unable to load tag : %s", mysql_error(&dbconn));
    } else {
      unsigned int index = 0;
      tag_id = atoll(dbrow[index++]);
      event_id = atoll(dbrow[index++]);
      assigned_on = StringToSystemTimePoint(dbrow[index++]);
      assigned_by = atoi(dbrow[index++]);
    }
  }
  */
}

Event_Tag::~Event_Tag() {
}

int Event_Tag::save() {
  std::string sql = stringtf("INSERT INTO `Events_Tags` (`TagId`, `EventId`, `AssignedDate`, `AssignedBy`)"
      " VALUES (%" PRIu64 ", %" PRIu64 ", from_unixtime(%" PRId64 "), %d)",
      tag_id,
      event_id,
      static_cast<int64>(std::chrono::system_clock::to_time_t(assigned_on)),
      assigned_by
      );
  int rc;
  //do {
    rc = zmDbDoInsert(sql);
  //} while (!rc and !zm_terminate);
  return rc;
}
