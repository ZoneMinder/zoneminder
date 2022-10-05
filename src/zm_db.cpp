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

// --- Base DB --- //
zmDb::zmDb() {}

zmDb::~zmDb() {}

// --- Query handling --- //
zmDbQuery::zmDbQuery(const zmDbQueryID& queryId, bool exitError /* = false */)
{
  db = database;
  id = queryId;
  exitOnError = exitError;
  stmt = database->mapStatements[id];
  result = nullptr;
  result_iter = nullptr;
  result_iter_end = nullptr;
}

zmDbQuery::~zmDbQuery()
{
  reset();

  stmt = nullptr;
}

void zmDbQuery::run(bool data_exchange)
{
  if (stmt == nullptr || result != nullptr)
    return;

  if (data_exchange)
  {
    result = new soci::row();
    stmt->define_and_bind();
    stmt->exchange_for_rowset(soci::into(*result));
  } 
  else 
  {
    stmt->define_and_bind();
  }

  bool resultFlag = false;
  unsigned int errcode = 0;
  try {
    resultFlag = stmt->execute(data_exchange);
  }
  catch (soci::mysql_soci_error const & e)
  {
    Error("Database error [code %d]: %s", e.err_num_, e.what());
    errcode = e.err_num_;
    resultFlag = false;
  }
  catch (soci::soci_error const & e)
  {
    Error("Database error [code -1]: %s", e.what());
    errcode = 0;
    resultFlag = false;
  }
  if( !resultFlag && exitOnError ) {
    exit(errcode);
  }

  return;
}
bool zmDbQuery::next()
{
  if (stmt == nullptr || result == nullptr)
    return false;

  if( result_iter == nullptr ) {
    result_iter = new soci::rowset_iterator<soci::row>( *stmt, *result );
    result_iter_end = new soci::rowset_iterator<soci::row>();
    return true;
  }

  return ++(*result_iter) != *result_iter_end;
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
  if (result_iter != nullptr)
  {
    delete result_iter;
  }
  if (result_iter_end != nullptr)
  {
    delete result_iter_end;
  }
  result = nullptr;
  result_iter =  nullptr;
  result_iter_end = nullptr;
}

void zmDbQuery::fetchOne()
{
  if (stmt == nullptr || result != nullptr)
    return;

  run(true);
  next();

  return;
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

int zmDbQuery::affectedRows()
{
  if (stmt == nullptr || result != nullptr)
    return -1;

  return stmt->get_affected_rows();
}

void zmDbQuery::deferOnClose(std::function<void ()>) 
{

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
