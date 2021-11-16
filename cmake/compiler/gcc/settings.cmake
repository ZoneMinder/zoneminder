target_compile_options(zm-warning-interface
  INTERFACE
    -Wall
    $<$<VERSION_GREATER:$<CXX_COMPILER_VERSION>,5.0>:-Wconditionally-supported>
    -Wextra
    -Wformat-security
    -Wno-cast-function-type
    $<$<VERSION_LESS:$<CXX_COMPILER_VERSION>,11>:-Wno-clobbered>
    $<$<VERSION_LESS:$<CXX_COMPILER_VERSION>,5.1>:-Wno-missing-field-initializers>
    -Wno-unused-parameter
    -Woverloaded-virtual
    -Wvla)

if(ENABLE_WERROR)
  target_compile_options(zm-warning-interface
    INTERFACE
      -Werror)
endif()

if(ASAN)
  target_compile_options(zm-compile-option-interface
    INTERFACE
      -D_GLIBCXX_SANITIZE_VECTOR=1
      -fno-omit-frame-pointer
      -fsanitize=address
      -fsanitize-recover=address
      -fsanitize-address-use-after-scope
      -Wno-stringop-truncation)

  target_link_options(zm-compile-option-interface
    INTERFACE
      -fno-omit-frame-pointer
      -fsanitize=address
      -fsanitize-recover=address
      -fsanitize-address-use-after-scope)

  message(STATUS "GCC: Enabled AddressSanitizer (ASan)")
endif()

if(TSAN)
  target_compile_options(zm-compile-option-interface
    INTERFACE
      -fsanitize=thread)

  target_link_options(zm-compile-option-interface
    INTERFACE
      -fsanitize=thread)

  message(STATUS "GCC: Enabled ThreadSanitizer (TSan)")
endif()
