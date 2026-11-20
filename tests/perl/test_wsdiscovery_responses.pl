#!/usr/bin/perl
#
# WSDiscovery::TransportUDP::send_receive must hand on every ProbeMatch that arrives in its wait.
# It opened a new receive socket for each 200 ms pass, read one datagram and closed it, and
# stopped after 21 passes. Answers queued behind the first one or arriving between two sockets
# were lost, and every answer used up a pass, so with many cameras the probe stopped after about
# 0.5 s and the ONVIF probe listed only some of them.
#
# The multicast calls are replaced so this runs on loopback: each "camera" answers from its own
# socket to 127.0.0.1 on the probe port, either all at the same moment or spread over 500 ms
# (the WS-Discovery APP_MAX_DELAY), counted from when send_receive has opened its sockets.
#
# Run as: perl tests/perl/test_wsdiscovery_responses.pl
#
use strict;
use warnings;
use FindBin;
use IO::Socket::INET;
use Time::HiRes qw(usleep);
use lib "$FindBin::Bin/../../onvif/modules/lib";

my $have = eval { require WSDiscovery::TransportUDP; 1 };
if (!$have) {
  print "1..0 # SKIP WSDiscovery::TransportUDP does not load here: $@";
  exit 0;
}

my $port = 20000 + $$ % 20000;
my @delays_ms;
my @children;
my $go;
{
  no warnings qw(redefine once prototype);
  *IO::Socket::Multicast::mcast_if = sub { 1 };
  *IO::Socket::Multicast::mcast_ttl = sub { 1 };
  # called once the receive sockets are open: let the cameras answer
  *IO::Socket::Multicast::mcast_add = sub { close($go) if $go; undef $go; 1 };
  # one process per answer time: cameras that answer at the same time send back to back
  *IO::Socket::Multicast::mcast_send = sub {
    my %cameras_at;
    push @{$cameras_at{$delays_ms[$_ - 1]}}, $_ for 1 .. @delays_ms;
    pipe(my $wait, $go) or die "pipe: $!";
    foreach my $delay (keys %cameras_at) {
      my $pid = fork();
      die "fork: $!" if !defined $pid;
      if (!$pid) {
        close($go);
        my %socket_of = map {
          $_ => (IO::Socket::INET->new(Proto => 'udp', PeerAddr => '127.0.0.1', PeerPort => $port)
            or die "camera socket: $!")
        } @{$cameras_at{$delay}};
        sysread($wait, my $eof, 1);
        usleep(1000 * $delay);
        $socket_of{$_}->send("<ProbeMatch n=\"$_\"/>") for @{$cameras_at{$delay}};
        exit 0;
      }
      push @children, $pid;
    }
    close($wait);
    return length $_[1];
  };
}

my @responses;
{
  no warnings qw(redefine once);
  *WSDiscovery::TransportUDP::_notify_response = sub { push @responses, $_[1]; };
}

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++; print "ok - $name\n";
  } else {
    $failures++; print "NOT OK - $name\n  got:  $got\n  want: $want\n";
  }
}

sub probe {
  @delays_ms = @_;
  @responses = ();
  @children = ();
  my $transport = WSDiscovery::TransportUDP->new();
  $transport->send_receive(envelope => '<Probe/>', endpoint => "soap.udp://239.255.255.250:$port");
  close($go) if $go;
  waitpid($_, 0) for @children;
  my %seen = map { $_ => 1 } @responses;
  return (scalar(@responses).' answers, '.scalar(keys %seen).' cameras', $transport->get_status());
}

my ($got, $status) = probe(0, 0, 0);
check('3 cameras answering at the same moment are all passed on', $got, '3 answers, 3 cameras');
check('the probe succeeded', $status, 'OK');

($got, $status) = probe(map { $_ * 500 / 30 } 0 .. 29);
check('30 cameras answering over 500 ms are all passed on', $got, '30 answers, 30 cameras');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
