# CMakeLists.txt for the ZoneMinder perl module.

# Create files from the .in files
configure_file(lib/ZoneMinder/Base.pm.in ${CMAKE_CURRENT_SOURCE_DIR}/lib/ZoneMinder/Base.pm @ONLY)
configure_file(lib/ZoneMinder/Config.pm.in ${CMAKE_CURRENT_SOURCE_DIR}/lib/ZoneMinder/Config.pm @ONLY)
configure_file(lib/ZoneMinder/Memory.pm.in ${CMAKE_CURRENT_SOURCE_DIR}/lib/ZoneMinder/Memory.pm @ONLY)
configure_file(lib/ZoneMinder/ConfigData.pm.in ${CMAKE_CURRENT_SOURCE_DIR}/lib/ZoneMinder/ConfigData.pm @ONLY)

# Add build target for the perl modules
add_custom_target(zmperlmodules ALL perl Makefile.PL FIRST_MAKEFILE=MakefilePerl INSTALL_BASE=output INSTALLPRIVLIB="output/${CMAKE_INSTALL_LIBDIR}/perl5" INSTALLARCHLIB="optout/${CMAKE_INSTALL_LIBDIR}/perl5/arch" INSTALLSITELIB="output/${CMAKE_INSTALL_LIBDIR}/perl5" INSTALLSITEARCH="optout/${CMAKE_INSTALL_LIBDIR}/perl5/arch" COMMAND make --makefile=MakefilePerl COMMAND make --makefile=MakefilePerl pure_install COMMENT "Building ZoneMinder perl modules")

# Add install target for the perl modules
install(DIRECTORY output/ DESTINATION "${CMAKE_INSTALL_PREFIX}")






