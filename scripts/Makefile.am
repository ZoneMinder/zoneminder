AUTOMAKE_OPTIONS = gnu

# Ack! Nasty hack to get modules Makefile regenerated if wiped with make clean
all-local: ZoneMinder/Makefile

ZoneMinder/Makefile: ZoneMinder/Makefile.PL
	( cd ZoneMinder; perl Makefile.PL )

bin_SCRIPTS = \
	zmdc.pl \
	zmaudit.pl \
	zmfilter.pl \
	zmtrigger.pl \
	zmx10.pl \
	zmwatch.pl \
	zmpkg.pl \
	zmupdate.pl \
	zmvideo.pl \
	zmcontrol.pl \
	zmtrack.pl

SUBDIRS = \
	. \
    ZoneMinder

EXTRA_DIST = \
	zmdc.pl \
	zmaudit.pl \
	zmfilter.pl \
	zmtrigger.pl \
	zmx10.pl \
	zmwatch.pl \
	zmpkg.pl \
	zmupdate.pl \
	zmvideo.pl \
	zmcontrol.pl \
	zmtrack.pl \
	ZoneMinder/Makefile.PL \
	ZoneMinder/README \
	ZoneMinder/Changes \
	ZoneMinder/META.yml \
	ZoneMinder/MANIFEST \
	ZoneMinder/t/ZoneMinder.t \
	ZoneMinder/lib/ZoneMinder.pm \
	ZoneMinder/lib/ZoneMinder/Base.pm.in \
	ZoneMinder/lib/ZoneMinder/Config.pm.in \
	ZoneMinder/lib/ZoneMinder/Debug.pm \
	ZoneMinder/lib/ZoneMinder/General.pm \
	ZoneMinder/lib/ZoneMinder/Database.pm \
	ZoneMinder/lib/ZoneMinder/Memory.pm \
	ZoneMinder/lib/ZoneMinder/Memory/Shared.pm \
	ZoneMinder/lib/ZoneMinder/Memory/Mapped.pm \
	ZoneMinder/lib/ZoneMinder/ConfigAdmin.pm \
	ZoneMinder/lib/ZoneMinder/Control.pm \
	ZoneMinder/lib/ZoneMinder/Control/PelcoD.pm \
	ZoneMinder/lib/ZoneMinder/Control/AxisV2.pm \
	ZoneMinder/lib/ZoneMinder/Control/PanasonicIP.pm \
	ZoneMinder/lib/ZoneMinder/Control/Visca.pm \
	ZoneMinder/lib/ZoneMinder/Control/Ncs370.pm \
	ZoneMinder/lib/ZoneMinder/Control/mjpgStreamer.pm \
	ZoneMinder/lib/ZoneMinder/Trigger/Channel.pm \
	ZoneMinder/lib/ZoneMinder/Trigger/Channel/Handle.pm \
	ZoneMinder/lib/ZoneMinder/Trigger/Channel/Spawning.pm \
	ZoneMinder/lib/ZoneMinder/Trigger/Channel/Inet.pm \
	ZoneMinder/lib/ZoneMinder/Trigger/Channel/Unix.pm \
	ZoneMinder/lib/ZoneMinder/Trigger/Channel/File.pm \
	ZoneMinder/lib/ZoneMinder/Trigger/Channel/Serial.pm \
	ZoneMinder/lib/ZoneMinder/Trigger/Connection.pm \
	ZoneMinder/lib/ZoneMinder/Trigger/Connection/Example.pm \
	zm.in \
	zmdbbackup.in \
	zmdbrestore.in \
	zmeventdump.in \
	zmlogrotate.conf.in
