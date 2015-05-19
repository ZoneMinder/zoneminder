package ONVIF::Media::Types::Dot11Capabilities;
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

my %TKIP_of :ATTR(:get<TKIP>);
my %ScanAvailableNetworks_of :ATTR(:get<ScanAvailableNetworks>);
my %MultipleConfiguration_of :ATTR(:get<MultipleConfiguration>);
my %AdHocStationMode_of :ATTR(:get<AdHocStationMode>);
my %WEP_of :ATTR(:get<WEP>);

__PACKAGE__->_factory(
    [ qw(        TKIP
        ScanAvailableNetworks
        MultipleConfiguration
        AdHocStationMode
        WEP

    ) ],
    {
        'TKIP' => \%TKIP_of,
        'ScanAvailableNetworks' => \%ScanAvailableNetworks_of,
        'MultipleConfiguration' => \%MultipleConfiguration_of,
        'AdHocStationMode' => \%AdHocStationMode_of,
        'WEP' => \%WEP_of,
    },
    {
        'TKIP' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'ScanAvailableNetworks' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'MultipleConfiguration' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'AdHocStationMode' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'WEP' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
    },
    {

        'TKIP' => 'TKIP',
        'ScanAvailableNetworks' => 'ScanAvailableNetworks',
        'MultipleConfiguration' => 'MultipleConfiguration',
        'AdHocStationMode' => 'AdHocStationMode',
        'WEP' => 'WEP',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Media::Types::Dot11Capabilities

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
Dot11Capabilities from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * TKIP


=item * ScanAvailableNetworks


=item * MultipleConfiguration


=item * AdHocStationMode


=item * WEP




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Media::Types::Dot11Capabilities
   TKIP =>  $some_value, # boolean
   ScanAvailableNetworks =>  $some_value, # boolean
   MultipleConfiguration =>  $some_value, # boolean
   AdHocStationMode =>  $some_value, # boolean
   WEP =>  $some_value, # boolean
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

