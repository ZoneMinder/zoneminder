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

Server::Server(zmDbQuery &dbrow) {
  id = dbrow.get<int>("Id");
  name = dbrow.get<std::string>("Name");
  protocol = dbrow.get<std::string>("Protocol");
  hostname = dbrow.get<std::string>("Hostname");
  path_to_index = dbrow.get<std::string>("PathToIndex");
  path_to_zms = dbrow.get<std::string>("PathToZMS");
  path_to_api = dbrow.get<std::string>("PathToApi");
}

/* If a zero or invalid p_id is passed, then the old default path will be assumed.  */
Server::Server(unsigned int p_id) : id(p_id) {
  if (id) {
    zmDbQuery serverQuery( SELECT_SERVER_DATA_WITH_ID );
    serverQuery.bind<int>("id", id);
    serverQuery.fetchOne();

    if( serverQuery.affectedRows() == 0 ) {
      Error("Unable to load server for id %d", id);
    } else {
      id = serverQuery.get<int>("Id");
      name = serverQuery.get<std::string>("Name");
      protocol = serverQuery.get<std::string>("Protocol");
      hostname = serverQuery.get<std::string>("Hostname");
      path_to_index = serverQuery.get<std::string>("PathToIndex");
      path_to_zms = serverQuery.get<std::string>("PathToZMS");
      path_to_api = serverQuery.get<std::string>("PathToApi");
      Debug(1, "Loaded Server %d '%s' %s://%s", id, name.c_str(), protocol.c_str(), hostname.c_str());
    }
  }
}

Server::~Server() {
}
