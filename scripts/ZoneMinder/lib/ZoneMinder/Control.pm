# ==========================================================================
#
# ZoneMinder Base Control Module
# Copyright (C) 2001-2008  Philip Coombes
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
# ==========================================================================
#
# This module contains the base class definitions for the camera control
# protocol implementations
#
package ZoneMinder::Control;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Object;
require ZoneMinder::Monitor;

require URI;
require URI::Escape;
require HTTP::Request;

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Base control class
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Database qw(:all);

use parent qw(ZoneMinder::Object);

use vars qw/ $table $primary_key %fields $serial %defaults $debug/;
$table = 'Controls';
$serial = $primary_key = 'Id';
%fields = map { $_ => $_ } qw(
  Id
  Name
  Type
  Protocol
  CanWake
  CanSleep
  CanReset
  CanReboot
  CanZoom
  CanAutoZoom
  CanZoomAbs
  CanZoomRel
  CanZoomCon
  MinZoomRange
  MaxZoomRange
  MinZoomStep
  MaxZoomStep
  HasZoomSpeed
  MinZoomSpeed
  MaxZoomSpeed
  CanFocus
  CanAutoFocus
  CanFocusAbs
  CanFocusRel
  CanFocusCon
  MinFocusRange
  MaxFocusRange
  MinFocusStep
  MaxFocusStep
  HasFocusSpeed
  MinFocusSpeed
  MaxFocusSpeed
  CanIris
  CanAutoIris
  CanIrisAbs
  CanIrisRel
  CanIrisCon
  MinIrisRange
  MaxIrisRange
  MinIrisStep
  MaxIrisStep
  HasIrisSpeed
  MinIrisSpeed
  MaxIrisSpeed
  CanGain
  CanAutoGain
  CanGainAbs
  CanGainRel
  CanGainCon
  MinGainRange
  MaxGainRange
  MinGainStep
  MaxGainStep
  HasGainSpeed
  MinGainSpeed
  MaxGainSpeed
  CanWhite
  CanAutoWhite
  CanWhiteAbs
  CanWhiteRel
  CanWhiteCon
  MinWhiteRange
  MaxWhiteRange
  MinWhiteStep
  MaxWhiteStep
  HasWhiteSpeed
  MinWhiteSpeed
  MaxWhiteSpeed
  HasPresets
  NumPresets
  HasHomePreset
  CanSetPresets
  CanMove
  CanMoveDiag
  CanMoveMap
  CanMoveAbs
  CanMoveRel
  CanMoveCon
  CanPan
  MinPanRange
  MaxPanRange
  MinPanStep
  MaxPanStep
  HasPanSpeed
  MinPanSpeed
  MaxPanSpeed
  HasTurboPan
  TurboPanSpeed
  CanTilt
  MinTiltRange
  MaxTiltRange
  MinTiltStep
  MaxTiltStep
  HasTiltSpeed
  MinTiltSpeed
  MaxTiltSpeed
  HasTurboTilt
  TurboTiltSpeed
  CanAutoScan
  NumScanPaths
  );
