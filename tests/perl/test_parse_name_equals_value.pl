#!/usr/bin/perl
#
# Tests ZoneMinder::General::parseNameEqualsValueToHash, which reads the
# name=value config replies of the Amcrest and Vivotek control modules.
# A value that itself holds an '=' must come back whole.
#
# Run as: perl tests/perl/test_parse_name_equals_value.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN { $ENV{LOG_FILE} = "/tmp/zm_test_parse_nv_$$.log"; }

use ZoneMinder::General;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $ok) = @_;
  if ($ok) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "NOT OK - $name\n";
  }
}

my $settings = ZoneMinder::General::parseNameEqualsValueToHash(
  "table.Snap.Url=http://host/cgi?a=1&b=2\r\n"
  ."table.General.MachineName=\n"
  ."stream_name='live=1'\n"
  ."plain=value\n"
);

check('keeps everything after the first = in the value',
  ($$settings{'table.Snap.Url'} // '') eq 'http://host/cgi?a=1&b=2');
check('an empty value is the empty string',
  ($$settings{'table.General.MachineName'} // 'undef') eq '');
check('strips the quotes around a value holding an =',
  ($$settings{stream_name} // '') eq 'live=1');
check('a plain value is unchanged', ($$settings{plain} // '') eq 'value');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
