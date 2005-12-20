use 5.006;
use ExtUtils::MakeMaker;
# See lib/ExtUtils/MakeMaker.pm for details of how to influence
# the contents of the Makefile that is written.
WriteMakefile(
    NAME              => 'ZoneMinder',
    VERSION_FROM      => 'lib/ZoneMinder/Base.pm', # finds $VERSION
    PREREQ_PM         => {}, # e.g., Module::Name => 1.1
	PM                => {
		'lib/ZoneMinder.pm' => '$(INST_LIBDIR)/ZoneMinder.pm',
		'lib/ZoneMinder/Base.pm' => '$(INST_LIBDIR)/ZoneMinder/Base.pm',
		'lib/ZoneMinder/Config.pm' => '$(INST_LIBDIR)/ZoneMinder/Config.pm',
		'lib/ZoneMinder/Debug.pm' => '$(INST_LIBDIR)/ZoneMinder/Debug.pm',
		'lib/ZoneMinder/Database.pm' => '$(INST_LIBDIR)/ZoneMinder/Database.pm',
		'lib/ZoneMinder/SharedMem.pm' => '$(INST_LIBDIR)/ZoneMinder/SharedMem.pm',
		'lib/ZoneMinder/ConfigAdmin.pm' => '$(INST_LIBDIR)/ZoneMinder/ConfigAdmin.pm',
	},
    ($] >= 5.005 ?     ## Add these new keywords supported since 5.005
      (ABSTRACT_FROM  => 'lib/ZoneMinder.pm', # retrieve abstract from module
       AUTHOR         => 'Philip Coombes <philip.coombes@zoneminder.com>') : ()),
);
