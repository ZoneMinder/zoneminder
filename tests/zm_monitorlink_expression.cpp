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

#include "zm_monitorlink_expression.h"

#include <memory>
#include <string>

// Monitor::Load() reassigns linked_monitors_string on every reload while the
// MonitorLinkExpression built from it lives on, so the expression and the
// tokens it parses must not point into the caller's string. Only delimiters
// here: a monitor id token would load that monitor from the database. The
// string is longer than the small-string buffer so that it lives on the heap;
// before the fix AddressSanitizer reports a heap-use-after-free in parse().
TEST_CASE("MonitorLinkExpression does not refer to the caller's string", "[MonitorLink]") {
  std::unique_ptr<MonitorLinkExpression> expression;
  {
    std::string linked = "((((((((((((((((((((((((";
    expression = std::make_unique<MonitorLinkExpression>(linked);
    linked.assign(64, '|');
  }
  REQUIRE(expression->parse());
  MonitorLinkExpression::Result result = expression->result();
  REQUIRE_FALSE(result.success);
}
