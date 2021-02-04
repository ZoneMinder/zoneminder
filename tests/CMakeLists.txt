# This file is part of the ZoneMinder Project.
#
# This file is free software; as a special exception the author gives
# unlimited permission to copy and/or distribute it, with or without
# modifications, as long as this notice is preserved.
#
# This program is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY, to the extent permitted by law; without even the
# implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.


include(Catch)

set(TEST_SOURCES
  zm_crypt.cpp)

add_executable(tests main.cpp ${TEST_SOURCES})

target_link_libraries(tests
  PRIVATE
    zm
    ${ZM_BIN_LIBS}
    bcrypt
    Catch2::Catch2)

target_include_directories(tests
  PRIVATE
    ${CMAKE_CURRENT_BINARY_DIR})

catch_discover_tests(tests)
