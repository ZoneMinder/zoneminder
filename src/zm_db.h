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
  soci::row* result;
  bool exitOnError;

public:
  zmDbQuery(const zmDbQueryID& id = LAST_QUERY, bool exitOnError = false);

  zmDbQuery(const zmDbQuery& other) : db(other.db), 
    id(other.id), stmt(other.stmt), exitOnError(other.exitOnError) {};

  virtual ~zmDbQuery();

  // Bind single value
  template <typename T>
  zmDbQuery& bind(const std::string &name, const T &value)
  {
    if (stmt == nullptr)
      return *this;

    stmt->exchange(soci::use<T>(value, name));
    return *this;
  }

  template <typename T>
  zmDbQuery& bind(const T &value)
  {
    if (stmt == nullptr)
      return *this;

    stmt->exchange(soci::use<T>(value));
    return *this;
  }

  // Bind vector value
  template <typename T>
  zmDbQuery& bindVec(const std::vector<T> &values)
  {
    if (stmt == nullptr)
      return *this;

    for( auto& it : values )
      stmt->exchange(soci::use<T>(it));
    return *this;
  }

  // Get values from results
  template <typename T>
  T get(const std::string &name)
  {
    if (stmt == nullptr || result == nullptr) {
      return T();
    }

    return result->get<T>(name);
  }

  template <typename T>
  T get(const int position)
  {
    if (stmt == nullptr || result == nullptr) {
      return T();
    }

    return result->get<T>(position);
  }

  // Get list of values via vector
  template <typename U>
  std::vector<U> getVec(const std::string &name)
  {
    if (stmt == nullptr || result == nullptr) {
      return U();
    }

    std::vector<U> outvector;
    soci::rowset<U> results = result->get<soci::rowset<U>>(name);

    for (auto it = results.cbegin(); it != results.cend(); ++it)
    {
      outvector.push_back( it->second );
    }

    return outvector;
  }

  // Return if field is present in result
  bool has(std::size_t & pos) {
    if (stmt == nullptr || result == nullptr) {
      return false;
    }
    return result->get_indicator(pos) == soci::indicator::i_ok;
  }
  bool has(std::string const & name) {
    if (stmt == nullptr || result == nullptr) {
      return false;
    }
    return result->get_indicator(name) == soci::indicator::i_ok;
  }

  zmDbQueryID getID() { return id; };

  virtual zmDbQuery &run(bool data_exchange);
  virtual bool next();
  virtual void reset();

  virtual zmDbQuery &fetchOne();
  virtual uint64_t insert();
  virtual uint64_t update();

  virtual int affectedRows();

  void deferOnClose( std::function<void()> );
};

class zmDbQueue
{
private:
  std::queue<zmDbQuery> mQueue;
  std::thread mThread;
  std::condition_variable mCondition;
  bool mTerminate;

public:
  zmDbQueue();
  ~zmDbQueue();
  void push(zmDbQuery &&query);
  void process();
  void stop();

  static void pushToQueue( zmDbQuery &&query );
};

// extern MYSQL dbconn;
// extern std::mutex db_mutex;
// extern zmDbQueue  dbQueue;

// extern bool zmDbConnected;

bool zmDbIsConnected();
bool zmDbConnect();
void zmDbClose();

#endif // ZM_DB_H
