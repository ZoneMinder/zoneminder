# Shared harness for the test_zmx10_*.pl tests (not a test itself).
# Generates scripts/zmx10.pl from the .in template, runs it as a server with
# stub ZoneMinder, DBI and X10::ActiveHome modules, and talks to it over a
# unix socket and a fifo that stands in for the X10 device. Everything lives
# in a temp dir beside the worktree; no database or X10 device is touched.
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);
use File::Basename qw(dirname);
use IO::Socket::UNIX;
use POSIX qw(mkfifo);
use Time::HiRes qw(sleep time);

our ($Dir, $Pid, $Failures, $Passed);
$Failures = 0;
$Passed = 0;

sub check {
  my ($name, $ok) = @_;
  print(($ok ? 'ok' : 'FAIL')." - $name\n");
  $ok ? $Passed++ : $Failures++;
}

sub finish {
  zmx10_stop();
  system('rm', '-rf', $Dir) if $Dir;
  print "\n$Passed passed, $Failures failed\n";
  exit($Failures ? 1 : 0);
}

sub write_file {
  my ($path, $content) = @_;
  make_path(dirname($path));
  open(my $fh, '>', $path) or die "Can't write $path: $!";
  print $fh $content;
  close($fh);
}

sub read_file {
  my $path = shift;
  open(my $fh, '<', $path) or return '';
  local $/;
  my $c = <$fh>;
  close($fh);
  return defined($c) ? $c : '';
}

my $STUB_ZM = <<'STUB';
package ZoneMinder::Base;
use constant ZM_VERSION => '0.0.0';
package ZoneMinder::Logger;
use constant { FATAL=>-3, ERROR=>-1, WARNING=>0, INFO=>1, DEBUG=>2 };
package ZoneMinder;
use strict;
use Exporter 'import';
our %Config;
BEGIN {
  %Config = (
    ZM_PATH_SOCKS => $ENV{ZMX10_DIR},
    ZM_X10_DEVICE => 'stub',
    ZM_X10_HOUSE_CODE => 'A',
    ZM_X10_DB_RELOAD_INTERVAL => 3600,
    ZM_FORCED_ALARM_SCORE => 255,
  );
}
use constant { STATE_UNKNOWN=>0, STATE_IDLE=>1, STATE_PREALARM=>2, STATE_ALARM=>3, STATE_ALERT=>4, STATE_TAPE=>5 };
our @EXPORT = (qw(%Config Info Debug Warning Error Fatal logInit logReinit logSetSignal
  zmDbConnect zmMemVerify zmMemInvalidate zmGetMonitorState zmMonitorEnable zmMonitorDisable
  zmTriggerEventOn zmTriggerEventCancel),
  qw(STATE_UNKNOWN STATE_IDLE STATE_PREALARM STATE_ALARM STATE_ALERT STATE_TAPE));
sub rec { open(my $f, '>>', "$ENV{ZMX10_DIR}/calls.log") or return; print $f join(' ', @_), "\n"; close($f); }
sub Info { rec('LOG', @_) }
sub Debug { rec('LOG', @_) }
sub Warning { rec('LOG', @_) }
sub Error { rec('LOG', @_) }
sub Fatal { rec('FATAL', @_); print STDERR "FATAL @_\n"; exit(1); }
sub logInit {} sub logReinit {} sub logSetSignal {}
sub zmDbConnect { return bless({}, 'StubDbh'); }
sub zmMemVerify { 1 }
sub zmMemInvalidate { 1 }
sub zmGetMonitorState {
  open(my $f, '<', "$ENV{ZMX10_DIR}/state") or return undef;
  my $s = <$f>; close($f); chomp($s); return $s;
}
sub zmMonitorEnable { rec('ENABLE', $_[0]->{Id}) }
sub zmMonitorDisable { rec('DISABLE', $_[0]->{Id}) }
sub zmTriggerEventOn { rec('TRIGGER_ON', $_[0]->{Id}) }
sub zmTriggerEventCancel { rec('TRIGGER_OFF', $_[0]->{Id}) }
package StubDbh;
sub prepare_cached {
  my ($self, $sql) = @_;
  my $rows = do "$ENV{ZMX10_DIR}/rows.pl";
  return bless({rows=>[ map { {%$_} } @$rows ], sql=>$sql}, 'StubSth');
}
sub errstr { '' }
package StubSth;
sub execute { 1 }
sub errstr { '' }
sub fetchrow_hashref {
  my $self = shift;
  my $ignore_deleted = defined($self->{sql})
    && $self->{sql} =~ /\bDeleted\s*=\s*0\b/;
  while ( my $row = shift(@{$self->{rows}}) ) {
    next if $ignore_deleted && $row->{Deleted};
    return $row;
  }
  return undef;
}
1;
STUB

