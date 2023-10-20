cmake_minimum_required(VERSION 3.8)
project(jwt-cpp-installation-tests)

set(TEST CACHE STRING "The test source file to be used")

find_package(jwt-cpp 0.6.0 EXACT REQUIRED CONFIG)

add_executable(test-project ${TEST}.cpp)
target_link_libraries(test-project jwt-cpp::jwt-cpp)
