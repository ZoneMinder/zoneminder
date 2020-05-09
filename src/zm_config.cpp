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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
// 

#include "zm.h"
#include "zm_db.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <dirent.h>
#include <glob.h>

#include "zm_utils.h"

// Note that Error and Debug calls won't actually go anywhere unless you 
// set the relevant ENV vars because the logger gets it's setting from the 
// config.

void zmLoadConfig() {

  // Process name, value pairs from the main config file first
  char configFile[PATH_MAX] = ZM_CONFIG;
  process_configfile(configFile);

  // Search for user created config files. If one or more are found then
  // update the Config hash with those values
  DIR* configSubFolder = opendir(ZM_CONFIG_SUBDIR);
  if ( configSubFolder ) { // subfolder exists and is readable
    char glob_pattern[PATH_MAX] = "";
    snprintf(glob_pattern, sizeof(glob_pattern), "%s/*.conf", ZM_CONFIG_SUBDIR);

    glob_t pglob;
    int glob_status = glob(glob_pattern, 0, 0, &pglob);
    if ( glob_status != 0 ) {
      if ( glob_status < 0 ) {
        Error("Can't glob '%s': %s", glob_pattern, strerror(errno));
      } else {
        Debug(1, "Can't glob '%s': %d", glob_pattern, glob_status);
      }
    } else {
      for ( unsigned int i = 0; i < pglob.gl_pathc; i++ ) {
        process_configfile(pglob.gl_pathv[i]);
      }
    }
    globfree(&pglob);
    closedir(configSubFolder);
  }

  if ( !zmDbConnect() ) {
    Fatal("Can't connect to db. Can't continue.");
  }
  config.Load();
  config.Assign();

  // Populate the server config entries
  if ( !staticConfig.SERVER_ID ) {
    if ( !staticConfig.SERVER_NAME.empty() ) {

      Debug(1, "Fetching ZM_SERVER_ID For Name = %s", staticConfig.SERVER_NAME.c_str());
      std::string sql = stringtf("SELECT `Id` FROM `Servers` WHERE `Name`='%s'",
          staticConfig.SERVER_NAME.c_str());
      zmDbRow dbrow;
      if ( dbrow.fetch(sql.c_str()) ) {
        staticConfig.SERVER_ID = atoi(dbrow[0]);
      } else {
        Fatal("Can't get ServerId for Server %s", staticConfig.SERVER_NAME.c_str());
      }

    } // end if has SERVER_NAME
  } else if ( staticConfig.SERVER_NAME.empty() ) {
    Debug(1, "Fetching ZM_SERVER_NAME For Id = %d", staticConfig.SERVER_ID);
    std::string sql = stringtf("SELECT `Name` FROM `Servers` WHERE `Id`='%d'", staticConfig.SERVER_ID);
    
    zmDbRow dbrow;
    if ( dbrow.fetch(sql.c_str()) ) {
      staticConfig.SERVER_NAME = std::string(dbrow[0]);
    } else {
      Fatal("Can't get ServerName for Server ID %d", staticConfig.SERVER_ID);
    }

    if ( staticConfig.SERVER_ID ) {
        Debug(3, "Multi-server configuration detected. Server is %d.", staticConfig.SERVER_ID);
    } else {
        Debug(3, "Single server configuration assumed because no Server ID or Name was specified.");
    }
  }

  snprintf(staticConfig.capture_file_format, sizeof(staticConfig.capture_file_format), "%%s/%%0%dd-capture.jpg", config.event_image_digits);
  snprintf(staticConfig.analyse_file_format, sizeof(staticConfig.analyse_file_format), "%%s/%%0%dd-analyse.jpg", config.event_image_digits);
  snprintf(staticConfig.general_file_format, sizeof(staticConfig.general_file_format), "%%s/%%0%dd-%%s", config.event_image_digits);
  snprintf(staticConfig.video_file_format, sizeof(staticConfig.video_file_format), "%%s/%%s");
}

