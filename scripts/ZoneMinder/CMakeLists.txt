# CMakeLists.txt for the ZoneMinder Perl modules.

# Create configured .pm files from .pm.in templates
configure_file(lib/ZoneMinder/Base.pm.in
  "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/Base.pm" @ONLY)
configure_file(lib/ZoneMinder/Config.pm.in
  "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/Config.pm" @ONLY)
configure_file(lib/ZoneMinder/Memory.pm.in
  "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/Memory.pm" @ONLY)
configure_file(lib/ZoneMinder/ConfigData.pm.in
  "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/ConfigData.pm" @ONLY)
configure_file(lib/ZoneMinder/ONVIF.pm.in
  "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/ONVIF.pm" @ONLY)

# Install static .pm files from source tree
install(DIRECTORY "${CMAKE_CURRENT_SOURCE_DIR}/lib/"
  DESTINATION "${ZM_PERL_INSTALL_PATH}"
  FILES_MATCHING PATTERN "*.pm")

# Install configured .pm files from build tree
install(DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/lib/"
  DESTINATION "${ZM_PERL_INSTALL_PATH}"
  FILES_MATCHING PATTERN "*.pm")
