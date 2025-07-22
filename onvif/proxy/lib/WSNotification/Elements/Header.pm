
package WSNotification::Elements::Header;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(0);

sub get_xmlns { 'http://schemas.xmlsoap.org/soap/envelope/' };

our $XML_ATTRIBUTE_CLASS;
undef $XML_ATTRIBUTE_CLASS;

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}

use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);
use Data::Dumper;

Class::Std::initialize();

{ # BLOCK to scope variables

my %Action_of :ATTR(:get<Action>);
my %MessageID_of :ATTR(:get<MessageID>);
my %ReplyTo_of :ATTR(:get<ReplyTo>);
my %To_of :ATTR(:get<To>);
__PACKAGE__->_factory(
    [ qw( Action MessageID ReplyTo To ) ],
    {
        'Action' => \%Action_of,
        'MessageID' => \%MessageID_of,
        'ReplyTo' => \%ReplyTo_of,
        'To' => \%To_of,
    },
    {
        'Action' => 'WSNotification::Elements::Action',
        'MessageID' => 'WSNotification::Elements::MessageID',
        'ReplyTo' => 'WSNotification::Elements::ReplyTo',
        'To' => 'WSNotification::Elements::To',
    },
    {
        'Action' => '',
        'MessageID' => '',
        'ReplyTo' => '',
        'To' => '',
    }
);

} # end BLOCK


1;
