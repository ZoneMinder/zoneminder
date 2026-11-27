#!/usr/bin/perl
# zmx10 polls each monitor's state once a second to drive its AlarmOutput
# devices. The other zmx10 tests stub the shared memory calls; this one runs
# zmx10.pl against the real ZoneMinder::Memory, with the other ZoneMinder
# modules, DBI, X10 and Sys::Mmap stubbed. The Sys::Mmap stand-in reads and
# writes the zm.mmap file directly and logs each map and unmap.
use strict;
use warnings;
use FindBin;
use Cwd;
use File::Temp qw(tempdir);
use File::Basename qw(dirname);
use POSIX qw(mkfifo);
use Time::HiRes qw(sleep);
require "$FindBin::Bin/zmx10_harness.pl";
our ($Dir, $Pid);

my $root = "$FindBin::Bin/../..";
$Dir = tempdir('zmx10-test-XXXXXX', DIR => dirname(Cwd::abs_path($root)), CLEANUP => 0);

write_file("$Dir/lib/ZoneMinder.pm", <<'STUB');
package ZoneMinder::Base;
our $VERSION = '0.0.0';
use constant ZM_VERSION => '0.0.0';
package ZoneMinder::Config;
our %Config = (
  ZM_PATH_SOCKS => $ENV{ZMX10_DIR},
  ZM_PATH_MAP => $ENV{ZMX10_DIR},
  ZM_PATH_UNAME => 'uname',
  ZM_X10_DEVICE => 'stub',
  ZM_X10_HOUSE_CODE => 'A',
  ZM_X10_DB_RELOAD_INTERVAL => $ENV{ZMX10_RELOAD} // 3600,
  ZM_FORCED_ALARM_SCORE => 255,
);
sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
package ZoneMinder::Logger;
use constant { FATAL=>-3, ERROR=>-1, WARNING=>0, INFO=>1, DEBUG=>2 };
sub rec { open(my $f, '>>', "$ENV{ZMX10_DIR}/calls.log") or return; print $f join(' ', @_), "\n"; close($f); }
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = \&{'ZoneMinder::Logger::'.$_} for qw(Debug Info Warning Warn Error Fatal);
}
sub Debug { rec('LOG', @_) }
sub Info { rec('LOG', @_) }
sub Warning { rec('LOG', @_) }
sub Warn { rec('LOG', @_) }
sub Error { rec('ERROR', @_) }
sub Fatal { rec('FATAL', @_); exit(1); }
BEGIN { $INC{'ZoneMinder/Base.pm'} = $INC{'ZoneMinder/Config.pm'} = $INC{'ZoneMinder/Logger.pm'} = 1; }
package ZoneMinder;
use strict;
require ZoneMinder::Memory;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::Config'} = \%ZoneMinder::Config::Config;
  *{$caller.'::'.$_} = \&{'ZoneMinder::Logger::'.$_} for qw(Debug Info Warning Error Fatal);
  *{$caller.'::'.$_} = sub {} for qw(logInit logReinit logSetSignal);
  *{$caller.'::zmDbConnect'} = sub { bless({}, 'StubDbh') };
  *{$caller.'::'.$_} = \&{'ZoneMinder::Memory::'.$_} for @{$ZoneMinder::Memory::EXPORT_TAGS{all}};
}
package StubDbh;
sub prepare_cached { bless({rows=>[ {Id=>1, Name=>'m1', Activation=>'', AlarmInput=>'', AlarmOutput=>'7'} ]}, 'StubSth') }
sub errstr { '' }
package StubSth;
sub execute { 1 }
sub errstr { '' }
sub fetchrow_hashref { shift(@{$_[0]->{rows}}) }
1;
STUB

# Reads and writes go straight to the file, so the test can change the state
# zmx10 sees the way zmc would.
write_file("$Dir/lib/Sys/Mmap.pm", <<'STUB');
package Sys::Mmap;
use strict;
use Exporter 'import';
our @EXPORT = qw(mmap munmap PROT_READ PROT_WRITE MAP_SHARED);
use constant { PROT_READ => 1, PROT_WRITE => 2, MAP_SHARED => 1 };
sub note { open(my $f, '>>', "$ENV{ZMX10_DIR}/calls.log") or return; print $f "$_[0]\n"; close($f); }
sub mmap { tie($_[0], 'Sys::Mmap::File', $_[4], $_[1]); note('MMAP'); return 1; }
sub munmap { untie($_[0]); $_[0] = undef; note('MUNMAP'); return 1; }
package Sys::Mmap::File;
sub TIESCALAR { my ($class, $fh, $len) = @_; return bless({fh=>$fh, len=>$len}, $class); }
sub FETCH { my $self = shift; my $buf = ''; sysseek($self->{fh}, 0, 0); sysread($self->{fh}, $buf, $self->{len}); return $buf; }
sub STORE { my ($self, $buf) = @_; sysseek($self->{fh}, 0, 0); syswrite($self->{fh}, $buf); return $buf; }
1;
STUB

