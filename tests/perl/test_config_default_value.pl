#!/usr/bin/perl
#
# The C++ config loader reads back only the Config rows where Value differs
# from DefaultValue, relying on compiled-in defaults for the rest. That only
# holds if saveConfigToDB writes both columns in the same form, so these tests
# check the stored form of every option in ConfigData.
#
# Run as: perl tests/perl/test_config_default_value.pl
#
# ConfigData has no database dependency, unlike ZoneMinder::Config, so this
# runs without a configured ZoneMinder.
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../build/scripts/ZoneMinder/lib";

use ZoneMinder::ConfigData qw(:data);

my $failures = 0;
my $passes = 0;

sub ok {
  my ($name, $cond, $detail) = @_;
  if ($cond) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print "  $detail\n" if defined $detail;
  }
}

my @booleans = grep { $_->{type}{db_type} eq 'boolean' } @options;
ok('ConfigData still has boolean options to check', scalar(@booleans) > 0);

# The representation itself: no option may reintroduce yes/no.
foreach my $option (@booleans) {
  my $default = $option->{default};
  next if !defined($default);
  ok("$option->{name} default is stored as 1 or 0",
      $default eq '1' || $default eq '0',
      "default is '$default'");
}

# Requires clauses are written into the Requires column verbatim, and the web
# UI string-compares them against the referenced option's stored Value.
foreach my $option (@options) {
  next if !$option->{requires};
  foreach my $req (@{$option->{requires}}) {
    my $target = $options_hash{$req->{name}};
    next if !$target or $target->{type}{db_type} ne 'boolean';
    ok("$option->{name} requires $req->{name} in stored form",
        $req->{value} eq '1' || $req->{value} eq '0',
        "requires value is '$req->{value}'");
  }
}

# The invariant the C++ loader depends on: at a fresh install every row has
# Value equal to DefaultValue, so none of them is read back. Both columns are
# written through dbValue by saveConfigToDB and by zmconfgen.
foreach my $option (@options) {
  my $db_type = $option->{type}{db_type};
  my $value = dbValue($db_type, $option->{value});
  my $default = dbValue($db_type, $option->{default});
  ok("$option->{name} stores Value equal to DefaultValue when unchanged",
      $value eq $default,
      "Value '$value' vs DefaultValue '$default'");
  next if $db_type ne 'boolean';
  ok("$option->{name} stores a boolean as 1 or 0",
      $value eq '1' || $value eq '0',
      "stored as '$value'");
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
