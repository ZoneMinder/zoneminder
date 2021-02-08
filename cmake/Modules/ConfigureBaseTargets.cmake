add_library(zm-compile-option-interface INTERFACE)

# Use -std=c++11 instead of -std=gnu++11
set(CXX_EXTENSIONS OFF)

add_library(zm-feature-interface INTERFACE)

target_compile_features(zm-feature-interface
  INTERFACE
    cxx_std_11)

# Interface to set warning levels on targets
# It gets populated in the compiler specific script
add_library(zm-warning-interface INTERFACE)

# An interface used by all other interfaces
add_library(zm-default-interface INTERFACE)
target_link_libraries(zm-default-interface
  INTERFACE
    zm-compile-option-interface
    zm-feature-interface)

# An interface which provides the flags and definitions
# used by the non-dependency targets.
add_library(zm-core-interface INTERFACE)
target_link_libraries(zm-core-interface
  INTERFACE
    zm-default-interface
    zm-warning-interface)
