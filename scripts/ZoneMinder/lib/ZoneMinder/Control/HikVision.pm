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
# ControlDevice: IP Camera Model
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);

use Time::HiRes qw( usleep );

use LWP::UserAgent;
use HTTP::Cookies;
use URI;
use URI::Encode qw(uri_encode);

my $ChannelID = 1;              # Usually...
my $DefaultFocusSpeed = 50;     # Should be between 1 and 100
my $DefaultIrisSpeed = 50;      # Should be between 1 and 100
my $uri;
my ($user, $pass, $host, $port, $realm) = ();

sub credentials {
  my $self = shift;
  ($user, $pass) = @_;
Debug("Setting credentials to $user/$pass");
}

sub open {
  my $self = shift;
  $self->loadMonitor();
  $port = 80;

  # Create a UserAgent for the requests
  $self->{UA} = LWP::UserAgent->new();
  $self->{UA}->cookie_jar( {} );

  # Extract the username/password host/port from ControlAddress
  if ($self->{Monitor}{ControlAddress} 
      and
    $self->{Monitor}{ControlAddress} ne 'user:pass@ip'
      and
    $self->{Monitor}{ControlAddress} ne 'user:port@ip'
  ) {
    Debug("Using ControlAddress for credentials: $self->{Monitor}{ControlAddress}");
    if ($self->{Monitor}{ControlAddress} =~ /^([^:]+):([^@]+)@(.+)/ ) { # user:pass@host...
      $user = $1 if !$user;
      $pass = $2 if !$pass;
      $host = $3;
    } elsif ( $self->{Monitor}{ControlAddress} =~ /^([^@]+)@(.+)/ ) { # user@host...
      $user = $1 if !$user;
      $host = $2;
    } else { # Just a host
      $host = $self->{Monitor}{ControlAddress};
    }
    # Check if it is a host and port or just a host
    if ( $host =~ /([^:]+):(.+)/ ) {
      $host = $1;
      $port = $2 ? $2 : $port;
    }
  } elsif ($self->{Monitor}{Path}) {
    Debug("Using Path for credentials: $self->{Monitor}{Path}");
    if (($self->{Monitor}->{Path} =~ /^(?<PROTOCOL>(https?|rtsp):\/\/)?(?<USERNAME>[^:@]+)?:?(?<PASSWORD>[^\/@]+)?@(?<ADDRESS>[^:\/]+)/)) {
Debug("Have " . $+{USERNAME});
Debug("Have " . $+{PASSWORD});
      $user = $+{USERNAME} if $+{USERNAME} and !$user;
      $pass = $+{PASSWORD} if $+{PASSWORD} and !$pass;
      $host = $+{ADDRESS} if $+{ADDRESS};
    } elsif (($self->{Monitor}->{Path} =~ /^(?<PROTOCOL>(https?|rtsp):\/\/)?(?<ADDRESS>[^:\/]+)/)) {
      $host = $+{ADDRESS} if $+{ADDRESS};
      $user = $self->{Monitor}->{User} if $self->{Monitor}->{User} and !$user;
      $pass = $self->{Monitor}->{Pass} if $self->{Monitor}->{Pass} and !$pass;
    } else {
      $user = $self->{Monitor}->{User} if $self->{Monitor}->{User} and !$user;
      $pass = $self->{Monitor}->{Pass} if $self->{Monitor}->{Pass} and !$pass;
    }
    $uri = URI->new($self->{Monitor}->{Path});
    $uri->scheme('http');
    $uri->port(80);
    $uri->path('');
    $host = $uri->host();
  } else {
    Debug('Not using credentials');
  }
  # Save the base url
  $self->{BaseURL} = "http://$host:$port";

  $ChannelID = $self->{Monitor}{ControlDevice} if $self->{Monitor}{ControlDevice};
  $realm = '';

  if (defined($user)) {
    Debug("Credentials: $host:$port, realm:$realm, $user, $pass");
    $self->{UA}->credentials("$host:$port", $realm, $user, $pass);
  } # end if defined user

  my $url = $self->{BaseURL} .'/ISAPI/Streaming/channels/101';
  my $response = $self->get($url);
  if ($response->status_line() eq '401 Unauthorized' and defined $user) {
    my $headers = $response->headers();
    foreach my $k ( keys %$headers ) {
      Debug("Initial Header $k => $$headers{$k}");
    }

    if ( $$headers{'www-authenticate'} ) {
      foreach my $auth_header ( ref $$headers{'www-authenticate'} eq 'ARRAY' ? @{$$headers{'www-authenticate'}} : ($$headers{'www-authenticate'})) {
        my ( $auth, $tokens ) = $auth_header =~ /^(\w+)\s+(.*)$/;
        Debug("Have tokens $auth $tokens");
        my %tokens = map { /(\w+)="?([^"]+)"?/i } split(', ', $tokens );
        if ( $tokens{realm} ) {
          if ( $realm ne $tokens{realm} ) {
            $realm = $tokens{realm};
            Debug("Changing REALM to $realm");
            $self->{UA}->credentials("$host:$port", $realm, $user, $pass);
            $response = $self->{UA}->get($url);
            if ( !$response->is_success() ) {
              Debug('Authentication still failed after updating REALM' . $response->status_line);
              $headers = $response->headers();
              foreach my $k ( keys %$headers ) {
                Debug("Initial Header $k => $$headers{$k}\n");
              }  # end foreach
            } else {
              last;
            }
          } else {
            Error('Authentication failed, not a REALM problem');
          }
        } else {
          Debug('Failed to match realm in tokens');
        } # end if
      } # end foreach auth header
    } else {
      debug('No headers line');
    } # end if headers
  } # end if not authen
  if ($response->is_success()) {
    $self->{state} = 'open';
  }
  Debug('Response: '. $response->status_line . ' ' . $response->content);
  return $response->is_success;
} # end sub open

