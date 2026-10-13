#!/usr/bin/perl
#
# ZoneMinder's Perl code must load without JSON.pm. The Debian and Ubuntu packages depend on
# libjson-maybexs-perl, not libjson-perl, so a module that says `use JSON` fails to load on a
# packaged install ("Can't locate JSON.pm in @INC"): the Reolink_HTTP and Uniview controls did,
# and ZoneMinder::General::jsonLoad (MAC vendor names for the network scan) required it too.
#
# This test hides JSON.pm from @INC, then loads those modules and runs jsonLoad.
#
# Run as: sudo -u www-data perl tests/perl/test_json_maybexs.pl
#
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempfile);
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

# Hide JSON.pm, as on a host without libjson-perl.
BEGIN {
  delete $INC{'JSON.pm'};
  unshift @INC, sub {
    my (undef, $file) = @_;
    die "Can't locate JSON.pm in \@INC (hidden by this test)\n" if $file eq 'JSON.pm';
    return;
  };
}

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $ok, $detail) = @_;
  if ($ok) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print "  $detail\n" if defined $detail;
  }
}

foreach my $module (qw(ZoneMinder::Control::Reolink_HTTP ZoneMinder::Control::Uniview)) {
  my $loaded = eval "require $module; 1";
  check("$module loads without JSON.pm", $loaded, $@);
  next if !$loaded;
  my $round = eval {
    no strict 'refs';
    my $text = &{"${module}::encode_json"}([{cmd => 'Login', param => {channel => 0}}]);
    &{"${module}::decode_json"}($text)->[0]{param}{channel};
  };
  check("$module encode_json/decode_json round trip", defined $round && $round == 0, $@);
}

require ZoneMinder::General;
my ($fh, $file) = tempfile(UNLINK => 1);
print $fh '{"00:11:22":"Vendor A"}';
close $fh;
my $json = ZoneMinder::General::jsonLoad($file);
check('ZoneMinder::General::jsonLoad reads a file without JSON.pm',
  ref($json) eq 'HASH' && ($json->{'00:11:22'} // '') eq 'Vendor A');

check('JSON.pm was never loaded', !exists $INC{'JSON.pm'} || !defined $INC{'JSON.pm'});

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
