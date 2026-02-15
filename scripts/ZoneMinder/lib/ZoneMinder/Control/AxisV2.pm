# ==========================================================================
#
# ZoneMinder Axis version 2 API Control Protocol Module
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
# This module contains the implementation of the Axis V2 API camera control
# protocol
#
package ZoneMinder::Control::AxisV2;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Axis V2 Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);

use Time::HiRes qw( usleep );

use LWP::UserAgent;
use HTTP::Cookies;

sub open {
  my $self = shift;
  $self->loadMonitor();

  $self->{ua} = LWP::UserAgent->new();
  $self->{ua}->cookie_jar( {} );
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);
  $self->{state} = 'closed';

  $$self{realm} = defined($self->{Monitor}->{ControlDevice}) ? $self->{Monitor}->{ControlDevice} : '';

  if (!$self->guess_credentials()) {
    Error('Failed to parse credentials from ControlAddress or Path');
    return undef;
  }

  # Try modern param.cgi endpoint first
  my $url = '/axis-cgi/param.cgi?action=list&group=Properties.PTZ.PTZ';
  if ($self->get_realm($url)) {
    my $res = $self->get($url);
    if ($res->is_success and $res->content() !~ /Properties\.PTZ\.PTZ=yes/) {
      Warning('Response suggests that camera doesn\'t support PTZ. Content:('.$res->content().')');
    }
    $self->{state} = 'open';
    return !undef;
  }

  # Fall back to older ptz.cgi for legacy cameras
  if ($self->get_realm('/axis-cgi/com/ptz.cgi')) {
    $self->{state} = 'open';
    return !undef;
  }

  return undef;
} # end sub open

sub sendCmd {
  my $self = shift;
  my $cmd = shift;

  return $self->get($cmd);
}

sub cameraReset {
  my $self = shift;
  Debug('Camera Reset');
  my $cmd = '/axis-cgi/admin/restart.cgi';
  $self->sendCmd($cmd);
}

sub moveConUp {
  my $self = shift;
  my $params = shift;
  my $panspeed = 0; # purely moving vertically
  my $tiltspeed = $self->getParam( $params, 'tiltspeed', 30 );
  Debug('Move Up');
  my $cmd = "/axis-cgi/com/ptz.cgi?continuouspantiltmove=$panspeed,$tiltspeed";
  $self->sendCmd($cmd);
}

sub moveConDown {
  my $self = shift;
  my $params = shift;
  my $panspeed = 0; # purely moving vertically
  my $tiltspeed = $self->getParam( $params, 'tiltspeed', 30 ) * -1 ;
  Debug('Move Down');
  my $cmd = "/axis-cgi/com/ptz.cgi?continuouspantiltmove=$panspeed,$tiltspeed";
  $self->sendCmd($cmd);
}

sub moveConLeft {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed', 30 ) * -1 ;
  my $tiltspeed = 0; # purely moving horizontally
  Debug('Move Left');
  my $cmd = "/axis-cgi/com/ptz.cgi?continuouspantiltmove=$panspeed,$tiltspeed";
  $self->sendCmd($cmd);
}

sub moveConRight {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed', 30 );
  my $tiltspeed = 0; # purely moving horizontally
  Debug('Move Right');
  my $cmd = "/axis-cgi/com/ptz.cgi?continuouspantiltmove=$panspeed,$tiltspeed";
  $self->sendCmd($cmd);
}

sub moveConUpRight {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed', 30 );
  my $tiltspeed = $self->getParam( $params, 'tiltspeed', 30 );
  Debug('Move Up/Right');
  my $cmd = "/axis-cgi/com/ptz.cgi?continuouspantiltmove=$panspeed,$tiltspeed";
  $self->sendCmd($cmd);
}

sub moveConUpLeft {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed', 30 ) * -1;
  my $tiltspeed = $self->getParam( $params, 'tiltspeed', 30 );
  Debug('Move Up/Left');
  my $cmd = "/axis-cgi/com/ptz.cgi?continuouspantiltmove=$panspeed,$tiltspeed";
  $self->sendCmd($cmd);
}

