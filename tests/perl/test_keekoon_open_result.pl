#!/usr/bin/perl
#
# ZoneMinder::Control::Keekoon open() must report success.
#
# It ended on Info("Open"), whose return value is empty, so open() was false
# every time. zmcontrol logs "Failed to open control" for a false open(), and
# its direct protocol mode (zmcontrol.pl --protocol Keekoon) dies on it.
#
# Run as: perl tests/perl/test_keekoon_open_result.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  # Optional CPAN modules that are only used by the config helpers.
  if (!eval { require URI::Encode; 1 }) {
    delete $INC{'URI/Encode.pm'};
    $INC{'URI/Encode.pm'} = __FILE__;
    no strict 'refs';
    *{'URI::Encode::uri_encode'} = sub { return $_[0]; };
  }
}

use ZoneMinder::Control::Keekoon;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '') eq (defined $want ? $want : '')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

my $control = bless {
  Monitor => {
    ControlAddress => 'http://admin:secret@192.168.1.50:80',
    AutoStopTimeout => 0,
  },
  AutoStopTimeoutConverted => 1,
}, 'ZoneMinder::Control::Keekoon';

my $result = $control->open();
check('open reports success', $result ? 1 : 0, 1);
check('state is open', $control->{state}, 'open');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
