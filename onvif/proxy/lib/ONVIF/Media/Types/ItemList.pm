package ONVIF::Media::Types::ItemList;
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

my %SimpleItem_of :ATTR(:get<SimpleItem>);
my %ElementItem_of :ATTR(:get<ElementItem>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        SimpleItem
        ElementItem
        Extension

    ) ],
    {
        'SimpleItem' => \%SimpleItem_of,
        'ElementItem' => \%ElementItem_of,
        'Extension' => \%Extension_of,
    },
    {

        'SimpleItem' => 'ONVIF::Media::Types::ItemList::_SimpleItem',

        'ElementItem' => 'ONVIF::Media::Types::ItemList::_ElementItem',
        'Extension' => 'ONVIF::Media::Types::ItemListExtension',
    },
    {

        'SimpleItem' => 'SimpleItem',
        'ElementItem' => 'ElementItem',
        'Extension' => 'Extension',
    }
);

} # end BLOCK




package ONVIF::Media::Types::ItemList::_ElementItem;
use strict;
use warnings;
{
our $XML_ATTRIBUTE_CLASS = 'ONVIF::Media::Types::ItemList::_ElementItem::XmlAttr';

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}

use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables


__PACKAGE__->_factory(
    [ qw(
    ) ],
    {
    },
    {
    },
    {

    }
);

} # end BLOCK




package ONVIF::Media::Types::ItemList::_ElementItem::XmlAttr;
use base qw(SOAP::WSDL::XSD::Typelib::AttributeSet);

{ # BLOCK to scope variables

my %Name_of :ATTR(:get<Name>);

__PACKAGE__->_factory(
    [ qw(
        Name
    ) ],
    {

        Name => \%Name_of,
    },
    {
        Name => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
    }
);

} # end BLOCK



}



package ONVIF::Media::Types::ItemList::_SimpleItem;
use strict;
use warnings;
{
our $XML_ATTRIBUTE_CLASS = 'ONVIF::Media::Types::ItemList::_SimpleItem::XmlAttr';

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}



# There's no variety - empty complexType
use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

__PACKAGE__->_factory();


package ONVIF::Media::Types::ItemList::_SimpleItem::XmlAttr;
use base qw(SOAP::WSDL::XSD::Typelib::AttributeSet);

{ # BLOCK to scope variables

my %Name_of :ATTR(:get<Name>);
my %Value_of :ATTR(:get<Value>);

__PACKAGE__->_factory(
    [ qw(
        Name
        Value
    ) ],
    {

        Name => \%Name_of,

        Value => \%Value_of,
    },
    {
        Name => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
        Value => 'SOAP::WSDL::XSD::Typelib::Builtin::anySimpleType',
    }
);

} # end BLOCK



}







1;


=pod

=head1 NAME

ONVIF::Media::Types::ItemList

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
ItemList from the namespace http://www.onvif.org/ver10/schema.

List of parameters according to the corresponding ItemListDescription. Each item in the list shall have a unique name. 




=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * SimpleItem


=item * ElementItem


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Media::Types::ItemList
   SimpleItem => ,
   ElementItem =>  {
   },
   Extension =>  { # ONVIF::Media::Types::ItemListExtension
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

