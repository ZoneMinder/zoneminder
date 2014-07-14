# ==========================================================================
#
# ZoneMinder ONVIF Control Protocol Module
# Copyright (C) Jan M. Hochstein
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
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
#
# ==========================================================================
#
# This module contains the implementation of the ONVIF capability prober
#

require SOAP::WSDL::Transport::HTTP;

require WSDiscovery::Interfaces::WSDiscovery::WSDiscoveryPort;
require WSDiscovery::Elements::Types;
require WSDiscovery::Elements::Scopes;

require WSDiscovery::TransportUDP;
require WSSecurity::SecuritySerializer;

require ONVIF::Device::Interfaces::Device::DevicePort;
require ONVIF::Media::Interfaces::Media::MediaPort;
require ONVIF::PTZ::Interfaces::PTZ::PTZPort;

use Data::Dump qw(dump);

# ========================================================================
# Globals

my %namespace_map = (
  'http://www.onvif.org/ver10/device/wsdl'      => 'device',    
  'http://www.onvif.org/ver10/media/wsdl'       => 'media',   
  'http://www.onvif.org/ver20/imaging/wsdl'     => 'imaging',
  'http://www.onvif.org/ver20/analytics/wsdl'   => 'analytics',
  'http://www.onvif.org/ver10/deviceIO/wsdl'    => 'deviceio', 
  'http://www.onvif.org/ver10/ptz/wsdl'         => 'ptz',
  'http://www.onvif.org/ver10/events/wsdl'      => 'events',
);

my %services = { };

my $serializer;

# =========================================================================

sub discover
{
  my $svc_discover = WSDiscovery::Interfaces::WSDiscovery::WSDiscoveryPort->new();

  my $result = $svc_discover->ProbeOp(
    { # WSDiscovery::Types::ProbeType
      Types => { 'dn:NetworkVideoTransmitter', 'tds:Device' }, # QNameListType
      Scopes =>  { value => '' },
    },, 
  );
  die $result if not $result;
#  print $result;

  foreach my $xaddr (split ' ', $result->get_ProbeMatch()->get_XAddrs()) {
#   find IPv4 address
    if($xaddr =~ m|//[0-9]+.[0-9]+.[0-9]+.[0-9]+./|) {    
      print $xaddr . ", ";
      last;
    }
  }
  
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

sub get_services
{
  my $result = $services{device}{ep}->GetServices( {
    IncludeCapability =>  'true', # boolean
    },,
  );

  die $result if not $result;
#  print $result . "\n";

 foreach  my $svc ( @{ $result->get_Service() } ) {
    my $short_name = $namespace_map{$svc->get_Namespace()};
    my $url_svc = $svc->get_XAddr()->get_value();
    if(defined $short_name && defined $url_svc) {
#      print "Got $short_name service\n";
      $services{$short_name}{url} = $url_svc;
    }
  }
}

sub create_services
{
  if(defined $services{media}{url})  {
    $services{media}{ep} = ONVIF::Media::Interfaces::Media::MediaPort->new({
      proxy => $services{media}{url},
      serializer => $serializer,
#      transport => $transport
    });
  }
  if(defined $services{ptz}{url})  {
    $services{ptz}{ep} = ONVIF::PTZ::Interfaces::PTZ::PTZPort->new({
      proxy => $services{ptz}{url},
      serializer => $serializer,
#      transport => $transport
    });
  }
}

sub get_users
{
  my $result = $services{device}{ep}->GetUsers( { },, );

  die $result if not $result;
  print $result . "\n";
}

sub create_user
{
  my ($username, $password) = @_;
  
  my $result = $services{device}{ep}->CreateUsers( { 
    },, 
  );

  die $result if not $result;
  print $result . "\n";
  
}

sub http_digest {
  my ($service, $username, $password) = @_;

#  my $transport = SecurityTransport->new();
#  $transport->set_username($username);
#  $transport->set_password($password);

#  warn "transport: " . $service->get_transport();

  *SOAP::Transport::HTTP::Client::get_basic_credentials = sub {
  #*SOAP::WSDL::Transport::HTTP::get_basic_credentials = sub {
    my ($self, $realm, $uri, $isproxy) = @_;

    warn "### Requested credentials for $uri ###";

    return ($username, $password)
  };
}  



sub profiles
{
#  my $result = $services{media}{ep}->GetVideoSources( { } ,, );
#  die $result if not $result;
#  print $result . "\n";

  $result = $services{media}{ep}->GetProfiles( { } ,, );
  die $result if not $result;
#  print $result . "\n";

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

    $result = $services{media}{ep}->GetStreamUri( { 
      StreamSetup =>  { # ONVIF::Media::Types::StreamSetup
        Stream => 'RTP_unicast', # StreamType
        Transport =>  { # ONVIF::Media::Types::Transport
          Protocol => 'RTSP', # TransportProtocol
        },
      },
      ProfileToken => $token, # ReferenceToken  
    } ,, );
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

  
  my $result = $services{ptz}{ep}->GetNodes( { } ,, );
  
  die $result if not $result;
  print $result . "\n";

}

# ========================================================================
# MAIN

my $action = shift;

if($action eq "probe") {
  discover();
}
else {
# all other actions need URI and credentials
  my $url_svc_device = shift;
  my $username = shift;
  my $password = shift;

  my $svc_device = ONVIF::Device::Interfaces::Device::DevicePort->new({
   proxy => $url_svc_device,
   deserializer_args => { strict => 0 }
  });

  $services{'device'} = { url => $url_svc_device, ep => $svc_device };

  get_services();

#  TODO: snyc device and client time  

# If GetUsers() is ok but empty then CreateUser()
#  if(not get_users()) {
#    create_user($username, $password);
#  }
  
  
  ## from here on use authorization
  $serializer = WSSecurity::SecuritySerializer->new();
  $serializer->set_username($username);
  $serializer->set_password($password);

  $services{device}{ep}->set_serializer($serializer);

  create_services($username, $password);

  
  if($action eq "profiles") {
    
    profiles();
  }
  elsif($action eq "move") {
    my $dir = shift;
    move($dir);
  }
  else {
    print("Error: Unknown command\"$action\"");
    exit(1);
  }
}
