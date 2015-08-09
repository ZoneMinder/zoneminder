# zlib
find_package(ZLIB REQUIRED)
set(HAVE_LIBZLIB 1)
list(APPEND ZM_BIN_LIBS "${ZLIB_LIBRARIES}")
include_directories("${ZLIB_INCLUDE_DIR}")
set(CMAKE_REQUIRED_INCLUDES "${ZLIB_INCLUDE_DIR}")
check_include_file("zlib.h" HAVE_ZLIB_H)
set(optlibsfound "${optlibsfound} zlib")
