#!/usr/bin/perl
#
# ZoneMinder::Control::TapoC520WS_ONVIF parseControlAddress with a password
# that contains a colon.  The userinfo was split on every colon, so
# admin:pa:ss@host gave the password 'pa' and the WS-Security digest was wrong.
#
# Run as: perl tests/perl/test_tapo_onvif_password_colon.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  if (!eval { require DateTime; 1 }) {
    $INC{'DateTime.pm'} = 1;
  }
}

use ZoneMinder::Control::TapoC520WS_ONVIF;

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

my $c = bless {}, 'ZoneMinder::Control::TapoC520WS_ONVIF';
$c->parseControlAddress('admin:pa:ss@192.168.1.10:2020');
check('username before the first colon', $$c{username}, 'admin');
check('password keeps its colon', $$c{password}, 'pa:ss');

$c = bless {}, 'ZoneMinder::Control::TapoC520WS_ONVIF';
$c->parseControlAddress('admin:p%40ss@192.168.1.10:2020');
check('escaped password is unescaped', $$c{password}, 'p@ss');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
