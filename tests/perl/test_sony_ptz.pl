#!/usr/bin/perl
#
# ZoneMinder::Control::Sony audit tests
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::Sony;

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

package FakeUA;
sub new {
  my ($class) = @_;
  return bless { reqs => [] }, $class;
}
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new();
}
sub agent {}

package FakeMonitor;
sub new {
  my ($class) = @_;
  return bless {
    Id => 1,
    Name => 'TestSony',
    ControlAddress => '192.168.1.50',
    Width => 1920,
    Height => 1080,
  }, $class;
}

package main;

sub sony_control {
  my $ua = FakeUA->new();
  my $c = bless {
    ua => $ua,
    Monitor => FakeMonitor->new(),
  }, 'ZoneMinder::Control::Sony';
  return ($c, $ua);
}

# 1. zoomStop method exists
{
  my ($c, $ua) = sony_control();
  check('Sony implements zoomStop', $c->can('zoomStop') ? 1 : 0, 1);
}

# 2. zoomStop sends /command/ptzf.cgi?Move=stop,zoom,1
{
  my ($c, $ua) = sony_control();
  if ($c->can('zoomStop')) {
    $c->zoomStop();
    my $url = @{$ua->{reqs}} ? $ua->{reqs}[-1]->uri . '' : '';
    check('zoomStop sends /command/ptzf.cgi?Move=stop,zoom,1',
      $url, 'http://192.168.1.50/command/ptzf.cgi?Move=stop,zoom,1');
  }
}

# 3. relative moves send panstep/tiltstep (panstep=12 tiltstep=34)
{
  my ($c, $ua) = sony_control();
  my $params = {panstep => '12', tiltstep => '34'};
  my @cases = (
    ['moveRelUpRight', '0912'],
    ['moveRelUpLeft', '0712'],
    ['moveRelDownRight', '0312'],
    ['moveRelDownLeft', '0112'],
    ['moveRelUp', '0834'],
    ['moveRelDown', '0234'],
    ['moveRelLeft', '0412'],
    ['moveRelRight', '0612'],
  );
  for my $case (@cases) {
    my ($meth, $rel) = @$case;
    if ($c->can($meth)) {
      $c->$meth($params);
      my $url = @{$ua->{reqs}} ? $ua->{reqs}[-1]->uri . '' : '';
      check("$meth sends /command/ptzf.cgi?relative=$rel (panstep=12 tiltstep=34)",
        $url, "http://192.168.1.50/command/ptzf.cgi?relative=$rel");
    } else {
      check("Sony implements $meth", 0, 1);
    }
  }
}

# 4. relative diagonal moves send panstep (second set panstep=07)
{
  my ($c, $ua) = sony_control();
  my $params = {panstep => '07', tiltstep => '21'};
  my @cases = (
    ['moveRelUpRight', '0907'],
    ['moveRelUpLeft', '0707'],
    ['moveRelDownRight', '0307'],
    ['moveRelDownLeft', '0107'],
  );
  for my $case (@cases) {
    my ($meth, $rel) = @$case;
    if ($c->can($meth)) {
      $c->$meth($params);
      my $url = @{$ua->{reqs}} ? $ua->{reqs}[-1]->uri . '' : '';
      check("$meth sends /command/ptzf.cgi?relative=$rel (panstep=07)",
        $url, "http://192.168.1.50/command/ptzf.cgi?relative=$rel");
    } else {
      check("Sony implements $meth", 0, 1);
    }
  }
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
