# ==========================================================================
#
# ZoneMinder Panasonic IP Control Protocol Module, $Date$, $Revision$
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
# This module contains the implementation of the Panasonic IP camera control
# protocol
#
package ZoneMinder::Control::PanasonicIP;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Panasonic IP Control Protocol
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

    my $result = undef;

    printMsg( $cmd, "Tx" );

    my $req = HTTP::Request->new( GET=>"http://".$self->{Monitor}->{ControlAddress}."/$cmd" );
    my $res = $self->{ua}->request($req);

    if ( $res->is_success )
    {
        $result = !undef;
    }
    else
    {
        Error( "Error check failed: '".$res->status_line()."'" );
    }

    return( $result );
}

sub cameraReset
{
    my $self = shift;
    Debug( "Camera Reset" );
    my $cmd = "nphRestart?PAGE=Restart&Restart=OK";
    $self->sendCmd( $cmd );
}

sub moveConUp
{
    my $self = shift;
    Debug( "Move Up" );
    my $cmd = "nphControlCamera?Direction=TiltUp";
    $self->sendCmd( $cmd );
}

sub moveConDown
{
    my $self = shift;
    Debug( "Move Down" );
    my $cmd = "nphControlCamera?Direction=TiltDown";
    $self->sendCmd( $cmd );
}

sub moveConLeft
{
    my $self = shift;
    Debug( "Move Left" );
    my $cmd = "nphControlCamera?Direction=PanLeft";
    $self->sendCmd( $cmd );
}

sub moveConRight
{
    my $self = shift;
    Debug( "Move Right" );
    my $cmd = "nphControlCamera?Direction=PanRight";
    $self->sendCmd( $cmd );
}

sub moveMap
{
    my $self = shift;
    my $params = shift;
    my $xcoord = $self->getParam( $params, 'xcoord' );
    my $ycoord = $self->getParam( $params, 'ycoord' );
    Debug( "Move Map to $xcoord,$ycoord" );
    my $cmd = "nphControlCamera?Direction=Direct&NewPosition.x=$xcoord&NewPosition.y=$ycoord&Width=".$self->{Monitor}->{Width}."&Height=".$self->{Monitor}->{Height};
    $self->sendCmd( $cmd );
}

sub zoomConTele
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Zoom Tele" );
    my $cmd = "nphControlCamera?Direction=ZoomTele";
    $self->sendCmd( $cmd );
}

sub zoomConWide
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Zoom Wide" );
    my $cmd = "nphControlCamera?Direction=ZoomWide";
    $self->sendCmd( $cmd );
}

sub focusConNear
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Focus Near" );
    my $cmd = "nphControlCamera?Direction=FocusNear";
    $self->sendCmd( $cmd );
}

sub focusConFar
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Focus Far" );
    my $cmd = "nphControlCamera?Direction=FocusFar";
    $self->sendCmd( $cmd );
}

sub focusAuto
{
    my $self = shift;
    Debug( "Focus Auto" );
    my $cmd = "nphControlCamera?Direction=FocusAuto";
    $self->sendCmd( $cmd );
}

sub focusMan
{
    my $self = shift;
    Debug( "Focus Manual" );
    my $cmd = "/axis-cgi/com/ptz.cgi?autofocus=off";
    $self->sendCmd( $cmd );
}

sub presetClear
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Debug( "Clear Preset $preset" );
    my $cmd = "nphPresetNameCheck?Data=$preset";
    $self->sendCmd( $cmd );
}

sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Debug( "Set Preset $preset" );
    my $cmd = "nphPresetNameCheck?PresetName=$preset&Data=$preset";
    $self->sendCmd( $cmd );
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Debug( "Goto Preset $preset" );
    my $cmd = "nphControlCamera?Direction=Preset&PresetOperation=Move&Data=$preset";
    $self->sendCmd( $cmd );
}

sub presetHome
{
    my $self = shift;
    Debug( "Home Preset" );
    my $cmd = "nphControlCamera?Direction=HomePosition";
    $self->sendCmd( $cmd );
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Database - Perl extension for blah blah blah

=head1 SYNOPSIS

  use ZoneMinder::Database;
  blah blah blah

=head1 DESCRIPTION

Stub documentation for ZoneMinder, created by h2xs. It looks like the
author of the extension was negligent enough to leave the stub
unedited.

Blah blah blah.

=head2 EXPORT

None by default.



=head1 SEE ALSO

Mention other useful documentation such as the documentation of
related modules or operating system documentation (such as man pages
in UNIX), or any relevant external documentation such as RFCs or
standards.

If you have a mailing list set up for your module, mention it here.

If you have a web site set up for your module, mention it here.

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
