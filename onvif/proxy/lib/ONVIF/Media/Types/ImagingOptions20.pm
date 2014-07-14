package ONVIF::Media::Types::ImagingOptions20;
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
my %IrCutFilterModes_of :ATTR(:get<IrCutFilterModes>);
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
        IrCutFilterModes
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
        'IrCutFilterModes' => \%IrCutFilterModes_of,
        'Sharpness' => \%Sharpness_of,
        'WideDynamicRange' => \%WideDynamicRange_of,
        'WhiteBalance' => \%WhiteBalance_of,
        'Extension' => \%Extension_of,
    },
    {
        'BacklightCompensation' => 'ONVIF::Media::Types::BacklightCompensationOptions20',
        'Brightness' => 'ONVIF::Media::Types::FloatRange',
        'ColorSaturation' => 'ONVIF::Media::Types::FloatRange',
        'Contrast' => 'ONVIF::Media::Types::FloatRange',
        'Exposure' => 'ONVIF::Media::Types::ExposureOptions20',
        'Focus' => 'ONVIF::Media::Types::FocusOptions20',
        'IrCutFilterModes' => 'ONVIF::Media::Types::IrCutFilterMode',
        'Sharpness' => 'ONVIF::Media::Types::FloatRange',
        'WideDynamicRange' => 'ONVIF::Media::Types::WideDynamicRangeOptions20',
        'WhiteBalance' => 'ONVIF::Media::Types::WhiteBalanceOptions20',
        'Extension' => 'ONVIF::Media::Types::ImagingOptions20Extension',
    },
    {

        'BacklightCompensation' => 'BacklightCompensation',
        'Brightness' => 'Brightness',
        'ColorSaturation' => 'ColorSaturation',
        'Contrast' => 'Contrast',
        'Exposure' => 'Exposure',
        'Focus' => 'Focus',
        'IrCutFilterModes' => 'IrCutFilterModes',
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

ONVIF::Media::Types::ImagingOptions20

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
ImagingOptions20 from the namespace http://www.onvif.org/ver10/schema.






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


=item * IrCutFilterModes


=item * Sharpness


=item * WideDynamicRange


=item * WhiteBalance


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Media::Types::ImagingOptions20
   BacklightCompensation =>  { # ONVIF::Media::Types::BacklightCompensationOptions20
     Mode => $some_value, # BacklightCompensationMode
     Level =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   Brightness =>  { # ONVIF::Media::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   ColorSaturation =>  { # ONVIF::Media::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   Contrast =>  { # ONVIF::Media::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   Exposure =>  { # ONVIF::Media::Types::ExposureOptions20
     Mode => $some_value, # ExposureMode
     Priority => $some_value, # ExposurePriority
     MinExposureTime =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MaxExposureTime =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MinGain =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MaxGain =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MinIris =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MaxIris =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     ExposureTime =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Gain =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Iris =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   Focus =>  { # ONVIF::Media::Types::FocusOptions20
     AutoFocusModes => $some_value, # AutoFocusMode
     DefaultSpeed =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     NearLimit =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     FarLimit =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Extension =>  { # ONVIF::Media::Types::FocusOptions20Extension
     },
   },
   IrCutFilterModes => $some_value, # IrCutFilterMode
   Sharpness =>  { # ONVIF::Media::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   WideDynamicRange =>  { # ONVIF::Media::Types::WideDynamicRangeOptions20
     Mode => $some_value, # WideDynamicMode
     Level =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   WhiteBalance =>  { # ONVIF::Media::Types::WhiteBalanceOptions20
     Mode => $some_value, # WhiteBalanceMode
     YrGain =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     YbGain =>  { # ONVIF::Media::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Extension =>  { # ONVIF::Media::Types::WhiteBalanceOptions20Extension
     },
   },
   Extension =>  { # ONVIF::Media::Types::ImagingOptions20Extension
     ImageStabilization =>  { # ONVIF::Media::Types::ImageStabilizationOptions
       Mode => $some_value, # ImageStabilizationMode
       Level =>  { # ONVIF::Media::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
       Extension =>  { # ONVIF::Media::Types::ImageStabilizationOptionsExtension
       },
     },
     Extension =>  { # ONVIF::Media::Types::ImagingOptions20Extension2
       IrCutFilterAutoAdjustment =>  { # ONVIF::Media::Types::IrCutFilterAutoAdjustmentOptions
         BoundaryType =>  $some_value, # string
         BoundaryOffset =>  $some_value, # boolean
         ResponseTimeRange =>  { # ONVIF::Media::Types::DurationRange
           Min =>  $some_value, # duration
           Max =>  $some_value, # duration
         },
         Extension =>  { # ONVIF::Media::Types::IrCutFilterAutoAdjustmentOptionsExtension
         },
       },
       Extension =>  { # ONVIF::Media::Types::ImagingOptions20Extension3
       },
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

