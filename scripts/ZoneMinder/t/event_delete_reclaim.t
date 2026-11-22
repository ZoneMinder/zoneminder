use strict;
use warnings;
use Test::More;
use File::Temp qw(tempdir);

eval { require ZoneMinder::Event; 1 } or plan skip_all => "cannot load ZoneMinder::Event: $@";
require ZoneMinder::Database;

# Event::delete() owns the transaction when AutoCommit is on: the four event
# deletes and the Storage DiskSpace reclaim commit together, so a database
# error from the reclaim (or a deadlock on any statement) rolls the whole lot
# back. When the caller owns the transaction delete() must not begin, commit or
# rollback, and it reclaims once after the deletes.
#
# Nothing here needs a real database: the fake dbh records statements and
# transaction calls in order, and the fake storage records its reclaim calls.

my $storage_dir = tempdir(CLEANUP => 1);

my @calls;

{
  package Test::Storage;
  sub new {
    my ($class, $id, $path) = @_;
    return bless { Id => $id, Path => $path, DiskSpace => 1_000_000 }, $class;
  }
  sub Id { return $_[0]{Id} }
  sub Path { return $_[0]{Path} }
  sub DiskSpace { return $_[0]{DiskSpace} }
  sub DoDelete { return 0 }  # keep delete() out of the filesystem

  # Records the reclaim and, when asked, leaves a db error on the handle so
  # delete() treats the reclaim like any other statement in its transaction.
  sub adjust_diskspace {
    my ($self, $delta) = @_;
    push @calls, ['adjust_diskspace', $delta];
    $$self{reclaims}++;
    my $dbh = $ZoneMinder::Database::dbh;
    if ($$self{db_error}) {
      $dbh->{err} = $$self{db_error};
      $dbh->{errstr} = 'mock adjust_diskspace failure';
    } else {
      $dbh->{err} = 0;
      $dbh->{errstr} = '';
    }
    return 1;
  }
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
  # DBI's begin_work turns AutoCommit off for the duration of the transaction
  # and commit/rollback turn it back on. That is what makes zmDbDo stop doing
  # its own retries once delete() owns the transaction.
  sub new {
    my ($class, %args) = @_;
    return bless {
      AutoCommit => exists $args{AutoCommit} ? $args{AutoCommit} : 1,
      err => 0,
      errstr => '',
      fail_sql => $args{fail_sql},
      fail_err => $args{fail_err},
      fail_times => $args{fail_times},
    }, $class;
  }
  sub ping { return 1 }
  sub err { return $_[0]{err} }
  sub errstr { return $_[0]{err} ? ($_[0]{errstr} || 'mock failure') : '' }
  sub _ok {
    my $self = shift;
    $self->{err} = 0;
    $self->{errstr} = '';
  }
  sub do {
    my ($self, $sql) = @_;
    push @calls, ['do', $sql];
    $self->_ok;

    my $fail = 0;
    if (defined $self->{fail_sql} and $sql =~ $self->{fail_sql}) {
      if (!defined $self->{fail_times}) {
        $fail = 1;
      } elsif ($self->{fail_times} > 0) {
        $self->{fail_times}--;
        $fail = 1;
      }
    }
    if ($fail) {
      $self->{err} = $self->{fail_err} || 1205;
      $self->{errstr} = 'mock statement failure';
      return;
    }
    return 1;
  }
  sub begin_work {
    my $self = shift;
    push @calls, ['begin_work'];
    $self->{AutoCommit} = 0;
    $self->_ok;
    return 1;
  }
  sub commit {
    my $self = shift;
    push @calls, ['commit'];
    $self->{AutoCommit} = 1;
    $self->_ok;
    return 1;
  }
  sub rollback {
    my $self = shift;
    push @calls, ['rollback'];
    $self->{AutoCommit} = 1;
    $self->_ok;
    return 1;
  }
  sub prepare { return Test::STH->new() }
  sub prepare_cached { return Test::STH->new() }
  sub selectrow_hashref { return undef }
  sub quote { return $_[1] }
}

my @delete_sql = (
  'DELETE FROM Stats WHERE EventId=?',
  'DELETE FROM Event_Data WHERE EventId=?',
  'DELETE FROM Frames WHERE EventId=?',
  'DELETE FROM Events WHERE Id=?',
);

sub run_delete {
  my (%opts) = @_;
  @calls = ();

  my $dbh = Test::DBH->new(
    AutoCommit => exists $opts{AutoCommit} ? $opts{AutoCommit} : 1,
    fail_sql => $opts{fail_sql},
    fail_err => $opts{fail_err},
    fail_times => $opts{fail_times},
  );
  $ZoneMinder::Database::dbh = $dbh;

  my $event = new ZoneMinder::Event();
  $$event{Id} = 42;
  $$event{MonitorId} = 8;
  $$event{StartDateTime} = '2026-01-01 00:00:00';

  my $storage = Test::Storage->new(2, $storage_dir);
  $$storage{db_error} = $opts{adjust_error} if $opts{adjust_error};
  $$event{Storage} = $storage;
  $$event{StorageId} = 2;
  $$event{Path} = $storage_dir;

  $event->DiskSpace(exists $opts{DiskSpace} ? $opts{DiskSpace} : 4096);

  my $ret = $event->delete();
  return ($ret, [@calls], $storage);
}

