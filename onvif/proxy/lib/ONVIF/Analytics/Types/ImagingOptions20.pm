package ONVIF::Analytics::Types::ImagingOptions20;
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
        'BacklightCompensation' => 'ONVIF::Analytics::Types::BacklightCompensationOptions20',
        'Brightness' => 'ONVIF::Analytics::Types::FloatRange',
        'ColorSaturation' => 'ONVIF::Analytics::Types::FloatRange',
        'Contrast' => 'ONVIF::Analytics::Types::FloatRange',
        'Exposure' => 'ONVIF::Analytics::Types::ExposureOptions20',
        'Focus' => 'ONVIF::Analytics::Types::FocusOptions20',
        'IrCutFilterModes' => 'ONVIF::Analytics::Types::IrCutFilterMode',
        'Sharpness' => 'ONVIF::Analytics::Types::FloatRange',
        'WideDynamicRange' => 'ONVIF::Analytics::Types::WideDynamicRangeOptions20',
        'WhiteBalance' => 'ONVIF::Analytics::Types::WhiteBalanceOptions20',
        'Extension' => 'ONVIF::Analytics::Types::ImagingOptions20Extension',
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

ONVIF::Analytics::Types::ImagingOptions20

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

 { # ONVIF::Analytics::Types::ImagingOptions20
   BacklightCompensation =>  { # ONVIF::Analytics::Types::BacklightCompensationOptions20
     Mode => $some_value, # BacklightCompensationMode
     Level =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   Brightness =>  { # ONVIF::Analytics::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   ColorSaturation =>  { # ONVIF::Analytics::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   Contrast =>  { # ONVIF::Analytics::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   Exposure =>  { # ONVIF::Analytics::Types::ExposureOptions20
     Mode => $some_value, # ExposureMode
     Priority => $some_value, # ExposurePriority
     MinExposureTime =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MaxExposureTime =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MinGain =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MaxGain =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MinIris =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MaxIris =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     ExposureTime =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Gain =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Iris =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   Focus =>  { # ONVIF::Analytics::Types::FocusOptions20
     AutoFocusModes => $some_value, # AutoFocusMode
     DefaultSpeed =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     NearLimit =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     FarLimit =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Extension =>  { # ONVIF::Analytics::Types::FocusOptions20Extension
     },
   },
   IrCutFilterModes => $some_value, # IrCutFilterMode
   Sharpness =>  { # ONVIF::Analytics::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   WideDynamicRange =>  { # ONVIF::Analytics::Types::WideDynamicRangeOptions20
     Mode => $some_value, # WideDynamicMode
     Level =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   WhiteBalance =>  { # ONVIF::Analytics::Types::WhiteBalanceOptions20
     Mode => $some_value, # WhiteBalanceMode
     YrGain =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     YbGain =>  { # ONVIF::Analytics::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Extension =>  { # ONVIF::Analytics::Types::WhiteBalanceOptions20Extension
     },
   },
   Extension =>  { # ONVIF::Analytics::Types::ImagingOptions20Extension
     ImageStabilization =>  { # ONVIF::Analytics::Types::ImageStabilizationOptions
       Mode => $some_value, # ImageStabilizationMode
       Level =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
       Extension =>  { # ONVIF::Analytics::Types::ImageStabilizationOptionsExtension
       },
     },
     Extension =>  { # ONVIF::Analytics::Types::ImagingOptions20Extension2
       IrCutFilterAutoAdjustment =>  { # ONVIF::Analytics::Types::IrCutFilterAutoAdjustmentOptions
         BoundaryType =>  $some_value, # string
         BoundaryOffset =>  $some_value, # boolean
         ResponseTimeRange =>  { # ONVIF::Analytics::Types::DurationRange
           Min =>  $some_value, # duration
           Max =>  $some_value, # duration
         },
         Extension =>  { # ONVIF::Analytics::Types::IrCutFilterAutoAdjustmentOptionsExtension
         },
       },
       Extension =>  { # ONVIF::Analytics::Types::ImagingOptions20Extension3
       },
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

