# CMakeLists.txt for openalpr plugin.

add_library(zm_plugin_openalpr SHARED openalpr_plugin.cpp)
include_directories(${zoneminder_SOURCE_DIR}/src ${zoneminder_BINARY_DIR}/src)

target_link_libraries(
    zm_plugin_openalpr -lopenalpr -lboost_program_options -lopencv_core
        -lopencv_imgproc)

# Do not set the soname when linking the plugin binary.
# Definition of a soname version in a plugin prevents some tools
# (like dpkg-shlibdeps for debian) to clearly identify the binary as a plugin.
# This results in a bunch of unwanted warnings about unresolvable reference to
# symbols (which are provided by the program that loads this plugins).
set_property(TARGET zm_plugin_openalpr PROPERTY NO_SONAME 1)

install (TARGETS zm_plugin_openalpr DESTINATION ${ZM_PLUGINSPKGLIBDIR})
