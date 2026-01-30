# FindLibUnwind.cmake
# Find the libunwind library for better stack traces on ARM and other platforms
#
# Sets:
#   LIBUNWIND_FOUND       - True if libunwind was found
#   LIBUNWIND_INCLUDE_DIR - Include directory for libunwind
#   LIBUNWIND_LIBRARIES   - Libraries to link against

find_path(LIBUNWIND_INCLUDE_DIR
  NAMES libunwind.h
  PATHS /usr/include /usr/local/include
)

find_library(LIBUNWIND_LIBRARY
  NAMES unwind
  PATHS /usr/lib /usr/local/lib /usr/lib/arm-linux-gnueabihf /usr/lib/aarch64-linux-gnu
)

# On some platforms, we also need libunwind-generic or platform-specific libs
find_library(LIBUNWIND_GENERIC_LIBRARY
  NAMES unwind-generic unwind-arm unwind-aarch64 unwind-x86_64 unwind-x86
  PATHS /usr/lib /usr/local/lib /usr/lib/arm-linux-gnueabihf /usr/lib/aarch64-linux-gnu
)

include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(LibUnwind
  REQUIRED_VARS LIBUNWIND_LIBRARY LIBUNWIND_INCLUDE_DIR
)

if(LIBUNWIND_FOUND)
  set(LIBUNWIND_LIBRARIES ${LIBUNWIND_LIBRARY})
  if(LIBUNWIND_GENERIC_LIBRARY)
    list(APPEND LIBUNWIND_LIBRARIES ${LIBUNWIND_GENERIC_LIBRARY})
  endif()
  mark_as_advanced(LIBUNWIND_INCLUDE_DIR LIBUNWIND_LIBRARY LIBUNWIND_GENERIC_LIBRARY)
endif()
