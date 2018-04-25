# ==========================================================================
#
# ZoneMinder Visca Control Protocol Module, $Date$, $Revision$
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
# This module contains the implementation of the Visca camera control
# protocol
#
package ZoneMinder::Control::Visca;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Visca Control Protocol
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
    $self->{port}->baudrate(9600);
    $self->{port}->databits(8);
    $self->{port}->parity('none');
    $self->{port}->stopbits(1);
    $self->{port}->handshake('rts');
    $self->{port}->stty_echo(0);

    #$self->{port}->read_const_time(250);
    $self->{port}->read_char_time(2);

    $self->{state} = 'open';
}

sub close
{
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
    my $cmp = shift || 0;

    my $result = undef;

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
                $self->printMsg( \@resp, "Rx" );

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
                last;
            }
        }
    }

    if ( $cmp )
    {
        Debug( "Waiting for command complete" );
        my $max_wait = 10;
        my $now = time();
        while( 1 )
        {
            #print( "Waiting\n" );
            my ( $count, $rx_msg ) = $self->{port}->read(16);

            if ( $count )
            {
                #print( "Rx1: ".$count." bytes\n" );
                my @resp = unpack( "C*", $rx_msg );
                $self->printMsg( \@resp, "Rx" );

                if ( $resp[0] = 0x80 + ($id<<4) )
                {
                    if ( ($resp[1] & 0xf0) == 0x50 )
                    {
                        Debug( "Got command complete" );
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
                last;
            }
        }
    }
    return( $result );
}

sub cameraOff
{
    my $self = shift;
    Debug( "Camera Off\n" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x00, 0x0, SYNC );
    $self->sendCmd( \@msg );
}

sub cameraOn
{
    my $self = shift;
    Debug( "Camera On\n" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x00, 0x2, SYNC );
    $self->sendCmd( \@msg );
}

sub stop
{
    my $self = shift;
    Debug( "Stop\n" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x01, 0x00, 0x00, 0x03, 0x03, SYNC );
    $self->sendCmd( \@msg );
}

sub moveConUp
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'tiltspeed', 0x40 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Up" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x01, 0x00, $speed, 0x03, 0x01, SYNC );
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
    my $speed = $self->getParam( $params, 'tiltspeed', 0x40 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Down" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x01, 0x00, $speed, 0x03, 0x02, SYNC );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop( $params );
    }
}

sub movConLeft
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'panspeed', 0x40 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Left" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x01, $speed, 0x00, 0x01, 0x03, SYNC );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop( $params );
    }
}

sub moveConRight
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'panspeed', 0x40 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Right" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x01, $speed, 0x00, 0x02, 0x03, SYNC );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop( $params );
    }
}

sub moveUpLeft
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed', 0x40 );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x40 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Up/Left" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x01, $panspeed, $tiltspeed, 0x01, 0x01, SYNC );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop( $params );
    }
}

sub moveUpRight
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed', 0x40 );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x40 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Up/Right" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x01, $panspeed, $tiltspeed, 0x02, 0x01, SYNC );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop( $params );
    }
}

sub moveDownLeft
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed', 0x40 );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x40 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Down/Left" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x01, $panspeed, $tiltspeed, 0x01, 0x02, SYNC );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop( $params );
    }
}

sub moveDownRight
{
    my $self = shift;
    my $params = shift;
    my $panspeed = $self->getParam( $params, 'panspeed', 0x40 );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x40 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Move Down/Right" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x01, $panspeed, $tiltspeed, 0x02, 0x02, SYNC );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->stop( $params );
    }
}

sub moveRelUp
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'tiltstep' );
    my $speed = $self->getParam( $params, 'tiltspeed', 0x40 );
    Debug( "Step Up" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x03, 0x00, $speed, 0x00, 0x00, 0x00, 0x00, ($step&0xf000)>>12, ($step&0x0f00)>>8, ($step&0x00f0)>>4, ($step&0x000f)>>0, SYNC );

    $self->sendCmd( \@msg );
}

sub moveRelDown
{
    my $self = shift;
    my $params = shift;
    my $step = -$self->getParam( $params, 'tiltstep' );
    my $speed = $self->getParam( $params, 'tiltspeed', 0x40 );
    Debug( "Step Down" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x03, 0x00, $speed, 0x00, 0x00, 0x00, 0x00, ($step&0xf000)>>12, ($step&0x0f00)>>8, ($step&0x00f0)>>4, ($step&0x000f)>>0, SYNC );
    $self->sendCmd( \@msg );
}

sub moveRelLeft
{
    my $self = shift;
    my $params = shift;
    my $step = -$self->getParam( $params, 'panstep' );
    my $speed = $self->getParam( $params, 'panspeed', 0x40 );
    Debug( "Step Left" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x03, $speed, 0x00, ($step&0xf000)>>12, ($step&0x0f00)>>8, ($step&0x00f0)>>4, ($step&0x000f)>>0, 0x00, 0x00, 0x00, 0x00, SYNC );
    $self->sendCmd( \@msg );
}

sub moveRelRight
{
    my $self = shift;
    my $params = shift;
    my $step = $self->getParam( $params, 'panstep' );
    my $speed = $self->getParam( $params, 'panspeed', 0x40 );
    Debug( "Step Right" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x03, $speed, 0x00, ($step&0xf000)>>12, ($step&0x0f00)>>8, ($step&0x00f0)>>4, ($step&0x000f)>>0, 0x00, 0x00, 0x00, 0x00, SYNC );
    $self->sendCmd( \@msg );
}

