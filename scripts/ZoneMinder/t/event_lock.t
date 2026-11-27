use strict;
use warnings;
use Test::More;

eval { require ZoneMinder::Event; 1 } or plan skip_all => "cannot load ZoneMinder::Event: $@";
plan tests => 18;

# Capture what would go to the database instead of talking to one. Logging is
# stubbed too: ZoneMinder::Logger writes to the Logs table, which would need a
# real connection.
my @sql;
my $insert_result = 1;
{
  no warnings 'redefine', 'once';
  *ZoneMinder::Event::zmDbDo = sub {
    push @sql, [@_];
    return $_[0] =~ /^INSERT/ ? $insert_result : 1;
  };
  *ZoneMinder::Event::Debug = sub { };
  *ZoneMinder::Event::Warning = sub { };
  *ZoneMinder::Event::Error = sub { };
}

my $Event = new ZoneMinder::Event();
$$Event{Id} = 42;

sub reset_state { @sql = (); $insert_result = 1; }

# --- Acquiring -------------------------------------------------------------
reset_state();
my $got = $Event->acquire_lock(900);

is(scalar @sql, 2, 'acquiring takes two statements');
like($sql[0][0], qr/^DELETE FROM Events_Lock WHERE EventId=\? AND ExpiresAt<NOW\(\)$/,
  'the expired lock for this event is cleared first');
is_deeply([@{$sql[0]}[1]], [42], 'the reap is scoped to this event');
like($sql[1][0], qr/^INSERT IGNORE INTO Events_Lock /,
  'then the lock is taken with INSERT IGNORE, which cannot block');
is($sql[1][1], 42, 'the event id is bound');
is($sql[1][3], 900, 'the requested expiry is bound');
is($got, 1, 'a row inserted means we hold the lock');

# The whole point is that this never takes a lock on the event itself.
unlike(join(' ', map { $$_[0] } @sql), qr/FOR UPDATE/i,
  'nothing here takes a row lock');
is(scalar(grep { $$_[0] =~ /Events_Lock/ } @sql), 2,
  'both statements are confined to Events_Lock, away from Events and its triggers');

# DBI reports 0E0, not 0, for a statement that affected nothing.
reset_state();
$insert_result = '0E0';
is($Event->acquire_lock(900), 0, 'no row inserted means another filter holds it');

reset_state();
$insert_result = undef;
is($Event->acquire_lock(900), 0, 'a failed insert is treated as not acquired');

# Callers that pass nothing get the configured expiry.
reset_state();
{
  no warnings 'once';
  local $ZoneMinder::Config::Config{ZM_FILTER_LOCK_TIMEOUT} = 120;
  $Event->acquire_lock();
}
is($sql[1][3], 120, 'ZM_FILTER_LOCK_TIMEOUT supplies the default expiry');

reset_state();
{
  no warnings 'once';
  local $ZoneMinder::Config::Config{ZM_FILTER_LOCK_TIMEOUT} = undef;
  $Event->acquire_lock();
}
cmp_ok($sql[1][3], '>', 0, 'an unconfigured expiry still falls back to a positive one');

# --- Ownership -------------------------------------------------------------
my $pid = $$;
like(ZoneMinder::Event::lock_owner(), qr/^\d+\.\Q$pid\E$/,
  'the owner string is server id and pid, so a lock can be traced to a process');

# --- Releasing -------------------------------------------------------------
reset_state();
$Event->release_lock();
is(scalar @sql, 1, 'releasing is a single statement');
like($sql[0][0], qr/^DELETE FROM Events_Lock WHERE EventId=\? AND LockedBy=\?$/,
  'we only release a lock we hold');
is_deeply([@{$sql[0]}[1,2]], [42, ZoneMinder::Event::lock_owner()],
  'bound with this event and this process');

# --- Reaping ---------------------------------------------------------------
reset_state();
ZoneMinder::Event::reap_expired_locks();
is_deeply(\@sql, [['DELETE FROM Events_Lock WHERE ExpiresAt<NOW()']],
  'reaping clears every expired lock, whoever left it');
