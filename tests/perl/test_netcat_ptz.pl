#!/usr/bin/perl
#
# ZoneMinder::Control::Netcat functional test.
#
# Tests credential parsing with '@' and ':' in password, Stop SOAP action header,
# and missing zoomStop and presetHome methods.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

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
sub new { my ($class, $body) = @_; return bless {body => $body}, $class; }
sub is_success { 1 }
sub status_line { '200 OK' }
sub content { $_[0]->{body} }

package FakeUA;
sub new { return bless {reqs => []}, $_[0]; }
sub agent {}
sub get { return FakeResponse->new(''); }
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new('<s:Envelope><s:Body/></s:Envelope>');
}

package FakeMonitor;
sub new {
  my ($class, %args) = @_;
  return bless {
    ControlAddress => $args{ControlAddress} // 'admin:secret@192.168.1.50:8899',
    ControlDevice => '000',
    AutoStopTimeout => 0,
  }, $class;
}

package main;

our $active_ua = FakeUA->new();
BEGIN {
  $INC{'DateTime.pm'} = 1;
  {
    package DateTime;
    sub now { return bless {}, shift; }
    sub iso8601 { return '2026-11-16T04:11:00'; }
  }
  no warnings 'redefine';
  require LWP::UserAgent;
  *LWP::UserAgent::new = sub { return $active_ua; };
}

use ZoneMinder::Control::Netcat;

sub camera {
  my (%args) = @_;
  $active_ua = FakeUA->new();
  my $control = bless {
    ua => $active_ua,
    Monitor => FakeMonitor->new(%args),
  }, 'ZoneMinder::Control::Netcat';
  return ($control, $active_ua);
}

# 1. Credentials with @ and : in password
{
  $ZoneMinder::Control::Netcat::username = undef;
  $ZoneMinder::Control::Netcat::password = undef;
  $ZoneMinder::Control::Netcat::address = undef;
  $ZoneMinder::Control::Netcat::port = undef;
  ZoneMinder::Control::Netcat::parseControlAddress('admin:p@ss:w@rd@192.168.1.50:8899');
  check('parseControlAddress user is admin', $ZoneMinder::Control::Netcat::username, 'admin');
  check('parseControlAddress password preserved', $ZoneMinder::Control::Netcat::password, 'p@ss:w@rd');
  check('parseControlAddress address is 192.168.1.50', $ZoneMinder::Control::Netcat::address, '192.168.1.50');
  check('parseControlAddress port is 8899', $ZoneMinder::Control::Netcat::port, '8899');
}

# 2. moveStop SOAP action header
{
  my ($control, $ua) = camera();
  $control->moveStop();
  my $req = $ua->{reqs}[-1];
  my $ct = $req ? $req->header('content-type') : '';
  check('moveStop uses Stop action header, not ContinuousMove',
    $ct =~ /action="http:\/\/www\.onvif\.org\/ver20\/ptz\/wsdl\/Stop"/ ? 1 : 0, 1);
}

# 3. Advertised zoomStop method
{
  check('Netcat implements zoomStop', ZoneMinder::Control::Netcat->can('zoomStop') ? 1 : 0, 1);
  if (ZoneMinder::Control::Netcat->can('zoomStop')) {
    my ($control, $ua) = camera();
    $control->zoomStop();
    my $req = $ua->{reqs}[-1];
    my $content = $req ? $req->content : '';
    my $ct = $req ? $req->header('content-type') : '';
    check('zoomStop sends Stop body with Zoom true',
      $content =~ /<Stop.*<PanTilt>false<\/PanTilt><Zoom>true<\/Zoom><\/Stop>/s ? 1 : 0, 1);
    check('zoomStop uses Stop action header',
      $ct =~ /action="http:\/\/www\.onvif\.org\/ver20\/ptz\/wsdl\/Stop"/ ? 1 : 0, 1);
  }
}

# 4. Advertised presetHome method
{
  check('Netcat implements presetHome', ZoneMinder::Control::Netcat->can('presetHome') ? 1 : 0, 1);
  if (ZoneMinder::Control::Netcat->can('presetHome')) {
    my ($control, $ua) = camera();
    $control->presetHome();
    my $req = $ua->{reqs}[-1];
    my $content = $req ? $req->content : '';
    my $ct = $req ? $req->header('content-type') : '';
    check('presetHome sends GotoHomePosition',
      $content =~ /<GotoHomePosition/ ? 1 : 0, 1);
    check('presetHome uses GotoHomePosition action header',
      $ct =~ /action="http:\/\/www\.onvif\.org\/ver20\/ptz\/wsdl\/GotoHomePosition"/ ? 1 : 0, 1);
  }
}

# 5. Credentials from the monitor Path are percent-encoded there
{
  $ZoneMinder::Control::Netcat::username = undef;
  $ZoneMinder::Control::Netcat::password = undef;
  $ZoneMinder::Control::Netcat::address = undef;
  $active_ua = FakeUA->new();
  my $control = bless {
    Monitor => {
      ControlAddress => '',
      ControlDevice => '000',
      Path => 'rtsp://ad%40min:p%40ss%3Aw@10.9.9.9:554/stream1',
    },
  }, 'ZoneMinder::Control::Netcat';
  no warnings 'redefine';
  local *ZoneMinder::Control::Netcat::loadMonitor = sub {};
  $control->open();
  check('Path user is decoded', $ZoneMinder::Control::Netcat::username, 'ad@min');
  check('Path password is decoded', $ZoneMinder::Control::Netcat::password, 'p@ss:w');
  check('Path address is 10.9.9.9', $ZoneMinder::Control::Netcat::address, '10.9.9.9');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
