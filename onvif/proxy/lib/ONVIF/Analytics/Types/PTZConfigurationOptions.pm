package ONVIF::Analytics::Types::PTZConfigurationOptions;
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
        'Spaces' => 'ONVIF::Analytics::Types::PTZSpaces',
        'PTZTimeout' => 'ONVIF::Analytics::Types::DurationRange',
        'PTControlDirection' => 'ONVIF::Analytics::Types::PTControlDirectionOptions',
        'Extension' => 'ONVIF::Analytics::Types::PTZConfigurationOptions2',
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

ONVIF::Analytics::Types::PTZConfigurationOptions

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

 { # ONVIF::Analytics::Types::PTZConfigurationOptions
   Spaces =>  { # ONVIF::Analytics::Types::PTZSpaces
     AbsolutePanTiltPositionSpace =>  { # ONVIF::Analytics::Types::Space2DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
       YRange =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     AbsoluteZoomPositionSpace =>  { # ONVIF::Analytics::Types::Space1DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     RelativePanTiltTranslationSpace =>  { # ONVIF::Analytics::Types::Space2DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
       YRange =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     RelativeZoomTranslationSpace =>  { # ONVIF::Analytics::Types::Space1DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     ContinuousPanTiltVelocitySpace =>  { # ONVIF::Analytics::Types::Space2DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
       YRange =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     ContinuousZoomVelocitySpace =>  { # ONVIF::Analytics::Types::Space1DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     PanTiltSpeedSpace =>  { # ONVIF::Analytics::Types::Space1DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     ZoomSpeedSpace =>  { # ONVIF::Analytics::Types::Space1DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Analytics::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     Extension =>  { # ONVIF::Analytics::Types::PTZSpacesExtension
     },
   },
   PTZTimeout =>  { # ONVIF::Analytics::Types::DurationRange
     Min =>  $some_value, # duration
     Max =>  $some_value, # duration
   },
   PTControlDirection =>  { # ONVIF::Analytics::Types::PTControlDirectionOptions
     EFlip =>  { # ONVIF::Analytics::Types::EFlipOptions
       Mode => $some_value, # EFlipMode
       Extension =>  { # ONVIF::Analytics::Types::EFlipOptionsExtension
       },
     },
     Reverse =>  { # ONVIF::Analytics::Types::ReverseOptions
       Mode => $some_value, # ReverseMode
       Extension =>  { # ONVIF::Analytics::Types::ReverseOptionsExtension
       },
     },
     Extension =>  { # ONVIF::Analytics::Types::PTControlDirectionOptionsExtension
     },
   },
   Extension =>  { # ONVIF::Analytics::Types::PTZConfigurationOptions2
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

