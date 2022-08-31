# - Find libmosquitto
# Find the native libmosquitto includes and libraries
#
#  MOSQUITTOPP_INCLUDE_DIR - where to find mosquitto.h, etc.
#  MOSQUITTOPP_LIBRARIES   - List of libraries when using libmosquitto.
#  MOSQUITTOPP_FOUND       - True if libmosquitto found.

if(MOSQUITTOPP_INCLUDE_DIR)
    # Already in cache, be silent
    set(MOSQUITTOPP_FIND_QUIETLY TRUE)
endif(MOSQUITTOPP_INCLUDE_DIR)

find_path(MOSQUITTOPP_INCLUDE_DIR mosquitto.h)

find_library(MOSQUITTOPP_LIBRARY NAMES libmosquittopp mosquittopp)

# Handle the QUIETLY and REQUIRED arguments and set MOSQUITTO_FOUND to TRUE if
# all listed variables are TRUE.
include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(MOSQUITTOPP DEFAULT_MSG MOSQUITTOPP_LIBRARY MOSQUITTOPP_INCLUDE_DIR)

if(MOSQUITTOPP_FOUND)
  set(MOSQUITTOPP_LIBRARIES ${MOSQUITTOPP_LIBRARY})
else(MOSQUITTOPP_FOUND)
  set(MOSQUITTOPP_LIBRARIES)
endif(MOSQUITTOPP_FOUND)

mark_as_advanced(MOSQUITTOPP_INCLUDE_DIR MOSQUITTOPP_LIBRARY)
