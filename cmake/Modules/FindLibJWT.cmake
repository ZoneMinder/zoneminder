#[=======================================================================[.rst:
FindLibJWT
----------

Find the JWT C Library (libjwt)


This module accepts optional COMPONENTS to select the crypto backend (these are mutually exclusive)::

  openssl (default)
  gnutls

IMPORTED Targets
^^^^^^^^^^^^^^^^

This module defines the following :prop_tgt:`IMPORTED` targets:

``JWT::libjwt``
  The JWT library, if found with the specified crypto backend.

Result Variables
^^^^^^^^^^^^^^^^

``LIBJWT_FOUND``
  System has libjwt
``LIBJWT_INCLUDE_DIRS``
  The libjwt include directory
``LIBJWT_LIBRARIES``
  The libraries needed to use libjwt
#]=======================================================================]

include(FindPackageHandleStandardArgs)
find_package(PkgConfig QUIET)

if(LibJWT_FIND_COMPONENTS)
  set(LIBJWT_CRYPTO_BACKEND "")
  foreach(component IN LISTS LibJWT_FIND_COMPONENTS)
    if(component MATCHES "^(openssl|gnutls)")
      if(LIBJWT_CRYPTO_BACKEND)
        message(FATAL_ERROR "LibJWT: Only one crypto library can be selected.")
      endif()
    set(LIBJWT_CRYPTO_BACKEND ${component})
    else()
      message(FATAL_ERROR "LibJWT: Wrong crypto backend specified.")
    endif()
  endforeach()
else()
  set(LIBJWT_CRYPTO_BACKEND "openssl")
endif()

set(LIBJWT_LIB_NAMES "")
if(LIBJWT_CRYPTO_BACKEND STREQUAL "openssl")
  set(LIBJWT_LIB_NAMES "jwt" "libjwt")
elseif(LIBJWT_CRYPTO_BACKEND STREQUAL "gnutls")
  set(LIBJWT_LIB_NAMES "jwt-gnutls" "libjwt-gnutls")
endif()

pkg_check_modules(PC_LIBJWT QUIET libjwt)

find_path(LIBJWT_INCLUDE_DIR
  NAMES jwt.h
  HINTS
    ${PC_LIBJWT_INCLUDEDIR}
    ${PC_LIBJWT_INCLUDE_DIRS})
mark_as_advanced(LIBJWT_INCLUDE_DIR)

find_library(LIBJWT_LIBRARY
  NAMES ${LIBJWT_LIB_NAMES}
  HINTS
    ${PC_LIBJWT_LIBDIR}
    ${PC_LIBJWT_LIBRARY_DIR})
mark_as_advanced(LIBJWT_LIBRARY)

find_package_handle_standard_args(LibJWT
  REQUIRED_VARS
    LIBJWT_INCLUDE_DIR
    LIBJWT_LIBRARY
  FAIL_MESSAGE
    "Could NOT find LibJWT with the crypto backend ${LIBJWT_CRYPTO_BACKEND}.")

if(LIBJWT_FOUND)
  set(LIBJWT_LIBRARIES ${LIBJWT_LIBRARY})
  set(LIBJWT_INCLUDE_DIRS ${LIBJWT_INCLUDE_DIR})

  add_library(JWT::libjwt UNKNOWN IMPORTED)
  set_target_properties(JWT::libjwt PROPERTIES
    INTERFACE_INCLUDE_DIRECTORIES "${LIBJWT_INCLUDE_DIRS}"
    IMPORTED_LOCATION "${LIBJWT_LIBRARY}")
endif()
