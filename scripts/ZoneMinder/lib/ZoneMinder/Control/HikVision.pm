# ==========================================================================
#
# ZoneMinder HikVision Control Protocol Module
# Copyright (C) 2016 Terry Sanders
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# ==========================================================================
#
# This module contains an implementation of the HikVision ISAPI camera control
# protocol
#
package ZoneMinder::Control::HikVision;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# HiKVision ISAPI Control Protocol
#
# Set the following:
# ControlAddress: username:password@camera_webaddress:port
# ControlDevice: IP Camera Model or Device 1
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);

use Time::HiRes qw( usleep );

use LWP::UserAgent;
use HTTP::Cookies;
use URI;
use URI::Encode qw(uri_encode);
use Data::Dumper;
#use Crypt::Mode::CBC;
#use Crypt::Cipher::AES;

my $ChannelID = 1;              # Usually...
my $DefaultFocusSpeed = 50;     # Should be between 1 and 100
my $DefaultIrisSpeed = 50;      # Should be between 1 and 100
my $uri;


sub open {
  my $self = shift;
  $self->loadMonitor();
  $$self{port} = 80;

  # Create a UserAgent for the requests
  $self->{ua} = LWP::UserAgent->new();
  $self->{ua}->cookie_jar( {} );

  # Extract the username/password host/port from ControlAddress
  if ($self->{Monitor}{ControlAddress} 
      and
    $self->{Monitor}{ControlAddress} ne 'user:pass@ip'
      and
    $self->{Monitor}{ControlAddress} ne 'user:port@ip'
  ) {
    Debug("Using ControlAddress for credentials: $self->{Monitor}{ControlAddress}");
    $uri = URI->new($self->{Monitor}->{ControlAddress});
    $uri = URI->new('http://'.$self->{Monitor}->{ControlAddress}) if ref($uri) eq 'URI::_foreign';
    $$self{host} = $uri->host();
    if ( $uri->userinfo()) {
      @$self{'username','password'} = $uri->userinfo() =~ /^(.*):(.*)$/;
    } else {
      $$self{username} = $self->{Monitor}->{User};
      $$self{password} = $self->{Monitor}->{Pass};
    }
    # Check if it is a host and port or just a host
    if ( $$self{host} =~ /([^:]+):(.+)/ ) {
      $$self{host} = $1;
      $$self{port} = $2 ? $2 : $$self{port};
    }
  } elsif ($self->{Monitor}{Path}) {
    Debug("Using Path for credentials: $self->{Monitor}{Path}");
    if (($self->{Monitor}->{Path} =~ /^(?<PROTOCOL>(https?|rtsp):\/\/)?(?<USERNAME>[^:@]+)?:?(?<PASSWORD>[^\/@]+)?@(?<ADDRESS>[^:\/]+)/)) {
Debug("Have " . $+{USERNAME});
Debug("Have " . $+{PASSWORD});
      $$self{username} = $+{USERNAME} if $+{USERNAME} and !$$self{username};
      $$self{password} = $+{PASSWORD} if $+{PASSWORD} and !$$self{password};
      $$self{host} = $+{ADDRESS} if $+{ADDRESS};
    } elsif (($self->{Monitor}->{Path} =~ /^(?<PROTOCOL>(https?|rtsp):\/\/)?(?<ADDRESS>[^:\/]+)/)) {
      $$self{host} = $+{ADDRESS} if $+{ADDRESS};
      $$self{username} = $self->{Monitor}->{User} if $self->{Monitor}->{User} and !$$self{username};
      $$self{password} = $self->{Monitor}->{Pass} if $self->{Monitor}->{Pass} and !$$self{password};
    } else {
      $$self{username}= $self->{Monitor}->{User} if $self->{Monitor}->{User} and !$$self{username};
      $$self{password} = $self->{Monitor}->{Pass} if $self->{Monitor}->{Pass} and !$$self{password};
    }
    $uri = URI->new($self->{Monitor}->{Path});
    $uri->scheme('http');
    $uri->port(80);
    $uri->path('');
    $$self{host} = $uri->host();
  } else {
    Debug('Not using credentials');
  }
  # Save the base url
  $self->{BaseURL} = "http://$$self{host}:$$self{port}";
  $ChannelID = $self->{Monitor}{ControlDevice} if $self->{Monitor}{ControlDevice} =~ /^\d+$/;
  $$self{realm} = defined($self->{Monitor}->{ControlDevice}) ? $self->{Monitor}->{ControlDevice} : '';

  # Save and test the credentials
  if (defined($$self{username})) {
    Debug("Credentials: $$self{host}:$$self{port}, realm:$$self{realm}, $$self{username}, $$self{password}");
    $self->{ua}->credentials("$$self{host}:$$self{port}", $$self{realm}, $$self{username}, $$self{password});
  } # end if defined user

  my $url = '/ISAPI/System/deviceInfo';
  if ($self->get_realm($url)) {
    $self->{state} = 'open';
    return !undef;
  }
  return undef;
} # end sub open

