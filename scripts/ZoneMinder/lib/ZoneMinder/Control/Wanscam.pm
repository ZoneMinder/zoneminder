# ==========================================================================
#
# ZoneMinder Wanscam Control Protocol Module, $Date: 2009-11-25 09:20:00 +0000 (Wed, 04 Nov 2009) $, $Revision: 0001 $
# Copyright (C) 2001-2008 Philip Coombes
# Modified for use with Foscam FI8918W IP Camera by Dave Harris
# Modified Feb 2011 by Howard Durdle (http://durdl.es/x) to:
#      fix horizontal panning, add presets and IR on/off
#      use Control Device field to pass username and password
# Modified June 5th, 2012 by Chris Bagwell to:
#   Rename to IPCAM since its common protocol with wide range of cameras.
#   Work with Logger module instead of Debug module.
#   Fix off-by-1 preset bug.
#   Support optional autostop timeout.
#   Add Zoom, Brightness, and Contrast support.
# Modified July 7th, 2012 by Patrik Brander to:
#   Rename to Wanscam
#   Pan Left/Right switched
#   IR On/Off switched
#   Brightness Increase/Decrease in 16 steps
#
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
# This module contains the implementation of the Wanscam camera control
# protocol.
#
# This is a protocol shared by a wide range of affordable cameras that
# appear to share similar reference design and software.  Examples
# include Foscam, Agasio, Wansview, etc.
#
# The basis for CGI based API can be found on internet by searching for
# "IPCAM CGI SDK 2.1". Here is sample site that also developes replacement
# firmware for some hardware versions.
#
# http://www.openipcam.com/files/Manuals/IPCAM%20CGI%20SDK%202.1.pdf
#
package ZoneMinder::Control::Wanscam;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Wanscam Control Protocol
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

    my $req = HTTP::Request->new( GET=>"http://".$self->{Monitor}->{ControlAddress}."/$cmd".$self->{Monitor}->{ControlDevice} );
    my $res = $self->{ua}->request($req);

    if ( $res->is_success )
    {
	$result = $res->decoded_content;
    }
    else
    {
	Error( "Error check failed:'".$res->status_line()."'" );
    }

    return( $result );
}

# Turn IO on (can be internally wired to IR's)
sub wake
{
    my $self = shift;
    Debug( "Wake - IO on" );
    my $cmd = "decoder_control.cgi?command=94&";
    $self->sendCmd( $cmd );
}

# Turn IO off (can be internally wired to IR's)
sub sleep
{
    my $self = shift;
    Debug( "Sleep - IO off" );
    my $cmd = "decoder_control.cgi?command=95&";
    $self->sendCmd( $cmd );
}

sub reset
{
    my $self = shift;
    Debug( "Camera Reset" );
    my $cmd = "reboot.cgi?";
    $self->sendCmd( $cmd );
}

sub moveConUp
{
    my $self = shift;
    my $params = shift;
    Debug( "Move Up" );
    my $cmd = "decoder_control.cgi?command=0&";
    $self->sendCmd( $cmd );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->moveStop( $params );
    }
}

sub moveConDown
{
    my $self = shift;
    my $params = shift;
    Debug( "Move Down" );
    my $cmd = "decoder_control.cgi?command=2&";
    $self->sendCmd( $cmd );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->moveStop( $params );
    }
}

sub moveConRight
{
    my $self = shift;
    my $params = shift;
    Debug( "Move Right" );
    my $cmd = "decoder_control.cgi?command=4&";
    $self->sendCmd( $cmd );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->moveStop( $params );
    }
}

sub moveConLeft
{
    my $self = shift;
    my $params = shift;
    Debug( "Move Left" );
    my $cmd = "decoder_control.cgi?command=6&";
    $self->sendCmd( $cmd );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->moveStop( $params );
    }
}

sub moveConUpLeft
{
    my $self = shift;
    my $params = shift;
    Debug( "Move Diagonally Up Left" );
    my $cmd = "decoder_control.cgi?command=91&";
    $self->sendCmd( $cmd );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->moveStop( $params );
    }
}

sub moveConDownLeft
{
    my $self = shift;
    my $params = shift;
    Debug( "Move Diagonally Down Left" );
    my $cmd = "decoder_control.cgi?command=93&";
    $self->sendCmd( $cmd );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->moveStop( $params );
    }
}

sub moveConUpRight
{
    my $self = shift;
    my $params = shift;
    Debug( "Move Diagonally Up Right" );
    my $cmd = "decoder_control.cgi?command=90&";
    $self->sendCmd( $cmd );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->moveStop( $params );
    }
}

