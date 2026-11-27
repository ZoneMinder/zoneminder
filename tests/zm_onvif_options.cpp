/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#ifdef WITH_GSOAP

#include "zm_config.h"
#include "zm_monitor.h"
#include "zm_monitor_onvif.h"

#include <string>

namespace {

class OptionsMonitor : public Monitor {
 public:
  explicit OptionsMonitor(const std::string &options) { onvif_options = options; }
};

class OptionsONVIF : public ONVIF {
 public:
  explicit OptionsONVIF(Monitor *m) : ONVIF(m) {}
  bool forced_closes_event() const { return closes_event; }
  const std::string &log_file() const { return soap_log_file; }
};

bool closes_event_for(const std::string &options) {
  OptionsMonitor monitor(options);
  OptionsONVIF onvif(&monitor);
  return onvif.forced_closes_event();
}

std::string soap_log_for(const std::string &options) {
  OptionsMonitor monitor(options);
  OptionsONVIF onvif(&monitor);
  return onvif.log_file();
}

}  // namespace

TEST_CASE("ONVIF closes_event option", "[onvif]") {
  SECTION("Off when not given") {
    REQUIRE_FALSE(closes_event_for(""));
    REQUIRE_FALSE(closes_event_for("pull_timeout=5"));
  }

  SECTION("Bare flag and true values enable it") {
    REQUIRE(closes_event_for("closes_event"));
    REQUIRE(closes_event_for("closes_event=true"));
    REQUIRE(closes_event_for("closes_event=1"));
    REQUIRE(closes_event_for("pull_timeout=5,closes_event=yes"));
  }

  SECTION("false, 0 and no disable it") {
    REQUIRE_FALSE(closes_event_for("closes_event=false"));
    REQUIRE_FALSE(closes_event_for("closes_event=0"));
    REQUIRE_FALSE(closes_event_for("closes_event=no"));
  }
}

TEST_CASE("ONVIF soap_log option stays inside the log directory", "[onvif]") {
  const std::string saved = staticConfig.PATH_LOGS;
  staticConfig.PATH_LOGS = "/var/log/zm";

  REQUIRE(soap_log_for("") == "");
  REQUIRE(soap_log_for("soap_log=/var/log/zm/onvif.log") == "/var/log/zm/onvif.log");
  REQUIRE(soap_log_for("pull_timeout=5,soap_log=/var/log/zm/m1/onvif.log") == "/var/log/zm/m1/onvif.log");

  REQUIRE(soap_log_for("soap_log=/etc/cron.d/zm") == "");
  REQUIRE(soap_log_for("soap_log=/var/log/zm/../../etc/cron.d/zm") == "");
  REQUIRE(soap_log_for("soap_log=/var/log/zmother/onvif.log") == "");
  REQUIRE(soap_log_for("soap_log=/var/log/zm/") == "");
  REQUIRE(soap_log_for("soap_log=onvif.log") == "");

  staticConfig.PATH_LOGS = saved;
}

#endif  // WITH_GSOAP
