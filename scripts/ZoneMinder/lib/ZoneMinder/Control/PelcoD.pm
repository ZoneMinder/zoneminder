# ==========================================================================
#
# ZoneMinder Pelco-D Control Protocol Module, $Date$, $Revision$
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
# This module contains the implementation of the Pelco-D camera control
# protocol
#
package ZoneMinder::Control::PelcoD;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Pelco-D Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);

use Time::HiRes qw( usleep );

use constant SYNC => 0xff;
use constant COMMAND_GAP => 100000; # In ms

sub open
{
    my $self = shift;

    $self->loadMonitor();

    use Device::SerialPort;
    $self->{port} = new Device::SerialPort( $self->{Monitor}->{ControlDevice} );
    $self->{port}->baudrate(2400);
    $self->{port}->databits(8);
    $self->{port}->parity('none');
    $self->{port}->stopbits(1);
    $self->{port}->handshake('none');

    $self->{port}->read_const_time(50);
    $self->{port}->read_char_time(10);

    $self->{state} = 'open';
}

sub close {
    my $self = shift;
    $self->{state} = 'closed';
    $self->{port}->close();
}

sub printMsg
{
    if ( logDebugging() )
    {
        my $self = shift;
        my $msg = shift;
        my $prefix = shift || "";
        $prefix = $prefix.": " if ( $prefix );

        my $line_length = 16;
        my $msg_len = int(@$msg);

        my $msg_str = $prefix;
        for ( my $i = 0; $i < $msg_len; $i++ )
        {
            if ( ($i > 0) && ($i%$line_length == 0) && ($i != ($msg_len-1)) )
            {
                $msg_str .= sprintf( "\n%*s", length($prefix), "" );
            }
            $msg_str .= sprintf( "%02x ", $msg->[$i] );
        }
        $msg_str .= "[".$msg_len."]";
        Debug( $msg_str );
    }
}

sub sendCmd
{
    my $self = shift;
    my $cmd = shift;
    my $ack = shift || 0;

    my $result = undef;

    my $checksum = 0x00;
    for ( my $i = 1; $i < int(@$cmd); $i++ )
    {
        $checksum += $cmd->[$i];
        $checksum &= 0xff;
    }
    push( @$cmd, $checksum );

    $self->printMsg( $cmd, "Tx" );
    my $id = $cmd->[0] & 0xf;

    my $tx_msg = pack( "C*", @$cmd );

    #print( "Tx: ".length( $tx_msg )." bytes\n" );
    my $n_bytes = $self->{port}->write( $tx_msg );
    if ( !$n_bytes )
    {
        Error( "Write failed: $!" );
    }
    if ( $n_bytes != length($tx_msg) )
    {
        Error( "Incomplete write, only ".$n_bytes." of ".length($tx_msg)." written: $!" );
    }

    if ( $ack )
    {
        Debug( "Waiting for ack" );
        my $max_wait = 3;
        my $now = time();
        while( 1 )
        {
            my ( $count, $rx_msg ) = $self->{port}->read(4);

            if ( $count )
            {
                #print( "Rx1: ".$count." bytes\n" );
                my @resp = unpack( "C*", $rx_msg );
                printMsg( \@resp, "Rx" );

                if ( $resp[0] = 0x80 + ($id<<4) )
                {
                    if ( ($resp[1] & 0xf0) == 0x40 )
                    {
                        my $socket = $resp[1] & 0x0f;
                        Debug( "Got ack for socket $socket" );
                        $result = !undef;
                    }
                    else
                    {
                        Error( "Got bogus response" );
                    }
                    last;
                }
                else
                {
                    Error( "Got message for camera ".(($resp[0]-0x80)>>4) );
                }
            }
            if ( (time() - $now) > $max_wait )
            {
                Warning( "Response timeout" );
                last;
            }
        }
    }
}

sub remoteReset
{
    my $self = shift;
    Debug( "Remote Reset" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x0f, 0x00, 0x00 );
    $self->sendCmd( \@msg );
}

sub resetDefaults
{
    my $self = shift;
    Debug( "Reset Defaults" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x29, 0x00, 0x00 );
    $self->sendCmd( \@msg );
}

sub cameraOff
{
    my $self = shift;
    Debug( "Camera Off" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x08, 0x00, 0x00, 0x00 );
    $self->sendCmd( \@msg );
}

sub cameraOn
{
    my $self = shift;
    Debug( "Camera On" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x88, 0x00, 0x00, 0x00 );
    $self->sendCmd( \@msg );
}

sub autoScan
{
    my $self = shift;
    Debug( "Auto Scan" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x90, 0x00, 0x00, 0x00 );
    $self->sendCmd( \@msg );
}

sub manScan
{
    my $self = shift;
    Debug( "Manual Scan" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x10, 0x00, 0x00, 0x00 );
    $self->sendCmd( \@msg );
}

sub stop
{
    my $self = shift;
    Debug( "Stop" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x00, 0x00, 0x00 );
    $self->sendCmd( \@msg );
}

sub moveConUp
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'tiltspeed' );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Up" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x08, 0x00, $speed );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop( $params );
    }
}

sub moveConDown
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'tiltspeed' );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Down" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x10, 0x00, $speed );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop();
    }
}

sub moveConLeft
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'panspeed' );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Left" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x04, $speed, 0x00 );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop();
    }
}

sub moveConRight
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'panspeed' );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Right" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x02, $speed, 0x00 );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop();
    }
}

sub moveConUpLeft
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed', 0x3f );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x3f );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Up/Left" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x0c, $panspeed, $tiltspeed );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop();
    }
}

