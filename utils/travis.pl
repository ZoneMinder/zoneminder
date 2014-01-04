#!/usr/bin/env perl

use strict;
use warnings;

my @tests = (
   'zmpkg.pl start',
   'zmfilter.pl -f purgewhenfull',
);

sub run_test {
   my $test = $_[0];
   print "Running test: '$test'";

   my @args = ('sudo', $test);
   system(@args) == 0 or die "'$test' failed to run!";
}

foreach my $test (@tests) {
   run_test($test);
}
