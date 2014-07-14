use 5.006;
use ExtUtils::MakeMaker;
# See lib/ExtUtils/MakeMaker.pm for details of how to influence
# the contents of the Makefile that is written.
WriteMakefile(
    NAME              => 'ONVIF',
#    VERSION_FROM      => 'lib/ZoneMinder/Base.pm', # finds $VERSION
    PREREQ_PM         => {}, # e.g., Module::Name => 1.1
# No need to specify perl modules.  MakeMaker will find them automatically
    ($] >= 5.005 ?     ## Add these new keywords supported since 5.005
      (#ABSTRACT_FROM  => 'lib/ZoneMinder.pm', # retrieve abstract from module
       AUTHOR         => 'Jan Hochstein') : ()),
);
