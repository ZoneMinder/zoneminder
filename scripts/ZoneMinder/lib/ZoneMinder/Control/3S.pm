# ==========================================================================
#
# ZoneMinder 3S API Control Protocol Module, $Date: 2014-11-12 08:00:00 +0300 (Tue, 21 Jun 2011) $, $Revision: 1 $
# Copyright (C) 2014  Juan Manuel Castro
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
# This module contains the implementation of the 3S camera control
# protocol
#Model:                 N5071
#Hardware Version:      00
#Firmware Version:      V1.03_STD-1
#Firmware Build Time:   Jun 19 2012 15:28:17

package ZoneMinder::Control::3S;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# 3S Control Protocol
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
    #$self->{ua}->agent( "ZoneMinder Control Agent/".ZM_VERSION );
    $self->{ua}->agent( "ZoneMinder Control Agent/" . ZoneMinder::Base::ZM_VERSION );
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

    #print("http://".$self->{Monitor}->{ControlAddress}."/$cmd");
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
    my $cmd = "/restart.cgi";
    $self->sendCmd( $cmd );
}

#Custom#
#Move X or Y Axis
sub Up
{
    my $self = shift;
    Debug( "Move Up" );
    my $cmd = "/ptz.cgi?move=up";
    $self->sendCmd( $cmd );
}

sub Down
{
    my $self = shift;
    Debug( "Move Down" );
    my $cmd = "/ptz.cgi?move=down";
    $self->sendCmd( $cmd );
}

sub Left
{
    my $self = shift;
    Debug( "Move Left" );
    my $cmd = "/ptz.cgi?move=left";
    $self->sendCmd( $cmd );
}

sub Right
{
    my $self = shift;
    Debug( "Move Right" );
    my $cmd = "/ptz.cgi?move=right";
    $self->sendCmd( $cmd );
}

##Zoom
sub Tele
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Zoom Tele" );
    my $cmd = "/ptz.cgi?rzoom=$step";
    $self->sendCmd( $cmd );
}

sub Wide
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Zoom Wide" );
    my $cmd = "/ptz.cgi?rzoom=-$step";
    $self->sendCmd( $cmd );
}

#Move X and Y Axis
sub UpRight
{
    my $self = shift;
    Debug( "Move Up/Right" );
    my $cmd = "/ptz.cgi?move=upright";
    $self->sendCmd( $cmd );
}

sub UpLeft
{
    my $self = shift;
    Debug( "Move Up/Left" );
    my $cmd = "/ptz.cgi?move=upleft";
    $self->sendCmd( $cmd );
}

sub DownRight
{
    my $self = shift;
    Debug( "Move Down/Right" );
    my $cmd = "/ptz.cgi?move=downright";
    $self->sendCmd( $cmd );
}

sub DownLeft
{
    my $self = shift;
    Debug( "Move Down/Left" );
    my $cmd = "/ptz.cgi?move=downleft";
    $self->sendCmd( $cmd );
}

#Foco
sub focusAuto
{
    my $self = shift;
    Debug( "Focus Auto" );
    my $cmd = "/ptz.cgi?Autofocus=on";
    $self->sendCmd( $cmd );
}

sub focusMan
{
    my $self = shift;
    Debug( "Focus Manual" );
    my $cmd = "/ptz.cgi?Autofocus=off";
    $self->sendCmd( $cmd );
}

sub Near
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Focus Near" );
    my $cmd = "/ptz.cgi?rfocus=-$step";
    $self->sendCmd( $cmd );
}

sub Far
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Focus Far" );
    my $cmd = "/ptz.cgi?rfocus=$step";
    $self->sendCmd( $cmd );
}

#Iris
sub Open
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Iris Open" );
    my $cmd = "/ptz.cgi?riris=$step";
    $self->sendCmd( $cmd );
}

sub Close
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Iris Close" );
    my $cmd = "/ptz.cgi?riris=-$step";
    $self->sendCmd( $cmd );
}

#Custom#

sub moveConUp
{
    my $self = shift;
    Debug( "Move Up" );
    my $cmd = "/ptz.cgi?move=up";
    $self->sendCmd( $cmd );
}

sub moveConDown
{
    my $self = shift;
    Debug( "Move Down" );
    my $cmd = "/ptz.cgi?move=down";
    $self->sendCmd( $cmd );
}

sub moveConLeft
{
    my $self = shift;
    Debug( "Move Left" );
    my $cmd = "/ptz.cgi?move=left";
    $self->sendCmd( $cmd );
}

sub moveConRight
{
    my $self = shift;
    Debug( "Move Right" );
    my $cmd = "/ptz.cgi?move=right";
    $self->sendCmd( $cmd );
}

sub moveConUpRight
{
    my $self = shift;
    Debug( "Move Up/Right" );
    my $cmd = "/ptz.cgi?move=upright";
    $self->sendCmd( $cmd );
}

sub moveConUpLeft
{
    my $self = shift;
    Debug( "Move Up/Left" );
    my $cmd = "/ptz.cgi?move=upleft";
    $self->sendCmd( $cmd );
}

