# ==========================================================================
#
# Perl WS-Security header for SOAP::WSDL
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
# Serializer with WS-Security header for SOAP::WSDL
#
package WSSecurity::SecuritySerializer;
use strict; 
use warnings;
use SOAP::WSDL::Factory::Serializer;
use Time::Local;
use Digest::SHA;
use MIME::Base64;


use base qw( ONVIF::Serializer::Base );

use version; our $VERSION = qv('1.00.00');

use constant    URI_SOAP11_ENV         => "http://schemas.xmlsoap.org/soap/envelope/";
use constant    URI_SOAP12_ENV         => "http://www.w3.org/2003/05/soap-envelope";

#SOAP::WSDL::Factory::Serializer->register( '1.1' , __PACKAGE__ );

my %username_of :ATTR(:name<username>       :default<()>);
my %password_of :ATTR(:name<password>       :default<()>);

#sub BUILD
#{
#  my ($self, $ident, $args_ref) = @_;
#  $soapversion_of{ $ident } = '1.2';
#}


SUBFACTORY: {
    for (qw(username password)) {
        my $setter = "set_$_";
        my $getter = "get_$_";
        no strict qw(refs);     ## no critic ProhibitNoStrict
        *{ $_ } = sub { my $self = shift;
            if (@_) {
                $self->$setter(@_);
                return $self;
            }
            return $self->$getter()
        };
    }
}

#
# #############################################################################
#
# the following methods have been adapted from an example implementation at
# http://www.wlp-systems.de/soap-lite-and-ws-security
#

sub timestamp {
    my ($sec,$min,$hour,$mday,$mon,$year,undef,undef,undef) = gmtime(time);
    $mon++;
    $year = $year + 1900;
    return sprintf("%04d-%02d-%02dT%02d:%02d:%02dZ",$year,$mon,$mday,$hour,$min,$sec);
}

sub create_generator {
    my ($name,$start_with) = @_;
    my $i = $start_with;
    return sub {  $name . ++$i; };
}

*default_nonce_generator = create_generator( "a value of ", int(1000*rand()) );

sub ws_authen {
    my($username,$password,$nonce_generator) = @_;
    if(!defined($nonce_generator)) {
        $nonce_generator = \&default_nonce_generator;
    }
    my $nonce = $nonce_generator->();
    my $timestamp = timestamp();

    my $pwDigest =  Digest::SHA::sha1( $nonce . $timestamp . $password );
    my $passwordHash = MIME::Base64::encode_base64($pwDigest,"");
    my $nonceHash = MIME::Base64::encode_base64($nonce,"");

    my $auth = <<END;
<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
               xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
  <wsse:UsernameToken>
    <wsse:Username>$username</wsse:Username>
    <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">$passwordHash</wsse:Password>
    <wsse:Nonce>$nonceHash</wsse:Nonce>
    <wsu:Created>$timestamp</wsu:Created>
  </wsse:UsernameToken>
</wsse:Security>
END

#    warn "Auth Header is: " . $auth;
    
    $auth;
}

#
# #############################################################################
#

sub security_header {
  my ($self) = @_;
  
  return ws_authen($self->username, $self->password, );
}

sub serialize_header() {
    my ($self, $method, $data, $opt) = @_;

    my $SOAP_NS = URI_SOAP11_ENV;
    if($self->soap_version() eq '1.2') {
      $SOAP_NS = URI_SOAP12_ENV;
    }

    # header is optional. Leave out if there's no header data
    return join ( q{},
        "<$opt->{ namespace }->{ $SOAP_NS }\:Header>",
        $self->security_header(),
        ( $data && blessed $data ) ? $data->serialize_qualified : (),
        "</$opt->{ namespace }->{ $SOAP_NS }\:Header>",
    );
}


1;
