
#include "zm_monitorlink_expression.h"


bool MonitorLinkExpression::parse() {

  Tokens tokens;
  auto first = std::begin(expression_);

  Debug(1, "Parsing %s", std::string(expression_).c_str());
  // First we tokenize
  while (first != std::end(expression_)) {
    auto const second = std::find_first_of(
                          first, std::cend(expression_),
                          std::cbegin(delimiters_), std::cend(delimiters_)
                        );

    if (first != second) {
      std::string_view t = expression_.substr(
                             std::distance(std::begin(expression_), first),
                             std::distance(first, second)
                           );
      Debug(1, "Have a token %s", std::string(t).c_str());

      tokens.emplace_back(t);
    }

    if (std::end(expression_) == second) {
      Debug(1, "Breaking");
      break;
    }

    std::string_view delim { second, 1 };
    if (!delim.empty()) {
      Debug(1, "Have delim %s", std::string(delim).c_str());
      tokens.emplace_back(delim);
    }
    first = std::next(second);
  }

  if (tokens.empty()) {
    Debug(1, "No tokens?");
    return false;
  }

  std::size_t current{ 0u };

  // Then we parse the tokens into a tree
  tree_ = parse_expression(tokens, current);
  return true;
}

bool MonitorLinkExpression::evaluate() {
  if (!tree_) {
    Debug(1, "No tree");
    return false;
  }
  MonitorLinkExpression::Result result = this->visit(*tree_);
  if (!result.success) {
    Warning("%s", std::string(result.message).c_str());
    return false;
  }
  return result.score > 0;
}

const MonitorLinkExpression::Result MonitorLinkExpression::result() {
  if (!tree_) {
    Debug(1, "No tree");
    MonitorLinkExpression::Result result;
    return result;
  }
  return this->visit(*tree_);
}

MonitorLinkExpression::Result MonitorLinkExpression::visit(Node const & node) {
  Debug(1, "visit: Node: %p Token: %d value %s",
        &node,
        static_cast<int>(node.token.type()),
        std::string(node.token.value()).c_str()
       );
  if (node.token.type() == Token::TokenType::monitorlink) {
    Debug(1, "Have monitorlink, return true, value %d", node.token.score());
    return { true, "", node.token.score() };
  } else if (nullptr == node.left || nullptr == node.right) {
    return { false, "Missing operand", 0 };
  }

  switch (node.token.type()) {
  case Token::TokenType::logical_and:
    Debug(1, "and");
    return visit_logical_and(node);
  case Token::TokenType::logical_or :
    Debug(1, "or");
    return visit_logical_or(node);
  case Token::TokenType::logical_comma :
    Debug(1, "comma");
    return visit_logical_or(node);
  default:
    Debug(1, "unknown");
    return { false, "Unknown token type" };
  }
}

MonitorLinkExpression::Result
MonitorLinkExpression::visit_logical_and(MonitorLinkExpression::Node const & node) {
  auto const left  { visit(*node.left) };
  auto const right { visit(*node.right) };

  // always pick the error message closer to the beginning of the expression
  auto const message {
    left.message.empty() ? right.message : left.message
  };

  Debug(1, "AND left score %d right score %d", left.score, right.score);
  return { left.success && right.success, message,
           ((left.score and right.score) ? left.score + right.score : 0)
         };
}

MonitorLinkExpression::Result
MonitorLinkExpression::visit_logical_or(MonitorLinkExpression::Node const & node) {
  auto const left  { visit(*node.left) };
  auto const right { visit(*node.right) };

  // always pick the error message closer to the beginning of the expression
  auto const message {
    left.message.empty() ? right.message : left.message
  };

  Debug(1, "Or left score %d right score %d", left.score, right.score);
  return {
    left.success || right.success,
    message,
    ((left.score or right.score) ? left.score + right.score : 0)
  };
}

std::unique_ptr<MonitorLinkExpression::Node>
MonitorLinkExpression::parse_expression( Tokens const & tokens, std::size_t & current ) {
  if (tokens.size() == 1) {
    Debug(1, "Special case monitorlink");
    // Special case, must be a monitorlink
    return std::make_unique<Node>(tokens[0]);
  }

  // First token could me a parenthesis or monitorlink.  Otherwise invalid.

  auto left{ parse_and_operation(tokens, current) };

  if (
    has_unused(tokens, current)
    and
    tokens[current].is_not(Token::TokenType::logical_or)
    and
    tokens[current].is_not(Token::TokenType::logical_comma)
  ) {
    Debug(1, "parse_expression: not or, Returning left %s", std::string(left->token.value()).c_str());
    return left;
  }

  /*
  if (tokens[current].is(Token::TokenType::monitorlink)) {
    Debug(1, "Left is a monitorlink");
    left = std::make_unique<Node>(tokens[current]);
    current++;
  } else {
    Debug(1, "Left is not a monitorlink, parsing and");
    left = parse_and_operation(tokens, current);
    // invalid
    //return nullptr;
  }
  */

  while (has_unused(tokens, current) and
         (
           tokens[current].is(Token::TokenType::logical_or)
           or
           tokens[current].is(Token::TokenType::logical_comma)
         )
        ) {
    Debug(1, "Have or adding it");

    auto logical_or{ std::make_unique<Node>( Token::TokenType::logical_or ) };
    current++;

    auto right{ parse_and_operation( tokens, current ) };
    if (right == nullptr) {
      Debug(1, "null from right side");
      return nullptr;
    }

    logical_or->left  = std::move( left  );
    logical_or->right = std::move( right );
    left = std::move( logical_or );
  }

  return left;
}

std::unique_ptr<MonitorLinkExpression::Node>
MonitorLinkExpression::parse_and_operation( Tokens const & tokens, std::size_t & current ) {
  auto left{ parse_parentheses( tokens, current ) };

  if (left == nullptr) {
    Debug(1, "null from parse_parenteses, adding a left.");
    //left = std::make_unique< Node >(Token::TokenType::lp);
    return nullptr;
  }

  while (
    has_unused(tokens, current)
    and
    tokens[current].is(Token::TokenType::logical_and)
  ) {
    ++current;

    auto logical_and{ std::make_unique< Node >(Token::TokenType::logical_and) };

    auto right{parse_parentheses(tokens, current) };
    if (right == nullptr) {
      // No right parentheses.  Add it?
      Debug(1, "No right parenthesis, adding it");
      //right = std::make_unique< Node >(Token::TokenType::rp);
      return nullptr;
    }

    logical_and->left  = std::move(left);
    logical_and->right = std::move(right);
    left = std::move(logical_and);
  }

  return left;
}

std::unique_ptr<MonitorLinkExpression::Node>
MonitorLinkExpression::parse_parentheses(Tokens const &tokens, std::size_t &current) {
  if (!has_unused(tokens, current)) {
    Debug(1, "No unused...");
    return nullptr;
  }

  if (tokens[current].is(Token::TokenType::lp)) {
    ++current;
    auto expression{ parse_expression(tokens, current) };

    // Because we are parsing a left, there SHOULD be a remaining right. If not, invalid.
    if (!has_unused(tokens, current)) return nullptr;

    if (tokens[ current++ ].is(Token::TokenType::rp)) {
      return expression;
    }
  } else if (tokens[current].is(Token::TokenType::monitorlink)) {
    Debug(1, "Have monitorlink, returning it");
    auto link {std::make_unique<Node>(tokens[current])};
    current++;
    return link;
  }

  return nullptr;
}
