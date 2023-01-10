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
#include <queue>
#include <string>
#include <thread>

#include <unordered_map>
#include <functional>
#include <stdexcept>
#include <iostream>

#pragma GCC diagnostic ignored "-Wuninitialized"
#include "soci/soci.h"
#include "soci/version.h"
#pragma GCC diagnostic warning "-Wuninitialized"

#include "zm_db_types.h"

class zmDb
{
private:
  int dbLevel;

protected:
  std::recursive_mutex db_mutex;
  soci::session db;
  std::chrono::steady_clock::time_point lastConnectionCheck;
  std::unordered_map<int, soci::statement*> mapStatements;
  std::unordered_map<int, std::string> autoIncrementTable;

  void disableDatabaseLog();
  void restoreDatabaseLog();

  template <typename T>
  T getFromResult(soci::rowset_iterator<soci::row>* result_iter, const int position) {
    try {
      return (*result_iter)->get<T>(position);
    }
    catch (soci::soci_error const & e)
    {
      std::cerr << "Database exception: " << e.what() << std::endl;
#if SOCI_VERSION >= 400003 // before version 4.0.3 error categories were not implemented
      switch( e.get_error_category() ){
        case soci::soci_error::error_category::invalid_statement:
        case soci::soci_error::error_category::no_privilege:
        case soci::soci_error::error_category::system_error:
          exit(-1);
        default:
          return T();
      }
#endif
    }
    return T();
  }

  template <typename T>
  T getFromResult(soci::rowset_iterator<soci::row>* result_iter, const std::string& name) {
    try {
      return (*result_iter)->get<T>(realColumnName(name));
    }
    catch (soci::soci_error const & e)
    {
      std::cerr << "Database exception: " << e.what() << std::endl;
#if SOCI_VERSION >= 400003 // before version 4.0.3 error categories were not implemented
      switch( e.get_error_category() ){
        case soci::soci_error::error_category::invalid_statement:
        case soci::soci_error::error_category::no_privilege:
        case soci::soci_error::error_category::system_error:
          exit(-1);
        default:
          return T();
      }
#endif
    }
    return T();
  }

  // unsigned int is a special case in that representation actually differs between mysql and postgresql
  virtual unsigned int getUnsignedIntColumn(soci::rowset_iterator<soci::row>* result_iter, const int position){
    return (*result_iter)->get<unsigned int>(position);
  };

  // unsigned int is a special case in that representation actually differs between mysql and postgresql
  virtual unsigned int getUnsignedIntColumn(soci::rowset_iterator<soci::row>* result_iter, const std::string& name){
    return (*result_iter)->get<unsigned int>(realColumnName(name));
  };


public:
  zmDb();
  virtual ~zmDb();

  virtual uint64_t lastInsertID(const zmDbQueryID&) = 0;
  virtual std::string realColumnName(const std::string&) = 0;

  virtual bool connected();
  virtual void lock();
  virtual void unlock();

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

  zmDbQuery& operator=(zmDbQuery other)
  {
    db = other.db;
    id = other.id;
    stmt = other.stmt;
    exitOnError = other.exitOnError;
    result = other.result;
    result_iter = other.result_iter;
    result_iter_end = other.result_iter_end;
    deferred = std::vector< std::function<void ()> >(other.deferred.begin(), other.deferred.end());
    executed = other.executed;
    return *this;
  }

  virtual ~zmDbQuery();

  // Bind single value
  template <typename T>
  void bind(const std::string &name, const T &value)
  {
    if (stmt == nullptr)
      return;

    stmt->exchange(soci::use<T>(value, db->realColumnName(name)));
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

    return db->getFromResult<T>( result_iter, name );
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

    return db->getFromResult<T>( result_iter, position );
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
    return (*result_iter)->get_indicator(db->realColumnName(name)) == soci::indicator::i_ok;
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
