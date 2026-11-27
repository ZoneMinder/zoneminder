#!/usr/bin/perl
# Server::Url() brackets an IPv6 hostname so the port can follow it.
# ZM_TEST_ROOT may point at another copy of the tree.
use strict;
use warnings;
use FindBin;

my $root;
BEGIN {
  $root = $ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..";
  unshift @INC, "$root/scripts/ZoneMinder/lib";
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  $INC{'ZoneMinder/Object.pm'} = 1;
  package ZoneMinder::Base;
  our $VERSION = '0';
  package ZoneMinder::Config;
  our %Config;
  sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
  package ZoneMinder::Logger;
  sub import {
    no strict 'refs';
    my $caller = caller();
    *{$caller.'::'.$_} = sub { return 1; } for qw(Debug Info Warning Error Fatal);
  }
  package ZoneMinder::Object;
  our $VERSION = '0';
  sub import {}
}

require ZoneMinder::Server;

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
  *ZoneMinder::Server::Id = sub { return $_[0]{Id}; };
  *ZoneMinder::Server::Protocol = sub { return $_[0]{Protocol}; };
  *ZoneMinder::Server::Hostname = sub { return $_[0]{Hostname}; };
  *ZoneMinder::Server::Port = sub { return $_[0]{Port}; };
}

sub url {
  my ($hostname, $port) = @_;
  my $server = bless { Id => 1, Protocol => 'http', Hostname => $hostname, Port => $port },
    'ZoneMinder::Server';
  return $server->Url();
}

check('IPv6 host with a port is bracketed', url('fd00::5', 8080), 'http://[fd00::5]:8080');
check('IPv6 host with the default port is bracketed', url('fd00::5', 80), 'http://[fd00::5]');
check('an already bracketed IPv6 host is unchanged', url('[fd00::5]', 8080), 'http://[fd00::5]:8080');
check('IPv4 host is unchanged', url('192.168.1.5', 8080), 'http://192.168.1.5:8080');
check('host name is unchanged', url('zm.example.com', 8080), 'http://zm.example.com:8080');

exit($failures ? 1 : 0);
