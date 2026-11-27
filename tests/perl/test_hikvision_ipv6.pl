#!/usr/bin/perl
#
# ZoneMinder::Control::HikVision must keep an IPv6 Control Address in one piece.
#
# open() split the host that URI hands back at its first colon, so a camera at
# [fe80::1]:8080 became host "fe80" with the rest taken for a port, and the base
# url and the credentials key were built without the brackets a URL needs.
#
# No network: get_realm() is stubbed and the user agent only records credentials.
#
# Run as: perl tests/perl/test_hikvision_ipv6.pl
#
use strict;
use warnings;
use FindBin;
use lib ($ENV{ZM_LIB_DIR} // "$FindBin::Bin/../../scripts/ZoneMinder/lib");

BEGIN {
  # Optional CPAN modules; only the config/firmware helpers use them.
  if (!eval { require URI::Encode; 1 }) {
    delete $INC{'URI/Encode.pm'};
    $INC{'URI/Encode.pm'} = __FILE__;
    no strict 'refs';
    *{'URI::Encode::uri_encode'} = sub { return $_[0]; };
  }
  if (!eval { require XML::LibXML; 1 }) {
    delete $INC{'XML/LibXML.pm'};
    $INC{'XML/LibXML.pm'} = __FILE__;
  }
}

use ZoneMinder::Control::HikVision;

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

package FakeUA;
sub new { return bless {creds => []}, $_[0]; }
sub cookie_jar { }
sub credentials {
  my ($self, @args) = @_;
  push @{$self->{creds}}, $args[0];
}

package main;

{
  no warnings qw(redefine once);
  *LWP::UserAgent::new = sub { return FakeUA->new(); };
  *ZoneMinder::Control::HikVision::get_realm = sub { return 1; };
  *ZoneMinder::Control::HikVision::loadMonitor = sub { };
}

sub opened {
  my ($address) = @_;
  my $c = bless {Monitor => {ControlAddress => $address, ControlDevice => 1}}, 'ZoneMinder::Control::HikVision';
  $c->open();
  return $c;
}

my $c = opened('admin:pw@[fe80::1]:8080');
check('IPv6 base url keeps its brackets and port', $c->{BaseURL}, 'http://[fe80::1]:8080');
check('IPv6 host is not split at a colon', $c->{host}, 'fe80::1');
check('IPv6 credentials are keyed by the bracketed host and port', $c->{ua}{creds}[0], '[fe80::1]:8080');

$c = opened('admin:pw@192.168.1.10:8080');
check('IPv4 base url is unchanged', $c->{BaseURL}, 'http://192.168.1.10:8080');
check('IPv4 credentials are unchanged', $c->{ua}{creds}[0], '192.168.1.10:8080');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
