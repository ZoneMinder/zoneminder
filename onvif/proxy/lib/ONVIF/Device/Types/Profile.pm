package ONVIF::Device::Types::Profile;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS = 'ONVIF::Device::Types::Profile::_Profile::XmlAttr';

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}

use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %Name_of :ATTR(:get<Name>);
my %VideoSourceConfiguration_of :ATTR(:get<VideoSourceConfiguration>);
my %AudioSourceConfiguration_of :ATTR(:get<AudioSourceConfiguration>);
my %VideoEncoderConfiguration_of :ATTR(:get<VideoEncoderConfiguration>);
my %AudioEncoderConfiguration_of :ATTR(:get<AudioEncoderConfiguration>);
my %VideoAnalyticsConfiguration_of :ATTR(:get<VideoAnalyticsConfiguration>);
my %PTZConfiguration_of :ATTR(:get<PTZConfiguration>);
my %MetadataConfiguration_of :ATTR(:get<MetadataConfiguration>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        Name
        VideoSourceConfiguration
        AudioSourceConfiguration
        VideoEncoderConfiguration
        AudioEncoderConfiguration
        VideoAnalyticsConfiguration
        PTZConfiguration
        MetadataConfiguration
        Extension

    ) ],
    {
        'Name' => \%Name_of,
        'VideoSourceConfiguration' => \%VideoSourceConfiguration_of,
        'AudioSourceConfiguration' => \%AudioSourceConfiguration_of,
        'VideoEncoderConfiguration' => \%VideoEncoderConfiguration_of,
        'AudioEncoderConfiguration' => \%AudioEncoderConfiguration_of,
        'VideoAnalyticsConfiguration' => \%VideoAnalyticsConfiguration_of,
        'PTZConfiguration' => \%PTZConfiguration_of,
        'MetadataConfiguration' => \%MetadataConfiguration_of,
        'Extension' => \%Extension_of,
    },
    {
        'Name' => 'ONVIF::Device::Types::Name',
        'VideoSourceConfiguration' => 'ONVIF::Device::Types::VideoSourceConfiguration',
        'AudioSourceConfiguration' => 'ONVIF::Device::Types::AudioSourceConfiguration',
        'VideoEncoderConfiguration' => 'ONVIF::Device::Types::VideoEncoderConfiguration',
        'AudioEncoderConfiguration' => 'ONVIF::Device::Types::AudioEncoderConfiguration',
        'VideoAnalyticsConfiguration' => 'ONVIF::Device::Types::VideoAnalyticsConfiguration',
        'PTZConfiguration' => 'ONVIF::Device::Types::PTZConfiguration',
        'MetadataConfiguration' => 'ONVIF::Device::Types::MetadataConfiguration',
        'Extension' => 'ONVIF::Device::Types::ProfileExtension',
    },
    {

        'Name' => 'Name',
        'VideoSourceConfiguration' => 'VideoSourceConfiguration',
        'AudioSourceConfiguration' => 'AudioSourceConfiguration',
        'VideoEncoderConfiguration' => 'VideoEncoderConfiguration',
        'AudioEncoderConfiguration' => 'AudioEncoderConfiguration',
        'VideoAnalyticsConfiguration' => 'VideoAnalyticsConfiguration',
        'PTZConfiguration' => 'PTZConfiguration',
        'MetadataConfiguration' => 'MetadataConfiguration',
        'Extension' => 'Extension',
    }
);

} # end BLOCK




package ONVIF::Device::Types::Profile::_Profile::XmlAttr;
use base qw(SOAP::WSDL::XSD::Typelib::AttributeSet);

{ # BLOCK to scope variables

my %token_of :ATTR(:get<token>);
my %fixed_of :ATTR(:get<fixed>);

__PACKAGE__->_factory(
    [ qw(
        token
        fixed
    ) ],
    {

        token => \%token_of,

        fixed => \%fixed_of,
    },
    {
        token => 'ONVIF::Device::Types::ReferenceToken',
        fixed => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
    }
);

} # end BLOCK




1;


=pod

=head1 NAME

ONVIF::Device::Types::Profile

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
Profile from the namespace http://www.onvif.org/ver10/schema.

A profile consists of a set of interconnected configuration entities. Configurations are provided by the NVT and can be either static or created dynamically by the NVT. For example, the dynamic configurations can be created by the NVT depending on current available encoding resources. 




