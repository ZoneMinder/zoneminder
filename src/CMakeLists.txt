# CMakeLists.txt for the ZoneMinder binaries

# Create files from the .in files
configure_file(zm_config.h.in "${CMAKE_CURRENT_BINARY_DIR}/zm_config.h" @ONLY)

# Group together all the source files that are used by all the binaries (zmc, zma, zmu, zms etc)
set(ZM_BIN_SRC_FILES zm_box.cpp zm_buffer.cpp zm_camera.cpp zm_comms.cpp zm_config.cpp zm_coord.cpp zm_curl_camera.cpp zm.cpp zm_db.cpp zm_logger.cpp zm_event.cpp zm_frame.cpp zm_eventstream.cpp zm_exception.cpp zm_file_camera.cpp zm_ffmpeg_input.cpp zm_ffmpeg_camera.cpp zm_group.cpp zm_image.cpp zm_jpeg.cpp zm_libvlc_camera.cpp zm_libvnc_camera.cpp zm_local_camera.cpp zm_monitor.cpp zm_monitorstream.cpp zm_ffmpeg.cpp zm_mpeg.cpp zm_packet.cpp zm_packetqueue.cpp zm_poly.cpp zm_regexp.cpp zm_remote_camera.cpp zm_remote_camera_http.cpp zm_remote_camera_nvsocket.cpp zm_remote_camera_rtsp.cpp zm_rtp.cpp zm_rtp_ctrl.cpp zm_rtp_data.cpp zm_rtp_source.cpp zm_rtsp.cpp zm_rtsp_auth.cpp zm_sdp.cpp zm_signal.cpp zm_stream.cpp zm_swscale.cpp zm_thread.cpp zm_time.cpp zm_timer.cpp zm_user.cpp zm_utils.cpp zm_video.cpp zm_videostore.cpp zm_zone.cpp zm_storage.cpp zm_fifo.cpp zm_crypt.cpp)


# A fix for cmake recompiling the source files for every target.
add_library(zm STATIC ${ZM_BIN_SRC_FILES})
link_directories(libbcrypt)

add_executable(zmc zmc.cpp)
add_executable(zma zma.cpp)
add_executable(zmu zmu.cpp)
add_executable(zms zms.cpp)

# JWT is a header only library. 
include_directories(libbcrypt/include/bcrypt)
include_directories(jwt-cpp/include/jwt-cpp)

target_link_libraries(zmc zm ${ZM_EXTRA_LIBS} ${ZM_BIN_LIBS} ${CMAKE_DL_LIBS})
target_link_libraries(zma zm ${ZM_EXTRA_LIBS} ${ZM_BIN_LIBS} ${CMAKE_DL_LIBS})
target_link_libraries(zmu zm ${ZM_EXTRA_LIBS} ${ZM_BIN_LIBS} ${CMAKE_DL_LIBS} bcrypt)
target_link_libraries(zms zm ${ZM_EXTRA_LIBS} ${ZM_BIN_LIBS} ${CMAKE_DL_LIBS} bcrypt)

# Generate man files for the binaries destined for the bin folder
FOREACH(CBINARY zma zmc zmu)
    POD2MAN(${CMAKE_CURRENT_SOURCE_DIR}/${CBINARY}.cpp zoneminder-${CBINARY} 8 ${ZM_MANPAGE_DEST_PREFIX})
ENDFOREACH(CBINARY zma zmc zmu)

install(TARGETS zmc zma zmu RUNTIME DESTINATION "${CMAKE_INSTALL_FULL_BINDIR}" PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(TARGETS zms RUNTIME DESTINATION "${ZM_CGIDIR}" PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(CODE "execute_process(COMMAND ln -sf zms nph-zms WORKING_DIRECTORY ${CMAKE_CURRENT_BINARY_DIR})" )
install(FILES ${CMAKE_CURRENT_BINARY_DIR}/nph-zms DESTINATION "${ZM_CGIDIR}")

