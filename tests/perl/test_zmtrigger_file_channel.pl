#!/usr/bin/perl
#
# The file channel (the Chan3 example in zmtrigger.pl, mode 'w') has to count
# as connected once it is open.  zmtrigger only writes alarm messages to
# connections where canWrite() is true, which needs the "connected" state, so a
# file channel left in the "open" state never got a single message.
#
# Run as: perl tests/perl/test_zmtrigger_file_channel.pl
#
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";
BEGIN { require "$FindBin::Bin/zmtrigger_stubs.pm"; ZmtStubs::install(); }
use ZoneMinder::Trigger::Channel::File;
use ZoneMinder::Trigger::Connection;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

my $dir = tempdir(CLEANUP => 1);
my $path = "$dir/zmtrigger.out";
open(my $fh, '>', $path) or die; close($fh);

my $conn = ZoneMinder::Trigger::Connection->new(name => 'file', mode => 'w',
  channel => ZoneMinder::Trigger::Channel::File->new(path => $path));
$conn->open();
check('open file channel can be written to', $conn->canWrite() ? 1 : 0, 1);

# What the main loop does
$conn->putMessages(['1|on|123|7', '1|off|125|7']) if $conn->canWrite();
$conn->close();
open($fh, '<', $path) or die;
my $content = do { local $/; <$fh> };
close($fh);
check('alarm messages reached the file', $content, "1|on|123|7\n1|off|125|7\n");

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
