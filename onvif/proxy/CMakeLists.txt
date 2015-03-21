

# Add build target for the perl modules
add_custom_target(zmonvifproxy ALL perl Makefile.PL FIRST_MAKEFILE=MakefilePerl PREFIX="${CMAKE_CURRENT_BINARY_DIR}/output" LIB="${CMAKE_CURRENT_BINARY_DIR}/output/${ZM_PERL_SUBPREFIX}" INSTALLSITEMAN3DIR="${CMAKE_CURRENT_BINARY_DIR}/output/${CMAKE_INSTALL_MANDIR}/man3" ${MAKEMAKER_NOECHO_COMMAND} COMMAND make --makefile=MakefilePerl COMMAND make --makefile=MakefilePerl pure_install COMMENT "Building ZoneMinder perl modules")

# Add install target for the perl modules
install(DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/output/" DESTINATION "${CMAKE_INSTALL_PREFIX}")

# Add additional files and directories to make clean
set_directory_properties(PROPERTIES ADDITIONAL_MAKE_CLEAN_FILES "output;blib;pm_to_blib;MakefilePerl")
