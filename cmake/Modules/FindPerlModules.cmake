# - try to find perl modules, passed as COMPONENTS
#
# Non-cache variable you might use in your CMakeLists.txt:
#  PERLMODULES_FOUND
#
# Requires these CMake modules:
#  FindPackageHandleStandardArgs (known included with CMake >=2.6.2)
#
# Original Author:
# 2012 Ryan Pavlik <rpavlik@iastate.edu> <abiryan@ryand.net>
# http://academic.cleardefinition.com
# Iowa State University HCI Graduate Program/VRAC
#
# Copyright Iowa State University 2012.
# Distributed under the Boost Software License, Version 1.0.
# (See accompanying file LICENSE_1_0.txt or copy at
# http://www.boost.org/LICENSE_1_0.txt)

if(NOT PERL_FOUND)
	find_package(Perl QUIET)
endif()

set(_deps_check)
if(PERL_FOUND)
	foreach(module ${PerlModules_FIND_COMPONENTS})
		string(REPLACE "::" "/" modfilename "${module}.pm")
		string(REPLACE "::" "_" modvarname "PERLMODULES_${module}_MODULE")
		string(TOUPPER "${modvarname}" modvarname)
		list(APPEND _deps_check ${modvarname})
		if(NOT ${modvarname})
			if(NOT PerlModules_FIND_QUIETLY)
				message(STATUS "Checking for perl module ${module}")
			endif()
			execute_process(COMMAND
				"${PERL_EXECUTABLE}"
				"-e"
				"use ${module}; print \$INC{\"${modfilename}\"}"
				RESULT_VARIABLE result_code
				OUTPUT_VARIABLE filename
				ERROR_VARIABLE error_info
				OUTPUT_STRIP_TRAILING_WHITESPACE)
			if(result_code EQUAL 0)
				if(NOT PerlModules_FIND_QUIETLY)
					message(STATUS
						"Checking for perl module ${module} - found at ${filename}")
				endif()
				set(${modvarname}
					"${filename}"
					CACHE
					FILEPATH
					"Location found for module ${module}"
					FORCE)
				mark_as_advanced(${modvarname})
			else()
				if(NOT PerlModules_FIND_QUIETLY)
					message(STATUS "Checking for perl module ${module} - failed")
				endif()
				set(${modvarname}
					"NOTFOUND"
					CACHE
					FILEPATH
					"No location found for module ${module}"
					FORCE)
				file(APPEND
					${CMAKE_BINARY_DIR}${CMAKE_FILES_DIRECTORY}/CMakeError.log
					"Determining if the Perl module ${module} exists failed with the following error output:\n"
					"${error_info}\n\n")
			endif()
		endif()
	endforeach()
endif()

include(FindPackageHandleStandardArgs)
find_package_handle_standard_args(PerlModules
	DEFAULT_MSG
	PERL_FOUND
	${_deps_check})

