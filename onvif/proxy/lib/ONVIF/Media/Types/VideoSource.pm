package ONVIF::Media::Types::VideoSource;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS;
undef $XML_ATTRIBUTE_CLASS;

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}


use base qw(ONVIF::Media::Types::DeviceEntity);
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
        'Resolution' => 'ONVIF::Media::Types::VideoResolution',
        'Imaging' => 'ONVIF::Media::Types::ImagingSettings',
        'Extension' => 'ONVIF::Media::Types::VideoSourceExtension',
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

ONVIF::Media::Types::VideoSource

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

 { # ONVIF::Media::Types::VideoSource
   Framerate =>  $some_value, # float
   Resolution =>  { # ONVIF::Media::Types::VideoResolution
     Width =>  $some_value, # int
     Height =>  $some_value, # int
   },
   Imaging =>  { # ONVIF::Media::Types::ImagingSettings
     BacklightCompensation =>  { # ONVIF::Media::Types::BacklightCompensation
       Mode => $some_value, # BacklightCompensationMode
       Level =>  $some_value, # float
     },
     Brightness =>  $some_value, # float
     ColorSaturation =>  $some_value, # float
     Contrast =>  $some_value, # float
     Exposure =>  { # ONVIF::Media::Types::Exposure
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
     Focus =>  { # ONVIF::Media::Types::FocusConfiguration
       AutoFocusMode => $some_value, # AutoFocusMode
       DefaultSpeed =>  $some_value, # float
       NearLimit =>  $some_value, # float
       FarLimit =>  $some_value, # float
     },
     IrCutFilter => $some_value, # IrCutFilterMode
     Sharpness =>  $some_value, # float
     WideDynamicRange =>  { # ONVIF::Media::Types::WideDynamicRange
       Mode => $some_value, # WideDynamicMode
       Level =>  $some_value, # float
     },
     WhiteBalance =>  { # ONVIF::Media::Types::WhiteBalance
       Mode => $some_value, # WhiteBalanceMode
       CrGain =>  $some_value, # float
       CbGain =>  $some_value, # float
     },
     Extension =>  { # ONVIF::Media::Types::ImagingSettingsExtension
     },
   },
   Extension =>  { # ONVIF::Media::Types::VideoSourceExtension
     Imaging =>  { # ONVIF::Media::Types::ImagingSettings20
       BacklightCompensation =>  { # ONVIF::Media::Types::BacklightCompensation20
         Mode => $some_value, # BacklightCompensationMode
         Level =>  $some_value, # float
       },
       Brightness =>  $some_value, # float
       ColorSaturation =>  $some_value, # float
       Contrast =>  $some_value, # float
       Exposure =>  { # ONVIF::Media::Types::Exposure20
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
       Focus =>  { # ONVIF::Media::Types::FocusConfiguration20
         AutoFocusMode => $some_value, # AutoFocusMode
         DefaultSpeed =>  $some_value, # float
         NearLimit =>  $some_value, # float
         FarLimit =>  $some_value, # float
         Extension =>  { # ONVIF::Media::Types::FocusConfiguration20Extension
         },
       },
       IrCutFilter => $some_value, # IrCutFilterMode
       Sharpness =>  $some_value, # float
       WideDynamicRange =>  { # ONVIF::Media::Types::WideDynamicRange20
         Mode => $some_value, # WideDynamicMode
         Level =>  $some_value, # float
       },
       WhiteBalance =>  { # ONVIF::Media::Types::WhiteBalance20
         Mode => $some_value, # WhiteBalanceMode
         CrGain =>  $some_value, # float
         CbGain =>  $some_value, # float
         Extension =>  { # ONVIF::Media::Types::WhiteBalance20Extension
         },
       },
       Extension =>  { # ONVIF::Media::Types::ImagingSettingsExtension20
         ImageStabilization =>  { # ONVIF::Media::Types::ImageStabilization
           Mode => $some_value, # ImageStabilizationMode
           Level =>  $some_value, # float
           Extension =>  { # ONVIF::Media::Types::ImageStabilizationExtension
           },
         },
         Extension =>  { # ONVIF::Media::Types::ImagingSettingsExtension202
           IrCutFilterAutoAdjustment =>  { # ONVIF::Media::Types::IrCutFilterAutoAdjustment
             BoundaryType =>  $some_value, # string
             BoundaryOffset =>  $some_value, # float
             ResponseTime =>  $some_value, # duration
             Extension =>  { # ONVIF::Media::Types::IrCutFilterAutoAdjustmentExtension
             },
           },
           Extension =>  { # ONVIF::Media::Types::ImagingSettingsExtension203
           },
         },
       },
     },
     Extension =>  { # ONVIF::Media::Types::VideoSourceExtension2
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

