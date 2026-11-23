#!/usr/bin/perl
#
# ZoneMinder::Control::Instar720p PTZ tests.
#
# Tests:
# 1. Credential parsing in open() with colons in password (e.g. admin:p:a:s:s@host).
# 2. getCamParams quote-stripping: quoted CGI response values like var brightness="6";
#    must not break arithmetic (e.g. "6" + 1 becoming 1 instead of capped at 6).
#
# Run as: perl tests/perl/test_instar720p_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib ($ENV{ZM_LIB_DIR} // "$FindBin::Bin/../../scripts/ZoneMinder/lib");

use URI;
use ZoneMinder::Control::Instar720p;

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
  my ($class, $body, $status) = @_;
  return bless { body => $body, status => $status // '200 OK' }, $class;
}
sub is_success { $_[0]->{status} =~ /^200/ }
sub status_line { $_[0]->{status} }
sub decoded_content { $_[0]->{body} }

package FakeUA;
sub new {
  my ($class, %opts) = @_;
  return bless {
    reqs => [],
    creds => {},
    body => $opts{body} // 'ok',
  }, $class;
}
sub agent {}
sub credentials {
  my ($self, $netloc, $realm, $user, $pass) = @_;
  $self->{creds}->{$netloc} = { realm => $realm, user => $user, pass => $pass };
}
sub get {
  my ($self, $url) = @_;
  push @{$self->{reqs}}, $url;
  return FakeResponse->new($self->{body});
}

package FakeMonitor;
sub new {
  my ($class, %opts) = @_;
  return bless {
    Id => 1,
    ControlAddress => $opts{ControlAddress} // 'http://admin:secret@192.0.2.1:80',
    ControlDevice => $opts{ControlDevice} // 'test-cam',
    ModectDuringPTZ => $opts{ModectDuringPTZ} // 1,
    AutoStopTimeout => $opts{AutoStopTimeout} // 0,
    suspended => 0,
    resumed => 0,
  }, $class;
}
sub suspendMotionDetection { $_[0]->{suspended}++ }
sub resumeMotionDetection { $_[0]->{resumed}++ }

package main;

our $ACTIVE_UA;

# Mock LWP::UserAgent::new to return our FakeUA instance
no warnings 'redefine';
*LWP::UserAgent::new = sub {
  return $ACTIVE_UA // FakeUA->new();
};

sub camera {
  my (%opts) = @_;
  my $ua = FakeUA->new(body => $opts{body});
  my $mon = FakeMonitor->new(%opts);
  my $control = bless {
    ua => $ua,
    Monitor => $mon,
    address => '192.0.2.1:80',
    base_url => 'http://admin:secret@192.0.2.1:80/',
  }, 'ZoneMinder::Control::Instar720p';
  return ($control, $ua, $mon);
}

# 1. Credential parsing in open() with colons in password
{
  my $ua = FakeUA->new();
  local $ACTIVE_UA = $ua;
  my $mon = FakeMonitor->new(ControlAddress => 'http://admin:p:a:s:s@192.0.2.1:80');
  my $control = bless {
    Monitor => $mon,
  }, 'ZoneMinder::Control::Instar720p';

  # Mock loadMonitor so open() doesn't query a database
  local *ZoneMinder::Control::loadMonitor = sub { $_[0]->{Monitor} = $mon };
  $control->open();

  my $creds = $control->{ua}->{creds}->{'192.0.2.1:80'};
  check('open extracts username when password has colons',
    $creds ? $creds->{user} : '', 'admin');
  check('open extracts password when password has colons',
    $creds ? $creds->{pass} : '', 'p:a:s:s');
}

# 2. Quotes in getCamParams must not break numeric arithmetic
{
  my ($control, $ua) = camera(body => qq{var brightness="6";\nvar contrast="5";\n});
  $control->irisAbsOpen({ step => 1 });
  my @urls = @{$ua->{reqs}};
  # With quotes preserved, "6" + 1 evaluates to 0 + 1 = 1, sending -brightness=1.
  # With quotes stripped, 6 + 1 = 7 capped at 6, sending -brightness=6.
  check('irisAbsOpen does not numify quoted "6" to 1',
    ($urls[-1] // '') =~ /-brightness=6/ ? 1 : 0, 1);
}

# 3. Commands with no corresponding stop must never suspend motion detection.
#    ModectDuringPTZ is 0, so the old code would have suspended here and never
#    resumed (the web UI sends no moveStop after these commands).
{
  for my $method (qw(presetHome up down left right)) {
    my ($control, $ua, $mon) = camera(ModectDuringPTZ => 0);
    $control->$method();
    check("$method does not suspend motion detection", $mon->{suspended}, 0);
  }
}

# 4. Positive control: a continuous move followed by stop does suspend and resume.
{
  my ($control, $ua, $mon) = camera(ModectDuringPTZ => 0);
  $control->moveConUp();
  $control->moveStop();
  check('moveConUp then moveStop suspends once', $mon->{suspended}, 1);
  check('moveConUp then moveStop resumes once', $mon->{resumed}, 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
