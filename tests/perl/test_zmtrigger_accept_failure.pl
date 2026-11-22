#!/usr/bin/perl
#
# Inet::accept did not check the result of accept(), so a failed accept (for
# example EMFILE) registered a connection with no file descriptor.  accept()
# must return nothing when the underlying accept() fails.
#
# Run as: perl tests/perl/test_zmtrigger_accept_failure.pl
#
use strict;
use warnings;
use FindBin;
our $root;
BEGIN {
  require "$FindBin::Bin/zmtrigger_stubs.pm"; ZmtStubs::install();
  $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../..";
}
use lib "$root/scripts/ZoneMinder/lib";

use ZoneMinder::Trigger::Channel::Inet;
use ZoneMinder::Trigger::Channel::Unix;
use ZoneMinder::Trigger::Connection;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

my $channel = ZoneMinder::Trigger::Channel::Inet->new(port => 0);
open(my $fh, '<', '/dev/null') or die "Can't open /dev/null: $!";
$channel->{handle} = $fh;   # accept() on a plain file fails with ENOTSOCK

my $accepted = eval { $channel->accept() };
check('channel accept does not die', $@ ? "died: $@" : 'ok', 'ok');
check('failed channel accept returns nothing',
  defined($accepted) ? 'defined' : 'undef', 'undef');

my $conn = ZoneMinder::Trigger::Connection->new(name => 'inet', mode => 'rw',
  channel => $channel);
my $spawned = eval { $conn->accept() };
check('connection accept does not die', $@ ? "died: $@" : 'ok', 'ok');
check('failed connection accept does not spawn a connection',
  defined($spawned) ? 'defined' : 'undef', 'undef');

# The Unix socket channel takes the same accept path and is on by default.
my $unix = ZoneMinder::Trigger::Channel::Unix->new(path => '/nonexistent/zmtrigger.sock');
open(my $ufh, '<', '/dev/null') or die "Can't open /dev/null: $!";
$unix->{handle} = $ufh;
my $uaccepted = eval { $unix->accept() };
check('unix channel accept does not die', $@ ? "died: $@" : 'ok', 'ok');
check('failed unix channel accept returns nothing',
  defined($uaccepted) ? 'defined' : 'undef', 'undef');
my $uconn = ZoneMinder::Trigger::Connection->new(name => 'unix', mode => 'rw',
  channel => $unix);
my $uspawned = eval { $uconn->accept() };
check('failed unix connection accept does not spawn a connection',
  defined($uspawned) ? 'defined' : 'undef', 'undef');

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
