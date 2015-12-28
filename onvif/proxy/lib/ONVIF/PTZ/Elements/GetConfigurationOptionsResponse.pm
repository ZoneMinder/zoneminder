
package ONVIF::PTZ::Elements::GetConfigurationOptionsResponse;
use strict;
use warnings;

{ # BLOCK to scope variables

sub get_xmlns { 'http://www.onvif.org/ver20/ptz/wsdl' }

__PACKAGE__->__set_name('GetConfigurationOptionsResponse');
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

my %PTZConfigurationOptions_of :ATTR(:get<PTZConfigurationOptions>);

__PACKAGE__->_factory(
    [ qw(        PTZConfigurationOptions

    ) ],
    {
        'PTZConfigurationOptions' => \%PTZConfigurationOptions_of,
    },
    {
        'PTZConfigurationOptions' => 'ONVIF::PTZ::Types::PTZConfigurationOptions',
    },
    {

        'PTZConfigurationOptions' => 'PTZConfigurationOptions',
    }
);

} # end BLOCK







} # end of BLOCK



1;


=pod

=head1 NAME

ONVIF::PTZ::Elements::GetConfigurationOptionsResponse

=head1 DESCRIPTION

Perl data type class for the XML Schema defined element
GetConfigurationOptionsResponse from the namespace http://www.onvif.org/ver20/ptz/wsdl.







=head1 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * PTZConfigurationOptions

 $element->set_PTZConfigurationOptions($data);
 $element->get_PTZConfigurationOptions();





=back


=head1 METHODS

=head2 new

 my $element = ONVIF::PTZ::Elements::GetConfigurationOptionsResponse->new($data);

Constructor. The following data structure may be passed to new():

 {
   PTZConfigurationOptions =>  { # ONVIF::PTZ::Types::PTZConfigurationOptions
     Spaces =>  { # ONVIF::PTZ::Types::PTZSpaces
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
     PTZTimeout =>  { # ONVIF::PTZ::Types::DurationRange
       Min =>  $some_value, # duration
       Max =>  $some_value, # duration
     },
     PTControlDirection =>  { # ONVIF::PTZ::Types::PTControlDirectionOptions
       EFlip =>  { # ONVIF::PTZ::Types::EFlipOptions
         Mode => $some_value, # EFlipMode
         Extension =>  { # ONVIF::PTZ::Types::EFlipOptionsExtension
         },
       },
       Reverse =>  { # ONVIF::PTZ::Types::ReverseOptions
         Mode => $some_value, # ReverseMode
         Extension =>  { # ONVIF::PTZ::Types::ReverseOptionsExtension
         },
       },
       Extension =>  { # ONVIF::PTZ::Types::PTControlDirectionOptionsExtension
       },
     },
     Extension =>  { # ONVIF::PTZ::Types::PTZConfigurationOptions2
     },
   },
 },

=head1 AUTHOR

Generated by SOAP::WSDL

=cut

