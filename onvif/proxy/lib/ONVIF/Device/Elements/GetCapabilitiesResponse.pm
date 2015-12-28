
package ONVIF::Device::Elements::GetCapabilitiesResponse;
use strict;
use warnings;

{ # BLOCK to scope variables

sub get_xmlns { 'http://www.onvif.org/ver10/device/wsdl' }

__PACKAGE__->__set_name('GetCapabilitiesResponse');
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

my %Capabilities_of :ATTR(:get<Capabilities>);

__PACKAGE__->_factory(
    [ qw(        Capabilities

    ) ],
    {
        'Capabilities' => \%Capabilities_of,
    },
    {
        'Capabilities' => 'ONVIF::Device::Types::Capabilities',
    },
    {

        'Capabilities' => 'Capabilities',
    }
);

} # end BLOCK







} # end of BLOCK



1;


=pod

=head1 NAME

ONVIF::Device::Elements::GetCapabilitiesResponse

=head1 DESCRIPTION

Perl data type class for the XML Schema defined element
GetCapabilitiesResponse from the namespace http://www.onvif.org/ver10/device/wsdl.







=head1 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * Capabilities

 $element->set_Capabilities($data);
 $element->get_Capabilities();





=back


=head1 METHODS

=head2 new

 my $element = ONVIF::Device::Elements::GetCapabilitiesResponse->new($data);

