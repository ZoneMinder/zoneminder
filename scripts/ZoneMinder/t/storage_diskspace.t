use strict;
use warnings;
use Test::More;

eval { require ZoneMinder::Storage; 1 } or plan skip_all => "cannot load ZoneMinder::Storage: $@";
plan tests => 12;

# Capture what would go to the database instead of talking to one. Logging is
# stubbed out as well: ZoneMinder::Logger writes to the Logs table, which would
# pull in a real connection.
my @sql;
my @fetched;
my $stored = 0;
{
  no warnings 'redefine', 'once';
  *ZoneMinder::Database::zmDbDo = sub { push @sql, [@_]; return 1; };
  *ZoneMinder::Database::zmDbFetchOne = sub {
    push @fetched, [@_];
    return {DiskSpace => $stored};
  };
  *ZoneMinder::Storage::Debug = sub { };
  *ZoneMinder::Storage::Warning = sub { };
}

sub reset_state { @sql = (); @fetched = (); }

my $Storage = bless {Id => 4, DiskSpace => 3000}, 'ZoneMinder::Storage';

# --- One relative statement, no read-modify-write ---------------------------
reset_state();
$Storage->adjust_diskspace(-1000);
is(scalar @sql, 1, 'an adjustment is a single statement');
like($sql[0][0], qr/^UPDATE Storage SET DiskSpace=/, 'it is an UPDATE of Storage.DiskSpace');
unlike($sql[0][0], qr/FOR UPDATE/i,
  'no SELECT ... FOR UPDATE: the row lock must not outlive the statement');
like($sql[0][0], qr/GREATEST\(COALESCE\(DiskSpace,0\)\+\?,0\)/,
  'the new value is computed relative to the stored one, so no lost update');
is_deeply([@{$sql[0]}[1,2]], [-1000, 4], 'bound with the signed delta and the storage id');

# --- Refreshing the object -------------------------------------------------
# ZoneMinder::Object caches objects for the life of the process and its
# accessors never re-load, so a cached DiskSpace can be arbitrarily stale.
# Adding the delta to it locally would preserve the error; read the new total.
reset_state();
$stored = 7500;
$Storage = bless {Id => 4, DiskSpace => 3000}, 'ZoneMinder::Storage';
$Storage->adjust_diskspace(-1000);
is(scalar @fetched, 1, 'the new total is read back');
like($fetched[0][0], qr/^SELECT DiskSpace FROM Storage WHERE Id=\?$/, 'by primary key');
is($fetched[0][1], 4, 'for the storage area we just adjusted');
is($$Storage{DiskSpace}, 7500,
  'the object takes the stored value, not the stale cached value plus the delta');

# --- No-ops ----------------------------------------------------------------
reset_state();
$Storage->adjust_diskspace(0);
is(scalar @sql + scalar @fetched, 0, 'a zero delta touches nothing');

# Event::Storage() hands back a blank object when the event has no StorageId.
(bless {}, 'ZoneMinder::Storage')->adjust_diskspace(-1000);
is(scalar @sql + scalar @fetched, 0, 'a storage area with no Id touches nothing');

# Deleting an event adjusts Storage on its own, outside any transaction the
# caller might want. Nothing here may open one.
reset_state();
$Storage->adjust_diskspace(-1000);
unlike(join(' ', map { $$_[0] } (@sql, @fetched)), qr/\b(BEGIN|START TRANSACTION|COMMIT)\b/i,
  'no transaction is opened around the adjustment');
