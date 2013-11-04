# CMakeLists.txt for the ZoneMinder web files

# Process the tools/mootools subdirectory
add_subdirectory(tools/mootools)

# Create files from the .in files
configure_file(includes/config.php.in "${CMAKE_CURRENT_BINARY_DIR}/includes/config.php" @ONLY)

# Install the web files
install(DIRECTORY ajax css graphics includes js lang skins tools views DESTINATION "${ZM_WEBDIR}" PATTERN "*.in" EXCLUDE PATTERN "*Make*" EXCLUDE PATTERN "*cmake*" EXCLUDE REGEX "includes/config.php$" EXCLUDE)
install(FILES index.php README.md DESTINATION "${ZM_WEBDIR}")
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/includes/config.php" DESTINATION "${ZM_WEBDIR}/includes")
