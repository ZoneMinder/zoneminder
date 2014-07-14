# ==========================================================================
#
# Perl WS-Discovery implementation
# Copyright (C) Jan M. Hochstein
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
#
# ==========================================================================
#
# UDP Transport for SOAP WS-Discovery to be used with SOAP::WSDL::Client
#
package WSDiscovery::TransportUDP;
use strict; 
use warnings;
use Class::Std::Fast::Storable;
use IO::Socket::Multicast;
use SOAP::WSDL::Factory::Transport;
use Time::HiRes qw( usleep );

use version; our $VERSION = qv('1.00.00');

# 50 times 100 msec = 5sec timeout
use constant WAIT_TIME => 100;
use constant WAIT_COUNT => 50;

SOAP::WSDL::Factory::Transport->register( 'soap.udp' => __PACKAGE__ );

my %code_of :ATTR(:name<code>           :default<()>);
my %status_of :ATTR(:name<status>       :default<()>);
my %message_of :ATTR(:name<message>     :default<()>);
my %is_success_of :ATTR(:name<is_success> :default<()>);

my %local_addr_of :ATTR(:name<local_addr> :init_arg<local_addr> :default<()>);


# create methods normally inherited from SOAP::Client
SUBFACTORY: {
    no strict qw(refs);
    foreach my $method ( qw(code message status is_success) ) {
        *{ $method } = *{ "get_$method" };
    }
}

sub send_multi() {
  my ($self, $address, $port, $data) = @_;

  my $destination = $address . ':' . $port;
  my $socket = IO::Socket::Multicast->new(PROTO => 'udp', 
      LocalPort=>$port, PeerAddr=>$destination, ReuseAddr=>1)
      
      or die 'Cannot open multicast socket to ' . ${address} . ':' . ${port};
      
  $socket->mcast_ttl(1);
  $socket->send($data);
}

sub receive_multi() {
  my ($self, $address, $port) = @_;
  my $data = undef;
  
  my $socket = IO::Socket::Multicast->new(PROTO => 'udp', 
      LocalPort=>$port, ReuseAddr=>1);
	$socket->mcast_add($address);
  
  $socket->recv($data, 9999);
  
  return $data;
}

sub receive_uni() {
  my ($self, $address, $port, $localaddr) = @_;
  my $data = undef;
  
  my $socket = IO::Socket::Multicast->new(PROTO => 'udp', 
      LocalAddr => $localaddr, LocalPort=>$port, ReuseAddr=>1);
      
	$socket->mcast_add($address);
  
  $socket->recv($data, 9999);
  
  return $data;
}
 
sub send_receive {
    my ($self, %parameters) = @_;
    my ($envelope, $soap_action, $endpoint, $encoding, $content_type) =
        @parameters{qw(envelope action endpoint encoding content_type)};

    my ($address,$port) = ($endpoint =~ /([^:\/]+):([0-9]+)/);

#    warn "address = ${address}";
#    warn "port = ${port}";

    $self->send_multi($address, $port, $envelope);

    my $localaddr = $self->get_local_addr();

    my $response;
    my $wait = WAIT_COUNT;
    while ($wait) {
      if($localaddr) {
        if($response = $self->receive_uni($address, $port, $localaddr)) {
          last;
        }
      }
      if($response = $self->receive_multi($address, $port)) {
        last;
      }
      msleep(WAIT_TIME);
      $wait --;
    }
    
    if($response) {
      $self->code();
      $self->message();
      $self->is_success(1);
      $self->status('OK');
    }
    else{
      $self->code();
      $self->message();
      $self->is_success(0);
      $self->status('TIMEOUT');
    }
    return $response;
}

1;
