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
  package ZoneMinder::Memory;
  sub zmMemVerify { return 1; }
  sub zmMemKey { return undef; }
  sub zmMemInvalidate { return 1; }
}

use ZoneMinder::Monitor;

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

my $max = 45;
sub monitor { return bless {Id => 1, Name => 'test', @_}, 'ZoneMinder::Monitor'; }

check('limit methods exist', (ZoneMinder::Monitor->can('WatchStartupLimit') && ZoneMinder::Monitor->can('WatchImageLimit')) ? 1 : 0, 1);

# No image since startup: zmc sleeps StartupDelay seconds after setting its
# startup time, so zmwatch must wait that long on top of the usual limit.
check('no startup delay keeps the usual limit', monitor(StartupDelay => 0)->WatchStartupLimit($max), 45);
check('missing startup delay keeps the usual limit', monitor()->WatchStartupLimit($max), 45);
check('startup delay is added to the limit', monitor(StartupDelay => 60)->WatchStartupLimit($max), 105);

# A camera under one frame per second is never judged sooner than a normal one.
check('no MaxFPS uses the usual limit', monitor()->WatchImageLimit($max), 45);
check('MaxFPS of zero uses the usual limit', monitor(MaxFPS => 0)->WatchImageLimit($max), 45);
check('MaxFPS of one or more uses the usual limit', monitor(MaxFPS => 5)->WatchImageLimit($max), 45);
check('0.5 fps is not restarted after 6 seconds', monitor(MaxFPS => 0.5)->WatchImageLimit($max), 45);
check('very slow camera gets three frame intervals', monitor(MaxFPS => 0.05)->WatchImageLimit($max), 60);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
