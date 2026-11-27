#!/usr/bin/perl
#
# ZoneMinder::Control::DericamP2 image-setting tests.
#
# The Hi3510 getimageattr reply quotes its values (var brightness="50";).
# Steps must start from the camera's value, not from 0.
#
# Run as: perl tests/perl/test_dericamp2_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::DericamP2;

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
sub new { my ($class, $body) = @_; return bless { body => $body }, $class; }
sub is_success { 1 }
sub status_line { '200 OK' }
sub decoded_content { $_[0]->{body} }

package FakeUA;
sub new { my ($class, $body) = @_; return bless { reqs => [], body => $body }, $class; }
sub request {
  my ($self, $req) = @_;
  push @{$self->{reqs}}, $req->uri->as_string;
  return FakeResponse->new($self->{body});
}

package main;

sub camera {
  my $ua = FakeUA->new(qq{var brightness="50";\r\nvar contrast="40";\r\nvar saturation="30";\r\n});
  %ZoneMinder::Control::DericamP2::CamParams = ();
  my $control = bless {
    ua => $ua,
    Monitor => { ControlAddress => '192.0.2.1', ControlDevice => '' },
  }, 'ZoneMinder::Control::DericamP2';
  return ($control, $ua);
}

{
  my ($control, $ua) = camera();
  $control->irisAbsOpen({ step => 5 });
  check('irisAbsOpen steps up from the quoted brightness',
    $ua->{reqs}[-1], 'http://192.0.2.1/cgi-bin/hi3510/param.cgi?cmd=setimageattr&-brightness=55');
}

{
  my ($control, $ua) = camera();
  $control->whiteAbsOut({ step => 5 });
  check('whiteAbsOut steps down from the quoted contrast',
    $ua->{reqs}[-1], 'http://192.0.2.1/cgi-bin/hi3510/param.cgi?cmd=setimageattr&-contrast=35');
}

{
  my ($control, $ua) = camera();
  $control->satIncrease({ step => 5 });
  check('satIncrease steps up from the quoted saturation',
    $ua->{reqs}[-1], 'http://192.0.2.1/cgi-bin/hi3510/param.cgi?cmd=setimageattr&-saturation=35');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
