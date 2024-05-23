add_library(zm-compile-option-interface INTERFACE)

# Use -std=c++11 instead of -std=gnu++11
set(CMAKE_CXX_EXTENSIONS OFF)

add_library(zm-feature-interface INTERFACE)

# The cxx_std_* feature flags were only introduced in CMake 3.8
# Use to old way to specify the required standard level for older CMake versions.
# Remove this once we raise the required CMake version.
if(${CMAKE_VERSION} VERSION_LESS 3.8.0)
  set(CMAKE_CXX_STANDARD 11)
else()
  target_compile_features(zm-feature-interface
    INTERFACE
      cxx_std_11)
endif()

# Interface to set warning levels on targets.
# It gets populated in the compiler specific script.
add_library(zm-warning-interface INTERFACE)

# Interface which disables all warnings on the target.
add_library(zm-no-warning-interface INTERFACE)
target_compile_options(zm-no-warning-interface
  INTERFACE
    -w)

# An interface used by all other interfaces.
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

# An interface which provides the flags and definitions
# used by the external dependency targets.
add_library(zm-dependency-interface INTERFACE)
target_link_libraries(zm-dependency-interface
  INTERFACE
    zm-default-interface
    zm-no-warning-interface)
