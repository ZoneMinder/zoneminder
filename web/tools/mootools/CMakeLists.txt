# CMakeLists.txt for the Mootools
# The only purpose of this file is to create the symlinks required.

# Find the latest mootools-core version and create a symlink mootools-core.js to it
file(GLOB mtcorelist RELATIVE "${CMAKE_CURRENT_SOURCE_DIR}" "mootools-core-*.js")
if(NOT mtcorelist)
	message(WARNING " Unable to find mootools-core files")
else(NOT mtcorelist)
	list(SORT mtcorelist)
	list(LENGTH mtcorelist mtcorelistcount)
	math(EXPR mtcoreindex "${mtcorelistcount} - 1")
	list(GET mtcorelist ${mtcoreindex} mtcorelatest)
	message(STATUS "Using mootools core file: ${mtcorelatest}")
	execute_process(COMMAND ln -f -s "${mtcorelatest}" "${CMAKE_CURRENT_BINARY_DIR}/mootools-core.js" RESULT_VARIABLE mtcoreresult)
	if(mtcoreresult)
		message(WARNING " Failed creating the required symlinks for mootools-core. Exit code: ${mtcoreresult}")
	endif(mtcoreresult)
endif(NOT mtcorelist)

# Find the latest mootools-more version and create a symlink mootools-more.js to it
file(GLOB mtmorelist RELATIVE "${CMAKE_CURRENT_SOURCE_DIR}" "mootools-more-*.js")
if(NOT mtmorelist)
	message(WARNING " Unable to find mootools-more files")
else(NOT mtmorelist)
	list(SORT mtmorelist)
	list(LENGTH mtmorelist mtmorelistcount)
	math(EXPR mtmoreindex "${mtmorelistcount} - 1")
	list(GET mtmorelist ${mtmoreindex} mtmorelatest)
	message(STATUS "Using mootools more file: ${mtmorelatest}")
	execute_process(COMMAND ln -f -s "${mtmorelatest}" "${CMAKE_CURRENT_BINARY_DIR}/mootools-more.js" RESULT_VARIABLE mtmoreresult)
	if(mtmoreresult)
		message(WARNING " Failed creating the required symlinks for mootools-more. Exit code: ${mtmoreresult}")
	endif(mtmoreresult)
endif(NOT mtmorelist)

# If this is an out-of-source build, copy the mootools files we picked to the binary directory
# This is required to fix a cmake bug regarding installing symlinks pointing to nonexistent files
if(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))
	execute_process(COMMAND ${CMAKE_COMMAND} -E copy "${CMAKE_CURRENT_SOURCE_DIR}/${mtcorelatest}" "${CMAKE_CURRENT_BINARY_DIR}/${mtcorelatest}")
	execute_process(COMMAND ${CMAKE_COMMAND} -E copy "${CMAKE_CURRENT_SOURCE_DIR}/${mtmorelatest}" "${CMAKE_CURRENT_BINARY_DIR}/${mtmorelatest}")
endif(NOT (CMAKE_BINARY_DIR STREQUAL CMAKE_SOURCE_DIR))

