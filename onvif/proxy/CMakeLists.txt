# CMakeLists.txt for the ZoneMinder ONVIF proxy module.

# Install the .pm files from source
install(DIRECTORY "${CMAKE_CURRENT_SOURCE_DIR}/lib/"
  DESTINATION "${ZM_PERL_INSTALLDIR}"
  FILES_MATCHING PATTERN "*.pm")
