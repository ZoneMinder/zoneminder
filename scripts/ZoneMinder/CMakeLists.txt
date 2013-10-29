# CMakeLists.txt for the ZoneMinder perl module.

# Create files from the .in files
configure_file(lib/ZoneMinder/Base.pm.in ${CMAKE_CURRENT_SOURCE_DIR}/lib/ZoneMinder/Base.pm @ONLY)
configure_file(lib/ZoneMinder/Config.pm.in ${CMAKE_CURRENT_SOURCE_DIR}/lib/ZoneMinder/Config.pm @ONLY)
configure_file(lib/ZoneMinder/Memory.pm.in ${CMAKE_CURRENT_SOURCE_DIR}/lib/ZoneMinder/Memory.pm @ONLY)
configure_file(lib/ZoneMinder/ConfigData.pm.in ${CMAKE_CURRENT_SOURCE_DIR}/lib/ZoneMinder/ConfigData.pm @ONLY)

if(CMAKE_VERBOSE_MAKEFILE)
	set(MAKEMAKER_NOECHO_COMMAND "")
else(CMAKE_VERBOSE_MAKEFILE)
	set(MAKEMAKER_NOECHO_COMMAND "NOECHO=\"1>/dev/null\"")
endif(CMAKE_VERBOSE_MAKEFILE)

# Add build target for the perl modules
add_custom_target(zmperlmodules ALL perl Makefile.PL FIRST_MAKEFILE=MakefilePerl PREFIX=output LIB="output/${ZM_PERL_SUBPREFIX}" INSTALLMAN3DIR="output/${CMAKE_INSTALL_MANDIR}/man3" ${MAKEMAKER_NOECHO_COMMAND} COMMAND make --makefile=MakefilePerl COMMAND make --makefile=MakefilePerl pure_install COMMENT "Building ZoneMinder perl modules")

# Add install target for the perl modules
install(DIRECTORY output/ DESTINATION "${CMAKE_INSTALL_PREFIX}")

# Add additional commands to make clean
set_directory_properties(PROPERTIES ADDITIONAL_MAKE_CLEAN_FILES "output;blib;pm_to_blib;MakefilePerl")
