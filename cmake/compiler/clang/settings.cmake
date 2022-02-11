target_compile_options(zm-warning-interface
  INTERFACE
    -Wall
    -Wextra
    -Wimplicit-fallthrough
    -Wno-unused-parameter
    -Wvla)

if(ENABLE_WERROR)
  target_compile_options(zm-warning-interface
    INTERFACE
      -Werror)
endif()

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

  message(STATUS "Clang: Enabled AddressSanitizer (ASan)")
endif()

if(TSAN)
  target_compile_options(zm-compile-option-interface
    INTERFACE
      -fsanitize=thread)

  target_link_options(zm-compile-option-interface
    INTERFACE
      -fsanitize=thread)

  message(STATUS "Clang: Enabled ThreadSanitizer (TSan)")
endif()
