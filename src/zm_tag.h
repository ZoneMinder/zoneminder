/*
 * ZoneMinder Tag Class Interface
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

#ifndef ZM_TAG_H
#define ZM_TAG_H

#include "zm_db.h"
#include "zm_time.h"
#include <string>

/*
CREATE TABLE `Tags` (
  `Id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
  `CreateDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `CreatedBy` int(10) unsigned,
  `LastAssignedDate` dateTime,
  PRIMARY KEY (`Id`),
  UNIQUE(`Name`)
);
*/
class Tag {
  private:
    uint64_t	id;
    std::string name;
    SystemTimePoint created_on;
    unsigned int    created_by;
    SystemTimePoint last_assigned_on;

  public:
    Tag();
    explicit Tag( const MYSQL_ROW &dbrow );
    explicit Tag( uint64_t p_id );
    ~Tag();

    uint64_t	Id() const { return id; };
    const std::string &Name() const { return name; };
    const std::string &Name(const std::string p_name) { return name = p_name; };
    SystemTimePoint LastAssignedOn() { return last_assigned_on; };
    SystemTimePoint LastAssignedOn(SystemTimePoint p_last_assigned_on) { return last_assigned_on = p_last_assigned_on; };

    uint64_t save();
    static Tag *find(const std::string &name);
};

#endif // ZM_TAG_H
