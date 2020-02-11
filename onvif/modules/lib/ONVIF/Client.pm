# ==========================================================================
#
# ZoneMinder ONVIF Client module
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
# This module contains the implementation of the ONVIF client module
#

package ONVIF::Client;
use strict; 
use warnings;
use Class::Std::Fast;

use version; our $VERSION = qv('1.00.00');

## Transport
require SOAP::WSDL::Transport::HTTP;

## Serializer
require ONVIF::Serializer::SOAP11;
require ONVIF::Serializer::SOAP12;
require WSSecurity::SecuritySerializer;

## Deserializer
require ONVIF::Deserializer::XSD;

## ONVIF APIs
require ONVIF::Device::Interfaces::Device::DevicePort;
require ONVIF::Media::Interfaces::Media::MediaPort;
require ONVIF::PTZ::Interfaces::PTZ::PTZPort;

use Data::Dump qw(dump);

# ========================================================================
# Globals

my %namespace_map = (
  'http://www.onvif.org/ver10/device/wsdl'      => 'device',    
  'http://www.onvif.org/ver10/media/wsdl'       => 'media',   
  'http://www.onvif.org/ver20/imaging/wsdl'     => 'imaging',
  'http://www.onvif.org/ver20/analytics/wsdl'   => 'analytics',
  'http://www.onvif.org/ver10/deviceIO/wsdl'    => 'deviceio', 
  'http://www.onvif.org/ver10/ptz/wsdl'         => 'ptz',
  'http://www.onvif.org/ver10/events/wsdl'      => 'events', 
  'http://www.onvif.org/ver10/recording.wsdl'   => 'recording',
  'http://www.onvif.org/ver10/search.wsdl'      => 'search',
  'http://www.onvif.org/ver10/replay/wsdl'      => 'replay',
);

# ========================================================================
# Attributes

my %services_of      :ATTR(:default<{}>);

my %serializer_of    :ATTR();
my %soap_version_of  :ATTR(:default<('1.1')>);

# =========================================================================
# private methods

sub service {
  my ($self, $serviceName, $attr) = @_;
  #print "service: " . $services_of{${$self}}{$serviceName}{$attr} . "\n";
# Please note that the Std::Class::Fast docs say not to use ident.
  $services_of{ident $self}{$serviceName}{$attr};
}

sub set_service {
  my ($self, $serviceName, $attr, $value) = @_;
  $services_of{ident $self}{$serviceName}{$attr} = $value;
}

sub serializer {
  my ($self) = @_;
  $serializer_of{ident $self};
}

sub set_serializer {
  my ($self, $serializer) = @_;
  $serializer_of{ident $self} = $serializer;
}

sub soap_version {
  my ($self) = @_;
  $soap_version_of{ident $self};
}

sub set_soap_version {
  my ($self, $soap_version) = @_;
  $soap_version_of{ident $self} = $soap_version;

  # setting the soap version invalidates the serializer
  delete $serializer_of{ ident $self };
}

sub get_service_urls {
  my ($self) = @_;

  my $result = $self->service('device', 'ep')->GetServices( {
    IncludeCapability =>  'true', # boolean
    }
  );
  if ( $result ) {
    foreach my $svc ( @{ $result->get_Service() } ) {
      my $short_name = $namespace_map{$svc->get_Namespace()};    
      my $url_svc = $svc->get_XAddr()->get_value();
      if ( defined $short_name && defined $url_svc ) {
        #print "Got $short_name service\n";
        $self->set_service($short_name, 'url', $url_svc);
      }
    }
    #} else {
    #print "No results from GetServices: $result\n";
  }

  # Some devices do not support getServices, so we have to try getCapabilities

  $result = $self->service('device', 'ep')->GetCapabilities( {}, , );
  if ( !$result ) {
    print "No results from GetCapabilities: $result\n";
    return;
  }
  # Result is a GetCapabilitiesResponse
  foreach my $capabilities ( @{ $result->get_Capabilities() } ) {
    foreach my $capability ( 'PTZ', 'Media', 'Imaging', 'Events', 'Device' ) {
      if ( my $function = $capabilities->can( "get_$capability" ) ) {
        my $Services = $function->( $capabilities );
        if ( !$Services ) {
          #print "Nothing returned from get_$capability\n";
        } else {
          foreach my $svc ( @{ $Services } ) {
            # The capability versions don't have a namespace, so just lowercase them.
            my $short_name = lc $capability;
            my $url_svc = $svc->get_XAddr()->get_value();
            if ( defined $url_svc ) {
              #print "Got $short_name service\n";
              $self->set_service($short_name, 'url', $url_svc);
            }
          } # end foreach svr
        }
      } else {
        print "No $capability function\n";

      } # end if has a get_ function
    } # end foreach capability
  } # end foreach capabilities

} # end sub get_service_urls

