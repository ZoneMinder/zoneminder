#!/usr/bin/perl
#
# ZoneMinder::Filter::getLoad reads the 1 minute load from uptime for
# SystemLoad filter terms. Under a locale with a decimal comma uptime prints
# "load average: 2,52, 1,58, 0,59", which must give 2.52, not 2.
#
# Run as: perl tests/perl/test_filter_system_load.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use File::Temp qw(tempdir);
use ZoneMinder::Filter;

my $failures = 0;
sub check {
  my ($name, $got, $want) = @_;
  my $ok = defined($got) && $got eq $want;
  print(($ok ? 'ok' : 'FAIL')." - $name\n");
  print '  got '.(defined $got ? $got : 'undef').", want $want\n" if !$ok;
  $failures++ if !$ok;
}

my $dir = tempdir(CLEANUP => 1);
local $ENV{PATH} = "$dir:$ENV{PATH}";

sub load_from {
  my $line = shift;
  open(my $fh, '>', "$dir/uptime") or die "Can't write $dir/uptime: $!";
  print $fh "#!/bin/sh\necho '$line'\n";
  close($fh);
  chmod(0755, "$dir/uptime");
  return ZoneMinder::Filter::getLoad();
}

check('linux, decimal point',
  load_from(' 10:00:01 up 3 days,  2:03,  1 user,  load average: 2.52, 1.58, 0.59'), '2.52');
check('linux, decimal comma',
  load_from(' 10:00:01 up 3 days,  2:03,  1 user,  load average: 2,52, 1,58, 0,59'), '2.52');
check('bsd and macos, decimal point',
  load_from('10:00  up 6 days, 22:11, 1 user, load averages: 4.87 4.37 3.22'), '4.87');
check('bsd and macos, decimal comma',
  load_from('10:00  up 6 days, 22:11, 1 user, load averages: 4,87 4,37 3,22'), '4.87');
check('no load in the output', load_from('garbage'), '-1');

print "\n".($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
