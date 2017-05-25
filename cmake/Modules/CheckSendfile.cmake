# Check whether sendfile() is supported and what prototype it has
include(CheckCSourceCompiles)
if (UNIX OR MINGW)
SET(CMAKE_REQUIRED_DEFINITIONS -Werror-implicit-function-declaration)
endif()
check_c_source_compiles("#include <sys/sendfile.h>
#include <stdio.h>
int main()
{
sendfile(1, 1, NULL, 0);
return 0;
}" HAVE_SENDFILE4_SUPPORT)
if(HAVE_SENDFILE4_SUPPORT)
add_definitions(-DHAVE_SENDFILE4_SUPPORT=1)
unset(CMAKE_REQUIRED_DEFINITIONS)
message(STATUS "Sendfile support: Linux/Solaris sendfile()")
return()
endif()
find_library(SENDFILE_LIBRARIES NAMES sendfile)
if(SENDFILE_LIBRARIES)
include(CheckLibraryExists)
check_library_exists(sendfile sendfile ${SENDFILE_LIBRARIES} HAVE_SENDFILE4_SUPPORT)
if(HAVE_SENDFILE4_SUPPORT)
add_definitions(-DHAVE_SENDFILE4_SUPPORT=1)
unset(CMAKE_REQUIRED_DEFINITIONS)
message(STATUS "Sendfile support: Solaris sendfile()")
return()
endif()
endif()
set(SENDFILE_LIBRARIES "")
check_c_source_compiles("#include <sys/socket.h>
#include <stdio.h>
int main()
{
sendfile(1, 1, 0, 0, NULL, NULL, 0);
return 0;
}" HAVE_SENDFILE7_SUPPORT)
if(HAVE_SENDFILE7_SUPPORT)
add_definitions(-DHAVE_SENDFILE7_SUPPORT=1)
unset(CMAKE_REQUIRED_DEFINITIONS)
message(STATUS "Sendfile support: FreeBSD sendfile()")
return()
endif()
check_c_source_compiles("#include <sys/socket.h>
#include <stdio.h>
#include <sys/uio.h>
int main()
{
sendfile(1, 1, 0, NULL, NULL, 0);
return 0;
}" HAVE_SENDFILE6_SUPPORT)
if(HAVE_SENDFILE6_SUPPORT)
add_definitions(-DHAVE_SENDFILE6_SUPPORT=1)
unset(CMAKE_REQUIRED_DEFINITIONS)
message(STATUS "Sendfile support: MacOS sendfile()")
return()
endif()

