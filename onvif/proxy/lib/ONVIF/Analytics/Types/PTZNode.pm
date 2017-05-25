package ONVIF::Analytics::Types::PTZNode;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS = 'ONVIF::Analytics::Types::PTZNode::_PTZNode::XmlAttr';

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}


use base qw(ONVIF::Analytics::Types::DeviceEntity);
# Variety: sequence
use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %Name_of :ATTR(:get<Name>);
my %SupportedPTZSpaces_of :ATTR(:get<SupportedPTZSpaces>);
my %MaximumNumberOfPresets_of :ATTR(:get<MaximumNumberOfPresets>);
my %HomeSupported_of :ATTR(:get<HomeSupported>);
my %AuxiliaryCommands_of :ATTR(:get<AuxiliaryCommands>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        Name
        SupportedPTZSpaces
        MaximumNumberOfPresets
        HomeSupported
        AuxiliaryCommands
        Extension

    ) ],
    {
        'Name' => \%Name_of,
        'SupportedPTZSpaces' => \%SupportedPTZSpaces_of,
        'MaximumNumberOfPresets' => \%MaximumNumberOfPresets_of,
        'HomeSupported' => \%HomeSupported_of,
        'AuxiliaryCommands' => \%AuxiliaryCommands_of,
        'Extension' => \%Extension_of,
    },
    {
        'Name' => 'ONVIF::Analytics::Types::Name',
        'SupportedPTZSpaces' => 'ONVIF::Analytics::Types::PTZSpaces',
        'MaximumNumberOfPresets' => 'SOAP::WSDL::XSD::Typelib::Builtin::int',
        'HomeSupported' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'AuxiliaryCommands' => 'ONVIF::Analytics::Types::AuxiliaryData',
        'Extension' => 'ONVIF::Analytics::Types::PTZNodeExtension',
    },
    {

        'Name' => 'Name',
        'SupportedPTZSpaces' => 'SupportedPTZSpaces',
        'MaximumNumberOfPresets' => 'MaximumNumberOfPresets',
        'HomeSupported' => 'HomeSupported',
        'AuxiliaryCommands' => 'AuxiliaryCommands',
        'Extension' => 'Extension',
    }
);

} # end BLOCK




package ONVIF::Analytics::Types::PTZNode::_PTZNode::XmlAttr;
use base qw(SOAP::WSDL::XSD::Typelib::AttributeSet);

{ # BLOCK to scope variables

my %FixedHomePosition_of :ATTR(:get<FixedHomePosition>);

__PACKAGE__->_factory(
    [ qw(
        FixedHomePosition
    ) ],
    {

        FixedHomePosition => \%FixedHomePosition_of,
    },
    {
        FixedHomePosition => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
    }
);

} # end BLOCK




1;


=pod

=head1 NAME

ONVIF::Analytics::Types::PTZNode

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
PTZNode from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * Name


=item * SupportedPTZSpaces


=item * MaximumNumberOfPresets


=item * HomeSupported


=item * AuxiliaryCommands


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Analytics::Types::PTZNode
   Name => $some_value, # Name
   SupportedPTZSpaces =>  { # ONVIF::Analytics::Types::PTZSpaces
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
   MaximumNumberOfPresets =>  $some_value, # int
   HomeSupported =>  $some_value, # boolean
   AuxiliaryCommands => $some_value, # AuxiliaryData
   Extension =>  { # ONVIF::Analytics::Types::PTZNodeExtension
     SupportedPresetTour =>  { # ONVIF::Analytics::Types::PTZPresetTourSupported
       MaximumNumberOfPresetTours =>  $some_value, # int
       PTZPresetTourOperation => $some_value, # PTZPresetTourOperation
       Extension =>  { # ONVIF::Analytics::Types::PTZPresetTourSupportedExtension
       },
     },
     Extension =>  { # ONVIF::Analytics::Types::PTZNodeExtension2
     },
   },
 },



=head2 attr

NOTE: Attribute documentation is experimental, and may be inaccurate.
See the correspondent WSDL/XML Schema if in question.

This class has additional attributes, accessibly via the C<attr()> method.

attr() returns an object of the class ONVIF::Analytics::Types::PTZNode::_PTZNode::XmlAttr.

The following attributes can be accessed on this object via the corresponding
get_/set_ methods:

=over

=item * FixedHomePosition

 Indication whether the HomePosition of a Node is fixed or it can be changed via the SetHomePosition command. 



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.


=back




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