%defaults = (
	Name => '',
    Type => q`'Ffmpeg'`,
    CanWake => '0',
    CanSleep => '0',
    CanReset => '0',
    CanReboot => '0',
    CanZoom => '0',
    CanAutoZoom => '0',
    CanZoomAbs => '0',
    CanZoomRel =>'0',
    CanZoomCon => '0',
    MinZoomRange  => undef,
    MaxZoomRange  => undef,
    MinZoomStep   => undef,
    MaxZoomStep => undef,
    HasZoomSpeed  => 0,
    MinZoomSpeed  => 0,
    MaxZoomSpeed => 0,
    CanFocus  => 0,
    CanAutoFocus  => 0,
    CanFocusAbs => 0,
    CanFocusRel => 0,
    CanFocusCon   => 0,
    MinFocusRange => undef,
    MaxFocusRange => undef,
    MinFocusStep  => undef,
    MaxFocusStep => undef,
    HasFocusSpeed => 0,
    MinFocusSpeed => undef,
    MaxFocusSpeed => undef,
    CanIris => 0,
    CanAutoIris => 0,
    CanIrisAbs=> 0,
    CanIrisRel => 0,
    CanIrisCon => 0,
    MinIrisRange => undef,
    MaxIrisRange => undef,
    MinIrisStep => undef,
    MaxIrisStep => undef,
    HasIrisSpeed => 0,
    MinIrisSpeed => undef,
    MaxIrisSpeed => undef,
    CanGain => 0,
    CanAutoGain => 0,
    CanGainAbs => 0,
    CanGainRel => 0,
    CanGainCon => 0,
    MinGainRange  => undef,
    MaxGainRange  => undef,
    MinGainStep => undef,
    MaxGainStep => undef,
    HasGainSpeed => 0,
    MinGainSpeed => undef,
    MaxGainSpeed => undef,
    CanWhite => 0,
    CanAutoWhite => 0,
    CanWhiteAbs => 0,
    CanWhiteRel => 0,
    CanWhiteCon => 0,
    MinWhiteRange => undef,
    MaxWhiteRange => undef,
    MinWhiteStep => undef,
    MaxWhiteStep => undef,
    HasWhiteSpeed => 0,
    MinWhiteSpeed => undef,
    MaxWhiteSpeed => undef,
    HasPresets => 0,
    NumPresets => 0,
    HasHomePreset => 0,
    CanSetPresets => 0,
    CanMove => 0,
    CanMoveDiag => 0,
    CanMoveMap => 0,
    CanMoveAbs => 0,
    CanMoveRel => 0,
    CanMoveCon => 0,
    CanPan => 0,
    MinPanRange => undef,
    MaxPanRange => undef,
    MinPanStep => undef,
    MaxPanStep => undef,
    HasPanSpeed => 0,
    MinPanSpeed => undef,
    MaxPanSpeed => undef,
    HasTurboPan => 0,
    TurboPanSpeed => undef,
    CanTilt => 0,
    MinTiltRange => undef,
    MaxTiltRange => undef,
    MinTiltStep => undef,
    MaxTiltStep => undef,
    HasTiltSpeed => 0,
    MinTiltSpeed => undef,
    MaxTiltSpeed => undef,
    HasTurboTilt => 0,
    TurboTiltSpeed => undef,
    CanAutoScan => 0,
    NumScanPaths => 0,
    );

our $AUTOLOAD;

sub AUTOLOAD {
  my $self = shift;
  my $class = ref($self);
  if ( !$class ) {
    my ( $caller, undef, $line ) = caller;
    Fatal("$self not object from $caller:$line");
  }

  my $name = $AUTOLOAD;
  $name =~ s/.*://;
  if ( exists($self->{$name}) ) {
    return $self->{$name};
  }
  my ( $caller, undef, $line ) = caller;
  Error("Can't access name:$name AUTOLOAD:$AUTOLOAD member of object of class $class from $caller:$line");
}

sub getKey {
  my $self = shift;
  return $self->{Id};
}

sub open {
  my $self = shift;
  Fatal('No open method defined for protocol '.$self->{Protocol});
}

sub close {
  my $self = shift;
  $self->{state} = 'closed';
  Debug('No close method defined for protocol '.$self->{Protocol});
}

sub loadMonitor {
  my $self = shift;
  if ( !$self->{Monitor} ) {
    if ( !($self->{Monitor} = ZoneMinder::Monitor->find_one(Id=>$self->{MonitorId})) ) {
      Fatal('Monitor id '.$self->{id}.' not found');
    }
    if ( defined($self->{Monitor}->{AutoStopTimeout}) ) {
# Convert to microseconds.
      $self->{Monitor}->{AutoStopTimeout} = int(1000000*$self->{Monitor}->{AutoStopTimeout});
    }
  }
}

sub getParam {
  my $self = shift;
  my $params = shift;
  my $name = shift;
  my $default = shift;

  if ( defined($params->{$name}) ) {
    return $params->{$name};
  } elsif ( defined($default) ) {
    return $default;
  }
  Error("Missing mandatory parameter '$name'");
}

sub executeCommand {
  my $self = shift;
  my $params = shift;

  $self->loadMonitor();

  my $command = $params->{command};
  delete $params->{command};

#if ( !defined($self->{$command}) )
#{
#Fatal( "Unsupported command '$command'" );
#}
  &{$self->{$command}}($self, $params);
}

# Uses LWP get command and adds debugging
# if $$self{BaseURL} is defined then it will be prepended
sub get {
  my $self = shift;
  my $url = shift;
  if (!$url) {
    Error('No url specified in get');
    return;
  }
  $url = $$self{BaseURL}.$url if $$self{BaseURL};
  my $response = $self->{ua}->get($url);
  Debug("Response from $url: ". $response->status_line . ' ' . $response->content);
  return $response;
}

