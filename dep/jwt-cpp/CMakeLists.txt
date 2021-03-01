add_library(jwt-cpp INTERFACE)
add_library(jwt-cpp::jwt-cpp ALIAS jwt-cpp)

target_include_directories(jwt-cpp
  INTERFACE
    ${CMAKE_CURRENT_SOURCE_DIR}/include)
