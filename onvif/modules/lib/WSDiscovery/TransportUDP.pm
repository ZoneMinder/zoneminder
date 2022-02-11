# ==========================================================================
#
# Perl WS-Discovery implementation
# Copyright (C) 2014  Jan M. Hochstein
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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

# 20 times 200 msec = 4sec timeout
use constant WAIT_TIME => 200.0;
use constant WAIT_COUNT => 20;

SOAP::WSDL::Factory::Transport->register( 'soap.udp' => __PACKAGE__ );

my %code_of :ATTR(:name<code>           :default<()>);
my %status_of :ATTR(:name<status>       :default<()>);
my %message_of :ATTR(:name<message>     :default<()>);
my %is_success_of :ATTR(:name<is_success> :default<()>);

my %local_addr_of :ATTR(:name<local_addr> :init_arg<local_addr> :default<()>);
my $net_interface;

# create methods normally inherited from SOAP::Client
SUBFACTORY: {
    no strict qw(refs);
    foreach my $method ( qw(code message status is_success) ) {
        *{ $method } = *{ "get_$method" };
    }
}

# override to receive more than one response
sub _notify_response
{
#  my ($transport, $response) = @_;
  
}

sub set_net_interface {
	my $self = shift;
	$net_interface = shift;
}

sub send_multi() {
  my ($self, $address, $port, $utf8_string) = @_;

  my $destination = $address . ':' . $port;
  my $socket = IO::Socket::Multicast->new(
			PROTO => 'udp',
      LocalPort=>$port,
			PeerAddr=>$destination,
			ReuseAddr=>1
			) or die 'Cannot open multicast socket to ' . ${address} . ':' . ${port};
	$_ = $socket->mcast_if($net_interface) if $net_interface;

  my $bytes = $utf8_string;
  utf8::encode($bytes);
      
  $socket->mcast_ttl(1);
  $socket->send($bytes);
}

sub receive_multi() {
  my ($self, $address, $port) = @_;
  my $data = undef;
  
  my $socket = IO::Socket::Multicast->new(
			PROTO => 'udp',
      LocalPort=>$port,
			ReuseAddr=>1);
	$socket->mcast_add($address, $net_interface);
  
  my $readbits = '';
  vec($readbits, $socket->fileno, 1) = 1;
  
  if ( select($readbits, undef, undef, WAIT_TIME/1000) ) {
     $socket->recv($data, 9999);
     return $data;
  }
  return undef;
}

sub receive_uni() {
  my ($self, $address, $port, $localaddr) = @_;
  my $data = undef;
  
  my $socket = IO::Socket::Multicast->new(
			PROTO => 'udp',
			LocalAddr => $localaddr,
			LocalPort=>$port,
			ReuseAddr=>1
			);
      
	$socket->mcast_add($address, $net_interface);
  
  my $readbits = '';
  vec($readbits, $socket->fileno, 1) = 1;
  
  if ( select($readbits, undef, undef, WAIT_TIME/1000) ) {
     $socket->recv($data, 9999);
     return $data;
  }
  return undef;
}
 
sub send_receive {
	my ($self, %parameters) = @_;
	my ($envelope, $soap_action, $endpoint, $encoding, $content_type) =
		@parameters{qw(envelope action endpoint encoding content_type)};

	my ($address,$port) = ($endpoint =~ /([^:\/]+):([0-9]+)/);

#warn "address = ${address}";
#warn "port = ${port}";

	$self->send_multi($address, $port, $envelope);

	my $localaddr = $self->get_local_addr();
#warn "localddr $localaddr";

	my ($response, $last_response);
	my $wait = WAIT_COUNT;
	while ( $wait >= 0 ) {
		if ( $localaddr ) {
			if ( $response = $self->receive_uni($address, $port, $localaddr) ) {
				$last_response = $response;
				$self->_notify_response($response);
			}
			$wait --;
		}
		if ( $response = $self->receive_multi($address, $port) ) {
			$last_response = $response;
			$self->_notify_response($response);
		}
		$wait --;
	}

	if ( $last_response ) {
		$self->set_code();
		$self->set_message('');
		$self->set_is_success(1);
		$self->set_status('OK');
	} else {
		$self->set_code();
		$self->set_message('Timed out waiting for response');
		$self->set_is_success(0);
		$self->set_status('TIMEOUT');
	}

	return $last_response;
}

1;
__END__
