# CMakeLists.txt for the ZoneMinder web files

# Create files from the .in files
#configure_file(app/Config/config.php.in ${CMAKE_CURRENT_SOURCE_DIR}/app/Config/config.php @ONLY)

# Install the web files
install(DIRECTORY app lib DESTINATION "${ZM_WEBDIR}" PATTERN "*.in" EXCLUDE PATTERN "*Make*" EXCLUDE PATTERN "*cmake*" EXCLUDE)
install(FILES index.php README.md DESTINATION "${ZM_WEBDIR}")
