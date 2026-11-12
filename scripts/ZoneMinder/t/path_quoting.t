use strict;
use warnings;
use Test::More;
use File::Temp qw(tempdir);
use File::Spec;

$ENV{PATH} = '/usr/bin:/bin';
delete @ENV{qw(ENV BASH_ENV CDPATH IFS)};

eval { require ZoneMinder::Filter; 1 } or plan skip_all => "cannot load ZoneMinder::Filter: $@";
plan tests => 5;

{
  no warnings 'redefine', 'once';
  *ZoneMinder::Filter::Debug = sub { };
  *ZoneMinder::Filter::Warning = sub { };
  *ZoneMinder::Filter::Error = sub { };
}

sub on_path {
  my ($name) = @_;
  for my $dir (File::Spec->path()) {
    my $file = File::Spec->catfile($dir, $name);
    return $file if -x $file;
  }
  return;
}

my $space_dir = tempdir("space XXXX", TMPDIR => 1, CLEANUP => 1);
my $quote_dir = tempdir("quote'XXXX", TMPDIR => 1, CLEANUP => 1);

cmp_ok(ZoneMinder::Filter::getDiskPercent($space_dir), '>=', 0, 'getDiskPercent on a path containing a space');
cmp_ok(ZoneMinder::Filter::getDiskBlocks($space_dir), '>=', 0, 'getDiskBlocks on a path containing a space');
cmp_ok(ZoneMinder::Filter::getDiskPercent($quote_dir), '>=', 0, 'getDiskPercent on a path containing a quote');
cmp_ok(ZoneMinder::Filter::getDiskBlocks($quote_dir), '>=', 0, 'getDiskBlocks on a path containing a quote');

SKIP: {
  my $ffmpeg = on_path('ffmpeg');
  my $ffprobe = on_path('ffprobe');
  my $reason = !$ffmpeg || !$ffprobe ? 'ffmpeg/ffprobe not on PATH' : '';
  if (!$reason) {
    eval { require ZoneMinder::Event; 1 } or $reason = "cannot load ZoneMinder::Event: $@";
  }
  skip $reason, 1 if $reason;

  my $file = File::Spec->catfile($quote_dir, 'clip.mp4');
  my $made = system($ffmpeg, '-v', 'error', '-f', 'lavfi',
    '-i', 'testsrc2=duration=1:size=64x64:rate=10', '-pix_fmt', 'yuv420p', $file) == 0;
  skip 'could not create test video', 1 unless $made;
  my $duration = ZoneMinder::Event::mp4_duration($file);
  ok(defined $duration && $duration >= 0.5 && $duration <= 1.5,
    'mp4_duration returns ~1s for a path containing a quote');
}
