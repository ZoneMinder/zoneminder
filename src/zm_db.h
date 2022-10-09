//
// ZoneMinder Core Interfaces, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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

#ifndef ZM_DB_H
#define ZM_DB_H

#include <condition_variable>
#include <mutex>
#include <mysql/mysql.h>
#include <mysql/mysqld_error.h>
#include <queue>
#include <string>
#include <thread>

#include <unordered_map>
#include <functional>
#include <stdexcept>

#include "soci.h"

#include "zm_db_types.h"

class zmDb
{
protected:
  std::mutex db_mutex;
  soci::session db;
  std::unordered_map<int, soci::statement*> mapStatements;
  std::unordered_map<int, std::string> autoIncrementTable;

public:
  zmDb();
  virtual ~zmDb();

  bool connected() {
    return db.is_connected();
  }
  virtual uint64_t lastInsertID(const zmDbQueryID&) = 0;

  friend class zmDbQuery;
};

class zmDbQuery
{
protected:
  zmDb *db;
  zmDbQueryID id;
  soci::statement *stmt;
  bool exitOnError;
  soci::row* result;
  soci::rowset_iterator<soci::row>* result_iter;
  soci::rowset_iterator<soci::row>* result_iter_end;
  std::vector< std::function<void ()> > deferred;
  bool executed;

public:
  zmDbQuery(const zmDbQueryID& id = LAST_QUERY, bool exitOnError = false);

  zmDbQuery(const zmDbQuery& other) : db(other.db), 
    id(other.id), stmt(other.stmt), exitOnError(other.exitOnError),
    result(other.result), result_iter(other.result_iter), result_iter_end(other.result_iter_end),
    deferred( other.deferred.begin(), other.deferred.end() ),
    executed(other.executed)
    {
      if( result != nullptr )
        throw new std::runtime_error( "Invalid copy operation during result examination" );
    };

  virtual ~zmDbQuery();

  // Bind single value
  template <typename T>
  void bind(const std::string &name, const T &value)
  {
    if (stmt == nullptr)
      return;

    stmt->exchange(soci::use<T>(value, name));
    return;
  }

  template <typename T>
  void bind(const T &value)
  {
    if (stmt == nullptr)
      return;

    stmt->exchange(soci::use<T>(value));
    return;
  }

  // Bind vector value
  template <typename T>
  void bindVec(const std::vector<T> &values)
  {
    if (stmt == nullptr)
      return;

    for( auto& it : values )
      stmt->exchange(soci::use<T>(it));
    return;
  }

  // Get values from results
  template <typename T>
  T get(const std::string &name)
  {
    if (stmt == nullptr || result == nullptr) {
      return T();
    }
    if( result_iter == nullptr || *result_iter == *result_iter_end ) {
      return T();
    }

    return (*result_iter)->get<T>(name);
  }

  template <typename T>
  T get(const int position)
  {
    if (stmt == nullptr || result == nullptr) {
      return T();
    }
    if( result_iter == nullptr || *result_iter == *result_iter_end ) {
      return T();
    }

    return (*result_iter)->get<T>(position);
  }

  // Get list of values via vector
  template <typename U>
  std::vector<U> getVec(const std::string &name)
  {
    if (stmt == nullptr || result == nullptr) {
      return U();
    }

    std::vector<U> outvector = new std::vector<U>( 50 );

    // soci::rowset<U> results = (*result_iter)->get<soci::rowset<U>>(name);

    // for (auto it = results.cbegin(); it != results.cend(); ++it)
    // {
    //   outvector.push_back( it->second );
    // }

    return outvector;
  }

  // Return if field is present in result
  bool has(std::size_t & pos) {
    if (stmt == nullptr || result == nullptr ) {
      return false;
    }
    if( result_iter == nullptr || *result_iter == *result_iter_end ) {
      return false;
    }
    return (*result_iter)->get_indicator(pos) == soci::indicator::i_ok;
  }
  bool has(std::string const & name) {
    if (stmt == nullptr || result == nullptr ) {
      return false;
    }
    if( result_iter == nullptr || *result_iter == *result_iter_end ) {
      return false;
    }
    return (*result_iter)->get_indicator(name) == soci::indicator::i_ok;
  }

  zmDbQueryID getID() { return id; };

  virtual void run(bool data_exchange);
  virtual bool next();
  virtual void reset();

  virtual void fetchOne();
  virtual uint64_t insert();
  virtual uint64_t update();

  virtual int affectedRows();

  void deferOnClose( std::function<void()> );
};

bool zmDbIsConnected();
bool zmDbConnect();
void zmDbClose();

#endif // ZM_DB_H
