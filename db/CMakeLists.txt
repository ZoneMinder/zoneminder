# CMakeLists.txt for the ZoneMinder database scripts

# Create files from the .in files
configure_file(zm_create.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_create.sql" @ONLY)
configure_file(zm_update-1.31.30.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.31.30.sql" @ONLY)

# Glob all database upgrade scripts
file(GLOB dbfileslist RELATIVE "${CMAKE_CURRENT_SOURCE_DIR}" "zm_update-*.sql")

# Install the database upgrade scripts
install(FILES ${dbfileslist} DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")

# install zm_update-1.31.30.sql
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.31.30.sql" DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")

# install zm_create.sql
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zm_create.sql" DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")

# install triggers.sql
install(FILES "${CMAKE_CURRENT_SOURCE_DIR}/triggers.sql" DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")

