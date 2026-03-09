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

#include "zm_config.h"

#include "zm_db.h"
#include "zm_logger.h"
#include "zm_utils.h"
#include <cerrno>
#include <cstring>
#include <dirent.h>
#include <glob.h>

// Note that Error and Debug calls won't actually go anywhere unless you
// set the relevant ENV vars because the logger gets it's setting from the
// config.

void zmLoadStaticConfig() {
  // Process name, value pairs from the main config file first
  process_configfile(ZM_CONFIG);

  // Search for user created config files. If one or more are found then
  // update the Config hash with those values
  DIR *configSubFolder = opendir(ZM_CONFIG_SUBDIR);
  if (configSubFolder) { // subfolder exists and is readable
    std::string glob_pattern = stringtf("%s/*.conf", ZM_CONFIG_SUBDIR);

    glob_t pglob;
    int glob_status = glob(glob_pattern.c_str(), 0, nullptr, &pglob);
    if (glob_status != 0) {
      if (glob_status < 0) {
        Error("Can't glob '%s': %s", glob_pattern.c_str(), strerror(errno));
      } else {
        Debug(1, "Can't glob '%s': %d", glob_pattern.c_str(), glob_status);
      }
    } else {
      for (unsigned int i = 0; i < pglob.gl_pathc; i++) {
        process_configfile(pglob.gl_pathv[i]);
      }
    }
    globfree(&pglob);
    closedir(configSubFolder);
  }
}

void zmLoadDBConfig() {
  if (!zmDbConnected) {
    Fatal("Not connected to the database. Can't continue.");
  }
  config.Load();

  // Populate the server config entries
  if (!staticConfig.SERVER_ID) {
    if (!staticConfig.SERVER_NAME.empty()) {

      Debug(1, "Fetching ZM_SERVER_ID For Name = %s", staticConfig.SERVER_NAME.c_str());
      std::string sql = stringtf("SELECT `Id` FROM `Servers` WHERE `Name`='%s'",
                                 staticConfig.SERVER_NAME.c_str());
      zmDbRow dbrow;
      if (dbrow.fetch(sql)) {
        staticConfig.SERVER_ID = atoi(dbrow[0]);
      } else {
        Fatal("Can't get ServerId for Server %s", staticConfig.SERVER_NAME.c_str());
      }

    } // end if has SERVER_NAME
  } else if (staticConfig.SERVER_NAME.empty()) {
    Debug(1, "Fetching ZM_SERVER_NAME For Id = %d", staticConfig.SERVER_ID);
    std::string sql = stringtf("SELECT `Name` FROM `Servers` WHERE `Id`='%d'", staticConfig.SERVER_ID);

    zmDbRow dbrow;
    if (dbrow.fetch(sql)) {
      staticConfig.SERVER_NAME = std::string(dbrow[0]);
    } else {
      Fatal("Can't get ServerName for Server ID %d", staticConfig.SERVER_ID);
    }
  }

  if (staticConfig.SERVER_ID) {
    Debug(3, "Multi-server configuration detected. Server is %d.", staticConfig.SERVER_ID);
  } else {
    Debug(3, "Single server configuration assumed because no Server ID or Name was specified.");
  }

  staticConfig.capture_file_format = stringtf("%%s/%%0%dd-capture.jpg", config.event_image_digits);
  staticConfig.analyse_file_format = stringtf("%%s/%%0%dd-analyse.jpg", config.event_image_digits);
  staticConfig.general_file_format = stringtf("%%s/%%0%dd-%%s", config.event_image_digits);
  staticConfig.video_file_format = "%s/%s";
}

