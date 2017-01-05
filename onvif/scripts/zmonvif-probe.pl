#!/usr/bin/perl -w
use strict;
#
# ==========================================================================
#
# ZoneMinder ONVIF Control Protocol Module
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
# This module contains the implementation of the ONVIF capability prober
#

use Getopt::Std;
use Data::UUID;

require ONVIF::Client;

require WSDiscovery10::Interfaces::WSDiscovery::WSDiscoveryPort;
require WSDiscovery10::Elements::Header;
require WSDiscovery10::Elements::Types;
require WSDiscovery10::Elements::Scopes;

require WSDiscovery::TransportUDP;

# 
# ========================================================================
# Globals

my $verbose = 0;
my $soap_version = undef;
my $client;

# =========================================================================
# internal functions 

sub deserialize_message
{
  my ($wsdl_client, $response) = @_;

  # copied and adapted from SOAP::WSDL::Client

    # get deserializer
    my $deserializer = $wsdl_client->get_deserializer();

    if(! $deserializer) {
      $deserializer = SOAP::WSDL::Factory::Deserializer->get_deserializer({
        soap_version => $wsdl_client->get_soap_version(),
        %{ $wsdl_client->get_deserializer_args() },
      });
    }
    # set class resolver if serializer supports it
    $deserializer->set_class_resolver( $wsdl_client->get_class_resolver() )
        if ( $deserializer->can('set_class_resolver') );
          
    # Try deserializing response - there may be some,
    # even if transport did not succeed (got a 500 response)
    if ( $response ) {
        # as our faults are false, returning a success marker is the only
        # reliable way of determining whether the deserializer succeeded.
        # Custom deserializers may return an empty list, or undef,
        # and $@ is not guaranteed to be undefined.
        my ($success, $result_body, $result_header) = eval {
            (1, $deserializer->deserialize( $response ));
        };
        if (defined $success) {
            return wantarray
                ? ($result_body, $result_header)
                : $result_body;
        }
        elsif (blessed $@) { #}&& $@->isa('SOAP::WSDL::SOAP::Typelib::Fault11')) {
            return $@;
        }
        else {
            return $deserializer->generate_fault({
                code => 'soap:Server',
                role => 'urn:localhost',
                message => "Error deserializing message: $@. \n"
                    . "Message was: \n$response"
            });
        }
    };
}


sub interpret_messages
{
  my ($svc_discover, $services, @responses ) = @_;

  foreach my $response ( @responses ) {

    if($verbose) {
      print "Received message:\n" . $response . "\n";
    }

    my $result = deserialize_message($svc_discover, $response);
    if(not $result) {
      if($verbose) {
        print "Error deserializing message. No message returned from deserializer.\n";
      }
      next;
    }

    my $xaddr;  
    foreach my $l_xaddr (split ' ', $result->get_ProbeMatch()->get_XAddrs()) {
  #   find IPv4 address
      if($verbose) {
        print "l_xaddr = $l_xaddr\n";
      }
      if($l_xaddr =~ m|//[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+[:/]|) {
        $xaddr = $l_xaddr;
        last;
      } else {
        print STDERR "Unable to find IPv4 address from xaddr $l_xaddr\n";
      }
    }

    # No usable address found
    next if not $xaddr;

    # ignore multiple responses from one service
    next if defined $services->{$xaddr};
    $services->{$xaddr} = 1;

    print "$xaddr, " . $svc_discover->get_soap_version() . ", ";

    print "(";
    my $scopes = $result->get_ProbeMatch()->get_Scopes();
    my $count = 0;
    foreach my $scope(split ' ', $scopes) {
      if($scope =~ m|onvif://www\.onvif\.org/(.+)/(.*)|) {
        my ($attr, $value) = ($1,$2);
        if( 0 < $count ++) {
          print ", ";
        }
        print $attr . "=\'" . $value . "\'";
      }
    }
    print ")\n";
  }
}

# =========================================================================
# functions 

sub discover
{
  ## collect all responses
  my @responses = ();

  no warnings 'redefine';

  *WSDiscovery::TransportUDP::_notify_response = sub {
    my ($transport, $response) = @_;
    push @responses, $response;
  };

  ## try both soap versions
  my %services;

  my $uuid_gen = Data::UUID->new();
 
  if ( ( ! $soap_version ) or ( $soap_version eq '1.1' ) ) {

    if($verbose) {
      print "Probing for SOAP 1.1\n"
    }
    my $svc_discover = WSDiscovery10::Interfaces::WSDiscovery::WSDiscoveryPort->new({ 
#    no_dispatch => '1',
        });
    $svc_discover->set_soap_version('1.1');

    my $uuid = $uuid_gen->create_str();

    my $result = $svc_discover->ProbeOp(
        { # WSDiscovery::Types::ProbeType
        Types => 'http://www.onvif.org/ver10/network/wsdl:NetworkVideoTransmitter http://www.onvif.org/ver10/device/wsdl:Device', # QNameListType
        Scopes =>  { value => '' },
        },
        WSDiscovery10::Elements::Header->new({
          Action => { value => 'http://schemas.xmlsoap.org/ws/2005/04/discovery/Probe' },
          MessageID => { value => "urn:uuid:$uuid" }, 
          To => { value => 'urn:schemas-xmlsoap-org:ws:2005:04:discovery' },
          })
        );
    print $result . "\n" if $verbose;

    interpret_messages($svc_discover, \%services, @responses);
    @responses = ();
  } # end if doing soap 1.1

  if ( ( ! $soap_version ) or ( $soap_version eq '1.2' ) ) {
    if($verbose) {
      print "Probing for SOAP 1.2\n"
    }
    my $svc_discover = WSDiscovery10::Interfaces::WSDiscovery::WSDiscoveryPort->new({
#    no_dispatch => '1',
        });
    $svc_discover->set_soap_version('1.2');

# copies of the same Probe message must have the same MessageID. 
# This is not a copy. So we generate a new uuid.
    my $uuid = $uuid_gen->create_str();

# Everyone else, like the nodejs onvif code and odm only ask for NetworkVideoTransmitter
    my $result = $svc_discover->ProbeOp(
        { # WSDiscovery::Types::ProbeType
        xmlattr => { 'xmlns:dn'  => 'http://www.onvif.org/ver10/network/wsdl', },
        Types => 'dn:NetworkVideoTransmitter', # QNameListType
        Scopes =>  { value => '' },
        },
        WSDiscovery10::Elements::Header->new({
          Action => { value => 'http://schemas.xmlsoap.org/ws/2005/04/discovery/Probe' },
          MessageID => { value => "urn:uuid:$uuid" }, 
          To => { value => 'urn:schemas-xmlsoap-org:ws:2005:04:discovery' },
          })
        );
    print $result . "\n" if $verbose;
    interpret_messages($svc_discover, \%services, @responses);
  } # end if doing soap 1.2

}


