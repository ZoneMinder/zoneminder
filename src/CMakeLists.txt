# CMakeLists.txt for the ZoneMinder binaries

# Create files from the .in files
configure_file(zm_config_data.h.in "${CMAKE_BINARY_DIR}/zm_config_data.h" @ONLY)

# Group together all the source files that are used by all the binaries (zmc, zmu, zms etc)
set(ZM_BIN_SRC_FILES
  zm_analysis_thread.cpp
  zm_buffer.cpp
  zm_camera.cpp
  zm_comms.cpp
  zm_config.cpp
  zm_curl_camera.cpp
  zm_crypt.cpp
  zm.cpp
  zm_db.cpp
  zm_decoder_thread.cpp
  zm_logger.cpp
  zm_event.cpp
  zm_eventstream.cpp
  zm_exception.cpp
  zm_fifo.cpp
  zm_fifo_debug.cpp
  zm_fifo_stream.cpp
  zm_file_camera.cpp
  zm_font.cpp
  zm_frame.cpp
  zm_group.cpp
  zm_image.cpp
  zm_jpeg.cpp
  zm_libvlc_camera.cpp
  zm_libvnc_camera.cpp
  zm_local_camera.cpp
  zm_monitor.cpp
  zm_monitorstream.cpp
  zm_ffmpeg.cpp
  zm_ffmpeg_camera.cpp
  zm_ffmpeg_input.cpp
  zm_mpeg.cpp
  zm_packet.cpp
  zm_packetqueue.cpp
  zm_poly.cpp
  zm_regexp.cpp
  zm_remote_camera.cpp
  zm_remote_camera_http.cpp
  zm_remote_camera_nvsocket.cpp
  zm_remote_camera_rtsp.cpp
  zm_rtp.cpp
  zm_rtp_ctrl.cpp
  zm_rtp_data.cpp
  zm_rtp_source.cpp
  zm_rtsp.cpp
  zm_rtsp_auth.cpp
  zm_rtsp_server_fifo_source.cpp
  zm_rtsp_server_fifo_adts_source.cpp
  zm_rtsp_server_fifo_h264_source.cpp
  zm_rtsp_server_fifo_audio_source.cpp
  zm_rtsp_server_fifo_video_source.cpp
  zm_sdp.cpp
  zm_signal.cpp
  zm_stream.cpp
  zm_swscale.cpp
  zm_user.cpp
  zm_utils.cpp
  zm_videostore.cpp
  zm_zone.cpp
  zm_storage.cpp)

# A fix for cmake recompiling the source files for every target.
add_library(zm STATIC ${ZM_BIN_SRC_FILES})

target_include_directories(zm
  PUBLIC
    ${CMAKE_BINARY_DIR}
    ${CMAKE_CURRENT_SOURCE_DIR})

target_link_libraries(zm
  PUBLIC
    FFMPEG::avcodec
    FFMPEG::avformat
    FFMPEG::avutil
    FFMPEG::swresample
    FFMPEG::swscale
    libbcrypt::bcrypt
    RtspServer::RtspServer
    martinmoene::span-lite
    ${ZM_BIN_LIBS}
  PRIVATE
    zm-core-interface)

if(${ZM_JWT_BACKEND} STREQUAL "jwt_cpp")
  target_link_libraries(zm
    PUBLIC
      jwt-cpp::jwt-cpp)
elseif(${ZM_JWT_BACKEND} STREQUAL "libjwt")
  target_link_libraries(zm
    PUBLIC
      JWT::libjwt)
endif()

if(TARGET V4L2::videodev2)
  target_link_libraries(zm
    PRIVATE
      V4L2::videodev2)
endif()

add_executable(zmc zmc.cpp)
add_executable(zms zms.cpp)
add_executable(zmu zmu.cpp)
add_executable(zmbenchmark zmbenchmark.cpp)

target_link_libraries(zmc
  PRIVATE
    zm-core-interface
    zm
    fmt::fmt
    ${ZM_EXTRA_LIBS}
    ${CMAKE_DL_LIBS})

target_link_libraries(zms
  PRIVATE
    zm-core-interface
    zm
    ${ZM_EXTRA_LIBS}
    ${CMAKE_DL_LIBS})

target_link_libraries(zmu
  PRIVATE
    zm-core-interface
    zm
    ${ZM_EXTRA_LIBS}
    ${CMAKE_DL_LIBS})

target_link_libraries(zmbenchmark
  PRIVATE
    zm-core-interface
    zm
    fmt::fmt
    ${ZM_EXTRA_LIBS}
    ${CMAKE_DL_LIBS})

# Generate man files for the binaries destined for the bin folder
if(BUILD_MAN)
  foreach(CBINARY zmc zmu)
    POD2MAN(${CMAKE_CURRENT_SOURCE_DIR}/${CBINARY}.cpp ${CBINARY} 8 ${ZM_MANPAGE_DEST_PREFIX})
  endforeach(CBINARY zmc zmu)
endif()

install(TARGETS zmc zmu RUNTIME DESTINATION "${CMAKE_INSTALL_FULL_BINDIR}" PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(TARGETS zms RUNTIME DESTINATION "${ZM_CGIDIR}" PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(CODE "execute_process(COMMAND ln -sf zms nph-zms WORKING_DIRECTORY ${CMAKE_CURRENT_BINARY_DIR})")
install(FILES ${CMAKE_CURRENT_BINARY_DIR}/nph-zms DESTINATION "${ZM_CGIDIR}")

if(HAVE_RTSP_SERVER)
  add_executable(zm_rtsp_server zm_rtsp_server.cpp)
  target_link_libraries(zm_rtsp_server
    PRIVATE
      zm-core-interface
      zm
      ${ZM_EXTRA_LIBS}
      ${CMAKE_DL_LIBS})
  install(TARGETS zm_rtsp_server RUNTIME DESTINATION "${CMAKE_INSTALL_FULL_BINDIR}" PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
endif()
