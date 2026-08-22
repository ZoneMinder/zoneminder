use strict;
use warnings;
use Test::More;
use File::Temp qw(tempdir);

eval { require ZoneMinder::Event; 1 } or plan skip_all => "cannot load ZoneMinder::Event: $@";
require ZoneMinder::Database;
plan tests => 11;

# Storage is shared across monitors; Event_Summaries is per-monitor. delete()
# has to take the coarse Storage row before the deletes cascade into the fine
# per-monitor rows, otherwise two concurrent batches on different monitors
# deadlock (issue #4964). Nothing here needs a database: we record the order
# statements are issued in.

my $storage_dir = tempdir(CLEANUP => 1);

{
  package Test::Storage;
  sub new { my ($class, $id, $path) = @_; return bless {Id => $id, Path => $path, DiskSpace => 1_000_000}, $class }
  sub Id { return $_[0]{Id} }
  sub Path { return $_[0]{Path} }
  sub DoDelete { return 0 }  # keep delete() out of the filesystem
  # Mirrors ZoneMinder::Storage::adjust_diskspace, which is what delete() calls
  # now. It has to go through zmDbDo so the statement shows up in the recorded
  # order, which is the whole point of this test.
  sub adjust_diskspace {
    my ($self, $delta) = @_;
    return if !$$self{Id} or !$delta;
    return ZoneMinder::Database::zmDbDo(
      'UPDATE Storage SET DiskSpace=GREATEST(COALESCE(DiskSpace,0)+?,0) WHERE Id=?',
      $delta, $$self{Id});
  }

  # The pre-fix code reclaimed disk space through these rather than through
  # zmDbDo. They are here so that path runs to completion and the ordering
  # assertions below are what fail, rather than a missing method.
  sub lock_and_load { return 1 }
  sub DiskSpace { return $_[0]{DiskSpace} }
  sub save { return '' }
}

{
  package Test::STH;
  # Just enough statement handle for ZoneMinder::Logger's INSERT INTO Logs.
  sub new { return bless {}, shift }
  sub execute { return 1 }
  sub finish { return 1 }
  sub errstr { return '' }
}

{
  package Test::DBH;
  # AutoCommit off means "caller is managing the transaction", which is the
  # batch path where the deadlock actually bites.
  sub new { return bless {AutoCommit => 0}, shift }
  sub ping { return 1 }
  sub err { return 0 }
  sub errstr { return '' }
  sub begin_work { return 1 }
  sub commit { return 1 }
  sub rollback { return 1 }
  sub do { return 1 }
  sub prepare { return Test::STH->new() }
  sub prepare_cached { return Test::STH->new() }
  sub selectrow_hashref { return undef }
  sub quote { return $_[1] }
}

my @issued;
{
  no warnings 'redefine', 'once';
  *ZoneMinder::Database::zmDbDo = sub { push @issued, [@_]; return 1 };
}

sub run_delete {
  my ($diskspace) = @_;
  @issued = ();
  no warnings "once";
  $ZoneMinder::Database::dbh = Test::DBH->new();

  my $event = new ZoneMinder::Event();
  $$event{Id} = 42;
  $$event{MonitorId} = 8;
  $$event{StartDateTime} = '2026-01-01 00:00:00';
  $$event{Storage} = Test::Storage->new(2, $storage_dir);
  $$event{StorageId} = 2;
  $$event{Path} = $storage_dir;
  $event->DiskSpace($diskspace);

  $event->delete();
  return map { $_->[0] } @issued;
}

my @sql = run_delete(4096);

is(scalar(@sql), 5, 'one Storage update plus the four deletes');
like($sql[0], qr/^UPDATE Storage /, 'Storage is locked first, before any delete');
like($sql[0], qr/GREATEST/, 'DiskSpace is clamped so it cannot go negative');
is_deeply([$issued[0][1], $issued[0][2]], [-4096, 2],
  'Storage update is parameterised by the negative reclaim and the storage id');

my ($storage_at) = grep { $sql[$_] =~ /^UPDATE Storage / } 0 .. $#sql;
my ($events_at)  = grep { $sql[$_] =~ /DELETE FROM Events / } 0 .. $#sql;
ok(defined($storage_at) && defined($events_at) && $storage_at < $events_at,
  'Storage precedes the Events delete that cascades into Event_Summaries');

is($sql[1], 'DELETE FROM Stats WHERE EventId=?', 'Stats deleted first of the event rows');
is($sql[2], 'DELETE FROM Event_Data WHERE EventId=?', 'then Event_Data');
is($sql[3], 'DELETE FROM Frames WHERE EventId=?', 'then Frames');
is($sql[4], 'DELETE FROM Events WHERE Id=?', 'Events last, so its triggers run after its dependants are gone');

# With nothing to reclaim there is no reason to touch the shared row at all.
my @no_reclaim = run_delete(0);
is(scalar(@no_reclaim), 4, 'no Storage update when the event has no disk usage');
unlike($no_reclaim[0], qr/^UPDATE Storage /, 'first statement is a delete when there is nothing to reclaim');
