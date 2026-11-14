#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  $INC{'ZoneMinder/Memory.pm'} = 1;
  package ZoneMinder::Base;
  our $VERSION = '0';
  package ZoneMinder::Config;
  our %Config;
  sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
  package ZoneMinder::Logger;
  sub INFO { return 1; }
  sub logLevel { return 0; }
  sub Debug { return 1; }
  sub Info { return 1; }
  sub Warning { return 1; }
  sub Error { return 1; }
  sub Fatal { return 1; }
  sub import {
    no strict 'refs';
    my $caller = caller();
    *{$caller.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal INFO);
  }
}

use ZoneMinder::Event;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '') eq (defined $want ? $want : '')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

sub guess {
  my %event = @_;
  my $event = bless {Id => 1, StartDateTime => '2024-05-06 07:08:09', %event}, 'ZoneMinder::Event';
  return $event->guess_EndDateTime();
}

check('end is the start plus the length',
  guess(Length => 12), '2024-05-06 07:08:21');
check('an event without a length ends a second after it started',
  guess(Length => 0), '2024-05-06 07:08:10');
check('an end that is already set is kept',
  guess(Length => 12, EndDateTime => '2024-05-06 08:00:00'), '2024-05-06 08:00:00');

# zmrecover only saves the event when this returns a value.
my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmrecover.pl.in") or die "Can't open zmrecover.pl.in: $!";
  local $/;
  <$fh>;
};
my @calls = $source =~ /->(guess_\w+)\(/g;
check('zmrecover calls a method that exists',
  join(',', grep { !ZoneMinder::Event->can($_) } @calls), '');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