=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * Name


=item * VideoSourceConfiguration


=item * AudioSourceConfiguration


=item * VideoEncoderConfiguration


=item * AudioEncoderConfiguration


=item * VideoAnalyticsConfiguration


=item * PTZConfiguration


=item * MetadataConfiguration


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Device::Types::Profile
   Name => $some_value, # Name
   VideoSourceConfiguration =>  { # ONVIF::Device::Types::VideoSourceConfiguration
     SourceToken => $some_value, # ReferenceToken
     Bounds => ,
     Extension =>  { # ONVIF::Device::Types::VideoSourceConfigurationExtension
       Rotate =>  { # ONVIF::Device::Types::Rotate
         Mode => $some_value, # RotateMode
         Degree =>  $some_value, # int
         Extension =>  { # ONVIF::Device::Types::RotateExtension
         },
       },
       Extension =>  { # ONVIF::Device::Types::VideoSourceConfigurationExtension2
       },
     },
   },
   AudioSourceConfiguration =>  { # ONVIF::Device::Types::AudioSourceConfiguration
     SourceToken => $some_value, # ReferenceToken
   },
   VideoEncoderConfiguration =>  { # ONVIF::Device::Types::VideoEncoderConfiguration
     Encoding => $some_value, # VideoEncoding
     Resolution =>  { # ONVIF::Device::Types::VideoResolution
       Width =>  $some_value, # int
       Height =>  $some_value, # int
     },
     Quality =>  $some_value, # float
     RateControl =>  { # ONVIF::Device::Types::VideoRateControl
       FrameRateLimit =>  $some_value, # int
       EncodingInterval =>  $some_value, # int
       BitrateLimit =>  $some_value, # int
     },
     MPEG4 =>  { # ONVIF::Device::Types::Mpeg4Configuration
       GovLength =>  $some_value, # int
       Mpeg4Profile => $some_value, # Mpeg4Profile
     },
     H264 =>  { # ONVIF::Device::Types::H264Configuration
       GovLength =>  $some_value, # int
       H264Profile => $some_value, # H264Profile
     },
     Multicast =>  { # ONVIF::Device::Types::MulticastConfiguration
       Address =>  { # ONVIF::Device::Types::IPAddress
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
   AudioEncoderConfiguration =>  { # ONVIF::Device::Types::AudioEncoderConfiguration
     Encoding => $some_value, # AudioEncoding
     Bitrate =>  $some_value, # int
     SampleRate =>  $some_value, # int
     Multicast =>  { # ONVIF::Device::Types::MulticastConfiguration
       Address =>  { # ONVIF::Device::Types::IPAddress
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
   VideoAnalyticsConfiguration =>  { # ONVIF::Device::Types::VideoAnalyticsConfiguration
     AnalyticsEngineConfiguration =>  { # ONVIF::Device::Types::AnalyticsEngineConfiguration
       AnalyticsModule =>  { # ONVIF::Device::Types::Config
         Parameters =>  { # ONVIF::Device::Types::ItemList
           SimpleItem => ,
           ElementItem =>  {
           },
           Extension =>  { # ONVIF::Device::Types::ItemListExtension
           },
         },
       },
       Extension =>  { # ONVIF::Device::Types::AnalyticsEngineConfigurationExtension
       },
     },
     RuleEngineConfiguration =>  { # ONVIF::Device::Types::RuleEngineConfiguration
       Rule =>  { # ONVIF::Device::Types::Config
         Parameters =>  { # ONVIF::Device::Types::ItemList
           SimpleItem => ,
           ElementItem =>  {
           },
           Extension =>  { # ONVIF::Device::Types::ItemListExtension
           },
         },
       },
       Extension =>  { # ONVIF::Device::Types::RuleEngineConfigurationExtension
       },
     },
   },
   PTZConfiguration =>  { # ONVIF::Device::Types::PTZConfiguration
     NodeToken => $some_value, # ReferenceToken
     DefaultAbsolutePantTiltPositionSpace =>  $some_value, # anyURI
     DefaultAbsoluteZoomPositionSpace =>  $some_value, # anyURI
     DefaultRelativePanTiltTranslationSpace =>  $some_value, # anyURI
     DefaultRelativeZoomTranslationSpace =>  $some_value, # anyURI
     DefaultContinuousPanTiltVelocitySpace =>  $some_value, # anyURI
     DefaultContinuousZoomVelocitySpace =>  $some_value, # anyURI
     DefaultPTZSpeed =>  { # ONVIF::Device::Types::PTZSpeed
       PanTilt => ,
       Zoom => ,
     },
     DefaultPTZTimeout =>  $some_value, # duration
     PanTiltLimits =>  { # ONVIF::Device::Types::PanTiltLimits
       Range =>  { # ONVIF::Device::Types::Space2DDescription
         URI =>  $some_value, # anyURI
         XRange =>  { # ONVIF::Device::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
         YRange =>  { # ONVIF::Device::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
       },
     },
     ZoomLimits =>  { # ONVIF::Device::Types::ZoomLimits
       Range =>  { # ONVIF::Device::Types::Space1DDescription
         URI =>  $some_value, # anyURI
         XRange =>  { # ONVIF::Device::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
       },
     },
     Extension =>  { # ONVIF::Device::Types::PTZConfigurationExtension
       PTControlDirection =>  { # ONVIF::Device::Types::PTControlDirection
         EFlip =>  { # ONVIF::Device::Types::EFlip
           Mode => $some_value, # EFlipMode
         },
         Reverse =>  { # ONVIF::Device::Types::Reverse
           Mode => $some_value, # ReverseMode
         },
         Extension =>  { # ONVIF::Device::Types::PTControlDirectionExtension
         },
       },
       Extension =>  { # ONVIF::Device::Types::PTZConfigurationExtension2
       },
     },
   },
   MetadataConfiguration =>  { # ONVIF::Device::Types::MetadataConfiguration
     PTZStatus =>  { # ONVIF::Device::Types::PTZFilter
       Status =>  $some_value, # boolean
       Position =>  $some_value, # boolean
     },
     Analytics =>  $some_value, # boolean
     Multicast =>  { # ONVIF::Device::Types::MulticastConfiguration
       Address =>  { # ONVIF::Device::Types::IPAddress
         Type => $some_value, # IPType
         IPv4Address => $some_value, # IPv4Address
         IPv6Address => $some_value, # IPv6Address
       },
       Port =>  $some_value, # int
       TTL =>  $some_value, # int
       AutoStart =>  $some_value, # boolean
     },
     SessionTimeout =>  $some_value, # duration
     AnalyticsEngineConfiguration =>  { # ONVIF::Device::Types::AnalyticsEngineConfiguration
       AnalyticsModule =>  { # ONVIF::Device::Types::Config
         Parameters =>  { # ONVIF::Device::Types::ItemList
           SimpleItem => ,
           ElementItem =>  {
           },
           Extension =>  { # ONVIF::Device::Types::ItemListExtension
           },
         },
       },
       Extension =>  { # ONVIF::Device::Types::AnalyticsEngineConfigurationExtension
       },
     },
     Extension =>  { # ONVIF::Device::Types::MetadataConfigurationExtension
     },
   },
   Extension =>  { # ONVIF::Device::Types::ProfileExtension
     AudioOutputConfiguration =>  { # ONVIF::Device::Types::AudioOutputConfiguration
       OutputToken => $some_value, # ReferenceToken
       SendPrimacy =>  $some_value, # anyURI
       OutputLevel =>  $some_value, # int
     },
     AudioDecoderConfiguration =>  { # ONVIF::Device::Types::AudioDecoderConfiguration
     },
     Extension =>  { # ONVIF::Device::Types::ProfileExtension2
     },
   },
 },



=head2 attr

NOTE: Attribute documentation is experimental, and may be inaccurate.
See the correspondent WSDL/XML Schema if in question.

This class has additional attributes, accessibly via the C<attr()> method.

attr() returns an object of the class ONVIF::Device::Types::Profile::_Profile::XmlAttr.

The following attributes can be accessed on this object via the corresponding
get_/set_ methods:

=over

=item * token

 Unique identifier of the profile.



This attribute is of type L<ONVIF::Device::Types::ReferenceToken|ONVIF::Device::Types::ReferenceToken>.

=item * fixed

 A value of true signals that the profile cannot be deleted. Default is false.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.


=back




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