my $STUB_DBI = "package DBI;\nuse strict;\n1;\n";

my $STUB_X10 = <<'STUB';
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
sub handle_input {
  my $self = shift;
  my $fh = $self->{fh};
  my $line = <$fh>;
  return if !defined($line);
  chomp($line);
  my ($house, $unit, $func) = split(' ', $line);
  $self->{cb}->(bless({h=>$house, u=>$unit, f=>$func}, 'X10::Event'));
}
sub Appliance {
  my ($self, %args) = @_;
  return bless({unit_code=>$args{unit_code}}, 'X10::Appliance');
}
package X10::Event;
sub house_code { $_[0]->{h} }
sub unit_code { $_[0]->{u} }
sub func { $_[0]->{f} }
sub as_string { "$_[0]->{h}$_[0]->{u} $_[0]->{f}" }
package X10::Appliance;
sub unit_code { $_[0]->{unit_code} }
sub address { 'A'.$_[0]->{unit_code} }
sub rec { open(my $f, '>>', "$ENV{ZMX10_DIR}/calls.log") or return; print $f join(' ', @_), "\n"; close($f); }
sub on { rec('APPLIANCE_ON', $_[0]->{unit_code}); 1 }
sub off { rec('APPLIANCE_OFF', $_[0]->{unit_code}); 1 }
1;
STUB

# zmx10_start(rows => [ {Id=>1, Name=>'m', Activation=>..., AlarmInput=>..., AlarmOutput=>...} ])
sub zmx10_start {
  my %opt = @_;
  my $root = "$FindBin::Bin/../..";
  my $base = dirname(Cwd::abs_path($root));
  $Dir = tempdir('zmx10-test-XXXXXX', DIR => $base, CLEANUP => 0);
  write_file("$Dir/lib/ZoneMinder.pm", $STUB_ZM);
  write_file("$Dir/lib/DBI.pm", $STUB_DBI);
  write_file("$Dir/lib/X10/ActiveHome.pm", $STUB_X10);
  my $src = read_file($ENV{ZMX10_IN} || "$root/scripts/zmx10.pl.in");
  $src =~ s/\@PERL_EXECUTABLE\@/$^X/g;
  $src =~ s/\@EXTRA_PERL_LIB\@//g;
  $src =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
  $src =~ s/^#!.*\n/#!$^X -w\n/;
  write_file("$Dir/zmx10.pl", $src);
  my $rows = $opt{rows} || [];
  require Data::Dumper;
  local $Data::Dumper::Terse = 1;
  write_file("$Dir/rows.pl", Data::Dumper::Dumper($rows));
  write_file("$Dir/state", ($opt{state} // 1)."\n");
  mkfifo("$Dir/x10.fifo", 0600) or die "mkfifo: $!";
  $Pid = fork();
  die "fork: $!" if !defined($Pid);
  if (!$Pid) {
    $ENV{ZMX10_DIR} = $Dir;
    open(STDOUT, '>', "$Dir/out.txt");
    open(STDERR, '>', "$Dir/err.txt");
    exec($^X, '-w', '-I', "$Dir/lib", "$Dir/zmx10.pl", '--command', 'start') or die "exec: $!";
  }
  for (1 .. 50) {
    last if -S "$Dir/zmx10.sock";
    sleep(0.1);
  }
  return $Pid;
}

sub zmx10_stop {
  return if !$Pid;
  kill('TERM', $Pid);
  waitpid($Pid, 0);
  $Pid = 0;
}

sub zmx10_alive {
  return $Pid && waitpid($Pid, POSIX::WNOHANG()) == 0;
}

# Send one raw message to the server socket the way the web UI does and
# return everything it answers; undef if the server is unreachable.
sub zmx10_send {
  my $msg = shift;
  my $s = IO::Socket::UNIX->new(Peer => "$Dir/zmx10.sock") or return undef;
  print $s $msg;
  $s->shutdown(1);
  local $/;
  my $r = <$s>;
  close($s);
  return defined($r) ? $r : '';
}

sub zmx10_event {
  my $line = shift;
  open(my $f, '>', "$Dir/x10.fifo") or die "fifo: $!";
  print $f "$line\n";
  close($f);
}

sub zmx10_set_state {
  my $state = shift;
  write_file("$Dir/state.tmp", "$state\n");
  rename("$Dir/state.tmp", "$Dir/state");
}

sub zmx10_calls {
  my $pat = shift;
  return scalar(grep { /^\Q$pat\E\b/ } split(/\n/, read_file("$Dir/calls.log")));
}

1;
