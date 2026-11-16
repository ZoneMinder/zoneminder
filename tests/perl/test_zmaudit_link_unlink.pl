#!/usr/bin/perl
#
# Regression test for GHSA-746p-4622-p3fr.
#
# zmaudit removes a filesystem monitor link that no longer points to a valid
# monitor directory. The link name is the monitor name, and the web layer names
# the link from the raw request (basename($_REQUEST['newMonitor']['Name'])),
# not the filtered name stored in the database, so it can hold shell
# metacharacters. The old code ran rm "$link" through a shell; inside the
# double-quoted argument $(...) and backticks in the name are still expanded, so
# a user with Monitors create/edit could have zmaudit run arbitrary commands
# when it later cleaned up the dangling link.
#
# The fix removes the link with Perl unlink() on the de-tainted name -- no shell,
# so nothing in the name is interpreted.
#
# This test (1) reads the real scripts/zmaudit.pl.in and asserts the monitor-link
# cleanup uses unlink() and no longer passes the link to a shell, and (2) shows
# behaviourally that unlink() removes a symlink whose name contains a command
# substitution without executing it. Run: perl tests/perl/test_zmaudit_link_unlink.pl
#
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $cond) = @_;
  if ($cond) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n"; }
}

# (1) Source checks against the real script.
my $src_file = "$FindBin::Bin/../../scripts/zmaudit.pl.in";
open(my $fh, '<', $src_file) or die "cannot open $src_file: $!";
my $src = do { local $/; <$fh> };
close($fh);

# Isolate the "does not point to valid monitor directory" cleanup block.
my $anchor = index($src, 'does not point to valid monitor directory');
check('found the monitor-link cleanup block', $anchor >= 0);
my $block = $anchor >= 0 ? substr($src, $anchor, 600) : '';
# Strip comment-only lines so the explanatory comment (which mentions the old
# rm "$link" on purpose) is not mistaken for live code.
my $code = join("\n", grep { !/^\s*#/ } split(/\n/, $block));

check('cleanup removes the link with unlink()',
  $code =~ /unlink\s*\(\s*\$link\s*\)/);
check('cleanup no longer runs rm "$link" through a shell',
  $code !~ /executeShellCommand\s*\(.*\$link/s);
check('cleanup does not build an rm command from the link name',
  $code !~ /rm.{0,20}"\$link"/s);
check('removal failure is reported',
  $code =~ /Error\(.*monitor link/);

# (2) Behavioural check: unlink() on a maliciously named symlink runs no shell.
my $dir = tempdir(CLEANUP => 1);
# A link name with shell metacharacters (no '/', so it is a single path
# component). If it ever reached a shell, $(...) / backticks would run touch.
my $evil_name = 'bad$(touch SENTINEL)`touch SENTINEL`name';
my $link = "$dir/$evil_name";

my $made = eval { symlink("$dir/nonexistent-target", $link) };
check('created a symlink with a command-substitution name', $made && -l $link);

# De-taint exactly as zmaudit does, then remove with unlink().
( my $detainted ) = ( $link =~ /^(.*)$/s );
my $removed = unlink($detainted);

check('unlink() removed the symlink', $removed && !-l $link);
check('no shell ran: the sentinel was never created',
  !-e "$dir/SENTINEL" && !-e 'SENTINEL');

print '1..'.($passes + $failures)."\n";
if ($failures) {
  print "# $failures of ".($passes + $failures)." checks failed\n";
  exit 1;
}
print "# all $passes checks passed\n";
exit 0;
