    # - Try to find Polkit
    # Once done this will define
    #
    # POLKIT_FOUND - system has Polkit
    # POLKIT_INCLUDE_DIRS - Polkit's include directories
    # POLKIT_AGENT_INCLUDE_DIRS - Polkit-agent's include directories
    # POLKIT_LIBRARIES - Link this to use polkit's gobject library
    # POLKIT_AGENT_LIBRARY - Link this to use the agent wrapper in polkit
    # POLKIT_DEFINITIONS - Compiler switches required for using Polkit
    # Copyright (c) 2009, Dario Freddi, <drf@kde.org>
    #
    # Redistribution and use is allowed according to the terms of the BSD license.
    # For details see the accompanying COPYING-CMAKE-SCRIPTS file.
    #NOTE: Polkit agent library is disabled!
    if (POLKIT_INCLUDE_DIR AND POLKIT_LIB)
    set(POLKIT_FIND_QUIETLY TRUE)
    endif (POLKIT_INCLUDE_DIR AND POLKIT_LIB)
    if (NOT WIN32)
    # use pkg-config to get the directories and then use these values
    # in the FIND_PATH() and FIND_LIBRARY() calls
    find_package(PkgConfig)
    pkg_search_module(PC_POLKIT polkit-gobject-1 polkit)
    #pkg_check_modules(PC_POLKIT_AGENT polkit-agent-1)
    set(POLKIT_DEFINITIONS ${PC_POLKIT_CFLAGS_OTHER})
    endif (NOT WIN32)
    # We must include glib paths too... which sucks balls
    find_package(GLIB2)
    find_path( GLIB_CONFIG_INCLUDE_DIR
    NAMES glibconfig.h
    PATH_SUFFIXES glib-2.0/include
    HINTS ${PC_POLKIT_INCLUDE_DIRS}
    )
    find_path( POLKIT_INCLUDE_DIR
    NAMES polkit/polkit.h libpolkit/libpolkit.h
    PATH_SUFFIXES polkit-1 polkit
    HINTS ${PC_POLKIT_INCLUDE_DIRS}
    )
    #find_path( POLKIT_AGENT_INCLUDE_DIR
    # NAMES polkitagent/polkitagent.h
    # PATH_SUFFIXES polkit-1
    # HINTS ${PC_POLKIT_AGENT_INCLUDE_DIRS}
    #)
    #set(POLKIT_INCLUDE_DIRS ${GLIB2_INCLUDE_DIR} ${_POLKIT_INCLUDE_DIR})
    #set(POLKIT_AGENT_INCLUDE_DIRS ${GLIB2_INCLUDE_DIR} ${_POLKIT_AGENT_INCLUDE_DIR})
    find_library( POLKIT_LIBRARIES
    NAMES polkit-gobject-1 polkit
    HINTS ${PC_POLKIT_LIBDIR}
    )
    #find_library( POLKIT_AGENT_LIBRARY
    # NAMES polkit-agent-1
    # HINTS ${PC_POLKIT_AGENT_LIBDIR}
    #)
    #set(POLKIT_LIBRARIES ${_POLKIT_LIBRARIES} ${GLIB2_LIBRARIES})
    #set(POLKIT_AGENT_LIBRARY ${_POLKIT_AGENT_LIBRARY} ${GLIB2_LIBRARIES})
    include(FindPackageHandleStandardArgs)
    # handle the QUIETLY and REQUIRED arguments and set POLKIT_FOUND to TRUE if
    # all listed variables are TRUE
    #find_package_handle_standard_args(Polkit DEFAULT_MSG POLKIT_LIBRARIES POLKIT_AGENT_LIBRARY
    # POLKIT_INCLUDE_DIR POLKIT_AGENT_INCLUDE_DIR GLIB2_FOUND)
    find_package_handle_standard_args(Polkit DEFAULT_MSG POLKIT_LIBRARIES
    POLKIT_INCLUDE_DIR GLIB2_FOUND)
    mark_as_advanced(POLKIT_INCLUDE_DIRS POLKIT_AGENT_INCLUDE_DIRS POLKIT_LIBRARIES POLKIT_AGENT_LIBRARY GLIB_INCLUDE_DIR)
    set(POLKIT_POLICY_FILES_INSTALL_DIR ${CMAKE_INSTALL_PREFIX}/share/polkit-1/actions)
