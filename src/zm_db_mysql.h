//
// ZoneMinder MySQL Database Implementation, $Date$, $Revision$
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

#ifndef ZM_DB_MYSQL_H
#define ZM_DB_MYSQL_H

#include "zm_logger.h"
#include "zm_signal.h"

#ifdef HAVE_LIBSOCI_MYSQL
#include <cstdlib>

#include <mysql/mysql.h>
#include <mysql/mysqld_error.h>

#include "soci/soci.h"
#include "soci/mysql/soci-mysql.h"

class zmDbMySQLAdapter : public zmDb {
public:
    zmDbMySQLAdapter();
    ~zmDbMySQLAdapter();
    virtual uint64_t lastInsertID(const zmDbQueryID& queryId);
    virtual std::string realColumnName(const std::string& column);
#if SOCI_VERSION < 400001 // before version 4.0.1 session::is_connected was not supported
    virtual bool connected() override;
#endif

private:
    void prepareSelectStatements();
    void prepareSelectMonitorStatements();
    void prepareSelectAllStatements();
    void prepareUpdateStatements();
    void prepareInsertStatements();

    void prepareAutoIncrementTables();
};

#endif

#endif
