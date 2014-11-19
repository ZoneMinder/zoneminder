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
	zmtrack.pl \
	zmcamtool.pl \
	zmsystemctl.pl

SUBDIRS = \
	. \
    ZoneMinder

EXTRA_DIST = \
	zmdc.pl.in \
	zmaudit.pl.in \
	zmfilter.pl.in \
	zmtrigger.pl.in \
	zmx10.pl.in \
	zmwatch.pl.in \
	zmpkg.pl.in \
	zmupdate.pl.in \
	zmvideo.pl.in \
	zmcontrol.pl.in \
	zmtrack.pl.in \
	zmcamtool.pl.in \
	zmsystemctl.pl.in \
	ZoneMinder/Makefile.PL \
	ZoneMinder/README \
	ZoneMinder/Changes \
	ZoneMinder/META.yml \
	ZoneMinder/MANIFEST \
	ZoneMinder/t/ZoneMinder.t \
	ZoneMinder/lib/ZoneMinder.pm \
	ZoneMinder/lib/ZoneMinder/Base.pm.in \
	ZoneMinder/lib/ZoneMinder/Config.pm.in \
	ZoneMinder/lib/ZoneMinder/Logger.pm \
	ZoneMinder/lib/ZoneMinder/General.pm \
	ZoneMinder/lib/ZoneMinder/Database.pm \
	ZoneMinder/lib/ZoneMinder/Memory.pm.in \
	ZoneMinder/lib/ZoneMinder/Memory/Shared.pm \
	ZoneMinder/lib/ZoneMinder/Memory/Mapped.pm \
	ZoneMinder/lib/ZoneMinder/ConfigAdmin.pm \
	ZoneMinder/lib/ZoneMinder/ConfigData.pm.in \
	ZoneMinder/lib/ZoneMinder/Control.pm \
	ZoneMinder/lib/ZoneMinder/Control \ # Grab all ptz control skips under the Control folder
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

