package ONVIF::Device::Types::NetworkCapabilities;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/device/wsdl' };

our $XML_ATTRIBUTE_CLASS = 'ONVIF::Device::Types::NetworkCapabilities::_NetworkCapabilities::XmlAttr';

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}



# There's no variety - empty complexType
use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

__PACKAGE__->_factory();


package ONVIF::Device::Types::NetworkCapabilities::_NetworkCapabilities::XmlAttr;
use base qw(SOAP::WSDL::XSD::Typelib::AttributeSet);

{ # BLOCK to scope variables

my %IPFilter_of :ATTR(:get<IPFilter>);
my %ZeroConfiguration_of :ATTR(:get<ZeroConfiguration>);
my %IPVersion6_of :ATTR(:get<IPVersion6>);
my %DynDNS_of :ATTR(:get<DynDNS>);
my %Dot11Configuration_of :ATTR(:get<Dot11Configuration>);
my %Dot1XConfigurations_of :ATTR(:get<Dot1XConfigurations>);
my %HostnameFromDHCP_of :ATTR(:get<HostnameFromDHCP>);
my %NTP_of :ATTR(:get<NTP>);
my %DHCPv6_of :ATTR(:get<DHCPv6>);

__PACKAGE__->_factory(
    [ qw(
        IPFilter
        ZeroConfiguration
        IPVersion6
        DynDNS
        Dot11Configuration
        Dot1XConfigurations
        HostnameFromDHCP
        NTP
        DHCPv6
    ) ],
    {

        IPFilter => \%IPFilter_of,

        ZeroConfiguration => \%ZeroConfiguration_of,

        IPVersion6 => \%IPVersion6_of,

        DynDNS => \%DynDNS_of,

        Dot11Configuration => \%Dot11Configuration_of,

        Dot1XConfigurations => \%Dot1XConfigurations_of,

        HostnameFromDHCP => \%HostnameFromDHCP_of,

        NTP => \%NTP_of,

        DHCPv6 => \%DHCPv6_of,
    },
    {
        IPFilter => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        ZeroConfiguration => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        IPVersion6 => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        DynDNS => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        Dot11Configuration => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        Dot1XConfigurations => 'SOAP::WSDL::XSD::Typelib::Builtin::int',
        HostnameFromDHCP => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        NTP => 'SOAP::WSDL::XSD::Typelib::Builtin::int',
        DHCPv6 => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
    }
);

} # end BLOCK




1;


=pod

=head1 NAME

ONVIF::Device::Types::NetworkCapabilities

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
NetworkCapabilities from the namespace http://www.onvif.org/ver10/device/wsdl.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over



=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

,



=head2 attr

NOTE: Attribute documentation is experimental, and may be inaccurate.
See the correspondent WSDL/XML Schema if in question.

This class has additional attributes, accessibly via the C<attr()> method.

attr() returns an object of the class ONVIF::Device::Types::NetworkCapabilities::_NetworkCapabilities::XmlAttr.

The following attributes can be accessed on this object via the corresponding
get_/set_ methods:

=over

=item * IPFilter

 Indicates support for IP filtering.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * ZeroConfiguration

 Indicates support for zeroconf.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * IPVersion6

 Indicates support for IPv6.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * DynDNS

 Indicates support for dynamic DNS configuration.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * Dot11Configuration

 Indicates support for IEEE 802.11 configuration.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * Dot1XConfigurations

 Indicates the maximum number of Dot1X configurations supported by the device



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::int|SOAP::WSDL::XSD::Typelib::Builtin::int>.

=item * HostnameFromDHCP

 Indicates support for retrieval of hostname from DHCP.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * NTP

 Maximum number of NTP servers supported by the devices SetNTP command.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::int|SOAP::WSDL::XSD::Typelib::Builtin::int>.

=item * DHCPv6

 Indicates support for Stateful IPv6 DHCP.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.


=back




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

