package ONVIF::PTZ::Types::AnalyticsEngineConfiguration;
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

my %AnalyticsModule_of :ATTR(:get<AnalyticsModule>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        AnalyticsModule
        Extension

    ) ],
    {
        'AnalyticsModule' => \%AnalyticsModule_of,
        'Extension' => \%Extension_of,
    },
    {
        'AnalyticsModule' => 'ONVIF::PTZ::Types::Config',
        'Extension' => 'ONVIF::PTZ::Types::AnalyticsEngineConfigurationExtension',
    },
    {

        'AnalyticsModule' => 'AnalyticsModule',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::PTZ::Types::AnalyticsEngineConfiguration

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
AnalyticsEngineConfiguration from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * AnalyticsModule


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::PTZ::Types::AnalyticsEngineConfiguration
   AnalyticsModule =>  { # ONVIF::PTZ::Types::Config
     Parameters =>  { # ONVIF::PTZ::Types::ItemList
       SimpleItem => ,
       ElementItem =>  {
       },
       Extension =>  { # ONVIF::PTZ::Types::ItemListExtension
       },
     },
   },
   Extension =>  { # ONVIF::PTZ::Types::AnalyticsEngineConfigurationExtension
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

