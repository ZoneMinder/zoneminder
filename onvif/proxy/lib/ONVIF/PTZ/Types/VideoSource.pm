package ONVIF::PTZ::Types::VideoSource;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS;
undef $XML_ATTRIBUTE_CLASS;

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}


use base qw(ONVIF::PTZ::Types::DeviceEntity);
# Variety: sequence
use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %Framerate_of :ATTR(:get<Framerate>);
my %Resolution_of :ATTR(:get<Resolution>);
my %Imaging_of :ATTR(:get<Imaging>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        Framerate
        Resolution
        Imaging
        Extension

    ) ],
    {
        'Framerate' => \%Framerate_of,
        'Resolution' => \%Resolution_of,
        'Imaging' => \%Imaging_of,
        'Extension' => \%Extension_of,
    },
    {
        'Framerate' => 'SOAP::WSDL::XSD::Typelib::Builtin::float',
        'Resolution' => 'ONVIF::PTZ::Types::VideoResolution',
        'Imaging' => 'ONVIF::PTZ::Types::ImagingSettings',
        'Extension' => 'ONVIF::PTZ::Types::VideoSourceExtension',
    },
    {

        'Framerate' => 'Framerate',
        'Resolution' => 'Resolution',
        'Imaging' => 'Imaging',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::PTZ::Types::VideoSource

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
VideoSource from the namespace http://www.onvif.org/ver10/schema.

Representation of a physical video input.




=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * Framerate


=item * Resolution


=item * Imaging


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::PTZ::Types::VideoSource
   Framerate =>  $some_value, # float
   Resolution =>  { # ONVIF::PTZ::Types::VideoResolution
     Width =>  $some_value, # int
     Height =>  $some_value, # int
   },
   Imaging =>  { # ONVIF::PTZ::Types::ImagingSettings
     BacklightCompensation =>  { # ONVIF::PTZ::Types::BacklightCompensation
       Mode => $some_value, # BacklightCompensationMode
       Level =>  $some_value, # float
     },
     Brightness =>  $some_value, # float
     ColorSaturation =>  $some_value, # float
     Contrast =>  $some_value, # float
     Exposure =>  { # ONVIF::PTZ::Types::Exposure
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
     Focus =>  { # ONVIF::PTZ::Types::FocusConfiguration
       AutoFocusMode => $some_value, # AutoFocusMode
       DefaultSpeed =>  $some_value, # float
       NearLimit =>  $some_value, # float
       FarLimit =>  $some_value, # float
     },
     IrCutFilter => $some_value, # IrCutFilterMode
     Sharpness =>  $some_value, # float
     WideDynamicRange =>  { # ONVIF::PTZ::Types::WideDynamicRange
       Mode => $some_value, # WideDynamicMode
       Level =>  $some_value, # float
     },
     WhiteBalance =>  { # ONVIF::PTZ::Types::WhiteBalance
       Mode => $some_value, # WhiteBalanceMode
       CrGain =>  $some_value, # float
       CbGain =>  $some_value, # float
     },
     Extension =>  { # ONVIF::PTZ::Types::ImagingSettingsExtension
     },
   },
   Extension =>  { # ONVIF::PTZ::Types::VideoSourceExtension
     Imaging =>  { # ONVIF::PTZ::Types::ImagingSettings20
       BacklightCompensation =>  { # ONVIF::PTZ::Types::BacklightCompensation20
         Mode => $some_value, # BacklightCompensationMode
         Level =>  $some_value, # float
       },
       Brightness =>  $some_value, # float
       ColorSaturation =>  $some_value, # float
       Contrast =>  $some_value, # float
       Exposure =>  { # ONVIF::PTZ::Types::Exposure20
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
       Focus =>  { # ONVIF::PTZ::Types::FocusConfiguration20
         AutoFocusMode => $some_value, # AutoFocusMode
         DefaultSpeed =>  $some_value, # float
         NearLimit =>  $some_value, # float
         FarLimit =>  $some_value, # float
         Extension =>  { # ONVIF::PTZ::Types::FocusConfiguration20Extension
         },
       },
       IrCutFilter => $some_value, # IrCutFilterMode
       Sharpness =>  $some_value, # float
       WideDynamicRange =>  { # ONVIF::PTZ::Types::WideDynamicRange20
         Mode => $some_value, # WideDynamicMode
         Level =>  $some_value, # float
       },
       WhiteBalance =>  { # ONVIF::PTZ::Types::WhiteBalance20
         Mode => $some_value, # WhiteBalanceMode
         CrGain =>  $some_value, # float
         CbGain =>  $some_value, # float
         Extension =>  { # ONVIF::PTZ::Types::WhiteBalance20Extension
         },
       },
       Extension =>  { # ONVIF::PTZ::Types::ImagingSettingsExtension20
         ImageStabilization =>  { # ONVIF::PTZ::Types::ImageStabilization
           Mode => $some_value, # ImageStabilizationMode
           Level =>  $some_value, # float
           Extension =>  { # ONVIF::PTZ::Types::ImageStabilizationExtension
           },
         },
         Extension =>  { # ONVIF::PTZ::Types::ImagingSettingsExtension202
           IrCutFilterAutoAdjustment =>  { # ONVIF::PTZ::Types::IrCutFilterAutoAdjustment
             BoundaryType =>  $some_value, # string
             BoundaryOffset =>  $some_value, # float
             ResponseTime =>  $some_value, # duration
             Extension =>  { # ONVIF::PTZ::Types::IrCutFilterAutoAdjustmentExtension
             },
           },
           Extension =>  { # ONVIF::PTZ::Types::ImagingSettingsExtension203
           },
         },
       },
     },
     Extension =>  { # ONVIF::PTZ::Types::VideoSourceExtension2
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

