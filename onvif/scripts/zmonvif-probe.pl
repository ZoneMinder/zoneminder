#!/usr/bin/perl -w
#
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

require ONVIF::Client;

require WSDiscovery::Interfaces::WSDiscovery::WSDiscoveryPort;
require WSDiscovery::Elements::Types;
require WSDiscovery::Elements::Scopes;

require WSDiscovery::TransportUDP;

#
# ========================================================================
# Globals

my $client;

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
    if($xaddr =~ m|//[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/|) {    
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


sub profiles
{
#  my $result = $services{media}{ep}->GetVideoSources( { } ,, );
#  die $result if not $result;
#  print $result . "\n";

  my $result = $client->get_endpoint('media')->GetProfiles( { } ,, );
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

    $result = $client->get_endpoint('media')->GetStreamUri( { 
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

  $result = $client->get_endpoint('analytics')->GetServiceCapabilities( { } ,, );
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

  $client = ONVIF::Client->new( { 'url_svc_device' => $url_svc_device } );

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
