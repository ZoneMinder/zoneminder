#!/usr/bin/perl
#
# ZoneMinder::Control::Uniview audit tests
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::Uniview;

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
sub cookie_jar {}
sub agent {}

package FakeMonitor;
sub new {
  my ($class) = @_;
  return bless {
    Id => 1,
    Name => 'TestUniview',
    ControlAddress => 'admin:123456@192.168.1.50',
    ControlDevice => '1',
    AutoStopTimeout => 0,
  }, $class;
}

package main;

sub uniview_control {
  my $ua = FakeUA->new();
  my $c = bless {
    ua => $ua,
    Monitor => FakeMonitor->new(),
    BaseURL => 'http://192.168.1.50',
    has_lapi => 0,
  }, 'ZoneMinder::Control::Uniview';
  return ($c, $ua);
}

# 1. focusStop exists and sends focus 0
{
  my ($c, $ua) = uniview_control();
  check('Uniview implements focusStop', $c->can('focusStop') ? 1 : 0, 1);
  if ($c->can('focusStop')) {
    $c->focusStop();
    my $req = $ua->{reqs}[-1];
    check('focusStop sends to focus endpoint',
      $req ? $req->uri . '' : '', 'http://192.168.1.50/ISAPI/System/Video/inputs/channels/1/focus');
    check('focusStop sends <focus>0</focus>',
      $req ? ($req->content =~ /<focus>0<\/focus>/ ? 1 : 0) : 0, 1);
  }
}

# 2. irisStop exists and sends iris 0
{
  my ($c, $ua) = uniview_control();
  check('Uniview implements irisStop', $c->can('irisStop') ? 1 : 0, 1);
  if ($c->can('irisStop')) {
    $c->irisStop();
    my $req = $ua->{reqs}[-1];
    check('irisStop sends to iris endpoint',
      $req ? $req->uri . '' : '', 'http://192.168.1.50/ISAPI/System/Video/inputs/channels/1/iris');
    check('irisStop sends <iris>0</iris>',
      $req ? ($req->content =~ /<iris>0<\/iris>/ ? 1 : 0) : 0, 1);
  }
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
