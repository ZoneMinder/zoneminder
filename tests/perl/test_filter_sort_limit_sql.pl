#!/usr/bin/perl
#
# ZoneMinder::Filter::Sql: sort_field and limit from a stored filter row must
# not be appended to the zmfilter query verbatim (SQL injection).
#
# ZoneMinder::Filter needs DBI and zm.conf to load, so the ORDER BY / LIMIT
# block is extracted from the module text and evaluated with the same inputs.
# ZM_TEST_ROOT selects the source tree (default: this repo).
use strict;
use warnings;
use FindBin;

my $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../..";
my $file = "$root/scripts/ZoneMinder/lib/ZoneMinder/Filter.pm";
open(my $fh, '<', $file) or die "Cannot open $file: $!";
my $src = do { local $/; <$fh> };
close $fh;

my ($block) = $src =~ /^(\s*my \$sort_column = '';.*?\$sql \.= ' LIMIT 0,'\.\$filter_expr->\{limit\};\s*\})/ms
  or die "Could not extract sort/limit block from $file";

my $code = <<"CODE";
sub build_sql {
  my (\$filter_expr) = \@_;
  my \$sql = 'SELECT E.Id FROM Events AS E';
$block
  return \$sql;
}
1;
CODE
eval $code or die "eval failed: $@\n$code";

my $failures = 0;
sub check {
  my ($name, $ok, $detail) = @_;
  print(($ok ? 'ok' : 'FAIL')." - $name\n");
  print "  got: $detail\n" if !$ok;
  $failures++ if !$ok;
}

my $sql;

$sql = build_sql({sort_field => 'Id; DROP TABLE Events', limit => 0});
check('sort_field with statement separator is ignored', $sql !~ /DROP|ORDER BY/, $sql);

$sql = build_sql({sort_field => 'Score,(SELECT SLEEP(5))', limit => 0});
check('sort_field with subselect is ignored', $sql !~ /SLEEP|ORDER BY/, $sql);

$sql = build_sql({sort_field => 'StartDateTime', sort_asc => 1, limit => 0});
check('StartDateTime sort still used', $sql =~ /ORDER BY E\.StartDateTime ASC/, $sql);

$sql = build_sql({sort_field => 'Notes', limit => 0});
check('plain column name still used', $sql =~ /ORDER BY E\.Notes DESC/, $sql);

$sql = build_sql({sort_field => 'Id', limit => '5; DROP TABLE Events'});
check('limit with statement separator is ignored', $sql !~ /DROP|LIMIT/, $sql);

$sql = build_sql({sort_field => 'Id', limit => '5 UNION SELECT Password FROM Users'});
check('limit with UNION is ignored', $sql !~ /UNION|LIMIT/, $sql);

$sql = build_sql({sort_field => 'Id', limit => 100});
check('numeric limit still used', $sql =~ /LIMIT 0,100$/, $sql);

exit($failures ? 1 : 0);
