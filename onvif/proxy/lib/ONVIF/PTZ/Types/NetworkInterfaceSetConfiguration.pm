package ONVIF::PTZ::Types::NetworkInterfaceSetConfiguration;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS;
undef $XML_ATTRIBUTE_CLASS;

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}

use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %Enabled_of :ATTR(:get<Enabled>);
my %Link_of :ATTR(:get<Link>);
my %MTU_of :ATTR(:get<MTU>);
my %IPv4_of :ATTR(:get<IPv4>);
my %IPv6_of :ATTR(:get<IPv6>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        Enabled
        Link
        MTU
        IPv4
        IPv6
        Extension

    ) ],
    {
        'Enabled' => \%Enabled_of,
        'Link' => \%Link_of,
        'MTU' => \%MTU_of,
        'IPv4' => \%IPv4_of,
        'IPv6' => \%IPv6_of,
        'Extension' => \%Extension_of,
    },
    {
        'Enabled' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'Link' => 'ONVIF::PTZ::Types::NetworkInterfaceConnectionSetting',
        'MTU' => 'SOAP::WSDL::XSD::Typelib::Builtin::int',
        'IPv4' => 'ONVIF::PTZ::Types::IPv4NetworkInterfaceSetConfiguration',
        'IPv6' => 'ONVIF::PTZ::Types::IPv6NetworkInterfaceSetConfiguration',
        'Extension' => 'ONVIF::PTZ::Types::NetworkInterfaceSetConfigurationExtension',
    },
    {

        'Enabled' => 'Enabled',
        'Link' => 'Link',
        'MTU' => 'MTU',
        'IPv4' => 'IPv4',
        'IPv6' => 'IPv6',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::PTZ::Types::NetworkInterfaceSetConfiguration

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
NetworkInterfaceSetConfiguration from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * Enabled


=item * Link


=item * MTU


=item * IPv4


=item * IPv6


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::PTZ::Types::NetworkInterfaceSetConfiguration
   Enabled =>  $some_value, # boolean
   Link =>  { # ONVIF::PTZ::Types::NetworkInterfaceConnectionSetting
     AutoNegotiation =>  $some_value, # boolean
     Speed =>  $some_value, # int
     Duplex => $some_value, # Duplex
   },
   MTU =>  $some_value, # int
   IPv4 =>  { # ONVIF::PTZ::Types::IPv4NetworkInterfaceSetConfiguration
     Enabled =>  $some_value, # boolean
     Manual =>  { # ONVIF::PTZ::Types::PrefixedIPv4Address
       Address => $some_value, # IPv4Address
       PrefixLength =>  $some_value, # int
     },
     DHCP =>  $some_value, # boolean
   },
   IPv6 =>  { # ONVIF::PTZ::Types::IPv6NetworkInterfaceSetConfiguration
     Enabled =>  $some_value, # boolean
     AcceptRouterAdvert =>  $some_value, # boolean
     Manual =>  { # ONVIF::PTZ::Types::PrefixedIPv6Address
       Address => $some_value, # IPv6Address
       PrefixLength =>  $some_value, # int
     },
     DHCP => $some_value, # IPv6DHCPConfiguration
   },
   Extension =>  { # ONVIF::PTZ::Types::NetworkInterfaceSetConfigurationExtension
     Dot3 =>  { # ONVIF::PTZ::Types::Dot3Configuration
     },
     Dot11 =>  { # ONVIF::PTZ::Types::Dot11Configuration
       SSID => $some_value, # Dot11SSIDType
       Mode => $some_value, # Dot11StationMode
       Alias => $some_value, # Name
       Priority => $some_value, # NetworkInterfaceConfigPriority
       Security =>  { # ONVIF::PTZ::Types::Dot11SecurityConfiguration
         Mode => $some_value, # Dot11SecurityMode
         Algorithm => $some_value, # Dot11Cipher
         PSK =>  { # ONVIF::PTZ::Types::Dot11PSKSet
           Key => $some_value, # Dot11PSK
           Passphrase => $some_value, # Dot11PSKPassphrase
           Extension =>  { # ONVIF::PTZ::Types::Dot11PSKSetExtension
           },
         },
         Dot1X => $some_value, # ReferenceToken
         Extension =>  { # ONVIF::PTZ::Types::Dot11SecurityConfigurationExtension
         },
       },
     },
     Extension =>  { # ONVIF::PTZ::Types::NetworkInterfaceSetConfigurationExtension2
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

