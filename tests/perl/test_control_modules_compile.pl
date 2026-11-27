#!/usr/bin/perl
#
# Every PTZ control module must compile. zmcontrol.pl loads the module named
# by the monitor's Control at run time, so one that does not compile fails
# only when someone tries to move that camera: PSIA.pm referred to variables
# it never declared from its first version on, and every PSIA command died.
#
# Run as: perl tests/perl/test_control_modules_compile.pl
#
use strict;
use warnings;
use FindBin;

my $lib = "$FindBin::Bin/../../scripts/ZoneMinder/lib";
my $failures = 0;
my $passes = 0;

foreach my $file (sort glob("$lib/ZoneMinder/Control.pm"), sort glob("$lib/ZoneMinder/Control/*.pm")) {
  (my $name = $file) =~ s{.*/lib/}{};
  my $out = `"$^X" -I"$lib" -c "$file" 2>&1`;
  if ($? == 0) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print map { "  $_\n" } grep { !/uninitialized value/ } split(/\n/, $out);
  }
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
