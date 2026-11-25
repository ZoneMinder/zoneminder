#!/usr/bin/perl
#
# The AutoTag filter action: zmfilter adds the filter's tag to each matching
# event, the daemon picks up filters that only tag, and Filter::Sql skips
# events that already carry the tag so they are not handled again each pass.
# Set ZMFILTER_IN to another zmfilter.pl.in to test it.
#
# Run as: perl tests/perl/test_zmfilter_autotag.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

# Stand-ins for the generated and database modules, so this runs without an
# installed ZoneMinder.
BEGIN {
  $INC{$_} = 1 for qw(ZoneMinder/Base.pm ZoneMinder/Config.pm ZoneMinder/Logger.pm
    ZoneMinder/Database.pm ZoneMinder/Object.pm ZoneMinder/Storage.pm ZoneMinder/Server.pm);
  package ZoneMinder::Object;
  sub new { my $c = shift; return bless {}, $c; }
  package ZoneMinder::Base;
  our $VERSION = '0';
  package ZoneMinder::Config;
  our %Config;
  sub import { no strict 'refs'; *{caller().'::Config'} = \%Config; }
  package ZoneMinder::Database;
  sub import {}
  package ZoneMinder::Logger;
  sub Debug { 1 } sub Info { 1 } sub Warning { 1 } sub Error { 1 } sub Fatal { 1 }
  sub import {
    no strict 'refs';
    my $c = caller();
    *{$c.'::'.$_} = \&{$_} for qw(Debug Info Warning Error Fatal);
  }
}
use ZoneMinder::General;
use ZoneMinder::Filter;

my $failures = 0;
my @queries;
# Callers pass a scalar truth value: a failed match in list context is an empty
# list, which would shift $detail into $ok.
sub check {
  my ($name, $ok, $detail) = @_;
  print(($ok ? 'ok' : 'FAIL')." - $name\n");
  print "  $detail\n" if !$ok and defined $detail;
  $failures++ if !$ok;
}

package FakeEvent;
sub save { return ''; }
package main;

our %Config;
sub Debug {} sub Info {} sub Warning {} sub Error {}
sub zmDbExecute { push @queries, [@_]; return 1; }

my $file = $ENV{ZMFILTER_IN} || "$FindBin::Bin/../../scripts/zmfilter.pl.in";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);
my ($sub) = $src =~ /^(sub checkFilterEvent \{.*?^\}[^\n]*\n)/ms or die "checkFilterEvent not found\n";
eval "$sub; 1" or die "Can't compile: $@";

sub run {
  my (%filter) = @_;
  @queries = ();
  checkFilterEvent({UserId => 3, %filter}, bless({ Id => 42 }, 'FakeEvent'));
  return @queries;
}

my @q = run(AutoTag => 1, AutoTagId => 7);
my ($insert) = grep { $_->[0] =~ /INSERT\b.*\bEvents_Tags\b/ } @q;
check('a tagging filter adds the tag to the event', $insert ? 1 : 0);
check('the tag row is the filter tag, the event and the filter owner',
  ($insert && join(',', @$insert[1..$#$insert]) eq '7,42,3') ? 1 : 0,
  $insert ? join(',', @$insert[1..$#$insert]) : 'no insert');
check('an event that already has the tag is not an error',
  ($insert && $insert->[0] =~ /INSERT IGNORE/) ? 1 : 0, $insert ? $insert->[0] : 'no insert');
check('the tag records when it was last assigned',
  scalar(grep { $_->[0] =~ /UPDATE\b.*\bTags\b.*LastAssignedDate/ and ($_->[1]//'') eq '7' } @q));

@q = run(AutoTag => 1, AutoTagId => 0);
check('no tag chosen means nothing is written', !@q, scalar(@q).' queries');
@q = run(AutoTag => 0, AutoTagId => 7);
check('the action turned off means nothing is written', !@q, scalar(@q).' queries');

# The daemon loads every filter that has an action, tagging included
my ($load) = $src =~ /(\$sql \.= '\( `AutoArchive` = 1.*?\) ORDER BY `Name`';)/s;
check('the daemon loads filters whose only action is tagging',
  ($load && $load =~ /`AutoTag` = 1/) ? 1 : 0, $load // 'filter query not found');

# Filter::Sql leaves out events that already carry the tag
{
  no warnings 'redefine';
  *ZoneMinder::Filter::sql_quote = sub { my $v = shift; return defined $v ? "'$v'" : 'NULL'; };
}
my $f = bless { Id=>1, Name=>'t', AutoTag=>1, AutoTagId=>7,
  Query_json=>'{"terms":[{"attr":"Id","op":">","val":"0"}]}' }, 'ZoneMinder::Filter';
my $sql = $f->Sql();
check('the filter SQL skips events that already have the tag',
  (defined($sql) && $sql =~ /NOT EXISTS \(SELECT 1 FROM Events_Tags AS ET WHERE ET\.EventId ?= ?E\.Id AND ET\.TagId ?= ?7\)/) ? 1 : 0, $sql);
$f = bless { Id=>1, Name=>'t', AutoTag=>1, AutoTagId=>'7) OR (1=1',
  Query_json=>'{"terms":[{"attr":"Id","op":">","val":"0"}]}' }, 'ZoneMinder::Filter';
$sql = $f->Sql();
check('a tag id that is not a number is not put into the SQL',
  (defined($sql) && $sql !~ /1=1/ && $sql !~ /Events_Tags/) ? 1 : 0, $sql);

print $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
