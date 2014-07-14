
package ONVIF::Media::Elements::GetVideoSourcesResponse;
use strict;
use warnings;

{ # BLOCK to scope variables

sub get_xmlns { 'http://www.onvif.org/ver10/media/wsdl' }

__PACKAGE__->__set_name('GetVideoSourcesResponse');
__PACKAGE__->__set_nillable();
__PACKAGE__->__set_minOccurs();
__PACKAGE__->__set_maxOccurs();
__PACKAGE__->__set_ref();

use base qw(
    SOAP::WSDL::XSD::Typelib::Element
    SOAP::WSDL::XSD::Typelib::ComplexType
);

our $XML_ATTRIBUTE_CLASS;
undef $XML_ATTRIBUTE_CLASS;

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}

use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %VideoSources_of :ATTR(:get<VideoSources>);

__PACKAGE__->_factory(
    [ qw(        VideoSources

    ) ],
    {
        'VideoSources' => \%VideoSources_of,
    },
    {
        'VideoSources' => 'ONVIF::Media::Types::VideoSource',
    },
    {

        'VideoSources' => 'VideoSources',
    }
);

} # end BLOCK







} # end of BLOCK



1;


=pod

=head1 NAME

ONVIF::Media::Elements::GetVideoSourcesResponse

=head1 DESCRIPTION

Perl data type class for the XML Schema defined element
GetVideoSourcesResponse from the namespace http://www.onvif.org/ver10/media/wsdl.







=head1 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * VideoSources

 $element->set_VideoSources($data);
 $element->get_VideoSources();





=back


=head1 METHODS

=head2 new

 my $element = ONVIF::Media::Elements::GetVideoSourcesResponse->new($data);

Constructor. The following data structure may be passed to new():

 {
   VideoSources =>  { # ONVIF::Media::Types::VideoSource
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
 },

=head1 AUTHOR

Generated by SOAP::WSDL

=cut

