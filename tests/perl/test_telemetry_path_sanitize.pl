#!/usr/bin/perl
#
# getMonitorRef in zmtelemetry.pl.in must not send camera credentials held in a
# Path query string, and must not invent a host for a Path that has none.
#
# Run as: perl tests/perl/test_telemetry_path_sanitize.pl
#
use strict;
use warnings;
use FindBin;
use URI;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '<undef>') eq (defined $want ? $want : '<undef>')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  ".(defined $got ? $got : '<undef>')."\n  want: ".(defined $want ? $want : '<undef>')."\n";
  }
}

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmtelemetry.pl.in")
    or die "Can't open zmtelemetry.pl.in: $!";
  local $/;
  <$fh>;
};

my ($block) = $source =~ /(# Sanitize Path\n\s*foreach my \$monitor \(\@\{\$arrayref\}\) \{.*?\n  \})/s;
die 'Could not extract the Path sanitizing loop from zmtelemetry.pl.in' unless $block;

sub sanitize {
  my ($type, $path) = @_;
  my $arrayref = [{Type => $type, Path => $path}];
  my @warnings;
  local $SIG{__WARN__} = sub { push @warnings, $_[0] };
  eval "$block; 1" or die $@;
  return ($arrayref->[0]{Path}, scalar @warnings);
}

my ($p, $w) = sanitize('Ffmpeg', 'rtsp://admin:secret@cam.local:554/stream1');
check('userinfo is replaced', $p, 'rtsp://username:password@host/stream1');

($p, $w) = sanitize('Ffmpeg', 'rtsp://cam.local/live?user=admin&password=secret');
check('query string credentials are not sent', $p, 'rtsp://username:password@host/live');

($p, $w) = sanitize('cURL', 'http://cam.local/snap.cgi?usr=admin&pwd=secret#x');
check('query and fragment are dropped', $p, 'http://username:password@host/snap.cgi');

($p, $w) = sanitize('Local', '/dev/video0?x=1');
check('Local path is left alone', $p, '/dev/video0?x=1');

($p, $w) = sanitize('Ffmpeg', undef);
check('NULL path stays undef', $p, undef);
check('NULL path does not warn', $w, 0);

($p, $w) = sanitize('Ffmpeg', '');
check('empty path stays empty', $p, '');

($p, $w) = sanitize('File', '/var/video/clip.mp4');
check('file path gets no invented host', $p, '/var/video/clip.mp4');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
