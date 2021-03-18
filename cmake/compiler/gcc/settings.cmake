target_compile_options(zm-warning-interface
  INTERFACE
    -Wall
    -Wconditionally-supported
    -Wextra
    -Wformat-security
    -Wno-cast-function-type
    -Wno-type-limits
    -Wno-unused-parameter)

if(ASAN)
  target_compile_options(zm-compile-option-interface
    INTERFACE
      -fno-omit-frame-pointer
      -fsanitize=address
      -fsanitize-recover=address
      -fsanitize-address-use-after-scope)

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
