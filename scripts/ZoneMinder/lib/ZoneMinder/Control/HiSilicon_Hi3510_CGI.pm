# ==========================================================================
#
# ZoneMinder HiSilicon Hi3510 CGI Control Protocol Module
# Contributed by Turgut Kalfaoglu, based on the FoscamCGI module
# by Jan M. Hochstein, adapted for the HiSilicon Hi3510 CGI interface.
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
# Tested: Tenvis TH661
#
# Many inexpensive IP cameras built on the HiSilicon Hi3510 SoC expose
# this CGI interface (cgi-bin/hi3510/ptzctrl.cgi) rather than the older
# Foscam decoder_control.cgi protocol. Credentials are passed as
# usr/pwd query parameters.
#
# On ControlAddress use the format:
#   USERNAME:PASSWORD@ADDRESS:PORT
#   eg: admin:mypassword@192.168.1.142:80
#
# If the password contains special characters such as @ or :, url-encode
# them (e.g. @ as %40).
#
# Presets 1-8 are stored on the camera. Preset 9 starts a horizontal
# patrol (hscan), preset 10 starts a vertical patrol (vscan).
#
# ==========================================================================

package ZoneMinder::Control::HiSilicon_Hi3510_CGI;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );
use URI::Escape qw( uri_escape );

sub open {
  my $self = shift;
  $self->loadMonitor();

  use LWP::UserAgent;
  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);

  # Parse username/password/host/port out of ControlAddress
  $self->guess_credentials();
  $$self{username} = 'admin' unless defined $$self{username};
  $$self{password} = '' unless defined $$self{password};
  $$self{port} = 80 unless $$self{port};

  $self->{state} = 'open';
}

sub sendCmd {
  my $self = shift;
  my $cmd = shift;
  my $result = undef;
  $self->printMsg($cmd, 'Tx');

  my $url = 'http://'.$$self{host}.':'.$$self{port}.'/cgi-bin/hi3510/'.$cmd.
    '&usr='.uri_escape($$self{username}).'&pwd='.uri_escape($$self{password});

  my $req = HTTP::Request->new(GET=>$url);
  my $res = $self->{ua}->request($req);

  if ( $res->is_success ) {
    $result = !undef;
  } else {
    Error("Command failed: '".$res->status_line()."'");
  }

  return $result;
}

# This makes use of the ZoneMinder Auto Stop Timeout on the Control tab
sub autoStop {
  my $self = shift;
  my $autostop = shift;
  if ( $autostop ) {
    Debug('Auto Stop');
    usleep($autostop);
    $self->sendCmd('ptzctrl.cgi?-step=0&-act=stop');
  }
}

# Reboot the camera
sub reset {
  my $self = shift;
  Debug('Camera Reset');
  $self->sendCmd('param.cgi?cmd=sysreboot');
}

# Up Arrow
sub moveConUp {
  my $self = shift;
  Debug('Move Up');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=up');
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

# Down Arrow
sub moveConDown {
  my $self = shift;
  Debug('Move Down');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=down');
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

# Left Arrow
sub moveConLeft {
  my $self = shift;
  Debug('Move Left');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=left');
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

# Right Arrow
sub moveConRight {
  my $self = shift;
  Debug('Move Right');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=right');
  $self->autoStop($self->{Monitor}->{AutoStopTimeout});
}

# Diagonal moves are not supported by the camera so we emulate them
sub moveConUpRight {
  my $self = shift;
  Debug('Move Up-Right');
  $self->moveConUp();
  $self->moveConRight();
}

sub moveConDownRight {
  my $self = shift;
  Debug('Move Down-Right');
  $self->moveConDown();
  $self->moveConRight();
}

sub moveConUpLeft {
  my $self = shift;
  Debug('Move Up-Left');
  $self->moveConUp();
  $self->moveConLeft();
}

sub moveConDownLeft {
  my $self = shift;
  Debug('Move Down-Left');
  $self->moveConDown();
  $self->moveConLeft();
}

# Stop
sub moveStop {
  my $self = shift;
  Debug('Move Stop');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=stop');
}

# Horizontal patrol
sub horizontalPatrol {
  my $self = shift;
  Debug('Horizontal Patrol');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=hscan');
}

# Vertical patrol
sub verticalPatrol {
  my $self = shift;
  Debug('Vertical Patrol');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=vscan');
}

# Stop patrol
sub horizontalPatrolStop {
  my $self = shift;
  Debug('Patrol Stop');
  $self->sendCmd('ptzctrl.cgi?-step=0&-act=stop');
}

# Recall Camera Preset
# Presets 1-8 are stored on the camera, preset 9 = hscan, preset 10 = vscan
sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Goto Preset $preset");

  if ( $preset >= 1 && $preset <= 8 ) {
    $self->sendCmd('ptzctrl.cgi?-step=0&-act=goto&-number='.($preset-1));
  } elsif ( $preset == 9 ) {
    $self->horizontalPatrol();
  } elsif ( $preset == 10 ) {
    $self->verticalPatrol();
  }
}

# Set Camera Preset
sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Set Preset $preset");

  if ( $preset >= 1 && $preset <= 8 ) {
    $self->sendCmd('ptzctrl.cgi?-step=0&-act=set&-number='.($preset-1));
  }
}

1;
