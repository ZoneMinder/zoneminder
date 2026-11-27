#!/usr/bin/perl
#
# ZoneMinder::Control::Reolink_HTTP must keep the brackets of an IPv6 Control Address.
#
# login and sendApiCmd built their urls from the host that URI hands back, which
# has no brackets for an IPv6 literal, so a camera at [fe80::1]:8080 was sent to
# the unparseable http://fe80::1:8080/cgi-bin/api.cgi and no command reached it.
#
# Run as: perl tests/perl/test_reolink_http_ipv6.pl
#
use strict;
use warnings;
use FindBin;
use lib (($ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..") . '/scripts/ZoneMinder/lib');

use ZoneMinder::Control::Reolink_HTTP;

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

package FakeResponse;
sub new { return bless {}, shift; }
sub is_success { 1 }
sub is_redirect { 0 }
sub status_line { '200 OK' }
sub header { undef }
sub decoded_content { '[{"cmd":"Login","code":0,"value":{"Token":{"leaseTime":3600,"name":"tok"}}}]' }

package main;

my @requests;
{
  no warnings qw(redefine once);
  *LWP::UserAgent::request = sub { push @requests, $_[1]->uri.''; return FakeResponse->new(); };
  *ZoneMinder::Control::Reolink_HTTP::loadMonitor = sub { };
}

sub urls_for {
  my ($address) = @_;
  @requests = ();
  my $c = bless {Monitor => {ControlAddress => $address, ControlDevice => 0, ModectDuringPTZ => 1}},
    'ZoneMinder::Control::Reolink_HTTP';
  $c->open();
  $c->sendApiCmd({cmd => 'GetPtzPreset', action => 1, param => {channel => 0}});
  return @requests[0, 1];
}

my ($login, $api) = urls_for('admin:pw@[fe80::1]:8080');
check('IPv6 login keeps the brackets and port', $login, 'http://[fe80::1]:8080/cgi-bin/api.cgi?cmd=Login&token=null');
check('IPv6 command keeps the brackets and port', $api, 'http://[fe80::1]:8080/cgi-bin/api.cgi?cmd=GetPtzPreset&token=tok');

($login, $api) = urls_for('admin:pw@10.0.0.5:8080');
check('IPv4 login is unchanged', $login, 'http://10.0.0.5:8080/cgi-bin/api.cgi?cmd=Login&token=null');
check('IPv4 command is unchanged', $api, 'http://10.0.0.5:8080/cgi-bin/api.cgi?cmd=GetPtzPreset&token=tok');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
