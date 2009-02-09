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
		'lib/ZoneMinder/General.pm' => '$(INST_LIBDIR)/ZoneMinder/General.pm',
		'lib/ZoneMinder/Database.pm' => '$(INST_LIBDIR)/ZoneMinder/Database.pm',
		'lib/ZoneMinder/Memory.pm' => '$(INST_LIBDIR)/ZoneMinder/Memory.pm',
		'lib/ZoneMinder/Memory/Shared.pm' => '$(INST_LIBDIR)/ZoneMinder/Memory/Shared.pm',
		'lib/ZoneMinder/Memory/Mapped.pm' => '$(INST_LIBDIR)/ZoneMinder/Memory/Mapped.pm',
		'lib/ZoneMinder/ConfigAdmin.pm' => '$(INST_LIBDIR)/ZoneMinder/ConfigAdmin.pm',
		'lib/ZoneMinder/Control.pm' => '$(INST_LIBDIR)/ZoneMinder/Control.pm',
		'lib/ZoneMinder/Control/PelcoD.pm' => '$(INST_LIBDIR)/ZoneMinder/Control/PelcoD.pm',
		'lib/ZoneMinder/Control/AxisV2.pm' => '$(INST_LIBDIR)/ZoneMinder/Control/AxisV2.pm',
		'lib/ZoneMinder/Control/PanasonicIP.pm' => '$(INST_LIBDIR)/ZoneMinder/Control/PanasonicIP.pm',
		'lib/ZoneMinder/Control/Visca.pm' => '$(INST_LIBDIR)/ZoneMinder/Control/Visca.pm',
		'lib/ZoneMinder/Control/Ncs370.pm' => '$(INST_LIBDIR)/ZoneMinder/Control/Ncs370.pm',
		'lib/ZoneMinder/Control/mjpgStreamer.pm' => '$(INST_LIBDIR)/ZoneMinder/Control/mjpgStreamer.pm',
		'lib/ZoneMinder/Trigger/Channel.pm' => '$(INST_LIBDIR)/ZoneMinder/Trigger/Channel.pm',
		'lib/ZoneMinder/Trigger/Channel/Handle.pm' => '$(INST_LIBDIR)/ZoneMinder/Trigger/Channel/Handle.pm',
		'lib/ZoneMinder/Trigger/Channel/Spawning.pm' => '$(INST_LIBDIR)/ZoneMinder/Trigger/Channel/Spawning.pm',
		'lib/ZoneMinder/Trigger/Channel/Inet.pm' => '$(INST_LIBDIR)/ZoneMinder/Trigger/Channel/Inet.pm',
		'lib/ZoneMinder/Trigger/Channel/Unix.pm' => '$(INST_LIBDIR)/ZoneMinder/Trigger/Channel/Unix.pm',
		'lib/ZoneMinder/Trigger/Channel/File.pm' => '$(INST_LIBDIR)/ZoneMinder/Trigger/Channel/File.pm',
		'lib/ZoneMinder/Trigger/Channel/Serial.pm' => '$(INST_LIBDIR)/ZoneMinder/Trigger/Channel/Serial.pm',
		'lib/ZoneMinder/Trigger/Connection.pm' => '$(INST_LIBDIR)/ZoneMinder/Trigger/Connection.pm',
		'lib/ZoneMinder/Trigger/Connection/Example.pm' => '$(INST_LIBDIR)/ZoneMinder/Trigger/Connection/Example.pm',
	},
    ($] >= 5.005 ?     ## Add these new keywords supported since 5.005
      (ABSTRACT_FROM  => 'lib/ZoneMinder.pm', # retrieve abstract from module
       AUTHOR         => 'Philip Coombes <philip.coombes@zoneminder.com>') : ()),
);
