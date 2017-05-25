package ONVIF::Device::Types::SystemCapabilities;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/device/wsdl' };

our $XML_ATTRIBUTE_CLASS = 'ONVIF::Device::Types::SystemCapabilities::_SystemCapabilities::XmlAttr';

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}



# There's no variety - empty complexType
use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

__PACKAGE__->_factory();


package ONVIF::Device::Types::SystemCapabilities::_SystemCapabilities::XmlAttr;
use base qw(SOAP::WSDL::XSD::Typelib::AttributeSet);

{ # BLOCK to scope variables

my %DiscoveryResolve_of :ATTR(:get<DiscoveryResolve>);
my %DiscoveryBye_of :ATTR(:get<DiscoveryBye>);
my %RemoteDiscovery_of :ATTR(:get<RemoteDiscovery>);
my %SystemBackup_of :ATTR(:get<SystemBackup>);
my %SystemLogging_of :ATTR(:get<SystemLogging>);
my %FirmwareUpgrade_of :ATTR(:get<FirmwareUpgrade>);
my %HttpFirmwareUpgrade_of :ATTR(:get<HttpFirmwareUpgrade>);
my %HttpSystemBackup_of :ATTR(:get<HttpSystemBackup>);
my %HttpSystemLogging_of :ATTR(:get<HttpSystemLogging>);
my %HttpSupportInformation_of :ATTR(:get<HttpSupportInformation>);

__PACKAGE__->_factory(
    [ qw(
        DiscoveryResolve
        DiscoveryBye
        RemoteDiscovery
        SystemBackup
        SystemLogging
        FirmwareUpgrade
        HttpFirmwareUpgrade
        HttpSystemBackup
        HttpSystemLogging
        HttpSupportInformation
    ) ],
    {

        DiscoveryResolve => \%DiscoveryResolve_of,

        DiscoveryBye => \%DiscoveryBye_of,

        RemoteDiscovery => \%RemoteDiscovery_of,

        SystemBackup => \%SystemBackup_of,

        SystemLogging => \%SystemLogging_of,

        FirmwareUpgrade => \%FirmwareUpgrade_of,

        HttpFirmwareUpgrade => \%HttpFirmwareUpgrade_of,

        HttpSystemBackup => \%HttpSystemBackup_of,

        HttpSystemLogging => \%HttpSystemLogging_of,

        HttpSupportInformation => \%HttpSupportInformation_of,
    },
    {
        DiscoveryResolve => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        DiscoveryBye => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        RemoteDiscovery => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        SystemBackup => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        SystemLogging => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        FirmwareUpgrade => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        HttpFirmwareUpgrade => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        HttpSystemBackup => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        HttpSystemLogging => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        HttpSupportInformation => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
    }
);

} # end BLOCK




1;


=pod

=head1 NAME

ONVIF::Device::Types::SystemCapabilities

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
SystemCapabilities from the namespace http://www.onvif.org/ver10/device/wsdl.






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

attr() returns an object of the class ONVIF::Device::Types::SystemCapabilities::_SystemCapabilities::XmlAttr.

The following attributes can be accessed on this object via the corresponding
get_/set_ methods:

=over

=item * DiscoveryResolve

 Indicates support for WS Discovery resolve requests.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * DiscoveryBye

 Indicates support for WS-Discovery Bye.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * RemoteDiscovery

 Indicates support for remote discovery.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * SystemBackup

 Indicates support for system backup through MTOM.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * SystemLogging

 Indicates support for retrieval of system logging through MTOM.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * FirmwareUpgrade

 Indicates support for firmware upgrade through MTOM.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * HttpFirmwareUpgrade

 Indicates support for system backup through MTOM.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * HttpSystemBackup

 Indicates support for system backup through HTTP.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * HttpSystemLogging

 Indicates support for retrieval of system logging through HTTP.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * HttpSupportInformation

 Indicates support for retrieving support information through HTTP.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.


=back




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

