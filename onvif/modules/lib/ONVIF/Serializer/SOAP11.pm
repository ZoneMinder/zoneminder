#!/usr/bin/perl -w
package ONVIF::Serializer::SOAP11;
use strict;
use warnings;

use base qw(ONVIF::Serializer::Base);

use SOAP::WSDL::Factory::Serializer;

SOAP::WSDL::Factory::Serializer->register( '1.1' , __PACKAGE__ );

sub BUILD
{
  my ($self, $ident, $args_ref) = @_;
#  $soapversion_of{ $ident } = '1.1';
  $self->set_soap_version('1.1');
}

1;
