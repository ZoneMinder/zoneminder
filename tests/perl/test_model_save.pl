#!/usr/bin/perl
#
# Test ZoneMinder::Model::save and Manufacturer relationships.
#
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
  sub Debug {} sub Info {} sub Warning {} sub Error {} sub Fatal {} sub INFO { 1 }
  sub import {
    no strict 'refs';
    my $caller = caller();
    *{$caller.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal INFO);
    *{$caller.'::logger'} = \$logger;
  }
  package ZoneMinder::Logger::Null;
  sub AUTOLOAD { 1 } sub DESTROY {}
}

use ZoneMinder::Model;
use ZoneMinder::Manufacturer;

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

my @queries;
my $mock_dbh = bless {}, 'MockDBH';
{
  no warnings 'redefine';
  *MockDBH::prepare = sub {
    my ($self, $sql) = @_;
    push @queries, $sql;
    return bless { sql => $sql }, 'MockSTH';
  };
  *MockDBH::last_insert_id = sub { return 99; };
  *MockDBH::selectrow_hashref = sub {
    my ($self, $sql, $opt, $id) = @_;
    if ($sql =~ /FROM `Manufacturers` WHERE `Id`=\?/) {
      return { Id => $id, Name => 'Sony' };
    }
    return undef;
  };
  *MockSTH::execute = sub { return 1; };
  *ZoneMinder::Database::start_transaction = sub { 0 };
  *ZoneMinder::Database::end_transaction = sub { 0 };
}
$ZoneMinder::Database::dbh = $mock_dbh;

# Test 1: Saving a Model without a Manufacturer object should not crash
@queries = ();
my $model = ZoneMinder::Model->new(undef, { Name => 'Cam123' });
my $save_err = eval { $model->save(); };
my $died = $@ ? $@ : '';
check('save() on model without manufacturer object does not die', $died, '');
check('save() on model executed INSERT', ($queries[0] =~ /^INSERT INTO `Models`/ ? 1 : 0), 1);

# Test 2: Calling Manufacturer() when ManufacturerId is set loads the Manufacturer
my $model_with_mfg_id = ZoneMinder::Model->new(10, { Id => 10, Name => 'Cam456', ManufacturerId => 5 });
my $mfg = $model_with_mfg_id->Manufacturer();
check('Manufacturer() returns a ZoneMinder::Manufacturer object', ref($mfg), 'ZoneMinder::Manufacturer');
check('Manufacturer() loaded correct name from ManufacturerId', $mfg ? $mfg->Name() : '', 'Sony');

# Test 3: Saving a Model with a new un-persisted Manufacturer saves the Manufacturer first
@queries = ();
my $new_mfg = ZoneMinder::Manufacturer->new(undef, { Name => 'Axis' });
my $model_with_new_mfg = ZoneMinder::Model->new(undef, { Name => 'P3245' });
$model_with_new_mfg->Manufacturer($new_mfg);
eval { $model_with_new_mfg->save(); };
check('save() with new manufacturer object executed manufacturer INSERT first',
  ($queries[0] =~ /^INSERT INTO `Manufacturers`/ ? 1 : 0), 1);
check('save() with new manufacturer populated ManufacturerId on model',
  $model_with_new_mfg->ManufacturerId(), 99);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
