#!/usr/bin/perl
use strict;
use warnings;
use FindBin;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

sub slurp {
  open(my $fh, '<', $_[0]) or die "Can't open $_[0]: $!";
  local $/;
  return scalar <$fh>;
}

my $tool = slurp("$FindBin::Bin/../../scripts/zmcamtool.pl.in");
my $schema = slurp("$FindBin::Bin/../../db/zm_create.sql.in");

my ($table) = $schema =~ /CREATE TABLE `MonitorPresets` \((.*?)\n\) ENGINE/s;
my $columns = () = $table =~ /^\s*`\w+`/mg;

my ($select) = $tool =~ /my \$sql = 'SELECT\s+(Name,.*?)\s+FROM Monitors WHERE Id = \?'/s;
my $selected = () = $select =~ /\w+/g;

my ($sub) = $tool =~ /^(sub insertQuery \{.*?^\})/ms;
eval $sub;
die $@ if $@;

my $sql;
{
  package FakeSth;
  sub execute { 1 }
  sub finish { 1 }
  package FakeDbh;
  sub prepare_cached { $sql = $_[1]; return bless {}, 'FakeSth'; }
}

insertQuery(bless({}, 'FakeDbh'), 'MonitorPresets', ('x') x $selected);
my ($values) = $sql =~ /VALUES \((.*)\)/;
my @given = split(/,/, $values, -1);
check('toPreset insert supplies a value for every MonitorPresets column', scalar(@given), $columns);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