sub get {
  my $self = shift;
  my $url = $self->{BaseURL}.shift;
  Debug("Getting $url");
  my $response = $self->{ua}->get($url);
  #Debug('Response: '. $response->status_line . ' ' . $response->content);
  return $response;
}

sub PutCmd {
  my $self = shift;
  my $cmd = shift;
  my $content = shift;
  if (!$cmd) {
    Error('No cmd specified in PutCmd');
    return;
  }
  my $req = HTTP::Request->new(PUT => $self->{BaseURL}.'/'.$cmd);
  if ( defined($content) ) {
    $req->content_type('application/x-www-form-urlencoded; charset=UTF-8');
    $req->content('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $content);
  }
  my $res = $self->{ua}->request($req);
  if (!$res->is_success) {
    # The camera timeouts connections at short intervals. When this
    # happens the user agent connects again and uses the same auth tokens.
    # The camera rejects this and asks for another token but the UserAgent
    # just gives up. Because of this I try the request again and it should
    # succeed the second time if the credentials are correct.
    #
    # Apparently it is necessary to create a new ua
    
    if ( $res->code == 401 ) {
      $self->{ua} = LWP::UserAgent->new();
      $self->{ua}->cookie_jar( {} );
      $self->{ua}->credentials("$$self{host}:$$self{port}", $$self{realm}, $$self{username}, $$self{password});

      $res = $self->{ua}->request($req);
      if (!$res->is_success) {
        # Check for username/password
        if ( $self->{Monitor}{ControlAddress} =~ /.+:(.+)@.+/ ) {
          Info('Check username/password is correct');
        } elsif ( $self->{Monitor}{ControlAddress} =~ /^[^:]+@.+/ ) {
          Info('No password in Control Address. Should there be one?');
        } elsif ( $self->{Monitor}{ControlAddress} =~ /^:.+@.+/ ) {
          Info('Password but no username in Control Address.');
        } else {
          Info('Missing username and password in Control Address.');
        }
        Error($res->status_line);
      }
    } else {
      Error($res->status_line);
    }
  } else {
    Debug("Success sending $cmd: ".$res->content);
  } # end unless res->is_success
  Debug($res->content);
} # end sub putCmd
#
# The move continuous functions all call moveVector
# with the direction to move in. This includes zoom
#
sub moveVector {
  my $self = shift;
  my $pandirection  = shift;
  my $tiltdirection = shift;
  my $zoomdirection = shift;
  my $params = shift;
  my $command;                    # The ISAPI/PTZ command

  my $duration = $self->duration();

  my $momentxml;
  if( $duration ) {
    $momentxml = "<Momentary><duration>$duration</duration></Momentary>";
    $command = "ISAPI/PTZCtrl/channels/$ChannelID/momentary";
  } else {
    $momentxml = '';
    $command = "ISAPI/PTZCtrl/channels/$ChannelID/continuous";
  }
  # Calculate movement speeds
  my $x = $pandirection  * $self->getParam( $params, 'panspeed', 0 );
  my $y = $tiltdirection * $self->getParam( $params, 'tiltspeed', 0 );
  my $z = $zoomdirection * $self->getParam( $params, 'speed', 0 );
  # Create the XML
  my $xml = '<PTZData>';
  $xml .= "<pan>$x</pan>" if $x;
  $xml .= "<tilt>$y</tilt>" if $y;
  $xml .= "<zoom>$z</zoom>" if $z;
  $xml .= $momentxml.'</PTZData>';
  # Send it to the camera
  $self->PutCmd($command, $xml);
}
sub zoomStop         { $_[0]->moveVector(  0,  0, 0, splice(@_,1)); }
sub moveStop         { $_[0]->moveVector(  0,  0, 0, splice(@_,1)); }
sub moveConUp        { $_[0]->moveVector(  0,  1, 0, splice(@_,1)); }
sub moveConUpRight   { $_[0]->moveVector(  1,  1, 0, splice(@_,1)); }
sub moveConRight     { $_[0]->moveVector(  1,  0, 0, splice(@_,1)); }
sub moveConDownRight { $_[0]->moveVector(  1, -1, 0, splice(@_,1)); }
sub moveConDown      { $_[0]->moveVector(  0, -1, 0, splice(@_,1)); }
sub moveConDownLeft  { $_[0]->moveVector( -1, -1, 0, splice(@_,1)); }
sub moveConLeft      { $_[0]->moveVector( -1,  0, 0, splice(@_,1)); }
sub moveConUpLeft    { $_[0]->moveVector( -1,  1, 0, splice(@_,1)); }
sub zoomConTele      { $_[0]->moveVector(  0,  0, 1, splice(@_,1)); }
sub zoomConWide      { $_[0]->moveVector(  0,  0,-1, splice(@_,1)); }
#
# Presets including Home set and clear
#
sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params,'preset');
  $self->PutCmd("ISAPI/PTZCtrl/channels/$ChannelID/presets/$preset/goto");
}
sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params,'preset');
  my $xml = "<PTZPreset><id>$preset</id></PTZPreset>";
  $self->PutCmd("ISAPI/PTZCtrl/channels/$ChannelID/presets/$preset",$xml);
}
sub presetHome {
  my $self = shift;
  my $params = shift;
  $self->PutCmd("ISAPI/PTZCtrl/channels/$ChannelID/homeposition/goto");
}

