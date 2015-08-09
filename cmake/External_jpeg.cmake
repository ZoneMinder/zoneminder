# jpeg
find_package(JPEG REQUIRED)
set(HAVE_LIBJPEG 1)
list(APPEND ZM_BIN_LIBS "${JPEG_LIBRARIES}")
#link_directories(${JPEG_LIBRARY})
include_directories("${JPEG_INCLUDE_DIR}")
set(CMAKE_REQUIRED_INCLUDES "${JPEG_INCLUDE_DIR}")
check_include_files("stdio.h;jpeglib.h" HAVE_JPEGLIB_H)
