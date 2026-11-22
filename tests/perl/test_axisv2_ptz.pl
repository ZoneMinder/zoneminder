#!/usr/bin/perl
#
# ZoneMinder::Control::AxisV2 audit tests
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::AxisV2;

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
sub is_success { $_[0]->{code} >= 200 && $_[0]->{code} < 300 }
sub code { $_[0]->{code} }
sub content { $_[0]->{content} }
sub status_line { $_[0]->{status_line} }

package FakeUA;
sub new {
  my ($class) = @_;
  return bless { reqs => [] }, $class;
}
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  my $uri = $req->uri . '';
  if ($uri =~ m{/axis-cgi/com/ptz\.cgi\?rfocus=}) {
    # Axis ptz.cgi returns 204 No Content with empty body on success
    return FakeResponse->new(code => 204, status_line => '204 No Content', content => '');
  }
  return FakeResponse->new(code => 200, status_line => '200 OK', content => 'OK');
}
sub get {
  my ($self, $url) = @_;
  my $req = HTTP::Request->new(GET => $url);
  return $self->request($req);
}
sub cookie_jar {}
sub agent {}

package FakeMonitor;
sub new {
  my ($class) = @_;
  return bless {
    Id => 1,
    Name => 'TestAxis',
    ControlAddress => '192.168.1.50',
    ControlDevice => '',
    Width => 1920,
    Height => 1080,
  }, $class;
}

package main;

sub axis_control {
  my ($ua) = @_;
  $ua //= FakeUA->new();
  my $c = bless {
    ua => $ua,
    Monitor => FakeMonitor->new(),
    BaseURL => 'http://192.168.1.50',
    address => '192.168.1.50',
    host => '192.168.1.50',
    port => 80,
  }, 'ZoneMinder::Control::AxisV2';
  return ($c, $ua);
}

# 1. reboot uses /axis-cgi/admin/restart.cgi, not /axis-cgi/restart.cgi
{
  my ($c, $ua) = axis_control();
  $c->reboot();
  my $url = @{$ua->{reqs}} ? $ua->{reqs}[-1]->uri . '' : '';
  check('reboot sends to /axis-cgi/admin/restart.cgi',
    $url, 'http://192.168.1.50/axis-cgi/admin/restart.cgi');
}

# 2. focusRelNear on 204 No Content does not recurse into opticssetup.cgi
{
  my ($c, $ua) = axis_control();
  $c->focusRelNear({ step => 100 });
  my @urls = map { $_->uri . '' } @{$ua->{reqs}};
  check('focusRelNear sends exactly one request when ptz.cgi returns 204',
    scalar(@urls), 1);
  check('focusRelNear does not fall back to opticssetup.cgi on 204',
    $urls[0] // '', 'http://192.168.1.50/axis-cgi/com/ptz.cgi?rfocus=-100');
  check('use_optics flag is not set on successful ptz.cgi',
    $c->{use_optics} // 0, 0);
}

# 4. focusRelFar on 204 No Content does not recurse into opticssetup.cgi
{
  my ($c, $ua) = axis_control();
  $c->focusRelFar({ step => 100 });
  my @urls = map { $_->uri . '' } @{$ua->{reqs}};
  check('focusRelFar sends exactly one request when ptz.cgi returns 204',
    scalar(@urls), 1);
  check('focusRelFar does not fall back to opticssetup.cgi on 204',
    $urls[0] // '', 'http://192.168.1.50/axis-cgi/com/ptz.cgi?rfocus=100');
  check('use_optics flag is not set on successful ptz.cgi',
    $c->{use_optics} // 0, 0);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
