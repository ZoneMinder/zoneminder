
package ONVIF::PTZ::Elements::GetNodesResponse;
use strict;
use warnings;

{ # BLOCK to scope variables

sub get_xmlns { 'http://www.onvif.org/ver20/ptz/wsdl' }

__PACKAGE__->__set_name('GetNodesResponse');
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

my %PTZNode_of :ATTR(:get<PTZNode>);

__PACKAGE__->_factory(
    [ qw(        PTZNode

    ) ],
    {
        'PTZNode' => \%PTZNode_of,
    },
    {
        'PTZNode' => 'ONVIF::PTZ::Types::PTZNode',
    },
    {

        'PTZNode' => 'PTZNode',
    }
);

} # end BLOCK







} # end of BLOCK



1;


=pod

=head1 NAME

ONVIF::PTZ::Elements::GetNodesResponse

=head1 DESCRIPTION

Perl data type class for the XML Schema defined element
GetNodesResponse from the namespace http://www.onvif.org/ver20/ptz/wsdl.







=head1 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * PTZNode

 $element->set_PTZNode($data);
 $element->get_PTZNode();





=back


=head1 METHODS

=head2 new

 my $element = ONVIF::PTZ::Elements::GetNodesResponse->new($data);

Constructor. The following data structure may be passed to new():

 {
   PTZNode =>  { # ONVIF::PTZ::Types::PTZNode
     Name => $some_value, # Name
     SupportedPTZSpaces =>  { # ONVIF::PTZ::Types::PTZSpaces
       AbsolutePanTiltPositionSpace =>  { # ONVIF::PTZ::Types::Space2DDescription
         URI =>  $some_value, # anyURI
         XRange =>  { # ONVIF::PTZ::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
         YRange =>  { # ONVIF::PTZ::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
       },
       AbsoluteZoomPositionSpace =>  { # ONVIF::PTZ::Types::Space1DDescription
         URI =>  $some_value, # anyURI
         XRange =>  { # ONVIF::PTZ::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
       },
       RelativePanTiltTranslationSpace =>  { # ONVIF::PTZ::Types::Space2DDescription
         URI =>  $some_value, # anyURI
         XRange =>  { # ONVIF::PTZ::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
         YRange =>  { # ONVIF::PTZ::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
       },
       RelativeZoomTranslationSpace =>  { # ONVIF::PTZ::Types::Space1DDescription
         URI =>  $some_value, # anyURI
         XRange =>  { # ONVIF::PTZ::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
       },
       ContinuousPanTiltVelocitySpace =>  { # ONVIF::PTZ::Types::Space2DDescription
         URI =>  $some_value, # anyURI
         XRange =>  { # ONVIF::PTZ::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
         YRange =>  { # ONVIF::PTZ::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
       },
       ContinuousZoomVelocitySpace =>  { # ONVIF::PTZ::Types::Space1DDescription
         URI =>  $some_value, # anyURI
         XRange =>  { # ONVIF::PTZ::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
       },
       PanTiltSpeedSpace =>  { # ONVIF::PTZ::Types::Space1DDescription
         URI =>  $some_value, # anyURI
         XRange =>  { # ONVIF::PTZ::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
       },
       ZoomSpeedSpace =>  { # ONVIF::PTZ::Types::Space1DDescription
         URI =>  $some_value, # anyURI
         XRange =>  { # ONVIF::PTZ::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
       },
       Extension =>  { # ONVIF::PTZ::Types::PTZSpacesExtension
       },
     },
     MaximumNumberOfPresets =>  $some_value, # int
     HomeSupported =>  $some_value, # boolean
     AuxiliaryCommands => $some_value, # AuxiliaryData
     Extension =>  { # ONVIF::PTZ::Types::PTZNodeExtension
       SupportedPresetTour =>  { # ONVIF::PTZ::Types::PTZPresetTourSupported
         MaximumNumberOfPresetTours =>  $some_value, # int
         PTZPresetTourOperation => $some_value, # PTZPresetTourOperation
         Extension =>  { # ONVIF::PTZ::Types::PTZPresetTourSupportedExtension
         },
       },
       Extension =>  { # ONVIF::PTZ::Types::PTZNodeExtension2
       },
     },
   },
 },

=head1 AUTHOR

Generated by SOAP::WSDL

=cut

