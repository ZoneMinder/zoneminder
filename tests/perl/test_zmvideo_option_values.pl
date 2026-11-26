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

my $scratch = '/tmp/numes-tests';
make_path($scratch) unless -d $scratch;
my $dir = tempdir(DIR => $scratch, CLEANUP => 1);
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

my $source_path = $ENV{ZMVIDEO_SRC} // "$FindBin::Bin/../../scripts/zmvideo.pl.in";
my $source = do {
  open(my $fh, '<', $source_path) or die "Can't open $source_path: $!";
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

# GenerateVideo args order: rate, fps, scale, size, overwrite, format, transforms.
sub gen_arg {
  my ($index) = @_;
  my $line = read_file("$dir/generate.args");
  return 'none' unless length $line;
  chomp $line;
  my @args = split(/\|/, $line, -1);
  return $args[$index] // 'none';
}

# Concat is only reachable through a filter; stub the filter lookup.
write_file("$dir/lib/ZoneMinder/Filter.pm", <<'EOM');
package ZoneMinder::Filter;
sub find_one { return bless {}, shift; }
sub Execute { return ({Id => 5}, {Id => 6}); }
sub Name { return 'allmotion'; }
1;
EOM

my ($status, $out);

($status, $out) = run('-e', '5', '-r', '.5');
check('-r .5 normalises to 0.5 instead of being dropped',
  gen_arg(0), '0.5');

($status, $out) = run('-e', '5', '-s', '.5');
check('-s .5 normalises to 0.5 instead of being dropped',
  gen_arg(2), '0.5');

($status, $out) = run('-e', '5', '--fps=1e1');
check('--fps=1e1 normalises to 10 instead of being dropped',
  gen_arg(1), '10');

($status, $out) = run('-e', '5', '-t', 'scale=320:240');
check('-t scale=320:240 exits successfully',
  $status == 0 ? 'success' : "status $status", 'success');
check('-t scale=320:240 keeps the transform',
  gen_arg(6), 'scale=320:240');

($status, $out) = run('-e', '5', '-t', 'a/b');
check('-t a/b is still rejected',
  $status != 0 ? 'rejected' : 'accepted', 'rejected');

($status, $out) = run('--filter_name=x', "--concat='my video'");
check('--concat=my video replaces spaces with underscores',
  -e "$dir/work/my_video.mp4" ? 'my_video'
    : -e "$dir/work/my.mp4" ? 'my' : 'none',
  'my_video');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
