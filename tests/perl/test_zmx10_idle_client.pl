#!/usr/bin/perl
# A client that connects to the server socket and sends nothing must not wedge
# the daemon. The read from an accepted client is bounded by CLIENT_READ_TIMEOUT,
# so the one second state poll keeps running and the alarm output is still
# switched on, and afterwards the server answers a normal request again.
use strict;
use warnings;
use FindBin;
use Cwd;
use IO::Socket::UNIX;
use POSIX qw(WNOHANG);
use Time::HiRes qw(sleep time);
require "$FindBin::Bin/zmx10_harness.pl";
our ($Dir, $Pid);

zmx10_start(rows => [ {Id=>1, Name=>'m1', Activation=>'', AlarmInput=>'', AlarmOutput=>'7'} ]);

# timeout(1) signals only this process, so on failure take the daemon down too.
$SIG{TERM} = sub {
  if ( $Pid ) {
    kill('TERM', $Pid);
    for ( 1 .. 20 ) {
      last if waitpid($Pid, WNOHANG) != 0;
      sleep(0.1);
    }
    kill('KILL', $Pid);
    waitpid($Pid, 0);
    $Pid = 0;
  }
  exit(1);
};

sleep(2);   # the first poll records the initial IDLE state

my $idle = IO::Socket::UNIX->new(Peer => "$Dir/zmx10.sock")
  or die "idle connect: $!";
my $connected = time();

sleep(1.5);                 # the server accepts the silent client
zmx10_set_state(3);         # ALARM

# Polling continues while the idle client is open: the alarm output comes on.
# The read timeout expires 5s after the accept, then the next poll fires.
my $deadline = time() + 9;
my $on = 0;
while ( time() < $deadline ) {
  last if ($on = zmx10_calls('APPLIANCE_ON'));
  sleep(0.2);
}
check('daemon is not stuck on the idle client (alarm output switched on)', $on == 1);

# Well past the 5 second client timeout, a normal request is still answered.
my $until = $connected + 7 - time();
sleep($until) if $until > 0;
my $reply;
eval {
  local $SIG{ALRM} = sub { die "status timed out\n" };
  alarm(6);
  $reply = zmx10_send("status\n");
  alarm(0);
};
alarm(0);
check('server answers a normal request after the client timeout', defined($reply));
check('server is still running', zmx10_alive());

close($idle);
finish();
