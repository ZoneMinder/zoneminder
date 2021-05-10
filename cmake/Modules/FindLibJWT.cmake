include(FindPackageHandleStandardArgs)

find_package(PkgConfig QUIET)
pkg_check_modules(PC_LIBJWT QUIET libjwt)

find_path(LIBJWT_INCLUDE_DIR
  NAMES jwt.h
  HINTS ${PC_LIBJWT_INCLUDEDIR} ${PC_LIBJWT_INCLUDE_DIRS}
  )

find_library(LIBJWT_LIBRARY
  NAMES jwt-gnutls libjwt-gnutls liblibjwt-gnutls
  HINTS ${PC_LIBJWT_LIBDIR} ${PC_LIBJWT_LIBRARY_DIR}
  )

find_package_handle_standard_args(LibJWT
  REQUIRED_VARS LIBJWT_INCLUDE_DIR LIBJWT_LIBRARY
  )

if(LIBJWT_FOUND)
  add_library(libjwt STATIC IMPORTED GLOBAL)
  set_target_properties(libjwt PROPERTIES
    IMPORTED_LOCATION "${LIBJWT_LIBRARY}"
    INTERFACE_INCLUDE_DIRECTORIES "${LIBJWT_INCLUDE_DIR}"
    )
endif()

mark_as_advanced(LIBJWT_INCLUDE_DIR LIBJWT_LIBRARY)