sub duration() {
  my $self = shift;
  my $params = shift;
  my $autostop = $self->getParam($params, 'autostop', 0);
  my $duration = $autostop * $self->{Monitor}{AutoStopTimeout};
  $duration = ($duration < 1000) ? $duration * 1000 : int($duration/1000);
  # Change from microseconds to milliseconds or seconds to milliseconds
  Debug("Calculate duration $duration from autostop($autostop) and AutoStopTimeout ".$self->{Monitor}{AutoStopTimeout});
  return $duration;
}
#
# Focus controls all call Focus with a +/- speed
#
sub Focus {
  my $self = shift;
  my $speed = shift;
  my $xml = "<FocusData><focus>$speed</focus></FocusData>";
  $self->PutCmd("ISAPI/System/Video/inputs/channels/$ChannelID/focus",$xml);
}
sub focusConNear {
  my $self = shift;
  my $params = shift;

  my $duration = $self->duration();
  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus(-$speed);
  if ($duration) {
    usleep($duration);
    $self->moveStop($params);
  }
}
sub Near {
  my $self = shift;
  my $params = shift;
  $self->Focus(-$DefaultFocusSpeed);
}
sub focusAbsNear {
  my $self = shift;
  my $params = shift;

  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus(-$speed);
}
sub focusRelNear {
  my $self = shift;
  my $params = shift;
  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus(-$speed);
}
sub focusConFar {
  my $self = shift;
  my $params = shift;

  my $duration = $self->duration();
  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus($speed);
  if($duration) {
    usleep($duration);
    $self->moveStop($params);
  }
}
sub Far {
  my $self = shift;
  my $params = shift;
  $self->Focus($DefaultFocusSpeed);
}
sub focusAbsFar {
  my $self = shift;
  my $params = shift;

  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus($speed);
}
sub focusRelFar {
  my $self = shift;
  my $params = shift;

  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus($speed);
}
#
# Iris controls all call Iris with a +/- speed
#
sub Iris {
  my $self = shift;
  my $speed = shift;

  my $xml = "<IrisData><iris>$speed</iris></IrisData>";
  $self->PutCmd("ISAPI/System/Video/inputs/channels/$ChannelID/iris",$xml);
}
sub irisConClose {
  my $self = shift;
  my $params = shift;

  my $duration = $self->duration();
  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris(-$speed);
  if($duration) {
    usleep($duration);
    $self->moveStop($params);
  }
}
sub Close {
  my $self = shift;
  my $params = shift;

  $self->Iris(-$DefaultIrisSpeed);
}
sub irisAbsClose {
  my $self = shift;
  my $params = shift;

  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris(-$speed);
}
sub irisRelClose {
  my $self = shift;
  my $params = shift;

  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris(-$speed);
}
sub irisConOpen {
  my $self = shift;
  my $params = shift;

  my $duration = $self->duration();
  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris($speed);
  if($duration) {
    usleep($duration);
    $self->moveStop($params);
  }
}
sub Open {
  my $self = shift;
  my $params = shift;

  $self->Iris($DefaultIrisSpeed);
}
sub irisAbsOpen {
  my $self = shift;
  my $params = shift;

  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris($speed);
}
sub irisRelOpen {
  my $self = shift;
  my $params = shift;

  # Get the iris speed
  my $speed = $self->getParam( $params, 'speed', $DefaultIrisSpeed );
  $self->Iris($speed);
}
#
# reset (reboot) the device
#
sub reboot {
  my $self = shift;

  $self->PutCmd('ISAPI/System/reboot');
}

