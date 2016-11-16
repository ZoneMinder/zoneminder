# CMakeLists.txt for the Fedora Target Distro.

# Display a message to show the Fedora build options are being processed.
message([STATUS] "Starting Fedora Build Options" ...)

# Process config files
configure_file(zoneminder.service.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.service @ONLY)
configure_file(zoneminder.tmpfiles.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.tmpfiles @ONLY)
configure_file(zoneminder.logrotate.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.logrotate @ONLY)
if(ZM_WEB_USER STREQUAL "nginx")
    configure_file(nginx/zoneminder.conf.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.conf @ONLY)
    configure_file(nginx/zoneminder.php-fpm.conf.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.php-fpm.conf @ONLY)
    configure_file(nginx/README.Fedora ${CMAKE_CURRENT_SOURCE_DIR}/README.Fedora COPYONLY)
else(ZM_WEB_USER STREQUAL "nginx")
    configure_file(zoneminder.conf.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.conf @ONLY)
endif(ZM_WEB_USER STREQUAL "nginx")

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

# Fedora requires cambozola as a separate package so just link to it
install(CODE "execute_process(COMMAND ln -sf ../../java/cambozola.jar \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/cambozola.jar\")")

# Install auxiliary files required to run zoneminder on Fedora
if(ZM_WEB_USER STREQUAL "nginx")
    install(FILES zoneminder.conf DESTINATION /etc/nginx/default.d PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
    install(FILES zoneminder.php-fpm.conf DESTINATION /etc/php-fpm.d PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ RENAME zoneminder.conf)
else(ZM_WEB_USER STREQUAL "nginx")
    install(FILES zoneminder.conf DESTINATION /etc/httpd/conf.d PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
endif(ZM_WEB_USER STREQUAL "nginx")

install(FILES zoneminder.logrotate DESTINATION /etc/logrotate.d RENAME zoneminder PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES ../../misc/zoneminder-tmpfiles.conf DESTINATION /etc/tmpfiles.d RENAME zoneminder.conf PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES redalert.wav DESTINATION ${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/sounds PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(FILES zoneminder.service DESTINATION /usr/lib/systemd/system PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(DIRECTORY jscalendar-1.0/ DESTINATION ${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/tools/jscalendar)

