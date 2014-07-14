package ONVIF::Device::Types::SecurityCapabilities;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/device/wsdl' };

our $XML_ATTRIBUTE_CLASS = 'ONVIF::Device::Types::SecurityCapabilities::_SecurityCapabilities::XmlAttr';

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}



# There's no variety - empty complexType
use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

__PACKAGE__->_factory();


package ONVIF::Device::Types::SecurityCapabilities::_SecurityCapabilities::XmlAttr;
use base qw(SOAP::WSDL::XSD::Typelib::AttributeSet);

{ # BLOCK to scope variables

my %TLS1_0_of :ATTR(:get<TLS1.0>);
my %TLS1_1_of :ATTR(:get<TLS1.1>);
my %TLS1_2_of :ATTR(:get<TLS1.2>);
my %OnboardKeyGeneration_of :ATTR(:get<OnboardKeyGeneration>);
my %AccessPolicyConfig_of :ATTR(:get<AccessPolicyConfig>);
my %DefaultAccessPolicy_of :ATTR(:get<DefaultAccessPolicy>);
my %Dot1X_of :ATTR(:get<Dot1X>);
my %RemoteUserHandling_of :ATTR(:get<RemoteUserHandling>);
my %X_509Token_of :ATTR(:get<X.509Token>);
my %SAMLToken_of :ATTR(:get<SAMLToken>);
my %KerberosToken_of :ATTR(:get<KerberosToken>);
my %UsernameToken_of :ATTR(:get<UsernameToken>);
my %HttpDigest_of :ATTR(:get<HttpDigest>);
my %RELToken_of :ATTR(:get<RELToken>);
my %SupportedEAPMethods_of :ATTR(:get<SupportedEAPMethods>);
my %MaxUsers_of :ATTR(:get<MaxUsers>);

__PACKAGE__->_factory(
    [ qw(
        TLS1.0
        TLS1.1
        TLS1.2
        OnboardKeyGeneration
        AccessPolicyConfig
        DefaultAccessPolicy
        Dot1X
        RemoteUserHandling
        X.509Token
        SAMLToken
        KerberosToken
        UsernameToken
        HttpDigest
        RELToken
        SupportedEAPMethods
        MaxUsers
    ) ],
    {

        'TLS1.0' => \%TLS1_0_of,

        'TLS1.1' => \%TLS1_1_of,

        'TLS1.2' => \%TLS1_2_of,

        OnboardKeyGeneration => \%OnboardKeyGeneration_of,

        AccessPolicyConfig => \%AccessPolicyConfig_of,

        DefaultAccessPolicy => \%DefaultAccessPolicy_of,

        Dot1X => \%Dot1X_of,

        RemoteUserHandling => \%RemoteUserHandling_of,

        'X.509Token' => \%X_509Token_of,

        SAMLToken => \%SAMLToken_of,

        KerberosToken => \%KerberosToken_of,

        UsernameToken => \%UsernameToken_of,

        HttpDigest => \%HttpDigest_of,

        RELToken => \%RELToken_of,

        SupportedEAPMethods => \%SupportedEAPMethods_of,

        MaxUsers => \%MaxUsers_of,
    },
    {
        'TLS1.0' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'TLS1.1' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'TLS1.2' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        OnboardKeyGeneration => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        AccessPolicyConfig => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        DefaultAccessPolicy => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        Dot1X => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        RemoteUserHandling => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'X.509Token' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        SAMLToken => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        KerberosToken => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        UsernameToken => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        HttpDigest => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        RELToken => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        SupportedEAPMethods => 'ONVIF::Device::Types::EAPMethodTypes',
        MaxUsers => 'SOAP::WSDL::XSD::Typelib::Builtin::int',
    }
);

} # end BLOCK




1;


=pod

=head1 NAME

ONVIF::Device::Types::SecurityCapabilities

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
SecurityCapabilities from the namespace http://www.onvif.org/ver10/device/wsdl.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over



=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

,



=head2 attr

NOTE: Attribute documentation is experimental, and may be inaccurate.
See the correspondent WSDL/XML Schema if in question.

This class has additional attributes, accessibly via the C<attr()> method.

attr() returns an object of the class ONVIF::Device::Types::SecurityCapabilities::_SecurityCapabilities::XmlAttr.

The following attributes can be accessed on this object via the corresponding
get_/set_ methods:

=over

=item * TLS1.0

 Indicates support for TLS 1.0.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * TLS1.1

 Indicates support for TLS 1.1.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * TLS1.2

 Indicates support for TLS 1.2.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * OnboardKeyGeneration

 Indicates support for onboard key generation.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * AccessPolicyConfig

 Indicates support for access policy configuration.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * DefaultAccessPolicy

 Indicates support for the ONVIF default access policy.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * Dot1X

 Indicates support for IEEE 802.1X configuration.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * RemoteUserHandling

 Indicates support for remote user configuration. Used when accessing another device.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * X.509Token

 Indicates support for WS-Security X.509 token.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * SAMLToken

 Indicates support for WS-Security SAML token.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * KerberosToken

 Indicates support for WS-Security Kerberos token.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * UsernameToken

 Indicates support for WS-Security Username token.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * HttpDigest

 Indicates support for WS over HTTP digest authenticated communication layer.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * RELToken

 Indicates support for WS-Security REL token.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::boolean|SOAP::WSDL::XSD::Typelib::Builtin::boolean>.

=item * SupportedEAPMethods

 IANA EAP Registry.



This attribute is of type L<ONVIF::Device::Types::EAPMethodTypes|ONVIF::Device::Types::EAPMethodTypes>.

=item * MaxUsers

 The maximum number of users that the device supports.



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::int|SOAP::WSDL::XSD::Typelib::Builtin::int>.


=back




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

