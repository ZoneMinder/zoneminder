#!/usr/bin/perl
#
# ZoneMinder::Control::FoscamCGI PTZ tests.
#
# Tests:
# 1. zoomStop method exists and stops continuous zoom tele/wide with command=19 / 17.
# 2. moveStop sends direction-specific stop commands instead of hardcoded command=1 (Stop Up).
#
# Run as: perl tests/perl/test_foscamcgi_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::FoscamCGI;

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
  return FakeResponse->new('ok');
}

package FakeMonitor;
sub new {
  my ($class, %opts) = @_;
  return bless {
    Id => 1,
    ControlAddress => '192.0.2.1:80',
    ControlDevice => 'user=admin&pwd=secret',
    AutoStopTimeout => $opts{AutoStopTimeout} // 0,
  }, $class;
}

package main;

sub camera {
  my (%opts) = @_;
  my $ua = FakeUA->new();
  my $control = bless {
    ua => $ua,
    Monitor => FakeMonitor->new(%opts),
  }, 'ZoneMinder::Control::FoscamCGI';
  return ($control, $ua);
}

# 1. zoomStop method must exist
check('FoscamCGI implements zoomStop',
  ZoneMinder::Control::FoscamCGI->can('zoomStop') ? 1 : 0, 1);

# 2. zoomConTele followed by zoomStop sends command=19
{
  my ($control, $ua) = camera();
  $control->zoomConTele();
  if ($control->can('zoomStop')) {
    $control->zoomStop();
  }
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('zoomConTele sends command=18', ($urls[0] // '') =~ /command=18\b/ ? 1 : 0, 1);
  check('zoomConTele zoomStop sends command=19',
    ($urls[-1] // '') =~ /command=19\b/ ? 1 : 0, 1);
}

# 3. zoomConWide followed by zoomStop sends command=17
{
  my ($control, $ua) = camera();
  $control->zoomConWide();
  if ($control->can('zoomStop')) {
    $control->zoomStop();
  }
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('zoomConWide sends command=16', ($urls[0] // '') =~ /command=16\b/ ? 1 : 0, 1);
  check('zoomConWide zoomStop sends command=17',
    ($urls[-1] // '') =~ /command=17\b/ ? 1 : 0, 1);
}

# 4. moveConDown followed by moveStop sends command=3 (Stop Down)
{
  my ($control, $ua) = camera();
  $control->moveConDown();
  $control->moveStop();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('moveConDown sends command=2', ($urls[0] // '') =~ /command=2\b/ ? 1 : 0, 1);
  check('moveConDown moveStop sends command=3 (Stop Down)',
    ($urls[-1] // '') =~ /command=3\b/ ? 1 : 0, 1);
}

# 5. moveConLeft followed by moveStop sends command=5 (Stop Left)
{
  my ($control, $ua) = camera();
  $control->moveConLeft();
  $control->moveStop();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('moveConLeft sends command=4', ($urls[0] // '') =~ /command=4\b/ ? 1 : 0, 1);
  check('moveConLeft moveStop sends command=5 (Stop Left)',
    ($urls[-1] // '') =~ /command=5\b/ ? 1 : 0, 1);
}

# 6. moveConRight followed by moveStop sends command=7 (Stop Right)
{
  my ($control, $ua) = camera();
  $control->moveConRight();
  $control->moveStop();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('moveConRight sends command=6', ($urls[0] // '') =~ /command=6\b/ ? 1 : 0, 1);
  check('moveConRight moveStop sends command=7 (Stop Right)',
    ($urls[-1] // '') =~ /command=7\b/ ? 1 : 0, 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
