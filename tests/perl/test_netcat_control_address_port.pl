#!/usr/bin/perl
#
# ZoneMinder::Control::Netcat parseControlAddress tests.
#
# Tests:
# 1. Bare address keeps the default port 80 set by open().
# 2. Address with a port overrides it.
# 3. Address with credentials sets username/password and keeps port 80.
# 4. Address with credentials and a port sets all four globals.
#
# Run as: perl tests/perl/test_netcat_control_address_port.pl
#
use strict;
use warnings;
use FindBin;

our $root;
BEGIN { $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../.."; }
use lib "$root/scripts/ZoneMinder/lib";

use ZoneMinder::Control::Netcat;

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

sub reset_globals {
  $ZoneMinder::Control::Netcat::port = 80;
  $ZoneMinder::Control::Netcat::address = undef;
  $ZoneMinder::Control::Netcat::username = undef;
  $ZoneMinder::Control::Netcat::password = undef;
}

# 1. Bare address keeps the default port
{
  reset_globals();
  ZoneMinder::Control::Netcat::parseControlAddress('192.0.2.1');
  check('192.0.2.1 sets address',
    $ZoneMinder::Control::Netcat::address, '192.0.2.1');
  check('192.0.2.1 keeps default port 80',
    $ZoneMinder::Control::Netcat::port, 80);
}

# 2. Address with a port
{
  reset_globals();
  ZoneMinder::Control::Netcat::parseControlAddress('192.0.2.1:8899');
  check('192.0.2.1:8899 sets address',
    $ZoneMinder::Control::Netcat::address, '192.0.2.1');
  check('192.0.2.1:8899 sets port 8899',
    $ZoneMinder::Control::Netcat::port, 8899);
}

# 3. Credentials without a port
{
  reset_globals();
  ZoneMinder::Control::Netcat::parseControlAddress('admin:secret@192.0.2.1');
  check('credentials set address',
    $ZoneMinder::Control::Netcat::address, '192.0.2.1');
  check('credentials keep default port 80',
    $ZoneMinder::Control::Netcat::port, 80);
  check('credentials set username',
    $ZoneMinder::Control::Netcat::username, 'admin');
  check('credentials set password',
    $ZoneMinder::Control::Netcat::password, 'secret');
}

# 4. Credentials with a port
{
  reset_globals();
  ZoneMinder::Control::Netcat::parseControlAddress('admin:secret@192.0.2.1:8000');
  check('credentials+port set address',
    $ZoneMinder::Control::Netcat::address, '192.0.2.1');
  check('credentials+port set port 8000',
    $ZoneMinder::Control::Netcat::port, 8000);
  check('credentials+port set username',
    $ZoneMinder::Control::Netcat::username, 'admin');
  check('credentials+port set password',
    $ZoneMinder::Control::Netcat::password, 'secret');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
