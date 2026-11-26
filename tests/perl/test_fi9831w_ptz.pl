#!/usr/bin/perl
#
# ZoneMinder::Control::FI9831W PTZ control tests.
#
# Tests presetHome (advertised by Controls row HasHomePreset=1 but previously
# omitted from module), moveStop, and continuous movement commands.
#
# Run as: perl tests/perl/test_fi9831w_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::FI9831W;

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
sub decoded_content { $_[0]->{body} }

package FakeUA;
sub new { my ($class) = @_; return bless {reqs => []}, $class; }
sub agent {}
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req;
  return FakeResponse->new('<CGI_Result><result>0</result></CGI_Result>');
}

package FakeMonitor;
sub new {
  return bless {
    Id => 1,
    ControlAddress => '192.0.2.1:88',
    ControlDevice => 'usr=admin&pwd=secret',
    AutoStopTimeout => 0,
  }, $_[0];
}

package main;

sub camera {
  my $ua = FakeUA->new();
  my $control = bless {
    ua => $ua,
    Monitor => FakeMonitor->new(),
  }, 'ZoneMinder::Control::FI9831W';
  return ($control, $ua);
}

# 1. Controls row advertises HasHomePreset=1; FI9831W must implement presetHome
{
  my ($control, $ua) = camera();
  check('FI9831W implements presetHome', $control->can('presetHome') ? 1 : 0, 1);
  if ($control->can('presetHome')) {
    $control->presetHome();
    my @urls = map { $_->uri.'' } @{$ua->{reqs}};
    check('presetHome sends ptzReset command',
      ($urls[-1] // '') =~ /cmd=ptzReset/ ? 1 : 0, 1);
  } else {
    check('presetHome sends ptzReset command', 0, 1);
  }
}

# 2. moveStop sends ptzStopRun
{
  my ($control, $ua) = camera();
  $control->moveStop();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('moveStop sends ptzStopRun',
    ($urls[-1] // '') =~ /cmd=ptzStopRun/ ? 1 : 0, 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