sub moveRelUpLeft
{
    my $self = shift;
    my $params = shift;
    my $panstep = -$self->getParam( $params, 'panstep' );
    my $tiltstep = $self->getParam( $params, 'tiltstep' );
    my $panspeed = $self->getParam( $params, 'panspeed', 0x40 );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x40 );
    Debug( "Step Up/Left" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x03, $panspeed, $tiltspeed, ($panstep&0xf000)>>12, ($panstep&0x0f00)>>8, ($panstep&0x00f0)>>4, ($panstep&0x000f)>>0, ($tiltstep&0xf000)>>12, ($tiltstep&0x0f00)>>8, ($tiltstep&0x00f0)>>4, ($tiltstep&0x000f)>>0, SYNC );
    $self->sendCmd( \@msg );
}

sub moveRelUpRight
{
    my $self = shift;
    my $params = shift;
    my $panstep = $self->getParam( $params, 'panstep' );
    my $tiltstep = $self->getParam( $params, 'tiltstep' );
    my $panspeed = $self->getParam( $params, 'panspeed', 0x40 );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x40 );
    Debug( "Step Up/Right" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x03, $panspeed, $tiltspeed, ($panstep&0xf000)>>12, ($panstep&0x0f00)>>8, ($panstep&0x00f0)>>4, ($panstep&0x000f)>>0, ($tiltstep&0xf000)>>12, ($tiltstep&0x0f00)>>8, ($tiltstep&0x00f0)>>4, ($tiltstep&0x000f)>>0, SYNC );
    $self->sendCmd( \@msg );
}

sub moveRelDownLeft
{
    my $self = shift;
    my $params = shift;
    my $panstep = -$self->getParam( $params, 'panstep' );
    my $tiltstep = -$self->getParam( $params, 'tiltstep' );
    my $panspeed = $self->getParam( $params, 'panspeed', 0x40 );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x40 );
    Debug( "Step Down/Left" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x03, $panspeed, $tiltspeed, ($panstep&0xf000)>>12, ($panstep&0x0f00)>>8, ($panstep&0x00f0)>>4, ($panstep&0x000f)>>0, ($tiltstep&0xf000)>>12, ($tiltstep&0x0f00)>>8, ($tiltstep&0x00f0)>>4, ($tiltstep&0x000f)>>0, SYNC );
    $self->sendCmd( \@msg );
}

sub moveRelDownRight
{
    my $self = shift;
    my $params = shift;
    my $panstep = $self->getParam( $params, 'panstep' );
    my $tiltstep = -$self->getParam( $params, 'tiltstep' );
    my $panspeed = $self->getParam( $params, 'panspeed', 0x40 );
    my $tiltspeed = $self->getParam( $params, 'tiltspeed', 0x40 );
    Debug( "Step Down/Right" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x03, $panspeed, $tiltspeed, ($panstep&0xf000)>>12, ($panstep&0x0f00)>>8, ($panstep&0x00f0)>>4, ($panstep&0x000f)>>0, ($tiltstep&0xf000)>>12, ($tiltstep&0x0f00)>>8, ($tiltstep&0x00f0)>>4, ($tiltstep&0x000f)>>0, SYNC );
    $self->sendCmd( \@msg );
}

sub zoomConTele
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed', 0x06 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Zoom Tele" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x07, 0x20|$speed, SYNC );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->zoomStop();
    }
}

sub zoomWide
{
    my $self = shift;
    my $params = shift;
    my $speed = $self->getParam( $params, 'speed', 0x06 );
    my $autostop = $self->getParam( $params, 'autostop', 0 );
    Debug( "Zoom Wide" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x07, 0x30|$speed, SYNC );
    $self->sendCmd( \@msg );
    if( $autostop && $self->{Monitor}->{AutoStopTimeout} )
    {
        usleep( $self->{Monitor}->{AutoStopTimeout} );
        $self->zoomStop();
    }
}

sub zoomStop
{
    my $self = shift;
    my $params = shift;
    Debug( "Zoom Stop" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x07, 0x00, SYNC );
    $self->sendCmd( \@msg );
}

sub focusConNear
{
    my $self = shift;
    my $params = shift;
    Debug( "Focus Near" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x08, 0x03, SYNC );
    $self->sendCmd( \@msg );
}

sub focusConFar
{
    my $self = shift;
    my $params = shift;
    Debug( "Focus Far" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x08, 0x02, SYNC );
    $self->sendCmd( \@msg );
}

sub focusStop
{
    my $self = shift;
    my $params = shift;
    Debug( "Focus Stop" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x08, 0x00, SYNC );
    $self->sendCmd( \@msg );
}

sub focusAuto
{
    my $self = shift;
    my $params = shift;
    Debug( "Focus Auto" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x38, 0x02, SYNC );
    $self->sendCmd( \@msg );
}

sub focusMan
{
    my $self = shift;
    my $params = shift;
    Debug( "Focus Man" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x38, 0x03, SYNC );
    $self->sendCmd( \@msg );
}

sub presetClear
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset', 1 );
    Debug( "Clear Preset $preset" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x3f, 0x00, $preset, SYNC );
    $self->sendCmd( \@msg );
}

sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset', 1 );
    Debug( "Set Preset $preset" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x3f, 0x01, $preset, SYNC );
    $self->sendCmd( \@msg );
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset', 1 );
    Debug( "Goto Preset $preset" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x04, 0x3f, 0x02, $preset, SYNC );
    $self->sendCmd( \@msg );
}

sub presetHome
{
    my $self = shift;
    my $params = shift;
    Debug( "Home Preset" );
    my @msg = ( 0x80|$self->{Monitor}->{ControlAddress}, 0x01, 0x06, 0x04, SYNC );
    $self->sendCmd( \@msg );
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
