package ONVIF::Analytics::Types::OSDTextOptions;
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

my %Type_of :ATTR(:get<Type>);
my %FontSizeRange_of :ATTR(:get<FontSizeRange>);
my %DateFormat_of :ATTR(:get<DateFormat>);
my %TimeFormat_of :ATTR(:get<TimeFormat>);
my %FontColor_of :ATTR(:get<FontColor>);
my %BackgroundColor_of :ATTR(:get<BackgroundColor>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        Type
        FontSizeRange
        DateFormat
        TimeFormat
        FontColor
        BackgroundColor
        Extension

    ) ],
    {
        'Type' => \%Type_of,
        'FontSizeRange' => \%FontSizeRange_of,
        'DateFormat' => \%DateFormat_of,
        'TimeFormat' => \%TimeFormat_of,
        'FontColor' => \%FontColor_of,
        'BackgroundColor' => \%BackgroundColor_of,
        'Extension' => \%Extension_of,
    },
    {
        'Type' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
        'FontSizeRange' => 'ONVIF::Analytics::Types::IntRange',
        'DateFormat' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
        'TimeFormat' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
        'FontColor' => 'ONVIF::Analytics::Types::OSDColorOptions',
        'BackgroundColor' => 'ONVIF::Analytics::Types::OSDColorOptions',
        'Extension' => 'ONVIF::Analytics::Types::OSDTextOptionsExtension',
    },
    {

        'Type' => 'Type',
        'FontSizeRange' => 'FontSizeRange',
        'DateFormat' => 'DateFormat',
        'TimeFormat' => 'TimeFormat',
        'FontColor' => 'FontColor',
        'BackgroundColor' => 'BackgroundColor',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Analytics::Types::OSDTextOptions

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
OSDTextOptions from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * Type


=item * FontSizeRange


=item * DateFormat


=item * TimeFormat


=item * FontColor


=item * BackgroundColor


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Analytics::Types::OSDTextOptions
   Type =>  $some_value, # string
   FontSizeRange =>  { # ONVIF::Analytics::Types::IntRange
     Min =>  $some_value, # int
     Max =>  $some_value, # int
   },
   DateFormat =>  $some_value, # string
   TimeFormat =>  $some_value, # string
   FontColor =>  { # ONVIF::Analytics::Types::OSDColorOptions
     Color =>      { # ONVIF::Analytics::Types::ColorOptions
       # One of the following elements.
       # No occurrence checks yet, so be sure to pass just one...
       ColorList => ,
       ColorspaceRange =>  { # ONVIF::Analytics::Types::ColorspaceRange
         X =>  { # ONVIF::Analytics::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
         Y =>  { # ONVIF::Analytics::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
         Z =>  { # ONVIF::Analytics::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
         Colorspace =>  $some_value, # anyURI
       },
     },
     Transparent =>  { # ONVIF::Analytics::Types::IntRange
       Min =>  $some_value, # int
       Max =>  $some_value, # int
     },
     Extension =>  { # ONVIF::Analytics::Types::OSDColorOptionsExtension
     },
   },
   BackgroundColor =>  { # ONVIF::Analytics::Types::OSDColorOptions
     Color =>      { # ONVIF::Analytics::Types::ColorOptions
       # One of the following elements.
       # No occurrence checks yet, so be sure to pass just one...
       ColorList => ,
       ColorspaceRange =>  { # ONVIF::Analytics::Types::ColorspaceRange
         X =>  { # ONVIF::Analytics::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
         Y =>  { # ONVIF::Analytics::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
         Z =>  { # ONVIF::Analytics::Types::FloatRange
           Min =>  $some_value, # float
           Max =>  $some_value, # float
         },
         Colorspace =>  $some_value, # anyURI
       },
     },
     Transparent =>  { # ONVIF::Analytics::Types::IntRange
       Min =>  $some_value, # int
       Max =>  $some_value, # int
     },
     Extension =>  { # ONVIF::Analytics::Types::OSDColorOptionsExtension
     },
   },
   Extension =>  { # ONVIF::Analytics::Types::OSDTextOptionsExtension
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

