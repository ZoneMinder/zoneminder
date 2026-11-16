#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

sub write_file {
  my ($path, $content) = @_;
  open(my $fh, '>', $path) or die "Can't write $path: $!";
  print $fh $content;
  close($fh);
}

my $dir = tempdir(CLEANUP => 1);
make_path("$dir/lib/ZoneMinder");

write_file("$dir/lib/ZoneMinder.pm", <<'EOM');
package ZoneMinder;
use strict;
use warnings;

our %Config = (ZM_PATH_BIN => '/bin');
our $iter = 0;
our $debug = 0;

sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::Config'} = \%Config;
  *{$caller.'::logInit'} = sub { 1 };
  *{$caller.'::logSetSignal'} = sub { 1 };
  *{$caller.'::logDebugging'} = sub { return $debug; };
  *{$caller.'::Debug'} = sub { 1 };
  *{$caller.'::Info'} = sub { print "INFO: $_[0]\n"; };
  *{$caller.'::Warning'} = sub { 1 };
  *{$caller.'::Fatal'} = sub { die "FATAL: $_[0]\n"; };
  *{$caller.'::zmDbConnect'} = sub {
    my $sth = bless {}, 'MockSTH';
    return bless { sth => $sth }, 'MockDBH';
  };
  *{$caller.'::zmMemVerify'} = sub { 1 };
  *{$caller.'::zmMonitorSuspend'} = sub { 1 };
  *{$caller.'::zmMonitorResume'} = sub { 1 };
  *{$caller.'::zmIsAlarmed'} = sub {
    $iter++;
    my $mode = $ENV{TEST_MODE} || 0;
    if ($mode == 1) {
      # Scenario 1: alarm in iter 1 (debug off), clear in iter 2 (debug off), iter 3 (debug turned on)
      if ($iter == 1) { return 1; }
      if ($iter == 2) { return 0; }
      if ($iter == 3) { $debug = 1; return 0; }
      exit(0);
    } elsif ($mode == 2) {
      # Scenario 2: alarm in iter 1, clear in iter 2 with debug ON
      if ($iter == 1) { return 1; }
      if ($iter == 2) { $debug = 1; return 0; }
      exit(0);
    }
    exit(0);
  };
  *{$caller.'::zmGetAlarmLocation'} = sub { return (10, 20); };
  *{$caller.'::executeShellCommand'} = sub { 1 };
}

package MockDBH;
sub prepare_cached { return $_[0]->{sth}; }

package MockSTH;
sub execute { 1 }
sub fetchrow_hashref {
  return {
    Id => 1,
    Controllable => 1,
    TrackMotion => 1,
    CanMoveMap => 1,
    TrackDelay => 0,
    ReturnLocation => -1,
  };
}
1;
EOM

write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmtrack.pl.in") or die "Can't open zmtrack.pl.in: $!";
  local $/;
  <$fh>;
};
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
$source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
write_file("$dir/zmtrack.pl", $source);

sub run {
  my ($mode_val) = @_;
  local $ENV{TEST_MODE} = $mode_val;
  my $out = qx($^X -T -I$dir/lib $dir/zmtrack.pl --monitor=1 2>&1);
  return ($? >> 8, $out);
}

# Scenario 1: Alarm cleared when debug was off. When debug is enabled later while idle,
# it should NOT falsely report leaving alarm state.
my ($status, $out) = run(1);
check('alarm state is cleared without debug so no spurious log when debug enables',
      $out =~ /INFO: Left alarm state/ ? 'spurious_log' : 'clean',
      'clean');

# Scenario 2: Alarm cleared when debug was on: should log 'Left alarm state'.
($status, $out) = run(2);
check('leaving alarm state logs when debug is enabled',
      $out =~ /INFO: Left alarm state/ ? 'logged' : 'missing',
      'logged');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
