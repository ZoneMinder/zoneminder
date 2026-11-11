use strict;
use warnings;
use Test::More;
use File::Temp qw(tempdir);
use File::Path qw(make_path);

eval { require ZoneMinder::Storage; 1 } or plan skip_all => "cannot load ZoneMinder::Storage: $@";
plan tests => 12;

# delete_path removes an event directory (or a single file) from a storage area
# whose path contains a space. The old shell command split on the space and
# deleted the wrong tree. Logging is stubbed: ZoneMinder::Logger writes to the
# Logs table, which would pull in a real connection.
my @errors;
{
  no warnings 'redefine', 'once';
  *ZoneMinder::Storage::Debug = sub { };
  *ZoneMinder::Storage::Info = sub { };
  *ZoneMinder::Storage::Warning = sub { };
  *ZoneMinder::Storage::Error = sub { push @errors, join(' ', @_); };
}

my $tmp = tempdir(CLEANUP => 1);
my $storage_path = "$tmp/cam disk";

make_path("$storage_path/1/2026-11-11/123");
open(my $fh, '>', "$storage_path/1/2026-11-11/123/a.txt") or die $!;
print $fh "a";
close($fh);
open($fh, '>', "$storage_path/1/2026-11-11/123/b.txt") or die $!;
print $fh "b";
close($fh);
open($fh, '>', "$storage_path/lone.txt") or die $!;
print $fh "lone";
close($fh);

make_path("$tmp/cam");
make_path("$tmp/disk");
open($fh, '>', "$tmp/cam/cam.txt") or die $!;
print $fh "cam";
close($fh);
open($fh, '>', "$tmp/disk/disk.txt") or die $!;
print $fh "disk";
close($fh);

my $Storage = bless { Path => $storage_path }, 'ZoneMinder::Storage';

@errors = ();
$Storage->delete_path('1/2026-11-11/123');
ok(!-e "$storage_path/1/2026-11-11/123", 'the event directory is removed');
ok(-e "$storage_path", 'the storage path survives');
ok(-e "$tmp/cam" && -e "$tmp/cam/cam.txt", 'a same-prefix sibling cam is untouched');
ok(-e "$tmp/disk" && -e "$tmp/disk/disk.txt", 'a same-prefix sibling disk is untouched');
is(scalar @errors, 0, 'no error deleting a present directory');

@errors = ();
$Storage->delete_path('lone.txt');
ok(!-e "$storage_path/lone.txt", 'a single file path removes only that file');
ok(-e "$storage_path", 'the storage path still survives after a file delete');

@errors = ();
$Storage->delete_path('');
is(scalar @errors, 1, 'an empty path is refused with an error');
like($errors[0], qr/storage area itself/, 'the refusal names the danger');
ok(-e "$storage_path", 'an empty path deletes nothing');

@errors = ();
$Storage->delete_path('.');
is(scalar @errors, 1, "a '.' path is refused with an error");
ok(-e "$storage_path", "a '.' path deletes nothing");