my %config_types = (
    'ISAPI/System/deviceInfo' => {
    },
    'ISAPI/System/time' => {
    },
    'ISAPI/System/time/ntpServers' => {
    },
    'ISAPI/System/Network/interfaces' => {
    },
    'ISAPI/System/logServer' => {
    },
    'ISAPI/Streaming/channels/1' => {
      Video => {
        videoResolutionWidth => { value=>1920 },
        videoResolutionHeight => { value=>1080 },
        maxFrameRate => { value=>1000}, # appears to be fps * 100
        keyframeInterval => {value=>5000},
      }
    },
    'ISAPI/Streaming/channels/101' => {
      Video => {
        videoResolutionWidth => { value=>1920 },
        videoResolutionHeight => { value=>1080 },
        maxFrameRate => { value=>1000}, # appears to be fps * 100
        keyframeInterval => {value=>5000},
      }
    },
    'ISAPI/Streaming/channels/102' => {
      Video => {
        videoResolutionWidth => { value=>1920 },
        videoResolutionHeight => { value=>1080 },
        maxFrameRate => { value=>1000}, # appears to be fps * 100
        keyframeInterval => {value=>5000},
      }
    },
    'ISAPI/System/Video/inputs/channels/1/overlays' => {
    },
    'ISAPI/System/Video/inputs/channels/101/overlays' => {
    },
    'ISAPI/System/Video/inputs/channels/1/motionDetectionExt' => {
    },
    'ISAPI/System/Network/Integrate' => {
    },
    'ISAPI/Security/ONVIF/users' => {
    },
    'ISAPI/Security/users' => {
    },
  );

sub xml_apply_updates {
  my ( $dom, $updates ) = @_;
  foreach my $cat ( keys %{$updates} ) {
    Debug("Applying update for cat $cat");
    foreach my $key ( split('/', $cat) ) {
      Debug("Applying update for key $key");
      my @tags =  $dom->getElementsByTagName($key);
      if (!@tags) {
        if (defined($$updates{$key})) {
          Debug("Found no tags matching $key in " . $dom->toString());
          my $e = XML::LibXML::Element->new($key);
          $dom->addChild($e);
          push @tags, $e;
        }
      }
      foreach my $tag ( @tags ) {
        if (! defined($$updates{$key})) {
          Debug("Removing $key from " . $tag->toString());
          $tag->getParentNode()->removeChild($tag);
        } elsif ( ! ref $$updates{$key} ) {
          $tag->removeChildNodes();
          $tag->appendText($$updates{$key});
          Debug( "Applying $key => $$updates{$key} " . $tag->toString());
        } else {
          Debug("Descending for $tag $$updates{$key}");
          xml_apply_updates($tag, $$updates{$key});
        }
      } # end foreach tag
    } # end foreach key
  } # end foreach category

} # end sub

sub xml_to_hash {
  my ($self, $xml) = @_;
  if (! $xml) {
    Warning("No xml passed to xml_to_hash $self $xml");
    return 
  }
  my %updates;

  foreach my $node ($xml->childNodes()) {
    next if $node->nodeName eq '#text';
    my @children = $node->childNodes();
    if (@children == 1 and $children[0]->nodeName eq '#text') {
      #print "Have a value for ".$node->nodeName."\n";
      $updates{$node->nodeName} = $node->textContent;
    } else {
      #print "Recursing for ".$node->nodeName."\n";
      #recurse
      my %u = $self->xml_to_hash($node);
      if ( %u ) {
        $updates{$node->nodeName} = \%u;
      }
    }
  }
  return %updates;
}

sub get_config {
  my $self = shift;
  my %config;
  foreach my $category ( @_ ? @_ : keys %config_types ) {
    my $response = $self->get('/'.$category);
    Debug($response->content);
    my $dom = XML::LibXML->load_xml(string => $response->content);
    if (!$dom) {
      Error('No document from :'.$response->content());
      return;
    }
    my $xml = $dom->documentElement();
    my %c = $self->xml_to_hash($xml);
    if (%c) {
      $config{$category} = \%c;
    }
  } # end foreach category
  return \%config;
}

