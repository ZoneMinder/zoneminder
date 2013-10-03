# CMakeLists.txt for the ZoneMinder database scripts

# Create files from the .in files
configure_file(zm_create.sql.in ${CMAKE_CURRENT_SOURCE_DIR}/zm_create.sql @ONLY)

# Glob all files matching zm*.sql (to exclude *.in files and autotools's files)
file(GLOB dbfileslist RELATIVE "${CMAKE_CURRENT_SOURCE_DIR}" "zm*.sql")

# Install the database scripts, exclude makefiles and cmake stuff
install(FILES ${dbfileslist} DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")

