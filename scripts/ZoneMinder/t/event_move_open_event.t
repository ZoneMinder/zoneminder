use strict;
use warnings;
use Test::More tests => 5;
use File::Temp qw(tempdir);
use File::Path qw(make_path);

eval { require ZoneMinder::Event; 1 } or BAIL_OUT("cannot load ZoneMinder::Event: $@");
require ZoneMinder::Database;

# An event that has no EndDateTime is still being recorded. MoveTo copies the
# files and then deletes the source directory, so the frames zmc writes after
# that are lost. It must be left alone until the event is closed.
{
  no warnings 'redefine', 'once';
  *ZoneMinder::Event::Debug = sub { };
  *ZoneMinder::Event::Info = sub { };
  *ZoneMinder::Event::Warning = sub { };
  *ZoneMinder::Event::Error = sub { };
  *ZoneMinder::Event::canEdit = sub { 1 };
}

{
  package Test::DBH;
  sub new { return bless { AutoCommit => 1 }, shift }
  sub begin_work { $_[0]{AutoCommit} = 0; 1 }
  sub commit { $_[0]{AutoCommit} = 1; 1 }
  sub rollback { $_[0]{AutoCommit} = 1; 1 }
  sub err { 0 }
  sub errstr { '' }
  sub ping { 1 }
  sub do { 1 }
  sub selectrow_hashref { undef }
  sub selectrow_arrayref { undef }
  # Just enough statement handle for ZoneMinder::Logger's INSERT INTO Logs.
  sub prepare { return Test::STH->new() }
  sub prepare_cached { return Test::STH->new() }
  sub quote { return $_[1] }
}

{
  package Test::STH;
  sub new { return bless {}, shift }
  sub execute { return 1 }
  sub finish { return 1 }
  sub errstr { return '' }
}

{
  package Test::Storage;
  sub new { my ($c, $id, $path) = @_; return bless { Id => $id, Path => $path, Type => 'local', adjusted => [] }, $c }
  sub Id { $_[0]{Id} }
  sub Path { $_[0]{Path} }
  sub Type { $_[0]{Type} }
  sub DoDelete { 1 }
  sub adjust_diskspace { push @{$_[0]{adjusted}}, $_[1]; 1 }
  sub delete_path { File::Path::remove_tree($_[0]{Path}.'/'.$_[1]) }
}

{ no warnings "once"; $ZoneMinder::Database::dbh = Test::DBH->new(); }

my $tmp = tempdir(CLEANUP => 1);
make_path("$tmp/a/1/2026-01-01/7", "$tmp/b");
open(my $fh, '>', "$tmp/a/1/2026-01-01/7/00001-capture.jpg") or die $!;
print $fh 'x';
close($fh);

# MoveTo reloads the event's storage by id, so hand it the fakes.
my %storages = (1 => Test::Storage->new(1, "$tmp/a"), 2 => Test::Storage->new(2, "$tmp/b"));
{
  no warnings qw(redefine once);
  *ZoneMinder::Storage::new = sub { return $storages{$_[1]} };
}

sub move_event {
  my ($end) = @_;
  my ($old, $new) = @storages{1, 2};
  my $event = ZoneMinder::Event->new();
  @$event{qw(Id MonitorId StorageId Scheme StartDateTime EndDateTime Time DiskSpace)} =
    (7, 1, 1, 'Medium', '2026-01-01 00:00:00', $end, 1767225600, 1);
  $$event{Storage} = $old;
  {
    no warnings qw(redefine once);
    local *ZoneMinder::Event::lock_and_load = sub { 1 };
    local *ZoneMinder::Event::save = sub { '' };
    return ($event->MoveTo($new), $event);
  }
}

my ($err, $event) = move_event(undef);
ok($err, 'MoveTo refuses an event that is still being recorded');
ok(-e "$tmp/a/1/2026-01-01/7/00001-capture.jpg", 'source files are left in place');
ok(!-e "$tmp/b/1/2026-01-01/7", 'nothing is copied to the new storage');

($err, $event) = move_event('2026-01-01 00:00:10');
ok(!$err, 'MoveTo moves a closed event') or diag($err);
ok(-e "$tmp/b/1/2026-01-01/7/00001-capture.jpg", 'closed event is copied to the new storage');