sub set_config {
  my $self = shift;
  my $diff = shift;
  foreach my $category ( @_ ? @_ : keys %config_types ) {
    if (! $$diff{$category}) {
      Debug("No changes for category $category");
      next;
    }
    Debug("Applying $category");

    my $response = $self->get('/'.$category);
    my $dom = XML::LibXML->load_xml(string => $response->content);
    if (!$dom) {
      Error('No document from :'.$response->content());
      return undef;
    }
    my $xml = $dom->documentElement();
    xml_apply_updates($xml, $$diff{$category});
    my $req = HTTP::Request->new(PUT=>$self->{BaseURL}.'/'.$category);
    Debug($xml->toString());
    $req->content($xml->toString());

    $response = $self->{ua}->request($req);
    if (!$response->is_success()) {
	    Error('status:'.$response->status_line);
	    Debug($response->content);
	    return undef;
    } else {
	    Debug('status:'.$response->status_line);
	    Debug($response->content);
    }
  }
  return !undef;
}

sub ping {
  my $self = shift;
  my $ip = @_ ? shift : $$self{host};
  return undef if ! $ip;

  require Net::Ping;
  Debug("Pinging $ip");

  my $p = Net::Ping->new();
  my $rv = $p->ping($ip);
  $p->close();
  Debug("Pinging $ip $rv");
  return $rv;
}

sub probe {
  my ($ip, $username, $password) = @_;

  my $self = new ZoneMinder::Control::HikVision();
  $self->set_credentials($username, $password);
  # Create a UserAgent for the requests
  $self->{ua} = LWP::UserAgent->new();
  $self->{ua}->cookie_jar( {} );

  foreach ( '80','443' ) {
    $$self{port} = $_;
    if ($self->get_realm('/ISAPI/Streaming/channels/101')) {
      return {
        url => 'http://'.$$self{username}.':'.$$self{password}.'@'.$ip.':'.$$self{port}.'/h264',
        realm => $$self{realm},
      };
    }
  } # end foreach port
  return undef;
}

sub profiles {
}

sub rtsp_url {
  my ($self, $ip) = @_;
  return 'rtsp://'.$ip.'/Streaming/Channels/101';
}

my %latest_firmware = (
  'I918L' => {
    latest_version=>'V5.7.1',
    build=>20211130,
    url=>'https://download.annke.com/firmware/4K_IPC/C800_5.7.1_211130.zip'
  },
  'DS-2CD2126G2-I' => {
    'latest_version'=>'V5.7.0',
    build=>240507,
    url=>'https://assets.hikvision.com/prd/public/all/files/202405/1715716961127/Firmware__V5.7.0_240507_S3000573675.zip',
    file=>'Firmware__V5.7.0_240507_S3000573675.zip',
  },
  'DS-2CD2046G2-I' => {
    'latest_version'=>'V5.7.18',
    build=>240826,
    url=>'https://assets.hikvision.com/prd/public/all/files/202409/Firmware__V5.7.18_240826_S3000597013.zip',
    file=>'Firmware__V5.7.18_240826_S3000597013.zip',
  },
  'DS-2CD2146G2-I' => {
    'latest_version'=>'V5.7.18',
    build=>240826,
    url=>'https://assets.hikvision.com/prd/public/all/files/202409/Firmware__V5.7.18_240826_S3000597013.zip',
    file=>'Firmware__V5.7.18_240826_S3000597013.zip',
  },
  'DS-2CD2142FWD-I' => {
    latest_version=>'V5.5.82',
    build=>190909,
    file=>'IPC_R6_EN_STD_5.5.82_190909.zip',
    url=>'https://www.hikvisioneurope.com/eu/portal/portal/Technical%20Materials/00%20%20Network%20Camera/00%20%20Product%20Firmware/R6%20platform%20%282X22FWD%2C%202X42FWD%2C%202X52%2C64X4FWD%2C1X31%2C1X41%29/V5.5.82_Build190909/IPC_R6_EN_STD_5.5.82_190909.zip',
  },
);

sub check_firmware {
  my $self = shift;
  my $config = $self->get_config('ISAPI/System/deviceInfo');
  print Dumper($config);
  my $model = $$config{'ISAPI/System/deviceInfo'}{model};
  if (!$model) {
    print "No model\n";
    return;
  }
  my $firmware = $$config{'ISAPI/System/deviceInfo'}{firmwareVersion};
  if ($latest_firmware{$model}) {
    my %result = %{$latest_firmware{$model}};
    $result{current_version} = $firmware;
    $result{current_build} = $$config{'ISAPI/System/deviceInfo'}{firmwareReleasedDate};
    $result{update_available} = ($firmware lt $result{latest_version});
    return %result;
  } else {
    Debug("We don't have a listing for latest firmware for ($model)");
  }
  return;
}

sub update_firmware {
  my $self = shift;
  my $firmware = shift;
  my $response = $self->put('/ISAPI/System/updateFirmware', $firmware);
}

1;
__END__
