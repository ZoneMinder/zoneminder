#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

sub write_file {
  my ($path, $content) = @_;
  open(my $fh, '>', $path) or die "Can't write $path: $!";
  print $fh $content;
  close($fh);
}

sub read_file {
  my ($path) = @_;
  open(my $fh, '<', $path) or return '';
  local $/;
  my $c = <$fh>;
  close($fh);
  return $c;
}

my $dir = tempdir(CLEANUP => 1);
make_path("$dir/lib/ZoneMinder", "$dir/tmp", "$dir/logs", "$dir/work");

# Fake ffmpeg: records its arguments and creates the last argument (the output).
write_file("$dir/ffmpeg", "#!/bin/sh\nprintf '%s ' \"\$@\" >> '$dir/ffmpeg.args'\necho >> '$dir/ffmpeg.args'\nfor last; do :; done\n: > \"\$last\"\n");
chmod(0755, "$dir/ffmpeg");

write_file("$dir/lib/ZoneMinder.pm", <<'EOM');
package ZoneMinder;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = sub { 1 } for qw(logInit Debug Info Warning);
  *{$caller.'::Error'} = sub { print STDERR "Error: @_\n"; };
  *{$caller.'::Fatal'} = sub { print STDERR "Fatal: @_\n"; exit(1); };
  *{$caller.'::zmDbConnect'} = sub { bless {}, 'StubDbh'; };
  *{$caller.'::Config'} = \%ZoneMinder::Config;
}
sub untaint { my ($v) = $_[0] =~ /^(.*)$/; return $v; }
%ZoneMinder::Config = (
  ZM_OPT_FFMPEG => 1,
  ZM_FFMPEG_FORMATS => untaint($ENV{TEST_FORMATS}),
  ZM_PATH_FFMPEG => untaint($ENV{TEST_FFMPEG}),
  ZM_PATH_LOGS => untaint($ENV{TEST_LOGS}),
  ZM_FFMPEG_OUTPUT_OPTIONS => '-r 25',
);
package ZoneMinder::Base;
use constant ZM_VERSION => '0';
package StubDbh;
sub prepare_cached { bless {}, 'StubSth'; }
package StubSth;
sub execute { my ($s, $id) = @_; $s->{id} = $id; 1; }
sub fetchrow_hashref { my $s = shift; return $s->{id} == 999 ? undef : { Id => $s->{id} }; }
1;
EOM
write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");
write_file("$dir/lib/ZoneMinder/Filter.pm", "package ZoneMinder::Filter;\n1;\n");
write_file("$dir/lib/ZoneMinder/Event.pm", <<'EOM');
package ZoneMinder::Event;
sub new { my ($c, $id, $row) = @_; return bless { Id => $id }, $c; }
sub GenerateVideo {
  my ($self, @args) = @_;
  my ($d) = $ENV{TEST_DIR} =~ m{^(.*)$};
  open(my $fh, '>>', "$d/generate.args") or die $!;
  print $fh join('|', map { defined $_ ? $_ : 'undef' } @args), "\n";
  close($fh);
  return if !$self->{Id} or $self->{Id} == 998;
  my ($file) = "$d/ev$self->{Id}.$args[5]" =~ m{^(.*)$};
  open(my $v, '>', $file) or die $!;
  close($v);
  return $file;
}
1;
EOM

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmvideo.pl.in") or die "Can't open zmvideo.pl.in: $!";
  local $/;
  <$fh>;
};
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
$source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
$source =~ s/\@ZM_TMPDIR\@/$dir\/tmp/g;
write_file("$dir/zmvideo.pl", $source);

sub run {
  unlink("$dir/generate.args", "$dir/ffmpeg.args");
  local $ENV{TEST_DIR} = $dir;
  local $ENV{TEST_FFMPEG} = "$dir/ffmpeg";
  local $ENV{TEST_LOGS} = "$dir/logs";
  local $ENV{TEST_FORMATS} = $ENV{TEST_FORMATS_OVERRIDE} // 'mp4* mpg avi 3gp**';
  my $out = qx(cd $dir/work && $^X -T -I$dir/lib $dir/zmvideo.pl @_ 2>&1 </dev/null);
  return ($? >> 8, $out);
}

my ($status, $out);

($status, $out) = run('-e', '5');
check('default format is the starred one in ZM_FFMPEG_FORMATS',
  (split /\|/, read_file("$dir/generate.args"))[5] // 'none', 'mp4');

($status, $out) = run('-e', '5', '--format=avi');
check('--format overrides the configured default',
  (split /\|/, read_file("$dir/generate.args"))[5] // 'none', 'avi');

($status, $out) = run('-e', '5', '--transforms=scale=640:360');
check('a transform that is not accepted is an error, not silently dropped',
  ($status != 0 || read_file("$dir/generate.args") =~ /scale=640:360/) ? 'reported' : 'dropped', 'reported');

($status, $out) = run('-e', '5', '--size=640*480');
check('a size that is not accepted is an error, not silently dropped',
  ($status != 0 || read_file("$dir/generate.args") =~ /640/) ? 'reported' : 'dropped', 'reported');

($status, $out) = run('-e', '5', '--fps=-5');
check('negative fps is rejected', $status != 0 ? 'rejected' : 'accepted', 'rejected');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
