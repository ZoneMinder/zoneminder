#!/usr/bin/perl
#
# ZoneMinder::Trigger::Connection::Example translates '|' to '-' on output and
# back on input.  zmtrigger hands the same message list to every output
# connection, so putMessages must not edit the list it is given, and must
# translate every '|' (getMessages translates every '-').
#
# Run as: perl tests/perl/test_zmtrigger_example_connection.pl
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
BEGIN { require "$FindBin::Bin/zmtrigger_stubs.pm"; ZmtStubs::install(); }
use ZoneMinder::Trigger::Connection;
use ZoneMinder::Trigger::Connection::Example;

package FakeChannel;
sub new { return bless { written => '' }, shift; }
sub write { $_[0]{written} .= $_[1]; return 1; }
sub fileno { return 3; }

package main;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

my $made = eval {
  ZoneMinder::Trigger::Connection::Example->new(name => 'ex', mode => 'rw', channel => FakeChannel->new());
};
check('constructor takes the same key/value pairs as Connection', $made ? 'ok' : "died: $@", 'ok');

# Build it the base way so the output checks run either way
my $channel = FakeChannel->new();
my $conn = bless(ZoneMinder::Trigger::Connection->new(name => 'ex', mode => 'rw', channel => $channel),
  'ZoneMinder::Trigger::Connection::Example');
my @messages = ('1|on|123|7', '1|off|125|7');
$conn->putMessages(\@messages);
check('every separator is translated', $channel->{written}, "1-on-123-7\n1-off-125-7\n");
check('the shared message list is left alone', join(',', @messages), '1|on|123|7,1|off|125|7');

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
