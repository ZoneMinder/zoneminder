#!/usr/bin/perl
#
# ZoneMinder::Control::Dahua reset/reboot test.
#
# reset should reboot the camera using Dahua's documented
# /cgi-bin/magicBox.cgi?action=reboot (same as reboot()),
# rather than sending an invalid /cgi-bin/ptz.cgi?action=Reset request.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::Dahua;

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
sub new { return bless {ok => $_[1]}, $_[0]; }
sub is_success { $_[0]->{ok} }
sub status_line { $_[0]->{ok} ? '200 OK' : '500 fail' }

package FakeUserAgent;
sub new { return bless {last_req => undef}, $_[0]; }
sub request {
  my ($self, $req) = @_;
  $self->{last_req} = $req;
  return FakeResponse->new(1);
}

package main;

$ZoneMinder::Control::Dahua::PROTOCOL = 'http://';
$ZoneMinder::Control::Dahua::ADDRESS = '192.0.2.1:80';

my $ua = FakeUserAgent->new();
my $control = bless {
  Monitor => {
    Id => 1,
    ControlAddress => 'admin:password@192.0.2.1:80',
  },
  state => 'open',
  dahua_channel_number => '0',
  ua => $ua,
}, 'ZoneMinder::Control::Dahua';

$control->reset();
my $got_uri = $ua->{last_req} ? $ua->{last_req}->uri()->as_string() : '';
check('reset sends magicBox.cgi?action=reboot',
  $got_uri,
  'http://192.0.2.1:80/cgi-bin/magicBox.cgi?action=reboot');

$control->reboot();
my $reboot_uri = $ua->{last_req} ? $ua->{last_req}->uri()->as_string() : '';
check('reboot sends magicBox.cgi?action=reboot',
  $reboot_uri,
  'http://192.0.2.1:80/cgi-bin/magicBox.cgi?action=reboot');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
