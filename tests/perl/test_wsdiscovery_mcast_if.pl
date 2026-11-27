#!/usr/bin/perl
#
# WSDiscovery::TransportUDP::send_multi must choose the probe's interface before anything picks a
# route. It connected the socket (PeerAddr) and set mcast_if afterwards; a connected socket keeps
# the route chosen at connect time, so on a host with more than one network the WS-Discovery Probe
# left through the default route and the web UI's ONVIF probe found no camera on the interface the
# user chose.
#
# IO::Socket::Multicast is replaced by a recorder, so this runs without a network.
#
# Run as: perl tests/perl/test_wsdiscovery_mcast_if.pl
#
use strict;
use warnings;
use FindBin;
use Socket ();
use lib "$FindBin::Bin/../../onvif/modules/lib";

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

my $have = eval { require WSDiscovery::TransportUDP; 1 };
if (!$have) {
  print "1..0 # SKIP WSDiscovery::TransportUDP does not load here: $@";
  exit 0;
}

# Record what send_multi does with its socket, in order.
my @calls;
{
  no warnings qw(redefine once prototype);
  *IO::Socket::Multicast::new = sub {
    my ($class, %args) = @_;
    push @calls, 'new('.join(',', map { "$_=$args{$_}" } sort grep { $_ ne 'PROTO' } keys %args).')';
    return bless {}, $class;
  };
  *IO::Socket::Multicast::mcast_if = sub { push @calls, "mcast_if($_[1])"; return 1; };
  *IO::Socket::Multicast::mcast_ttl = sub { push @calls, "mcast_ttl($_[1])"; return 1; };
  *IO::Socket::Multicast::mcast_send = sub {
    my $dest = $_[2];
    # A packed sockaddr_in is the form IO::Socket::Multicast 1.12 sends correctly.
    $dest = length($dest) == 16 ? join(':', Socket::inet_ntoa((Socket::unpack_sockaddr_in($dest))[1]),
      (Socket::unpack_sockaddr_in($dest))[0]) : "unpacked $dest";
    push @calls, "mcast_send($dest)";
    return length $_[1];
  };
  *IO::Socket::Multicast::send = sub { push @calls, 'send'; return length $_[1]; };
  *IO::Socket::Multicast::connect = sub { push @calls, 'connect'; return 1; };
}

my $transport = WSDiscovery::TransportUDP->new();
$transport->set_net_interface('eth1');
$transport->send_multi('239.255.255.250', 3702, '<Probe/>');

check('the socket is not connected (no PeerAddr)', (grep { /PeerAddr|connect/ } @calls) ? 'connected' : 'not connected',
  'not connected');
check('the interface is set, then the probe goes to the group',
  join(' ', grep { /^mcast_(if|send)/ || $_ eq 'send' } @calls), 'mcast_if(eth1) mcast_send(239.255.255.250:3702)');

@calls = ();
$transport->set_net_interface(undef);
$transport->send_multi('239.255.255.250', 3702, '<Probe/>');
check('without an interface, nothing is set and the probe still goes out',
  join(' ', grep { /^mcast_(if|send)/ || $_ eq 'send' } @calls), 'mcast_send(239.255.255.250:3702)');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
