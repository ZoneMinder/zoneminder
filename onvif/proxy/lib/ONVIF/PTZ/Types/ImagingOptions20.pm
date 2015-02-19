package ONVIF::PTZ::Types::ImagingOptions20;
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
        'BacklightCompensation' => 'ONVIF::PTZ::Types::BacklightCompensationOptions20',
        'Brightness' => 'ONVIF::PTZ::Types::FloatRange',
        'ColorSaturation' => 'ONVIF::PTZ::Types::FloatRange',
        'Contrast' => 'ONVIF::PTZ::Types::FloatRange',
        'Exposure' => 'ONVIF::PTZ::Types::ExposureOptions20',
        'Focus' => 'ONVIF::PTZ::Types::FocusOptions20',
        'IrCutFilterModes' => 'ONVIF::PTZ::Types::IrCutFilterMode',
        'Sharpness' => 'ONVIF::PTZ::Types::FloatRange',
        'WideDynamicRange' => 'ONVIF::PTZ::Types::WideDynamicRangeOptions20',
        'WhiteBalance' => 'ONVIF::PTZ::Types::WhiteBalanceOptions20',
        'Extension' => 'ONVIF::PTZ::Types::ImagingOptions20Extension',
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

ONVIF::PTZ::Types::ImagingOptions20

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

 { # ONVIF::PTZ::Types::ImagingOptions20
   BacklightCompensation =>  { # ONVIF::PTZ::Types::BacklightCompensationOptions20
     Mode => $some_value, # BacklightCompensationMode
     Level =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   Brightness =>  { # ONVIF::PTZ::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   ColorSaturation =>  { # ONVIF::PTZ::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   Contrast =>  { # ONVIF::PTZ::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   Exposure =>  { # ONVIF::PTZ::Types::ExposureOptions20
     Mode => $some_value, # ExposureMode
     Priority => $some_value, # ExposurePriority
     MinExposureTime =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MaxExposureTime =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MinGain =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MaxGain =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MinIris =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     MaxIris =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     ExposureTime =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Gain =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Iris =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   Focus =>  { # ONVIF::PTZ::Types::FocusOptions20
     AutoFocusModes => $some_value, # AutoFocusMode
     DefaultSpeed =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     NearLimit =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     FarLimit =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Extension =>  { # ONVIF::PTZ::Types::FocusOptions20Extension
     },
   },
   IrCutFilterModes => $some_value, # IrCutFilterMode
   Sharpness =>  { # ONVIF::PTZ::Types::FloatRange
     Min =>  $some_value, # float
     Max =>  $some_value, # float
   },
   WideDynamicRange =>  { # ONVIF::PTZ::Types::WideDynamicRangeOptions20
     Mode => $some_value, # WideDynamicMode
     Level =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
   },
   WhiteBalance =>  { # ONVIF::PTZ::Types::WhiteBalanceOptions20
     Mode => $some_value, # WhiteBalanceMode
     YrGain =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     YbGain =>  { # ONVIF::PTZ::Types::FloatRange
       Min =>  $some_value, # float
       Max =>  $some_value, # float
     },
     Extension =>  { # ONVIF::PTZ::Types::WhiteBalanceOptions20Extension
     },
   },
   Extension =>  { # ONVIF::PTZ::Types::ImagingOptions20Extension
     ImageStabilization =>  { # ONVIF::PTZ::Types::ImageStabilizationOptions
       Mode => $some_value, # ImageStabilizationMode
       Level =>  { # ONVIF::PTZ::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
       Extension =>  { # ONVIF::PTZ::Types::ImageStabilizationOptionsExtension
       },
     },
     Extension =>  { # ONVIF::PTZ::Types::ImagingOptions20Extension2
       IrCutFilterAutoAdjustment =>  { # ONVIF::PTZ::Types::IrCutFilterAutoAdjustmentOptions
         BoundaryType =>  $some_value, # string
         BoundaryOffset =>  $some_value, # boolean
         ResponseTimeRange =>  { # ONVIF::PTZ::Types::DurationRange
           Min =>  $some_value, # duration
           Max =>  $some_value, # duration
         },
         Extension =>  { # ONVIF::PTZ::Types::IrCutFilterAutoAdjustmentOptionsExtension
         },
       },
       Extension =>  { # ONVIF::PTZ::Types::ImagingOptions20Extension3
       },
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

