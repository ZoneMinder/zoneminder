package ONVIF::Analytics::Types::ImagingSettings20;
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

my %BacklightCompensation_of :ATTR(:get<BacklightCompensation>);
my %Brightness_of :ATTR(:get<Brightness>);
my %ColorSaturation_of :ATTR(:get<ColorSaturation>);
my %Contrast_of :ATTR(:get<Contrast>);
my %Exposure_of :ATTR(:get<Exposure>);
my %Focus_of :ATTR(:get<Focus>);
my %IrCutFilter_of :ATTR(:get<IrCutFilter>);
my %Sharpness_of :ATTR(:get<Sharpness>);
my %WideDynamicRange_of :ATTR(:get<WideDynamicRange>);
my %WhiteBalance_of :ATTR(:get<WhiteBalance>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        BacklightCompensation
        Brightness
        ColorSaturation
        Contrast
        Exposure
        Focus
        IrCutFilter
        Sharpness
        WideDynamicRange
        WhiteBalance
        Extension

    ) ],
    {
        'BacklightCompensation' => \%BacklightCompensation_of,
        'Brightness' => \%Brightness_of,
        'ColorSaturation' => \%ColorSaturation_of,
        'Contrast' => \%Contrast_of,
        'Exposure' => \%Exposure_of,
        'Focus' => \%Focus_of,
        'IrCutFilter' => \%IrCutFilter_of,
        'Sharpness' => \%Sharpness_of,
        'WideDynamicRange' => \%WideDynamicRange_of,
        'WhiteBalance' => \%WhiteBalance_of,
        'Extension' => \%Extension_of,
    },
    {
        'BacklightCompensation' => 'ONVIF::Analytics::Types::BacklightCompensation20',
        'Brightness' => 'SOAP::WSDL::XSD::Typelib::Builtin::float',
        'ColorSaturation' => 'SOAP::WSDL::XSD::Typelib::Builtin::float',
        'Contrast' => 'SOAP::WSDL::XSD::Typelib::Builtin::float',
        'Exposure' => 'ONVIF::Analytics::Types::Exposure20',
        'Focus' => 'ONVIF::Analytics::Types::FocusConfiguration20',
        'IrCutFilter' => 'ONVIF::Analytics::Types::IrCutFilterMode',
        'Sharpness' => 'SOAP::WSDL::XSD::Typelib::Builtin::float',
        'WideDynamicRange' => 'ONVIF::Analytics::Types::WideDynamicRange20',
        'WhiteBalance' => 'ONVIF::Analytics::Types::WhiteBalance20',
        'Extension' => 'ONVIF::Analytics::Types::ImagingSettingsExtension20',
    },
    {

        'BacklightCompensation' => 'BacklightCompensation',
        'Brightness' => 'Brightness',
        'ColorSaturation' => 'ColorSaturation',
        'Contrast' => 'Contrast',
        'Exposure' => 'Exposure',
        'Focus' => 'Focus',
        'IrCutFilter' => 'IrCutFilter',
        'Sharpness' => 'Sharpness',
        'WideDynamicRange' => 'WideDynamicRange',
        'WhiteBalance' => 'WhiteBalance',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Analytics::Types::ImagingSettings20

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
ImagingSettings20 from the namespace http://www.onvif.org/ver10/schema.

Type describing the ImagingSettings of a VideoSource. The supported options and ranges can be obtained via the GetOptions command.




=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * BacklightCompensation


=item * Brightness


=item * ColorSaturation


=item * Contrast


=item * Exposure


=item * Focus


=item * IrCutFilter


=item * Sharpness


=item * WideDynamicRange


=item * WhiteBalance


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Analytics::Types::ImagingSettings20
   BacklightCompensation =>  { # ONVIF::Analytics::Types::BacklightCompensation20
     Mode => $some_value, # BacklightCompensationMode
     Level =>  $some_value, # float
   },
   Brightness =>  $some_value, # float
   ColorSaturation =>  $some_value, # float
   Contrast =>  $some_value, # float
   Exposure =>  { # ONVIF::Analytics::Types::Exposure20
     Mode => $some_value, # ExposureMode
     Priority => $some_value, # ExposurePriority
     Window => ,
     MinExposureTime =>  $some_value, # float
     MaxExposureTime =>  $some_value, # float
     MinGain =>  $some_value, # float
     MaxGain =>  $some_value, # float
     MinIris =>  $some_value, # float
     MaxIris =>  $some_value, # float
     ExposureTime =>  $some_value, # float
     Gain =>  $some_value, # float
     Iris =>  $some_value, # float
   },
   Focus =>  { # ONVIF::Analytics::Types::FocusConfiguration20
     AutoFocusMode => $some_value, # AutoFocusMode
     DefaultSpeed =>  $some_value, # float
     NearLimit =>  $some_value, # float
     FarLimit =>  $some_value, # float
     Extension =>  { # ONVIF::Analytics::Types::FocusConfiguration20Extension
     },
   },
   IrCutFilter => $some_value, # IrCutFilterMode
   Sharpness =>  $some_value, # float
   WideDynamicRange =>  { # ONVIF::Analytics::Types::WideDynamicRange20
     Mode => $some_value, # WideDynamicMode
     Level =>  $some_value, # float
   },
   WhiteBalance =>  { # ONVIF::Analytics::Types::WhiteBalance20
     Mode => $some_value, # WhiteBalanceMode
     CrGain =>  $some_value, # float
     CbGain =>  $some_value, # float
     Extension =>  { # ONVIF::Analytics::Types::WhiteBalance20Extension
     },
   },
   Extension =>  { # ONVIF::Analytics::Types::ImagingSettingsExtension20
     ImageStabilization =>  { # ONVIF::Analytics::Types::ImageStabilization
       Mode => $some_value, # ImageStabilizationMode
       Level =>  $some_value, # float
       Extension =>  { # ONVIF::Analytics::Types::ImageStabilizationExtension
       },
     },
     Extension =>  { # ONVIF::Analytics::Types::ImagingSettingsExtension202
       IrCutFilterAutoAdjustment =>  { # ONVIF::Analytics::Types::IrCutFilterAutoAdjustment
         BoundaryType =>  $some_value, # string
         BoundaryOffset =>  $some_value, # float
         ResponseTime =>  $some_value, # duration
         Extension =>  { # ONVIF::Analytics::Types::IrCutFilterAutoAdjustmentExtension
         },
       },
       Extension =>  { # ONVIF::Analytics::Types::ImagingSettingsExtension203
       },
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

