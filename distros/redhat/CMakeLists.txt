# CMakeLists.txt for the Redhat/CentOS Target Distro.

# Display a message to show the RHEL build options are being processed.
message([STATUS] "Starting RHEL Build Options" ...)

# Create the zoneminder service file
if(ZM_TARGET_DISTRO STREQUAL "el7")
	configure_file(zoneminder.service.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.service @ONLY)
	configure_file(zoneminder.el7.conf.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.el7.conf @ONLY)
	configure_file(zoneminder.tmpfiles.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.tmpfiles @ONLY)
	configure_file(zoneminder.el7.logrotate.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.el7.logrotate @ONLY)
else(ZM_TARGET_DISTRO STREQUAL "el7")
	configure_file(zoneminder.sysvinit.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.sysvinit @ONLY)
	configure_file(zoneminder.el6.logrotate.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.el6.logrotate @ONLY)
	configure_file(zoneminder.el6.conf.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.el6.conf @ONLY)
endif(ZM_TARGET_DISTRO STREQUAL "el7")

# Unpack jscalendar & move files into position
message(STATUS "Unpacking and Installing jscalendar...")
execute_process(COMMAND ${CMAKE_CURRENT_SOURCE_DIR}/jscalendar.sh 
                WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR} 
                ERROR_VARIABLE unzip_jsc
               )
if("${unzip_jsc}" STREQUAL "")
    message(STATUS "jscalendar successfully installed.")
else("${unzip_jsc}" STREQUAL "")
    message(FATAL_ERROR "\nAn error occured while jscalendar was being processed:\n${unzip_jsc}")
endif("${unzip_jsc}" STREQUAL "")

# Create several empty folders
file(MAKE_DIRECTORY sock swap zoneminder zoneminder-upload events images temp)

# Install the empty folders
#install(DIRECTORY run DESTINATION /var DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_WRITE GROUP_READ GROUP_EXECUTE WORLD_WRITE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY sock swap DESTINATION /var/lib/zoneminder DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY zoneminder DESTINATION /var/log DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY zoneminder DESTINATION /run DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY zoneminder-upload DESTINATION /var/spool DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY events images temp DESTINATION /var/lib/zoneminder DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)

# Create symlinks
install(CODE "execute_process(COMMAND ln -sf ../../../../var/lib/zoneminder/events \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/events\")")
install(CODE "execute_process(COMMAND ln -sf ../../../../var/lib/zoneminder/images \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/images\")")
install(CODE "execute_process(COMMAND ln -sf ../../../../var/lib/zoneminder/temp \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/temp\")")
install(CODE "execute_process(COMMAND ln -sf ../../../../../../var/lib/zoneminder/temp \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/api/app/tmp\")")

# Link to Cambozola, which is now packaged in zmrepo
install(CODE "execute_process(COMMAND ln -sf ../../java/cambozola.jar \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/cambozola.jar\")")

# Install auxiliary files required to run zoneminder on CentOS
install(FILES redalert.wav DESTINATION ${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/sounds PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY jscalendar-1.0/ DESTINATION ${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/tools/jscalendar)

if(ZM_TARGET_DISTRO STREQUAL "el7")
	install(FILES zoneminder.el7.conf DESTINATION /etc/httpd/conf.d RENAME zoneminder.conf PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
	install(FILES zoneminder.el7.logrotate DESTINATION /etc/logrotate.d RENAME zoneminder PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
	install(FILES zoneminder.service DESTINATION /usr/lib/systemd/system PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
	install(FILES ../../misc/zoneminder-tmpfiles.conf DESTINATION /etc/tmpfiles.d RENAME zoneminder.conf PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
else(ZM_TARGET_DISTRO STREQUAL "el7")
	install(FILES zoneminder.el6.conf DESTINATION /etc/httpd/conf.d RENAME zoneminder.conf PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
	install(FILES zoneminder.el6.logrotate DESTINATION /etc/logrotate.d RENAME zoneminder PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
	install(FILES zoneminder.sysvinit DESTINATION /etc/rc.d/init.d RENAME zoneminder PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
endif(ZM_TARGET_DISTRO STREQUAL "el7")

