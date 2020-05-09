# CMakeLists.txt for the ZoneMinder perl scripts.

# If this is an out-of-source build, copy the files we need to the binary directory
if(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))
	file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/zmonvif-probe.pl" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}")
endif(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))

# Install the perl scripts
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zmonvif-probe.pl"  DESTINATION "${CMAKE_INSTALL_FULL_BINDIR}" PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
