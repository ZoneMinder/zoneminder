
#include "zm_monitorlink_expression.h"


bool MonitorLinkExpression::parse() {

  Tokens tokens;
  auto first = std::begin(expression_);

  Debug(1, "Parsing %s", expression_.c_str());
  // First we tokenize
  while (first != std::end(expression_)) {
    auto const second = std::find_first_of(
        first, std::cend(expression_),
        std::cbegin(delimiters_), std::cend(delimiters_)
        );

    if (first != second) {
      std::string t = expression_.substr(
            std::distance(std::begin(expression_), first),
            std::distance(first, second)
            );
      Debug(1, "Have a token %s", t.c_str());

      tokens.emplace_back(t);
    }

    if (std::end(expression_) == second) {
      Debug(1, "Breaking");
      break;
    }

    std::string delim { second, 1 };
    if (!delim.empty()) {
      Debug(1, "Have delim %s", delim.c_str());
      tokens.emplace_back(delim);
    }
    first = std::next(second);
  }

  if (tokens.empty()) return false;

  std::size_t current{ 0u };

  // Then we parse the tokens into a tree
  tree_ = parse_expression(tokens, current);
  return true;
}

bool MonitorLinkExpression::evaluate() {
  MonitorLinkExpression::result result = this->visit(*tree_);
  if (!result.success) {
    Warning("%s", result.message.c_str());
    return false;
  } 
  return true;
}

MonitorLinkExpression::result
MonitorLinkExpression::visit(Node const & node) {
  if ( nullptr == node.left || nullptr == node.right ) {
    return { false, "Missing operand" };
  }

  switch ( node.token.type() ) {
    case Token::TokenType::logical_and:
      return visit_logical_and(node);
    case Token::TokenType::logical_or :
    case Token::TokenType::logical_comma :
      return visit_logical_or(node);
    default:
      return { false, "Unknown token type" };
  }
}

MonitorLinkExpression::result
MonitorLinkExpression::visit_logical_and(MonitorLinkExpression::Node const & node) 
{
  auto const left  { visit(*node.left) };
  auto const right { visit(*node.right) };

  // always pick the error message closer to the beginning of the expression
  auto const message {
    left.message.empty() ? right.message : left.message
  };

  return { left.success && right.success, message };
}

MonitorLinkExpression::result
MonitorLinkExpression::visit_logical_or(MonitorLinkExpression::Node const & node) 
{
  auto const left  { visit(*node.left) };
  auto const right { visit(*node.right) };

  // always pick the error message closer to the beginning of the expression
  auto const message {
    left.message.empty() ? right.message : left.message
  };

  return { left.success || right.success, message };
}

std::unique_ptr<MonitorLinkExpression::Node> 
MonitorLinkExpression::parse_expression( Tokens const & tokens, std::size_t & current ) {
  auto left{ parse_and_operation(tokens, current) };

  if (
      has_unused(tokens, current) 
      and
      tokens[current].is_not(Token::TokenType::logical_or)
     ) {
    return left;
  }

  while (
      has_unused(tokens, current)
      and
      tokens[current].is(Token::TokenType::logical_or)
      ) {
    ++current;
    auto logical_or{ std::make_unique<Node>(Token::TokenType::logical_or) };

    auto right{ parse_and_operation( tokens, current ) };
    if ( right == nullptr ) { return nullptr; }

    logical_or->left  = std::move( left  );
    logical_or->right = std::move( right );
    left = std::move( logical_or );
  }

  return left;
}

std::unique_ptr<MonitorLinkExpression::Node> 
MonitorLinkExpression::parse_and_operation( Tokens const & tokens, std::size_t & current ) {
  auto left{ parse_parentheses( tokens, current ) };

  if (left == nullptr) return nullptr;

  while (
      has_unused(tokens, current)
      and
      tokens[current].is(Token::TokenType::logical_and)
      ) {
    ++current;

    auto logical_and{ std::make_unique< Node >(Token::TokenType::logical_and) };

    auto right{parse_parentheses(tokens, current) };
    if (right == nullptr) return nullptr;

    logical_and->left  = std::move(left);
    logical_and->right = std::move(right);
    left = std::move(logical_and);
  }

  return left;
}

std::unique_ptr<MonitorLinkExpression::Node> 
MonitorLinkExpression::parse_parentheses(Tokens const & tokens, std::size_t & current) {
  if (!has_unused(tokens, current)) return nullptr;

  if (tokens[current].is(Token::TokenType::lp)) {
    ++current;
    auto expression{ parse_expression(tokens, current) };
    if (!has_unused(tokens, current)) return nullptr;

    if (tokens[ current++ ].is(Token::TokenType::rp)) {
      return expression;
    }
  }

  return nullptr;
}
