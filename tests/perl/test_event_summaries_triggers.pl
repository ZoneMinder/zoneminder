#!/usr/bin/perl
#
# Event_Summaries is maintained by database triggers, so the only way to check
# it is to drive real statements against a real server and read the counters
# back. These tests load db/triggers.sql into a scratch database and exercise
# the paths that maintain it: creating, resizing, archiving and deleting an
# event, and pruning a bucket table the way zmstats.pl does.
#
# Run as:
#   ZM_TEST_DB_NAME=zm_trigger_test ZM_TEST_DB_HOST=localhost \
#   ZM_TEST_DB_USER=... ZM_TEST_DB_PASS=... perl tests/perl/test_event_summaries_triggers.pl
#
# Skips rather than fails when no scratch database is configured, so it stays
# out of the way of anyone running the suite without one. It never touches the
# ZoneMinder database; point it only at a database you are happy to have
# emptied, because it deletes from the tables it uses between cases.
#
# To make one, copying the schema out of an existing install rather than
# running zm_create.sql:
#   mysql -e "CREATE DATABASE zm_trigger_test CHARACTER SET utf8mb4"
#   mysqldump --no-data --skip-triggers zm \
#     Events Events_Hour Events_Day Events_Week Events_Month \
#     Events_Archived Event_Summaries Monitors Zones | mysql zm_trigger_test
use strict;
use warnings;
use FindBin;
use DBI;

my $db_name = $ENV{ZM_TEST_DB_NAME};
if (!$db_name) {
  print "1..0 # SKIP set ZM_TEST_DB_NAME to a scratch database to run these\n";
  exit 0;
}
my $db_host = $ENV{ZM_TEST_DB_HOST} || 'localhost';
my $db_user = $ENV{ZM_TEST_DB_USER};
my $db_pass = $ENV{ZM_TEST_DB_PASS};

my $dbh = DBI->connect("DBI:mysql:database=$db_name;host=$db_host", $db_user, $db_pass,
                       {RaiseError => 1, AutoCommit => 1, PrintError => 0});

my $failures = 0;
my $passes = 0;

sub ok {
  my ($name, $cond, $detail) = @_;
  if ($cond) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n"; print "  $detail\n" if defined $detail; }
}

sub is {
  my ($name, $got, $want) = @_;
  $got = defined($got) ? $got : '(undef)';
  ok($name, $got eq $want, "got '$got', wanted '$want'");
}

# Load through the mysql client rather than DBI. The file is written for that
# client's "delimiter //", and it is also how the file is applied in production
# (cmake install and zmupdate.pl), so this exercises the real path instead of a
# reimplementation of it.
sub load_triggers {
  my ($path) = @_;
  local $ENV{MYSQL_PWD} = $db_pass if defined $db_pass;   # keeps it out of ps
  my @cmd = ('mysql', "--host=$db_host", "--database=$db_name");
  push @cmd, "--user=$db_user" if defined $db_user;
  open(my $in, '<', $path) or die "can't read $path: $!";
  open(my $mysql, '|-', @cmd) or die "can't run mysql: $!";
  print {$mysql} $_ while <$in>;
  close $in;
  close $mysql or die "mysql failed loading $path (exit @{[$? >> 8]})";
}

sub reset_tables {
  $dbh->do("DELETE FROM $_") for
    qw(Events Events_Hour Events_Day Events_Week Events_Month Events_Archived Event_Summaries);
}

# Rows touched in Event_Summaries, which is what the contention is about.
sub handler_updates {
  my $row = $dbh->selectrow_arrayref("SHOW SESSION STATUS LIKE 'Handler_update'");
  return $row->[1];
}

sub summary {
  my ($monitor_id, $col) = @_;
  my $row = $dbh->selectrow_arrayref(
    "SELECT COALESCE(`$col`,0) FROM Event_Summaries WHERE MonitorId=?", undef, $monitor_id);
  return $row ? $row->[0] : undef;
}

