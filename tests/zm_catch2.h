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

#ifndef ZONEMINDER_TESTS_ZM_CATCH2_H_
#define ZONEMINDER_TESTS_ZM_CATCH2_H_

#include "catch2/catch_all.hpp"

#include "zm_vector2.h"

inline std::ostream &operator<<(std::ostream &os, Vector2 const &value) {
  os << "{ X: " << value.x_ << ", Y: " << value.y_ << " }";
  return os;
}

#endif //ZONEMINDER_TESTS_ZM_CATCH2_H_
