# CMakeLists.txt for the ZoneMinder perl module.

# If this is an out-of-source build, copy the files we need to the binary directory
if(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))
	file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/Changes" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}")
	file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/Makefile.PL" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}")
	file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/MANIFEST" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}")
	file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/META.yml" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}")
	file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/README" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}")
	file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/t" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}")
	file(COPY "${CMAKE_CURRENT_SOURCE_DIR}/lib" DESTINATION "${CMAKE_CURRENT_BINARY_DIR}" PATTERN "*.in" EXCLUDE)
endif(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))

# Create files from the .in files
configure_file(lib/ZoneMinder/Base.pm.in "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/Base.pm" @ONLY)
configure_file(lib/ZoneMinder/Config.pm.in "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/Config.pm" @ONLY)
configure_file(lib/ZoneMinder/Memory.pm.in "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/Memory.pm" @ONLY)
configure_file(lib/ZoneMinder/ConfigData.pm.in "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/ConfigData.pm" @ONLY)
configure_file(lib/ZoneMinder/ONVIF.pm.in "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/ONVIF.pm" @ONLY)

if(CMAKE_VERBOSE_MAKEFILE)
	set(MAKEMAKER_NOECHO_COMMAND "")
else(CMAKE_VERBOSE_MAKEFILE)
	set(MAKEMAKER_NOECHO_COMMAND "NOECHO=\"1>/dev/null\"")
endif(CMAKE_VERBOSE_MAKEFILE)

# Add build target for the perl modules
add_custom_target(zmperlmodules ALL perl Makefile.PL ${ZM_PERL_MM_PARMS} FIRST_MAKEFILE=MakefilePerl DESTDIR="${CMAKE_CURRENT_BINARY_DIR}/output" ${MAKEMAKER_NOECHO_COMMAND} COMMAND make -f MakefilePerl pure_install COMMENT "Building ZoneMinder perl modules")

# Add install target for the perl modules
install(DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/output/" DESTINATION "/")

# Add additional files and directories to make clean
set_directory_properties(PROPERTIES ADDITIONAL_MAKE_CLEAN_FILES "output;blib;pm_to_blib;MakefilePerl")
