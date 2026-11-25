#!/usr/bin/perl
#
# ZoneMinder::Control::Serial_HEX_PelcoD open() must report success.
#
# zmcontrol logs "Failed to open control" (and the direct protocol mode dies)
# when open() is false, and reopens a control whose state is not 'open' before
# every command.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

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
sub new { my ($class, $code, $body) = @_; return bless {code => $code, body => $body}, $class; }
sub is_success { $_[0]->{code} == 200 }
sub status_line { $_[0]->{code} == 200 ? '200 OK' : '404 Not Found' }
sub content { $_[0]->{body} }
sub headers { return {}; }

package FakeUA;
# $replies is a list of [code, body] given out in turn.
sub new { my ($class, @replies) = @_; return bless {reqs => [], replies => [@replies]}, $class; }
sub agent {}
sub cookie_jar {}
sub credentials {}
sub get {
  my ($self, $url) = @_;
  push @{$self->{reqs}}, $url;
  my $reply = shift @{$self->{replies}} or die "unexpected request $url";
  return FakeResponse->new(@$reply);
}

package main;

our $active_ua;
BEGIN {
  no warnings 'redefine';
  require LWP::UserAgent;
  *LWP::UserAgent::new = sub { return $active_ua; };
}

use ZoneMinder::Control::Serial_HEX_PelcoD;

sub camera {
  my @replies = @_;
  $active_ua = FakeUA->new(@replies);
  my $control = bless {
    Monitor => {
      ControlAddress => 'admin:secret@192.168.1.99',
      ControlDevice => '1:1:Http Server',
      AutoStopTimeout => 0,
    },
  }, 'ZoneMinder::Control::Serial_HEX_PelcoD';
  return $control;
}

{
  my $control = camera([200, "PTZ_VENDOR='DYNACOLOR,UNKNWN'\n"]);
  my $result = $control->open();
  check('open succeeds on a camera that answers PTZ_VENDOR', $result ? 1 : 0, 1);
  check('state is open', $control->{state}, 'open');
}

{
  my $control = camera([404, ''], [200, "PTZ_VENDOR='DYNACOLOR,UNKNWN'\n"]);
  my $result = $control->open();
  check('open succeeds on an older camera that answers the plain query', $result ? 1 : 0, 1);
  check('older camera state is open', $control->{state}, 'open');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
