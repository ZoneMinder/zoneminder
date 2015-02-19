
package ONVIF::Media::Elements::CreateProfileResponse;
use strict;
use warnings;

{ # BLOCK to scope variables

sub get_xmlns { 'http://www.onvif.org/ver10/media/wsdl' }

__PACKAGE__->__set_name('CreateProfileResponse');
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

my %Profile_of :ATTR(:get<Profile>);

__PACKAGE__->_factory(
    [ qw(        Profile

    ) ],
    {
        'Profile' => \%Profile_of,
    },
    {
        'Profile' => 'ONVIF::Media::Types::Profile',
    },
    {

        'Profile' => 'Profile',
    }
);

} # end BLOCK







} # end of BLOCK



1;


=pod

=head1 NAME

ONVIF::Media::Elements::CreateProfileResponse

=head1 DESCRIPTION

Perl data type class for the XML Schema defined element
CreateProfileResponse from the namespace http://www.onvif.org/ver10/media/wsdl.







=head1 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * Profile

 $element->set_Profile($data);
 $element->get_Profile();





=back


=head1 METHODS

=head2 new

 my $element = ONVIF::Media::Elements::CreateProfileResponse->new($data);

Constructor. The following data structure may be passed to new():

 {
   Profile =>  { # ONVIF::Media::Types::Profile
     Name => $some_value, # Name
     VideoSourceConfiguration =>  { # ONVIF::Media::Types::VideoSourceConfiguration
       SourceToken => $some_value, # ReferenceToken
       Bounds => ,
       Extension =>  { # ONVIF::Media::Types::VideoSourceConfigurationExtension
         Rotate =>  { # ONVIF::Media::Types::Rotate
           Mode => $some_value, # RotateMode
           Degree =>  $some_value, # int
           Extension =>  { # ONVIF::Media::Types::RotateExtension
           },
         },
         Extension =>  { # ONVIF::Media::Types::VideoSourceConfigurationExtension2
         },
       },
     },
     AudioSourceConfiguration =>  { # ONVIF::Media::Types::AudioSourceConfiguration
       SourceToken => $some_value, # ReferenceToken
     },
     VideoEncoderConfiguration =>  { # ONVIF::Media::Types::VideoEncoderConfiguration
       Encoding => $some_value, # VideoEncoding
       Resolution =>  { # ONVIF::Media::Types::VideoResolution
         Width =>  $some_value, # int
         Height =>  $some_value, # int
       },
       Quality =>  $some_value, # float
       RateControl =>  { # ONVIF::Media::Types::VideoRateControl
         FrameRateLimit =>  $some_value, # int
         EncodingInterval =>  $some_value, # int
         BitrateLimit =>  $some_value, # int
       },
       MPEG4 =>  { # ONVIF::Media::Types::Mpeg4Configuration
         GovLength =>  $some_value, # int
         Mpeg4Profile => $some_value, # Mpeg4Profile
       },
       H264 =>  { # ONVIF::Media::Types::H264Configuration
         GovLength =>  $some_value, # int
         H264Profile => $some_value, # H264Profile
       },
       Multicast =>  { # ONVIF::Media::Types::MulticastConfiguration
         Address =>  { # ONVIF::Media::Types::IPAddress
           Type => $some_value, # IPType
           IPv4Address => $some_value, # IPv4Address
           IPv6Address => $some_value, # IPv6Address
         },
         Port =>  $some_value, # int
         TTL =>  $some_value, # int
         AutoStart =>  $some_value, # boolean
       },
       SessionTimeout =>  $some_value, # duration
     },
     AudioEncoderConfiguration =>  { # ONVIF::Media::Types::AudioEncoderConfiguration
       Encoding => $some_value, # AudioEncoding
       Bitrate =>  $some_value, # int
       SampleRate =>  $some_value, # int
       Multicast =>  { # ONVIF::Media::Types::MulticastConfiguration
         Address =>  { # ONVIF::Media::Types::IPAddress
           Type => $some_value, # IPType
           IPv4Address => $some_value, # IPv4Address
           IPv6Address => $some_value, # IPv6Address
         },
         Port =>  $some_value, # int
         TTL =>  $some_value, # int
         AutoStart =>  $some_value, # boolean
       },
       SessionTimeout =>  $some_value, # duration
     },
     VideoAnalyticsConfiguration =>  { # ONVIF::Media::Types::VideoAnalyticsConfiguration
       AnalyticsEngineConfiguration =>  { # ONVIF::Media::Types::AnalyticsEngineConfiguration
         AnalyticsModule =>  { # ONVIF::Media::Types::Config
           Parameters =>  { # ONVIF::Media::Types::ItemList
             SimpleItem => ,
             ElementItem =>  {
             },
             Extension =>  { # ONVIF::Media::Types::ItemListExtension
             },
           },
         },
         Extension =>  { # ONVIF::Media::Types::AnalyticsEngineConfigurationExtension
         },
       },
       RuleEngineConfiguration =>  { # ONVIF::Media::Types::RuleEngineConfiguration
         Rule =>  { # ONVIF::Media::Types::Config
           Parameters =>  { # ONVIF::Media::Types::ItemList
             SimpleItem => ,
             ElementItem =>  {
             },
             Extension =>  { # ONVIF::Media::Types::ItemListExtension
             },
           },
         },
         Extension =>  { # ONVIF::Media::Types::RuleEngineConfigurationExtension
         },
       },
     },
     PTZConfiguration =>  { # ONVIF::Media::Types::PTZConfiguration
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
     MetadataConfiguration =>  { # ONVIF::Media::Types::MetadataConfiguration
       PTZStatus =>  { # ONVIF::Media::Types::PTZFilter
         Status =>  $some_value, # boolean
         Position =>  $some_value, # boolean
       },
       Analytics =>  $some_value, # boolean
       Multicast =>  { # ONVIF::Media::Types::MulticastConfiguration
         Address =>  { # ONVIF::Media::Types::IPAddress
           Type => $some_value, # IPType
           IPv4Address => $some_value, # IPv4Address
           IPv6Address => $some_value, # IPv6Address
         },
         Port =>  $some_value, # int
         TTL =>  $some_value, # int
         AutoStart =>  $some_value, # boolean
       },
       SessionTimeout =>  $some_value, # duration
       AnalyticsEngineConfiguration =>  { # ONVIF::Media::Types::AnalyticsEngineConfiguration
         AnalyticsModule =>  { # ONVIF::Media::Types::Config
           Parameters =>  { # ONVIF::Media::Types::ItemList
             SimpleItem => ,
             ElementItem =>  {
             },
             Extension =>  { # ONVIF::Media::Types::ItemListExtension
             },
           },
         },
         Extension =>  { # ONVIF::Media::Types::AnalyticsEngineConfigurationExtension
         },
       },
       Extension =>  { # ONVIF::Media::Types::MetadataConfigurationExtension
       },
     },
     Extension =>  { # ONVIF::Media::Types::ProfileExtension
       AudioOutputConfiguration =>  { # ONVIF::Media::Types::AudioOutputConfiguration
         OutputToken => $some_value, # ReferenceToken
         SendPrimacy =>  $some_value, # anyURI
         OutputLevel =>  $some_value, # int
       },
       AudioDecoderConfiguration =>  { # ONVIF::Media::Types::AudioDecoderConfiguration
       },
       Extension =>  { # ONVIF::Media::Types::ProfileExtension2
       },
     },
   },
 },

=head1 AUTHOR

Generated by SOAP::WSDL

=cut

