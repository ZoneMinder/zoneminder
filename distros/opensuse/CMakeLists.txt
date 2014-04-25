# CMakeLists.txt for the OpenSuse Target Distro.
# Amended Apr 02 2014 David Wilcox
# Add named variables so that if destinations change it will be easier
# temp directory was not being installed

SET(zmuid_final wwwrun)
SET(zmgid_final www)
SET(webroot /srv/www/htdocs)
SET(zm_webdir ${webroot}/zoneminder)

# Download jscalendar & move files into position
file(DOWNLOAD http://downloads.sourceforge.net/jscalendar/jscalendar-1.0.zip ${CMAKE_CURRENT_SOURCE_DIR}/jscalendar-1.0.zip STATUS download_jsc)
if(download_jsc EQUAL 0)
message(STATUS "Jscalander successfully downloaded.  Installing...")
execute_process(COMMAND ${CMAKE_CURRENT_SOURCE_DIR}/jscalendar.sh WORKING_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR} ERROR_VARIABLE unzip_jsc)
message(STATUS "Status of jscalender script was: ${unzip_jsc}")
else(download_jsc EQUAL 0)
message(STATUS "Unable to download optional jscalander. Skipping...")
endif(download_jsc EQUAL 0)

# Create several empty folders
file(MAKE_DIRECTORY sock swap zoneminder zoneminder-upload events images temp)

# Install the empty folders
#install(DIRECTORY run DESTINATION /var DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_WRITE GROUP_READ GROUP_EXECUTE WORLD_WRITE WORLD_READ WORLD_EXECUTE)
#install(DIRECTORY sock swap DESTINATION /var/lib/zoneminder DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY zoneminder DESTINATION /var/log DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY zoneminder DESTINATION /var/run DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY zoneminder-upload DESTINATION /var/spool DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY zoneminder DESTINATION ${webroot} DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY events images temp DESTINATION ${zm_webdir} DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)

# Create symlinks
install(CODE "execute_process(COMMAND ln -sf ../../../../${zm_webdir}/events \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/events\")")
install(CODE "execute_process(COMMAND ln -sf ../../../../${zm_webdir}/images \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/images\")")
install(CODE "execute_process(COMMAND ln -sf ../../../../${zm_webdir}/temp \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/temp\")")

# Opensuse cambazola? requires cambozola as a separate package so just link to it
#install(CODE "execute_process(COMMAND ln -sf ../../java/cambozola.jar \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/cambozola.jar\")")

# Install auxillary files required to run zoneminder on OpenSuse
install(FILES zoneminder.conf DESTINATION /etc/apache2/conf.d PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES zoneminder.logrotate DESTINATION /etc/logrotate.d RENAME zoneminder PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES zoneminder.tmpfiles DESTINATION /etc/tmpfiles.d RENAME zoneminder.conf PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(FILES redalert.wav DESTINATION ${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/sounds PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(FILES zoneminder.service DESTINATION /usr/lib/systemd/system PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)

# Install jscalendar
if(unzip_jsc STREQUAL "")
install(DIRECTORY jscalendar-1.0/ DESTINATION ${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/tools/jscalendar)
endif(unzip_jsc STREQUAL "")
