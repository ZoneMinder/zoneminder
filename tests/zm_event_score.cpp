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
