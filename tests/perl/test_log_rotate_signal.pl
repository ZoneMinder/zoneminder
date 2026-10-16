#!/usr/bin/perl
#
# Tests that ZoneMinder::Logger reopens its log file on SIGWINCH.  logrotate
# used to go through SIGHUP, which for the perl daemons means exit-and-be-
# restarted and for zmc means a camera reconnect - a nightly gap in recording,
# issue #5063.  WINCH must rotate the log without any of that.
#
# Run as: sudo -u www-data perl tests/perl/test_log_rotate_signal.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

my $logfile = "/tmp/zm_test_logrot_$$.log";
my $rotated = $logfile.'.1';
BEGIN { $ENV{LOG_FILE} = "/tmp/zm_test_logrot_$$.log"; }

use ZoneMinder::Logger qw(:all);

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '<undef>') eq (defined $want ? $want : '<undef>')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "NOT OK - $name: got '".(defined $got ? $got : '<undef>')."' want '".(defined $want ? $want : '<undef>')."'\n";
  }
}

sub slurp {
  my ($path) = @_;
  open(my $fh, '<', $path) or return '';
  local $/ = undef;
  my $content = <$fh>;
  close($fh);
  return defined $content ? $content : '';
}

unlink($logfile, $rotated);

logInit(id=>'zm_test_logrot');
logTermLevel(NOLOG);
logFileLevel(INFO);
logSetSignal();

check('WINCH handler installed', ref($SIG{WINCH}), 'CODE');

Info('before rotation');
rename($logfile, $rotated) or die "Can't rename $logfile: $!";
Info('still holding the old handle');

check('writes still land in the renamed file',
  (slurp($rotated) =~ /still holding the old handle/) ? 1 : 0, 1);
check('nothing recreated the original path yet', -e $logfile ? 1 : 0, 0);

kill('WINCH', $$);
Info('after rotation');

check('the original path is written again after WINCH',
  (slurp($logfile) =~ /after rotation/) ? 1 : 0, 1);
check('the renamed file is left alone after WINCH',
  (slurp($rotated) =~ /after rotation/) ? 1 : 0, 0);

logTerm();
unlink($logfile, $rotated);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
