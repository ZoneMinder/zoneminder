# CMakeLists.txt for the ZoneMinder web API files
# The only purpose of this file is to configure the required files

# Generate random salt and seed for the API
string(RANDOM LENGTH 40 ZM_API_SALT)
string(RANDOM LENGTH 29 ALPHABET 0123456789 ZM_API_SEED)

# Configure database.php
configure_file(app/Config/database.php.default "${CMAKE_CURRENT_BINARY_DIR}/app/Config/database.php" @ONLY)

# Configure core.php
configure_file(app/Config/core.php.default "${CMAKE_CURRENT_BINARY_DIR}/app/Config/core.php" @ONLY)

# Configure app/Config/bootstrap.php
configure_file(app/Config/bootstrap.php.in "${CMAKE_CURRENT_BINARY_DIR}/app/Config/bootstrap.php" @ONLY)

# Configure lib/Cake/bootstrap.php
configure_file(lib/Cake/bootstrap.php.in "${CMAKE_CURRENT_BINARY_DIR}/lib/Cake/bootstrap.php" @ONLY)
