use strict;
use warnings;

BEGIN {
  $ENV{PATH} = '/usr/bin:/bin';
  delete @ENV{qw(ENV BASH_ENV CDPATH IFS)};
}

use Test::More;
use File::Temp qw(tempdir);
use File::Path qw(make_path);

eval { require ZoneMinder::Event; 1 } or plan skip_all => "cannot load ZoneMinder::Event: $@";
plan tests => 6;

{
  no warnings 'redefine', 'once';
  *ZoneMinder::Event::canEdit = sub { 1 };
  *ZoneMinder::Event::Debug = sub { };
  *ZoneMinder::Event::Error = sub { };
  *ZoneMinder::Event::Warning = sub { };
  *ZoneMinder::Event::Info = sub { };
  *ZoneMinder::Storage::Debug = sub { };
  *ZoneMinder::Storage::Error = sub { };
  *ZoneMinder::Storage::Warning = sub { };
  *ZoneMinder::Storage::Info = sub { };
}

sub make_event {
  return bless {
    Id => 555,
    MonitorId => 7,
    StorageId => 77,
    Scheme => 'Medium',
    StartDateTime => '2026-11-12 01:00:00',
  }, 'ZoneMinder::Event';
}

sub setup_files {
  my ($root) = @_;
  my $dir = "$root/7/2026-11-12/555";
  make_path($dir);
  for my $name (qw(00001-analyse.jpg 00002-analyse.jpg 00001-capture.jpg 00001-analyse.jpg.bak)) {
    open my $fh, '>', "$dir/$name" or die "cannot create $dir/$name: $!";
    close $fh;
  }
  return $dir;
}

{
  my $root = tempdir(TMPDIR => 1, CLEANUP => 1);
  my $dir = setup_files($root);
  my $event = make_event();
  is($event->RelativePath(), '7/2026-11-12/555', 'RelativePath gives 7/2026-11-12/555');

  my $storage = bless { Id => 77, Path => $root, Type => 'local' }, 'ZoneMinder::Storage';
  $event->delete_analysis_jpegs($storage);

  ok(!-e "$dir/00001-analyse.jpg", '00001-analyse.jpg deleted');
  ok(!-e "$dir/00002-analyse.jpg", '00002-analyse.jpg deleted');
  ok(-e "$dir/00001-capture.jpg", '00001-capture.jpg kept');
  ok(-e "$dir/00001-analyse.jpg.bak", '00001-analyse.jpg.bak kept');
}

{
  my @deleted_paths;
  {
    no warnings 'redefine', 'once';
    *ZoneMinder::Storage::delete_path = sub {
      my ($self, $path) = @_;
      push @deleted_paths, $path;
    };
  }

  my $root = tempdir("zm space XXXXXX", TMPDIR => 1, CLEANUP => 1);
  setup_files($root);
  my $event = make_event();
  my $storage = bless { Id => 77, Path => $root, Type => 'local' }, 'ZoneMinder::Storage';
  $event->delete_analysis_jpegs($storage);

  is_deeply(
    [sort @deleted_paths],
    ['7/2026-11-12/555/00001-analyse.jpg', '7/2026-11-12/555/00002-analyse.jpg'],
    'space root: delete_path called with bare file names',
  );
}
