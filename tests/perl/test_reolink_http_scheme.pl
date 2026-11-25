#!/usr/bin/perl
#
# ZoneMinder::Control::Reolink_HTTP must use the scheme of the Control Address.
#
# open() set the protocol to http whatever the address said, so a camera
# given as https://user:pass@host was sent plain http to port 443 and the login
# failed unless the camera's http port happened to redirect.
#
# Run as: perl tests/perl/test_reolink_http_scheme.pl
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

sub open_camera {
  my ($address) = @_;
  @requests = ();
  my $c = bless {Monitor => {ControlAddress => $address, ControlDevice => 0, ModectDuringPTZ => 1}},
    'ZoneMinder::Control::Reolink_HTTP';
  my $ok = $c->open();
  return ($c, $ok, $requests[0]);
}

my ($c, $ok, $url) = open_camera('https://admin:pw@10.0.0.5');
check('https address logs in over https', $url, 'https://10.0.0.5:443/cgi-bin/api.cgi?cmd=Login&token=null');
check('https address opens', $ok ? 'yes' : 'no', 'yes');
check('https address accepts the camera certificate', $c->{ua}->ssl_opts('verify_hostname'), 0);

($c, $ok, $url) = open_camera('https://admin:pw@10.0.0.5:8443');
check('https address keeps its port', $url, 'https://10.0.0.5:8443/cgi-bin/api.cgi?cmd=Login&token=null');

($c, $ok, $url) = open_camera('admin:pw@10.0.0.5:8080');
check('address without a scheme stays http', $url, 'http://10.0.0.5:8080/cgi-bin/api.cgi?cmd=Login&token=null');

($c, $ok, $url) = open_camera('http://admin:pw@10.0.0.5:81');
check('http address stays http', $url, 'http://10.0.0.5:81/cgi-bin/api.cgi?cmd=Login&token=null');
check('http address keeps certificate checking untouched', $c->{ua}->ssl_opts('verify_hostname'), 1);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
