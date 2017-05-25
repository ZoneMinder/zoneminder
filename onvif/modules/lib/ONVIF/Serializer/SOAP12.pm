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
# This module contains the SOAP 1.2 serializer
#

package ONVIF::Serializer::SOAP12;
use strict;
use warnings;

use base qw(ONVIF::Serializer::Base);

use SOAP::WSDL::Factory::Serializer;

SOAP::WSDL::Factory::Serializer->register( '1.2' , __PACKAGE__ );

sub BUILD
{
  my ($self, $ident, $args_ref) = @_;
#  $soapversion_of{ $ident } = '1.2';
 $self->set_soap_version('1.2');
}

1;