Constructor. The following data structure may be passed to new():

 {
   Capabilities =>  { # ONVIF::Device::Types::Capabilities
     Analytics =>  { # ONVIF::Device::Types::AnalyticsCapabilities
       XAddr =>  $some_value, # anyURI
       RuleSupport =>  $some_value, # boolean
       AnalyticsModuleSupport =>  $some_value, # boolean
     },
     Device =>  { # ONVIF::Device::Types::DeviceCapabilities
       XAddr =>  $some_value, # anyURI
       Network =>  { # ONVIF::Device::Types::NetworkCapabilities
         IPFilter =>  $some_value, # boolean
         ZeroConfiguration =>  $some_value, # boolean
         IPVersion6 =>  $some_value, # boolean
         DynDNS =>  $some_value, # boolean
         Extension =>  { # ONVIF::Device::Types::NetworkCapabilitiesExtension
           Dot11Configuration =>  $some_value, # boolean
           Extension =>  { # ONVIF::Device::Types::NetworkCapabilitiesExtension2
           },
         },
       },
       System =>  { # ONVIF::Device::Types::SystemCapabilities
         DiscoveryResolve =>  $some_value, # boolean
         DiscoveryBye =>  $some_value, # boolean
         RemoteDiscovery =>  $some_value, # boolean
         SystemBackup =>  $some_value, # boolean
         SystemLogging =>  $some_value, # boolean
         FirmwareUpgrade =>  $some_value, # boolean
         SupportedVersions =>  { # ONVIF::Device::Types::OnvifVersion
           Major =>  $some_value, # int
           Minor =>  $some_value, # int
         },
         Extension =>  { # ONVIF::Device::Types::SystemCapabilitiesExtension
           HttpFirmwareUpgrade =>  $some_value, # boolean
           HttpSystemBackup =>  $some_value, # boolean
           HttpSystemLogging =>  $some_value, # boolean
           HttpSupportInformation =>  $some_value, # boolean
           Extension =>  { # ONVIF::Device::Types::SystemCapabilitiesExtension2
           },
         },
       },
       IO =>  { # ONVIF::Device::Types::IOCapabilities
         InputConnectors =>  $some_value, # int
         RelayOutputs =>  $some_value, # int
         Extension =>  { # ONVIF::Device::Types::IOCapabilitiesExtension
           Auxiliary =>  $some_value, # boolean
           AuxiliaryCommands => $some_value, # AuxiliaryData
           Extension =>  { # ONVIF::Device::Types::IOCapabilitiesExtension2
           },
         },
       },
       Security =>  { # ONVIF::Device::Types::SecurityCapabilities
         TLS1__1 =>  $some_value, # boolean
         TLS1__2 =>  $some_value, # boolean
         OnboardKeyGeneration =>  $some_value, # boolean
         AccessPolicyConfig =>  $some_value, # boolean
         X__509Token =>  $some_value, # boolean
         SAMLToken =>  $some_value, # boolean
         KerberosToken =>  $some_value, # boolean
         RELToken =>  $some_value, # boolean
         Extension =>  { # ONVIF::Device::Types::SecurityCapabilitiesExtension
           TLS1__0 =>  $some_value, # boolean
           Extension =>  { # ONVIF::Device::Types::SecurityCapabilitiesExtension2
             Dot1X =>  $some_value, # boolean
             SupportedEAPMethod =>  $some_value, # int
             RemoteUserHandling =>  $some_value, # boolean
           },
         },
       },
       Extension =>  { # ONVIF::Device::Types::DeviceCapabilitiesExtension
       },
     },
     Events =>  { # ONVIF::Device::Types::EventCapabilities
       XAddr =>  $some_value, # anyURI
       WSSubscriptionPolicySupport =>  $some_value, # boolean
       WSPullPointSupport =>  $some_value, # boolean
       WSPausableSubscriptionManagerInterfaceSupport =>  $some_value, # boolean
     },
     Imaging =>  { # ONVIF::Device::Types::ImagingCapabilities
       XAddr =>  $some_value, # anyURI
     },
     Media =>  { # ONVIF::Device::Types::MediaCapabilities
       XAddr =>  $some_value, # anyURI
       StreamingCapabilities =>  { # ONVIF::Device::Types::RealTimeStreamingCapabilities
         RTPMulticast =>  $some_value, # boolean
         RTP_TCP =>  $some_value, # boolean
         RTP_RTSP_TCP =>  $some_value, # boolean
         Extension =>  { # ONVIF::Device::Types::RealTimeStreamingCapabilitiesExtension
         },
       },
       Extension =>  { # ONVIF::Device::Types::MediaCapabilitiesExtension
         ProfileCapabilities =>  { # ONVIF::Device::Types::ProfileCapabilities
           MaximumNumberOfProfiles =>  $some_value, # int
         },
       },
     },
     PTZ =>  { # ONVIF::Device::Types::PTZCapabilities
       XAddr =>  $some_value, # anyURI
     },
     Extension =>  { # ONVIF::Device::Types::CapabilitiesExtension
       DeviceIO =>  { # ONVIF::Device::Types::DeviceIOCapabilities
         XAddr =>  $some_value, # anyURI
         VideoSources =>  $some_value, # int
         VideoOutputs =>  $some_value, # int
         AudioSources =>  $some_value, # int
         AudioOutputs =>  $some_value, # int
         RelayOutputs =>  $some_value, # int
       },
       Display =>  { # ONVIF::Device::Types::DisplayCapabilities
         XAddr =>  $some_value, # anyURI
         FixedLayout =>  $some_value, # boolean
       },
       Recording =>  { # ONVIF::Device::Types::RecordingCapabilities
         XAddr =>  $some_value, # anyURI
         ReceiverSource =>  $some_value, # boolean
         MediaProfileSource =>  $some_value, # boolean
         DynamicRecordings =>  $some_value, # boolean
         DynamicTracks =>  $some_value, # boolean
         MaxStringLength =>  $some_value, # int
       },
       Search =>  { # ONVIF::Device::Types::SearchCapabilities
         XAddr =>  $some_value, # anyURI
         MetadataSearch =>  $some_value, # boolean
       },
       Replay =>  { # ONVIF::Device::Types::ReplayCapabilities
         XAddr =>  $some_value, # anyURI
       },
       Receiver =>  { # ONVIF::Device::Types::ReceiverCapabilities
         XAddr =>  $some_value, # anyURI
         RTP_Multicast =>  $some_value, # boolean
         RTP_TCP =>  $some_value, # boolean
         RTP_RTSP_TCP =>  $some_value, # boolean
         SupportedReceivers =>  $some_value, # int
         MaximumRTSPURILength =>  $some_value, # int
       },
       AnalyticsDevice =>  { # ONVIF::Device::Types::AnalyticsDeviceCapabilities
         XAddr =>  $some_value, # anyURI
         RuleSupport =>  $some_value, # boolean
         Extension =>  { # ONVIF::Device::Types::AnalyticsDeviceExtension
         },
       },
       Extensions =>  { # ONVIF::Device::Types::CapabilitiesExtension2
       },
     },
   },
 },

=head1 AUTHOR

Generated by SOAP::WSDL

=cut

