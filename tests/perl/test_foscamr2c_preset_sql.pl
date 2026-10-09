#!/usr/bin/perl
#
# ZoneMinder::Control::FOSCAMR2C must not put the preset into SQL text. The preset comes
# from the web request; presetGoto/presetSet interpolated it into
# "SELECT Label FROM ControlPresets WHERE Preset = $preset" and sent the selected Label to
# the camera, so a viewer could read any value from the database. refs GHSA-qcm7-vq92-f86f
#
# Run as: sudo -u www-data perl tests/perl/test_foscamr2c_preset_sql.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Control::FOSCAMR2C;

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

# A database handle that records each statement and the values bound to it.
package FakeSth;
sub new { my ($class, $log, $sql) = @_; return bless {log => $log, sql => $sql}, $class; }
sub execute { my $self = shift; push @{$self->{log}}, $self->{sql}.' ['.join(',', @_).']'; return 1; }
sub fetchrow_hashref { my $self = shift; return $self->{sql} =~ /ControlPresets`/ ? {Label => 'FrontDoor'} : undef; }
sub finish { return 1; }
package FakeDbh;
sub new { my ($class, $log) = @_; return bless {log => $log}, $class; }
sub prepare { my ($self, $sql) = @_; return FakeSth->new($self->{log}, $sql); }
package main;

my @sql;
my @sent;
{
  no warnings 'redefine';
  *ZoneMinder::Control::FOSCAMR2C::zmDbConnect = sub { return FakeDbh->new(\@sql); };
  *ZoneMinder::Control::FOSCAMR2C::sendCmd = sub { push @sent, $_[1]; return 1; };
}

sub run {
  my ($method, $preset) = @_;
  @sql = ();
  @sent = ();
  my $control = bless {Monitor => {Id => 7}}, 'ZoneMinder::Control::FOSCAMR2C';
  $control->$method({preset => $preset});
}

my $injected = "0/**/UNION/**/SELECT/**/Value/**/FROM/**/Config/**/WHERE/**/Name='ZM_AUTH_HASH_SECRET'";

run('presetGoto', 3);
check('presetGoto looks the preset up by monitor with bound values',
  $sql[0], 'SELECT `Label` FROM `ControlPresets` WHERE `MonitorId` = ? AND `Preset` = ? [7,3]');
check('presetGoto sends the stored label', $sent[0], 'CGIProxy.fcgi?cmd=ptzGotoPresetPoint&name=FrontDoor');

run('presetGoto', $injected);
check('presetGoto runs no SQL for a non-numeric preset', scalar(@sql), 0);
check('presetGoto sends nothing for a non-numeric preset', scalar(@sent), 0);

run('presetSet', 4);
check('presetSet binds every value',
  scalar(grep { /\$|'4'|= 4\b/ } @sql), 0);
check('presetSet stores the label with bound values',
  (grep { /^INSERT/ } @sql)[0], 'INSERT INTO `ControlPresetNames`(`Preset`, `Label2`) VALUES (?, ?) [4,FrontDoor]');

run('presetSet', $injected);
check('presetSet runs no SQL for a non-numeric preset', scalar(@sql), 0);
check('presetSet sends nothing for a non-numeric preset', scalar(@sent), 0);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
