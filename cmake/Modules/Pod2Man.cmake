#
# Copyright (C) 2012	Emmanuel Roullit <emmanuel.roullit@gmail.com>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or (at
# your option) any later version.
# 
# This program is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
# or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
# for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin St, Fifth Floor, Boston, MA 02110, USA
#

# Generate man pages of the project by using the
# POD header written in the tool source code.
# To use it, include this file in CMakeLists.txt and
# invoke POD2MAN(<podfile> <manfile> <section>)

MACRO(POD2MAN PODFILE MANFILE SECTION MANPAGE_DEST_PREFIX)
	FIND_PROGRAM(POD2MAN pod2man)
	FIND_PROGRAM(GZIP gzip)

	IF(NOT POD2MAN)
		MESSAGE(FATAL ERROR "Need pod2man installed to generate man page")
	ENDIF(NOT POD2MAN)

	IF(NOT GZIP)
		MESSAGE(FATAL ERROR "Need gzip installed to compress man page")
	ENDIF(NOT GZIP)

	IF(NOT EXISTS ${PODFILE})
		MESSAGE(FATAL ERROR "Could not find pod file ${PODFILE} to generate man page")
	ENDIF(NOT EXISTS ${PODFILE})

	ADD_CUSTOM_COMMAND(
		OUTPUT ${CMAKE_CURRENT_BINARY_DIR}/${MANFILE}.${SECTION}
		DEPENDS ${PODFILE}
		COMMAND ${POD2MAN}
		ARGS --section ${SECTION} --center ${CMAKE_PROJECT_NAME} --release --stderr --name ${MANFILE}
		${PODFILE} > ${CMAKE_CURRENT_BINARY_DIR}/${MANFILE}.${SECTION}
	)

	ADD_CUSTOM_COMMAND(
		OUTPUT ${CMAKE_CURRENT_BINARY_DIR}/${MANFILE}.${SECTION}.gz
		COMMAND ${GZIP} --best -c ${CMAKE_CURRENT_BINARY_DIR}/${MANFILE}.${SECTION} > ${CMAKE_CURRENT_BINARY_DIR}/${MANFILE}.${SECTION}.gz
		DEPENDS ${CMAKE_CURRENT_BINARY_DIR}/${MANFILE}.${SECTION}
	)

	SET(MANPAGE_TARGET "man-${MANFILE}")

	ADD_CUSTOM_TARGET(${MANPAGE_TARGET} ALL DEPENDS ${CMAKE_CURRENT_BINARY_DIR}/${MANFILE}.${SECTION}.gz)

	INSTALL(
		FILES ${CMAKE_CURRENT_BINARY_DIR}/${MANFILE}.${SECTION}.gz
		DESTINATION ${MANPAGE_DEST_PREFIX}/man${SECTION}
    	)
ENDMACRO(POD2MAN PODFILE MANFILE SECTION MANPAGE_DEST_PREFIX)

MACRO(ADD_MANPAGE_TARGET)
	# It is not possible add a dependency to target 'install'
	# Run hard-coded 'make man' when 'make install' is invoked
	INSTALL(CODE "EXECUTE_PROCESS(COMMAND make man)")
	ADD_CUSTOM_TARGET(man)
ENDMACRO(ADD_MANPAGE_TARGET)

