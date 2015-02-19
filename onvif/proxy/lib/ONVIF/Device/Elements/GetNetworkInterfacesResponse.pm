
package ONVIF::Device::Elements::GetNetworkInterfacesResponse;
use strict;
use warnings;

{ # BLOCK to scope variables

sub get_xmlns { 'http://www.onvif.org/ver10/device/wsdl' }

__PACKAGE__->__set_name('GetNetworkInterfacesResponse');
__PACKAGE__->__set_nillable();
__PACKAGE__->__set_minOccurs();
__PACKAGE__->__set_maxOccurs();
__PACKAGE__->__set_ref();

use base qw(
    SOAP::WSDL::XSD::Typelib::Element
    SOAP::WSDL::XSD::Typelib::ComplexType
);

our $XML_ATTRIBUTE_CLASS;
undef $XML_ATTRIBUTE_CLASS;

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}

use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %NetworkInterfaces_of :ATTR(:get<NetworkInterfaces>);

__PACKAGE__->_factory(
    [ qw(        NetworkInterfaces

    ) ],
    {
        'NetworkInterfaces' => \%NetworkInterfaces_of,
    },
    {
        'NetworkInterfaces' => 'ONVIF::Device::Types::NetworkInterface',
    },
    {

        'NetworkInterfaces' => 'NetworkInterfaces',
    }
);

} # end BLOCK







} # end of BLOCK



1;


=pod

=head1 NAME

ONVIF::Device::Elements::GetNetworkInterfacesResponse

=head1 DESCRIPTION

Perl data type class for the XML Schema defined element
GetNetworkInterfacesResponse from the namespace http://www.onvif.org/ver10/device/wsdl.







=head1 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * NetworkInterfaces

 $element->set_NetworkInterfaces($data);
 $element->get_NetworkInterfaces();





=back


=head1 METHODS

=head2 new

 my $element = ONVIF::Device::Elements::GetNetworkInterfacesResponse->new($data);

Constructor. The following data structure may be passed to new():

 {
   NetworkInterfaces =>  { # ONVIF::Device::Types::NetworkInterface
     Enabled =>  $some_value, # boolean
     Info =>  { # ONVIF::Device::Types::NetworkInterfaceInfo
       Name =>  $some_value, # string
       HwAddress => $some_value, # HwAddress
       MTU =>  $some_value, # int
     },
     Link =>  { # ONVIF::Device::Types::NetworkInterfaceLink
       AdminSettings =>  { # ONVIF::Device::Types::NetworkInterfaceConnectionSetting
         AutoNegotiation =>  $some_value, # boolean
         Speed =>  $some_value, # int
         Duplex => $some_value, # Duplex
       },
       OperSettings =>  { # ONVIF::Device::Types::NetworkInterfaceConnectionSetting
         AutoNegotiation =>  $some_value, # boolean
         Speed =>  $some_value, # int
         Duplex => $some_value, # Duplex
       },
       InterfaceType => $some_value, # IANA-IfTypes
     },
     IPv4 =>  { # ONVIF::Device::Types::IPv4NetworkInterface
       Enabled =>  $some_value, # boolean
       Config =>  { # ONVIF::Device::Types::IPv4Configuration
         Manual =>  { # ONVIF::Device::Types::PrefixedIPv4Address
           Address => $some_value, # IPv4Address
           PrefixLength =>  $some_value, # int
         },
         LinkLocal =>  { # ONVIF::Device::Types::PrefixedIPv4Address
           Address => $some_value, # IPv4Address
           PrefixLength =>  $some_value, # int
         },
         FromDHCP =>  { # ONVIF::Device::Types::PrefixedIPv4Address
           Address => $some_value, # IPv4Address
           PrefixLength =>  $some_value, # int
         },
         DHCP =>  $some_value, # boolean
       },
     },
     IPv6 =>  { # ONVIF::Device::Types::IPv6NetworkInterface
       Enabled =>  $some_value, # boolean
       Config =>  { # ONVIF::Device::Types::IPv6Configuration
         AcceptRouterAdvert =>  $some_value, # boolean
         DHCP => $some_value, # IPv6DHCPConfiguration
         Manual =>  { # ONVIF::Device::Types::PrefixedIPv6Address
           Address => $some_value, # IPv6Address
           PrefixLength =>  $some_value, # int
         },
         LinkLocal =>  { # ONVIF::Device::Types::PrefixedIPv6Address
           Address => $some_value, # IPv6Address
           PrefixLength =>  $some_value, # int
         },
         FromDHCP =>  { # ONVIF::Device::Types::PrefixedIPv6Address
           Address => $some_value, # IPv6Address
           PrefixLength =>  $some_value, # int
         },
         FromRA =>  { # ONVIF::Device::Types::PrefixedIPv6Address
           Address => $some_value, # IPv6Address
           PrefixLength =>  $some_value, # int
         },
         Extension =>  { # ONVIF::Device::Types::IPv6ConfigurationExtension
         },
       },
     },
     Extension =>  { # ONVIF::Device::Types::NetworkInterfaceExtension
       InterfaceType => $some_value, # IANA-IfTypes
       Dot3 =>  { # ONVIF::Device::Types::Dot3Configuration
       },
       Dot11 =>  { # ONVIF::Device::Types::Dot11Configuration
         SSID => $some_value, # Dot11SSIDType
         Mode => $some_value, # Dot11StationMode
         Alias => $some_value, # Name
         Priority => $some_value, # NetworkInterfaceConfigPriority
         Security =>  { # ONVIF::Device::Types::Dot11SecurityConfiguration
           Mode => $some_value, # Dot11SecurityMode
           Algorithm => $some_value, # Dot11Cipher
           PSK =>  { # ONVIF::Device::Types::Dot11PSKSet
             Key => $some_value, # Dot11PSK
             Passphrase => $some_value, # Dot11PSKPassphrase
             Extension =>  { # ONVIF::Device::Types::Dot11PSKSetExtension
             },
           },
           Dot1X => $some_value, # ReferenceToken
           Extension =>  { # ONVIF::Device::Types::Dot11SecurityConfigurationExtension
           },
         },
       },
       Extension =>  { # ONVIF::Device::Types::NetworkInterfaceExtension2
       },
     },
   },
 },

=head1 AUTHOR

Generated by SOAP::WSDL

=cut

