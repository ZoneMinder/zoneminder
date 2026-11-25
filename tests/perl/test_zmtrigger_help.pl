#!/usr/bin/perl
#
# zmtrigger.pl used to ignore every command line option: --help fell through to
# logInit, zmDbConnect and opening the TCP/unix listeners.  --help/-h and
# --version/-v must be handled right after the module imports, before logInit
# and the connections, and an unknown option must print the usage and exit
# non-zero.  No arguments must still start the daemon as before.
#
# The stubs print "DB connect" and "OPEN <channel>" whenever the daemon reaches
# the database or opens one of its channels, so the option tests can prove that
# none of that happens for --help, --version and an unknown option.
#
# Run as: perl tests/perl/test_zmtrigger_help.pl
#
use strict;
use warnings;
use FindBin;
require "$FindBin::Bin/zmtrigger_harness.pm";

# A scenario with nothing to simulate: the option paths must exit before any of
# the stubs are used.
my $quiet = <<'EOM';
package ZmtSim;
our %rows = ();
1;
EOM

sub startup_lines {
  my ($r) = @_;
  return grep { /^(?:DB connect|OPEN )/ } @{$r->{lines}};
}

sub has_usage {
  my ($r) = @_;
  return scalar grep { /^(?:Usage:|SYNOPSIS|OPTIONS)/ } @{$r->{lines}};
}

foreach my $opt (qw(--help -h)) {
  my $r = run_zmtrigger($quiet, [$opt]);
  check("$opt exits 0", $r->{exit} . '/' . $r->{signal}, '0/0');
  check("$opt prints the usage", has_usage($r) ? 1 : 0, 1);
  check("$opt prints the synopsis", scalar(grep { /^(?:Usage:|SYNOPSIS)/ } @{$r->{lines}}) ? 1 : 0, 1);
  check("$opt names the script", scalar(grep { /zmtrigger/ } @{$r->{lines}}) ? 1 : 0, 1);
  check("$opt does not connect or open a channel", join(',', startup_lines($r)), '');
}

# ZoneMinder::Base::ZM_VERSION in the stub harness
my $zm_version = '9.8.7';
foreach my $opt (qw(--version -v)) {
  my $r = run_zmtrigger($quiet, [$opt]);
  check("$opt exits 0", $r->{exit} . '/' . $r->{signal}, '0/0');
  check("$opt prints the version", join('', grep { /^\S+$/ } @{$r->{lines}}), $zm_version);
  check("$opt does not connect or open a channel", join(',', startup_lines($r)), '');
}

my $bad = run_zmtrigger($quiet, ['--not-an-option']);
check('an unknown option exits non-zero', ($bad->{exit} != 0 && !$bad->{signal}) ? 1 : 0, 1);
check('an unknown option prints the usage', has_usage($bad) ? 1 : 0, 1);
check('an unknown option does not connect or open a channel', join(',', startup_lines($bad)), '');

# No arguments keeps the old behaviour: connect to the database and open the
# two channels the daemon defines.
my $normal = run_zmtrigger(<<'EOM');
package ZmtSim;
our $max_ticks = 3;
our %rows = ();
1;
EOM
check('no arguments still runs to the end', $normal->{exit} . '/' . $normal->{signal}, '0/0');
check('no arguments still connects to the database',
  scalar(grep { /^DB connect$/ } @{$normal->{lines}}) >= 1 ? 1 : 0, 1);
check('no arguments still opens the channels',
  join(',', grep { /^OPEN / } @{$normal->{lines}}), 'OPEN Inet,OPEN Unix');

finish();
