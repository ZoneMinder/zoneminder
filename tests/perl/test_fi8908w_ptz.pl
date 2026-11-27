#!/usr/bin/perl
#
# ZoneMinder::Control::FI8908W PTZ tests.
#
# Tests:
# 1. Credential parsing with colons in password in ControlDevice (e.g. admin:p:a:s:s).
# 2. moveStop sends direction-specific stop commands instead of hardcoded command=1 (Stop Up).
#
# Run as: perl tests/perl/test_fi8908w_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::FI8908W;

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
    ControlDevice => $opts{ControlDevice} // 'admin:secret',
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
  }, 'ZoneMinder::Control::FI8908W';
  return ($control, $ua);
}

# 1. Colons in password in ControlDevice (user:password)
{
  my ($control, $ua) = camera(ControlDevice => 'admin:p:a:s:s');
  $control->moveConUp();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('password with colons preserved in URL query',
    ($urls[0] // '') =~ /user=admin&pwd=p:a:s:s$/ ? 1 : 0, 1);
}

# 2. moveConDown followed by moveStop must send command=3 (Stop Down), not command=1 (Stop Up)
{
  my ($control, $ua) = camera();
  $control->moveConDown();
  $control->moveStop();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('moveConDown sends command=2', ($urls[0] // '') =~ /command=2\b/ ? 1 : 0, 1);
  check('moveConDown moveStop sends command=3 (Stop Down)',
    ($urls[-1] // '') =~ /command=3\b/ ? 1 : 0, 1);
}

# 3. moveConLeft followed by moveStop must send command=7 (Stop Left), not command=1 (Stop Up)
{
  my ($control, $ua) = camera();
  $control->moveConLeft();
  $control->moveStop();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('moveConLeft sends command=6', ($urls[0] // '') =~ /command=6\b/ ? 1 : 0, 1);
  check('moveConLeft moveStop sends command=7 (Stop Left)',
    ($urls[-1] // '') =~ /command=7\b/ ? 1 : 0, 1);
}

# 4. moveConRight followed by moveStop must send command=5 (Stop Right), not command=1 (Stop Up)
{
  my ($control, $ua) = camera();
  $control->moveConRight();
  $control->moveStop();
  my @urls = map { $_->uri.'' } @{$ua->{reqs}};
  check('moveConRight sends command=4', ($urls[0] // '') =~ /command=4\b/ ? 1 : 0, 1);
  check('moveConRight moveStop sends command=5 (Stop Right)',
    ($urls[-1] // '') =~ /command=5\b/ ? 1 : 0, 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
