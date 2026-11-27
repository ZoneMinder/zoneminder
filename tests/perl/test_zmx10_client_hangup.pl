#!/usr/bin/perl
# A client that connects, sends a partial request and hangs up before the
# server writes its reply made dPrint() write to a socket whose other end was
# closed. That write raised SIGPIPE and killed the zmx10 daemon, so X10
# triggering stopped until it was restarted. The server must ignore SIGPIPE
# and keep serving.
use strict;
use warnings;
use FindBin;
use Cwd;
use IO::Socket::UNIX;
require "$FindBin::Bin/zmx10_harness.pl";
our $Dir;

zmx10_start(rows => [ {Id=>1, Name=>'m1', Activation=>'', AlarmInput=>'', AlarmOutput=>'7'} ]);
sleep(1);

sub hangup_client {
  my $s = IO::Socket::UNIX->new(Peer => "$Dir/zmx10.sock") or return 0;
  print $s 'bogus';              # no newline: the server reads to EOF
  close($s);                     # the reply goes to a socket that is gone
  return 1;
}

my $opened = 0;
for (1 .. 3) {
  $opened += hangup_client();
  sleep(0.5);
}
check('three clients connected and hung up', $opened == 3);

sleep(1);
check('zmx10 survives clients that hang up before the reply', zmx10_alive());
check('zmx10 still answers a normal request',
  defined(zmx10_send("status\n")));

zmx10_set_state(3);
sleep(2);
check('alarm still switches the device on after the hangups',
  zmx10_calls('APPLIANCE_ON') == 1);

finish();
