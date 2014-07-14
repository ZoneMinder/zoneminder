package ONVIF::PTZ::Types::PTZConfigurationOptions;
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

my %Spaces_of :ATTR(:get<Spaces>);
my %PTZTimeout_of :ATTR(:get<PTZTimeout>);
my %PTControlDirection_of :ATTR(:get<PTControlDirection>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        Spaces
        PTZTimeout
        PTControlDirection
        Extension

    ) ],
    {
        'Spaces' => \%Spaces_of,
        'PTZTimeout' => \%PTZTimeout_of,
        'PTControlDirection' => \%PTControlDirection_of,
        'Extension' => \%Extension_of,
    },
    {
        'Spaces' => 'ONVIF::PTZ::Types::PTZSpaces',
        'PTZTimeout' => 'ONVIF::PTZ::Types::DurationRange',
        'PTControlDirection' => 'ONVIF::PTZ::Types::PTControlDirectionOptions',
        'Extension' => 'ONVIF::PTZ::Types::PTZConfigurationOptions2',
    },
    {

        'Spaces' => 'Spaces',
        'PTZTimeout' => 'PTZTimeout',
        'PTControlDirection' => 'PTControlDirection',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::PTZ::Types::PTZConfigurationOptions

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
PTZConfigurationOptions from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * Spaces


=item * PTZTimeout


=item * PTControlDirection


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::PTZ::Types::PTZConfigurationOptions
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




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

