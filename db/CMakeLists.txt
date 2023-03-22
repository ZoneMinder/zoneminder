# CMakeLists.txt for the ZoneMinder database scripts

# Create files from the .in files
configure_file(zm_create.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_create.sql" @ONLY)
configure_file(zm_update-1.31.30.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.31.30.sql" @ONLY)
configure_file(zm_update-1.35.24.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.35.24.sql" @ONLY)
configure_file(zm_update-1.37.4.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.37.4.sql" @ONLY)
configure_file(zm_update-1.37.38.sql.in "${CMAKE_CURRENT_BINARY_DIR}/zm_update-1.37.38.sql" @ONLY)

# Glob all database upgrade scripts
file(GLOB dbfileslist RELATIVE "${CMAKE_CURRENT_SOURCE_DIR}" "*.sql")

# Install the database upgrade scripts
install(FILES ${dbfileslist} DESTINATION "${CMAKE_INSTALL_DATADIR}/zoneminder/db")
