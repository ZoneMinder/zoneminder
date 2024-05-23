#[=======================================================================[.rst:
FindFFMPEG
----------

Find the FFmpeg and associated libraries.


This module accepts following COMPONENTS::

  avcodec
  avdevice
  avfilter
  avformat
  avutil
  swresample
  swscale

IMPORTED Targets
^^^^^^^^^^^^^^^^

This module defines the following :prop_tgt:`IMPORTED` targets:

``FFMPEG::<component>``
  The FFmpeg component.

Result Variables
^^^^^^^^^^^^^^^^

``FFMPEG_INCLUDE_DIRS``
  Include directories necessary to use FFmpeg.
``FFMPEG_LIBRARIES``
  Libraries necessary to use FFmpeg. Note that this only includes libraries for the components requested.
``FFMPEG_VERSION``
  The version of FFMPEG found (avutil).


For each component, the following are provided:

``FFMPEG_<component>_FOUND``
  FFmpeg component was found.
``FFMPEG_<component>_INCLUDE_DIRS``
  Include directories for the component.
``FFMPEG_<component>_LIBRARIES``
  Libraries for the component.

#]=======================================================================]

function(_ffmpeg_find component pkgconfig_name header)
  find_package(PkgConfig)
  pkg_check_modules(PC_FFMPEG_${component} ${pkgconfig_name})

  find_path(FFMPEG_${component}_INCLUDE_DIR
    NAMES "lib${component}/${header}"
    HINTS
      ${PC_FFMPEG_${component}_INCLUDEDIR}
      ${PC_FFMPEG_${component}_INCLUDE_DIRS}
    PATH_SUFFIXES
      ffmpeg)
  mark_as_advanced("FFMPEG_${component}_INCLUDE_DIR")

  find_library(FFMPEG_${component}_LIBRARY
    NAMES
      ${component}
      ${PC_FFMPEG_${component}_LIBRARIES}
    HINTS
      ${PC_FFMPEG_${component}_LIBDIR}
      ${PC_FFMPEG_${component}_LIBRARY_DIRS})
  mark_as_advanced("${component}_LIBRARY")

  if(FFMPEG_${component}_LIBRARY AND FFMPEG_${component}_INCLUDE_DIR)
    set(_deps_found TRUE)
    set(_deps_link)
    foreach(_ffmpeg_dep IN LISTS ARGN)
      if(TARGET "FFMPEG::${_ffmpeg_dep}")
        list(APPEND _deps_link "FFMPEG::${_ffmpeg_dep}")
      else()
        set(_deps_found FALSE)
      endif()
    endforeach()
    if(_deps_found)
      if(NOT TARGET "FFMPEG::${component}")
        add_library("FFMPEG::${component}" UNKNOWN IMPORTED)
        set_target_properties("FFMPEG::${component}" PROPERTIES
          IMPORTED_LOCATION "${FFMPEG_${component}_LIBRARY}"
          INTERFACE_INCLUDE_DIRECTORIES "${FFMPEG_${component}_INCLUDE_DIR}"
          IMPORTED_LINK_INTERFACE_LIBRARIES "${_deps_link}")
      endif()
      set(FFMPEG_${component}_FOUND 1 PARENT_SCOPE)
      set(FFMPEG_${component}_VERSION "${PC_FFMPEG_${component}_VERSION}" PARENT_SCOPE)
    else()
      set("FFMPEG_${component}_FOUND" 0 PARENT_SCOPE)
      set(what)
      if(NOT FFMPEG_${component}_LIBRARY)
        set(what "library")
      endif()
      if(NOT FFMPEG_${component}_INCLUDE_DIR)
        if(what)
          string(APPEND what " or headers")
        else()
          set(what "headers")
        endif()
      endif()
      set("FFMPEG_${component}_NOT_FOUND_MESSAGE"
        "Could not find the ${what} for ${component}."
        PARENT_SCOPE)
    endif()
  endif()
endfunction()

_ffmpeg_find(avutil libavutil avutil.h)
_ffmpeg_find(swresample libswresample swresample.h
  avutil)
_ffmpeg_find(swscale libswscale swscale.h
  avutil)
_ffmpeg_find(avcodec libavcodec avcodec.h
  avutil)
_ffmpeg_find(avformat libavformat avformat.h
  avcodec avutil)
_ffmpeg_find(avfilter libavfilter avfilter.h
  avutil)
_ffmpeg_find(avdevice libavdevice avdevice.h
  avformat avutil)

if(TARGET FFMPEG::avutil)
  set(FFMPEG_VERSION "${FFMPEG_avutil_VERSION}")
endif()

set(FFMPEG_INCLUDE_DIRS)
set(FFMPEG_LIBRARIES)
set(_ffmpeg_required_vars)
foreach(_ffmpeg_component IN LISTS FFMPEG_FIND_COMPONENTS)
  if(TARGET "FFMPEG::${_ffmpeg_component}")
    set(FFMPEG_${_ffmpeg_component}_INCLUDE_DIRS
      "${FFMPEG_${_ffmpeg_component}_INCLUDE_DIR}")
    set(FFMPEG_${_ffmpeg_component}_LIBRARIES
      "${FFMPEG_${_ffmpeg_component}_LIBRARY}")
    list(APPEND FFMPEG_INCLUDE_DIRS
      "${FFMPEG_${_ffmpeg_component}_INCLUDE_DIRS}")
    list(APPEND FFMPEG_LIBRARIES
      "${FFMPEG_${_ffmpeg_component}_LIBRARIES}")
    if(FFMEG_FIND_REQUIRED_${_ffmpeg_component})
      list(APPEND _ffmpeg_required_vars
        "FFMPEG_${_ffmpeg_required_vars}_INCLUDE_DIRS"
        "FFMPEG_${_ffmpeg_required_vars}_LIBRARIES")
    endif()
  endif()
endforeach()
unset(_ffmpeg_component)

if(FFMPEG_INCLUDE_DIRS)
  list(REMOVE_DUPLICATES FFMPEG_INCLUDE_DIRS)
endif()

include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(FFMPEG
  REQUIRED_VARS
    FFMPEG_INCLUDE_DIRS
    FFMPEG_LIBRARIES
    ${_ffmpeg_required_vars}
  VERSION_VAR
    FFMPEG_VERSION
  HANDLE_COMPONENTS)
unset(_ffmpeg_required_vars)
