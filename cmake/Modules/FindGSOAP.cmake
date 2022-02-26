#
# This module detects if gsoap is installed and determines where the
# include files and libraries are.
#
# This code sets the following variables:
#
# GSOAP_IMPORT_DIR      = full path to the gsoap import directory
# GSOAP_LIBRARIES       = full path to the gsoap libraries
# GSOAP_SSL_LIBRARIES   = full path to the gsoap ssl libraries
# GSOAP_INCLUDE_DIR     = include dir to be used when using the gsoap library
# GSOAP_PLUGIN_DIR      = gsoap plugins directory
# GSOAP_WSDL2H          = wsdl2h binary
# GSOAP_SOAPCPP2        = soapcpp2 binary
# GSOAP_FOUND           = set to true if gsoap was found successfully
#
# GSOAP_ROOT
#   setting this enables search for gsoap libraries / headers in this location

# -----------------------------------------------------
# GSOAP Import Directories
# -----------------------------------------------------
find_path(GSOAP_IMPORT_DIR
  NAMES wsa.h
  PATHS ${GSOAP_ROOT}/import ${GSOAP_ROOT}/share/gsoap/import
)

# -----------------------------------------------------
# GSOAP Libraries
# -----------------------------------------------------
find_library(GSOAP_CXX_LIBRARIES
	NAMES gsoap++
	HINTS ${GSOAP_ROOT}/lib ${GSOAP_ROOT}/lib64
		  ${GSOAP_ROOT}/lib32
	DOC "The main gsoap library"
)
find_library(GSOAP_SSL_CXX_LIBRARIES
	NAMES gsoapssl++
	HINTS ${GSOAP_ROOT}/lib ${GSOAP_ROOT}/lib64
		  ${GSOAP_ROOT}/lib32
	DOC "The ssl gsoap library"
)


# -----------------------------------------------------
# GSOAP Include Directories
# -----------------------------------------------------
find_path(GSOAP_INCLUDE_DIR
	NAMES stdsoap2.h
	HINTS ${GSOAP_ROOT} ${GSOAP_ROOT}/include ${GSOAP_ROOT}/include/*
	DOC "The gsoap include directory"
)

# -----------------------------------------------------
# GSOAP plugin Directories
# -----------------------------------------------------
find_path(GSOAP_PLUGIN_DIR
	NAMES wsseapi.c
	HINTS ${GSOAP_ROOT} /usr/share/gsoap/plugin
	DOC "The gsoap plugin directory"
)

# -----------------------------------------------------
# GSOAP Binaries
# ----------------------------------------------------
if(NOT GSOAP_TOOL_DIR)
	set(GSOAP_TOOL_DIR GSOAP_ROOT)
endif()

find_program(GSOAP_WSDL2H
	NAMES wsdl2h
	HINTS ${GSOAP_TOOL_DIR}/bin
	DOC "The gsoap bin directory"
)
find_program(GSOAP_SOAPCPP2
	NAMES soapcpp2
	HINTS ${GSOAP_TOOL_DIR}/bin
	DOC "The gsoap bin directory"
)
# -----------------------------------------------------
# GSOAP version
# try to determine the flagfor the 2.7.6 compatiblity, break with 2.7.13 and re-break with 2.7.16
# ----------------------------------------------------
if(GSOAP_SOAPCPP2)
  execute_process(COMMAND ${GSOAP_SOAPCPP2}  "-V"   OUTPUT_VARIABLE GSOAP_STRING_VERSION ERROR_VARIABLE GSOAP_STRING_VERSION )
  string(REGEX MATCH "[0-9]*\\.[0-9]*\\.[0-9]*" GSOAP_VERSION ${GSOAP_STRING_VERSION})
endif()
# -----------------------------------------------------
# GSOAP_276_COMPAT_FLAGS and GSOAPVERSION
# try to determine the flagfor the 2.7.6 compatiblity, break with 2.7.13 and re-break with 2.7.16
# ----------------------------------------------------
if( "${GSOAP_VERSION}"  VERSION_LESS "2.7.6")
	set(GSOAP_276_COMPAT_FLAGS "")
elseif ( "${GSOAP_VERSION}"  VERSION_LESS "2.7.14")
	set(GSOAP_276_COMPAT_FLAGS "-z")
else ( "${GSOAP_VERSION}"  VERSION_LESS "2.7.14")
	set(GSOAP_276_COMPAT_FLAGS "-z1 -z2")
endif ( "${GSOAP_VERSION}"  VERSION_LESS "2.7.6")

# -----------------------------------------------------
# handle the QUIETLY and REQUIRED arguments and set GSOAP_FOUND to TRUE if
# all listed variables are TRUE
# -----------------------------------------------------
include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(GSOAP DEFAULT_MSG GSOAP_CXX_LIBRARIES
	GSOAP_INCLUDE_DIR GSOAP_WSDL2H GSOAP_SOAPCPP2)
mark_as_advanced(GSOAP_INCLUDE_DIR GSOAP_LIBRARIES GSOAP_WSDL2H GSOAP_SOAPCPP2)

if(GSOAP_FOUND)
  if(GSOAP_FIND_REQUIRED AND GSOAP_FIND_VERSION AND ${GSOAP_VERSION} VERSION_LESS ${GSOAP_FIND_VERSION})
	message(SEND_ERROR "Found GSOAP version ${GSOAP_VERSION} less then required ${GSOAP_FIND_VERSION}.")
  endif()
endif()

