#!/usr/bin/perl
#
# Tests that an unterminated line held by
# ZoneMinder::Trigger::Connection::getMessages is bounded in size, while
# complete lines (including ones that follow a very long line) still arrive.
#
# Run as: perl tests/perl/test_trigger_partial_cap.pl
#
use strict;
use warnings;
use FindBin;

my $root;
BEGIN { $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../.."; }
use lib "$root/scripts/ZoneMinder/lib";

BEGIN {
  # Connection.pm only needs a version and the log functions; avoid the generated modules
  $INC{'ZoneMinder/Base.pm'} = $INC{'ZoneMinder/Logger.pm'} = 1;
  package ZoneMinder::Base;
  our $VERSION = '1';
  package ZoneMinder::Logger;
  sub import { no strict 'refs'; my $c = caller; *{"${c}::$_"} = sub {} for qw(Debug Info Warning Error Fatal); }
}

use ZoneMinder::Trigger::Connection;

package FakeChannel;
sub new { my $c = shift; return bless { chunks => [@_] }, $c; }
sub read { my $s = shift; return shift @{$s->{chunks}}; }

package main;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '<undef>') eq (defined $want ? $want : '<undef>')) {
    $passes++; print "ok - $name\n";
  } else {
    $failures++; print "NOT OK - $name: got '".(defined $got ? $got : '<undef>')."' want '".(defined $want ? $want : '<undef>')."'\n";
  }
}

# 1. An unterminated stream must not be buffered without bound.
{
  my $conn = ZoneMinder::Trigger::Connection->new(name => 't', mode => 'r',
    channel => FakeChannel->new(map { 'A' x 1000 } 1 .. 20));
  $conn->getMessages() for 1 .. 20;
  check('unterminated stream does not grow without bound',
    length($conn->{partial} // '') <= 4096 ? 1 : 0, 1);
}

# 2. A normal partial followed by the rest still yields one joined message.
{
  my $prefix = 'x' x 100;
  my $conn = ZoneMinder::Trigger::Connection->new(name => 't', mode => 'r',
    channel => FakeChannel->new($prefix, "rest\n"));
  my @out;
  while (defined(my $m = $conn->getMessages())) { push @out, @$m; }
  check('partial followed by rest joins into one message', join(',', @out), $prefix.'rest');
}

# 3. A very long line must not swallow the complete message after it.
{
  my $conn = ZoneMinder::Trigger::Connection->new(name => 't', mode => 'r',
    channel => FakeChannel->new('B' x 10000, "\n", "1|on|1|a\n"));
  my @out;
  while (defined(my $m = $conn->getMessages())) { push @out, @$m; }
  check('message after a long line still delivered', $out[-1] // '<none>', '1|on|1|a');
}

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
