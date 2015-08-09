# OpenSSL
find_package(OpenSSL REQUIRED)
set(HAVE_LIBOPENSSL 1)
set(HAVE_LIBCRYPTO 1)
list(APPEND ZM_BIN_LIBS "${OPENSSL_LIBRARIES}")
include_directories("${OPENSSL_INCLUDE_DIR}")
set(CMAKE_REQUIRED_INCLUDES "${OPENSSL_INCLUDE_DIR}")
check_include_file("openssl/md5.h" HAVE_OPENSSL_MD5_H)
set(optlibsfound "${optlibsfound} OpenSSL")
