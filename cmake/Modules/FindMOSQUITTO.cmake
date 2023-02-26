# - Find libmosquitto
# Find the native libmosquitto includes and libraries
#
#  MOSQUITTO_INCLUDE_DIR - where to find mosquitto.h, etc.
#  MOSQUITTO_LIBRARIES   - List of libraries when using libmosquitto.
#  MOSQUITTO_FOUND       - True if libmosquitto found.

if(MOSQUITTO_INCLUDE_DIR)
    # Already in cache, be silent
    set(MOSQUITTO_FIND_QUIETLY TRUE)
endif(MOSQUITTO_INCLUDE_DIR)

find_path(MOSQUITTO_INCLUDE_DIR mosquitto.h)

find_library(MOSQUITTO_LIBRARY NAMES libmosquitto mosquitto)

# Handle the QUIETLY and REQUIRED arguments and set MOSQUITTO_FOUND to TRUE if
# all listed variables are TRUE.
include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(MOSQUITTO DEFAULT_MSG MOSQUITTO_LIBRARY MOSQUITTO_INCLUDE_DIR)

if(MOSQUITTO_FOUND)
  set(MOSQUITTO_LIBRARIES ${MOSQUITTO_LIBRARY})
else(MOSQUITTO_FOUND)
  set(MOSQUITTO_LIBRARIES)
endif(MOSQUITTO_FOUND)

mark_as_advanced(MOSQUITTO_INCLUDE_DIR MOSQUITTO_LIBRARY)
