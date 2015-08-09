find_package(Perl 5.6.0 REQUIRED)
# Checking for perl modules requires FindPerlModules.cmake
# Check all required modules at once
# TODO: Add checking for the optional modules
find_package(PerlModules COMPONENTS Sys::Syslog DBI DBD::mysql Getopt::Long Time::HiRes Date::Manip LWP::UserAgent ExtUtils::MakeMaker ${ZM_MMAP_PERLPACKAGE})
if(NOT PERLMODULES_FOUND)
	message(FATAL_ERROR "Not all required perl modules were found on your system")
endif(NOT PERLMODULES_FOUND)
