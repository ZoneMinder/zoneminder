/*
 * ZoneMinder Event Tag Class Interface
 * Copyright (C) 2025 ZoneMinder LLC
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

#ifndef ZM_EVENT_TAG_H
#define ZM_EVENT_TAG_H

#include "zm_db.h"
#include "zm_time.h"
#include <string>

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

class Event_Tag {
  private:
    uint64_t	tag_id;
    uint64_t	event_id;
    SystemTimePoint assigned_on;
    unsigned int    assigned_by;

  public:
    Event_Tag();
    explicit Event_Tag( const MYSQL_ROW &dbrow );
    explicit Event_Tag(uint64_t p_tag_id, uint64_t p_event_id, SystemTimePoint p_assigned_on, unsigned int assigned_by=0);
    ~Event_Tag();

    uint64_t TagId() const { return tag_id; };
    uint64_t EventId() const { return event_id; };
    SystemTimePoint AssignedOn() { return assigned_on; };
    SystemTimePoint AssignedOn(SystemTimePoint p_assigned_on) { return assigned_on = p_assigned_on; };
    unsigned int AssignedBy() { return assigned_by; };
    unsigned int AssignedBy(unsigned int p_assigned_by) { return assigned_by = p_assigned_by; };

    int save();
};

#endif // ZM_EVENT_TAG_H
