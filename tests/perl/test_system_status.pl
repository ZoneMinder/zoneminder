#!/usr/bin/perl
#
# ZoneMinder::General::systemStatus returns what "zmdc.pl check" prints.
# zmupdate.pl compares it with 'running' to offer to stop ZoneMinder before
# upgrading the database, so the trailing newline must be gone whether or
# not debug logging is on.
#
# Run as: perl tests/perl/test_system_status.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN { $ENV{LOG_FILE} = "/tmp/zm_test_system_status_$$.log"; }

use File::Temp qw(tempdir);
use ZoneMinder::Config qw(:all);
use ZoneMinder::General qw(systemStatus);

my $failures = 0;
sub check {
  my ($name, $ok) = @_;
  print(($ok ? 'ok' : 'NOT OK')." - $name\n");
  $failures++ if !$ok;
}

my $dir = tempdir(CLEANUP => 1);
$Config{ZM_PATH_BIN} = $dir;

foreach my $state ('running', 'stopped') {
  open(my $fh, '>', "$dir/zmdc.pl") or die "Can't write $dir/zmdc.pl: $!";
  print $fh "#!/bin/sh\necho $state\n";
  close($fh);
  chmod(0755, "$dir/zmdc.pl");
  my $status = systemStatus();
  check("zmdc.pl check printing '$state' gives '$state'", $status eq $state);
}

print "\n".($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
