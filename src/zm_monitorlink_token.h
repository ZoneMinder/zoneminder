

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

#ifndef ZM_MONITORLINK_TOKEN_H
#define ZM_MONITORLINK_TOKEN_H

#include "zm_monitor.h"

class Token {
 public:
  /**
    * enum class token_type
    *
    * Represents a token type. Supported types are logical operators, relational operators, parentheses and monitorlink
    */
  enum class [[ nodiscard ]] TokenType : std::uint8_t {
    unknown,
    monitorlink,
    logical_and,
    logical_or,
    logical_comma,
    lp,
    rp
  };

  /**
   * @class token
   *
   * Represents all tokens ('and', 'or', 'eq', ...).
   */
 private:
  using token_type_pair = std::pair<std::string_view, TokenType>;
  constexpr static std::array symbols {
    token_type_pair{ "&", TokenType::logical_and },
    token_type_pair{ "|", TokenType::logical_or  },
    token_type_pair{ ",", TokenType::logical_comma  }, // or
    token_type_pair{ "(", TokenType::lp          },
    token_type_pair{ ")", TokenType::rp          }
  };

  //constexpr TokenType to_token_type( std::string_view const value ) noexcept;
  constexpr TokenType to_token_type( std::string_view const value ) noexcept {
    auto find_matching {
      [ value ]( auto const & collection ) noexcept {
        return utils::find_if
        (
          std::cbegin( collection ),
          std::cend  ( collection ),
        [ value ]( auto && item ) {
          return item.first == value;
        }
        );
      }
    };

    auto const symbol{ find_matching(symbols) };
    if (symbol != std::cend(symbols)) {
      return symbol->second;
    }

    return TokenType::monitorlink;
  } // end constexpr TokenType to_token_type( std::string_view const value )

 public:

  Token(TokenType const type, std::string_view const value)
    : type_(type)
    , value_(value)
    , monitor_link_(nullptr) {
    if (type_ == TokenType::monitorlink) {
      auto colon_position = value_.find(':');
      unsigned int monitor_id = 0;
      unsigned int zone_id = 0;
      std::string monitor_name;
      std::string zone_name;

      if (colon_position != std::string::npos) {
        // Has a zone specification
        monitor_id = std::stoul(std::string(value_.substr(0, colon_position)));
        zone_id = std::stoul(std::string(value_.substr(colon_position+1, std::string::npos)));
      } else {
        monitor_id = std::stoul(std::string(value_));
      }
      Debug(1, "Have linked monitor %d zone %d", monitor_id, zone_id);

      std::shared_ptr<Monitor> monitor = Monitor::Load(monitor_id, false, Monitor::QUERY);
      monitor_link_ = new Monitor::MonitorLink(monitor, zone_id);
    } else {
      Debug( 1, "Not a monitor link value is %s", std::string(value_).c_str());
    }
  }

  constexpr Token() noexcept :
    type_(TokenType::unknown),
    value_(""),
    monitor_link_(nullptr)
  { }
  //Token( TokenType const type, std::string_view const value );

  constexpr Token( Token       && rhs ) noexcept = default;
  constexpr Token( Token const  & rhs ) noexcept = default;

  constexpr Token( TokenType const type ) noexcept
    : type_ ( type )
    , value_("")
    , monitor_link_(nullptr)
  {}

  Token( std::string_view const value ) noexcept
    : type_ (to_token_type(value))
    , value_(value)
    , monitor_link_(nullptr) {
    if (type_ == TokenType::monitorlink) {
      auto colon_position = value_.find(':');
      unsigned int monitor_id = 0;
      unsigned int zone_id = 0;
      std::string monitor_name;
      std::string zone_name;

      if (colon_position != std::string::npos) {
        // Has a zone specification
        monitor_id = std::stoul(std::string(value_.substr(0, colon_position)));
        zone_id = std::stoul(std::string(value_.substr(colon_position+1, std::string::npos)));
      } else {
        monitor_id = std::stoul(std::string(value_));
      }
      Debug(1, "Have linked monitor %d zone %d", monitor_id, zone_id);

      std::shared_ptr<Monitor> monitor = Monitor::Load(monitor_id, false, Monitor::QUERY);
      monitor_link_ = new Monitor::MonitorLink(monitor, zone_id);
    } else {
      Debug( 1, "Not a monitor link value is %s", std::string(value_).c_str());
    }
  }

  Token & operator=( Token       && rhs ) noexcept = default;
  Token & operator=( Token const  & rhs ) noexcept = default;

  [[ nodiscard ]] constexpr bool operator==( Token const & rhs ) const noexcept {
    return type_  == rhs.type_ && value_ == rhs.value_;
  }

  ~Token() noexcept = default;
  constexpr void type( TokenType const type ) noexcept {
    if ( type != type_ ) {
      type_  = type;
      value_ = "";//to_token_keyword( type );
    }

  }

  constexpr TokenType type() const noexcept { return type_; }

  constexpr void value( std::string_view const value ) noexcept {
    type_  = to_token_type( value );
    value_ = value;

  }

  [[ nodiscard ]] constexpr std::string_view value() const noexcept {
    return value_;
  }

  [[ nodiscard ]] constexpr bool is( TokenType const type ) const noexcept {
    return type_ == type;
  }

  [[ nodiscard ]] constexpr bool is_not( TokenType const type ) const noexcept {
    return type_ != type;
  }

  [[ nodiscard ]] constexpr bool is_one_of(
    TokenType const first,
    TokenType const second
  ) const noexcept {
    return is(first) || is(second);
  }

  [[ nodiscard ]] constexpr bool hasAlarmed() const {
    return (monitor_link_ && monitor_link_->connect() && monitor_link_->hasAlarmed());
  }

  [[ nodiscard ]] constexpr int score() const {
    if ( monitor_link_ ) {
      if (!monitor_link_->isConnected() ) {
        Debug(1, "connecting");
        if (!monitor_link_->connect()) {
          Debug(1, "failed");
          return 0;
        }
      }
      int s = monitor_link_->score();
      Debug(1, "Score from monitor %s is %d", monitor_link_->Name(), s);
      return s;
    }
    return 0;
  }

 private:
  TokenType         type_;
  std::string_view  value_;
  Monitor::MonitorLink       *monitor_link_;
};
#endif
