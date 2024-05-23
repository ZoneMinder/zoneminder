#[=======================================================================[.rst:
FindV4L2
----------

Find V4L2 headers and libv4l2


This module accepts optional COMPONENTS:

  videodev2 (default)
  libv4l2

IMPORTED Targets
^^^^^^^^^^^^^^^^

This module defines the following :prop_tgt:`IMPORTED` targets::

``V4L2::videodev2``
  The Video for Linux Two header file, if found.
``V4L2::libv4l2``
  A thin abstraction layer on top of video4linux2 devices, if found.

Result Variables
^^^^^^^^^^^^^^^^

``V4L2_FOUND``
  System has v4l2 support. If no components are specified only the videodev2.h header has to be found.
``V4L2_INCLUDE_DIRS``
  The v4l2 include directories.
``V4L2_LIBRARIES``
  The libraries needed to have v4l2 support according to the specified components.
#]=======================================================================]

find_path(V4L2_VIDEODEV2_INCLUDE_DIR
  NAMES linux/videodev2.h)
mark_as_advanced(V4L2_VIDEODEV2_INCLUDE_DIR)

if(EXISTS "${V4L2_VIDEODEV2_INCLUDE_DIR}")
  set(V4L2_videodev2_FOUND TRUE)
else()
  set(V4L2_videodev2_FOUND FALSE)
endif()

pkg_check_modules(PC_V4L2_LIBV4L2 QUIET libv4l2)

find_path(V4L2_LIBV4L2_INCLUDE_DIR
  NAMES libv4l2.h
  HINTS
    ${PC_V4L2_LIBV4L2_INCLUDEDIR}
    ${PC_V4L2_LIBV4L2_INCLUDE_DIRS})
mark_as_advanced(V4L2_LIBV4L2_INCLUDE_DIR)

find_library(V4L2_LIBV4L2_LIBRARY
  NAMES ${PC_V4L2_LIBV4L2_LIBRARIES}
  HINTS
    ${PC_V4L2_LIBV4L2_LIBDIR}
    ${PC_V4L2_LIBV4L2_LIBRARY_DIR})
mark_as_advanced(V4L2_LIBV4L2_LIBRARY)

if(EXISTS "${V4L2_LIBV4L2_INCLUDE_DIR}" AND
  EXISTS "${V4L2_LIBV4L2_LIBRARY}")
  set(V4L2_libv4l2_FOUND TRUE)
else()
  set(V4L2_libv4l2_FOUND FALSE)
endif()

include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(V4L2
  REQUIRED_VARS
    V4L2_VIDEODEV2_INCLUDE_DIR
  HANDLE_COMPONENTS)

set(V4L2_INCLUDE_DIRS)
set(V4L2_LIBRARIES)

if(V4L2_videodev2_FOUND)
  set(V4L2_VIDEODEV2_INCLUDE_DIRS ${V4L2_VIDEODEV2_INCLUDE_DIR})
  list(APPEND V4L2_INCLUDE2_DIRS
    "${V4L2_VIDEODEV2_INCLUDE_DIRS}")

  add_library(V4L2::videodev2 INTERFACE IMPORTED)
  set_target_properties(V4L2::videodev2 PROPERTIES
    INTERFACE_INCLUDE_DIRECTORIES "${V4L2_VIDEODEV2_INCLUDE_DIRS}")
endif()

if(V4L2_libv4l2_FOUND)
  set(V4L2_LIBV4L2_INCLUDE_DIRS ${V4L2_LIBV4L2_INCLUDE_DIR})
  set(V4L2_LIBV4L2_LIBRARIES ${V4L2_LIBV4L2_LIBRARY})

  list(APPEND V4L2_INCLUDE_DIRS
    "${V4L2_LIBV4L2_INCLUDE_DIRS}")
  list(APPEND V4L2_LIBRARIES
    "${V4L2_LIBV4L2_LIBRARIES}")

  add_library(V4L2::libv4l2 UNKNOWN IMPORTED)
  set_target_properties(V4L2::libv4l2 PROPERTIES
    INTERFACE_INCLUDE_DIRECTORIES "${V4L2_LIBV4L2_INCLUDE_DIRS}"
    IMPORTED_LOCATION "${V4L2_LIBV4L2_LIBRARY}")
endif()
