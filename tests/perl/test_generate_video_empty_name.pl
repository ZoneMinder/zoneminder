#!/usr/bin/env perl
use strict;
use warnings;

use FindBin;
use File::Temp qw(tempdir);
use File::Basename qw(basename);

my $root = (defined $ENV{ZM_TEST_ROOT} && length $ENV{ZM_TEST_ROOT})
  ? $ENV{ZM_TEST_ROOT}
  : "$FindBin::Bin/../..";

my $pm_file = "$root/scripts/ZoneMinder/lib/ZoneMinder/Event.pm";

open my $pm_fh, '<', $pm_file or die "Cannot open $pm_file: $!\n";
my $pm_source = do {
  local $/;
  <$pm_fh>;
};
close $pm_fh;

my ($sub_text) = $pm_source =~ /^(sub GenerateVideo \{.*?^\} # end sub GenerateVideo)$/ms;
die "Could not find sub GenerateVideo in $pm_file\n" unless defined $sub_text;

package ZoneMinder::Event;

our %Config;
our $TEST_DIR;

sub Debug { }
sub Info { }
sub Warning { }
sub Error { }
sub Path { return $TEST_DIR }
sub DiskSpace { return undef }

package main;

my $eval_error;
{
  local $@;
  eval "package ZoneMinder::Event;\n$sub_text\n1;";
  $eval_error = $@;
}
die "Failed to compile GenerateVideo: $eval_error" if $eval_error;

my $passed = 0;
my $failed = 0;

sub check {
  my ($condition, $label) = @_;
  if ($condition) {
    print "ok - $label\n";
    $passed++;
  } else {
    print "FAIL - $label\n";
    $failed++;
  }
}

sub run_case {
  my ($name) = @_;

  my $dir = tempdir(CLEANUP => 1);
  my $args_file = "$dir/args";
  my $script_file = "$dir/ffmpeg";

  my $script = "#!/bin/sh\n"
    . "echo \"\$@\" > '$args_file'\n"
    . "for last; do :; done\n"
    . ": > \"\$last\"\n";

  open my $script_fh, '>', $script_file
    or die "Cannot write $script_file: $!\n";
  print $script_fh $script;
  close $script_fh;
  chmod 0755, $script_file or die "Cannot chmod $script_file: $!\n";

  $ZoneMinder::Event::TEST_DIR = $dir;
  %ZoneMinder::Event::Config = (
    ZM_PATH_FFMPEG => $script_file,
    ZM_EVENT_IMAGE_DIGITS => 5,
    ZM_FFMPEG_INPUT_OPTIONS => '',
    ZM_FFMPEG_OUTPUT_OPTIONS => '',
  );

  my $obj = bless {
    Id => 7,
    Name => $name,
    Width => 160,
    Height => 120,
    Frames => 30,
    FullLength => 3,
    DefaultVideo => '7-video.mp4',
  }, 'ZoneMinder::Event';

  my $returned = $obj->GenerateVideo(1, 0, 1, 0, 1, 'mp4', '');

  my $recorded = '';
  if (-e $args_file) {
    open my $args_fh, '<', $args_file
      or die "Cannot read $args_file: $!\n";
    $recorded = do {
      local $/;
      <$args_fh>;
    };
    close $args_fh;
  }
  $recorded = '' unless defined $recorded;

  my @argv = split /\s+/, $recorded;
  my $last_arg = @argv ? $argv[-1] : '';
  my $label = length $name ? $name : '(empty name)';

  check(
    $last_arg ne '' && $last_arg !~ /^-/,
    "last ffmpeg argument does not start with '-' for $label [got '$last_arg']"
  );

  my $returned_base = (defined $returned && length $returned)
    ? basename($returned)
    : '';
  check(
    $returned_base ne '' && $returned_base !~ /^-/,
    "returned basename does not start with '-' for $label [got '$returned_base']"
  );
}

run_case('');
run_case('Front door');

print "\n$passed passed, $failed failed\n";
exit 1 if $failed;
exit 0;
