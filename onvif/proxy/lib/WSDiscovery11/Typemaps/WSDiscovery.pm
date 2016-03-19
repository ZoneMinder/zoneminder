
package WSDiscovery::Typemaps::WSDiscovery;
use strict;
use warnings;

our $typemap_1 = {
               'ProbeMatches/ProbeMatch/XAddrs' => 'WSDiscovery::Types::UriListType',
               'ProbeMatches/ProbeMatch' => 'WSDiscovery::Types::ProbeMatchType',
               'Fault/detail' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
               'ProbeMatches/ProbeMatch/EndpointReference/Metadata' => 'WSDiscovery::Types::MetadataType',
               'ProbeMatches/ProbeMatch/Scopes' => 'WSDiscovery::Types::ScopesType',
               'ProbeMatches/ProbeMatch/EndpointReference/Address' => 'WSDiscovery::Types::AttributedURIType',
               'Probe/Types' => 'WSDiscovery::Types::QNameListType',
               'Probe' => 'WSDiscovery::Elements::Probe',
               'Fault/faultstring' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
               'ProbeMatches/ProbeMatch/MetadataVersion' => 'SOAP::WSDL::XSD::Typelib::Builtin::unsignedInt',
               'Probe/Scopes' => 'WSDiscovery::Types::ScopesType',
               'Fault/faultactor' => 'SOAP::WSDL::XSD::Typelib::Builtin::token',
               'Fault/faultcode' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyURI',
               'ProbeMatches/ProbeMatch/Types' => 'WSDiscovery::Types::QNameListType',
               'ProbeMatches/ProbeMatch/EndpointReference/ReferenceParameters' => 'WSDiscovery::Types::ReferenceParametersType',
               'ProbeMatches/ProbeMatch/EndpointReference' => 'WSDiscovery::Types::EndpointReferenceType',
               'Fault' => 'SOAP::WSDL::SOAP::Typelib::Fault11',
               'ProbeMatches' => 'WSDiscovery::Elements::ProbeMatches',
               'MessageID' => 'WSDiscovery::Elements::MessageID',
               'RelatesTo' => '__SKIP__',
               'To' => '__SKIP__',
               'Action' => '__SKIP__',
               'AppSequence' => '__SKIP__',
              }; 
;

sub get_class {
  my $name = join '/', @{ $_[1] };
  return $typemap_1->{ $name };
}

sub get_typemap {
    return $typemap_1;
}

1;

__END__

__END__

=pod

=head1 NAME

WSDiscovery::Typemaps::WSDiscovery - typemap for WSDiscovery

=head1 DESCRIPTION

Typemap created by SOAP::WSDL for map-based SOAP message parsers.

=cut