sub http_digest {
  my ($service, $username, $password) = @_;

#  my $transport = SecurityTransport->new();
#  $transport->set_username($username);
#  $transport->set_password($password);

#  warn "transport: " . $service->get_transport();

  *SOAP::Transport::HTTP::Client::get_basic_credentials = sub {
  #*SOAP::WSDL::Transport::HTTP::get_basic_credentials = sub {
    my ($self, $realm, $uri, $isproxy) = @_;

    warn "### Requested credentials for $uri ###";

    return ($username, $password)
  };
}  

sub BUILD {
  my ($self,  $ident, $args_ref) = @_;
  
  my $url_svc_device = $args_ref->{'url_svc_device'};
  my $soap_version = $args_ref->{'soap_version'};
  if(! $soap_version) {
    $soap_version = '1.1';
  }
  $self->set_soap_version($soap_version);
  
  my $serializer = ONVIF::Serializer::Base->new();
  $serializer->set_soap_version($soap_version);

  my $svc_device = ONVIF::Device::Interfaces::Device::DevicePort->new({
   proxy => $url_svc_device,
   serializer => $serializer,
#   "strict => 0" does not work with SOAP header
#   deserializer_args => { strict => 0 }
  });

  $services_of{$ident}{device} = { url => $url_svc_device, ep => $svc_device };

  # Can't, don't have credentials yet
  # $self->get_service_urls();
}

sub get_users {
  my ($self) = @_;

  my $result = $self->service('device', 'ep')->GetUsers( { },, );

  die $result if not $result;
#  print $result . "\n";
}

sub create_user {
  my ($self, $username, $password) = @_;
  
  my $result = $self->service('device', 'ep')->CreateUsers( { 
    User =>  { # ONVIF::Device::Types::User
      Username =>  $username,       # string
      Password =>  $password,       # string
      UserLevel => 'Administrator', # UserLevel
      Extension =>  { # ONVIF::Device::Types::UserExtension
      },
    },
  },,
 );

  die $result if not $result;
#  print $result . "\n";
}

sub set_credentials {
  my ($self, $username, $password, $create_if_not_exists) = @_;

#  TODO: snyc device and client time  

  if ($create_if_not_exists) {
#  If GetUsers() is ok but empty then CreateUsers()
#    if(not get_users()) {
#      create_user($username, $password);
#    }
  }
  
  ## from here on use authorization
  $self->set_serializer( WSSecurity::SecuritySerializer->new() );
  $self->serializer()->set_soap_version($self->soap_version());
  $self->serializer()->set_username($username);
  $self->serializer()->set_password($password);

  $self->service('device', 'ep')->set_serializer($self->serializer());
}

# use this after set_credentials
sub create_services {
  my ($self) = @_;

  $self->get_service_urls();

  if ( defined $self->service('media', 'url') ) {
    $self->set_service('media', 'ep', ONVIF::Media::Interfaces::Media::MediaPort->new({
      proxy => $self->service('media', 'url'),
      serializer => $self->serializer(),
#      transport => $transport
    }));
  }
  if ( defined $self->service('ptz', 'url') ) {
    $self->set_service('ptz', 'ep', ONVIF::PTZ::Interfaces::PTZ::PTZPort->new({
      proxy => $self->service('ptz', 'url'),
      serializer => $self->serializer(),
#      transport => $transport
    }));
  }
}

sub get_endpoint {
  my ($self, $serviceType) = @_;
  
  $self->service($serviceType, 'ep');
}

1;
__END__
