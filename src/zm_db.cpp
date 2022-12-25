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
#include "zm_db_postgresql.h"

#include "zm_logger.h"
#include "zm_signal.h"
#include <cstdlib>
#include <chrono>
#include <thread>

zmDb *database = nullptr;

bool zmDbConnected = false;

bool zmDbConnect()
{
  // For some reason having these lines causes memory corruption and crashing on newer debian/ubuntu
  // But they really need to be here in order to prevent a double open of db
  if (zmDbConnected)
  {
    // Warning("Calling zmDbConnect when already connected");
    return true;
  }

  std::string dbType = TrimSpaces( StringToLower(staticConfig.DB_TYPE) );

  try {
#ifdef HAVE_LIBSOCI_MYSQL
  if( dbType.compare("mysql") == 0 ) database = new zmDbMySQLAdapter();
#endif

#ifdef HAVE_LIBSOCI_POSTGRESQL
  if( dbType.compare("pgsql") == 0 ) database = new zmDbPostgreSQLAdapter();
#endif
  }
  catch (const std::exception &err)
  {
      Error("Cannot initialize database: %s", err.what());
      exit(-1);
  }
  
  if( database == nullptr ) {
    Logger::fetch()->databaseLevel( Logger::NOLOG ); // to disable the recursive log write to db
    Error("Type '%s' not an available database backend ("
#ifdef HAVE_LIBSOCI_MYSQL
      "'mysql',"
#endif
#ifdef HAVE_LIBSOCI_POSTGRESQL
      "'pgsql',"
#endif
      ")", dbType.c_str());
    exit(-1);
  }
  zmDbConnected = true;
  return zmDbConnected;
}

void zmDbClose()
{
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
zmDb::zmDb() : dbLevel(0) {}

zmDb::~zmDb() {}

bool zmDb::connected() {
#if SOCI_VERSION >= 400001 // before version 4.0.1 session::is_connected was not supported
  std::chrono::steady_clock::time_point now = std::chrono::steady_clock::now();
  if( std::chrono::duration_cast<std::chrono::seconds>(now - lastConnectionCheck).count() <= 10 ) {
    return true;
  }
  try {
    lastConnectionCheck = now;
    if( !db.is_connected() ) {
      db.reconnect();
      return db.is_connected();
    }
  }
  catch (const std::exception &err)
  {
    disableDatabaseLog();
    Error("Can't disconnect server: %s", staticConfig.DB_HOST.c_str());
    restoreDatabaseLog();
    return false;
  }
  return true;
#else
  return false;
#endif
}

void zmDb::lock() {
  while( !db_mutex.try_lock() ) {
    std::this_thread::sleep_for(std::chrono::microseconds(100));
  }
}

void zmDb::unlock() {
  db_mutex.unlock();
}

void zmDb::disableDatabaseLog() {
  this->dbLevel = Logger::fetch()->databaseLevel( Logger::NOOPT ); // to disable the recursive log write to db
  Logger::fetch()->databaseLevel( Logger::NOLOG );
}
void zmDb::restoreDatabaseLog() {
  Logger::fetch()->databaseLevel( this->dbLevel );
}

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
  executed = false;
}

zmDbQuery::~zmDbQuery()
{
  if( executed ) {
    for( auto f : deferred ) {
      f();
    }
  }

  reset();
  stmt = nullptr;
}

// unsigned int is a special case in that representation actually differs between mysql and postgresql
template <>
unsigned int zmDb::getFromResult<unsigned int>(soci::rowset_iterator<soci::row>* result_iter, const int position) {
  try {
    return getUnsignedIntColumn(result_iter, position);
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
        return 0;
    }
#endif
  }
  return 0;
}

// unsigned int is a special case in that representation actually differs between mysql and postgresql
template <>
unsigned int zmDb::getFromResult<unsigned int>(soci::rowset_iterator<soci::row>* result_iter, const std::string& name) {
  try {
    return getUnsignedIntColumn(result_iter, name);
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
        return 0;
    }
#endif
  }
  return 0;
}

void zmDbQuery::run(bool data_exchange)
{
  if (stmt == nullptr || result != nullptr)
    return;

  executed = true;
  db->lock();

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
    resultFlag = stmt->execute(!data_exchange);
  }
  catch (soci::soci_error const & e)
  {
    errcode = 0;
    resultFlag = false;
    db->disableDatabaseLog();
    Error( "Database exception: %s", e.what() );
    db->restoreDatabaseLog();

#if SOCI_VERSION >= 400003 // before version 4.0.3 error categories were not implemented
    switch( e.get_error_category() ){
      case soci::soci_error::error_category::invalid_statement:
      case soci::soci_error::error_category::no_privilege:
      case soci::soci_error::error_category::system_error:
        exitOnError = true;
        break;

      default:
        break;
    }
#endif
  }
  if( !resultFlag && exitOnError ) {
    exit(errcode);
  }

  return;
}
bool zmDbQuery::next()
{
  if (stmt == nullptr || result == nullptr || affectedRows() == 0)
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

#if SOCI_VERSION >= 400000
  stmt->bind_clean_up();
#else
  stmt->clean_up();
#endif

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

  if( executed ) {
    executed = false;
    db->unlock();
  }
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

  uint64_t result = db->lastInsertID(id);
  reset();
  return result;
}

uint64_t zmDbQuery::update()
{
  if (stmt == nullptr)
    return 0;

  run(false);

  uint64_t result = stmt->get_affected_rows();
  reset();
  return result;
}

int zmDbQuery::affectedRows()
{
  if (stmt == nullptr || result == nullptr)
    return -1;

  return stmt->get_affected_rows();
}

void zmDbQuery::deferOnClose(std::function<void ()> deferFunction ) 
{
  deferred.push_back( deferFunction );
}
