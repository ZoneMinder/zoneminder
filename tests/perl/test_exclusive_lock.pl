#!/usr/bin/perl
#
# Tests ZoneMinder::General::acquireExclusiveLock, which zmcontrol.pl uses to
# keep a second control server from starting for a monitor that already has one.
# The old code had no lock at all: the second server unlinked the live socket and
# bound its own, so the first was left listening on an unlinked inode while both
# still held the camera's control connection open.  Issue #4423.
#
# The interesting property is the one a pid file or "does the socket exist"
# check cannot give you: the lock has to be gone after the holder is killed, or a
# crashed server would block its own restart for good.
#
# Run as: perl tests/perl/test_exclusive_lock.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN { $ENV{LOG_FILE} = "/tmp/zm_test_lock_$$.log"; }

use File::Temp qw(tempdir);
use POSIX qw(:sys_wait_h);
use ZoneMinder::General qw(acquireExclusiveLock);

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $ok) = @_;
  if ($ok) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "NOT OK - $name\n";
  }
}

my $dir = tempdir(CLEANUP => 1);
my $lock = "$dir/zmcontrol-1.lock";

# A fresh path: nobody holds it, so we get a handle and the file now exists.
my $first = acquireExclusiveLock($lock);
check('acquires a lock on a path that does not exist yet', defined $first);
check('creates the lock file', -e $lock);

# flock() tracks the open file description, not the process, so a second open of
# the same path is refused even from here.  This is what stops a second server.
my $second = acquireExclusiveLock($lock);
check('refuses a second lock while the first is held', !defined $second);

# Dropping the handle releases it, which is also what happens at process exit.
undef $first;
my $third = acquireExclusiveLock($lock);
check('the lock is available again once the holder lets go', defined $third);
undef $third;

# Now the case that matters: a holder that dies without cleaning up.
my ($rd, $wr);
pipe($rd, $wr) or die "pipe: $!";
my $pid = fork();
die "fork: $!" if !defined $pid;
if (!$pid) {
  close($rd);
  my $held = acquireExclusiveLock($lock);
  syswrite($wr, defined $held ? "y" : "n");
  sleep(60); # killed by the parent below, so the lock is never released tidily
  exit(0);
}
close($wr);
my $got = '';
sysread($rd, $got, 1);
check('a separate process can take the lock', $got eq 'y');

my $blocked = acquireExclusiveLock($lock);
check('and holds it against everyone else', !defined $blocked);

# SIGKILL leaves the lock file behind with no chance to tidy up, exactly like a
# crashed or OOM-killed control server.
kill('KILL', $pid);
waitpid($pid, 0);
check('the lock file survives the kill', -e $lock);

my $after = acquireExclusiveLock($lock);
check('a killed holder does not block the next server', defined $after);
undef $after;
close($rd);

# A path that cannot be opened is a failure to lock, not a lock.
my $bad = acquireExclusiveLock("$dir/no/such/dir/zmcontrol-1.lock");
check('returns undef when the lock file cannot be opened', !defined $bad);

print "\n$passes passed, $failures failed\n";
unlink($ENV{LOG_FILE});
exit($failures ? 1 : 0);
