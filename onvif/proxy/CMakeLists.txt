# CMakeLists.txt for the ZoneMinder ONVIF proxy module.

install(DIRECTORY "${CMAKE_CURRENT_SOURCE_DIR}/lib/"
  DESTINATION "${ZM_PERL_INSTALL_PATH}"
  FILES_MATCHING PATTERN "*.pm")
