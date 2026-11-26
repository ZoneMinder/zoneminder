#!/usr/bin/perl
#
# zmonvif-probe.pl --version is documented as printing the version of this
# script, but with no VERSION_MESSAGE of its own Getopt::Std printed its
# "[unknown] calling Getopt::Std::getopts" banner instead of the ZoneMinder
# version.
#
# Run as: perl tests/perl/test_zmonvif_probe_version.pl
#
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

my $dir = tempdir(CLEANUP => 1);
make_path("$dir/lib/ZoneMinder", "$dir/lib/ONVIF");
write_file("$dir/lib/ONVIF/Client.pm", "package ONVIF::Client;\n1;\n");

write_file("$dir/lib/ZoneMinder/Base.pm", <<'EOM');
package ZoneMinder::Base;
use constant ZM_VERSION => '1.39.99';
1;
EOM

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmonvif-probe.pl.in") or die "Can't open zmonvif-probe.pl.in: $!";
  local $/;
  <$fh>;
};
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
write_file("$dir/zmonvif-probe.pl", $source);

sub run {
  my $out = qx($^X -I$dir/lib $dir/zmonvif-probe.pl @_ 2>&1);
  return ($? >> 8, $out);
}

my ($status, $out) = run('--version');
check('--version prints the ZoneMinder version', $out, "1.39.99\n");
check('--version exits 0', $status, 0);

($status, $out) = run('--help');
check('--help still prints the usage', $out =~ /Usage: .*zmonvif-probe\.pl \[-v\] probe/ ? 'usage' : 'other', 'usage');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
