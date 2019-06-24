# CMakeLists.txt for the ZoneMinder misc files

# Create files from the .in files
configure_file(apache.conf.in "${CMAKE_CURRENT_BINARY_DIR}/apache.conf" @ONLY)
configure_file(nginx.conf.in "${CMAKE_CURRENT_BINARY_DIR}/nginx.conf" @ONLY)
configure_file(logrotate.conf.in "${CMAKE_CURRENT_BINARY_DIR}/logrotate.conf" @ONLY)
configure_file(syslog.conf.in "${CMAKE_CURRENT_BINARY_DIR}/syslog.conf" @ONLY)
configure_file(com.zoneminder.systemctl.policy.in "${CMAKE_CURRENT_BINARY_DIR}/com.zoneminder.systemctl.policy" @ONLY)
configure_file(com.zoneminder.systemctl.rules.in "${CMAKE_CURRENT_BINARY_DIR}/com.zoneminder.systemctl.rules" @ONLY)
configure_file(zoneminder.service.in "${CMAKE_CURRENT_BINARY_DIR}/zoneminder.service" @ONLY)
configure_file(zoneminder-tmpfiles.conf.in "${CMAKE_CURRENT_BINARY_DIR}/zoneminder-tmpfiles.conf" @ONLY)
configure_file(zoneminder.desktop.in "${CMAKE_CURRENT_BINARY_DIR}/zoneminder.desktop" @ONLY)
configure_file(zm-sudo.in "${CMAKE_CURRENT_BINARY_DIR}/zm-sudo" @ONLY)

# Do not install the misc files by default
#install(FILES "${CMAKE_CURRENT_BINARY_DIR}/apache.conf" "${CMAKE_CURRENT_BINARY_DIR}/logrotate.conf" "${CMAKE_CURRENT_BINARY_DIR}/syslog.conf" DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/misc")

# Install Policykit rules and actions into the proper folders only on systems with systemd
if(WITH_SYSTEMD)
    install(FILES "${CMAKE_CURRENT_BINARY_DIR}/com.zoneminder.systemctl.policy" DESTINATION "${PC_POLKIT_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/polkit-1/actions")
    install(FILES "${CMAKE_CURRENT_BINARY_DIR}/com.zoneminder.systemctl.rules" DESTINATION "${PC_POLKIT_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/polkit-1/rules.d")
endif(WITH_SYSTEMD)
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zoneminder.desktop" DESTINATION "${CMAKE_INSTALL_DATADIR}/applications")
