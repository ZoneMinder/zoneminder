#!/usr/bin/perl
#
# ZoneMinder::Filter::Sql: statements built for terms that used to produce
# invalid SQL (empty attr, no terms with an Auto* action, Group, several zones).
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Filter;

my $failures = 0;
sub check {
  my ($name, $ok, $detail) = @_;
  print(($ok ? 'ok' : 'FAIL')." - $name\n");
  print "  $detail\n" if !$ok and defined $detail;
  $failures++ if !$ok;
}

{
  no warnings 'redefine';
  *ZoneMinder::Filter::sql_quote = sub {
    my $v = shift;
    return 'NULL' if !defined $v;
    $v =~ s/'/''/g;
    return "'$v'";
  };
  # Group 5 holds monitors 1 and 2, its child group 6 holds 2 and 3
  *ZoneMinder::Filter::_groupMonitorIds = sub {
    my $id = shift;
    return (1, 2, 3) if $id eq '5';
    return ();
  };
}

sub like {
  my ($name, $sql, $re) = @_;
  check($name, defined($sql) && scalar($sql =~ $re), $sql);
}

sub unlike {
  my ($name, $sql, $re) = @_;
  check($name, defined($sql) && !($sql =~ $re), $sql);
}

sub make_filter {
  my ($json, %extra) = @_;
  return bless { Id=>1, Name=>'test', Query_json=>$json, %extra }, 'ZoneMinder::Filter';
}

# Balanced brackets and no operator left dangling
sub well_formed {
  my $sql = shift;
  my $depth = 0;
  for my $c (split //, $sql) {
    $depth++ if $c eq '(';
    $depth-- if $c eq ')';
    return 0 if $depth < 0;
  }
  return 0 if $depth;
  return 0 if $sql =~ /\b(?:and|or)\s+(?:and|or)\b/i or $sql =~ /\(\s*(?:and|or)\b/i or $sql =~ /\b(?:and|or)\s*\)/i;
  return 1;
}

# Empty attr: the term is refused, not half emitted
{
  my $f = make_filter('{"terms":[{"attr":"Name","op":"=","val":"a"},{"cnj":"and","obr":1,"attr":"","op":"=","val":"x"},{"cnj":"and","attr":"Cause","op":"=","val":"b","cbr":1}]}');
  my $sql = $f->Sql(undef);
  check('empty attr refuses the filter instead of leaving "and (" behind', !defined($sql) || well_formed($sql), $sql);
}

# No terms with an Auto action
{
  my $sql = make_filter('{"terms":[]}', AutoArchive=>1)->Sql(undef);
  like('no terms + AutoArchive has a WHERE', $sql, qr/FROM Events AS E WHERE \( E\.Archived = 0 \)/);
  $sql = make_filter('{"terms":[],"skip_locked":1}', AutoArchive=>1, LockRows=>1)->Sql(undef);
  like('no terms + AutoArchive + skip_locked has no stray AND', $sql, qr/WHERE \( E\.Archived = 0 \) AND NOT EXISTS/);
  $sql = make_filter('{"terms":[],"skip_locked":1}', LockRows=>1)->Sql(undef);
  like('no terms, no Auto action, skip_locked starts the WHERE', $sql, qr/FROM Events AS E WHERE NOT EXISTS/);
  $sql = make_filter('{"terms":[{"attr":"Name","op":"=","val":"a"}]}', AutoArchive=>1, AutoEmail=>1)->Sql(undef);
  like('terms + Auto actions unchanged', $sql, qr/\) AND \( E\.Archived = 0 or E\.Emailed = 0 \)/);
}

# Group term
{
  my $sql = make_filter('{"terms":[{"attr":"Group","op":"=","val":"5"}]}')->Sql(undef);
  like('Group is compared by monitor id', $sql, qr/E\.MonitorId IN \(1,2,3\)/);
  unlike('Group does not reference E.Group', $sql, qr/E\.Group/);
  $sql = make_filter('{"terms":[{"attr":"Group","op":"!=","val":"5"}]}')->Sql(undef);
  like('Group != is NOT IN', $sql, qr/E\.MonitorId NOT IN \(1,2,3\)/);
  $sql = make_filter('{"terms":[{"attr":"Group","op":"=","val":"99"}]}')->Sql(undef);
  like('empty group is a valid list matching nothing', $sql, qr/E\.MonitorId IN \(0\)/);
  $sql = make_filter('{"terms":[{"attr":"Group","op":"!=","val":"99"}]}')->Sql(undef);
  like('empty group with != matches everything', $sql, qr/E\.MonitorId NOT IN \(0\)/);
}

# AlarmedZoneId with more than one zone
{
  my $sql = make_filter('{"terms":[{"attr":"AlarmedZoneId","op":"=","val":"3,4"}]}')->Sql(undef);
  like('both zones are queried', $sql, qr/ZoneId=3\).*ZoneId=4\)/s);
  like('zones are alternatives', $sql, qr/\(EXISTS \(SELECT \* FROM Stats[^)]*ZoneId=3\) OR EXISTS \(SELECT \* FROM Stats[^)]*ZoneId=4\)\)/);
  $sql = make_filter('{"terms":[{"attr":"AlarmedZoneId","op":"=","val":"3"}]}')->Sql(undef);
  like('single zone as before', $sql, qr/EXISTS \(SELECT \* FROM Stats WHERE EventId=E\.Id AND Score > 0 AND ZoneId=3\)/);
  $sql = make_filter('{"terms":[{"attr":"AlarmedZoneId","op":"=","val":"\"3\""}]}')->Sql(undef);
  like('quoted zone id', $sql, qr/ZoneId=3\)/);
}

print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
