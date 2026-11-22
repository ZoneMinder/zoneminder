#!/usr/bin/perl
#
# zmdc's server answers each client with cPrint(). If the client has already
# closed its end of the socket (zmdc.pl's own reader gives up after 30 seconds
# idle, a web request can time out), the write raised SIGPIPE and killed the
# zmdc server, leaving its daemons without a supervisor and a stale pid file.
# cPrint() now ignores SIGPIPE for that write and closes the dead client, so the
# server closing CLIENT afterwards does not raise it either.
#
# cPrint is taken from zmdc.pl.in and run in a child against a socket whose
# other end is closed.
#
# Run as: perl tests/perl/test_zmdc_cprint_client_gone.pl
#
use strict;
use warnings;
use FindBin;
use Socket;

my $src_file = $ENV{ZMDC_SRC} || "$FindBin::Bin/../../scripts/zmdc.pl.in";
open(my $fh, '<', $src_file) or die "Can't read $src_file: $!";
my $src = do { local $/; <$fh> };
close($fh);
my ($cprint) = $src =~ /^(sub cPrint \{.*?^\})/ms or die "no cPrint in $src_file";

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

# The server's accepted CLIENT is not autoflushed, so a short reply is only
# buffered by print and written when CLIENT is closed; try both.
for my $autoflush (1, 0) {
  socketpair(my $server_end, my $client_end, AF_UNIX, SOCK_STREAM, PF_UNSPEC) or die "socketpair: $!";
  close($client_end);

  my $pid = fork();
  die "fork: $!" if !defined $pid;
  if (!$pid) {
    open(CLIENT, '>&', $server_end) or exit(3);
    select((select(CLIENT), $| = 1)[0]) if $autoflush;
    eval $cprint;
    exit(4) if $@;
    cPrint("running\n");
    cPrint("still running\n");
    # The server closes CLIENT after every request.
    close(CLIENT);
    exit(0);
  }
  waitpid($pid, 0);
  my $status = $?;
  check('zmdc survives writing to a client that has gone'.($autoflush ? '' : ' (buffered)'),
    ($status & 127) ? 'signal '.($status & 127) : 'exit '.($status >> 8), 'exit 0');
}

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
