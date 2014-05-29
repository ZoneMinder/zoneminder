# CMakeLists.txt for the Redhat/CentOS Target Distro.

# Create the zoneminder service file
configure_file(zoneminder.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.service @ONLY)

# Download jscalendar & move files into position
file(DOWNLOAD http://softlayer-dal.dl.sourceforge.net/project/jscalendar/jscalendar/1.0/jscalendar-1.0.zip ${CMAKE_CURRENT_SOURCE_DIR}/jscalendar-1.0.zip LOG jsc_log STATUS download_jsc)
#message(STATUS "Log of jscalender script was: ${jsc_log}")
if(download_jsc EQUAL 0)
	message(STATUS "Jscalander successfully downloaded.  Installing...")
	execute_process(COMMAND ${CMAKE_CURRENT_SOURCE_DIR}/jscalendar.sh WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR} ERROR_VARIABLE unzip_jsc)
	message(STATUS "Status of jscalender script was: ${unzip_jsc}")
else(download_jsc EQUAL 0)
	message(STATUS "Unable to download optional jscalander. Skipping...")
endif(download_jsc EQUAL 0)

# Download cambozola & move files into position
file(DOWNLOAD http://www.andywilcock.com/code/cambozola/cambozola-0.931.tar.gz ${CMAKE_CURRENT_SOURCE_DIR}/cambozola-0.931.tar.gz STATUS download_camb)
if(download_camb EQUAL 0)
	message(STATUS "Cambozola successfully downloaded.  Installing...")
	execute_process(COMMAND ${CMAKE_CURRENT_SOURCE_DIR}/cambozola.sh WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR} ERROR_VARIABLE untar_camb)
	message(STATUS "Status of cambozola script was: ${untar_camb}")
else(download_camb EQUAL 0)
	message(STATUS "Unable to download optional Cambozola. Skipping...")
endif(download_camb EQUAL 0)

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

# Install auxillary files required to run zoneminder on CentOS
install(FILES zoneminder.conf DESTINATION /etc/httpd/conf.d PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES zm-logrotate_d DESTINATION /etc/logrotate.d RENAME zoneminder PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES redalert.wav DESTINATION ${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/sounds PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(FILES zoneminder.service DESTINATION /etc/rc.d/init.d RENAME zoneminder PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)

# Install jscalendar
if(unzip_jsc STREQUAL "")
	install(DIRECTORY jscalendar-1.0/ DESTINATION ${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/tools/jscalendar)
endif(unzip_jsc STREQUAL "")

# Install cambozola
if(untar_camb STREQUAL "")
	install(FILES cambozola-0.931/dist/cambozola.jar DESTINATION ${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www)
endif(untar_camb STREQUAL "")
