# CMakeLists.txt for the ZoneMinder database scripts

# Create files from the .in files
configure_file(zm_create.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_create.sql" @ONLY)
configure_file(zm_update-1.31.30.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.31.30.sql" @ONLY)
configure_file(zm_update-1.35.24.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.35.24.sql" @ONLY)
configure_file(zm_update-1.37.4.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.37.4.sql" @ONLY)
configure_file(zm_update-1.37.38.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.37.38.sql" @ONLY)

# install zm_create.sql
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zm_create.sql" DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")

# Glob all database upgrade scripts
file(GLOB dbfileslist RELATIVE "${CMAKE_CURRENT_SOURCE_DIR}" "*.sql")

# install zm_update-1.31.30.sql
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.31.30.sql" DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")
# install zm_update-1.35.24.sql
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.35.24.sql" DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")
# install zm_update-1.37.4.sql
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.37.4.sql" DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")
# install zm_update-1.37.38.sql
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.37.38.sql" DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")

# Install the database upgrade scripts
install(FILES ${dbfileslist} DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")