sub get {
  my $self = shift;
  my $url = shift;
  Debug("Getting $url");
  my $response = $self->{UA}->get($url);
  #Debug('Response: '. $response->status_line . ' ' . $response->content);
  return $response;
}

sub PutCmd {
  my $self = shift;
  my $cmd = shift;
  my $content = shift;
  if (!$cmd) {
    Error("No cmd specified in PutCmd");
    return;
  }
  Debug("Put: $cmd to ".$self->{BaseURL}.(defined($content)?' content:'.$content:''));
  my $req = HTTP::Request->new(PUT => $self->{BaseURL}.'/'.$cmd);
  if ( defined($content) ) {
    $req->content_type('application/x-www-form-urlencoded; charset=UTF-8');
    $req->content('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $content);
  }
  my $res = $self->{UA}->request($req);
  if (!$res->is_success) {
    #
    # The camera timeouts connections at short intervals. When this
    # happens the user agent connects again and uses the same auth tokens.
    # The camera rejects this and asks for another token but the UserAgent
    # just gives up. Because of this I try the request again and it should
    # succeed the second time if the credentials are correct.
    #
    if ( $res->code == 401 ) {
      #
      # It has failed authentication. The odds are
      # that the user has set some parameter incorrectly
      # so check the realm against the ControlDevice
      # entry and send a message if different
      #
      my $headers = $res->headers();
      foreach my $k ( keys %$headers ) {
        Debug("Initial Header $k => $$headers{$k}");
      }

      if ( $$headers{'www-authenticate'} ) {
        foreach my $auth ( ref $$headers{'www-authenticate'} eq 'ARRAY' ? @{$$headers{'www-authenticate'}} : ($$headers{'www-authenticate'})) {
          foreach (split(/\s*,\s*/, $auth)) {
            if ( $_ =~ /^realm\s*=\s*"([^"]+)"/i ) {
              if ($realm ne $1) {
                Warning("Control Device appears to be incorrect.
                  Control Device should be set to \"$1\".
                  Control Device currently set to \"$self->{Monitor}{ControlDevice}\".");
                $realm = $1;
                $self->{UA}->credentials("$host:$port", $realm, $user, $pass);
                return PutCmd($self, $cmd, $content);
              }
            }
          } # end foreach auth token
        } # end foreach auth token
      } else {
        Debug("No authenticate header");
      }
      #
      # Check for username/password
      #
      if ( $self->{Monitor}{ControlAddress} =~ /.+:.+@.+/ ) {
        Info('Check username/password is correct');
      } elsif ( $self->{Monitor}{ControlAddress} =~ /^[^:]+@.+/ ) {
        Info('No password in Control Address. Should there be one?');
      } elsif ( $self->{Monitor}{ControlAddress} =~ /^:.+@.+/ ) {
        Info('Password but no username in Control Address.');
      } else {
        Info('Missing username and password in Control Address.');
      }
      Error($res->status_line);
    } else {
      Error($res->status_line);
    }
  } # end unless res->is_success
} # end sub putCmd

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

  # Calculate autostop time
  my $duration = $self->getParam( $params, 'autostop', 0 ) * $self->{Monitor}{AutoStopTimeout};
  # Change from microseconds to milliseconds
  $duration = int($duration/1000);
  my $momentxml;
  if ($duration) {
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

  # Calculate autostop time
  my $duration = $self->getParam( $params, 'autostop', 0 ) * $self->{Monitor}{AutoStopTimeout};
  # Get the focus speed
  my $speed = $self->getParam( $params, 'speed', $DefaultFocusSpeed );
  $self->Focus(-$speed);
  if($duration) {
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

  # Calculate autostop time
  my $duration = $self->getParam( $params, 'autostop', 0 ) * $self->{Monitor}{AutoStopTimeout};
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

  # Calculate autostop time
  my $duration = $self->getParam( $params, 'autostop', 0 ) * $self->{Monitor}{AutoStopTimeout};
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

  # Calculate autostop time
  my $duration = $self->getParam( $params, 'autostop', 0 ) * $self->{Monitor}{AutoStopTimeout};
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
    'ISAPI/System/time' => {
    },
    'ISAPI/System/time/ntpServers' => {
    },
    'ISAPI/Streaming/channels/101' => {
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
    my $response = $self->get($self->{BaseURL}.'/'.$category);
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

    my $response = $self->get($self->{BaseURL}.'/'.$category);
    my $dom = XML::LibXML->load_xml(string => $response->content);
    if (!$dom) {
      Error('No document from :'.$response->content());
      return;
    }
    my $xml = $dom->documentElement();
    xml_apply_updates($xml, $$diff{$category});
    my $req = HTTP::Request->new(PUT=>$self->{BaseURL}.'/'.$category);
    Debug($xml->toString());
    $req->content($xml->toString());

    $response = $self->{UA}->request($req);
    Debug( 'status:'.$response->status_line );
    Debug($response->content);
  }
}

sub ping {
  return -1 if ! $host;

  require Net::Ping;

  my $p = Net::Ping->new();
  my $rv = $p->ping($host);
  $p->close();
  return $rv;
}

sub probe {
  my ($ip, $user, $pass) = @_;

  my $self = new ZoneMinder::Control::HikVision();
  # Create a UserAgent for the requests
  $self->{UA} = LWP::UserAgent->new();
  $self->{UA}->cookie_jar( {} );
  my $realm;

  foreach my $port ( '80','443' ) {
    my $url = 'http://'.$user.':'.$pass.'@'.$ip.':'.$port.'/ISAPI/Streaming/channels/101';
    Debug("Probing $url");
    my $response = $self->get($url);
    if ($response->status_line() eq '401 Unauthorized' and defined $user) {
      my $headers = $response->headers();
      foreach my $k ( keys %$headers ) {
        Debug("Initial Header $k => $$headers{$k}");
      }

      if ( $$headers{'www-authenticate'} ) {
        my ( $auth, $tokens ) = $$headers{'www-authenticate'} =~ /^(\w+)\s+(.*)$/;
        my %tokens = map { /(\w+)="?([^"]+)"?/i } split(', ', $tokens );
        if ($tokens{realm}) {
          $realm = $tokens{realm};
          Debug('Changing REALM to '.$tokens{realm});
          $self->{UA}->credentials("$ip:$port", $tokens{realm}, $user, $pass);
          $response = $self->{UA}->get($url);
          if (!$response->is_success()) {
            Error('Authentication still failed after updating REALM' . $response->status_line);
          }
          $headers = $response->headers();
          foreach my $k ( keys %$headers ) {
            Debug("Initial Header $k => $$headers{$k}\n");
          }  # end foreach
        } else {
          Debug('Failed to match realm in tokens');
        } # end if
      } else {
        Debug('No headers line');
      } # end if headers
    } # end if not authen
    Debug('Response: '. $response->status_line . ' ' . $response->content);
    if ($response->is_success) {
      return {
        url => 'http://'.$user.':'.$pass.'@'.$ip.':'.$port.'/h264',
        realm => $realm,
      };
    }
  } # end foreach port
  return undef;
}

sub profiles {
}

1;
__END__
