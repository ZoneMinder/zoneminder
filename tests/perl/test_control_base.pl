#!/usr/bin/perl
#
# ZoneMinder::Control base class.
#
# * Monitor::Control() gives the control object its monitor, so loadMonitor
#   skipped the seconds to microseconds conversion of the Auto Stop Timeout and
#   the modules usleep()ed 1.5 for a 1.5 second timeout.
# * The user and password in a monitor Path are url-encoded, but were used as
#   they stand, so an @ written as %40 failed to log in. A user with no
#   password was dropped, and a camera with no login got an empty ':' userinfo.
# * A ControlAddress user name was not url-decoded, though the password was.
# * get_realm only retried on the exact status line '401 Unauthorized', not on
#   a camera that words it '401 Authorization Required'.
#
# Run as: perl tests/perl/test_control_base.pl
#
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

package FakeUA;
sub new { return bless {creds => [], gets => 0}, shift; }
sub credentials { my $self = shift; push @{$self->{creds}}, [@_]; }
sub get {
  my ($self, $url) = @_;
  $self->{gets}++;
  return $self->{gets} == 1 ? $self->{first} : $self->{second};
}

package FakeResponse;
sub new { my ($class, %a) = @_; return bless {%a}, $class; }
sub is_success { $_[0]{code} == 200 }
sub code { $_[0]{code} }
sub status_line { $_[0]{code}.' '.$_[0]{msg} }
sub content { '' }
sub headers { return {'www-authenticate' => 'Digest realm="cam1", nonce="abc"'}; }

package main;

# AutoStopTimeout through Monitor::Control(), the way zmcontrol.pl gets it
my $dir = tempdir(CLEANUP => 1);
make_path("$dir/ZoneMinder/Control");
open(my $fh, '>', "$dir/ZoneMinder/Control/TestProto.pm") or die $!;
print $fh "package ZoneMinder::Control::TestProto;\nuse parent 'ZoneMinder::Control';\n1;\n";
close($fh);
unshift @INC, $dir;
{
  no warnings qw(redefine once);
  *ZoneMinder::Control::find_one = sub { return bless {Id => 3, Name => 'Base', Protocol => 'TestProto'}, 'ZoneMinder::Control'; };
  *ZoneMinder::Control::clone = sub { return bless {%{$_[0]}}, ref($_[0]); };
  *ZoneMinder::Monitor::clone = sub { return bless {%{$_[0]}}, ref($_[0]); };
}
my $monitor = bless {Id => 7, Name => 'test', ControlId => 3, AutoStopTimeout => '1.50'}, 'ZoneMinder::Monitor';
my $control = $monitor->Control();
$control->loadMonitor();
check('AutoStopTimeout from Monitor::Control is in microseconds', $control->{Monitor}{AutoStopTimeout}, 1500000);
$control->loadMonitor();
check('a second loadMonitor does not convert again', $control->{Monitor}{AutoStopTimeout}, 1500000);

sub control_with {
  my ($mon) = @_;
  return bless {ua => FakeUA->new, Monitor => $mon}, 'ZoneMinder::Control';
}

# Path credentials
my $c = control_with({Path => 'rtsp://admin:p%40ss@cam/stream'});
$c->parse_Path();
check('Path password is url-decoded', $c->{password}, 'p@ss');
check('Path user is kept', $c->{username}, 'admin');

$c = control_with({Path => 'rtsp://me%40example.com:pw@cam/stream'});
$c->parse_Path();
check('Path user is url-decoded', $c->{username}, 'me@example.com');

$c = control_with({Path => 'rtsp://admin@cam/stream'});
$c->parse_Path();
check('Path user without a password is kept', $c->{username}, 'admin');
check('Path user without a password has an empty password', $c->{password}, '');

$c = control_with({Path => 'rtsp://cam/stream', User => 'U', Pass => 'P@s'});
$c->parse_Path();
check('monitor User and Pass are used when the Path has none', $c->{username}.'/'.$c->{password}, 'U/P@s');
check('monitor User and Pass are encoded into BaseURL', $c->{BaseURL}, 'http://U:P%40s@cam/');

$c = control_with({Path => 'rtsp://cam/stream'});
$c->parse_Path();
check('no login at all leaves BaseURL without userinfo', $c->{BaseURL}, 'http://cam/');

# ControlAddress credentials
$c = control_with({ControlAddress => 'me%40example.com:pw@cam:8080'});
$c->parse_ControlAddress();
check('ControlAddress user is url-decoded', $c->{username}, 'me@example.com');
check('ControlAddress password is kept', $c->{password}, 'pw');
check('ControlAddress port is kept', $c->{port}, 8080);

$c = control_with({ControlAddress => 'admin:pa@ss@cam'});
$c->parse_ControlAddress();
check('ControlAddress password with a raw @ is kept', $c->{password}, 'pa@ss');

# get_realm on a 401 with another reason phrase
$c = control_with({});
$c->{username} = 'admin';
$c->{password} = 'pw';
$c->{host} = 'cam';
$c->{port} = 80;
$c->{ua}{first} = FakeResponse->new(code => 401, msg => 'Authorization Required');
$c->{ua}{second} = FakeResponse->new(code => 200, msg => 'OK');
check('get_realm retries with the realm after 401 Authorization Required', $c->get_realm('http://cam/x'), 1);
check('get_realm learned the realm', $c->{realm}, 'cam1');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
