# =========================================================================r
#
# ZoneMinder D-Link DCS-5020L IP Control Protocol Module, $Date: $, $Revision: $
# Copyright (C) 2013 Art Scheel
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
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
# ==========================================================================
#
# This module contains the implementation of the D-Link DCS-5020L IP camera control
# protocol. 
#
package ZoneMinder::Control::DCS5020L;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# D-Link DCS-5020L Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );

sub open {
  my $self = shift;

  $self->loadMonitor();

  use LWP::UserAgent;
  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->agent('ZoneMinder Control Agent/' . ZoneMinder::Base::ZM_VERSION);
  $self->{state} = 'open';
}

sub close {
  my $self = shift;
  $self->{state} = 'closed';
}

sub sendCmd {
  my $self = shift;
  my $cmd = shift;
  my $cgi = shift;

  my $result = undef;

  printMsg($cmd, 'Tx');

  my $req = HTTP::Request->new( POST=>"http://$self->{Monitor}->{ControlAddress}/$cgi.cgi" );
  $req->content($cmd);
  my $res = $self->{ua}->request($req);

  if ( $res->is_success ) {
    $result = !undef;
  } else {
    Error("Error check failed: '".$res->status_line()."'");
  }

  return $result;
}

sub move {
  my $self = shift;
  my $dir = shift;
  my $panStep = shift;
  my $tiltStep = shift;
  my $cmd = "PanSingleMoveDegree=$panStep&TiltSingleMoveDegree=$tiltStep&PanTiltSingleMove=$dir";
  $self->sendCmd($cmd, 'pantiltcontrol');
}

sub moveRel {
  my $self = shift;
  my $params = shift;
  my $panStep = $self->getParam($params, 'panstep', 0);
  my $tiltStep = $self->getParam($params, 'tiltstep', 0);
  my $dir = shift;
  $self->move( $dir, $panStep, $tiltStep );
}

sub moveRelUpLeft {
  my $self = shift;
  my $params = shift;
  $self->moveRel($params, 0);
}

sub moveRelUp {
  my $self = shift;
  my $params = shift;
  $self->moveRel($params, 1);
}

sub moveRelUpRight {
  my $self = shift;
  my $params = shift;
  $self->moveRel($params, 2);
}

sub moveRelLeft {
  my $self = shift;
  my $params = shift;
  $self->moveRel($params, 3);
}

sub moveRelRight {
  my $self = shift;
  my $params = shift;
  $self->moveRel($params, 5);
}

sub moveRelDownLeft {
  my $self = shift;
  my $params = shift;
  $self->moveRel($params, 6);
}

sub moveRelDown {
  my $self = shift;
  my $params = shift;
  $self->moveRel($params, 7);
}

sub moveRelDownRight {
  my $self = shift;
  my $params = shift;
  $self->moveRel($params, 8);
}

# moves the camera to center on the point that the user clicked on in the video image. 
# This isn't extremely accurate but good enough for most purposes 
sub moveMap {
  # if the camera moves too much or too little, try increasing or decreasing this value
  my $f = 11;

  my $self = shift;
  my $params = shift;
  my $xcoord = $self->getParam( $params, 'xcoord' );
  my $ycoord = $self->getParam( $params, 'ycoord' );

  my $hor = $xcoord * 100 / $self->{Monitor}->{Width};
  my $ver = $ycoord * 100 / $self->{Monitor}->{Height};

  my $direction;
  my $horSteps;
  my $verSteps;
  if ($hor < 50 && $ver < 50) {
    # up left
    $horSteps = (50 - $hor) / $f;
    $verSteps = (50 - $ver) / $f;
    $direction = 0;
  } elsif ($hor >= 50 && $ver < 50) {
    # up right
    $horSteps = ($hor - 50) / $f;
    $verSteps = (50 - $ver) / $f;
    $direction = 2;
  } elsif ($hor < 50 && $ver >= 50) {
    # down left
    $horSteps = (50 - $hor) / $f;
    $verSteps = ($ver - 50) / $f;
    $direction = 6;
  } elsif ($hor >= 50 && $ver >= 50) {
    # down right
    $horSteps = ($hor - 50) / $f;
    $verSteps = ($ver - 50) / $f;
    $direction = 8;
  }
  my $v = int($verSteps + .5);
  my $h = int($horSteps + .5);
  Debug("Move Map to $xcoord,$ycoord, hor=$h, ver=$v with direction $direction");
  $self->move($direction, $h, $v);
}

sub presetClear {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam( $params, 'preset' );
  Debug( "Clear Preset $preset" );
  my $cmd = "ClearPosition=$preset";
  $self->sendCmd( $cmd, 'pantiltcontrol' );
}

sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam( $params, 'preset' );
  Debug( "Set Preset $preset" );
  my $cmd = "SetCurrentPosition=$preset&SetName=preset_$preset";
  $self->sendCmd( $cmd, 'pantiltcontrol' );
}

sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam( $params, 'preset' );
  Debug( "Goto Preset $preset" );
  my $cmd = "PanTiltPresetPositionMove=$preset";
  $self->sendCmd( $cmd, 'pantiltcontrol' );
}

sub presetHome {
  my $self = shift;
  Debug( "Home Preset" );
  $self->move( 4, 0, 0 );
}

#  IR Controls
#
#  wake = IR on
#  sleep = IR off
#  reset = IR auto

sub setDayNightMode {
  my $self = shift;
  my $mode = shift;
  my $cmd = "DayNightMode=$mode&ConfigReboot=No";
  $self->sendCmd($cmd, 'daynight');
}

sub wake {
  my $self = shift;
  Debug('Wake - IR on');
  $self->setDayNightMode(2);
}

sub sleep {
  my $self = shift;
  Debug('Sleep - IR off');
  $self->setDayNightMode(3);
}

sub reset {
  my $self = shift;
  Debug('Reset - IR auto');
  $self->setDayNightMode(0);
}

1;
__END__

=head1 NAME

ZoneMinder::Control::DCS5020L - Perl extension for DCS-5020L

=head1 SYNOPSIS

  use ZoneMinder::Database;
  DLINK DCS-5020L

=head1 DESCRIPTION

ZoneMinder driver for the D-Link consumer camera DCS-5020L.

=head2 EXPORT

None by default.



=head1 SEE ALSO

See if there are better instructions for the DCS-5020L at
http://www.zoneminder.com/wiki/index.php/Dlink

=head1 AUTHOR

Art Scheel <lt>ascheel (at) gmail<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2018 ZoneMinder LLC

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

=cut
