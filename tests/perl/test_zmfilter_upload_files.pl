#!/usr/bin/perl
#
# uploadArchFile archives the event's images and video. A storage area whose
# path has a space in it must still put the event's files in the archive.
# Set ZMFILTER_IN to another zmfilter.pl.in to test it.
#
# Run as: perl tests/perl/test_zmfilter_upload_files.pl
#
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);
use File::Basename;

my $failures = 0;
sub check {
  my ($name, $got, $want) = @_;
  if (defined($got) && $got eq $want) { print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name (got ".(defined $got ? $got : 'undef').", want $want)\n"; }
}

my @archived;
package Archive::Tar;
sub create_archive { my ($class, $file, $compress, @files) = @_; @archived = @files; return 1; }
sub error { return ''; }
package Net::FTP;
sub new { return undef; }
package FakeMonitor;
sub Name { return 'Front'; }
package FakeEvent;
sub Monitor { return bless {}, 'FakeMonitor'; }
sub Path { return $_[0]{Path}; }
package main;

our %Config;
sub Debug {} sub Info {} sub Warning {} sub Error {}
sub zmDbExecute {}

my $file = $ENV{ZMFILTER_IN} || "$FindBin::Bin/../../scripts/zmfilter.pl.in";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);
my ($sub) = $src =~ /^(sub uploadArchFile \{.*?^\}[^\n]*\n)/ms or die "uploadArchFile not found\n";
eval "$sub; 1" or die "Can't compile: $@";

my $root = tempdir(CLEANUP => 1);
%Config = (
  ZM_UPLOAD_HOST => 'example.invalid',
  ZM_UPLOAD_LOC_DIR => "$root/upload",
  ZM_UPLOAD_ARCH_FORMAT => 'tar',
  ZM_UPLOAD_ARCH_COMPRESS => 0,
  ZM_UPLOAD_PROTOCOL => 'ftp',
);

sub archived_files {
  my $storage = shift;
  my $path = "$root/$storage/1/2026-11-23/7";
  make_path($path);
  foreach my $name (qw(00001-capture.jpg 00002-capture.jpg snapshot.jpg 7-video.mp4)) {
    open(my $out, '>', "$path/$name") or die "Can't create $path/$name: $!";
    close($out);
  }
  @archived = ();
  uploadArchFile({}, bless({ Id => 7, Path => $path }, 'FakeEvent'));
  return join(',', sort map { basename($_) } grep { -f $_ } @archived).' of '.scalar(@archived);
}

my $all = '00001-capture.jpg,00002-capture.jpg,7-video.mp4,snapshot.jpg of 4';
check('storage path without a space: every file is archived', archived_files('events'), $all);
check('storage path with a space: every file is archived', archived_files('my events'), $all);

print $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
