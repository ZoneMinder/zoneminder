# CMakeLists.txt for the ZoneMinder perl module.

# If this is an out-of-source build, copy the files we need to the binary directory
if(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))
	execute_process(COMMAND ${CMAKE_COMMAND} -E copy "${CMAKE_CURRENT_SOURCE_DIR}/Changes" "${CMAKE_CURRENT_BINARY_DIR}/Changes")
	execute_process(COMMAND ${CMAKE_COMMAND} -E copy "${CMAKE_CURRENT_SOURCE_DIR}/Makefile.PL" "${CMAKE_CURRENT_BINARY_DIR}/Makefile.PL")
	execute_process(COMMAND ${CMAKE_COMMAND} -E copy "${CMAKE_CURRENT_SOURCE_DIR}/MANIFEST" "${CMAKE_CURRENT_BINARY_DIR}/MANIFEST")
	execute_process(COMMAND ${CMAKE_COMMAND} -E copy "${CMAKE_CURRENT_SOURCE_DIR}/META.yml" "${CMAKE_CURRENT_BINARY_DIR}/META.yml")
	execute_process(COMMAND ${CMAKE_COMMAND} -E copy "${CMAKE_CURRENT_SOURCE_DIR}/README" "${CMAKE_CURRENT_BINARY_DIR}/README")
	execute_process(COMMAND ${CMAKE_COMMAND} -E copy_directory "${CMAKE_CURRENT_SOURCE_DIR}/t" "${CMAKE_CURRENT_BINARY_DIR}/t")
	execute_process(COMMAND ${CMAKE_COMMAND} -E copy_directory "${CMAKE_CURRENT_SOURCE_DIR}/lib" "${CMAKE_CURRENT_BINARY_DIR}/lib")
endif(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))

# Create files from the .in files
configure_file(lib/ZoneMinder/Base.pm.in "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/Base.pm" @ONLY)
configure_file(lib/ZoneMinder/Config.pm.in "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/Config.pm" @ONLY)
configure_file(lib/ZoneMinder/Memory.pm.in "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/Memory.pm" @ONLY)
configure_file(lib/ZoneMinder/ConfigData.pm.in "${CMAKE_CURRENT_BINARY_DIR}/lib/ZoneMinder/ConfigData.pm" @ONLY)

if(CMAKE_VERBOSE_MAKEFILE)
	set(MAKEMAKER_NOECHO_COMMAND "")
else(CMAKE_VERBOSE_MAKEFILE)
	set(MAKEMAKER_NOECHO_COMMAND "NOECHO=\"1>/dev/null\"")
endif(CMAKE_VERBOSE_MAKEFILE)

# Add build target for the perl modules
add_custom_target(zmperlmodules ALL perl Makefile.PL FIRST_MAKEFILE=MakefilePerl PREFIX="${CMAKE_CURRENT_BINARY_DIR}/output" LIB="${CMAKE_CURRENT_BINARY_DIR}/output/${ZM_PERL_SUBPREFIX}" INSTALLSITEMAN3DIR="${CMAKE_CURRENT_BINARY_DIR}/output/${CMAKE_INSTALL_MANDIR}/man3" ${MAKEMAKER_NOECHO_COMMAND} COMMAND make --makefile=MakefilePerl COMMAND make --makefile=MakefilePerl pure_install COMMENT "Building ZoneMinder perl modules")

# Add install target for the perl modules
install(DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/output/" DESTINATION "${CMAKE_INSTALL_PREFIX}")

# Add additional files and directories to make clean
set_directory_properties(PROPERTIES ADDITIONAL_MAKE_CLEAN_FILES "output;blib;pm_to_blib;MakefilePerl")
