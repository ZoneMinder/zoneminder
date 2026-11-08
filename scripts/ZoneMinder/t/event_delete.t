use strict;
use warnings;
use Test::More;
use File::Temp qw(tempdir);

eval { require ZoneMinder::Event; 1 } or plan skip_all => "cannot load ZoneMinder::Event: $@";
plan tests => 5;

# Event::delete removes the event's rows and then its files. ZM_OPT_FAST_DELETE
# used to skip the files outside zmaudit, which only runs when ZM_RUN_AUDIT is
# on. The option is gone, so a value left in %Config must not change anything.
#
# The database and the event's storage area are stubbed; the rows and files
# that would be deleted are recorded instead.

my @sql;
my $files_deleted;
my $storage_path = tempdir(CLEANUP => 1);
my $do_delete = 1;

{
  package TestDbh;
  sub new { return bless { AutoCommit => 1 }, shift; }
  sub ping { 1 }
  sub do { 1 }
  sub begin_work { $_[0]{AutoCommit} = 0; 1 }
  sub commit { $_[0]{AutoCommit} = 1; 1 }
  sub rollback { $_[0]{AutoCommit} = 1; 1 }
  sub err { 0 }
  sub errstr { '' }

  package TestStorage;
  sub new { return bless {}, shift; }
  sub Path { $storage_path }
  sub DoDelete { $do_delete }
  sub adjust_diskspace { 1 }
}

{
  no warnings 'redefine', 'once';
  $ZoneMinder::Database::dbh = TestDbh->new();
  *ZoneMinder::Database::zmDbDo = sub { push @sql, [@_]; return 1; };
  *ZoneMinder::Event::canEdit = sub { 1 };
  *ZoneMinder::Event::Storage = sub { TestStorage->new() };
  *ZoneMinder::Event::Path = sub { "$storage_path/3/7" };
  *ZoneMinder::Event::DiskSpace = sub { 0 };
  *ZoneMinder::Event::delete_files = sub { $files_deleted = 1; };
  *ZoneMinder::Event::Info = sub { };
  *ZoneMinder::Event::Debug = sub { };
  *ZoneMinder::Event::Warning = sub { };
  *ZoneMinder::Event::Error = sub { };
}

sub delete_event {
  @sql = ();
  $files_deleted = 0;
  my $Event = new ZoneMinder::Event();
  @$Event{qw(Id MonitorId StartDateTime)} = (7, 3, '2026-09-01 10:00:00');
  $Event->delete();
}

{
  no warnings 'once';
  local $ZoneMinder::Config::Config{ZM_OPT_FAST_DELETE} = 1;
  delete_event();
}
ok((grep { $$_[0] eq 'DELETE FROM Frames WHERE EventId=?' and $$_[1] == 7 } @sql),
  'the event\'s Frames rows are deleted');
ok((grep { $$_[0] eq 'DELETE FROM Events WHERE Id=?' and $$_[1] == 7 } @sql),
  'the Events row is deleted');
ok($files_deleted, 'the event\'s files are deleted even with a leftover ZM_OPT_FAST_DELETE');

delete_event();
ok($files_deleted, 'the event\'s files are deleted');

$do_delete = 0;
delete_event();
ok(!$files_deleted, 'a storage area with DoDelete off keeps the files');
