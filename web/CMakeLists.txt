# CMakeLists.txt for the ZoneMinder web files

# Process the api subdirectory
add_subdirectory(api)
# Create files from the .in files
configure_file(includes/config.php.in "${CMAKE_CURRENT_BINARY_DIR}/includes/config.php" @ONLY)

# Install the web files
install(DIRECTORY vendor api ajax css email_content fonts graphics includes js lang skins views DESTINATION "${ZM_WEBDIR}" PATTERN "*.in" EXCLUDE PATTERN "*Make*" EXCLUDE PATTERN "*cmake*" EXCLUDE)
install(FILES index.php robots.txt DESTINATION "${ZM_WEBDIR}")
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/includes/config.php" DESTINATION "${ZM_WEBDIR}/includes")

# Install the api config files (if its not in the source directory)
if(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))
  install(FILES "${CMAKE_CURRENT_BINARY_DIR}/api/app/Config/core.php" DESTINATION "${ZM_WEBDIR}/api/app/Config")
  install(FILES "${CMAKE_CURRENT_BINARY_DIR}/api/app/Config/database.php" DESTINATION "${ZM_WEBDIR}/api/app/Config")
  install(FILES "${CMAKE_CURRENT_BINARY_DIR}/api/app/Config/bootstrap.php" DESTINATION "${ZM_WEBDIR}/api/app/Config")
  install(FILES "${CMAKE_CURRENT_BINARY_DIR}/api/lib/Cake/bootstrap.php" DESTINATION "${ZM_WEBDIR}/api/lib/Cake")
endif()
