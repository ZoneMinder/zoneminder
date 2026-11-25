#
# Shared helper for the test_zmtrigger_*.pl tests (not a test itself).
#
# Builds a runnable copy of scripts/zmtrigger.pl.in in a temp dir and runs it
# in a child perl with stub ZoneMinder modules, no database, no sockets and a
# virtual clock (time() and select() are replaced, so a 300 second reload
# interval costs nothing).  A scenario, written in perl, drives the stubs:
#
#   package ZmtSim;
#   our %rows;               # monitors "table": Id => { Id, Deleted, Capturing, ... }
#   our $max_ticks = 5;      # stop (SIGTERM) after this many main loop passes
#   sub tick { my $n = shift; ... }       # called at the end of every pass
#   sub mem { my ($id, $field) = @_; }    # shared memory value, or undef
#   our @inbox;              # [ tick, 'message' ] sent by the "Inet" client
#   sub connect_ok { my $id = shift; 1 }  # may Monitor->connect() succeed
#
# The child prints one line per observable event: "ACT ..." for shared memory
# writes, "OUT ..." for lines the daemon writes to its client, "FIND ..." for
# database queries and "LOG ..." for log messages.
#
use strict;
use warnings;
use File::Temp qw(tempdir);
use File::Path qw(make_path);
use FindBin;

my $repo = $ENV{ZMT_REPO} // $ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..";

sub write_file {
  my ($path, $content) = @_;
  open(my $fh, '>', $path) or die "Can't write $path: $!";
  print $fh $content;
  close($fh);
}

sub run_zmtrigger {
  my ($scenario, $args) = @_;
  $args //= [];
  my $dir = tempdir(CLEANUP => 1);
  make_path("$dir/lib/ZoneMinder/Trigger/Channel");

  open(my $in, '<', "$repo/scripts/zmtrigger.pl.in") or die "Can't read zmtrigger.pl.in: $!";
  my $src = do { local $/; <$in> };
  close($in);
  $src =~ s/^#!.*\n//;
  $src =~ s/\@EXTRA_PERL_LIB\@//;
  $src =~ s/\@ZM_SCRIPT_PATH\@/\/bin:\/usr\/bin/g;
  my $prelude = <<'EOM';
BEGIN {
  $main::T = 1_000_000;
  require Time::HiRes;
  no warnings 'redefine';
  *CORE::GLOBAL::time = sub { int($main::T) };
  *CORE::GLOBAL::select = sub { $main::T += ($_[3] || 0); return 0; };
  *Time::HiRes::usleep = sub ($) { $main::T += $_[0] / 1e6; };
}
EOM
  write_file("$dir/zmtrigger.pl", $prelude.$src);
  write_file("$dir/scenario.pl", $scenario);

  write_file("$dir/lib/ZoneMinder/Base.pm",
    "package ZoneMinder::Base;\nour \$VERSION = '1';\nuse constant ZM_VERSION => '9.8.7';\n1;\n");
  write_file("$dir/lib/ZoneMinder/Config.pm", <<'EOM');
package ZoneMinder::Config;
our %Config;
sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
1;
EOM
  write_file("$dir/lib/ZoneMinder/Logger.pm", <<'EOM');
package ZoneMinder::Logger;
our $logger = bless {}, 'ZoneMinder::Logger::Null';
sub INFO { return 1; }
sub logLevel { return 0; }
sub Debug { return 1; }
sub Info { print "LOG info @_\n"; }
sub Warning { print "LOG warning @_\n"; }
sub Error { print "LOG error @_\n"; }
sub Fatal { die "FATAL @_\n"; }
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal INFO);
  *{$caller.'::logger'} = \$logger;
}
package ZoneMinder::Logger::Null;
sub AUTOLOAD { return 1; }
sub DESTROY { }
1;
EOM
  write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");
  write_file("$dir/lib/ZoneMinder.pm", <<'EOM');
