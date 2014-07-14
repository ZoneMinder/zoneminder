package ONVIF::Media::Types::PTZConfiguration;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS;
undef $XML_ATTRIBUTE_CLASS;

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}


use base qw(ONVIF::Media::Types::ConfigurationEntity);
# Variety: sequence
use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %Name_of :ATTR(:get<Name>);
my %UseCount_of :ATTR(:get<UseCount>);
my %NodeToken_of :ATTR(:get<NodeToken>);
my %DefaultAbsolutePantTiltPositionSpace_of :ATTR(:get<DefaultAbsolutePantTiltPositionSpace>);
my %DefaultAbsoluteZoomPositionSpace_of :ATTR(:get<DefaultAbsoluteZoomPositionSpace>);
my %DefaultRelativePanTiltTranslationSpace_of :ATTR(:get<DefaultRelativePanTiltTranslationSpace>);
my %DefaultRelativeZoomTranslationSpace_of :ATTR(:get<DefaultRelativeZoomTranslationSpace>);
my %DefaultContinuousPanTiltVelocitySpace_of :ATTR(:get<DefaultContinuousPanTiltVelocitySpace>);
my %DefaultContinuousZoomVelocitySpace_of :ATTR(:get<DefaultContinuousZoomVelocitySpace>);
my %DefaultPTZSpeed_of :ATTR(:get<DefaultPTZSpeed>);
my %DefaultPTZTimeout_of :ATTR(:get<DefaultPTZTimeout>);
my %PanTiltLimits_of :ATTR(:get<PanTiltLimits>);
my %ZoomLimits_of :ATTR(:get<ZoomLimits>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        Name
        UseCount
        NodeToken
        DefaultAbsolutePantTiltPositionSpace
        DefaultAbsoluteZoomPositionSpace
        DefaultRelativePanTiltTranslationSpace
        DefaultRelativeZoomTranslationSpace
        DefaultContinuousPanTiltVelocitySpace
        DefaultContinuousZoomVelocitySpace
        DefaultPTZSpeed
        DefaultPTZTimeout
        PanTiltLimits
        ZoomLimits
        Extension

    ) ],
    {
        'Name' => \%Name_of,
        'UseCount' => \%UseCount_of,
        'NodeToken' => \%NodeToken_of,
        'DefaultAbsolutePantTiltPositionSpace' => \%DefaultAbsolutePantTiltPositionSpace_of,
        'DefaultAbsoluteZoomPositionSpace' => \%DefaultAbsoluteZoomPositionSpace_of,
        'DefaultRelativePanTiltTranslationSpace' => \%DefaultRelativePanTiltTranslationSpace_of,
        'DefaultRelativeZoomTranslationSpace' => \%DefaultRelativeZoomTranslationSpace_of,
        'DefaultContinuousPanTiltVelocitySpace' => \%DefaultContinuousPanTiltVelocitySpace_of,
        'DefaultContinuousZoomVelocitySpace' => \%DefaultContinuousZoomVelocitySpace_of,
        'DefaultPTZSpeed' => \%DefaultPTZSpeed_of,
        'DefaultPTZTimeout' => \%DefaultPTZTimeout_of,
        'PanTiltLimits' => \%PanTiltLimits_of,
        'ZoomLimits' => \%ZoomLimits_of,
        'Extension' => \%Extension_of,
    },
    {
        'Name' => 'ONVIF::Media::Types::Name',
        'UseCount' => 'SOAP::WSDL::XSD::Typelib::Builtin::int',
        'NodeToken' => 'ONVIF::Media::Types::ReferenceToken',
        'DefaultAbsolutePantTiltPositionSpace' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyURI',
        'DefaultAbsoluteZoomPositionSpace' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyURI',
        'DefaultRelativePanTiltTranslationSpace' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyURI',
        'DefaultRelativeZoomTranslationSpace' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyURI',
        'DefaultContinuousPanTiltVelocitySpace' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyURI',
        'DefaultContinuousZoomVelocitySpace' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyURI',
        'DefaultPTZSpeed' => 'ONVIF::Media::Types::PTZSpeed',
        'DefaultPTZTimeout' => 'SOAP::WSDL::XSD::Typelib::Builtin::duration',
        'PanTiltLimits' => 'ONVIF::Media::Types::PanTiltLimits',
        'ZoomLimits' => 'ONVIF::Media::Types::ZoomLimits',
        'Extension' => 'ONVIF::Media::Types::PTZConfigurationExtension',
    },
    {

        'Name' => 'Name',
        'UseCount' => 'UseCount',
        'NodeToken' => 'NodeToken',
        'DefaultAbsolutePantTiltPositionSpace' => 'DefaultAbsolutePantTiltPositionSpace',
        'DefaultAbsoluteZoomPositionSpace' => 'DefaultAbsoluteZoomPositionSpace',
        'DefaultRelativePanTiltTranslationSpace' => 'DefaultRelativePanTiltTranslationSpace',
        'DefaultRelativeZoomTranslationSpace' => 'DefaultRelativeZoomTranslationSpace',
        'DefaultContinuousPanTiltVelocitySpace' => 'DefaultContinuousPanTiltVelocitySpace',
        'DefaultContinuousZoomVelocitySpace' => 'DefaultContinuousZoomVelocitySpace',
        'DefaultPTZSpeed' => 'DefaultPTZSpeed',
        'DefaultPTZTimeout' => 'DefaultPTZTimeout',
        'PanTiltLimits' => 'PanTiltLimits',
        'ZoomLimits' => 'ZoomLimits',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Media::Types::PTZConfiguration

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
PTZConfiguration from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * NodeToken


=item * DefaultAbsolutePantTiltPositionSpace


=item * DefaultAbsoluteZoomPositionSpace


=item * DefaultRelativePanTiltTranslationSpace


=item * DefaultRelativeZoomTranslationSpace


=item * DefaultContinuousPanTiltVelocitySpace


=item * DefaultContinuousZoomVelocitySpace


=item * DefaultPTZSpeed


=item * DefaultPTZTimeout


=item * PanTiltLimits


=item * ZoomLimits


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Media::Types::PTZConfiguration
   NodeToken => $some_value, # ReferenceToken
   DefaultAbsolutePantTiltPositionSpace =>  $some_value, # anyURI
   DefaultAbsoluteZoomPositionSpace =>  $some_value, # anyURI
   DefaultRelativePanTiltTranslationSpace =>  $some_value, # anyURI
   DefaultRelativeZoomTranslationSpace =>  $some_value, # anyURI
   DefaultContinuousPanTiltVelocitySpace =>  $some_value, # anyURI
   DefaultContinuousZoomVelocitySpace =>  $some_value, # anyURI
   DefaultPTZSpeed =>  { # ONVIF::Media::Types::PTZSpeed
     PanTilt => ,
     Zoom => ,
   },
   DefaultPTZTimeout =>  $some_value, # duration
   PanTiltLimits =>  { # ONVIF::Media::Types::PanTiltLimits
     Range =>  { # ONVIF::Media::Types::Space2DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Media::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
       YRange =>  { # ONVIF::Media::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
   },
   ZoomLimits =>  { # ONVIF::Media::Types::ZoomLimits
     Range =>  { # ONVIF::Media::Types::Space1DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Media::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
   },
   Extension =>  { # ONVIF::Media::Types::PTZConfigurationExtension
     PTControlDirection =>  { # ONVIF::Media::Types::PTControlDirection
       EFlip =>  { # ONVIF::Media::Types::EFlip
         Mode => $some_value, # EFlipMode
       },
       Reverse =>  { # ONVIF::Media::Types::Reverse
         Mode => $some_value, # ReverseMode
       },
       Extension =>  { # ONVIF::Media::Types::PTControlDirectionExtension
       },
     },
     Extension =>  { # ONVIF::Media::Types::PTZConfigurationExtension2
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