void process_configfile(char const *configFile) {
  FILE *cfg;
  char line[512];
  if ( (cfg = fopen(configFile, "r")) == nullptr ) {
    Fatal("Can't open %s: %s", configFile, strerror(errno));
    return;
  }
  while ( fgets(line, sizeof(line), cfg) != nullptr ) {
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
    } while ( temp_ptr >= name_ptr && (*temp_ptr == ' ' || *temp_ptr == '\t') );

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
    else if ( strcasecmp(name_ptr, "ZM_DIR_MODELS") == 0 )
      staticConfig.DIR_MODELS = std::string(val_ptr);
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

ConfigItem::ConfigItem() : cfg_type_(CFG_UNKNOWN), accessed_(false) {
  cfg_value_.integer_value = 0;
}

ConfigItem::ConfigItem(const char *p_name, const char *p_value, const char *const p_type)
    : name_(p_name), value_(p_value), type_(p_type), cfg_type_(CFG_UNKNOWN), accessed_(false) {
  cfg_value_.integer_value = 0;
}

void ConfigItem::ConvertValue() const {
  if ( type_ == "boolean" ) {
    cfg_type_ = CFG_BOOLEAN;
    cfg_value_.boolean_value = (bool)strtol(value_.c_str(), nullptr, 0);
  } else if ( type_ == "integer" ) {
    cfg_type_ = CFG_INTEGER;
    cfg_value_.integer_value = strtol(value_.c_str(), nullptr, 10);
  } else if ( type_ == "hexadecimal" ) {
    cfg_type_ = CFG_INTEGER;
    cfg_value_.integer_value = strtol(value_.c_str(), nullptr, 16);
  } else if ( type_ == "decimal" ) {
    cfg_type_ = CFG_DECIMAL;
    cfg_value_.decimal_value = strtod(value_.c_str(), nullptr);
  } else {
    cfg_type_ = CFG_STRING;
  }
  accessed_ = true;
}

bool ConfigItem::BooleanValue() const {
  if ( !accessed_ )
    ConvertValue();

  if ( cfg_type_ != CFG_BOOLEAN ) {
    Error("Attempt to fetch boolean value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.",
          name_.c_str(), type_.c_str());
    exit(-1);
  }

  return cfg_value_.boolean_value;
}

int ConfigItem::IntegerValue() const {
  if ( !accessed_ )
    ConvertValue();

  if ( cfg_type_ != CFG_INTEGER ) {
    Error("Attempt to fetch integer value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.",
          name_.c_str(), type_.c_str());
    exit(-1);
  }

  return cfg_value_.integer_value;
}

double ConfigItem::DecimalValue() const {
  if ( !accessed_ )
    ConvertValue();

  if ( cfg_type_ != CFG_DECIMAL ) {
    Error("Attempt to fetch decimal value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.",
          name_.c_str(), type_.c_str());
    exit(-1);
  }

  return cfg_value_.decimal_value;
}

const char *ConfigItem::StringValue() const {
  if ( !accessed_ )
    ConvertValue();

  if ( cfg_type_ != CFG_STRING ) {
    Error("Attempt to fetch string value for %s, actual type is %s. Try running 'zmupdate.pl -f' to reload config.",
          name_.c_str(), type_.c_str());
    exit(-1);
  }

  return value_.c_str();
}

Config::Config() {
  // Set all members to compiled-in defaults
  ZM_CFG_DEFAULTS_INIT

  // Register name-to-member bindings for DB loading
  ZM_CFG_MAP_INIT
}

void Config::RegisterBinding(const char *name, MemberBinding::Type type, void *ptr) {
  bindings_[name] = {type, ptr};
}

void Config::ApplyItem(const char *name, const char *value, const char *type) {
  auto bind_it = bindings_.find(name);
  if (bind_it == bindings_.end()) {
    return;
  }

  // Store ConfigItem to own the string memory for const char* members
  auto [item_it, inserted] = items_.emplace(
      std::piecewise_construct,
      std::forward_as_tuple(name),
      std::forward_as_tuple(name, value, type));
  if (!inserted) {
    // Replace existing item
    item_it->second = ConfigItem(name, value, type);
  }

  const ConfigItem &item = item_it->second;
  const MemberBinding &binding = bind_it->second;

  switch (binding.type) {
    case MemberBinding::BOOL:
      *static_cast<bool*>(binding.ptr) = item.BooleanValue();
      break;
    case MemberBinding::INT:
      *static_cast<int*>(binding.ptr) = item.IntegerValue();
      break;
    case MemberBinding::DOUBLE:
      *static_cast<double*>(binding.ptr) = item.DecimalValue();
      break;
    case MemberBinding::STRING:
      *static_cast<const char**>(binding.ptr) = item.StringValue();
      break;
  }
}

void Config::Load() {
  // Only load rows where the user has changed the value from the default.
  // Compiled-in defaults (from ZM_CFG_DEFAULTS_INIT) cover everything else.
  // DefaultValue is NULL on very old schemas, so also load those to be safe.
  MYSQL_RES *result = zmDbFetch(
      "SELECT `Name`, `Value`, `Type` FROM `Config`"
      " WHERE `Value` != `DefaultValue`"
      " OR `DefaultValue` IS NULL");
  if (!result) {
    Warning("Failed to load config from database, using compiled-in defaults");
    return;
  }

  int loaded = 0;
  while (MYSQL_ROW dbrow = mysql_fetch_row(result)) {
    if (dbrow[0] && dbrow[1] && dbrow[2]) {
      if (bindings_.count(dbrow[0])) {
        ApplyItem(dbrow[0], dbrow[1], dbrow[2]);
        loaded++;
      }
    }
  }
  mysql_free_result(result);

  Debug(1, "Config loaded: %d items overriding compiled-in defaults (%zu registered)",
        loaded, bindings_.size());
}

Config config;
