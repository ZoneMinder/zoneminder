BEGIN {
  $ENV{PATH} = '/usr/bin:/bin';
  delete @ENV{qw(ENV BASH_ENV CDPATH IFS)};
}

use strict;
use warnings;
use Test::More;
use File::Temp qw(tempdir);

eval { require ZoneMinder::Event; 1 } or plan skip_all => "cannot load ZoneMinder::Event: $@";
plan tests => 8;

{
  no warnings 'redefine', 'once';
  *ZoneMinder::Event::Path = sub { $_[0]{test_path} };
  *ZoneMinder::Event::Debug = sub { };
  *ZoneMinder::Event::Warning = sub { };
}

sub check_in {
  my ($path) = @_;
  my $Event = bless { Id => 1, test_path => $path }, 'ZoneMinder::Event';
  return $Event->check_for_in_filesystem();
}

my @roots = (
  tempdir(TMPDIR => 1, CLEANUP => 1),
  tempdir('zm space XXXXXX', TMPDIR => 1, CLEANUP => 1),
);

for my $root (@roots) {
  my $empty = "$root/empty";
  mkdir $empty;

  my $hidden = "$root/hidden";
  mkdir $hidden;
  open my $fh, '>', "$hidden/.hidden" or die "cannot write .hidden: $!";
  close $fh;

  my $full = "$root/full";
  mkdir $full;
  open $fh, '>', "$full/00001-capture.jpg" or die "cannot write capture: $!";
  close $fh;

  ok(!check_in($empty), 'empty event dir has no files');
  ok(!check_in($hidden), 'dir holding only .hidden has no files');
  ok(check_in($full), 'dir holding 00001-capture.jpg has a file');
  ok(!check_in("$root/does-not-exist"), 'missing path has no files');
}
