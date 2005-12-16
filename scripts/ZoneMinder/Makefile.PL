use 5.006;
use ExtUtils::MakeMaker;
# See lib/ExtUtils/MakeMaker.pm for details of how to influence
# the contents of the Makefile that is written.
WriteMakefile(
    NAME              => 'ZoneMinder',
    VERSION_FROM      => 'lib/ZoneMinder.pm', # finds $VERSION
    PREREQ_PM         => {}, # e.g., Module::Name => 1.1
	PM                => {'lib/ZoneMinder.pm' => '$(INST_LIBDIR)/ZoneMinder.pm'},
    ($] >= 5.005 ?     ## Add these new keywords supported since 5.005
      (ABSTRACT_FROM  => 'lib/ZoneMinder.pm', # retrieve abstract from module
       AUTHOR         => 'Philip Coombes <stan@localdomain>') : ()),
);