sub profiles
{
#  my $result = $services{media}{ep}->GetVideoSources( { } ,, );
#  die $result if not $result;
#  print $result . "\n";

  my $result = $client->get_endpoint('media')->GetProfiles( { } ,, );
  die $result if not $result;
  if($verbose) {
    print "Received message:\n" . $result . "\n";
  }

 my $profiles = $result->get_Profiles();

 foreach  my $profile ( @{ $profiles } ) {
 
   my $token = $profile->attr()->get_token() ;
   print $token . ", " . 
         $profile->get_Name() . ", " .
         $profile->get_VideoEncoderConfiguration()->get_Encoding() . ", " .
         $profile->get_VideoEncoderConfiguration()->get_Resolution()->get_Width() . ", " .
         $profile->get_VideoEncoderConfiguration()->get_Resolution()->get_Height() . ", " .
         $profile->get_VideoEncoderConfiguration()->get_RateControl()->get_FrameRateLimit() .
         ", ";

   # Specification gives conflicting values for unicast stream types, try both.
   # http://www.onvif.org/onvif/ver10/media/wsdl/media.wsdl#op.GetStreamUri
   foreach my $streamtype ( 'RTP_unicast', 'RTP-Unicast' ) {
     $result = $client->get_endpoint('media')->GetStreamUri( {
       StreamSetup =>  { # ONVIF::Media::Types::StreamSetup
         Stream => $streamtype, # StreamType
         Transport =>  { # ONVIF::Media::Types::Transport
           Protocol => 'RTSP', # TransportProtocol
         },
       },
       ProfileToken => $token, # ReferenceToken
     } ,, );
     last if $result;
   }
   die $result if not $result;
  #  print $result . "\n";

    print $result->get_MediaUri()->get_Uri() .
          "\n";
 }

#
# use message parser without schema validation ???
#

}

sub move
{
  my ($dir) = @_;

  
  my $result = $client->get_endpoint('ptz')->GetNodes( { } ,, );
  
  die $result if not $result;
  print $result . "\n";

}

sub metadata
{
  my $result = $client->get_endpoint('media')->GetMetadataConfigurations( { } ,, );
  die $result if not $result;
  print $result . "\n";

  $result = $client->get_endpoint('media')->GetVideoAnalyticsConfigurations( { } ,, );
  die $result if not $result;
  print $result . "\n";

#  $result = $client->get_endpoint('analytics')->GetServiceCapabilities( { } ,, );
#  die $result if not $result;
#  print $result . "\n";
   
}

# ========================================================================
# options processing

$Getopt::Std::STANDARD_HELP_VERSION = 1;

our ($opt_v);

my $OPTIONS = "v";

sub HELP_MESSAGE
{
  my ($fh, $pkg, $ver, $opts) = @_;
  print $fh "Usage: " . __FILE__ . " [-v] probe <soap version>\n";
  print $fh "       " . __FILE__ . " [-v] <command> <device URI> <soap version> <user> <password>\n";
  print $fh  <<EOF
  Commands are:
    probe     - scan for devices on the local network and list them
    profiles  - print the device's supported stream configurations
    metadata  - print some of the device's configuration settings
    move      - move the device (only ptz cameras)
  Common parameters:
    -v        - increase verbosity
  Device access parameters (for all commands but 'probe'):
    device URL    - the ONVIF Device service URL
    soap version  - SOAP version (1.1 or 1.2)
    user          - username of a user with access to the device
    password      - password for the user
EOF
}

# ========================================================================
# MAIN

if(!getopts($OPTIONS)) {
  HELP_MESSAGE(\*STDOUT);
  exit(1);
}

if(defined $opt_v) {
  $verbose = 1;
}

my $action = shift;

if(!defined $action) {
  HELP_MESSAGE(\*STDOUT);
  exit(1);
}

if($action eq "probe") {
  $soap_version = shift;
  discover();
}
else {
# all other actions need URI and credentials
  my $url_svc_device = shift @ARGV;
  $soap_version = shift @ARGV;
  my $username = @ARGV ? shift @ARGV : '';
  my $password = @ARGV ? shift @ARGV: '';

  $client = ONVIF::Client->new( { 
      'url_svc_device' => $url_svc_device, 
      'soap_version' => $soap_version } );

  $client->set_credentials($username, $password, 1);
  
  $client->create_services();

  
  if($action eq "profiles") {
    
    profiles();
  }
  elsif($action eq "move") {
    my $dir = shift;
    move($dir);
  }
  elsif($action eq "metadata") {
    metadata();
  }
  else {
    print("Error: Unknown command\"$action\"");
    exit(1);
  }
}
