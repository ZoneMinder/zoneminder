if(TARGET boost_json)
  return()
endif()

unset(BOOSTJSON_INCLUDE_DIR CACHE)
find_path(BOOSTJSON_INCLUDE_DIR "boost/json.hpp" "boost/json/src.hpp")
if(EXISTS "${BOOSTJSON_INCLUDE_DIR}/boost/json.hpp")
  file(WRITE "${CMAKE_CURRENT_BINARY_DIR}/private-boost-json.cpp.in" "#include <boost/json/src.hpp>")
  configure_file("${CMAKE_CURRENT_BINARY_DIR}/private-boost-json.cpp.in" private-boost-json.cpp COPYONLY)
  add_library(boost_json "${BOOSTJSON_INCLUDE_DIR}/boost/json.hpp"
                         "${BOOSTJSON_INCLUDE_DIR}/boost/json/src.hpp"
                         "${CMAKE_CURRENT_BINARY_DIR}/private-boost-json.cpp")
  target_include_directories(boost_json PUBLIC ${BOOSTJSON_INCLUDE_DIR})
  target_compile_definitions(boost_json PUBLIC BOOST_JSON_STANDALONE)
  target_compile_features(boost_json PUBLIC cxx_std_17)
endif()
