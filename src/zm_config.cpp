//
// ZoneMinder Configuration Implementation, $Date$, $Revision$
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

#include "zm.h"
#include "zm_db.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>

void zmLoadConfig()
{
	FILE *cfg;
	char line[512];
	if ( (cfg = fopen( ZM_CONFIG, "r")) == NULL )
	{
		Fatal( "Can't open %s: %s", ZM_CONFIG, strerror(errno) );
	}
	while ( fgets( line, sizeof(line), cfg ) != NULL )
	{
		char *line_ptr = line;

		// Trim off any cr/lf line endings
		int chomp_len = strcspn( line_ptr, "\r\n" );
		line_ptr[chomp_len] = '\0';

		// Remove leading white space
		int white_len = strspn( line_ptr, " \t" );
		line_ptr += white_len;

		// Check for comment or empty line
		if ( *line_ptr == '\0' || *line_ptr == '#' )
			continue;

		// Remove trailing white space
		char *temp_ptr = line_ptr+strlen(line_ptr)-1;
		while ( *temp_ptr == ' ' || *temp_ptr == '\t' )
		{
			*temp_ptr-- = '\0';
			temp_ptr--;
		}

		// Now look for the '=' in the middle of the line
		temp_ptr = strchr( line_ptr, '=' );
		if ( !temp_ptr )
		{
			Warning( "Invalid data in %s: '%s'", ZM_CONFIG, line );
			continue;
		}

		// Assign the name and value parts
		char *name_ptr = line_ptr;
		char *val_ptr = temp_ptr+1;

		// Trim trailing space from the name part
		do
		{
			*temp_ptr = '\0';
			temp_ptr--;
		}
		while ( *temp_ptr == ' ' || *temp_ptr == '\t' );

		// Remove leading white space from the value part
		white_len = strspn( val_ptr, " \t" );
		val_ptr += white_len;

		if ( strcasecmp( name_ptr, "ZM_DB_HOST" ) == 0 )
            staticConfig.DB_HOST = std::string(val_ptr);
		else if ( strcasecmp( name_ptr, "ZM_DB_NAME" ) == 0 )
            staticConfig.DB_NAME = std::string(val_ptr);
		else if ( strcasecmp( name_ptr, "ZM_DB_USER" ) == 0 )
            staticConfig.DB_USER = std::string(val_ptr);
		else if ( strcasecmp( name_ptr, "ZM_DB_PASS" ) == 0 )
            staticConfig.DB_PASS = std::string(val_ptr);
		else if ( strcasecmp( name_ptr, "ZM_PATH_WEB" ) == 0 )
            staticConfig.PATH_WEB = std::string(val_ptr);
		else
		{
			// We ignore this now as there may be more parameters than the
			// c/c++ binaries are bothered about
			// Warning( "Invalid parameter '%s' in %s", name_ptr, ZM_CONFIG );
		}
	}
	fclose( cfg);
	zmDbConnect();
	config.Load();
	config.Assign();
}

StaticConfig staticConfig;

ConfigItem::ConfigItem( const char *p_name, const char *p_value, const char *const p_type )
{
	name = new char[strlen(p_name)+1];
	strcpy( name, p_name );
	value = new char[strlen(p_value)+1];
	strcpy( value, p_value );
	type = new char[strlen(p_type)+1];
	strcpy( type, p_type );

	//Info( "Created new config item %s = %s (%s)\n", name, value, type );

	accessed = false;
}

ConfigItem::~ConfigItem()
{
	delete[] name;
	delete[] value;
	delete[] type;
}

void ConfigItem::ConvertValue() const
{
	if ( !strcmp( type, "boolean" ) )
	{
		cfg_type = CFG_BOOLEAN;
		cfg_value.boolean_value = (bool)strtol( value, 0, 0 );
	}
	else if ( !strcmp( type, "integer" ) )
	{
		cfg_type = CFG_INTEGER;
		cfg_value.integer_value = strtol( value, 0, 10 );
	}
	else if ( !strcmp( type, "hexadecimal" ) )
	{
		cfg_type = CFG_INTEGER;
		cfg_value.integer_value = strtol( value, 0, 16 );
	}
	else if ( !strcmp( type, "decimal" ) )
	{
		cfg_type = CFG_DECIMAL;
		cfg_value.decimal_value = strtod( value, 0 );
	}
	else
	{
		cfg_type = CFG_STRING;
		cfg_value.string_value = value;
	}
	accessed = true;
}

bool ConfigItem::BooleanValue() const
{
	if ( !accessed )
		ConvertValue();

	if ( cfg_type != CFG_BOOLEAN )
	{
		Error( "Attempt to fetch boolean value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.", name, type );
		exit( -1 );
	}

	return( cfg_value.boolean_value );
}

int ConfigItem::IntegerValue() const
{
	if ( !accessed )
		ConvertValue();

	if ( cfg_type != CFG_INTEGER )
	{
		Error( "Attempt to fetch integer value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.", name, type );
		exit( -1 );
	}

	return( cfg_value.integer_value );
}

double ConfigItem::DecimalValue() const
{
	if ( !accessed )
		ConvertValue();

	if ( cfg_type != CFG_DECIMAL )
	{
		Error( "Attempt to fetch decimal value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.", name, type );
		exit( -1 );
	}

	return( cfg_value.decimal_value );
}

const char *ConfigItem::StringValue() const
{
	if ( !accessed )
		ConvertValue();

	if ( cfg_type != CFG_STRING )
	{
		Error( "Attempt to fetch string value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.", name, type );
		exit( -1 );
	}

	return( cfg_value.string_value );
}

Config::Config()
{
	n_items = 0;
	items = 0;
}

Config::~Config()
{
	if ( items )
	{
		for ( int i = 0; i < n_items; i++ )
		{
			delete items[i];
		}
		delete[] items;
	}
}

void Config::Load()
{
    static char sql[ZM_SQL_SML_BUFSIZ];
   
    strncpy( sql, "select Name, Value, Type from Config order by Id", sizeof(sql) );
	if ( mysql_query( &dbconn, sql ) )
	{
		Error( "Can't run query: %s", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}

	MYSQL_RES *result = mysql_store_result( &dbconn );
	if ( !result )
	{
		Error( "Can't use query result: %s", mysql_error( &dbconn ) );
		exit( mysql_errno( &dbconn ) );
	}
	n_items = mysql_num_rows( result );

	if ( n_items <= ZM_MAX_CFG_ID )
	{
		Error( "Config mismatch, expected %d items, read %d. Try running 'zmupdate.pl -f' to reload config.", ZM_MAX_CFG_ID+1, n_items );
		exit( -1 );
	}

	items = new ConfigItem *[n_items];
	for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row( result ); i++ )
	{
		items[i] = new ConfigItem( dbrow[0], dbrow[1], dbrow[2] );
	}
	mysql_free_result( result );
}

void Config::Assign()
{
ZM_CFG_ASSIGN_LIST
}

const ConfigItem &Config::Item( int id )
{
	if ( !n_items )
	{
		Load();
		Assign();
	}

	if ( id < 0 || id > ZM_MAX_CFG_ID )
	{
		Error( "Attempt to access invalid config, id = %d. Try running 'zmupdate.pl -f' to reload config.", id );
		exit( -1 );
	}

	ConfigItem *item = items[id];
	
	if ( !item )
	{
		Error( "Can't find config item %d", id );
		exit( -1 );
	}
		
	return( *item );
}

Config config;
