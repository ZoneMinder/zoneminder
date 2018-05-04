# ==========================================================================
#
# ZoneMinder Toshiba IK WB11A IP Camera Control Protocol Module,
# Copyright (C) 2013  Tim Craig (timcraigNO@SPAMsonic.net)
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
# This module contains the implementation of the Airlink SkyIPCam
# AICN747/AICN747W, TrendNet TV-IP410/TV-IP410W and other OEM versions of the
# Fitivision CS-130A/CS-131A camera control protocol.
#
package ZoneMinder::Control::Toshiba_IK_WB11A;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
#   Toshiba IK-WB11A IP Camera Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );

sub open
{
    my $self = shift;

    $self->loadMonitor();

    use LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new;
    $self->{ua}->agent( "ZoneMinder Control Agent/".ZoneMinder::Base::ZM_VERSION );

    $self->{state} = 'open';
}

sub printMsg
{
    my $self = shift;
    my $msg = shift;
    my $msg_len = length($msg);

    Debug( $msg."[".$msg_len."]" );
}

sub sendCmd
{
    my $self = shift;
    my $cmd = shift;

    #my $result = undef;

    printMsg( $cmd, "Tx" );

    my $req = HTTP::Request->new( GET=>"http://".$self->{Monitor}->{ControlAddress}."$cmd" );
    my $res = $self->{ua}->request($req);
    return( !undef );
}

sub reset
{
    my $self = shift;
    Debug( "Camera Reset" );
    my $cmd = "/control.cgi?cont_2=16";
    $self->sendCmd( $cmd );
}

sub moveMap
{
   Debug("MoveMap");
   my $self = shift;
   my $params = shift;
   my $xcoord = $self->getParam( $params, 'xcoord' );
   my $ycoord = $self->getParam( $params, 'ycoord' );

   my $hor = $xcoord / $self->{Monitor}->{Width};
   my $ver = $ycoord / $self->{Monitor}->{Height};

   my $maxver = 10;
   my $maxhor = 10;

   my $horSteps = 0;
   my $verSteps = 0;

   $horSteps = $hor * $maxhor;
   $verSteps = $ver * $maxver;

    my $v = int($verSteps);
    my $h = int($horSteps);

    Debug( "Move Map to $xcoord,$ycoord, hor=$h, ver=$v");
    my $cmd = "/cont.cgi?contptpoint_".$h."_".$v."=1";
    $self->sendCmd( $cmd );
}

sub moveRelUp
{
    my $self = shift;
    Debug( "Step Up" );
    my $cmd = "/control.cgi?cont_2=4";
    $self->sendCmd( $cmd );
}

sub moveRelDown
{
    my $self = shift;
    Debug( "Step Down" );
    my $cmd = "/control.cgi?cont_2=8";
    $self->sendCmd( $cmd );
}

sub moveRelLeft
{
    my $self = shift;
    Debug( "Step Left" );
    my $cmd = "/control.cgi?cont_2=1";
    $self->sendCmd( $cmd );
}

sub moveRelRight
{
    my $self = shift;
    Debug( "Step Right" );
    my $cmd = "/control.cgi?cont_2=2";
    $self->sendCmd( $cmd );
}

sub presetClear
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Debug( "Clear Preset $preset" );
    my $cmdNum = 3 << 8 | $preset;
    my $cmd = "/control.cgi?cont_4=$cmdNum";
    $self->sendCmd( $cmd );
}

sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Debug( "Set Preset $preset" );
    my $cmdNum = 2 << 8 | $preset;
    my $cmd = "/control.cgi?cont_4=$cmdNum";
    $self->sendCmd( $cmd );
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Debug( "Goto Preset $preset" );
    my $cmdNum = 1 << 8 | $preset;
    my $cmd = "/control.cgi?cont_4=$cmdNum";
    $self->sendCmd( $cmd );
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Control::Toshiba_IK_WB11A - Zoneminder PTZ control module the Toshiba IK-WB11A IP Camera

=head1 SYNOPSIS

  use ZoneMinder::Control::Toshiba_IK_WB11A;
  blah blah blah

=head1 DESCRIPTION

   This is for Zoneminder PTZ control module for the Toshib_IK_WB11A camera.

=head2 EXPORT

None by default.



=head1 SEE ALSO

www.zoneminder.com

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>
Tim Craig, E<lt>timcraigNO@SPAMsonic.netE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2013 by Tim Craig

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