sub moveConDownRight
{
    my $self = shift;
    Debug( "Move Down/Right" );
    my $cmd = "/ptz.cgi?move=downright";
    $self->sendCmd( $cmd );
}

sub moveConDownLeft
{
    my $self = shift;
    Debug( "Move Down/Left" );
    my $cmd = "/ptz.cgi?move=downleft";
    $self->sendCmd( $cmd );
}

sub moveRelUp
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'tiltstep' );
    Debug( "Step Up $step" );
    my $cmd = "/ptz.cgi?tilt=$step";
    $self->sendCmd( $cmd );
}

sub moveRelDown
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'tiltstep' );
    Debug( "Step Down $step" );
    my $cmd = "/ptz.cgi?tilt=-$step";
    $self->sendCmd( $cmd );
}

sub moveRelLeft
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'panstep' );
    Debug( "Step Left $step" );
    my $cmd = "/ptz.cgi?pan=-$step";
    $self->sendCmd( $cmd );
}

sub moveRelRight
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'panstep' );
    Debug( "Step Right $step" );
    my $cmd = "/ptz.cgi?pan=$step";
    $self->sendCmd( $cmd );
}

sub moveRelUpRight
{
    my $self = shift;
    my $params = shift;
    my $panstep = $self->getParam( $params, 'panstep' );
    my $tiltstep = $self->getParam( $params, 'tiltstep' );
    Debug( "Step Up/Right $tiltstep/$panstep" );
    my $cmd = "/ptz.cgi?pan=$panstep&tilt=$tiltstep";
    $self->sendCmd( $cmd );
}

sub moveRelUpLeft
{
    my $self = shift;
    my $params = shift;
    my $panstep = $self->getParam( $params, 'panstep' );
    my $tiltstep = $self->getParam( $params, 'tiltstep' );
    Debug( "Step Up/Left $tiltstep/$panstep" );
    my $cmd = "/ptz.cgi?pan=-$panstep&tilt=$tiltstep";
    $self->sendCmd( $cmd );
}

sub moveRelDownRight
{
    my $self = shift;
    my $params = shift;
    my $panstep = $self->getParam( $params, 'panstep' );
    my $tiltstep = $self->getParam( $params, 'tiltstep' );
    Debug( "Step Down/Right $tiltstep/$panstep" );
    my $cmd = "/ptz.cgi?pan=$panstep&tilt=-$tiltstep";
    $self->sendCmd( $cmd );
}

sub moveRelDownLeft
{
    my $self = shift;
    my $params = shift;
    my $panstep = $self->getParam( $params, 'panstep' );
    my $tiltstep = $self->getParam( $params, 'tiltstep' );
    Debug( "Step Down/Left $tiltstep/$panstep" );
    my $cmd = "/ptz.cgi?pan=-$panstep&tilt=-$tiltstep";
    $self->sendCmd( $cmd );
}

sub zoomRelTele
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Zoom Tele" );
    my $cmd = "/ptz.cgi?rzoom=$step";
    $self->sendCmd( $cmd );
}

sub zoomRelWide
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Zoom Wide" );
    my $cmd = "/ptz.cgi?rzoom=-$step";
    $self->sendCmd( $cmd );
}

sub focusRelNear
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Focus Near" );
    my $cmd = "/ptz.cgi?rfocus=-$step";
    $self->sendCmd( $cmd );
}

sub focusRelFar
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Focus Far" );
    my $cmd = "/ptz.cgi?rfocus=$step";
    $self->sendCmd( $cmd );
}

sub focusAuto
{
    my $self = shift;
    Debug( "Focus Auto" );
    my $cmd = "/ptz.cgi?Autofocus=on";
    $self->sendCmd( $cmd );
}

sub focusMan
{
    my $self = shift;
    Debug( "Focus Manual" );
    my $cmd = "/ptz.cgi?Autofocus=off";
    $self->sendCmd( $cmd );
}

sub irisRelOpen
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Iris Open" );
    my $cmd = "/ptz.cgi?riris=$step";
    $self->sendCmd( $cmd );
}

sub irisRelClose
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    Debug( "Iris Close" );
    my $cmd = "/ptz.cgi?riris=-$step";
    $self->sendCmd( $cmd );
}

sub irisAuto
{
    my $self = shift;
    Debug( "Iris Auto" );
    my $cmd = "/ptz.cgi?autoiris=on";
    $self->sendCmd( $cmd );
}

sub irisMan
{
    my $self = shift;
    Debug( "Iris Manual" );
    my $cmd = "/ptz.cgi?autoiris=off";
    $self->sendCmd( $cmd );
}

sub presetClear
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Debug( "Clear Preset $preset" );
    my $cmd = "/ptz.cgi?removeserverpresetno=$preset";
    $self->sendCmd( $cmd );
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Debug( "Goto Preset $preset" );
    my $cmd = "/ptz.cgi?gotoserverpresetno=$preset";
    $self->sendCmd( $cmd );
}

sub presetHome
{
    my $self = shift;
    Debug( "Home Preset" );
    my $cmd = "/ptz.cgi?move=home";
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

Juan Manuel Castro, E<lt>juanmanuel.castro@gmail.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2014 Juan Manuel Castro

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
