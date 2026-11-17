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

#include "zm_tag.h"

TEST_CASE("Tag from a row with NULL dates", "[Tag]") {
  // Tags.CreateDate and LastAssignedDate may be NULL. The row constructor
  // checked the row pointer instead of the CreateDate column, so a NULL
  // CreateDate built a std::string from a null pointer.
  char id[] = "5";
  char name[] = "person";
  char *row[] = {id, name, nullptr, nullptr, nullptr};
  MYSQL_ROW dbrow = row;
  REQUIRE_NOTHROW(Tag(dbrow));
  Tag tag(dbrow);
  // An unset date is the epoch, which save() replaces with now
  REQUIRE(tag.LastAssignedOn().time_since_epoch() == Seconds(0));
}
