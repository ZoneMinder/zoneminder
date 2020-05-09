
package WSDiscovery10::Typemaps::WSDiscovery;
use strict;
use warnings;

our $typemap_1 = {
               'Fault/faultactor' => 'SOAP::WSDL::XSD::Typelib::Builtin::token',
               'Fault' => 'SOAP::WSDL::SOAP::Typelib::Fault11',
               'Probe/Types' => 'WSDiscovery10::Types::QNameListType',
               'ProbeMatches/ProbeMatch/EndpointReference/ReferenceParameters' => 'WSDiscovery10::Types::ReferenceParametersType',
               'Fault/faultstring' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
               'Fault/detail' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
               'Probe/Scopes' => 'WSDiscovery10::Types::ScopesType',
               'Fault/faultcode' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyURI',
               'ProbeMatches/ProbeMatch' => 'WSDiscovery10::Types::ProbeMatchType',
               'ProbeMatches/ProbeMatch/MetadataVersion' => 'SOAP::WSDL::XSD::Typelib::Builtin::unsignedInt',
               'ProbeMatches/ProbeMatch/Scopes' => 'WSDiscovery10::Types::ScopesType',
               'ProbeMatches' => 'WSDiscovery10::Elements::ProbeMatches',
               'ProbeMatches/ProbeMatch/EndpointReference/ServiceName' => 'WSDiscovery10::Types::ServiceNameType',
               'Probe' => 'WSDiscovery10::Elements::Probe',
               'ProbeMatches/ProbeMatch/EndpointReference/Address' => 'WSDiscovery10::Types::AttributedURI',
               'ProbeMatches/ProbeMatch/XAddrs' => 'WSDiscovery10::Types::UriListType',
               'ProbeMatches/ProbeMatch/Types' => 'WSDiscovery10::Types::QNameListType',
               'ProbeMatches/ProbeMatch/EndpointReference' => 'WSDiscovery10::Types::EndpointReferenceType',
               'ProbeMatches/ProbeMatch/EndpointReference/ReferenceProperties' => 'WSDiscovery10::Types::ReferencePropertiesType',
               'ProbeMatches/ProbeMatch/EndpointReference/PortType' => 'WSDiscovery10::Types::AttributedQName',
               'MessageID' => '__SKIP__',
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

WSDiscovery10::Typemaps::WSDiscovery - typemap for WSDiscovery

=head1 DESCRIPTION

Typemap created by SOAP::WSDL for map-based SOAP message parsers.

=cut

