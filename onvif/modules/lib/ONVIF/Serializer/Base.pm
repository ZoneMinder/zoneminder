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
# This module contains the base class for the SOAP serializers
#

package ONVIF::Serializer::Base;
use strict;
use warnings;

# =========================================================================

use Class::Std::Fast::Storable;
use Scalar::Util qw(blessed);

require SOAP::WSDL::Factory::Serializer;

## require SOAP::Constants;
use constant    URI_1999_SCHEMA_XSD    => "http://www.w3.org/1999/XMLSchema";
use constant    URI_1999_SCHEMA_XSI    => "http://www.w3.org/1999/XMLSchema-instance";
use constant    URI_2000_SCHEMA_XSD    => "http://www.w3.org/2000/10/XMLSchema";
use constant    URI_2000_SCHEMA_XSI    => "http://www.w3.org/2000/10/XMLSchema-instance";
use constant    URI_2001_SCHEMA_XSD    => "http://www.w3.org/2001/XMLSchema";
use constant    URI_2001_SCHEMA_XSI    => "http://www.w3.org/2001/XMLSchema-instance";
use constant    URI_LITERAL_ENC        => "";
use constant    URI_SOAP11_ENC         => "http://schemas.xmlsoap.org/soap/encoding/";
use constant    URI_SOAP11_ENV         => "http://schemas.xmlsoap.org/soap/envelope/";
use constant    URI_SOAP11_NEXT_ACTOR  => "http://schemas.xmlsoap.org/soap/actor/next";
use constant    URI_SOAP12_ENC         => "http://www.w3.org/2003/05/soap-encoding";
use constant    URI_SOAP12_ENV         => "http://www.w3.org/2003/05/soap-envelope";
use constant    URI_SOAP12_NOENC       => "http://www.w3.org/2003/05/soap-envelope/encoding/none";
use constant    URI_SOAP12_NEXT_ACTOR  => "http://www.w3.org/2003/05/soap-envelope/role/next";


my %soap_version_of :ATTR( :default<()>);

my $XML_INSTANCE_NS = 'http://www.w3.org/2001/XMLSchema-instance';

sub soap_version
{
  my ($self) = @_;
  $soap_version_of{ident $self};
}

sub set_soap_version
{
  my ($self, $version) = @_;
  if(! (($version eq '1.1') or  ($version eq '1.2')) ) {
    warn "Undefined SOAP version \'$version\'";
    return;
  }
  #print "using SOAP $version\n";
  $soap_version_of{ident $self} = $version;
}


sub serialize {
    my ($self, $args_of_ref) = @_;

    my $SOAP_NS;
    if($self->soap_version() eq '1.2') {
      $SOAP_NS = URI_SOAP12_ENV;
    }
    else {
      $SOAP_NS = URI_SOAP11_ENV;
    }

    my $opt = $args_of_ref->{ options };

    if (not $opt->{ namespace }->{ $SOAP_NS })
    {
        $opt->{ namespace }->{ $SOAP_NS } = 'SOAP-ENV';
    } 

    if (not $opt->{ namespace }->{ $XML_INSTANCE_NS })
    {
        $opt->{ namespace }->{ $XML_INSTANCE_NS } = 'xsi';
    }

    my $soap_prefix = $opt->{ namespace }->{ $SOAP_NS };

    # XML starts with header
    my $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    
    # envelope starts with namespaces
    $xml .= "<$soap_prefix\:Envelope ";

    while (my ($uri, $prefix) = each %{ $opt->{ namespace } })
    {
        $xml .= "xmlns:$prefix=\"$uri\" ";
    }
    #
    # add namespace for user-supplied prefix if needed
    $xml .= "xmlns:$opt->{prefix}=\"" . $args_of_ref->{ body }->get_xmlns() . "\" "
        if $opt->{prefix};

    # TODO insert encoding
    $xml.='>';
    $xml .= $self->serialize_header($args_of_ref->{ method }, $args_of_ref->{ header }, $opt);
    $xml .= $self->serialize_body($args_of_ref->{ method }, $args_of_ref->{ body }, $opt);
    $xml .= '</' . $soap_prefix .':Envelope>';

    return $xml;
}

sub serialize_header {
    my ($self, $method, $data, $opt) = @_;

    my $SOAP_NS;
    if($self->soap_version() eq '1.2') {
      $SOAP_NS = URI_SOAP12_ENV;
    }
    else {
      $SOAP_NS = URI_SOAP11_ENV;
    }

    # header is optional. Leave out if there's no header data
    return q{} if not $data;
    return join ( q{},
        "<$opt->{ namespace }->{ $SOAP_NS }\:Header>",
        blessed $data ? $data->serialize_qualified : (),
        "</$opt->{ namespace }->{ $SOAP_NS }\:Header>",
    );
}

sub serialize_body {
    my ($self, $method, $data, $opt) = @_;

    my $SOAP_NS;
    if($self->soap_version() eq '1.2') {
      $SOAP_NS = URI_SOAP12_ENV;
    }
    else {
      $SOAP_NS = URI_SOAP11_ENV;
    }

    # TODO This one wipes out the old class' XML name globally
    # Fix in some more appropriate place...
    $data->__set_name("$opt->{prefix}:" . $data->__get_name() ) if $opt->{prefix};

    # Body is NOT optional. Serialize to empty body
    # if we have no data.
    return join ( q{},
        "<$opt->{ namespace }->{ $SOAP_NS }\:Body>",
        defined $data
            ? ref $data eq 'ARRAY'
                ? join q{}, map { blessed $_ ? $_->serialize_qualified() : () } @{ $data }
                : blessed $data
                    ? $opt->{prefix}
                        ? $data->serialize()
                        : $data->serialize_qualified()
                    : ()
            : (),
        "</$opt->{ namespace }->{ $SOAP_NS }\:Body>",
    );
}

# =========================================================================


1;


__END__

=pod

=head1 NAME

Copy of SOAP:WSDL::Serializer::XSD adapted 

=head1 LICENSE AND COPYRIGHT

This file was adapted from a part of SOAP-WSDL. You may 
distribute/modify it under the same terms as perl itself

=head1 REPOSITORY INFORMATION

 $Rev: 851 $
 $LastChangedBy: kutterma $
 $Id: XSD.pm 851 2009-05-15 22:45:18Z kutterma $
 $HeadURL: https://soap-wsdl.svn.sourceforge.net/svnroot/soap-wsdl/SOAP-WSDL/trunk/lib/SOAP/WSDL/Serializer/XSD.pm $

=cut

