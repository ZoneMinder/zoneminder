#!/usr/bin/perl
use strict;
use warnings;
use FindBin;

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

sub check_fields {
  my ($name, $entry, $id, $capturing, $analysing, $recording) = @_;
  if (!$entry or ref($entry) ne 'HASH') {
    $failures++;
    print "FAIL - $name (entry is not a hashref)\n";
    print '  got:  '.(defined $entry ? $entry : '(undef)')."\n";
    return;
  }
  check("$name Id", $entry->{Id}, $id);
  check("$name Capturing", $entry->{Capturing}, $capturing);
  check("$name Analysing", $entry->{Analysing}, $analysing);
  check("$name Recording", $entry->{Recording}, $recording);
}

my $path = "$FindBin::Bin/../../scripts/zmpkg.pl.in";
open(my $fh, '<', $path) or die "Cannot read $path: $!";
my $src = do { local $/; <$fh> };
close($fh);

# Stub the logging subs the extracted code may call
sub Error { }
sub Debug { }
sub Warning { }
sub Info { }

foreach my $subname ('legacyFunctionModes', 'parseStateDefinition', 'findStateDefinition') {
  $src =~ /^(sub $subname \{.*?^\})/ms or die "$subname not found in $path";
  eval $1;
  die $@ if $@;
}

# 4-field entry
my $defs = parseStateDefinition('test', '1:Always:None:OnMotion') || [];
check('4-field entry count', scalar @$defs, 1);
check_fields('4-field entry', $defs->[0], 1, 'Always', 'None', 'OnMotion');

# 3-field legacy entry
$defs = parseStateDefinition('test', '2:Modect:1') || [];
check('3-field legacy count', scalar @$defs, 1);
check_fields('3-field legacy entry', $defs->[0], 2, 'Always', 'Always', 'OnMotion');

# disabled legacy entry
$defs = parseStateDefinition('test', '3:Mocord:0') || [];
check('disabled legacy count', scalar @$defs, 1);
check_fields('disabled legacy entry', $defs->[0], 3, 'None', 'None', 'None');

# whitespace is trimmed on the entry and on each field
$defs = parseStateDefinition('test', ' 2 : Always:None:None ') || [];
check('trimmed entry count', scalar @$defs, 1);
check('trimmed entry Id', $defs->[0] ? $defs->[0]{Id} : undef, '2');
check('trimmed entry Capturing', $defs->[0] ? $defs->[0]{Capturing} : undef, 'Always');

# malformed entries are skipped, not pushed with undef Id
$defs = parseStateDefinition('test', 'garbage,1:2,4:Bogus:1,5:Always:Always:Always') || [];
check('malformed entries skipped, count', scalar @$defs, 1);
check('no entry has undef Id', (grep { !defined $_->{Id} } @$defs) ? 'undef Id present' : 'no undef Id', 'no undef Id');
check_fields('well-formed entry among malformed', $defs->[0], 5, 'Always', 'Always', 'Always');

# empty definition string gives an empty list
$defs = parseStateDefinition('test', '') || [];
check('empty definition gives empty list', scalar @$defs, 0);

# findStateDefinition matches Id exactly, never as a regex
my $search = [
  {Id=>'12',   Capturing=>'None',   Analysing=>'None', Recording=>'None'},
  {Id=>'.*',   Capturing=>'Always', Analysing=>'None', Recording=>'None'},
  {Id=>'1',    Capturing=>'Always', Analysing=>'Always', Recording=>'OnMotion'},
];
my $found = findStateDefinition($search, '1');
check('findStateDefinition exact match Id', $found ? $found->{Id} : undef, '1');
check('findStateDefinition exact match Capturing', $found ? $found->{Capturing} : undef, 'Always');

$found = findStateDefinition($search, '5');
check('regex .* Id does not match monitor 5', defined $found ? 'found' : 'not found', 'not found');

$found = findStateDefinition([{Id=>'12', Capturing=>'None', Analysing=>'None', Recording=>'None'}], '1');
check("monitor id 1 does not match definition Id 12", defined $found ? 'found' : 'not found', 'not found');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
