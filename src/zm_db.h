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

class zmDbQueue {
 private:
  std::queue<std::string> mQueue;
  std::thread             mThread;
  std::mutex              mMutex;
  std::condition_variable mCondition;
  bool                    mTerminate;
 public:
  zmDbQueue();
  ~zmDbQueue();
  void push(std::string &&sql);
  void process();
  void stop();
};

class zmDbRow {
 private:
  MYSQL_RES *result_set;
  MYSQL_ROW row;
 public:
  zmDbRow() : result_set(nullptr), row(nullptr) { };
  MYSQL_RES *fetch(const std::string &query);
  zmDbRow(MYSQL_RES *, MYSQL_ROW *row);
  ~zmDbRow();

  MYSQL_ROW mysql_row() const { return row; };

  char *operator[](unsigned int index) const {
    return row[index];
  }
};

extern MYSQL dbconn;
extern std::mutex db_mutex;
extern zmDbQueue  dbQueue;
extern unsigned long db_thread_id;

extern bool zmDbConnected;

bool zmDbConnect();
void zmDbClose();
int zmDbDo(const std::string &query);
int zmDbDoInsert(const std::string &query);
int zmDbDoUpdate(const std::string &query);

MYSQL_RES * zmDbFetch(const std::string &query);
zmDbRow *zmDbFetchOne(const std::string &query);

std::string zmDbEscapeString(const std::string& to_escape);

#endif // ZM_DB_H
