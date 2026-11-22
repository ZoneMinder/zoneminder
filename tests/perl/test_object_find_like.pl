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
  package ZoneMinder::Config;
  our %Config;
  sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
  package ZoneMinder::Logger;
  our $logger = bless {}, 'ZoneMinder::Logger::Null';
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
    *{$caller.'::logger'} = \$logger;
  }
  package ZoneMinder::Logger::Null;
  sub AUTOLOAD { return 1; }
  sub DESTROY { }
}

use ZoneMinder::Object;

package ZoneMinder::Thing;
our @ISA = ('ZoneMinder::Object');
our ($table, $primary_key, %fields, $default_sort);
$table = 'Things';
$primary_key = 'Id';
$default_sort = 'Id';
%fields = map { $_ => $_ } qw(Id MonitorId Name);

package main;

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

sub sql {
  my $sql = ZoneMinder::Thing->find_sql(@_);
  return $$sql{sql};
}

check('like compares the column itself',
  sql('Name like' => '%x%'), 'SELECT * FROM Things WHERE Name like ? ORDER BY Id');
check('ilike is spelled like for MySQL and MariaDB',
  sql('Name ilike' => '%x%'), 'SELECT * FROM Things WHERE Name like ? ORDER BY Id');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
