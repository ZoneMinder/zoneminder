# ==========================================================================
#
# ZoneMinder Pelco D over HTTP via Serial_HEX API Control Protocol Module
# Copyright (C) 2001-2008  Philip Coombes
# Copyright (C) 2025 V.Nikolaev
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
# This module contains the implementation of the Pelco-D over SERIAL_HEX API camera control
# protocol used in ACTi ACD2100
#
# Example:
# 
# Control Device: Channel:CameraID:Realm ( 1:1:Http Server )
# Control Address: user:pass@ip (admin:123456@192.168.1.99)
#
# CameraID is for the Pelco-D Cammera Address after the SYNC. Default value 1
# URL command for STOP move
# http://192.168.1.99/cgi-bin/cmd/encoder?CHANNEL=1&SERIAL_HEX=FF010000000001
# URL command for pan right 0x02 with speed 0x06
# http://192.168.1.99/cgi-bin/cmd/encoder?CHANNEL=1&SERIAL_HEX=FF010002060009

package ZoneMinder::Control::Serial_HEX_PelcoD;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Pelco-D over HTTP_SERIAL_HEX Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );
use URI;
use constant SYNC => 0xff;

our $uri;
my ($channel, $camera, $realm);

sub open {
  my $self = shift;

  $self->loadMonitor();

  if ($self->{Monitor}->{ControlAddress} and ($self->{Monitor}->{ControlAddress} ne 'user:pass@ip')) {
    Debug("Getting connection details from Control Address " . $self->{Monitor}->{ControlAddress});
    if ( $self->{Monitor}->{ControlAddress} !~ /^\w+:\/\// ) {
      # Has no scheme at the beginning, so won't parse as a URI
      $self->{Monitor}->{ControlAddress} = 'http://'.$self->{Monitor}->{ControlAddress};
    }
    $uri = URI->new($self->{Monitor}->{ControlAddress});
  } elsif ($self->{Monitor}->{Path}) {
    Debug("Getting connection details from Path " . $self->{Monitor}->{Path});
    $uri = URI->new($self->{Monitor}->{Path});
    $uri->scheme('http');
    $uri->port(80);
    $uri->path('');
  }

  use LWP::UserAgent;
  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->cookie_jar( {} );
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);
  $self->{state} = 'closed';

  my ( $username, $password, $host ) = ( $uri->authority() =~ /^([^:]+):([^@]*)@(.+)$/ );
  Debug("Have username: $username password: $password host: $host from authority:" . $uri->authority());
  
  $uri->userinfo(undef);

  #  my $realm = $self->{Monitor}->{ControlDevice};
  ($channel, $camera, $realm) = split /:/, $self->{Monitor}->{ControlDevice};

  $self->{ua}->credentials($uri->host_port(), $realm, $username, $password);
  my $url = 'cgi-bin/cmd/encoder?PTZ_VENDOR';

  # test auth
  my $res = $self->{ua}->get($uri->canonical().$url);

  if ($res->is_success) {
    if ($res->content() ne "PTZ_VENDOR='DYNACOLOR,UNKNWN'\n") {
      Warning('Response suggests that camera doesn\'t support PTZ. Content:('.$res->content().')');
    }
    $self->{state} = 'open';
    return;
  }
  if ($res->status_line() eq '404 Not Found') {
    #older style
    $url = 'cgi-bin/cmd/encoder?';
    $res = $self->{ua}->get($uri->canonical().$url);
    Debug("Result from getting ".$uri->canonical().$url . ':' . $res->status_line());
  }

  if ($res->status_line() eq '401 Unauthorized') {
    my $headers = $res->headers();
    foreach my $k ( keys %$headers ) {
      Debug("Initial Header $k => $$headers{$k}");
    }

    if ( $$headers{'www-authenticate'} ) {
      foreach my $auth_header ( ref $$headers{'www-authenticate'} eq 'ARRAY' ? @{$$headers{'www-authenticate'}} : ($$headers{'www-authenticate'})) {
        my ( $auth, $tokens ) = $auth_header =~ /^(\w+)\s+(.*)$/;
        if ( $tokens =~ /\w+="([^\"]+)"/i ) {
          if ( $realm ne $1 ) {
            $realm = $1;
            $self->{ua}->credentials($uri->host_port(), $realm, $username, $password);
            $res = $self->{ua}->get($uri->canonical().$url);
            if ( $res->is_success() ) {
              Info("Auth succeeded after setting realm to $realm.  You can set this value in the Control Device field to speed up connections and remove these log entries.");
              $self->{state} = 'open';
              return;
            }
            Error('Authentication still failed after updating REALM status: '.$res->status_line);
          } else {
            Error('Authentication failed, not a REALM problem');
          }
        } else {
          Error('Failed to match realm in tokens');
        } # end if
      } # end foreach auth header
    } else {
      Debug('No headers line');
    } # end if headers
  } else {
    Debug('Failed to open '.$uri->canonical().$url.' status: '.$res->status_line());
  } # end if $res->status_line() eq '401 Unauthorized'
} # end sub open

