# Only build vendored jwt-cpp if not using system-installed version
if(NOT HAVE_SYSTEM_JWT_CPP)
  # EXCLUDE_FROM_ALL so the vendored copy's own install() rules do not stage
  # jwt-cpp headers, picojson.h and its cmake package files into our install
  # prefix. It is a header-only INTERFACE target, so it still links. See #4590.
  add_subdirectory(jwt-cpp EXCLUDE_FROM_ALL)
endif()
add_subdirectory(libbcrypt)
add_subdirectory(RtspServer)
add_subdirectory(span-lite)
set(ENABLE_INSTALL_SAVED "${ENABLE_INSTALL}")
set(ENABLE_INSTALL OFF)
set(CMAKE_POLICY_DEFAULT_CMP0077 NEW)
add_subdirectory(CxxUrl)
unset(CMAKE_POLICY_DEFAULT_CMP0077)
set(ENABLE_INSTALL "${ENABLE_INSTALL_SAVED}")
