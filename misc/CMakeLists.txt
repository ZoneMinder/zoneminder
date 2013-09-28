# CMakeLists.txt for the ZoneMinder misc files

# Create files from the .in files
configure_file(apache.conf.in ${CMAKE_CURRENT_SOURCE_DIR}/apache.conf @ONLY)
configure_file(logrotate.conf.in ${CMAKE_CURRENT_SOURCE_DIR}/logrotate.conf @ONLY)
configure_file(syslog.conf.in ${CMAKE_CURRENT_SOURCE_DIR}/syslog.conf @ONLY)

# Install the misc files
install(FILES apache.conf logrotate.conf syslog.conf DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/misc")

