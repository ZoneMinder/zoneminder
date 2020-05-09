/*
 * ZoneMinder Group Class Interface, $Date$, $Revision$
 * Copyright (C) 2001-2008 Philip Coombes
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

#include "zm_db.h"

#ifndef ZM_GROUP_H
#define ZM_GROUP_H

class Group {

protected:
	unsigned int	id;
	unsigned int	parent_id;
	char name[64+1];

public:
	Group();
	explicit Group( MYSQL_ROW &dbrow );
	explicit Group( unsigned int p_id );
	~Group();

	unsigned int	Id() const { return id; }
	unsigned int	ParentId() const { return id; }
	const char *Name() const { return name; }
};

#endif // ZM_GROUP_H
