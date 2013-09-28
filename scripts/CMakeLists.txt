# CMakeLists.txt for the ZoneMinder perl scripts.

# Process the perl modules subdirectory
add_subdirectory(ZoneMinder ZoneMinder)

# Create the directory for the final files to be copied
file(MAKE_DIRECTORY output)

# Create files from the .in files
configure_file(zmaudit.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmaudit.pl @ONLY)
configure_file(zmcontrol.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmcontrol.pl @ONLY)
configure_file(zmdc.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmdc.pl @ONLY)
configure_file(zmfilter.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmfilter.pl @ONLY)
configure_file(zmpkg.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmpkg.pl @ONLY)
configure_file(zmtrack.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmtrack.pl @ONLY)
configure_file(zmtrigger.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmtrigger.pl @ONLY)
configure_file(zmupdate.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmupdate.pl @ONLY)
configure_file(zmvideo.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmvideo.pl @ONLY)
configure_file(zmwatch.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmwatch.pl @ONLY)
configure_file(zmx10.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmx10.pl @ONLY)
#configure_file(zmdbbackup.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmdbbackup @ONLY)
#configure_file(zmdbrestore.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmdbrestore @ONLY)
#configure_file(zm.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zm @ONLY)
#configure_file(zmeventdump.in ${CMAKE_CURRENT_SOURCE_DIR}/output/zmeventdump @ONLY)

# Install the perl scripts
install(DIRECTORY output/ DESTINATION "${CMAKE_INSTALL_FULL_BINDIR}" FILE_PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
