# CMakeLists.txt for the ZoneMinder misc files

# Create files from the .in files
configure_file(apache.conf.in "${CMAKE_CURRENT_BINARY_DIR}/apache.conf" @ONLY)
configure_file(logrotate.conf.in "${CMAKE_CURRENT_BINARY_DIR}/logrotate.conf" @ONLY)
configure_file(syslog.conf.in "${CMAKE_CURRENT_BINARY_DIR}/syslog.conf" @ONLY)

# Do not install the misc files by default
#install(FILES "${CMAKE_CURRENT_BINARY_DIR}/apache.conf" "${CMAKE_CURRENT_BINARY_DIR}/logrotate.conf" "${CMAKE_CURRENT_BINARY_DIR}/syslog.conf" DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/misc")

if(ZM_SYSTEMD)
	install(FILES "${CMAKE_CURRENT_BINARY_DIR}/com.zoneminder.systemctl.policy" DESTINATION "${PC_POLKIT_PREFIX}${CMAKE_INSTALL_DATAROOTDIR}/polkit-1/actions")
	install(FILES "${CMAKE_CURRENT_BINARY_DIR}/com.zoneminder.systemctl.rules" DESTINATION "${PC_POLKIT_PREFIX}${CMAKE_INSTALL_DATAROOTDIR}/polkit-1/rules.d")
endif(ZM_SYSTEMD)
