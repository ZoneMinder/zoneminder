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
# Same reason as jwt-cpp: keep the vendored copy's install() rules - its static
# library, url.hpp/string.hpp and its cmake package files - out of our install
# prefix. This replaces forcing CxxUrl's own ENABLE_INSTALL option off, which
# worked but rested on the submodule continuing to spell that option exactly
# that way, and on CMAKE_POLICY_DEFAULT_CMP0077 to make the set() stick at all
# (CxxUrl asks for CMake 3.10, so option() would otherwise ignore it). A rename
# upstream would have quietly restored the leak. CMake enforces this one, and
# inter-target dependencies supersede the exclusion, so zm still links it.
add_subdirectory(CxxUrl EXCLUDE_FROM_ALL)
