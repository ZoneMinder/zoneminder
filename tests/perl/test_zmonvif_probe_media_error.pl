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

my $dir = tempdir(CLEANUP => 1);
make_path("$dir/lib/ONVIF", "$dir/lib/ZoneMinder", "$dir/lib/XML");

write_file("$dir/lib/ONVIF/Client.pm", <<'EOM');
package ONVIF::Client;
sub new { bless {}, shift }
sub set_credentials {}
sub create_services {}
sub get_endpoint { return; }
1;
EOM

write_file("$dir/lib/ZoneMinder/ONVIF.pm", <<'EOM');
package ZoneMinder::ONVIF;
our $verbose = 0;
1;
EOM

write_file("$dir/lib/XML/LibXML.pm", <<'EOM');
package XML::LibXML;
sub load_xml { bless {}, shift }
sub toString { "" }
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

my ($status, $out) = run('custom_cmd', 'http://127.0.0.1/device', '1.2');
check('missing media endpoint reports error message', $out =~ /No media endpoint for client/ ? 'reported' : 'missing', 'reported');
check('missing media endpoint does not die with return outside subroutine', $out !~ /Can't return outside a subroutine/ ? 'clean' : 'died', 'clean');
check('missing media endpoint exits with code 1', $status, 1);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
