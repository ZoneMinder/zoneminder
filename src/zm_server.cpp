/*
 * ZoneMinder Server class
 * Copyright (C) 2022 ZoneMinder Inc
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

#include "zm_server.h"

#include "zm_db.h"
#include "zm_logger.h"
#include "zm_utils.h"
#include <cstring>

Server::Server() : id(0) {
  Warning("Instantiating default Server Object. Should not happen.");
}

Server::Server(MYSQL_ROW &dbrow) {
  unsigned int index = 0;
  id = atoi(dbrow[index++]);
  name = dbrow[index++];
  protocol = dbrow[index++];
  hostname = dbrow[index++];
  path_to_index = dbrow[index++];
  path_to_zms = dbrow[index++];
  path_to_api = dbrow[index++];
}

/* If a zero or invalid p_id is passed, then the old default path will be assumed.  */
Server::Server(unsigned int p_id) : id(p_id) {
  if (id) {
    std::string sql = stringtf("SELECT `Id`, `Name`, `Protocol`, `Hostname`, `PathToIndex`, `PathToZMS`, `PathToApi` FROM `Servers` WHERE `Id`=%u", id);
    Debug(2, "Loading Server for %u using %s", id, sql.c_str());
    zmDbRow dbrow;
    if (!dbrow.fetch(sql)) {
      Error("Unable to load server for id %d: %s", id, mysql_error(&dbconn));
    } else {
      unsigned int index = 0;
      id = atoi(dbrow[index++]);
      name = dbrow[index++];
      protocol = dbrow[index++];
      hostname = dbrow[index++];
      path_to_index = std::string(dbrow[index++]);
      path_to_zms = std::string(dbrow[index++]);
      path_to_api = std::string(dbrow[index++]);
      Debug(1, "Loaded Server %d '%s' %s://%s", id, name.c_str(), protocol.c_str(), hostname.c_str());
    }
  }
}

Server::~Server() {
}
