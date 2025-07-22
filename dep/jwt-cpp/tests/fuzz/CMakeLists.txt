if(NOT ${CMAKE_CXX_COMPILER_ID} STREQUAL Clang)
  message(FATAL_ERROR "Fuzzing is only available on Clang")
endif()

function(ADD_FUZZING_EXECUTABLE TARGET)
  add_executable(jwt-cpp-fuzz-${TARGET} "${TARGET}.cpp")
  target_compile_options(
    jwt-cpp-fuzz-${TARGET}
    PRIVATE -g -O1 -fsanitize=fuzzer,address,signed-integer-overflow,undefined
            -fno-omit-frame-pointer)
  target_link_options(
    jwt-cpp-fuzz-${TARGET} PRIVATE
    -fsanitize=fuzzer,address,signed-integer-overflow,undefined
    -fno-omit-frame-pointer)
  target_link_libraries(jwt-cpp-fuzz-${TARGET} PRIVATE jwt-cpp::jwt-cpp)
endfunction()

add_fuzzing_executable(BaseEncodeFuzz)
add_fuzzing_executable(BaseDecodeFuzz)
add_fuzzing_executable(TokenDecodeFuzz)
