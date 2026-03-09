//
// ZoneMinder Configuration, $Date$, $Revision$
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

#ifndef ZM_CONFIG_H
#define ZM_CONFIG_H

#include "config.h"
#include "zm_config_data.h"
#include "zm_config_defines.h"
#include <string>
#include <unordered_map>

#define ZM_MAX_IMAGE_WIDTH    2048        // The largest image we imagine ever handling
#define ZM_MAX_IMAGE_HEIGHT    1536        // The largest image we imagine ever handling
#define ZM_MAX_IMAGE_COLOURS    4        // The largest image we imagine ever handling
#define ZM_MAX_IMAGE_DIM    (ZM_MAX_IMAGE_WIDTH*ZM_MAX_IMAGE_HEIGHT)
#define ZM_MAX_IMAGE_SIZE    (ZM_MAX_IMAGE_DIM*ZM_MAX_IMAGE_COLOURS)

#define ZM_SCALE_BASE      100          // The factor by which we bump up 'scale' to simulate FP
#define ZM_RATE_BASE      100          // The factor by which we bump up 'rate' to simulate FP

#define ZM_NETWORK_BUFSIZ     32768         // Size of network buffer

#define ZM_MAX_FPS        30          // The maximum frame rate we expect to handle
#define ZM_SAMPLE_RATE      int(1000000/ZM_MAX_FPS) // A general nyquist sample frequency for delays etc
#define ZM_SUSPENDED_RATE     int(1000000/4) // A slower rate for when disabled etc

void zmLoadStaticConfig();
void zmLoadDBConfig();

extern void process_configfile(char const *configFile);

struct StaticConfig {
  std::string DB_HOST;
  std::string DB_NAME;
  std::string DB_USER;
  std::string DB_PASS;
  std::string DB_SSL_CA_CERT;
  std::string DB_SSL_CLIENT_KEY;
  std::string DB_SSL_CLIENT_CERT;
  std::string PATH_WEB;
  std::string SERVER_NAME;
  unsigned int SERVER_ID;
  std::string DIR_EVENTS;
  std::string DIR_MODELS;
  std::string DIR_SOUNDS;
  std::string DIR_EXPORTS;
  std::string PATH_ZMS;
  std::string PATH_MAP;
  std::string PATH_SOCKS;
  std::string PATH_LOGS;
  std::string PATH_SWAP;
  std::string PATH_ARP;
  std::string capture_file_format;
  std::string analyse_file_format;
  std::string general_file_format;
  std::string video_file_format;
};

extern StaticConfig staticConfig;

class ConfigItem {
 private:
  std::string name_;
  std::string value_;
  std::string type_;

  mutable enum { CFG_UNKNOWN, CFG_BOOLEAN, CFG_INTEGER, CFG_DECIMAL, CFG_STRING } cfg_type_;
  mutable union {
    bool boolean_value;
    int integer_value;
    double decimal_value;
  } cfg_value_;
  mutable bool accessed_;

 public:
  ConfigItem();
  ConfigItem(const char *p_name, const char *p_value, const char *p_type);

  void ConvertValue() const;
  bool BooleanValue() const;
  int IntegerValue() const;
  double DecimalValue() const;
  const char *StringValue() const;
};

class Config {
 public:
  struct MemberBinding {
    enum Type { BOOL, INT, DOUBLE, STRING } type;
    void *ptr;
  };

  ZM_CFG_DECLARE_LIST

 private:
  std::unordered_map<std::string, MemberBinding> bindings_;
  std::unordered_map<std::string, ConfigItem> items_;

  void RegisterBinding(const char *name, MemberBinding::Type type, void *ptr);
  void ApplyItem(const char *name, const char *value, const char *type);

 public:
  Config();
  ~Config() = default;

  void Load();
};

extern Config config;

#endif // ZM_CONFIG_H
