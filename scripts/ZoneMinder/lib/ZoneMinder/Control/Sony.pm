# ==========================================================================
#
# ZoneMinder Sony Network Camera SNC-EP521 Control Protocol Module, date: Sun Jun 22 10:26:25 IRDT 2014
# Copyright (C) 2013-2014  Iman Darabi <iman.darabi@gmail.com>
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
# This module contains the implementation of the Sony Network Camera PTZ API
#
package ZoneMinder::Control::Sony;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Sony Network Camera Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

sub open {
  my $self = shift;

  $self->loadMonitor();

  use LWP::UserAgent;
  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->agent('ZoneMinder Control Agent/'.$ZoneMinder::Base::VERSION);

  $self->{state} = 'open';
}

sub sendCmd {
  my $self = shift;
  my $cmd = shift;

  my $result = undef;

  $self->printMsg($cmd, 'Tx');

  #print( "http://$address/$cmd\n" );
  my $req = HTTP::Request->new( GET=>'http://'.$self->{Monitor}->{ControlAddress}.'/'.$cmd);
  my $res = $self->{ua}->request($req);

  if ( $res->is_success ) {
    $result = !undef;
  } else {
    Error("Error check failed: '".$res->status_line()."'");
  }

  return $result;
}

sub moveConUp {
  my $self = shift;
  Debug('Move Up');

  my $cmd = '/command/ptzf.cgi?Move=up,8,1';
  $self->sendCmd($cmd);
}

sub moveConDown {
  my $self = shift;
  Debug( "Move Down" );

  my $cmd = "/command/ptzf.cgi?Move=down,8,1";
  $self->sendCmd( $cmd );
}

sub moveConLeft {
  my $self = shift;
  Debug( "Move Left" );

  my $cmd = "/command/ptzf.cgi?Move=left,8,1";
  $self->sendCmd( $cmd );
}

sub moveConRight {
  my $self = shift;
  Debug('Move Right');
  my $cmd = '/command/ptzf.cgi?Move=right,8,1';
  $self->sendCmd($cmd);
}

sub moveConUpRight {
  my $self = shift;
  Debug( "Move Up/Right" );

  my $cmd = "/command/ptzf.cgi?Move=up-right,8,1";
  $self->sendCmd( $cmd );
}

sub moveConUpLeft {
  my $self = shift;
  Debug( "Move Up/Left" );

  my $cmd = "/command/ptzf.cgi?Move=up-left,8,1";
  $self->sendCmd( $cmd );
}

sub moveConDownRight {
  my $self = shift;
  Debug( "Move Down/Right" );

  my $cmd = "/command/ptzf.cgi?Move=down-right,8,1";
  $self->sendCmd( $cmd );
}

sub moveConDownLeft {
  my $self = shift;
  Debug( "Move Down/Left" );

  my $cmd = "/command/ptzf.cgi?Move=down-left,8,1";
  $self->sendCmd( $cmd );
}

sub moveMap {
  my $self = shift;
  my $params = shift;
  my $xcoord = $self->getParam( $params, 'xcoord' );
  my $ycoord = $self->getParam( $params, 'ycoord' );

  Debug( "Move Map to $xcoord,$ycoord" );

  my $cmd = "/command/ptzf.cgi?AreaZoom=$xcoord,$ycoord,$self->{Monitor}->{Width},$self->{Monitor}->{Height}";
  $self->sendCmd( $cmd );
}

sub moveRelUp {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'tiltstep');
  Debug("Step Up $step");

  my $cmd = "/command/ptzf.cgi?relative=08$step";
  $self->sendCmd($cmd);
}

sub moveRelDown {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'tiltstep');
  Debug( "Step Down $step" );
  my $cmd = "/command/ptzf.cgi?relative=02$step";
  $self->sendCmd($cmd);
}

sub moveRelLeft {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam( $params, 'panstep' );
  Debug( "Step Left $step" );
  my $cmd = "/command/ptzf.cgi?relative=04$step";
  $self->sendCmd($cmd);
}

sub moveRelRight {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'panstep');
  Debug((ref $self)."Step Right $step");
  my $cmd = "/command/ptzf.cgi?relative=06$step";
  $self->sendCmd($cmd);
}

