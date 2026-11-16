#!/usr/bin/perl
#
# Tests that ZoneMinder::Trigger::Connection::getMessages keeps an unfinished
# line until the rest arrives, instead of handing both halves to zmtrigger as
# two separate messages.
#
# Run as: perl tests/perl/test_trigger_partial_message.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

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

sub run {
  my $conn = ZoneMinder::Trigger::Connection->new(name => 't', mode => 'r',
    channel => FakeChannel->new(@_));
  my @out;
  while (defined(my $m = $conn->getMessages())) { push @out, @$m; }
  return join(',', @out), $conn;
}

check('whole lines', (run("1|on|1|a\n2|cancel\n"))[0], '1|on|1|a,2|cancel');
check('crlf', (run("1|on|1|a\r\n2|cancel\r\n"))[0], '1|on|1|a,2|cancel');
check('split mid message', (run("1|on|1|mo", "tion|t\n"))[0], '1|on|1|motion|t');
check('split before newline', (run("1|on|1|a", "\n"))[0], '1|on|1|a');
check('split across two messages', (run("1|cancel\n2|on|", "1|b\n"))[0], '1|cancel,2|on|1|b');
check('unterminated at end of input', (run("1|on|1|a"))[0], '1|on|1|a');

my ($none, $conn) = run();
$conn->{channel} = FakeChannel->new("1|on|1|a");
check('unterminated is held back', scalar(@{$conn->getMessages()}), 0);
check('held message not due yet', $conn->timedActions(), undef);
$conn->{partial_time} -= 2;
check('held message released after a second', join(',', @{$conn->timedActions()}), '1|on|1|a');
check('nothing left', $conn->timedActions(), undef);

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
