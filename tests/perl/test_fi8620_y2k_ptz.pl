#!/usr/bin/perl
#
# ZoneMinder::Control::FI8620_Y2k request shapes.
#
# focusMan is the OSD on/off toggle.  Its second 'if' ran straight after the
# first, so one press sent "OSD off" and then "OSD on" and the overlay stayed
# on.
#
# Run as: perl tests/perl/test_fi8620_y2k_ptz.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::FI8620_Y2k;

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
sub new { return bless {}, $_[0]; }
sub is_success { 1 }
sub status_line { '200 OK' }

package FakeUA;
sub new { return bless {reqs => []}, $_[0]; }
sub request {
  my ($self, $req) = @_;
  my $uri = $req->uri->as_string;
  $uri =~ s/%20/ /g;
  $uri =~ s{^.*/hi3510/}{};
  push @{$self->{reqs}}, $uri;
  return FakeResponse->new;
}

package main;

my $class = 'ZoneMinder::Control::FI8620_Y2k';

sub camera {
  my $ua = FakeUA->new;
  my $control = bless {
    ua => $ua,
    Monitor => {Id => 1, AutoStopTimeout => 0, ControlAddress => '192.0.2.1', ControlDevice => 'x'},
  }, $class;
  return ($control, $ua);
}

# What the camera is sent for one call, with the autostop delay stubbed out.
sub sent {
  my ($control, $ua, $method, $params) = @_;
  no strict 'refs';
  no warnings 'redefine';
  local *{$class.'::autoStop'} = sub {};
  @{$ua->{reqs}} = ();
  $control->$method($params);
  return [@{$ua->{reqs}}];
}

# --- focusMan toggles the OSD -----------------------------------------------
{
  my ($control, $ua) = camera();
  my @osd = grep { /name=OSD/ } @{sent($control, $ua, 'focusMan', {})};
  check('first focusMan sends only OSD off', join('|', @osd),
    'param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=OSD off');
  my @overlay = grep { /setoverlayattr/ } @{sent($control, $ua, 'moveConUp', {tiltspeed => 63})};
  check('no move overlay is drawn while the OSD is off', scalar(@overlay), 0);
  @osd = grep { /name=OSD/ } @{sent($control, $ua, 'focusMan', {})};
  check('second focusMan sends only OSD on', join('|', @osd),
    'param.cgi?cmd=setoverlayattr&-region=1&-show=1&-name=OSD on');
  @overlay = grep { /setoverlayattr/ } @{sent($control, $ua, 'moveConUp', {tiltspeed => 63})};
  check('move overlay is drawn again once the OSD is on', scalar(@overlay), 1);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
