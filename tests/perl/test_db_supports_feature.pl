#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ZoneMinder/Config.pm'} = 1;
  $INC{'ZoneMinder/Logger.pm'} = 1;
  package ZoneMinder::Base;
  our $VERSION = '0';
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

use ZoneMinder::Database qw(:all);

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

my $server_version;
{
  no warnings qw(redefine once);
  *ZoneMinder::Database::zmDbFetchOne = sub { return {'VERSION()' => $server_version}; };
}

sub skip_locked {
  $server_version = shift;
  return zmDbSupportsFeature('skip_locked') ? 'yes' : 'no';
}

check('MariaDB 10.5 has no SKIP LOCKED', skip_locked('10.5.23-MariaDB'), 'no');
check('MariaDB 10.6 has SKIP LOCKED', skip_locked('10.6.12-MariaDB'), 'yes');
check('MariaDB 10.11 has SKIP LOCKED', skip_locked('10.11.6-MariaDB-0+deb12u1'), 'yes');
check('MariaDB 11.4 has SKIP LOCKED', skip_locked('11.4.2-MariaDB'), 'yes');
check('MySQL 5.7 has no SKIP LOCKED', skip_locked('5.7.44'), 'no');
check('MySQL 8.0.0 has no SKIP LOCKED', skip_locked('8.0.0'), 'no');
check('MySQL 8.0.36 has SKIP LOCKED', skip_locked('8.0.36'), 'yes');
check('MySQL 8.4 has SKIP LOCKED', skip_locked('8.4.0'), 'yes');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
