package ONVIF::Media::Types::PTZConfigurationOptions;
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
        'Spaces' => 'ONVIF::Media::Types::PTZSpaces',
        'PTZTimeout' => 'ONVIF::Media::Types::DurationRange',
        'PTControlDirection' => 'ONVIF::Media::Types::PTControlDirectionOptions',
        'Extension' => 'ONVIF::Media::Types::PTZConfigurationOptions2',
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

ONVIF::Media::Types::PTZConfigurationOptions

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

 { # ONVIF::Media::Types::PTZConfigurationOptions
   Spaces =>  { # ONVIF::Media::Types::PTZSpaces
     AbsolutePanTiltPositionSpace =>  { # ONVIF::Media::Types::Space2DDescription
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
     AbsoluteZoomPositionSpace =>  { # ONVIF::Media::Types::Space1DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Media::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     RelativePanTiltTranslationSpace =>  { # ONVIF::Media::Types::Space2DDescription
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
     RelativeZoomTranslationSpace =>  { # ONVIF::Media::Types::Space1DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Media::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     ContinuousPanTiltVelocitySpace =>  { # ONVIF::Media::Types::Space2DDescription
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
     ContinuousZoomVelocitySpace =>  { # ONVIF::Media::Types::Space1DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Media::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     PanTiltSpeedSpace =>  { # ONVIF::Media::Types::Space1DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Media::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     ZoomSpeedSpace =>  { # ONVIF::Media::Types::Space1DDescription
       URI =>  $some_value, # anyURI
       XRange =>  { # ONVIF::Media::Types::FloatRange
         Min =>  $some_value, # float
         Max =>  $some_value, # float
       },
     },
     Extension =>  { # ONVIF::Media::Types::PTZSpacesExtension
     },
   },
   PTZTimeout =>  { # ONVIF::Media::Types::DurationRange
     Min =>  $some_value, # duration
     Max =>  $some_value, # duration
   },
   PTControlDirection =>  { # ONVIF::Media::Types::PTControlDirectionOptions
     EFlip =>  { # ONVIF::Media::Types::EFlipOptions
       Mode => $some_value, # EFlipMode
       Extension =>  { # ONVIF::Media::Types::EFlipOptionsExtension
       },
     },
     Reverse =>  { # ONVIF::Media::Types::ReverseOptions
       Mode => $some_value, # ReverseMode
       Extension =>  { # ONVIF::Media::Types::ReverseOptionsExtension
       },
     },
     Extension =>  { # ONVIF::Media::Types::PTControlDirectionOptionsExtension
     },
   },
   Extension =>  { # ONVIF::Media::Types::PTZConfigurationOptions2
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

