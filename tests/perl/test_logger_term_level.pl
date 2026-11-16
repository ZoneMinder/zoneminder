#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN { delete $ENV{LOG_PRINT}; }

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

logInit(id=>'zm_test_termlevel', termLevel=>DEBUG, fileLevel=>NOLOG, databaseLevel=>NOLOG, syslogLevel=>NOLOG);
check('termLevel passed to logInit is used', logTermLevel(), DEBUG);
logTerm();

logInit(id=>'zm_test_termlevel', fileLevel=>NOLOG, databaseLevel=>NOLOG, syslogLevel=>NOLOG);
check('without the option the ZM_LOG_LEVEL_TERM setting is used',
  logTermLevel(), $ZoneMinder::Config::Config{ZM_LOG_LEVEL_TERM});
logTerm();

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
