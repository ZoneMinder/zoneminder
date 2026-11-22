#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

my @errors;
my @urls;
BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  $INC{'ZoneMinder/Memory.pm'} = 1;
  $INC{'LWP/UserAgent.pm'} = 1;
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
  sub Error { push @errors, $_[0]; return 1; }
  sub Fatal { return 1; }
  sub import {
    no strict 'refs';
    my $caller = caller();
    *{$caller.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal INFO);
  }
  package ZoneMinder::Memory;
  sub zmMemKey { return undef; }
  package LWP::UserAgent;
  sub new { return bless {}, shift; }
  sub ssl_opts { return; }
  sub get { push @urls, $_[1]; return bless {}, 'Stub::Response'; }
  package Stub::Response;
  sub is_success { return 1; }
  sub status_line { return '200 OK'; }
}

use ZoneMinder::Monitor;

my $failures = 0;
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

{
  no warnings qw(redefine once);
  *ZoneMinder::Monitor::Server = sub { return bless {}, 'Stub::Server'; };
  *Stub::Server::UrlToApi = sub { return 'http://other/zm/api'; };
}

$ZoneMinder::Config{ZM_SERVER_ID} = 1;
$ZoneMinder::Config{ZM_OPT_USE_AUTH} = 1;
my $monitor = bless {Id => 7, Type => 'Ffmpeg', ServerId => 2}, 'ZoneMinder::Monitor';

# AUTH_RELAY is neither hashed nor plain: the command must be refused, not sent
$ZoneMinder::Config{ZM_AUTH_RELAY} = 'none';
$monitor->zmcControl('restart');
check('no request is sent when AUTH_RELAY is none', scalar(@urls), 0);
check('the unsupported AUTH_RELAY is reported', scalar(@errors), 1);

# A supported relay still sends the command to the other server
@errors = ();
$ZoneMinder::Config{ZM_AUTH_RELAY} = 'plain';
$monitor->zmcControl('restart');
check('the command is sent to the monitor server', join(',', @urls), 'http://other/zm/api/monitors/daemonControl/7/restart/zmc.json');
check('no error for plain', scalar(@errors), 0);

print $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
