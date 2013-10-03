# CMakeLists.txt for the ZoneMinder perl scripts.

# Process the perl modules subdirectory
add_subdirectory(ZoneMinder ZoneMinder)

# Create files from the .in files
configure_file(zmaudit.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/zmaudit.pl @ONLY)
configure_file(zmcontrol.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/zmcontrol.pl @ONLY)
configure_file(zmdc.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/zmdc.pl @ONLY)
configure_file(zmfilter.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/zmfilter.pl @ONLY)
configure_file(zmpkg.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/zmpkg.pl @ONLY)
configure_file(zmtrack.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/zmtrack.pl @ONLY)
configure_file(zmtrigger.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/zmtrigger.pl @ONLY)
configure_file(zmupdate.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/zmupdate.pl @ONLY)
configure_file(zmvideo.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/zmvideo.pl @ONLY)
configure_file(zmwatch.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/zmwatch.pl @ONLY)
configure_file(zmx10.pl.in ${CMAKE_CURRENT_SOURCE_DIR}/zmx10.pl @ONLY)
#configure_file(zmdbbackup.in ${CMAKE_CURRENT_SOURCE_DIR}/zmdbbackup @ONLY)
#configure_file(zmdbrestore.in ${CMAKE_CURRENT_SOURCE_DIR}/zmdbrestore @ONLY)
#configure_file(zm.in ${CMAKE_CURRENT_SOURCE_DIR}/zm @ONLY)
#configure_file(zmeventdump.in ${CMAKE_CURRENT_SOURCE_DIR}/zmeventdump @ONLY)

# Install the perl scripts
install(FILES zmaudit.pl zmcontrol.pl zmdc.pl zmfilter.pl zmpkg.pl zmtrack.pl zmtrigger.pl zmupdate.pl zmvideo.pl zmwatch.pl zmx10.pl DESTINATION "${CMAKE_INSTALL_FULL_BINDIR}" PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