void process_configfile(char* configFile) {
  FILE *cfg;
  char line[512];
  if ( (cfg = fopen(configFile, "r")) == NULL ) {
    Fatal("Can't open %s: %s", configFile, strerror(errno));
    return;
  }
  while ( fgets(line, sizeof(line), cfg) != NULL ) {
    char *line_ptr = line;

    // Trim off any cr/lf line endings
    int chomp_len = strcspn(line_ptr, "\r\n");
    line_ptr[chomp_len] = '\0';

    // Remove leading white space
    int white_len = strspn(line_ptr, " \t");
    line_ptr += white_len;

    // Check for comment or empty line
    if ( *line_ptr == '\0' || *line_ptr == '#' )
      continue;

    // Remove trailing white space and trailing quotes
    char *temp_ptr = line_ptr+strlen(line_ptr)-1;
    while ( *temp_ptr == ' ' || *temp_ptr == '\t' || *temp_ptr == '\'' || *temp_ptr == '\"') {
      *temp_ptr-- = '\0';
      temp_ptr--;
    }

    // Now look for the '=' in the middle of the line
    temp_ptr = strchr(line_ptr, '=');
    if ( !temp_ptr ) {
      Warning("Invalid data in %s: '%s'", configFile, line);
      continue;
    }

    // Assign the name and value parts
    char *name_ptr = line_ptr;
    char *val_ptr = temp_ptr+1;

    // Trim trailing space from the name part
    do {
      *temp_ptr = '\0';
      temp_ptr--;
    } while ( *temp_ptr == ' ' || *temp_ptr == '\t' );

    // Remove leading white space and leading quotes from the value part
    white_len = strspn(val_ptr, " \t");
    white_len += strspn(val_ptr, "\'\"");
    val_ptr += white_len;

    if ( strcasecmp(name_ptr, "ZM_DB_HOST") == 0 )
      staticConfig.DB_HOST = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_DB_NAME") == 0 )
      staticConfig.DB_NAME = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_DB_USER") == 0 )
      staticConfig.DB_USER = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_DB_PASS") == 0 )
      staticConfig.DB_PASS = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_DB_SSL_CA_CERT") == 0 )
      staticConfig.DB_SSL_CA_CERT = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_DB_SSL_CLIENT_KEY") == 0 )
      staticConfig.DB_SSL_CLIENT_KEY = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_DB_SSL_CLIENT_CERT") == 0 )
      staticConfig.DB_SSL_CLIENT_CERT = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_PATH_WEB") == 0 )
      staticConfig.PATH_WEB = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_SERVER_HOST") == 0 )
      staticConfig.SERVER_NAME = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_SERVER_NAME") == 0 )
      staticConfig.SERVER_NAME = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_SERVER_ID") == 0 )
      staticConfig.SERVER_ID = atoi(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_DIR_EVENTS") == 0 )
      staticConfig.DIR_EVENTS = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_DIR_SOUNDS") == 0 )
      staticConfig.DIR_SOUNDS = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_DIR_EXPORTS") == 0 )
      staticConfig.DIR_EXPORTS = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_PATH_ZMS") == 0 )
      staticConfig.PATH_ZMS = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_PATH_MAP") == 0 )
      staticConfig.PATH_MAP = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_PATH_SOCKS") == 0 )
      staticConfig.PATH_SOCKS = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_PATH_LOGS") == 0 )
      staticConfig.PATH_LOGS = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_PATH_SWAP") == 0 )
      staticConfig.PATH_SWAP = std::string(val_ptr);
    else if ( strcasecmp(name_ptr, "ZM_PATH_ARP") == 0 )
      staticConfig.PATH_ARP = std::string(val_ptr);
    else {
      // We ignore this now as there may be more parameters than the
      // c/c++ binaries are bothered about
      // Warning( "Invalid parameter '%s' in %s", name_ptr, ZM_CONFIG );
    }
  } // end foreach line of the config
  fclose(cfg);
}

StaticConfig staticConfig;

ConfigItem::ConfigItem(const char *p_name, const char *p_value, const char *const p_type) {
  name = new char[strlen(p_name)+1];
  strcpy(name, p_name);
  value = new char[strlen(p_value)+1];
  strcpy(value, p_value);
  type = new char[strlen(p_type)+1];
  strcpy(type, p_type);

  //Info( "Created new config item %s = %s (%s)\n", name, value, type );

  cfg_type = CFG_UNKNOWN;
  accessed = false;
}

ConfigItem::ConfigItem(const ConfigItem &item) {
  name = new char[strlen(item.name)+1];
  strcpy(name, item.name);
  value = new char[strlen(item.value)+1];
  strcpy(value, item.value);
  type = new char[strlen(item.type)+1];
  strcpy(type, item.type);

  //Info( "Created new config item %s = %s (%s)\n", name, value, type );

  accessed = false;
}
void ConfigItem::Copy(const ConfigItem &item) {
  if (name) delete name;
  name = new char[strlen(item.name)+1];
  strcpy(name, item.name);
  if (value) delete value;
  value = new char[strlen(item.value)+1];
  strcpy(value, item.value);
  if (type) delete type;
  type = new char[strlen(item.type)+1];
  strcpy(type, item.type);

  //Info( "Created new config item %s = %s (%s)\n", name, value, type );
  accessed = false;
}

ConfigItem::~ConfigItem() {
  delete[] name;
  delete[] value;
  delete[] type;
}

void ConfigItem::ConvertValue() const {
  if ( !strcmp( type, "boolean" ) ) {
    cfg_type = CFG_BOOLEAN;
    cfg_value.boolean_value = (bool)strtol(value, 0, 0);
  } else if ( !strcmp(type, "integer") ) {
    cfg_type = CFG_INTEGER;
    cfg_value.integer_value = strtol(value, 0, 10);
  } else if ( !strcmp(type, "hexadecimal") ) {
    cfg_type = CFG_INTEGER;
    cfg_value.integer_value = strtol(value, 0, 16);
  } else if ( !strcmp(type, "decimal") ) {
    cfg_type = CFG_DECIMAL;
    cfg_value.decimal_value = strtod(value, 0);
  } else {
    cfg_type = CFG_STRING;
    cfg_value.string_value = value;
  }
  accessed = true;
}

bool ConfigItem::BooleanValue() const {
  if ( !accessed )
    ConvertValue();

  if ( cfg_type != CFG_BOOLEAN ) {
    Error("Attempt to fetch boolean value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.", name, type);
    exit(-1);
  }

  return cfg_value.boolean_value;
}

int ConfigItem::IntegerValue() const {
  if ( !accessed )
    ConvertValue();

  if ( cfg_type != CFG_INTEGER ) {
    Error("Attempt to fetch integer value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.", name, type);
    exit(-1);
  }

  return cfg_value.integer_value;
}

double ConfigItem::DecimalValue() const {
  if ( !accessed )
    ConvertValue();

  if ( cfg_type != CFG_DECIMAL ) {
    Error("Attempt to fetch decimal value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.", name, type);
    exit(-1);
  }

  return cfg_value.decimal_value;
}

const char *ConfigItem::StringValue() const {
  if ( !accessed )
    ConvertValue();

  if ( cfg_type != CFG_STRING ) {
    Error("Attempt to fetch string value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.", name, type);
    exit(-1);
  }

  return cfg_value.string_value;
}

Config::Config() {
  n_items = 0;
  items = 0;
}

Config::~Config() {
  if ( items ) {
    for ( int i = 0; i < n_items; i++ ) {
      delete items[i];
      items[i] = NULL;
    }
    delete[] items;
    items = NULL;
  }
}

void Config::Load() {
  static char sql[ZM_SQL_SML_BUFSIZ];
   
  strncpy(sql, "SELECT `Name`, `Value`, `Type` FROM `Config` ORDER BY `Id`", sizeof(sql) );
  if ( mysql_query(&dbconn, sql) ) {
    Error("Can't run query: %s", mysql_error(&dbconn));
    exit(mysql_errno(&dbconn));
  }

  MYSQL_RES *result = mysql_store_result(&dbconn);
  if ( !result ) {
    Error("Can't use query result: %s", mysql_error(&dbconn));
    exit(mysql_errno(&dbconn));
  }
  n_items = mysql_num_rows(result);

  if ( n_items <= ZM_MAX_CFG_ID ) {
    Error("Config mismatch, expected %d items, read %d. Try running 'zmupdate.pl -f' to reload config.", ZM_MAX_CFG_ID+1, n_items);
    exit(-1);
  }

  items = new ConfigItem *[n_items];
  for( int i = 0; MYSQL_ROW dbrow = mysql_fetch_row(result); i++ ) {
    items[i] = new ConfigItem(dbrow[0], dbrow[1], dbrow[2]);
  }
  mysql_free_result(result);
}

void Config::Assign() {
ZM_CFG_ASSIGN_LIST
}

const ConfigItem &Config::Item(int id) {
  if ( !n_items ) {
    Load();
    Assign();
  }

  if ( id < 0 || id > ZM_MAX_CFG_ID ) {
    Error("Attempt to access invalid config, id = %d. Try running 'zmupdate.pl -f' to reload config.", id);
    exit(-1);
  }

  ConfigItem *item = items[id];
  
  if ( !item ) {
    Error("Can't find config item %d", id);
    exit(-1);
  }
    
  return *item;
}

Config config;
