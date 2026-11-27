#!/usr/bin/perl
#
# ZoneMinder::Control::PSIA audit tests
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use LWP::UserAgent;
use HTTP::Request;
use ZoneMinder::Control::PSIA;

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
sub new {
  my ($class, %args) = @_;
  return bless {
    code => $args{code} // 200,
    content => $args{content} // '',
    status_line => $args{status_line} // '200 OK',
  }, $class;
}
sub is_success { 1 }
sub code { $_[0]->{code} }
sub content { $_[0]->{content} }
sub status_line { $_[0]->{status_line} }
sub headers { {} }

package FakeUA;
sub new {
  my ($class) = @_;
  return bless { reqs => [], creds => [] }, $class;
}
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new();
}
sub credentials {
  my ($self, @args) = @_;
  push @{$self->{creds}}, \@args;
}
sub agent {}

package FakeMonitor;
sub new {
  my ($class, $addr) = @_;
  return bless {
    Id => 1,
    Name => 'TestPSIA',
    ControlAddress => $addr // '192.168.1.50',
    ControlDevice => 'TV-IP450PI',
    Path => '',
  }, $class;
}

package main;

my $current_ua;
{
  no warnings 'redefine';
  *LWP::UserAgent::new = sub { return $current_ua; };
  *ZoneMinder::Database::zmDbConnect = sub { return undef; };
  *ZoneMinder::Control::PSIA::zmDbConnect = sub { return undef; };
}

sub psia_control {
  my ($addr) = @_;
  $current_ua = FakeUA->new();
  my $c = bless {
    ua => $current_ua,
    Monitor => FakeMonitor->new($addr),
  }, 'ZoneMinder::Control::PSIA';
  return ($c, $current_ua);
}

# 1. Password with @ in ControlAddress parsed correctly
{
  my ($c, $ua) = psia_control('admin:p@ssword@192.168.1.50:80');
  $c->open();
  my $first_req = @{$ua->{reqs}} ? $ua->{reqs}[0]->uri . '' : '';
  check('password with @ connects to correct host/port',
    $first_req, 'http://192.168.1.50:80/PSIA/PTZ/channels');
  check('password with @ parsed without truncation',
    $ZoneMinder::Control::PSIA::PASSWORD, 'p@ssword');
}

# 2. ControlAddress without credentials parsed correctly
{
  my ($c, $ua) = psia_control('192.168.1.50');
  $c->open();
  my $first_req = @{$ua->{reqs}} ? $ua->{reqs}[0]->uri . '' : '';
  check('address without credentials connects to correct host:port',
    $first_req, 'http://192.168.1.50:80/PSIA/PTZ/channels');
}

# 3. sendPutRequest sets application/xml content-type for XML payloads
{
  my ($c, $ua) = psia_control('admin:pass@192.168.1.50:80');
  $c->open();
  @{$ua->{reqs}} = ();
  $c->sendPutRequest('/PSIA/PTZ/channels/1/momentary', '<PTZData></PTZData>');
  my $req = $ua->{reqs}[0];
  check('sendPutRequest uses application/xml content type',
    $req ? scalar($req->header('Content-Type')) : '', 'application/xml; charset=UTF-8');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
