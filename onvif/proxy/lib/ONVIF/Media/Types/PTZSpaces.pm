package ONVIF::Media::Types::PTZSpaces;
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

my %AbsolutePanTiltPositionSpace_of :ATTR(:get<AbsolutePanTiltPositionSpace>);
my %AbsoluteZoomPositionSpace_of :ATTR(:get<AbsoluteZoomPositionSpace>);
my %RelativePanTiltTranslationSpace_of :ATTR(:get<RelativePanTiltTranslationSpace>);
my %RelativeZoomTranslationSpace_of :ATTR(:get<RelativeZoomTranslationSpace>);
my %ContinuousPanTiltVelocitySpace_of :ATTR(:get<ContinuousPanTiltVelocitySpace>);
my %ContinuousZoomVelocitySpace_of :ATTR(:get<ContinuousZoomVelocitySpace>);
my %PanTiltSpeedSpace_of :ATTR(:get<PanTiltSpeedSpace>);
my %ZoomSpeedSpace_of :ATTR(:get<ZoomSpeedSpace>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        AbsolutePanTiltPositionSpace
        AbsoluteZoomPositionSpace
        RelativePanTiltTranslationSpace
        RelativeZoomTranslationSpace
        ContinuousPanTiltVelocitySpace
        ContinuousZoomVelocitySpace
        PanTiltSpeedSpace
        ZoomSpeedSpace
        Extension

    ) ],
    {
        'AbsolutePanTiltPositionSpace' => \%AbsolutePanTiltPositionSpace_of,
        'AbsoluteZoomPositionSpace' => \%AbsoluteZoomPositionSpace_of,
        'RelativePanTiltTranslationSpace' => \%RelativePanTiltTranslationSpace_of,
        'RelativeZoomTranslationSpace' => \%RelativeZoomTranslationSpace_of,
        'ContinuousPanTiltVelocitySpace' => \%ContinuousPanTiltVelocitySpace_of,
        'ContinuousZoomVelocitySpace' => \%ContinuousZoomVelocitySpace_of,
        'PanTiltSpeedSpace' => \%PanTiltSpeedSpace_of,
        'ZoomSpeedSpace' => \%ZoomSpeedSpace_of,
        'Extension' => \%Extension_of,
    },
    {
        'AbsolutePanTiltPositionSpace' => 'ONVIF::Media::Types::Space2DDescription',
        'AbsoluteZoomPositionSpace' => 'ONVIF::Media::Types::Space1DDescription',
        'RelativePanTiltTranslationSpace' => 'ONVIF::Media::Types::Space2DDescription',
        'RelativeZoomTranslationSpace' => 'ONVIF::Media::Types::Space1DDescription',
        'ContinuousPanTiltVelocitySpace' => 'ONVIF::Media::Types::Space2DDescription',
        'ContinuousZoomVelocitySpace' => 'ONVIF::Media::Types::Space1DDescription',
        'PanTiltSpeedSpace' => 'ONVIF::Media::Types::Space1DDescription',
        'ZoomSpeedSpace' => 'ONVIF::Media::Types::Space1DDescription',
        'Extension' => 'ONVIF::Media::Types::PTZSpacesExtension',
    },
    {

        'AbsolutePanTiltPositionSpace' => 'AbsolutePanTiltPositionSpace',
        'AbsoluteZoomPositionSpace' => 'AbsoluteZoomPositionSpace',
        'RelativePanTiltTranslationSpace' => 'RelativePanTiltTranslationSpace',
        'RelativeZoomTranslationSpace' => 'RelativeZoomTranslationSpace',
        'ContinuousPanTiltVelocitySpace' => 'ContinuousPanTiltVelocitySpace',
        'ContinuousZoomVelocitySpace' => 'ContinuousZoomVelocitySpace',
        'PanTiltSpeedSpace' => 'PanTiltSpeedSpace',
        'ZoomSpeedSpace' => 'ZoomSpeedSpace',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Media::Types::PTZSpaces

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
PTZSpaces from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * AbsolutePanTiltPositionSpace


=item * AbsoluteZoomPositionSpace


=item * RelativePanTiltTranslationSpace


=item * RelativeZoomTranslationSpace


=item * ContinuousPanTiltVelocitySpace


=item * ContinuousZoomVelocitySpace


=item * PanTiltSpeedSpace


=item * ZoomSpeedSpace


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Media::Types::PTZSpaces
   AbsolutePanTiltPositionSpace =>  { # ONVIF::Media::Types::Space2DDescription
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
   AbsoluteZoomPositionSpace =>  { # ONVIF::Media::Types::Space1DDescription
     URI =>  $some_value, # anyURI
     XRange =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   RelativePanTiltTranslationSpace =>  { # ONVIF::Media::Types::Space2DDescription
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
   RelativeZoomTranslationSpace =>  { # ONVIF::Media::Types::Space1DDescription
     URI =>  $some_value, # anyURI
     XRange =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   ContinuousPanTiltVelocitySpace =>  { # ONVIF::Media::Types::Space2DDescription
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
   ContinuousZoomVelocitySpace =>  { # ONVIF::Media::Types::Space1DDescription
     URI =>  $some_value, # anyURI
     XRange =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   PanTiltSpeedSpace =>  { # ONVIF::Media::Types::Space1DDescription
     URI =>  $some_value, # anyURI
     XRange =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   ZoomSpeedSpace =>  { # ONVIF::Media::Types::Space1DDescription
     URI =>  $some_value, # anyURI
     XRange =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   Extension =>  { # ONVIF::Media::Types::PTZSpacesExtension
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

