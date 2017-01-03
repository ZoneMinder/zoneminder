# ==========================================================================
#
# ZoneMinder ONVIF Client module
# Copyright (C) 2014  Jan M. Hochstein
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
# ==========================================================================
#
# This module contains the implementation of a SOAP deserializer
#

package ONVIF::Deserializer::XSD;
use strict;
use warnings;

use base qw(SOAP::WSDL::Deserializer::XSD);

use SOAP::WSDL::SOAP::Typelib::Fault11;
use ONVIF::Deserializer::MessageParser;

use SOAP::WSDL::Factory::Deserializer;

SOAP::WSDL::Factory::Deserializer->register('1.1', __PACKAGE__ );
SOAP::WSDL::Factory::Deserializer->register('1.2', __PACKAGE__ );

## we get the soap version from the message parser
my %soap_version_of :ATTR( :default<()>);


sub soap_version {
  my ($self) = @_;
  if($SOAP::WSDL::Deserializer::XSD::parser_of{ident $self}) {
    return $SOAP::WSDL::Deserializer::XSD::parser_of{ident $self}->soap_version();
  }
  return '';
}

sub deserialize {
    my ($self, $content) = @_;

    my $parser = $SOAP::WSDL::Deserializer::XSD::parser_of{ ${ $self } };
    if(not $parser) {
      $parser = ONVIF::Deserializer::MessageParser->new({
          strict => $SOAP::WSDL::Deserializer::XSD::strict_of{ ${ $self } }
      });
      $SOAP::WSDL::Deserializer::XSD::parser_of{ ${ $self } } = $parser;
    }

    $parser->class_resolver( 
        $self->SOAP::WSDL::Deserializer::XSD::get_class_resolver() );
    eval { $parser->parse_string( $content ) };
    if ($@) {
        return $self->generate_fault({
            code => 'SOAP-ENV:Server',
            role => 'urn:localhost',
            message => "Error deserializing message: $@. \n"
                . "Message was: \n$content"
        });
    }
    return ( $parser->get_data(), 
             $parser->get_header() );
}

sub generate_fault {
    my ($self, $args_from_ref) = @_;
    return SOAP::WSDL::SOAP::Typelib::Fault11->new({
            faultcode => $args_from_ref->{ code } || 'SOAP-ENV:Client',
            faultactor => $args_from_ref->{ role } || 'urn:localhost',
            faultstring => $args_from_ref->{ message } || "Unknown error"
    });
}

1;

__END__


