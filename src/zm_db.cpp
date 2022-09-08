//
// ZoneMinder Database Implementation, $Date$, $Revision$
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
#include "zm_db.h"
#include "zm_db_mysql.h"

#include "zm_logger.h"
#include "zm_signal.h"
#include <cstdlib>

zmDb *database = nullptr;
zmDbQueue dbQueue;

bool zmDbConnected = false;

bool zmDbConnect()
{
  // For some reason having these lines causes memory corruption and crashing on newer debian/ubuntu
  // But they really need to be here in order to prevent a double open of mysql
  if (zmDbConnected)
  {
    // Warning("Calling zmDbConnect when already connected");
    return true;
  }

  database = new zmDbMySQLAdapter();

  // if ( mysql_query(&dbconn, "SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED") ) {
  //   Error("Can't set isolation level: %s", mysql_error(&dbconn));
  // }
  zmDbConnected = true;
  return zmDbConnected;
}

void zmDbClose()
{
  // std::lock_guard<std::mutex> lck(db_mutex);
  if (!zmDbConnected)
    return;

  delete database;
  database = nullptr;

  zmDbConnected = false;
}

bool zmDbIsConnected() {
  return zmDbConnected && database->connected();
}

// --- Query handling --- //
zmDbQuery::zmDbQuery(const zmDbQueryID& queryId)
{
  db = database;
  id = queryId;
  stmt = database->mapStatements[id];
  result = nullptr;
}

zmDbQuery::~zmDbQuery()
{
  reset();

  stmt = nullptr;
}

zmDbQuery &zmDbQuery::run(bool data_exchange)
{
  if (stmt == nullptr || result != nullptr)
    return *this;

  if (data_exchange)
  {
    result = new soci::row();
    stmt->exchange_for_rowset(soci::into(*result));
  }

  stmt->define_and_bind();
  stmt->execute(data_exchange);

  return *this;
}
bool zmDbQuery::next()
{
  if (stmt == nullptr || result == nullptr)
    return false;

  return stmt->fetch();
}
void zmDbQuery::reset()
{
  if (stmt == nullptr)
    return;

  stmt->bind_clean_up();

  if (result != nullptr)
  {
    delete result;
  }
  result = nullptr;
}

zmDbQuery &zmDbQuery::fetchOne()
{
  if (stmt == nullptr || result != nullptr)
    return *this;

  run(true)
    .next();

  return *this;
}

uint64_t zmDbQuery::insert()
{
  if (stmt == nullptr)
    return 0;

  run(false);

  return db->lastInsertID(id);
}

uint64_t zmDbQuery::update()
{
  if (stmt == nullptr)
    return 0;

  run(false);

  return stmt->get_affected_rows();
}

// --- Query Queue handling --- //

std::mutex dbQueueMutex;
zmDbQueue zmQueue;

zmDbQueue::zmDbQueue() : mTerminate(false)
{
  mThread = std::thread(&zmDbQueue::process, this);
}

zmDbQueue::~zmDbQueue()
{
  stop();
}

void zmDbQueue::stop()
{
  {
    std::unique_lock<std::mutex> lock(dbQueueMutex);

    mTerminate = true;
  }
  mCondition.notify_all();

  if (mThread.joinable()) mThread.join();
}

void zmDbQueue::process()
{
  std::unique_lock<std::mutex> lock(dbQueueMutex);

  while (!mTerminate and !zm_terminate) {
    if (mQueue.empty()) {
      mCondition.wait(lock);
    }
    while (!mQueue.empty()) {
      if (mQueue.size() > 30) {
        Logger *log = Logger::fetch();
        Logger::Level db_level = log->databaseLevel();
        log->databaseLevel(Logger::NOLOG);
        Warning("db queue size has grown larger %zu than 20 entries", mQueue.size());
        log->databaseLevel(db_level);
      }
      zmDbQuery query = mQueue.front();
      mQueue.pop();
      // My idea for leaving the locking around each sql statement is to allow
      // other db writers to get a chance
      lock.unlock();
      query.run(false);
      lock.lock();
    }
  }
} // end void zmDbQueue::process()

void zmDbQueue::push(zmDbQuery &&sql)
{
  {
    std::unique_lock<std::mutex> lock(dbQueueMutex);
    if (mTerminate) return;
    mQueue.push(std::move(sql));
  }
  mCondition.notify_all();
}

void zmDbQueue::pushToQueue(zmDbQuery &&query)
{
  zmQueue.push( std::move(query) );
}