package ZoneMinder;
use strict;
use warnings;
use ZoneMinder::Logger;
use ZoneMinder::Base;
our %Config = (ZM_PATH_SOCKS => '/nonexistent', ZM_SERVER_ID => 0);
our $tick = 0;
use constant { STATE_UNKNOWN => 0, STATE_IDLE => 1, STATE_PREALARM => 2,
  STATE_ALARM => 3, STATE_ALERT => 4, STATE_TAPE => 5 };
do $ENV{ZMT_SCENARIO} or die "scenario: ".($@ || $!);
sub import {
  no strict 'refs';
  my $c = caller;
  *{$c.'::Config'} = \%Config;
  *{$c.'::'.$_} = \&{'ZoneMinder::Logger::'.$_} for qw(Debug Info Warning Error Fatal);
  *{$c.'::'.$_} = sub { 1 } for qw(logInit logSetSignal);
  *{$c.'::'.$_} = \&{$_} for qw(STATE_IDLE STATE_PREALARM STATE_ALARM STATE_ALERT STATE_TAPE
    zmDbConnect zmMemVerify zmMemRead zmGetMonitorState zmGetLastEvent zmInAlarm
    zmTriggerEventOn zmTriggerEventOff zmTriggerEventCancel zmTriggerShowtext
    zmMonitorEnable zmMonitorDisable);
}
sub zmDbConnect {
  print "DB connect\n";
  if ($tick++ > 0) {
    ZmtSim::tick($tick - 1) if defined &ZmtSim::tick;
    if ($tick - 1 >= $ZmtSim::max_ticks) { kill 'TERM', $$; }
  }
  return bless {}, 'FakeDbh';
}
sub zmMemVerify { return defined &ZmtSim::verify ? ZmtSim::verify($_[0]) : 1; }
sub zmMemRead {
  my ($m, $fields) = @_;
  my @v = map { my ($s, $f) = split(/[:]/, $_); ZmtSim::mem($m->{Id}, $f) } (ref $fields ? @$fields : $fields);
  return wantarray ? @v : $v[0];
}
sub zmGetMonitorState { return ZmtSim::mem($_[0]{Id}, 'state'); }
sub zmGetLastEvent { return ZmtSim::mem($_[0]{Id}, 'last_event'); }
sub zmInAlarm { return 0; }
sub zmTriggerEventOn { print "ACT on $_[0]{Id} score=$_[1] cause=$_[2] text=".($_[3] // '')."\n"; }
sub zmTriggerEventOff { print "ACT off $_[0]{Id}\n"; }
sub zmTriggerEventCancel { print "ACT cancel $_[0]{Id}\n"; }
sub zmTriggerShowtext { print "ACT showtext $_[0]{Id} ".($_[1] // '<undef>')."\n"; }
sub zmMonitorEnable { print "ACT enable $_[0]{Id}\n"; }
sub zmMonitorDisable { print "ACT disable $_[0]{Id}\n"; }
1;
EOM

  # Monitor uses the real ZoneMinder::Object find/find_one/find_sql so the
  # queries zmtrigger sends are the real ones, against a fake handle.
  write_file("$dir/lib/ZoneMinder/Monitor.pm", <<'EOM');
package ZoneMinder::Monitor;
use strict;
use warnings;
require ZoneMinder::Object;
our @ISA = ('ZoneMinder::Object');
our ($table, $primary_key, %fields, $default_sort, $dbh);
$table = 'Monitors';
$primary_key = 'Id';
$default_sort = 'Id';
# The same spelling as scripts/ZoneMinder/lib/ZoneMinder/Monitor.pm: Deleted is capitalised
%fields = map { $_ => $_ } qw(Id Name Deleted ServerId Capturing Recording);
$dbh = bless {}, 'FakeDbh';
sub Id { return $_[0]{Id}; }
sub connect {
  my $self = shift;
  return defined &ZmtSim::connect_ok ? ZmtSim::connect_ok($self->{Id}) : 1;
}
sub disconnect { return 1; }

package FakeDbh;
sub errstr { return ''; }
sub selectall_arrayref {
  my ($self, $sql, $attr, @values) = @_;
  print "FIND $sql | @values\n";
  my @where = $sql =~ /WHERE (.*?)(?: ORDER BY| LIMIT|$)/ ? split(/ AND /, $1) : ();
  my @rows = map { { %$_ } } map { $ZmtSim::rows{$_} } sort { $a <=> $b } keys %ZmtSim::rows;
  foreach my $cond (@where) {
    my $val = shift @values;
    if ($cond =~ /^`?(\w+)`?\s*(!=|=)\s*\?$/) {
      my ($col, $op) = ($1, $2);
      @rows = grep { $op eq '=' ? $_->{$col} eq $val : $_->{$col} ne $val } @rows;
    }
  }
  return \@rows;
}
1;
EOM

  # Channels: the "Inet" one is the client that sends messages and reads what
  # the daemon writes, the others are idle.
  write_file("$dir/lib/ZoneMinder/Trigger/Channel/Inet.pm", <<'EOM');
package ZoneMinder::Trigger::Channel::Inet;
use strict;
use warnings;
require ZoneMinder::Trigger::Channel;
our @ISA = ('ZoneMinder::Trigger::Channel');
sub new {
  my $class = shift;
  my $self = ZoneMinder::Trigger::Channel->new();
  $self->{state} = 'connected';
  return bless $self, $class;
}
sub open { print "OPEN Inet\n"; return 1; }
sub spawns { return undef; }
sub fileno { return -1; }
sub read {
  my $self = shift;
  my @now = grep { $_->[0] <= $ZoneMinder::tick } @ZmtSim::inbox;
  @ZmtSim::inbox = grep { $_->[0] > $ZoneMinder::tick } @ZmtSim::inbox;
  return undef if !@now;
  return join('', map { $_->[1]."\n" } @now);
}
sub write {
  my ($self, $buffer) = @_;
  if ($ZmtSim::write_handle) {
    return defined(syswrite($ZmtSim::write_handle, $buffer)) ? 1 : undef;
  }
  print "OUT $_\n" for split(/\n/, $buffer);
  return 1;
}
1;
EOM
  foreach my $name (qw(Unix Serial)) {
    write_file("$dir/lib/ZoneMinder/Trigger/Channel/$name.pm", <<"EOM");
package ZoneMinder::Trigger::Channel::$name;
use strict;
use warnings;
require ZoneMinder::Trigger::Channel;
our \@ISA = ('ZoneMinder::Trigger::Channel');
sub new { my \$class = shift; return bless ZoneMinder::Trigger::Channel->new(), \$class; }
sub open { print "OPEN $name\\n"; return 1; }
sub spawns { return undef; }
sub fileno { return -1; }
sub read { return undef; }
sub write { return 1; }
1;
EOM
  }

  my $lib = "$repo/scripts/ZoneMinder/lib";
  my @inc = map { ('-I', $_) } ("$dir/lib", $lib, split(/:/, $ENV{ZMT_EXTRA_INC} // ''));
  my $out = "$dir/out.txt";
  my $pid = fork();
  die "fork: $!" if !defined $pid;
  if (!$pid) {
    $ENV{ZMT_SCENARIO} = "$dir/scenario.pl";
    open(STDOUT, '>', $out) or die;
    open(STDERR, '>&', \*STDOUT) or die;
    exec($^X, '-w', @inc, "$dir/zmtrigger.pl", @$args) or die "exec: $!";
  }
  local $SIG{ALRM} = sub { kill 'KILL', $pid; };
  alarm 60;
  waitpid($pid, 0);
  alarm 0;
  my $status = $?;
  open(my $fh, '<', $out) or die;
  my @lines = <$fh>;
  close($fh);
  chomp @lines;
  return { lines => \@lines, status => $status, signal => $status & 127, exit => $status >> 8 };
}

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  $got = '(undef)' if !defined $got;
  $want = '(undef)' if !defined $want;
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

sub finish {
  print "$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}

1;
