
####### Expanded from @PACKAGE_INIT@ by configure_package_config_file() #######
####### Any changes to this file will be overwritten by the next CMake run ####
####### The input file was jwt-cpp-config.cmake.in                            ########

get_filename_component(PACKAGE_PREFIX_DIR "${CMAKE_CURRENT_LIST_DIR}/../" ABSOLUTE)

macro(set_and_check _var _file)
  set(${_var} "${_file}")
  if(NOT EXISTS "${_file}")
    message(FATAL_ERROR "File or directory ${_file} referenced by variable ${_var} does not exist !")
  endif()
endmacro()

macro(check_required_components _NAME)
  foreach(comp ${${_NAME}_FIND_COMPONENTS})
    if(NOT ${_NAME}_${comp}_FOUND)
      if(${_NAME}_FIND_REQUIRED_${comp})
        set(${_NAME}_FOUND FALSE)
      endif()
    endif()
  endforeach()
endmacro()

####################################################################################

set(JWT_EXTERNAL_PICOJSON OFF)
set(JWT_SSL_LIBRARY OpenSSL)

include(CMakeFindDependencyMacro)
if(${JWT_SSL_LIBRARY} MATCHES "wolfSSL")
  find_dependency(PkgConfig REQUIRED)
  pkg_check_modules(wolfssl REQUIRED IMPORTED_TARGET wolfssl)
  list(TRANSFORM wolfssl_INCLUDE_DIRS APPEND "/wolfssl") # This is required to access OpenSSL compatibility API
else()
  find_dependency(${JWT_SSL_LIBRARY} REQUIRED)
endif()

if(JWT_EXTERNAL_PICOJSON)
  find_dependency(picojson REQUIRED)
endif()

include("${CMAKE_CURRENT_LIST_DIR}/jwt-cpp-targets.cmake")
