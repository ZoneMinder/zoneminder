package ONVIF::PTZ::Types::OSDConfigurationOptions;
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

my %MaximumNumberOfOSDs_of :ATTR(:get<MaximumNumberOfOSDs>);
my %Type_of :ATTR(:get<Type>);
my %PositionOption_of :ATTR(:get<PositionOption>);
my %TextOption_of :ATTR(:get<TextOption>);
my %ImageOption_of :ATTR(:get<ImageOption>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        MaximumNumberOfOSDs
        Type
        PositionOption
        TextOption
        ImageOption
        Extension

    ) ],
    {
        'MaximumNumberOfOSDs' => \%MaximumNumberOfOSDs_of,
        'Type' => \%Type_of,
        'PositionOption' => \%PositionOption_of,
        'TextOption' => \%TextOption_of,
        'ImageOption' => \%ImageOption_of,
        'Extension' => \%Extension_of,
    },
    {
        'MaximumNumberOfOSDs' => 'ONVIF::PTZ::Types::MaximumNumberOfOSDs',
        'Type' => 'ONVIF::PTZ::Types::OSDType',
        'PositionOption' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
        'TextOption' => 'ONVIF::PTZ::Types::OSDTextOptions',
        'ImageOption' => 'ONVIF::PTZ::Types::OSDImgOptions',
        'Extension' => 'ONVIF::PTZ::Types::OSDConfigurationOptionsExtension',
    },
    {

        'MaximumNumberOfOSDs' => 'MaximumNumberOfOSDs',
        'Type' => 'Type',
        'PositionOption' => 'PositionOption',
        'TextOption' => 'TextOption',
        'ImageOption' => 'ImageOption',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::PTZ::Types::OSDConfigurationOptions

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
OSDConfigurationOptions from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * MaximumNumberOfOSDs


=item * Type


=item * PositionOption


=item * TextOption


=item * ImageOption


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::PTZ::Types::OSDConfigurationOptions
   MaximumNumberOfOSDs => ,
   Type => $some_value, # OSDType
   PositionOption =>  $some_value, # string
   TextOption =>  { # ONVIF::PTZ::Types::OSDTextOptions
     Type =>  $some_value, # string
     FontSizeRange =>  { # ONVIF::PTZ::Types::IntRange
       Min =>  $some_value, # int
       Max =>  $some_value, # int
     },
     DateFormat =>  $some_value, # string
     TimeFormat =>  $some_value, # string
     FontColor =>  { # ONVIF::PTZ::Types::OSDColorOptions
       Color =>        { # ONVIF::PTZ::Types::ColorOptions
         # One of the following elements.
         # No occurrence checks yet, so be sure to pass just one...
         ColorList => ,
         ColorspaceRange =>  { # ONVIF::PTZ::Types::ColorspaceRange
           X =>  { # ONVIF::PTZ::Types::FloatRange
             Min =>  $some_value, # float
             Max =>  $some_value, # float
           },
           Y =>  { # ONVIF::PTZ::Types::FloatRange
             Min =>  $some_value, # float
             Max =>  $some_value, # float
           },
           Z =>  { # ONVIF::PTZ::Types::FloatRange
             Min =>  $some_value, # float
             Max =>  $some_value, # float
           },
           Colorspace =>  $some_value, # anyURI
         },
       },
       Transparent =>  { # ONVIF::PTZ::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
       Extension =>  { # ONVIF::PTZ::Types::OSDColorOptionsExtension
       },
     },
     BackgroundColor =>  { # ONVIF::PTZ::Types::OSDColorOptions
       Color =>        { # ONVIF::PTZ::Types::ColorOptions
         # One of the following elements.
         # No occurrence checks yet, so be sure to pass just one...
         ColorList => ,
         ColorspaceRange =>  { # ONVIF::PTZ::Types::ColorspaceRange
           X =>  { # ONVIF::PTZ::Types::FloatRange
             Min =>  $some_value, # float
             Max =>  $some_value, # float
           },
           Y =>  { # ONVIF::PTZ::Types::FloatRange
             Min =>  $some_value, # float
             Max =>  $some_value, # float
           },
           Z =>  { # ONVIF::PTZ::Types::FloatRange
             Min =>  $some_value, # float
             Max =>  $some_value, # float
           },
           Colorspace =>  $some_value, # anyURI
         },
       },
       Transparent =>  { # ONVIF::PTZ::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
       Extension =>  { # ONVIF::PTZ::Types::OSDColorOptionsExtension
       },
     },
     Extension =>  { # ONVIF::PTZ::Types::OSDTextOptionsExtension
     },
   },
   ImageOption =>  { # ONVIF::PTZ::Types::OSDImgOptions
     ImagePath =>  $some_value, # anyURI
     Extension =>  { # ONVIF::PTZ::Types::OSDImgOptionsExtension
     },
   },
   Extension =>  { # ONVIF::PTZ::Types::OSDConfigurationOptionsExtension
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