sub moveConDownRight {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed', 30 );
  my $tiltspeed = $self->getParam( $params, 'tiltspeed', 30 ) * -1;
  Debug('Move Down/Right');
  my $cmd = "/axis-cgi/com/ptz.cgi?continuouspantiltmove=$panspeed,$tiltspeed";
  $self->sendCmd( $cmd );
}

sub moveConDownLeft {
  my $self = shift;
  my $params = shift;
  my $panspeed = $self->getParam( $params, 'panspeed', 30 ) * -1;
  my $tiltspeed = $self->getParam( $params, 'tiltspeed', 30 ) * -1;
  Debug('Move Down/Left');
  my $cmd = "/axis-cgi/com/ptz.cgi?continuouspantiltmove=$panspeed,$tiltspeed";
  $self->sendCmd($cmd);
}

sub moveMap {
  my $self = shift;
  my $params = shift;
  my $xcoord = $self->getParam($params, 'xcoord');
  my $ycoord = $self->getParam($params, 'ycoord');
  Debug("Move Map to $xcoord,$ycoord");
  my $cmd = "/axis-cgi/com/ptz.cgi?center=$xcoord,$ycoord&imagewidth=".$self->{Monitor}->{Width}.'&imageheight='.$self->{Monitor}->{Height};
  $self->sendCmd($cmd);
}

sub moveRelUp {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'tiltstep');
  Debug("Step Up $step");
  my $cmd = '/axis-cgi/com/ptz.cgi?rtilt='.$step;
  $self->sendCmd($cmd);
}

sub moveRelDown {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'tiltstep');
  Debug("Step Down $step");
  my $cmd = '/axis-cgi/com/ptz.cgi?rtilt=-'.$step;
  $self->sendCmd($cmd);
}

sub moveRelLeft {
  my $self = shift;
  my $params = shift;
  my $step = abs($self->getParam($params, 'panstep'));
  Debug("Step Left $step");
  my $cmd = '/axis-cgi/com/ptz.cgi?rpan=-'.$step;
  $self->sendCmd($cmd);
}

sub moveRelRight {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'panstep');
  Debug("Step Right $step");
  my $cmd = '/axis-cgi/com/ptz.cgi?rpan='.$step;
  $self->sendCmd($cmd);
}

sub moveRelUpRight {
  my $self = shift;
  my $params = shift;
  my $panstep = $self->getParam($params, 'panstep');
  my $tiltstep = $self->getParam($params, 'tiltstep');
  Debug("Step Up/Right $tiltstep/$panstep");
  my $cmd = "/axis-cgi/com/ptz.cgi?rpan=$panstep&rtilt=$tiltstep";
  $self->sendCmd($cmd);
}

sub moveRelUpLeft {
  my $self = shift;
  my $params = shift;
  my $panstep = abs($self->getParam($params, 'panstep'));
  my $tiltstep = abs($self->getParam($params, 'tiltstep'));
  Debug("Step Up/Left $tiltstep/$panstep");
  my $cmd = "/axis-cgi/com/ptz.cgi?rpan=-$panstep&rtilt=$tiltstep";
  $self->sendCmd($cmd);
}

sub moveRelDownRight {
  my $self = shift;
  my $params = shift;
  my $panstep = $self->getParam($params, 'panstep');
  my $tiltstep = $self->getParam($params, 'tiltstep');
  Debug("Step Down/Right $tiltstep/$panstep");
  my $cmd = "/axis-cgi/com/ptz.cgi?rpan=$panstep&rtilt=-$tiltstep";
  $self->sendCmd($cmd);
}

sub moveRelDownLeft {
  my $self = shift;
  my $params = shift;
  my $panstep = $self->getParam($params, 'panstep');
  my $tiltstep = $self->getParam($params, 'tiltstep');
  Debug("Step Down/Left $tiltstep/$panstep");
  my $cmd = "/axis-cgi/com/ptz.cgi?rpan=-$panstep&rtilt=-$tiltstep";
  $self->sendCmd($cmd);
}

sub zoomConTele {
  my $self = shift;
  my $params = shift;
  my $speed = 20;
  Debug('Zoom ConTele');
  my $cmd = "/axis-cgi/com/ptz.cgi?continuouszoommove=$speed";
  $self->sendCmd($cmd);
}