sub moveConDownRight
{
    my $self = shift;
    my $params = shift;
    Debug( "Move Diagonally Down Right" );
    my $cmd = "decoder_control.cgi?command=92&";
    $self->sendCmd( $cmd );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->moveStop( $params );
    }
}

# command=1 is technically Up Stop but seems to work for all stops.
sub moveStop
{
    my $self = shift;
    Debug( "Move Stop" );
    print("autostop\n");
    my $cmd = "decoder_control.cgi?command=1&";
    $self->sendCmd( $cmd );
}

sub zoomConTele
{
    my $self = shift;
    my $params = shift;
    Debug( "Zoom Tele" );
    my $cmd = "decoder_control.cgi?command=16&";
    $self->sendCmd( $cmd );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
	$cmd = "decoder_control.cgi?command=17&";
	$self->sendCmd( $cmd );
    }
}

sub zoomConWide
{
    my $self = shift;
    my $params = shift;
    Debug( "Zoom Wide" );
    my $cmd = "decoder_control.cgi?command=18&";
    $self->sendCmd( $cmd );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
	$cmd = "decoder_control.cgi?command=19&";
	$self->sendCmd( $cmd );
    }
}

sub zoomConStop
{
    my $self = shift;
    my $params = shift;
    Debug( "Zoom Stop" );
    my $cmd = "decoder_control.cgi?command=17&";
    $self->sendCmd( $cmd );
}

# Increase Brightness
sub irisAbsOpen
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    my $brightness = 100;

    my $cmd = "get_camera_params.cgi?";
    my $resp = $self->sendCmd( $cmd );

    $brightness = int($1) if ( $resp =~ m/var brightness=([0-9]*);/ );
    $brightness += $step * 16;
    $brightness = 255 if ($brightness > 255);
    Debug( "Iris Open $brightness" );
    $cmd = "camera_control.cgi?param=1&value=".$brightness."&";
    $self->sendCmd( $cmd );
}

# Decrease Brightness
sub irisAbsClose
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    my $brightness = 100;

    my $cmd = "get_camera_params.cgi?";
    my $resp = $self->sendCmd( $cmd );

    $brightness = int($1) if ( $resp =~ m/var brightness=([0-9]*);/ );
    $brightness -= $step * 16;
    $brightness = 0 if ($brightness < 0);
    Debug( "Iris Close $brightness" );
    $cmd = "camera_control.cgi?param=1&value=".$brightness."&";
    $self->sendCmd( $cmd );
}

# Increase Contrast
sub whiteAbsIn
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    my $contrast = 5;

    my $cmd = "get_camera_params.cgi?";
    my $resp = $self->sendCmd( $cmd );

    $contrast = int($1) if ( $resp =~ m/var contrast=([0-9]*);/ );
    $contrast += $step;
    $contrast = 6 if ($contrast > 6);
    Debug( "White In $contrast" );
    $cmd = "camera_control.cgi?param=2&value=".$contrast."&";
    $self->sendCmd( $cmd );
}

# Decrease Contrast
sub whiteAbsOut
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'step' );
    my $contrast = 5;

    my $cmd = "get_camera_params.cgi?";
    my $resp = $self->sendCmd( $cmd );

    $contrast = int($1) if ( $resp =~ m/var contrast=([0-9]*);/ );
    $contrast -= $step;
    $contrast = 0 if ($contrast < 0);
    Debug( "White Out $contrast" );
    $cmd = "camera_control.cgi?param=2&value=".$contrast."&";
    $self->sendCmd( $cmd );
}

sub presetHome
{
    my $self = shift;
    Debug( "Home Preset" );
    my $cmd = "decoder_control.cgi?command=25&";
    $self->sendCmd( $cmd );
}

sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    my $presetCmd = 30 + (($preset-1)*2);
    Debug( "Set Preset $preset with cmd $presetCmd" );
    my $cmd = "decoder_control.cgi?command=$presetCmd&";
    $self->sendCmd( $cmd );
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    my $presetCmd = 31 + (($preset-1)*2);
    Debug( "Goto Preset $preset with cmd $presetCmd" );
    my $cmd = "decoder_control.cgi?command=$presetCmd&";
    $self->sendCmd( $cmd );
}
1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Database - Perl extension for blah blah blah

=head1 SYNOPSIS

  use ZoneMinder::Control::Wanscam
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

Philip Coombes, <philip.coombes@zoneminder.com>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
