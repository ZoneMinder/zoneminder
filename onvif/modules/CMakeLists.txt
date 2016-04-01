# CMakeLists.txt for the ZoneMinder ONVIF modules.

# If this is an out-of-source build, copy the files we need to the binary directory
if(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))
	file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/Makefile.PL" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}")
	file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/lib" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}" PATTERN "*.in" EXCLUDE)
endif(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))

# MAKEMAKER_NOECHO_COMMAND previously defined in /scripts/zoneminder/CMakeLists.txt

# Add build target for the perl modules
add_custom_target(zmonvifmodules ALL perl Makefile.PL ${ZM_PERL_MM_PARMS} FIRST_MAKEFILE=MakefilePerl DESTDIR="${CMAKE_CURRENT_BINARY_DIR}/output" ${MAKEMAKER_NOECHO_COMMAND} COMMAND make -f MakefilePerl pure_install COMMENT "Building ZoneMinder perl ONVIF proxy module")

# Add install target for the perl modules
install(DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/output/" DESTINATION "/")

# Add additional files and directories to make clean
set_directory_properties(PROPERTIES ADDITIONAL_MAKE_CLEAN_FILES "output;blib;pm_to_blib;MakefilePerl")
