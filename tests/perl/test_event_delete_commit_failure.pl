#!/usr/bin/perl
#
# ZoneMinder::Event::delete() deletes the event's rows in its own transaction
# and then removes the event's files. If the COMMIT fails the rows are rolled
# back and still there, so the files must be kept; they used to be deleted
# anyway, leaving rows that point at nothing.
#
# The real sub delete is taken from Event.pm and run against a fake $dbh.
#
# Run as: perl tests/perl/test_event_delete_commit_failure.pl [path/to/Event.pm]
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);

my $file = $ARGV[0] // "$FindBin::Bin/../../scripts/ZoneMinder/lib/ZoneMinder/Event.pm";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);
my ($sub) = $src =~ /^(sub delete \{.*?^\} # end sub delete)/ms;
die "sub delete not found in $file\n" if !$sub;

package FakeDbh;
sub new { my ($c, %a) = @_; return bless { AutoCommit => 1, commit_ok => 1, err => 0, %a, calls => [] }, $c; }
sub ping { 1 }
sub do { push @{$_[0]{calls}}, $_[1]; 1 }
sub begin_work { push @{$_[0]{calls}}, 'begin'; $_[0]{err} = 0; 1 }
sub commit {
  my $s = shift;
  push @{$s->{calls}}, 'commit';
  return 1 if $s->{commit_ok};
  $s->{err} = 2013;
  return 0;
}
sub rollback { push @{$_[0]{calls}}, 'rollback'; 1 }
sub err { $_[0]{commit_ok} ? 0 : $_[0]{err} }
sub errstr { $_[0]{commit_ok} ? '' : 'Lost connection to MySQL server during query' }

package FakeStorage;
sub new { my ($c, $p) = @_; return bless { path => $p }, $c; }
sub Path { $_[0]{path} }
sub DoDelete { 1 }
sub adjust_diskspace { 1 }

package ZoneMinder::Database;
our $dbh;
sub zmDbDo { 1 }

package ZoneMinder::Event;
our @logged;
sub Warning { push @logged, "W: $_[0]" }
sub Info { }
sub Debug { }
sub Error { push @logged, "E: $_[0]" }
sub new { my ($c, %a) = @_; return bless { files_deleted => 0, %a }, $c; }
sub canEdit { 1 }
sub Storage { $_[0]{storage} }
sub DiskSpace { 0 }
sub Path { $_[0]{storage}->Path().'/'.$_[0]{Id} }
sub delete_files { $_[0]{files_deleted}++ }
{
  no warnings 'redefine';
  eval "package ZoneMinder::Event;\n$sub\n1;" or die "Couldn't compile sub delete: $@";
}

package main;
my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $cond, $detail) = @_;
  if ($cond) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n"; print "  $detail\n" if defined $detail; }
}

my $dir = tempdir(CLEANUP => 1);
sub make_event {
  return ZoneMinder::Event->new(Id => 7, MonitorId => 1, StartDateTime => '2026-11-24 00:00:00',
    storage => FakeStorage->new($dir));
}

$ZoneMinder::Database::dbh = FakeDbh->new(commit_ok => 1);
my $event = make_event();
$event->delete();
check('a committed delete removes the event files', $event->{files_deleted} == 1,
  'files_deleted='.$event->{files_deleted});

$ZoneMinder::Database::dbh = FakeDbh->new(commit_ok => 0);
@ZoneMinder::Event::logged = ();
$event = make_event();
$event->delete();
check('a failed commit keeps the event files', $event->{files_deleted} == 0,
  'files_deleted='.$event->{files_deleted});
check('a failed commit is rolled back',
  scalar(grep { $_ eq 'rollback' } @{$ZoneMinder::Database::dbh->{calls}}) >= 1,
  join(',', @{$ZoneMinder::Database::dbh->{calls}}));
check('a failed commit logs an error',
  scalar(grep { /^E: Failed deleting event 7/ } @ZoneMinder::Event::logged) == 1,
  join(' | ', @ZoneMinder::Event::logged));

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
