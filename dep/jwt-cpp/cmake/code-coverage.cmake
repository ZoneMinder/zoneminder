set(COVERAGE_CMAKE "${CMAKE_BINARY_DIR}/cmake/CodeCoverage.cmake")
if(NOT EXISTS ${COVERAGE_CMAKE})
  set(COVERAGE_URL "https://raw.githubusercontent.com/bilke/cmake-modules/master/CodeCoverage.cmake")
  file(DOWNLOAD ${COVERAGE_URL} ${COVERAGE_CMAKE})
endif()

include(${COVERAGE_CMAKE})

function(setup_coverage TARGET)
  target_compile_options(${TARGET} PRIVATE -g -O0 -fprofile-arcs -ftest-coverage)
  target_link_libraries(${TARGET} PRIVATE gcov)
endfunction()
