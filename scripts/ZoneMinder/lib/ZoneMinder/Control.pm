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
require IO::Socket::SSL;

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

# Seconds. The camera answers in milliseconds when it is there at all, so this
# only ever bounds the case where it is not.
use constant HTTP_TIMEOUT => 10;

$table = 'Controls';
$serial = $primary_key = 'Id';
%fields = map { $_ => $_ } qw(
  Id
  Name
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
      Fatal('Monitor id '.$self->{MonitorId}.' not found');
    }
  }
  # Monitor::Control() hands us a monitor already, so the conversion cannot
  # live in the lookup above or the timeout stays in seconds and the modules
  # usleep() it as microseconds. executeCommand calls this for every command,
  # so convert once.
  if ( !$self->{AutoStopTimeoutConverted} ) {
    $self->{AutoStopTimeoutConverted} = 1;
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

# Joins $$self{BaseURL} (which ends in a slash after canonical()) and a path with exactly one slash
sub fullUrl {
  my ($self, $url) = @_;
  return $url if !$$self{BaseURL};
  my $base = $$self{BaseURL};
  $base =~ s{/+$}{};
  $url =~ s{^/+}{};
  return $base.'/'.$url;
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
  $url = $self->fullUrl($url);
  my $response = $self->{ua}->get($url);

  if (!$response->is_success && !$self->{ssl_verify_disabled} && $response->status_line =~ /SSL|certificate|verify/i) {
    Warning('SSL certificate verification failed (' . $response->status_line . '), retrying without verification');
    $self->{ua}->ssl_opts(
      verify_hostname => 0,
      SSL_verify_mode => IO::Socket::SSL::SSL_VERIFY_NONE(),
      SSL_hostname => '',
    );
    $self->{ssl_verify_disabled} = 1;
    $response = $self->{ua}->get($url);
  }

  Debug('Response from '.urlForLog($url).': '. $response->status_line . ' ' . $response->content);
  return $response;
}

sub put {
  my $self = shift;
  my $url = shift;
  if (!$url) {
    Error('No url specified in put');
    return;
  }
  $url = $self->fullUrl($url);
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

  if (!$res->is_success && !$self->{ssl_verify_disabled} && $res->status_line =~ /SSL|certificate|verify/i) {
    Warning('SSL certificate verification failed (' . $res->status_line . '), retrying without verification');
    $self->{ua}->ssl_opts(
      verify_hostname => 0,
      SSL_verify_mode => IO::Socket::SSL::SSL_VERIFY_NONE(),
      SSL_hostname => '',
    );
    $self->{ssl_verify_disabled} = 1;
    $res = $self->{ua}->request($req);
  }

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
  $url = $self->fullUrl($url);
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
    foreach my $key (keys %$headers) {
      $req->header($key=>$$headers{$key});
    }
  }

  my $res = $self->{ua}->request($req);

  if (!$res->is_success && !$self->{ssl_verify_disabled} && $res->status_line =~ /SSL|certificate|verify/i) {
    Warning('SSL certificate verification failed (' . $res->status_line . '), retrying without verification');
    $self->{ua}->ssl_opts(
      verify_hostname => 0,
      SSL_verify_mode => IO::Socket::SSL::SSL_VERIFY_NONE(),
      SSL_hostname => '',
    );
    $self->{ssl_verify_disabled} = 1;
    $res = $self->{ua}->request($req);
  }

  if (!$res->is_success) {
    Error($res->status_line);
  } # end unless res->is_success
  Debug('Response for '.urlForLog($url).': '. $res->status_line . ' ' . $res->content);
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

# User info for a base url. LWP sends it as Basic auth, taking it up to the
# first @ and splitting it on the first :, so both halves are %-encoded. A :
# in the user stays raw: LWP refuses it encoded, and the header is the same.
sub _userinfo {
  my ($user, $pass) = @_;
  return join(':', (defined $user ? URI::Escape::uri_escape($user, '^A-Za-z0-9\-_.~:') : ()),
    (defined $pass ? URI::Escape::uri_escape($pass, '^A-Za-z0-9\-_.~') : ()));
}

# What goes on the wire is UTF-8, as zmc sends the monitor User and Pass; LWP
# dies on characters above \x{ff}.
sub _utf8 {
  my $s = shift;
  utf8::encode($s) if defined $s;
  return $s;
}

# Sets username and password for the drivers and keeps the UTF-8 sent for get_realm
sub _set_credentials {
  my ($self, $user, $pass, @wire) = @_;
  @$self{'username', 'password'} = ($user, $pass);
  $$self{wire_credentials} = [$user, $pass, @wire];
}

# The UTF-8 the helpers sent, while username and password are still theirs
sub _wire_credentials {
  my $self = shift;
  my ($user, $pass, @wire) = @{$$self{wire_credentials} // []};
  my $same = sub { (defined $_[0] ? "=$_[0]" : '') eq (defined $_[1] ? "=$_[1]" : '') };
  return @wire if @wire and $same->($user, $$self{username}) and $same->($pass, $$self{password});
  return @$self{'username', 'password'};
}

sub parse_ControlAddress {
  my $self = shift;
  my $monitor = $self->{Monitor};
  my $address = $$monitor{ControlAddress} // '';
  $address =~ s/^\s+|\s+$//g;
  # Extract the username/password host/port from ControlAddress
  if ($address
      and $address ne 'user:pass@ip'
      and $address ne 'user:port@ip'
  ) {
    Debug('Using ControlAddress for credentials: '.urlForLog($address));
    my $scheme = ($address =~ s/^(\w+):\/\///) ? $1 : 'http';
    # Passwords may not be url-encoded: the user info ends at the first @ before a host[:port], else the
    # last @. An @ after host[:port]/ or host? is in the path; host:port? may begin a password (admin:1234?5@cam).
    # host#x@y is read as user info, like a#b@cam; such a fragment has to be %-encoded.
    my $host_re = qr{(?:[A-Za-z0-9._\x{80}-\x{10ffff}-]+|\[[0-9A-Za-z:.%_~-]+\])};
    my ($userinfo, $end);
    if ($address !~ m{^$host_re(?:(?::\d*)?/|\?)}) {
      while ($address =~ /\@/g) {
        $end = $-[0];
        last if $address =~ m{\G$host_re(?::\d*)?[/?#]};
      }
    }
    ($userinfo, $address) = (substr($address, 0, $end), substr($address, $end + 1)) if defined $end;
    my $uri = URI->new($scheme.'://'.$address);
    $uri = URI->new('http://'.$address) if ref($uri) eq 'URI::_foreign';
    $$self{host} = $uri->host();

    if (defined $userinfo and $userinfo ne '' and $userinfo ne ':') {
      my ($user, $pass) = split(/:/, $userinfo, 2);
      $pass //= '';
      # A user name from the database (a character string) goes to drivers as UTF-8
      my $driver_user = $user;
      utf8::encode($driver_user) if utf8::is_utf8($driver_user);
      my @wire = map { URI::Escape::uri_unescape(_utf8($_)) } $user, $pass;
      $self->_set_credentials(URI::Escape::uri_unescape($driver_user), URI::Escape::uri_unescape($pass), @wire);
      $uri->userinfo(_userinfo(@wire));
    }
    # URI gives the explicit port if present, otherwise the scheme default (http 80, https 443, rtsp 554)
    $$self{port} = $uri->port();
    $$self{address} = $uri->host_port();
    $$self{uri} = $uri;
    $$self{BaseURL} = $uri->canonical();
    $self->{ua}->credentials($uri->host_port(), $$self{realm}, $self->_wire_credentials());
    Debug('Have base url '.urlForLog($$self{BaseURL}).' with credentials for '.$uri->host_port().' realm '.($$self{realm} // '').' user '.($$self{username} // ''));
    return 1;
  }
  return 0;
}

sub parse_Path {
  my $self = shift;
  my $monitor = $self->{Monitor};
  Debug('Using Path for credentials: '.urlForLog($$monitor{Path}));
  my $uri = URI->new($$monitor{Path});
  return 0 if !$uri->has_recognized_scheme;

  if ($uri->scheme() eq 'rtsp') {
    $uri->scheme('http');
    $uri->port(80);
  }
  $uri->path_query('');
  if ( $uri->userinfo()) {
    # The userinfo of a URL is url-encoded (an @ in a password has to be %40),
    # and the user may have no password at all.
    my ($user, $pass) = map { URI::Escape::uri_unescape($_) } split(/:/, $uri->userinfo(), 2);
    # URI %-encodes a byte string byte by byte, so the wire takes the user info from the UTF-8 Path
    my @wire = map { URI::Escape::uri_unescape($_) } split(/:/, URI->new(_utf8($$monitor{Path}))->userinfo(), 2);
    $uri->userinfo(_userinfo(@wire));
    $self->_set_credentials($user, $pass // '', $wire[0], $wire[1] // '');
  } else {
    my ($user, $pass) = @$monitor{'User', 'Pass'};
    $self->_set_credentials($user, $pass, _utf8($user), _utf8($pass));
    # This will put it into canonical, unless the camera has no login
    $uri->userinfo(_userinfo(_utf8($user), _utf8($pass // ''))) if defined($user) and length($user);
  }
  $$self{address} = $uri->host_port();
  $$self{host} = $uri->host();
  $$self{uri} = $uri;
  $$self{port} = $uri->port();
  $$self{BaseURL} = $uri->canonical();
  $$self{ua}->credentials($uri->host_port(), $$self{realm}, $self->_wire_credentials());
  Debug('Have base url '.urlForLog($$self{BaseURL}).' with credentials for '.$uri->host_port().' realm '.($$self{realm} // '').' user '.($$self{username} // ''));
  return 1;
}

# Whether a keepAlive is due, and if so record that one is being sent.
#
# zmcontrol used to ping only when its select() timed out, which meant a
# monitor with steady command traffic never pinged at all: select returns a
# ready descriptor every time, the idle branch is never reached, and the
# camera's session expires while the monitor is at its busiest. Cameras that
# expire a session on a timer refresh it on the keepAlive alone, not on
# ordinary requests, so the busiest monitors were the ones that lost their
# session. Timing it from the last ping instead makes the interval hold
# regardless of how much else is going on.
#
# $now is injectable so the interval can be tested without sleeping.
sub keepAliveDue {
  my ($self, $interval, $now) = @_;
  $now = time() if !defined $now;
  # First call sets the baseline rather than firing immediately: the session
  # was just established by open().
  $self->{last_keepalive} = $now if !defined $self->{last_keepalive};
  # An ntp step backwards would otherwise hold this off until real time caught
  # up again - minutes with no ping, which is the failure this exists to stop.
  $self->{last_keepalive} = $now if $now < $self->{last_keepalive};
  return 0 if $now - $self->{last_keepalive} < $interval;
  $self->{last_keepalive} = $now;
  return 1;
}

# Seconds the daemon may wait in select() before the next keepAlive is due.
# Waiting the whole interval after every command lets the gap between pings
# reach twice the interval: a command just before a ping is due restarts a full
# wait, and the ping then comes a whole interval later.
sub keepAliveWait {
  my ($self, $interval, $now) = @_;
  $now = time() if !defined $now;
  return $interval if !defined $self->{last_keepalive} or $now < $self->{last_keepalive};
  my $left = $interval - ($now - $self->{last_keepalive});
  return $left > 1 ? $left : 1;
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

  if ($response->code() == 401 and defined $$self{username}) {
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
            Debug("Changing REALM to $$self{realm}, $$self{address} or $$self{host}:$$self{port}, user $$self{username}");
            $self->{ua}->credentials($$self{address}?$$self{address}:"$$self{host}:$$self{port}", $$self{realm}, $self->_wire_credentials());
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

# Resolve the camera host/ip from the monitor without opening a connection.
# Returns the cached $self->{host} if open()/guess_credentials() already set it,
# otherwise parses it out of the monitor's ControlAddress/Path using the shared
# guess_credentials() (which only needs a UserAgent, no network I/O). This lets
# callers such as ping() work before open() has been called.
sub host {
  my $self = shift;
  $$self{host} = shift if @_;
  return $$self{host} if $$self{host};

  if (!$self->{ua}) {
    require LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new(timeout => HTTP_TIMEOUT);
  }
  $self->guess_credentials();
  return $$self{host};
}

sub ping {
  my $self = shift;
  my $ip = @_ ? shift : $self->host();
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

Licensed under the GNU General Public License v2 or later; see the COPYING
file distributed with ZoneMinder for the full text.


=cut
