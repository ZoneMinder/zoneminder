#!/usr/bin/perl
#
# uploadArchFile builds the archive name from the monitor name. A monitor name
# can contain "." and "/", so "../x" must not place the archive outside the
# upload folder.
# Set ZMFILTER_IN to another zmfilter.pl.in to test it.
#
# Run as: perl tests/perl/test_zmfilter_upload_name.pl
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

my @created;
package Archive::Tar;
sub create_archive { push @created, $_[1]; return 1; }
sub error { return ''; }
package Net::FTP;
sub new { return undef; }
package FakeMonitor;
sub Name { return $_[0]{Name}; }
package FakeEvent;
sub Monitor { return bless { Name => $_[0]{MonitorName} }, 'FakeMonitor'; }
sub Path { return '/nonexistent'; }
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
my $dir = "$root/up/inner";
%Config = (
  ZM_UPLOAD_HOST => 'example.invalid',
  ZM_UPLOAD_LOC_DIR => $dir,
  ZM_UPLOAD_ARCH_FORMAT => 'tar',
  ZM_UPLOAD_ARCH_COMPRESS => 0,
  ZM_UPLOAD_PROTOCOL => 'ftp',
);

sub archive_for {
  my $name = shift;
  @created = ();
  uploadArchFile({}, bless { Id => 7, MonitorName => $name }, 'FakeEvent');
  return $created[0];
}

my $path = archive_for('Front Door');
check('plain name stays in the upload folder', $path, "$dir/Front Door-7.tar");
$path = archive_for('../escape');
check('"../x" archive is written in the upload folder', defined $path ? dirname($path) : undef, $dir);
check('"../x" archive name uses the last component', defined $path ? basename($path) : undef, 'escape-7.tar');
$path = archive_for('a/b/c');
check('nested name archive is written in the upload folder', defined $path ? dirname($path) : undef, $dir);

print $failures ? "$failures failed\n" : "all passed\n";
exit($failures ? 1 : 0);