sub put {
  my $self = shift;
  my $url = shift;
  if (!$url) {
    Error('No url specified in put');
    return;
  }
  $url = $$self{BaseURL}.$url if $$self{BaseURL};
  my $req = HTTP::Request->new(PUT => $url);

  my $content = shift;

  $req->content($content) if defined($content);

  my $options = shift if @_;
  if ($options) {
    foreach my $header (keys %$options) {
      if ($req->can($header) ) {
        $req->$header($$options{$header});
      } else {
        $req->header($header=>$$options{$header});
      }
    }
  }

  #defaults
  $req->header('Content-Length' => length $content) if !$$options{'Content-Length'};
  $req->content_type('application/x-www-form-urlencoded; charset=UTF-8') if ! $$options{'Content-Type'};

  my $res = $self->{ua}->request($req);
  if (!$res->is_success) {
    Error($res->status_line);
  } # end unless res->is_success
  Debug('Response: '. $res->status_line . ' ' . $res->content);
  return $res;
} # end sub put

sub post {
  my $self = shift;
  my $url = shift;
  if (!$url) {
    Error('No url specified in put');
    return;
  }
  $url = $$self{BaseURL}.$url if $$self{BaseURL};
  my $content = shift if @_;
  my $headers = shift if @_;
  my $req = HTTP::Request->new('POST', $url);
  if ( defined($content) ) {
    if (ref $content eq 'HASH') {
      my $uri = $$self{uri};
      $uri->query_form(%{$content});
      $content = $uri->query;
    }
    $req->content_type('application/x-www-form-urlencoded; charset=UTF-8');
    $req->content($content);
  }

  if (defined $headers) {
    if (ref $headers eq 'HASH') {
      foreach my $key (keys %$headers) {
        $req->header($key=>$$headers{$key});
      }
    } else {
      Debug("WHat is headers? ".Data::Dumper::Dumper($headers));
    }
  }

  my $res = $self->{ua}->request($req);
  if (!$res->is_success) {
    Error($res->status_line);
  } # end unless res->is_success
  Debug('Response for '.$url.': '. $res->status_line . ' ' . $res->content);
  return $res;
} # end sub post

sub printMsg {
  my $self = shift;
  my $msg = shift;
  my $msg_len = length($msg);

  Debug($msg.'['.$msg_len.']');
}

sub credentials {
  my $self = shift;
  @$self{'username', 'password'} = @_;
}

sub parse_ControlAddress {
  my $self = shift;
  my $monitor = $self->{Monitor};
  # Extract the username/password host/port from ControlAddress
  if ($$monitor{ControlAddress}
      and $$monitor{ControlAddress} ne 'user:pass@ip'
      and $$monitor{ControlAddress} ne 'user:port@ip'
  ) {
    my $address = $$monitor{ControlAddress};
    Debug("Using ControlAddress for credentials: $address");
    if ($address !~ /^\w+:\/\//) {
      # Has no scheme at the beginning, so won't parse as a URI
      $address = 'http://'.$address;
    }
    # To support older installs which likely have non-url encoded passwords we use a dumber regexp than URI uses
    if ($address =~ /^(?<PROTOCOL>(https?|rtsp):\/\/)?(?<USERNAME>[^:@]+)?:?(?<PASSWORD>[^\/@]+)?@(?<ADDRESS>[^:\/]+)/) {
      $address = $+{PROTOCOL}.($+{USERNAME} ? join(':', $+{USERNAME}, URI::Escape::uri_escape($+{PASSWORD})).'@' : '').$+{ADDRESS};
    }
    my $uri = URI->new($address);
    $uri = URI->new('http://'.$address) if ref($uri) eq 'URI::_foreign';
    $$self{host} = $uri->host();

    if ($uri->userinfo()) {
      @$self{'username','password'} = $uri->userinfo() =~ /^(.*):(.*)$/;
      $$self{password} = URI::Escape::uri_unescape($$self{password});
    }
    # Check if it is a host and port or just a host
    if ( $$self{host} =~ /([^:]+):(.+)/ ) {
      $$self{host} = $1;
      $$self{port} = $2 ? $2 : $$self{port};
    } elsif ($uri->scheme() eq 'http') {
      $$self{port} = 80;
      $uri->port($$self{port});
    } elsif ($uri->scheme() eq 'https') {
      $$self{port} = 443;
      $uri->port($$self{port});
    }
    $$self{uri} = $uri;
    $$self{BaseURL} = $uri->canonical();
    while (substr($$self{BaseURL}, -1, 1) eq '/') { chop($$self{BaseURL}); }
    $self->{ua}->credentials($uri->host_port(), $$self{realm}, $$self{username}, $$self{password});
    Debug("Have base url $$self{BaseURL} with credentials ".$uri->host_port().join(',', @$self{'realm', 'username', 'password'}));
    return 1;
  }
  return 0;
}

