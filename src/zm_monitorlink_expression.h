//
// ZoneMinder Monitor Class Interfaces
// Copyright (C) 2022 ZoneMinder Inc
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

#ifndef ZM_MONITORLINK_EVAL_H
#define ZM_MONITORLINK_EVAL_H

#include "zm_utils.h"
#include "zm_monitorlink_token.h"

#include <istream>
#include <string>

class MonitorLinkExpression {

 public:
  /**
   * struct node
   *
   * Represents the tree node containing references to left and right child nodes
   * as well as the token that the node represents in the actual expression tree.
   */
  class Node {
   public:
    Token token{ Token::TokenType::unknown };

    std::unique_ptr< Node > left { nullptr };
    std::unique_ptr< Node > right{ nullptr };

    constexpr Node() noexcept = default;

    Node( Node       && rhs ) noexcept = default;
    Node( Node const  & rhs ) noexcept = delete;

    constexpr Node(Token::TokenType const type) noexcept : token(type) {}
    constexpr Node(Token const &token) noexcept : token(token) {}

    Node & operator=( Node && rhs ) noexcept = default;
    Node & operator=( Node const  & rhs ) noexcept = delete;

    ~Node() noexcept = default;
  };

  struct Result {
    /**
     * True if evaluation process is successful. Otherwise, false.
     */
    bool success{ false };

    /**
     * Message in case of the fault.
     */
    std::string_view message{};
    int score {-1};
  };

 public:
  using Tokens = std::vector< Token >;
 private:
  std::unique_ptr<Node> tree_;
  int score_;

  static std::unique_ptr< Node > parse_and_operation       ( Tokens const & tokens, std::size_t & current );
  static std::unique_ptr< Node > parse_or_operation       ( Tokens const & tokens, std::size_t & current );
  static std::unique_ptr< Node > parse_parentheses         ( Tokens const & tokens, std::size_t & current );
  static std::unique_ptr< Node > parse_terminal            ( Tokens const & tokens, std::size_t & current );
  static std::unique_ptr<Node> parse_expression( Tokens const & tokens, std::size_t & current );

  static inline bool has_unused( Tokens const & tokens, std::size_t const current ) noexcept {
    return current < std::size( tokens );
  }

  static Result visit(Node const &node);
  static Result visit_logical_and(Node const &node);
  static Result visit_logical_or(Node const &node);

 public:
  MonitorLinkExpression();
  MonitorLinkExpression(const std::string &expression) : expression_(expression) {
  };
  int score() { return score_; }
  bool evaluate();
  const Result result();
  bool parse();
 private:
  const std::string_view delimiters_ = "|&(),";
  std::string_view expression_;
};

#endif