sub zoomConWide {
  my $self = shift;
  my $params = shift;
  #my $step = $self->getParam($params, 'step');
  my $speed = -20;
  Debug('Zoom ConWide');
  my $cmd = "/axis-cgi/com/ptz.cgi?continuouszoommove=$speed";
  $self->sendCmd($cmd);
}

sub zoomStop {
  my $self = shift;
  my $params = shift;
  my $speed = 0;
  Debug('Zoom Stop');
  my $cmd = "/axis-cgi/com/ptz.cgi?continuouszoommove=$speed";
  $self->sendCmd($cmd);
}

sub moveStop {
  my $self = shift;
  my $params = shift;
  my $speed = 0;
  Debug('Move Stop');
  # we have to stop both pans and zooms
  $self->sendCmd("/axis-cgi/com/ptz.cgi?continuouspantiltmove=$speed,$speed");
  $self->sendCmd("/axis-cgi/com/ptz.cgi?continuouszoommove=$speed");
}

sub zoomRelTele {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'step');
  Debug('Zoom Tele');
  my $cmd = "/axis-cgi/com/ptz.cgi?rzoom=$step";
  $self->sendCmd($cmd);
}

sub zoomRelWide {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'step');
  Debug('Zoom Wide');
  my $cmd = "/axis-cgi/com/ptz.cgi?rzoom=-$step";
  $self->sendCmd($cmd);
}

sub focusRelNear {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'step');
  Debug('Focus Rel Near');
  if ($$self{use_optics}) {
    my $cmd = '/axis-cgi/opticssetup.cgi?rfocus=-minstep';
    $self->sendCmd($cmd);
  } else {
    my $cmd = "/axis-cgi/com/ptz.cgi?rfocus=-$step";
    my $res = $self->sendCmd($cmd);
    if ($res->content ne 'ok') {
      $$self{use_optics} = 1;
      $self->focusRelNear($params);
    }
  }
}

sub focusRelFar {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'step');
  Debug('Focus Rel Far');
  if ($$self{use_optics}) {
    my $cmd = '/axis-cgi/opticssetup.cgi?rfocus=minstep';
    $self->sendCmd($cmd);
  } else {
    my $cmd = "/axis-cgi/com/ptz.cgi?rfocus=$step";
    my $res = $self->sendCmd($cmd);
    if ($res->content ne 'ok') {
      $$self{use_optics} = 1;
      $self->focusRelFar($params);
    }
  }
}

sub focusAbs {
  # Takes a value 0-1 in small decimal incremements
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'step');
  # step comes in as an integer, needs to be 0-1
  Debug('Focus Abs');
  my $cmd = "/axis-cgi/opticssetup.cgi?afocus=$step";
  $self->sendCmd($cmd);
}

sub focusAuto {
  my $self = shift;
  Debug('Focus Auto');
  my $cmd = '/axis-cgi/com/ptz.cgi?autofocus=on';
  $self->sendCmd($cmd);
}

sub focusMan {
  my $self = shift;
  Debug('Focus Manual');
  my $cmd = '/axis-cgi/com/ptz.cgi?autofocus=off';
  $self->sendCmd($cmd);
}

sub irisRelOpen {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'step');
  Debug('Iris Open');
  my $cmd = "/axis-cgi/com/ptz.cgi?riris=$step";
  $self->sendCmd($cmd);
}

sub irisRelClose {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'step');
  Debug('Iris Close');
  my $cmd = "/axis-cgi/com/ptz.cgi?riris=-$step";
  $self->sendCmd($cmd);
}

sub irisAuto {
  my $self = shift;
  Debug('Iris Auto');
  my $cmd = '/axis-cgi/com/ptz.cgi?autoiris=on';
  $self->sendCmd($cmd);
}

sub irisMan {
  my $self = shift;
  Debug('Iris Manual');
  my $cmd = '/axis-cgi/com/ptz.cgi?autoiris=off';
  $self->sendCmd($cmd);
}

sub presetClear {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Clear Preset $preset");
  my $cmd = "/axis-cgi/com/ptz.cgi?removeserverpresetno=$preset";
  $self->sendCmd($cmd);
}

sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Set Preset $preset");
  my $cmd = "/axis-cgi/com/ptz.cgi?setserverpresetno=$preset";
  $self->sendCmd($cmd);
}

sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Goto Preset $preset");
  my $cmd = "/axis-cgi/com/ptz.cgi?gotoserverpresetno=$preset";
  $self->sendCmd($cmd);
}

sub presetHome {
  my $self = shift;
  Debug('Home Preset');
  my $cmd = '/axis-cgi/com/ptz.cgi?move=home';
  $self->sendCmd($cmd);
}

sub reboot {
  my $self = shift;
  my $response = $self->sendCmd('/axis-cgi/restart.cgi');
  return $response->is_success;
}

# ==========================================================================
# Configuration get/set via param.cgi
# ==========================================================================

my %config_types = (
  'Properties.System'    => {},
  'Properties.Image'     => {},
  'Properties.PTZ'       => {},
  'Properties.Streaming' => {},
  'Properties.Network'   => {},
);

sub get_config {
  my $self = shift;
  my %config;
  foreach my $category ( @_ ? @_ : keys %config_types ) {
    my $response = $self->get("/axis-cgi/param.cgi?action=list&group=$category");
    if (!$response || !$response->is_success()) {
      Error("Failed to get config for $category: " . ($response ? $response->status_line : 'no response'));
      next;
    }
    my %params;
    foreach my $line (split(/\n/, $response->content())) {
      $line =~ s/\r$//;
      next if $line eq '' or $line =~ /^#/;
      if ($line =~ /^([^=]+)=(.*)$/) {
        $params{$1} = $2;
      }
    }
    $config{$category} = \%params;
  }
  return \%config;
}

sub set_config {
  my $self = shift;
  my $diff = shift;
  foreach my $category ( @_ ? @_ : keys %config_types ) {
    if (!$$diff{$category}) {
      Debug("No changes for category $category");
      next;
    }
    Debug("Applying $category");
    my $params = $$diff{$category};
    my @pairs;
    foreach my $key (keys %$params) {
      push @pairs, "$key=$$params{$key}";
    }
    my $query = 'action=update&' . join('&', @pairs);
    my $response = $self->get("/axis-cgi/param.cgi?$query");
    if (!$response || !$response->is_success()) {
      Error("Failed to set config for $category: " . ($response ? $response->status_line : 'no response'));
      return undef;
    }
    if ($response->content() !~ /^OK/) {
      Error("param.cgi update failed for $category: " . $response->content());
      return undef;
    }
  }
  return !undef;
}

# ==========================================================================
# Network probe and RTSP URL
# ==========================================================================

sub probe {
  my ($ip, $username, $password) = @_;

  my $self = new ZoneMinder::Control::AxisV2();
  $self->{ua} = LWP::UserAgent->new();
  $self->{ua}->cookie_jar( {} );
  $$self{username} = $username;
  $$self{password} = $password;
  $$self{realm} = '';

  foreach my $port ( '80', '443' ) {
    $$self{port} = $port;
    $$self{host} = $ip;
    $$self{BaseURL} = "http://$ip:$port";
    $$self{address} = "$ip:$port";
    $self->{ua}->credentials("$ip:$port", '', $username, $password);

    if ($self->get_realm('/axis-cgi/param.cgi?action=list&group=Properties.System.SerialNumber')) {
      return {
        url => "rtsp://$ip/axis-media/media.amp",
        realm => $$self{realm},
      };
    }
  } # end foreach port
  return undef;
}

sub rtsp_url {
  my ($self, $ip) = @_;
  return 'rtsp://'.$ip.'/axis-media/media.amp';
}

sub profiles {
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Control::Axis - Zoneminder control for Axis Cameras using the V2 API

=head1 SYNOPSIS

  use ZoneMinder::Control::AxisV2 ; place this in /usr/share/perl5/ZoneMinder/Control

=head1 DESCRIPTION

This module is an implementation of the Axis V2 API 

=head2 EXPORT

None by default.



=head1 SEE ALSO

AXIS VAPIX Library Documentation; e.g.:
https://www.axis.com/vapix-library/subjects/t10175981/section/t10036011/display 

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
