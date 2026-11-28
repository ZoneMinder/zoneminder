#!/usr/bin/perl
#
# ZoneMinder::Control::Dahua presetSet and an unlabelled preset.
#
# The web ui deletes the ControlPresets row when a preset is saved without a
# label. presetSet then sent SetPresetName with no name, which goes out as
# arg2=0 and named the preset "0" on the camera. It now sends SetPreset only,
# and still sends the name when there is a label.
#
# Run as: perl tests/perl/test_dahua_preset_set_label.pl
# ZM_CONTROL_LIB may name another scripts/ZoneMinder/lib to test instead.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
use lib ($ENV{ZM_CONTROL_LIB} // ());

use ZoneMinder::Control::Dahua;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '(undef)') eq (defined $want ? $want : '(undef)')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

package FakeSth;
sub new { return bless {row => $_[1]}, $_[0]; }
sub execute { 1 }
sub fetchrow_hashref { $_[0]->{row} }

package FakeDbh;
sub new { return bless {row => $_[1]}, $_[0]; }
sub prepare { FakeSth->new($_[0]->{row}) }

package FakeResponse;
sub new { return bless {}, $_[0]; }
sub is_success { 1 }
sub status_line { '200 OK' }

package FakeUserAgent;
sub new { return bless {reqs => []}, $_[0]; }
sub request { push @{$_[0]->{reqs}}, $_[1]->uri()->as_string(); return FakeResponse->new(); }

package main;

$ZoneMinder::Control::Dahua::PROTOCOL = 'http://';
$ZoneMinder::Control::Dahua::ADDRESS = '192.0.2.1:80';

# The requests presetSet makes for preset 3, given the stored ControlPresets row.
sub preset_set {
  my ($row) = @_;
  no warnings 'redefine';
  local *ZoneMinder::Control::Dahua::zmDbConnect = sub { FakeDbh->new($row) };
  my $ua = FakeUserAgent->new();
  my $control = bless {
    Monitor => {Id => 1, ControlAddress => '192.0.2.1'},
    state => 'open',
    dahua_channel_number => '0',
    ua => $ua,
  }, 'ZoneMinder::Control::Dahua';
  $control->presetSet({preset => 3});
  return @{$ua->{reqs}};
}

my $base = 'http://192.0.2.1:80/cgi-bin/ptz.cgi?action=start&channel=0&code=';

my @none = preset_set(undef);
check('no label row: one request', scalar(@none), 1);
check('no label row: sends SetPreset', $none[0], $base.'SetPreset&arg1=0&arg2=3&arg3=0&arg4=0');

my @empty = preset_set({Label => ''});
check('empty label: one request', scalar(@empty), 1);

my @null = preset_set({Label => undef});
check('null label: one request', scalar(@null), 1);

my @named = preset_set({Label => 'Front door'});
check('label: two requests', scalar(@named), 2);
check('label: sends SetPreset first', $named[0], $base.'SetPreset&arg1=0&arg2=3&arg3=0&arg4=0');
check('label: names the preset', $named[1], $base.'SetPresetName&arg1=3&arg2=Front%20door&arg3=0&arg4=0');

my @zero = preset_set({Label => '0'});
check('label 0: still named', scalar(@zero), 2);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