# An event as zm_event.cpp creates one: the Events row, a row in each bucket,
# and the Event_Summaries counters bumped in the same statement.
sub create_event {
  my ($id, $monitor_id, $disk_space) = @_;
  # StateId is NOT NULL with no default; its value is irrelevant here.
  $dbh->do("INSERT INTO Events (Id,MonitorId,StateId,StartDateTime,DiskSpace,Archived,Name)
            VALUES (?,?,1,NOW(),?,0,'test')", undef, $id, $monitor_id, $disk_space);
  for my $bucket (qw(Events_Hour Events_Day Events_Week Events_Month)) {
    $dbh->do("INSERT INTO $bucket (EventId,MonitorId,StartDateTime,DiskSpace)
              VALUES (?,?,NOW(),?)", undef, $id, $monitor_id, $disk_space);
  }
  $dbh->do("INSERT INTO Event_Summaries
              (MonitorId,HourEvents,DayEvents,WeekEvents,MonthEvents,TotalEvents,
               HourEventDiskSpace,DayEventDiskSpace,WeekEventDiskSpace,MonthEventDiskSpace,TotalEventDiskSpace)
            VALUES (?,1,1,1,1,1,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              HourEvents=COALESCE(HourEvents,0)+1, DayEvents=COALESCE(DayEvents,0)+1,
              WeekEvents=COALESCE(WeekEvents,0)+1, MonthEvents=COALESCE(MonthEvents,0)+1,
              TotalEvents=COALESCE(TotalEvents,0)+1,
              HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)+VALUES(HourEventDiskSpace),
              DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)+VALUES(DayEventDiskSpace),
              WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)+VALUES(WeekEventDiskSpace),
              MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)+VALUES(MonthEventDiskSpace),
              TotalEventDiskSpace=COALESCE(TotalEventDiskSpace,0)+VALUES(TotalEventDiskSpace)",
           undef, $monitor_id, ($disk_space) x 5);
}

load_triggers("$FindBin::Bin/../../db/triggers.sql");

# ---------------------------------------------------------------------------
reset_tables();
create_event(1, 7, 100);
is('create: TotalEvents', summary(7, 'TotalEvents'), '1');
is('create: HourEvents', summary(7, 'HourEvents'), '1');
is('create: TotalEventDiskSpace', summary(7, 'TotalEventDiskSpace'), '100');
is('create: HourEventDiskSpace', summary(7, 'HourEventDiskSpace'), '100');

# ---------------------------------------------------------------------------
# Recording grows the event; every bucket's disk space has to follow.
$dbh->do("UPDATE Events SET DiskSpace=250 WHERE Id=1");
is('resize: TotalEventDiskSpace', summary(7, 'TotalEventDiskSpace'), '250');
is('resize: HourEventDiskSpace', summary(7, 'HourEventDiskSpace'), '250');
is('resize: MonthEventDiskSpace', summary(7, 'MonthEventDiskSpace'), '250');
is('resize: bucket row followed',
   $dbh->selectrow_arrayref("SELECT DiskSpace FROM Events_Hour WHERE EventId=1")->[0], '250');

# ---------------------------------------------------------------------------
$dbh->do("UPDATE Events SET Archived=1 WHERE Id=1");
is('archive: ArchivedEvents', summary(7, 'ArchivedEvents'), '1');
is('archive: ArchivedEventDiskSpace', summary(7, 'ArchivedEventDiskSpace'), '250');

# An already-archived event that keeps recording. Events_Archived is updated
# but Event_Summaries.ArchivedEventDiskSpace has to move with it.
$dbh->do("UPDATE Events SET DiskSpace=400 WHERE Id=1");
is('archived resize: Events_Archived row followed',
   $dbh->selectrow_arrayref("SELECT DiskSpace FROM Events_Archived WHERE EventId=1")->[0], '400');
is('archived resize: ArchivedEventDiskSpace', summary(7, 'ArchivedEventDiskSpace'), '400');
is('archived resize: TotalEventDiskSpace', summary(7, 'TotalEventDiskSpace'), '400');

# ---------------------------------------------------------------------------
$dbh->do("UPDATE Events SET Archived=0 WHERE Id=1");
is('unarchive: ArchivedEvents', summary(7, 'ArchivedEvents'), '0');
is('unarchive: ArchivedEventDiskSpace', summary(7, 'ArchivedEventDiskSpace'), '0');

