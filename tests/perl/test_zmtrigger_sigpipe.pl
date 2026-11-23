#!/usr/bin/perl
#
# A client that disconnects before the daemon writes to it makes the write
# raise SIGPIPE.  Without a handler the default action kills zmtrigger, so a
# single vanished client can take the whole daemon down.  zmtrigger must
# ignore SIGPIPE and keep running.
#
# Run as: perl tests/perl/test_zmtrigger_sigpipe.pl
#
use strict;
use warnings;
use FindBin;
require "$FindBin::Bin/zmtrigger_harness.pm";

my $r = run_zmtrigger(<<'EOM');
package ZmtSim;
our $max_ticks = 20;
our %rows = (
  1 => { Id => 1, Name => 'door', Deleted => 0, Capturing => 'Always', Recording => 'None', ServerId => 0 },
);
sub connect_ok { return 1; }
sub mem { my ($id, $f) = @_; return $f eq 'state' ? 1 : 0; }
sub tick {
  # The client goes away and its next write raises SIGPIPE
  if ($_[0] == 3) { kill 'PIPE', $$; }
}
1;
EOM

check('daemon survived SIGPIPE and ran to the end', $r->{exit} . '/' . $r->{signal}, '0/0');
finish();