sub moveRelUpRight {
  my $self = shift;
  my $params = shift;
  my $panstep = $self->getParam( $params, 'panstep' );
  my $tiltstep = $self->getParam( $params, 'tiltstep' );
  Debug("Step Up/Right $tiltstep/$panstep");
  my $cmd = "/command/ptzf.cgi?relative=0905";
  $self->sendCmd($cmd);
}

sub moveRelUpLeft {
  my $self = shift;
  my $params = shift;
  my $panstep = $self->getParam($params, 'panstep');
  my $tiltstep = $self->getParam($params, 'tiltstep');
  Debug("Step Up/Left $tiltstep/$panstep");
  my $cmd = '/command/ptzf.cgi?relative=0705';
  $self->sendCmd($cmd);
}

sub moveRelDownRight {
  my $self = shift;
  my $params = shift;
  my $panstep = $self->getParam($params, 'panstep');
  my $tiltstep = $self->getParam($params, 'tiltstep');
  Debug("Step Down/Right $tiltstep/$panstep");
  my $cmd = '/command/ptzf.cgi?relative=0305';
  $self->sendCmd($cmd);
}

sub moveRelDownLeft {
  my $self = shift;
  my $params = shift;
  my $panstep = $self->getParam( $params, 'panstep');
  my $tiltstep = $self->getParam( $params, 'tiltstep');
  Debug("Step Down/Left $tiltstep/$panstep");
  my $cmd = '/command/ptzf.cgi?relative=0105';
  $self->sendCmd($cmd);
}

sub moveStop {
  my $self = shift;
  Debug('Move Stop');

  $self->sendCmd('/command/ptzf.cgi?Move=stop,pantilt,1');
  $self->sendCmd('/command/ptzf.cgi?Move=stop,zoom,1');
}

sub zoomRelTele {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'step');
  Debug('Zoom Tele');

  my $cmd = '/command/ptzf.cgi?Move=tele,8';
  $self->sendCmd($cmd);
  $self->sendCmd($cmd);	# do it twice, so faster, why <speed> don't affect?

  my $cmd = '/command/ptzf.cgi?Move=stop,zoom,1';
  $self->sendCmd($cmd);
}

sub zoomRelWide {
  my $self = shift;
  my $params = shift;
  my $step = $self->getParam($params, 'step');
  Debug('Zoom Wide');

  my $cmd = '/command/ptzf.cgi?Move=wide,8';
  $self->sendCmd( $cmd );
  $self->sendCmd( $cmd );	# do it twice, so faster, why <speed> don't affect?

  my $cmd = '/command/ptzf.cgi?Move=stop,zoom,1';
  $self->sendCmd( $cmd );
}

sub zoomConWide {
  my $self = shift;
  Debug('zoom ConWide');

  my $cmd = '/command/ptzf.cgi?Move=wide,4';
  $self->sendCmd( $cmd );
}

sub zoomConTele {
  my $self = shift;
  Debug( "zoom ConTele" );

  my $cmd = "/command/ptzf.cgi?Move=tele,4";
  $self->sendCmd( $cmd );

}

sub presetClear {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Clear Preset $preset");
  my $cmd = "/command/presetposition.cgi?PresetClear=$preset,1";
  $self->sendCmd($cmd);
}

sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Set Preset $preset");
  my $cmd = "/command/presetposition.cgi?PresetSet=$preset,No.$preset,on,1";
  $self->sendCmd($cmd);
}

sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Goto Preset $preset");
  my $cmd = "/command/presetposition.cgi?PresetCall=$preset,12,1";
  $self->sendCmd($cmd);
}

sub presetHome {
  my $self = shift;
  Debug('Home Preset');
  my $cmd = '/command/presetposition.cgi?HomePos=ptz-recall';
  $self->sendCmd( $cmd );
}

1;
__END__

=head1 NAME

ZoneMinder::Control::Sony - PTZ driver for Sony cameras

=head1 SYNOPSIS

This file is used by zmcontrol.pl to talk to Sony cameras.

=head1 AUTHOR

iman darabi, E<lt>iman.darabi@gmail.com<gt>
Updated by Isaac Connor E<lt>isaac@zoneminder.com<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2013-2014  Iman Darabi

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.

=cut
