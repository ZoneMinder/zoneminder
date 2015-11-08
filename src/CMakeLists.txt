# CMakeLists.txt for the ZoneMinder binaries

# Create files from the .in files
configure_file(zm_config.h.in "${CMAKE_CURRENT_BINARY_DIR}/zm_config.h" @ONLY)

# Group together all the source files that are used by all the binaries (zmc, zma, zmu, zms etc)
set(ZM_BIN_SRC_FILES zm_box.cpp zm_buffer.cpp zm_camera.cpp zm_comms.cpp zm_config.cpp zm_coord.cpp zm_curl_camera.cpp zm.cpp zm_db.cpp zm_logger.cpp zm_event.cpp zm_exception.cpp zm_file_camera.cpp zm_ffmpeg_camera.cpp  zm_image.cpp zm_jpeg.cpp zm_libvlc_camera.cpp zm_local_camera.cpp zm_monitor.cpp zm_ffmpeg.cpp zm_mpeg.cpp zm_poly.cpp zm_regexp.cpp zm_remote_camera.cpp zm_remote_camera_http.cpp zm_remote_camera_rtsp.cpp zm_rtp.cpp  zm_rtp_ctrl.cpp zm_rtp_data.cpp zm_rtp_source.cpp zm_rtsp.cpp zm_rtsp_auth.cpp zm_sdp.cpp zm_signal.cpp zm_stream.cpp zm_thread.cpp zm_time.cpp zm_timer.cpp zm_user.cpp zm_utils.cpp zm_zone.cpp)

if(ZM_PLUGIN_SUPPORT)
	set(ZM_BIN_SRC_FILES ${ZM_BIN_SRC_FILES} zm_detector.cpp zm_image_analyser.cpp zm_plugin.cpp zm_plugin_manager.cpp)
endif(ZM_PLUGIN_SUPPORT)

# with -fPIC
IF(UNIX AND NOT WIN32)
	FIND_PROGRAM(CMAKE_UNAME uname /bin /usr/bin /usr/local/bin )
	IF(CMAKE_UNAME)
		EXEC_PROGRAM(uname ARGS -m OUTPUT_VARIABLE CMAKE_SYSTEM_PROCESSOR)
		SET(CMAKE_SYSTEM_PROCESSOR ${CMAKE_SYSTEM_PROCESSOR} CACHE INTERNAL "processor type (i386 and x86_64)")
		IF(CMAKE_SYSTEM_PROCESSOR MATCHES "x86_64")
			ADD_DEFINITIONS(-fPIC)
		ENDIF(CMAKE_SYSTEM_PROCESSOR MATCHES "x86_64")
	ENDIF(CMAKE_UNAME)
ENDIF(UNIX AND NOT WIN32)

# A fix for cmake recompiling the source files for every target.
add_library(zoneminder STATIC ${ZM_BIN_SRC_FILES})

add_executable(zmc zmc.cpp)
add_executable(zma zma.cpp)
add_executable(zmu zmu.cpp)
add_executable(zmf zmf.cpp)
add_executable(zms zms.cpp)
add_executable(zmstreamer zmstreamer.cpp)

target_link_libraries(zmc -ldl zoneminder ${ZM_EXTRA_LIBS} ${ZM_BIN_LIBS})
target_link_libraries(zma -ldl zoneminder ${ZM_EXTRA_LIBS} ${ZM_BIN_LIBS})
target_link_libraries(zmu -ldl zoneminder ${ZM_EXTRA_LIBS} ${ZM_BIN_LIBS})
target_link_libraries(zmf -ldl zoneminder ${ZM_EXTRA_LIBS} ${ZM_BIN_LIBS})
target_link_libraries(zms -ldl zoneminder ${ZM_EXTRA_LIBS} ${ZM_BIN_LIBS})
target_link_libraries(zmstreamer -ldl zoneminder ${ZM_EXTRA_LIBS} ${ZM_BIN_LIBS})

# Generate man files for the binaries destined for the bin folder
FOREACH(CBINARY zma zmc zmf zmu zmstreamer)
    POD2MAN(${CMAKE_CURRENT_SOURCE_DIR}/${CBINARY}.cpp zoneminder-${CBINARY} 8)
ENDFOREACH(CBINARY zma zmc zmf zmu zmstreamer)

install(TARGETS zmc zma zmu zmf zmstreamer RUNTIME DESTINATION "${CMAKE_INSTALL_FULL_BINDIR}" PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(TARGETS zms RUNTIME DESTINATION "${ZM_CGIDIR}" PERMISSIONS OWNER_WRITE OWNER_READ OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
install(CODE "execute_process(COMMAND ln -sf zms nph-zms WORKING_DIRECTORY ${CMAKE_CURRENT_BINARY_DIR})" )
install(FILES ${CMAKE_CURRENT_BINARY_DIR}/nph-zms DESTINATION "${ZM_CGIDIR}")

if(ZM_PLUGIN_SUPPORT)
	install(FILES jinclude.h zm_db.h zm_detector.h zm_logger.h zm_poly.h zm_mem_utils.h zm_box.h zm_ffmpeg.h zm_plugin_manager.h zm_plugin.h zm_jpeg.h zm_mpeg.h zm_image_analyser.h zm_zone.h zm_rgb.h zm_utils.h zm_event.h zm_stream.h zm_coord.h zm_image.h DESTINATION include/zoneminder)
	install(FILES "${CMAKE_CURRENT_BINARY_DIR}/zm_config.h" "${CMAKE_CURRENT_BINARY_DIR}/zm_config_defines.h" DESTINATION include/zoneminder)
	install(TARGETS zoneminder ARCHIVE DESTINATION "${CMAKE_INSTALL_LIBDIR}")
endif(ZM_PLUGIN_SUPPORT)
