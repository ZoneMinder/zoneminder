#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

use ZoneMinder::Monitor;
use ZoneMinder::Control;

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

my $dir = tempdir(CLEANUP => 1);
make_path("$dir/ZoneMinder/Control");
open(my $fh, '>', "$dir/ZoneMinder/Control/TestProto.pm") or die $!;
print $fh "package ZoneMinder::Control::TestProto;\nuse parent 'ZoneMinder::Control';\n1;\n";
close($fh);
unshift @INC, $dir;

my $row;
{
  no warnings 'redefine';
  *ZoneMinder::Control::find_one = sub { return bless {%$row}, 'ZoneMinder::Control'; };
  *ZoneMinder::Control::clone = sub { return bless {%{$_[0]}}, ref($_[0]); };
  *ZoneMinder::Monitor::clone = sub { return bless {%{$_[0]}}, ref($_[0]); };
}

sub control_for {
  ($row) = @_;
  my $monitor = bless {Id => 7, Name => 'test', ControlId => 3}, 'ZoneMinder::Monitor';
  return $monitor->Control();
}

my $control = control_for({Id => 3, Name => 'Other', Protocol => 'TestProto'});
check('the module named by Protocol is loaded', ref($control), 'ZoneMinder::Control::TestProto');
check('Protocol is kept', $control->{Protocol}, 'TestProto');

$control = control_for({Id => 3, Name => 'TestProto', Protocol => ''});
check('an empty Protocol falls back to the module named by Name', ref($control), 'ZoneMinder::Control::TestProto');
check('the Protocol that was used is recorded for zmcontrol.pl', $control->{Protocol}, 'TestProto');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
