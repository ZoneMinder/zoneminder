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

TEST_CASE("MonitorLinkExpression rejects malformed syntax and unconsumed tokens without crashing", "[MonitorLink]") {
  SECTION("leading right parenthesis does not dereference null left operand") {
    MonitorLinkExpression expression(") abc");
    REQUIRE(expression.parse());
    REQUIRE_FALSE(expression.result().success);
  }

  SECTION("empty parentheses do not crash") {
    MonitorLinkExpression expression("()");
    REQUIRE(expression.parse());
    REQUIRE_FALSE(expression.result().success);
  }

  SECTION("single operator token is rejected") {
    MonitorLinkExpression expression("&");
    REQUIRE(expression.parse());
    REQUIRE_FALSE(expression.result().success);
  }

  SECTION("unbalanced trailing parenthesis is rejected") {
    MonitorLinkExpression expression("abc )");
    REQUIRE(expression.parse());
    REQUIRE_FALSE(expression.result().success);
  }
}

// Non numeric ids load no monitor (no database) and score 0, but still count
// as links, so a well formed expression of them evaluates successfully.
TEST_CASE("MonitorLinkExpression ignores empty comma separated terms", "[MonitorLink]") {
  const std::string linked = GENERATE(
      std::string("a,b,"),
      std::string("a,,b"),
      std::string(",a,b"),
      std::string("a,b,,"),
      std::string("(a,),b"),
      std::string("a,b"));
  CAPTURE(linked);
  MonitorLinkExpression expression(linked);
  REQUIRE(expression.parse());
  REQUIRE(expression.result().success);
}

// Spaces typed around operators and brackets are not part of a link. Before,
// the space between ")" and "|" was read as a link token and the leftover
// token discarded the whole tree, so the expression never triggered.
TEST_CASE("MonitorLinkExpression ignores spaces around operators and brackets", "[MonitorLink]") {
  const std::string linked = GENERATE(
      std::string("(a) | (b)"),
      std::string("(a | b) & c"),
      std::string(" a , b "),
      std::string("a & (b)\t"),
      std::string("( a )"));
  CAPTURE(linked);
  MonitorLinkExpression expression(linked);
  REQUIRE(expression.parse());
  REQUIRE(expression.result().success);
}
