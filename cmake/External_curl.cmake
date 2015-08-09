# Do not check for cURL if ZM_NO_CURL is on
if(NOT ZM_NO_CURL)
	# cURL
	find_package(CURL REQUIRED)
	set(HAVE_LIBCURL 1)
	list(APPEND ZM_BIN_LIBS ${CURL_LIBRARIES})
	include_directories(${CURL_INCLUDE_DIRS})
	set(CMAKE_REQUIRED_INCLUDES ${CURL_INCLUDE_DIRS})
	check_include_file("curl/curl.h" HAVE_CURL_CURL_H)
	set(optlibsfound "${optlibsfound} cURL")
endif(NOT ZM_NO_CURL)
