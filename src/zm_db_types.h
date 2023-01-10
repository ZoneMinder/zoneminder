//
// ZoneMinder Core Interfaces, $Date$, $Revision$
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

#ifndef ZM_DB_TYPES_H
#define ZM_DB_TYPES_H

#include "zm_db.h"
#include "zm_utils.h"
#include <cmath>

typedef enum
{
  /* SELECT QUERIES */
  SELECT_SERVER_ID_WITH_NAME = 0,
  SELECT_SERVER_NAME_WITH_ID,
  SELECT_SERVER_DATA_WITH_ID,
  SELECT_GROUP_WITH_ID,
  SELECT_MAX_EVENTS_ID_WITH_MONITORID_AND_FRAMES_NOT_ZERO,
  SELECT_GROUPS_PARENT_OF_MONITOR_ID,
  SELECT_MONITOR_ID_REMOTE_RTSP_AND_RTPUNI,
  SELECT_STORAGE_WITH_ID,
  SELECT_EVENT_WITH_ID,
  SELECT_USER_AND_DATA_WITH_USERNAME_ENABLED,
  SELECT_USER_AND_DATA_WITH_USERID_ENABLED,
  SELECT_USER_AND_DATA_PLUS_TOKEN_WITH_USERNAME_ENABLED,
  SELECT_GROUP_PERMISSIONS_FOR_USERID,
  SELECT_MONITOR_PERMISSIONS_FOR_USERID,
  SELECT_MONITOR_FOR_GROUPID,

  /* SELECT COMBINATION MONITOR */
  SELECT_MONITOR_WITH_ID,
  SELECT_MONITOR_TYPE,
  SELECT_MONITOR_TYPE_AND_DEVICE,
  SELECT_MONITOR_TYPE_AND_SERVER,
  SELECT_MONITOR_TYPE_AND_DEVICE_AND_SERVER,
  SELECT_MONITOR_TYPE_AND_PROTOCOL,
  SELECT_MONITOR_TYPE_AND_SERVER_AND_PROTOCOL,
  SELECT_MONITOR_TYPE_AND_PATH,
  SELECT_MONITOR_TYPE_AND_PATH_AND_SERVER,
  SELECT_MONITOR_TYPE_RTSP,
  SELECT_MONITOR_TYPE_RTSP_AND_SERVER,
  SELECT_MONITOR_TYPE_RTSP_AND_ID,
  SELECT_MONITOR_TYPE_RTSP_AND_SERVER_AND_ID,

  /* SELECT ALL QUERIES */
  SELECT_ALL_ACTIVE_STATES_ID,
  SELECT_ALL_CONFIGS,
  SELECT_ALL_STORAGE_ID,
  SELECT_ALL_STORAGE_ID_AND_SERVER_ID,
  SELECT_ALL_STORAGE_ID_WITH_SERVERID_NULL,
  SELECT_ALL_STORAGE_ID_WITH_SERVERID_NULL_OR_DIFFERENT,
  SELECT_ALL_EVENTS_ID_WITH_MONITORID_EQUAL,
  SELECT_ALL_FRAMES_OF_EVENT_WITH_ID,
  SELECT_ALL_EVENTS_ID_WITH_MONITORID_AND_ID_LESSER_THAN,
  SELECT_ALL_EVENTS_ID_WITH_MONITORID_AND_ID_LARGER_THAN,
  SELECT_ALL_USERS_AND_DATA_ENABLED,
  SELECT_ALL_ZONES_WITH_MONITORID_EQUAL_TO,
  SELECT_ALL_MONITORS_DATA,
  SELECT_ALL_MONITORS_DATA_VERBOSE,

  /* UPDATE QUERIES */
  UPDATE_NEW_EVENT_WITH_ID,
  UPDATE_NEW_EVENT_WITH_ID_NO_NAME,
  UPDATE_EVENT_WITH_ID_SET_NOTES,
  UPDATE_EVENT_WITH_ID_SET_SCORE,
  UPDATE_EVENT_WITH_ID_SET_STORAGEID,
  UPDATE_EVENT_WITH_ID_SET_SAVEJPEGS,
  UPDATE_MONITORSTATUS_WITH_MONITORID_SET_CAPTUREFPS,

  /* INSERT QUERIES */
  INSERT_EVENTS,
  INSERT_FRAMES,
  INSERT_STATS_SINGLE,
  INSERT_STATS_MULTIPLE,
  INSERT_LOGS,
  INSERT_MONITOR_STATUS_RUNNING,
  INSERT_MONITOR_STATUS_CONNECTED,
  INSERT_MONITOR_STATUS_NOTRUNNING,

  LAST_QUERY
} zmDbQueryID;

class zmDb;
class zmDbQuery;

class zmDecimal
{
private:
  long  integerPart;
  int64_t fractionalPart;
  bool partsValuesValid;

  double fullValue;
  bool fullValueValid;

public:
  explicit zmDecimal() :
      integerPart(0), fractionalPart(0), partsValuesValid( false ), 
      fullValue(0.0), fullValueValid( false ) {};

  explicit zmDecimal( long intPart, int64_t fracPart ) :
      integerPart(intPart), fractionalPart(fracPart), partsValuesValid( true ), 
      fullValue(0.0), fullValueValid( false ) {};

  explicit zmDecimal( double fullValue ) :
      integerPart(0), fractionalPart(0), partsValuesValid( false ),
      fullValue(fullValue), fullValueValid( true ) {};

  ~zmDecimal() {}

  double toValue() {
    if( fullValueValid )
      return fullValue;

    if( !partsValuesValid )
      throw soci::soci_error("Conversion parameters for decimal value incorrect.");

    char str[256];
    sprintf(str, "%ld.%06" PRIi64, integerPart, static_cast<int64>(fractionalPart) );
        
    fullValue = std::stod( std::string(str) );
    fullValueValid = true;

    return fullValue;
  }

  void toParts( long& intPart, int64_t& fracPart ) {
    if( partsValuesValid ) {
      intPart = integerPart;
      fracPart = fractionalPart;
      return;
    }

    if( !fullValueValid )
      throw soci::soci_error("Conversion parameters for decimal value incorrect.");

    // example of the process for 125.789456

    // integerConvertPart gets integer part = 125
    double integerConvertPart = 0.0;
    std::modf( fullValue, &integerConvertPart );

    int ignoredExponent;
    double mantissa = 0.0;
    // this divides the full value without the integral part
    // meaning fullValue - integerConvertPart = 0.789456 and
    // exponent value (0, ignored) and the mantissa, which will be
    // exactly 789456 and can be now casted safely (hopefully) without 
    // rounding or loss of precision
    frexp10( fullValue - integerConvertPart, ignoredExponent, mantissa );

    integerPart = static_cast<long>(integerConvertPart);
    intPart = integerPart;

    fracPart = static_cast<int64_t>(mantissa);

    partsValuesValid = true;
  }
};

namespace soci
{

template<> struct type_conversion<zmDecimal>
{
    typedef double base_type;
    static void from_base(const double & v, indicator & ind, zmDecimal & p)
    {
        p = zmDecimal( v );
    }
    static void to_base(zmDecimal & p, double & v, indicator & ind)
    {
        v = p.toValue();
        ind = i_ok;
    }
};

}

#endif // ZM_DB_TYPES_H