sub moveConUpRight
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed', 0x3f );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x3f );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Up/Right" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x0a, $panspeed, $tiltspeed );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop();
    }
}

sub moveConDownLeft
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed', 0x3f );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x3f );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Down/Left" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x14, $panspeed, $tiltspeed );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop();
    }
}

sub moveConDownRight
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed', 0x3f );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x3f );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Down/Right" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x12, $panspeed, $tiltspeed );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop();
    }
}

sub moveStop
{
    my $self = shift;
    Debug( "Move Stop" );
    $self->stop();
}

sub flip180
{
    my $self = shift;
    Debug( "Flip 180" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x07, 0x00, 0x21 );
    $self->sendCmd( \@msg );
}

sub zeroPan
{
    my $self = shift;
    Debug( "Zero Pan" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x07, 0x00, 0x22 );
    $self->sendCmd( \@msg );
}

sub _setZoomSpeed
{
    my $self = shift;
    my $speed = shift;
    Debug( "Set Zoom Speed $speed" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x25, 0x00, $speed );
    $self->sendCmd( \@msg );
}

sub zoomStop
{
    my $self = shift;
    Debug( "Zoom Stop" );
    $self->stop();
    $self->_setZoomSpeed( 0 );
}

sub zoomConTele
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed', 0x01 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Zoom Tele" );
    $self->_setZoomSpeed( $speed );
    usleep( COMMAND_GAP );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x20, 0x00, 0x00 );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->zoomStop();
    }
}

sub zoomConWide
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed', 0x01 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Zoom Wide" );
    $self->_setZoomSpeed( $speed );
    usleep( COMMAND_GAP );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x40, 0x00, 0x00 );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->zoomStop();
    }
}

sub _setFocusSpeed
{
    my $self = shift;
    my $speed = shift;
    Debug( "Set Focus Speed $speed" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x27, 0x00, $speed );
    $self->sendCmd( \@msg );
}

sub focusConNear
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed', 0x03 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Focus Near" );
    $self->_setFocusSpeed( $speed );
    usleep( COMMAND_GAP );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x01, 0x00, 0x00, 0x00 );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->_setFocusSpeed( 0 );
    }
}

sub focusConFar
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed', 0x03 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Focus Far" );
    $self->_setFocusSpeed( $speed );
    usleep( COMMAND_GAP );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x80, 0x00, 0x00 );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->_setFocusSpeed( 0 );
    }
}

sub focusStop
{
    my $self = shift;
    Debug( "Focus Stop" );
    $self->stop();
    $self->_setFocusSpeed( 0 );
}

sub focusAuto
{
    my $self = shift;
    Debug( "Focus Auto" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x2b, 0x00, 0x00 );
    $self->sendCmd( \@msg );
}

sub focusMan
{
    my $self = shift;
    Debug( "Focus Man" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x2b, 0x00, 0x02 );
    $self->sendCmd( \@msg );
}

sub _setIrisSpeed
{
    my $self = shift;
    my $speed = shift;
    Debug( "Set Iris Speed $speed" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x27, 0x00, $speed );
    $self->sendCmd( \@msg );
}

sub irisConClose
{
    my $self = shift;
    my $params = shift;
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Iris Close" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x04, 0x00, 0x00, 0x00 );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->_setIrisSpeed( 0 );
    }
}

sub irisConOpen
{
    my $self = shift;
    my $params = shift;
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Iris Open" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x02, 0x80, 0x00, 0x00 );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->_setIrisSpeed( 0 );
    }
}

sub irisStop
{
    my $self = shift;
    Debug( "Iris Stop" );
    $self->stop();
    $self->_setIrisSpeed( 0 );
}

sub irisAuto
{
    my $self = shift;
    Debug( "Iris Auto" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x2d, 0x00, 0x00 );
    $self->sendCmd( \@msg );
}

sub irisMan
{
    my $self = shift;
    Debug( "Iris Man" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x2d, 0x00, 0x02 );
    $self->sendCmd( \@msg );
}

sub writeScreen
{
    my $self = shift;
    my $params = shift;
    my $string = $self->getParam( $params, 'string' );
    Debug( "Writing '$string' to screen" );
    
    my @chars = unpack( "C*", $string );
    for ( my $i = 0; $i < length($string); $i++ )
    {
        my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x15, $i, $chars[$i] );
        $self->sendCmd( \@msg );
        usleep( COMMAND_GAP );
    }
}

sub clearScreen
{
    my $self = shift;
    Debug( "Clear Screen" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x17, 0x00, 0x00 );
    $self->sendCmd( \@msg );
}

sub clearPreset
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset', 1 );
    Debug( "Clear Preset $preset" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x05, 0x00, $preset );
    $self->sendCmd( \@msg );
}

sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset', 1 );
    Debug( "Set Preset $preset" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x03, 0x00, $preset );
    $self->sendCmd( \@msg );
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset', 1 );
    Debug( "Goto Preset $preset" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x07, 0x00, $preset );
    $self->sendCmd( \@msg );
}

sub presetHome
{
    my $self = shift;
    Debug( "Home Preset" );
    my @msg = ( SYNC, $self->{Monitor}->{ControlAddress}, 0x00, 0x07, 0x00, 0x22 );
    $self->sendCmd( \@msg );
}

sub reset
{
    my $self = shift;
    Debug( "Reset" );
    $self->remoteReset();
    $self->resetDefaults();
}

sub wake
{
    my $self = shift;
    Debug( "Wake" );
    $self->cameraOn();
}

sub sleep
{
    my $self = shift;
    Debug( "Sleep" );
    $self->cameraOff();
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
