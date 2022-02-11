add_library(span-lite INTERFACE)
add_library(martinmoene::span-lite ALIAS span-lite)

target_include_directories(span-lite INTERFACE
  ${CMAKE_CURRENT_SOURCE_DIR}/include)