sub parse_Path {
  my $self = shift;
  my $monitor = $self->{Monitor};
  Debug("Using Path for credentials: $$monitor{Path}");
  my $uri = URI->new($$monitor{Path});
  return 0 if !$uri->has_recognized_scheme;

  if ($uri->scheme() eq 'rtsp') {
    $uri->scheme('http');
    $uri->port(80);
  }
  $uri->path_query('');
  if ( $uri->userinfo()) {
    @$self{'username', 'password'} = $uri->userinfo() =~ /^(.*):(.*)$/;
  } else {
    $$self{username} = $$monitor{User};
    $$self{password} = $$monitor{Pass};
    # This will put it into canonical
    $uri->userinfo($$self{username}.':'.$$self{password});
  }
  $$self{address} = $uri->host_port();
  $$self{host} = $uri->host();
  $$self{uri} = $uri;
  $$self{port} = $uri->port();
  $$self{BaseURL} = $uri->canonical();
  while (substr($$self{BaseURL}, -1, 1) eq '/') { chop($$self{BaseURL}); }
  $$self{ua}->credentials($uri->host_port(), @$self{'realm', 'username', 'password'});
  Debug("Have base url $$self{BaseURL} with credentials ".$uri->host_port().join(',', @$self{'realm', 'username', 'password'}));
  return 1;
}

sub guess_credentials {
  my $self = shift;

  if (!($self->parse_ControlAddress() or $self->parse_Path())) {
    Debug('Unable to guess credentials');
    return 0;
  }
  return 1;
}

sub get_realm {
  my $self = shift;
  my $url = shift;
  my $response = $self->get($url);
  return 1 if $response->is_success();

  if ($response->status_line() eq '401 Unauthorized' and defined $$self{username}) {
    my $headers = $response->headers();
    foreach my $k ( keys %$headers ) {
      Debug("Initial Header $k => $$headers{$k}");
    }
    if ( $$headers{'www-authenticate'} ) {
      foreach my $auth_header ( ref $$headers{'www-authenticate'} eq 'ARRAY' ? @{$$headers{'www-authenticate'}} : ($$headers{'www-authenticate'})) {
        my ( $auth, $tokens ) = $auth_header =~ /^(\w+)\s+(.*)$/;
        my %tokens = map { /(\w+)="?([^"]+)"?/i } split(', ', $tokens );
        if ( $tokens{realm} ) {
          if ((!$$self{realm}) or ($$self{realm} ne $tokens{realm})) {
            $$self{realm} = $tokens{realm};
            Debug("Changing REALM to $$self{realm}, $$self{host}:$$self{port}, $$self{realm}, $$self{username}, $$self{password}");
            $self->{ua}->credentials("$$self{host}:$$self{port}", $$self{realm}, $$self{username}, $$self{password});
            $response = $self->get($url);
            if ( !$response->is_success() ) {
              Debug('Authentication still failed after updating REALM' . $response->status_line);
              $headers = $response->headers();
              foreach my $k ( keys %$headers ) {
                Debug("Initial Header $k => $$headers{$k}\n");
              }  # end foreach
            } else {
              return 1;
            }
          } else {
            Error('Authentication failed, not a REALM problem');
          }
        } else {
          Debug('Failed to match realm in tokens');
        } # end if
      } # end foreach auth header
    } else {
      Debug('No headers line');
    } # end if headers
  } # end if not authen
  return undef;
} # end sub get_realm

sub ping {
  my $self = shift;
  my $ip = @_ ? shift : $$self{host};
  if (!$ip) {
    Warning("No ip to ping. Please either pass ip or populate self{host}");
    return undef;
  }

  require Net::Ping;
  Debug("Pinging $ip");

  my $p = Net::Ping->new();
  my $rv = $p->ping($ip);
  $p->close();
  Debug("Pinging $ip $rv");
  return $rv;
}

1;
__END__

=head1 NAME

ZoneMinder::Control - Parent class defining Control API

=head1 SYNOPSIS

use ZoneMinder::Control;

This should be used as the parent class for packages implementing control
apis for various cameras.

=head1 DESCRIPTION



=head2 EXPORT

None by default.


=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