my $memory = read_file("$root/scripts/ZoneMinder/lib/ZoneMinder/Memory.pm.in");
$memory =~ s/\@ENABLE_MMAP\@/yes/g;
write_file("$Dir/lib/ZoneMinder/Memory.pm", $memory);
write_file("$Dir/lib/ZoneMinder/Memory/Mapped.pm", read_file("$root/scripts/ZoneMinder/lib/ZoneMinder/Memory/Mapped.pm"));
write_file("$Dir/lib/X10/ActiveHome.pm", <<'STUB');
package X10::ActiveHome;
use strict;
use Fcntl;
sub new {
  my ($class, %args) = @_;
  sysopen(my $fh, "$ENV{ZMX10_DIR}/x10.fifo", O_RDWR) or die "fifo: $!";
  return bless({fh=>$fh}, $class);
}
sub register_listener { $_[0]->{cb} = $_[1] }
sub select_fds { fileno($_[0]->{fh}) }
sub handle_input { my $fh = $_[0]->{fh}; <$fh>; }
sub Appliance { my ($self, %args) = @_; return bless({unit_code=>$args{unit_code}}, 'X10::Appliance'); }
package X10::Appliance;
sub unit_code { $_[0]->{unit_code} }
sub address { 'A'.$_[0]->{unit_code} }
sub on { ZoneMinder::Logger::rec('APPLIANCE_ON', $_[0]->{unit_code}); 1 }
sub off { ZoneMinder::Logger::rec('APPLIANCE_OFF', $_[0]->{unit_code}); 1 }
1;
STUB
write_file("$Dir/lib/DBI.pm", "package DBI;\n1;\n");

my $src = read_file("$root/scripts/zmx10.pl.in");
$src =~ s/\@EXTRA_PERL_LIB\@//g;
$src =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
$src =~ s/^#!.*\n/#!$^X -w\n/;
write_file("$Dir/zmx10.pl", $src);
mkfifo("$Dir/x10.fifo", 0600) or die "mkfifo: $!";

# Lay out monitor 1's shared memory with the same module, filling in what
# zmMemVerify checks and the state the poll reads.
$ENV{ZMX10_DIR} = $Dir;
unshift(@INC, "$Dir/lib");
require ZoneMinder;
no warnings 'once';
my $mem = \%ZoneMinder::Memory::mem_data;
my $mem_size = $ZoneMinder::Memory::mem_size;
use warnings 'once';
sub offset { return $mem->{$_[0]}{contents}{$_[1]}{offset}; }

sub set_state {
  my $state = shift;
  my $buf = "\0" x $mem_size;
  substr($buf, offset('shared_data', 'size'), 4) = pack('L', $mem->{shared_data}{size});
  substr($buf, offset('shared_data', 'valid'), 1) = pack('C', 1);
  substr($buf, offset('shared_data', 'state'), 4) = pack('L', $state);
  substr($buf, offset('trigger_data', 'size'), 4) = pack('L', $mem->{trigger_data}{size});
  # Rewritten in place, as zmc changes it: the file stays the one zmx10 opened.
  my $fh;
  open($fh, '+<', "$Dir/zm.mmap.1") or open($fh, '>', "$Dir/zm.mmap.1") or die "mmap file: $!";
  syswrite($fh, $buf);
  close($fh);
}

sub start_zmx10 {
  $Pid = fork();
  die "fork: $!" if !defined($Pid);
  if (!$Pid) {
    open(STDOUT, '>', "$Dir/out.txt");
    open(STDERR, '>', "$Dir/err.txt");
    exec($^X, '-w', '-I', "$Dir/lib", "$Dir/zmx10.pl", '--command', 'start') or die "exec: $!";
  }
  for (1 .. 50) {
    last if -S "$Dir/zmx10.sock";
    sleep(0.1);
  }
}

set_state(1);   # IDLE
start_zmx10();

sleep(1.5);              # first poll records the initial state
set_state(3); sleep(2);  # ALARM
check('device 7 switched on when the monitor alarms', zmx10_calls('APPLIANCE_ON') == 1);
set_state(1); sleep(2);  # IDLE
check('device 7 switched off when the alarm ends', zmx10_calls('APPLIANCE_OFF') == 1);
check('state polls read shared memory without errors', zmx10_calls('ERROR') == 0);

# Reloading every second: each reload replaces the monitors, and the ones it
# drops must be unmapped, or every reload leaves a mapping behind.
zmx10_stop();
unlink("$Dir/calls.log", "$Dir/zmx10.sock");
$ENV{ZMX10_RELOAD} = 1;
start_zmx10();
sleep(5.5);
zmx10_stop();
my $maps = zmx10_calls('MMAP');
check("reloads happened ($maps maps)", $maps >= 3);
check('reloads leave at most the current mapping', $maps - zmx10_calls('MUNMAP') <= 1);
finish();
