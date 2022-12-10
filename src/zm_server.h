/*
 * ZoneMinder Server Class Interface
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

#ifndef ZM_SERVER_H
#define ZM_SERVER_H

#include "zm_db.h"
#include <string>

class Server {
protected:
	unsigned int	id;
  std::string name;
  std::string protocol;
  std::string hostname;
  std::string path_to_index;
  std::string path_to_zms;
  std::string path_to_api;

public:
	Server();
	explicit Server( zmDbQuery &dbrow );
	explicit Server( unsigned int p_id );
	~Server();

	unsigned int	Id() const { return id; }
	const std::string &Name() const { return name; }
	const std::string &Protocol() const { return protocol; }
	const std::string &Hostname() const { return hostname; }
	const std::string &PathToZMS() const { return path_to_zms; }
	const std::string &PathToAPI() const { return path_to_api; }
	const std::string &PathToIndex() const { return path_to_index; }
};

#endif // ZM_SERVER_H
