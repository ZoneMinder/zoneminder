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

#include "soci.h"

typedef enum
{
  /* SELECT QUERIES */
  SELECT_SERVER_ID_WITH_NAME = 0,
  SELECT_SERVER_NAME_WITH_ID,
  SELECT_GROUP_WITH_ID,
  SELECT_MAX_EVENTS_ID_WITH_MONITORID_AND_FRAMES_NOT_ZERO,
  SELECT_GROUPS_PARENT_OF_MONITOR_ID,
  SELECT_MONITOR_ID_REMOTE_RTSP_AND_RTPUNI,
  SELECT_STORAGE_WITH_ID,
  SELECT_USER_AND_DATA_WITH_USERNAME_ENABLED,
  SELECT_USER_AND_DATA_PLUS_TOKEN_WITH_USERNAME_ENABLED,
  SELECT_ALL_ACTIVE_STATES_ID,
  SELECT_ALL_CONFIGS,
  SELECT_ALL_STORAGE_ID_DIFFERENT_THAN,
  SELECT_ALL_STORAGE_ID_WITH_SERVERID_NULL,
  SELECT_ALL_EVENTS_ID_WITH_MONITORID_EQUAL,
  SELECT_ALL_FRAMES_WITH_DATA_OF_EVENT_WITH_ID,
  SELECT_ALL_FRAMES_OF_EVENT_WITH_ID,
  SELECT_ALL_EVENTS_ID_WITH_MONITORID_AND_ID_LESSER_THAN,
  SELECT_ALL_EVENTS_ID_WITH_MONITORID_AND_ID_LARGER_THAN,
  SELECT_ALL_MONITORS_DATA_OFFICIAL,
  SELECT_ALL_USERS_AND_DATA_ENABLED,
  SELECT_ALL_ZONES_WITH_MONITORID_EQUAL_TO,
  SELECT_ALL_MONITORS_DATA,

  /* UPDATE QUERIES */
  UPDATE_NEW_EVENT_WITH_ID,
  UPDATE_EVENT_WITH_ID_SET_NOTES,
  UPDATE_EVENT_WITH_ID_SET_SCORE,
  UPDATE_EVENT_WITH_ID_SET_STORAGEID,
  UPDATE_EVENT_WITH_ID_SET_SAVEJPEGS,
  UPDATE_MONITORSTATUS_WITH_MONITORID_SET_CAPTUREFPS,

  /* INSERT QUERIES */
  INSERT_EVENTS,
  INSERT_FRAMES,
  INSERT_STATS_SINGLE,
  INSERT_STATS_MULTIPLE,
  INSERT_LOGS,
  INSERT_MONITOR_STATUS_RUNNING,
  INSERT_MONITOR_STATUS_CONNECTED,
  INSERT_MONITOR_STATUS_NOTRUNNING,

  LAST_QUERY
} zmDbQueryID;

class zmDb;
class zmDbQuery;

class zmDb
{
protected:
  std::mutex db_mutex;
  soci::session db;
  std::unordered_map<int, soci::statement*> mapStatements;

public:
  zmDb(){};
  virtual ~zmDb();

  bool connected() {
    return db.is_connected();
  }

  friend class zmDbQuery;
};

class zmDbQuery
{
protected:
  zmDb *db;
  soci::statement *stmt;
  soci::row* result;

public:
  zmDbQuery(const zmDbQueryID& id);
  virtual ~zmDbQuery();

  zmDbQuery(const zmDbQuery& other) : db(other.db), stmt(other.stmt) {}

  template <typename T>
  zmDbQuery& bind(const std::string &name, const T &value)
  {
    if (stmt == nullptr)
      return *this;

    stmt->exchange(soci::use<T>(value, name));
    return *this;
  }

  template <typename T>
  T get(const std::string &name)
  {
    if (stmt == nullptr || result == nullptr) {
      T zero;
      return zero;
    }

    return result->get<T>(name);
  }

  virtual zmDbQuery &run(bool data_exchange);
  virtual bool next();
  virtual void reset();

  virtual zmDbQuery &fetchOne();
};

class zmDbQueue
{
private:
  std::queue<std::string> mQueue;
  std::thread mThread;
  std::mutex mMutex;
  std::condition_variable mCondition;
  bool mTerminate;

public:
  zmDbQueue();
  ~zmDbQueue();
  void push(std::string &&sql);
  void process();
  void stop();
};

// extern MYSQL dbconn;
// extern std::mutex db_mutex;
// extern zmDbQueue  dbQueue;

// extern bool zmDbConnected;

bool zmDbIsConnected();
bool zmDbConnect(const std::string &backend);
zmDbQuery &zmDbGetQuery(const zmDbQueryID &id);
void zmDbClose();

#endif // ZM_DB_H