sub printMsg
{
  my $msg = shift;
  my $msg_len = int(@$msg);
  my $msg_str;
  for ( my $i = 0; $i < $msg_len; $i++ ) {
    $msg_str .= sprintf( "%02X", $msg->[$i] );
    }
  return $msg_str;
}

sub sendCmd {
  my $self = shift;
  my $cmd = shift;
  my $checksum = 0x00;

  for ( my $i = 1; $i < int(@$cmd); $i++ )
  {
    $checksum += $cmd->[$i];
    $checksum &= 0xff;
  }
  push( @$cmd, $checksum );

# conver to hex
  my $msg = printMsg($cmd);

# Url adr
  my $adr = "cgi-bin/cmd/encoder?CHANNEL=".$channel."&SERIAL_HEX=";
  my $tx_msg = $adr.$msg;

#  Debug ($tx_msg);

  my $url = $uri->canonical().$tx_msg;
  my $res = $self->{ua}->get($url);

  if ( $res->is_success ) {
    Debug('sndCmd command: '.$url.' content: '.$res->content);
    return !undef;
  }

  Error("Error cmd $url failed: '".$res->status_line()."'");

  return undef;
}

sub autoStop
{
    my $self = shift;
    my $autostop = shift;
    if( $autostop )
    {
        Debug( "Auto Stop" );
        usleep( $autostop );
        my @cmd = ( SYNC, $camera, 0x00 , 0x00, 0x00, 0x00 );
        $self->sendCmd( \@cmd );
    }
}

sub stop
{
    my $self = shift;
    Debug( "Stop" );
    my @cmd = ( SYNC, $camera, 0x00, 0x00, 0x00, 0x00 );
    $self->sendCmd( \@cmd );
}

sub reboot {
  my $self = shift;
  Debug('Camera Reboot');
  my @cmd = ( SYNC, $camera, 0xf0, 0x83, 0x00, 0x01 );
  $self->sendCmd( \@cmd );
}