sub delete_sqls {
  my ($calls) = @_;
  return map { $_->[1] } grep { $_->[0] eq 'do' and $_->[1] =~ /^DELETE / } @$calls;
}

sub adjust_deltas {
  my ($calls) = @_;
  return map { $_->[1] } grep { $_->[0] eq 'adjust_diskspace' } @$calls;
}

sub call_count {
  my ($calls, $type) = @_;
  return scalar grep { $_->[0] eq $type } @$calls;
}

# The deletes, reclaims, commits and rollbacks, in issue order. Transaction
# plumbing (SET TRANSACTION, begin_work) is checked through call_count().
sub reclaim_sequence {
  my ($calls) = @_;
  my @sequence;
  foreach my $call (@$calls) {
    my ($kind, $arg) = @$call;
    if ($kind eq 'do') {
      push @sequence, 'delete' if $arg =~ /^DELETE /;
    } elsif ($kind ne 'begin_work') {
      push @sequence, $kind;
    }
  }
  return @sequence;
}

# 1. Owned, success: deletes, reclaim, commit in that order.
my ($ret, $calls, $storage) = run_delete(DiskSpace => 4096);
is_deeply([delete_sqls($calls)], \@delete_sql,
  'owned success: the four event deletes run in dependency order');
is_deeply([reclaim_sequence($calls)],
  [('delete') x 4, 'adjust_diskspace', 'commit'],
  'owned success: the reclaim happens after the deletes and before the commit');
is(call_count($calls, 'adjust_diskspace'), 1, 'owned success: reclaims exactly once');
is_deeply([adjust_deltas($calls)], [-4096],
  'owned success: reclaim is the negative of the event disk space');
is(call_count($calls, 'begin_work'), 1, 'owned success: delete() begins its own transaction');

# 2. Owned, a DELETE fails with a non-deadlock error.
($ret, $calls, $storage) = run_delete(
  DiskSpace => 4096,
  fail_sql => qr/^DELETE FROM Stats /,
  fail_err => 1205,  # lock wait timeout, not a deadlock
);
ok(!defined $ret, 'owned failure: delete() returns failure');
is_deeply([reclaim_sequence($calls)], ['delete', 'rollback'],
  'owned failure: rolls back after the failed delete');
is(call_count($calls, 'adjust_diskspace'), 0,
  'owned failure: the reclaim is never attempted after a failed delete');
is(call_count($calls, 'commit'), 0, 'owned failure: never commits');

# 3. Owned, deadlock 1213 on the first attempt then success.
($ret, $calls, $storage) = run_delete(
  DiskSpace => 4096,
  fail_sql => qr/^DELETE FROM Stats /,
  fail_err => 1213,  # ER_LOCK_DEADLOCK
  fail_times => 1,
);
is_deeply([reclaim_sequence($calls)],
  ['delete', 'rollback', ('delete') x 4, 'adjust_diskspace', 'commit'],
  'owned deadlock: rollback, retry, reclaim, then commit');
is(call_count($calls, 'rollback'), 1, 'owned deadlock: rolls back the first attempt once');
is(call_count($calls, 'begin_work'), 2, 'owned deadlock: retries in a fresh transaction');
is(call_count($calls, 'adjust_diskspace'), 1,
  'owned deadlock: reclaim is applied only once in total');
is(call_count($calls, 'commit'), 1, 'owned deadlock: commits the successful retry');

# 4. Owned, adjust_diskspace sets a db error.
($ret, $calls, $storage) = run_delete(
  DiskSpace => 4096,
  adjust_error => 1205,  # non-deadlock failure from the reclaim statement
);
ok(!defined $ret, 'owned reclaim failure: delete() returns failure');
is_deeply([reclaim_sequence($calls)],
  [('delete') x 4, 'adjust_diskspace', 'rollback'],
  'owned reclaim failure: the reclaim error rolls back the deletes');
is(call_count($calls, 'commit'), 0, 'owned reclaim failure: does not commit');
is(call_count($calls, 'rollback'), 1, 'owned reclaim failure: rolls back once');

# 5. Caller-owned: delete() must leave transaction control with the caller.
($ret, $calls, $storage) = run_delete(DiskSpace => 4096, AutoCommit => 0);
is_deeply([reclaim_sequence($calls)], [('delete') x 4, 'adjust_diskspace'],
  'caller-owned: deletes then a single reclaim');
is(call_count($calls, 'adjust_diskspace'), 1, 'caller-owned: reclaims exactly once');
is(call_count($calls, 'begin_work'), 0, 'caller-owned: delete() does not begin a transaction');
is(call_count($calls, 'commit'), 0, 'caller-owned: delete() does not commit');
is(call_count($calls, 'rollback'), 0, 'caller-owned: delete() does not roll back');

# 6. Nothing to reclaim.
($ret, $calls, $storage) = run_delete(DiskSpace => 0);
is_deeply([reclaim_sequence($calls)], [('delete') x 4, 'commit'],
  'zero DiskSpace: deletes and commits without a reclaim');
is(call_count($calls, 'adjust_diskspace'), 0,
  'zero DiskSpace: never calls adjust_diskspace');

done_testing();
