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

# open() takes the host and credentials from the ControlAddress.
package FakeOpenUA;
sub new { return bless {reqs => []}, $_[0]; }
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new(1);
}
sub credentials {}
sub agent {}
sub ssl_opts {}

package main;

sub dahua_open {
  my ($addr) = @_;
  my $open_ua = FakeOpenUA->new();
  no warnings 'redefine';
  local *LWP::UserAgent::new = sub { return $open_ua; };
  $ZoneMinder::Control::Dahua::ADDRESS = '';
  $ZoneMinder::Control::Dahua::USERNAME = '';
  $ZoneMinder::Control::Dahua::PASSWORD = '';
  my $c = bless {
    Monitor => { Id => 1, ControlAddress => $addr, Path => '' },
  }, 'ZoneMinder::Control::Dahua';
  $c->open();
  return @{$open_ua->{reqs}} ? $open_ua->{reqs}[0]->uri()->as_string() : '';
}

check('address without credentials connects to that host',
  dahua_open('192.0.2.1'),
  'http://192.0.2.1:80/cgi-bin/configManager.cgi?action=getConfig&name=Ptz');
check('address without credentials sets no username',
  $ZoneMinder::Control::Dahua::USERNAME, '');

check('password containing @ connects to the host after the last @',
  dahua_open('admin:p@ss@192.0.2.1:8080'),
  'http://192.0.2.1:8080/cgi-bin/configManager.cgi?action=getConfig&name=Ptz');
check('password containing @ is kept whole',
  $ZoneMinder::Control::Dahua::PASSWORD, 'p@ss');

check('address with a port and no credentials connects to it',
  dahua_open('192.0.2.1:8080'),
  'http://192.0.2.1:8080/cgi-bin/configManager.cgi?action=getConfig&name=Ptz');
check('address with an empty username still connects to the host',
  dahua_open(':secret@192.0.2.1'),
  'http://192.0.2.1:80/cgi-bin/configManager.cgi?action=getConfig&name=Ptz');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