sub moveConUp {
  my $self = shift;
  my $params = shift;
  my $panspeed = 0; # purely moving vertically
  my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
  Debug('Move Up');
  my @cmd = ( SYNC, $camera, 0x00, 0x08, $panspeed, $tiltspeed );
  $self->sendCmd( \@cmd );
  $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConDown {
  my $self = shift;
  my $params = shift;
  my $panspeed = 0; # purely moving vertically
  my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
  Debug('Move Down');
  my @cmd = ( SYNC, $camera, 0x00, 0x10, $panspeed, $tiltspeed );
  $self->sendCmd( \@cmd );
  $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConLeft {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed' );
  my $tiltspeed = 0; # purely moving horizontally
  Debug('Move Left');
  my @cmd = ( SYNC, $camera, 0x00, 0x04, $panspeed, $tiltspeed );
  $self->sendCmd( \@cmd );
  $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConRight {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed' );
  my $tiltspeed = 0; # purely moving horizontally
  Debug('Move Right');
  my @cmd = ( SYNC, $camera, 0x00, 0x02, $panspeed, $tiltspeed );
  $self->sendCmd( \@cmd);
  $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConUpRight {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed' );
  my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
  Debug('Move Up/Right');
  my @cmd = ( SYNC, $camera, 0x00, 0x0a, $panspeed, $tiltspeed );
  $self->sendCmd( \@cmd );
  $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConUpLeft {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed' );
  my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
  Debug('Move Up/Left');
  my @cmd = ( SYNC, $camera, 0x00, 0x0c, $panspeed, $tiltspeed );
  $self->sendCmd( \@cmd );
  $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConDownRight {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed' );
  my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
  Debug('Move Down/Right');
  my @cmd = ( SYNC, $camera, 0x00, 0x12, $panspeed, $tiltspeed );
  $self->sendCmd( \@cmd );
  $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub moveConDownLeft {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed' );
  my $tiltspeed = $self->getParam( $params, 'tiltspeed' );
  Debug('Move Down/Left');
  my @cmd = ( SYNC, $camera, 0x00, 0x14, $panspeed, $tiltspeed );
  $self->sendCmd( \@cmd );
  $self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}

sub zoomConTele {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam( $params, 'speed' );
  Debug('Zoom ConTele');
  #Zoom speed 00-slow 01-low 02-med 03-hi Others=low
  my @cmd = ( SYNC, $camera, 0x00, 0x25, 0x00, $speed );
  $self->sendCmd( \@cmd );
  #iris
  my @cmd2 = ( SYNC, $camera, 0x00, 0x20, 0x00, 0x00 );
  $self->sendCmd( \@cmd2 );
}

sub zoomConWide {
  my $self = shift;
  my $params = shift;
  my $speed = $self->getParam( $params, 'speed' );
  Debug('Zoom ConWide');
  #Zoom speed 00-slow 01-low 02-med 03-hi Others=low
  my @cmd = ( SYNC, $camera, 0x00, 0x25, 0x00, $speed );
  $self->sendCmd( \@cmd );
  #zoom wide
  my @cmd2 = ( SYNC, $camera, 0x00, 0x40, 0x00, 0x00 );
  $self->sendCmd( \@cmd2 );
}

sub zoomStop {
  my $self = shift;
  my $params = shift;
  my $speed = 0;
  Debug('Zoom Stop');
  my @cmd = ( SYNC, $camera, 0x00, 0x00, 0x00, 0x00 );
  $self->sendCmd( \@cmd );
}

sub moveStop {
  my $self = shift;
  Debug( "Move Stop" );
  $self->stop();
}

sub focusConNear {
  my $self = shift;
  my $params = shift;
  Debug('Focus Near');
  #focus speed low/med
  my @cmd = ( SYNC, $camera, 0x00, 0x27, 0x00, 0x01 );
  $self->sendCmd( \@cmd );
  #Focus near
  my @cmd2 = ( SYNC, $camera, 0x01, 0x00, 0x00, 0x00 );
  $self->sendCmd( \@cmd2 );
}

sub focusConFar {
  my $self = shift;
  my $params = shift;
  Debug('Focus Far');
   #focus speed low/med
  my @cmd = ( SYNC, $camera, 0x00, 0x27, 0x00, 0x01 );
  $self->sendCmd( \@cmd );
  my @cmd2 = ( SYNC, $camera, 0x00, 0x80, 0x00, 0x00 );
  $self->sendCmd( \@cmd2 );
}

sub focusAuto {
  my $self = shift;
  Debug('Focus Auto');
  my @cmd = ( SYNC, $camera, 0x00, 0x2B, 0x00, 0x00 );
  $self->sendCmd( \@cmd );
}

sub focusMan {
  my $self = shift;
  Debug('Focus Manual');
  my @cmd = ( SYNC, $camera, 0x00, 0x2B, 0x00, 0x01 );
  $self->sendCmd( \@cmd );
}

sub focusStop {
  my $self = shift;
  Debug('Focus Manual');
  my @cmd = ( SYNC, $camera, 0x00, 0x00, 0x00, 0x00 );
  $self->sendCmd( \@cmd );
}

sub irisRelOpen {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'step');
  Debug('Iris Open');
  #Stop
  my @cmd = ( SYNC, $camera, 0x00, 0x00, 0x00, 0x00 );
  $self->sendCmd( \@cmd );
  #iris
  my @cmd2 = ( SYNC, $camera, 0x02, 0x00, 0x00, 0x00 );
  $self->sendCmd( \@cmd2 );
}

sub irisRelClose {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'step');
  Debug('Iris Close');
  #Stop
  my @cmd = ( SYNC, $camera, 0x00, 0x00, 0x00, 0x00 );
  $self->sendCmd( \@cmd );
  #iris
  my @cmd2 = ( SYNC, $camera, 0x04, 0x00, 0x00, 0x00 );
  $self->sendCmd( \@cmd2 );
}

sub irisAuto {
  my $self = shift;
  Debug('Iris Auto');
  my @cmd = ( SYNC, $camera, 0x00, 0x2D, 0x00, 0x01 );
  $self->sendCmd( \@cmd );
}

sub irisMan {
  my $self = shift;
  Debug('Iris Manual');
  my @cmd = ( SYNC, $camera, 0x00, 0x2D, 0x00, 0x00 );
  $self->sendCmd( \@cmd );
}

sub presetClear {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Clear Preset $preset");
  my @cmd = ( SYNC, $camera, 0x00, 0x05, 0x00, $preset );
  $self->sendCmd( \@cmd );
}

sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Set Preset $preset");
  my @cmd = ( SYNC, $camera, 0x00, 0x03, 0x00, $preset );
  $self->sendCmd( \@cmd );
}

sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Goto Preset $preset");
  my @cmd = ( SYNC, $camera, 0x00, 0x07, 0x00, $preset );
  $self->sendCmd( \@cmd );
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Control::Serial_HEX_PelcoD - Zoneminder control for SERIAL_HEX Cameras using the Pelco-D

=head1 SYNOPSIS

  use ZoneMinder::Control::Serial_HEX_PelcoD ; place this in /usr/share/perl5/ZoneMinder/Control

=head1 DESCRIPTION

This module is an implementation of the SERIAL_HEX of the Pelco-D 

=head2 EXPORT

None by default.



=head1 SEE ALSO

AXIS VAPIX Library Documentation; e.g.:
https://support.pelco.com 

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes
Copyright (C) 2025  V.Nikolaev

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