# ---------------------------------------------------------------------------
my $before = handler_updates();
$dbh->do("DELETE FROM Events WHERE Id=1");
my $delete_updates = handler_updates() - $before;
is('delete: TotalEvents', summary(7, 'TotalEvents'), '0');
is('delete: HourEvents', summary(7, 'HourEvents'), '0');
is('delete: MonthEvents', summary(7, 'MonthEvents'), '0');
is('delete: TotalEventDiskSpace', summary(7, 'TotalEventDiskSpace'), '0');
is('delete: buckets emptied',
   $dbh->selectrow_arrayref("SELECT COUNT(*) FROM Events_Hour WHERE EventId=1")->[0], '0');

# The whole point of consolidating: one Event DELETE should touch the
# Event_Summaries row once, not once per bucket.
ok("delete: Event_Summaries updated at most twice (got $delete_updates)",
   $delete_updates <= 2,
   "a single Event delete drove $delete_updates row updates; the cascade design drove 5");

# ---------------------------------------------------------------------------
# zmstats.pl prunes aged rows straight out of the bucket tables. Nothing
# decrements the bucket counters when it does, by design: the Events-level
# triggers only adjust a counter when the event was still in that bucket, and
# zmstats resyncs Hour/Day/Week/Month from COUNT(*)/SUM(DiskSpace) on the same
# pass. So the bucket counters are eventually consistent, bounded by
# ZM_STATS_UPDATE_INTERVAL, while the Total columns stay exact throughout.
reset_tables();
create_event(2, 7, 50);
$dbh->do("DELETE FROM Events_Hour WHERE EventId=2");
is('bucket prune: TotalEvents untouched', summary(7, 'TotalEvents'), '1');
is('bucket prune: TotalEventDiskSpace untouched', summary(7, 'TotalEventDiskSpace'), '50');

$dbh->do("DELETE FROM Events WHERE Id=2");
# Exact, because they are maintained by the Events triggers alone.
is('prune then delete: TotalEvents exact', summary(7, 'TotalEvents'), '0');
is('prune then delete: TotalEventDiskSpace exact', summary(7, 'TotalEventDiskSpace'), '0');
# Untouched by the prune, so still exact too.
is('prune then delete: MonthEvents exact', summary(7, 'MonthEvents'), '0');
# Stale, and allowed to be: the row was pruned out from under the counter.
# It must never go negative or below the truth, only sit above it until the
# resync runs.
ok('prune then delete: HourEvents is stale-high, never negative',
   summary(7, 'HourEvents') >= 0,
   'HourEvents went negative: '.summary(7, 'HourEvents'));

# The resync zmstats.pl runs on every pass where it pruned anything. This is
# what makes the staleness above self-healing rather than permanent.
$dbh->do(
  "UPDATE Event_Summaries es SET
     HourEvents          = (SELECT COUNT(*) FROM Events_Hour  WHERE MonitorId=es.MonitorId),
     HourEventDiskSpace  = COALESCE((SELECT SUM(DiskSpace) FROM Events_Hour  WHERE MonitorId=es.MonitorId),0),
     DayEvents           = (SELECT COUNT(*) FROM Events_Day   WHERE MonitorId=es.MonitorId),
     DayEventDiskSpace   = COALESCE((SELECT SUM(DiskSpace) FROM Events_Day   WHERE MonitorId=es.MonitorId),0),
     WeekEvents          = (SELECT COUNT(*) FROM Events_Week  WHERE MonitorId=es.MonitorId),
     WeekEventDiskSpace  = COALESCE((SELECT SUM(DiskSpace) FROM Events_Week  WHERE MonitorId=es.MonitorId),0),
     MonthEvents         = (SELECT COUNT(*) FROM Events_Month WHERE MonitorId=es.MonitorId),
     MonthEventDiskSpace = COALESCE((SELECT SUM(DiskSpace) FROM Events_Month WHERE MonitorId=es.MonitorId),0)
   WHERE es.MonitorId=7");
is('after resync: HourEvents', summary(7, 'HourEvents'), '0');
is('after resync: HourEventDiskSpace', summary(7, 'HourEventDiskSpace'), '0');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
