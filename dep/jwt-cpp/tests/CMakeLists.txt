if(JWT_DISABLE_BASE64)
  message(FATAL_ERROR "Tests requires the base64 support to be enabled!")
endif()

if(JWT_DISABLE_PICOJSON)
  message(FATAL_ERROR "Tests requires the picojson support to be enabled!")
endif()

enable_testing()
include(GoogleTest)
if(HUNTER_ENABLED)
  hunter_add_package(GTest)
endif()
find_package(GTest REQUIRED)

set(TEST_SOURCES
    ${CMAKE_CURRENT_SOURCE_DIR}/BaseTest.cpp ${CMAKE_CURRENT_SOURCE_DIR}/ClaimTest.cpp
    ${CMAKE_CURRENT_SOURCE_DIR}/Keys.cpp ${CMAKE_CURRENT_SOURCE_DIR}/HelperTest.cpp
    ${CMAKE_CURRENT_SOURCE_DIR}/TestMain.cpp ${CMAKE_CURRENT_SOURCE_DIR}/TokenFormatTest.cpp
    ${CMAKE_CURRENT_SOURCE_DIR}/TokenTest.cpp ${CMAKE_CURRENT_SOURCE_DIR}/JwksTest.cpp
    ${CMAKE_CURRENT_SOURCE_DIR}/OpenSSLErrorTest.cpp ${CMAKE_CURRENT_SOURCE_DIR}/traits/NlohmannTest.cpp)

find_package(jsoncons CONFIG)
if(TARGET jsoncons)
  list(APPEND TEST_SOURCES ${CMAKE_CURRENT_SOURCE_DIR}/traits/JsonconsTest.cpp)
endif()

include("private-find-boost-json")
if(TARGET boost_json)
  list(APPEND TEST_SOURCES ${CMAKE_CURRENT_SOURCE_DIR}/traits/BoostJsonTest.cpp)
endif()

add_executable(jwt-cpp-test ${TEST_SOURCES})

# NOTE: Don't use space inside a generator expression here, because the function prematurely breaks the expression into
# multiple lines. https://cmake.org/pipermail/cmake/2018-April/067422.html
set(JWT_TESTER_GCC_FLAGS -Wall -Wextra -Wpedantic)
set(JWT_TESTER_CLANG_FLAGS -Weverything -Wno-c++98-compat -Wno-global-constructors -Wno-weak-vtables)
target_compile_options(
  jwt-cpp-test PRIVATE $<$<CXX_COMPILER_ID:MSVC>:/W4> $<$<CXX_COMPILER_ID:GNU>:${JWT_TESTER_GCC_FLAGS}>
                       $<$<CXX_COMPILER_ID:Clang>:${JWT_TESTER_CLANG_FLAGS}>)
if(HUNTER_ENABLED)
  target_link_libraries(jwt-cpp-test PRIVATE GTest::gtest GTest::gtest_main)
  # Define a compile define to bypass openssl error tests
  target_compile_definitions(jwt-cpp-test PRIVATE HUNTER_ENABLED=1)
else()
  target_link_libraries(jwt-cpp-test PRIVATE GTest::GTest GTest::Main)
  if(TARGET jsoncons)
    target_link_libraries(jwt-cpp-test PRIVATE jsoncons)
  endif()
  if(TARGET boost_json)
    target_link_libraries(jwt-cpp-test PRIVATE boost_json)
  endif()
endif()
target_link_libraries(jwt-cpp-test PRIVATE jwt-cpp nlohmann_json::nlohmann_json
                                           $<$<NOT:$<CXX_COMPILER_ID:MSVC>>:${CMAKE_DL_LIBS}>)

gtest_add_tests(TARGET jwt-cpp-test)

if(JWT_ENABLE_COVERAGE)
  include("code-coverage")
  setup_coverage(jwt-cpp-test)
  set(COVERAGE_EXCLUDES "/usr/**" "/home/*/.conan/**" "*test*" "*build*" "**/nlohmann/json.hpp"
                        "**/picojson/picojson.h" "*boost*" "*jsoncons*")
  setup_target_for_coverage_lcov(NAME coverage EXECUTABLE ${CMAKE_CURRENT_BINARY_DIR}/jwt-cpp-test)
endif()
