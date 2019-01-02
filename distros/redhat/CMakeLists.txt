# CMakeLists.txt for the Redhat Target Distros.

#
# General strategy is to configure and install all files specific to Apache and Nginx
# Then let the rpm specfile sort them into the appropriate sub-package
#

# Display a message to show the RHEL build options are being processed.
if(ZM_TARGET_DISTRO MATCHES "^el")
    message([STATUS] "Starting RHEL Build Options" ...)
elseif(ZM_TARGET_DISTRO MATCHES "^fc")
    message([STATUS] "Starting Fedora Build Options" ...)
else(ZM_TARGET_DISTRO MATCHES "^el")
    message([WARNING] "Unknown Build Option Detected" ...)
endif(ZM_TARGET_DISTRO MATCHES "^el")

#
# CONFIGURE STAGE
#

# Configure the common zoneminder files
configure_file(common/zoneminder.logrotate.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.logrotate @ONLY)
configure_file(common/zoneminder.service.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.service @ONLY)
file(MAKE_DIRECTORY sock swap zoneminder zoneminder-upload events temp)

# Configure the Apache zoneminder files
configure_file(httpd/zm-httpd.conf.in ${CMAKE_CURRENT_SOURCE_DIR}/zm-httpd.conf @ONLY)
configure_file(httpd/zoneminder.httpd.conf.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.httpd.conf @ONLY)
configure_file(httpd/zoneminder.httpd.tmpfiles.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.httpd.tmpfiles.conf @ONLY)
configure_file(httpd/com.zoneminder.systemctl.rules.httpd.in ${CMAKE_CURRENT_SOURCE_DIR}/com.zoneminder.systemctl.rules.httpd @ONLY)

# Configure the Nginx zoneminder files
configure_file(nginx/zm-nginx.conf ${CMAKE_CURRENT_SOURCE_DIR}/zm-nginx.conf COPYONLY)
configure_file(nginx/zoneminder.nginx.conf.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.nginx.conf @ONLY)
configure_file(nginx/redirect.nginx.conf ${CMAKE_CURRENT_SOURCE_DIR}/redirect.nginx.conf COPYONLY)
configure_file(nginx/zoneminder.nginx.tmpfiles.in ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.nginx.tmpfiles.conf @ONLY)
configure_file(nginx/zm-web-user.conf ${CMAKE_CURRENT_SOURCE_DIR}/zm-web-user.conf COPYONLY)
configure_file(nginx/zoneminder.php-fpm.conf ${CMAKE_CURRENT_SOURCE_DIR}/zoneminder.php-fpm.conf COPYONLY)
configure_file(nginx/com.zoneminder.systemctl.rules.nginx ${CMAKE_CURRENT_SOURCE_DIR}/com.zoneminder.systemctl.rules.nginx COPYONLY)

#
# INSTALLATION STAGE
#


# Install the common zoneminder files
install(FILES zoneminder.logrotate DESTINATION /etc/logrotate.d RENAME zoneminder PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES zoneminder.service DESTINATION /usr/lib/systemd/system PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)

install(DIRECTORY sock swap DESTINATION /var/lib/zoneminder DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY zoneminder DESTINATION /var/log DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY zoneminder DESTINATION /var/run DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY zoneminder DESTINATION /var/cache DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY zoneminder-upload DESTINATION /var/spool DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(DIRECTORY events temp DESTINATION /var/lib/zoneminder DIRECTORY_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)

# Install the Apache zoneminder files
install(FILES zm-httpd.conf DESTINATION /usr/lib/systemd/system/zoneminder.service.d PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES zoneminder.httpd.conf DESTINATION /etc/zm/www PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES zoneminder.httpd.tmpfiles.conf DESTINATION /usr/lib/tmpfiles.d PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES com.zoneminder.systemctl.rules.httpd DESTINATION /etc/zm/www PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)

# Install the Nginx zoneminder files
install(FILES zm-nginx.conf DESTINATION /usr/lib/systemd/system/zoneminder.service.d PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES zoneminder.nginx.conf DESTINATION /etc/zm/www PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES redirect.nginx.conf DESTINATION /etc/zm/www PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES zoneminder.nginx.tmpfiles.conf DESTINATION /usr/lib/tmpfiles.d PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES com.zoneminder.systemctl.rules.nginx DESTINATION /etc/zm/www PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES zm-web-user.conf DESTINATION /etc/zm/conf.d PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)
install(FILES zoneminder.php-fpm.conf DESTINATION /etc/php-fpm.d PERMISSIONS OWNER_WRITE OWNER_READ GROUP_READ WORLD_READ)

# Miscellaneous

# Symlink the cake php temp folder to the ZoneMinder temp folder
install(CODE "execute_process(COMMAND ln -sf ../../../../../../var/lib/zoneminder/temp \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/api/app/tmp\")")

# Link to Cambozola
install(CODE "execute_process(COMMAND ln -sf ../../java/cambozola.jar \"\$ENV{DESTDIR}${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/cambozola.jar\")")

# Install auxiliary files
install(FILES common/redalert.wav DESTINATION ${CMAKE_INSTALL_PREFIX}/${CMAKE_INSTALL_DATAROOTDIR}/zoneminder/www/sounds PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)

