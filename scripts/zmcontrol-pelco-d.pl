#!/usr/bin/perl -wT
#
# ==========================================================================
#
# ZoneMinder Pelco-D Control Script, $Date$, $Revision$
# Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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
# This script continuously monitors the recorded events for the given
# monitor and applies any filters which would delete and/or upload 
# matching events
#
use strict;

# ==========================================================================
#
# These are the elements you can edit to suit your installation
#
# ==========================================================================

use constant DBG_ID => "zmctrl-peld"; # Tag that appears in debug to identify source
use constant DBG_LEVEL => 0; # 0 is errors, warnings and info only, > 0 for debug

# ==========================================================================

use ZoneMinder;
use Getopt::Long;
use Device::SerialPort;
use Time::HiRes qw( usleep );

$| = 1;

$ENV{PATH}  = '/bin:/usr/bin';
$ENV{SHELL} = '/bin/sh' if exists $ENV{SHELL};
delete @ENV{qw(IFS CDPATH ENV BASH_ENV)};

sub Usage
{
	print( "
Usage: zmcontrol-pelco-d.pl <various options>
");
	exit( -1 );
}

zmDbgInit( DBG_ID, level=>DBG_LEVEL );

my $arg_string = join( " ", @ARGV );

my $device = "/dev/ttyS0";
my $address = 1;
my $command;
my $autostop;
my ( $speed, $step );
my ( $xcoord, $ycoord );
my ( $panspeed, $tiltspeed );
my ( $panstep, $tiltstep );
my $preset;

if ( !GetOptions(
	'device=s'=>\$device,
	'address=i'=>\$address,
	'command=s'=>\$command,
	'autostop=f'=>\$autostop,
	'speed=i'=>\$speed,
	'step=i'=>\$step,
	'xcoord=i'=>\$xcoord,
	'ycoord=i'=>\$ycoord,
	'panspeed=i'=>\$panspeed,
	'tiltspeed=i'=>\$tiltspeed,
	'panstep=i'=>\$panstep,
	'tiltstep=i'=>\$tiltstep,
	'preset=i'=>\$preset
	)
)
{
	Usage();
}

if ( defined($autostop) )
{
	# Convert to microseconds.
	$autostop = int(1000000*$autostop);
}

Debug( $arg_string."\n" );

srand( time() );

my $serial_port = new Device::SerialPort( $device );
$serial_port->baudrate(2400);
$serial_port->databits(8);
$serial_port->parity('none');
$serial_port->stopbits(1);
$serial_port->handshake('none');
 
$serial_port->read_const_time(50);
$serial_port->read_char_time(10);

sub printMsg
{
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
	$msg_str .= "[".$msg_len."]\n";
	Debug( $msg_str );
}

sub sendCmd
{
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

	printMsg( $cmd, "Tx" );
	my $id = $cmd->[0] & 0xf;

	my $tx_msg = pack( "C*", @$cmd );

	#print( "Tx: ".length( $tx_msg )." bytes\n" );
	my $n_bytes = $serial_port->write( $tx_msg );
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
		Debug( "Waiting for ack\n" );
		my $max_wait = 3;
		my $now = time();
		while( 1 )
		{
			my ( $count, $rx_msg ) = $serial_port->read(4);

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
						Debug( "Got ack for socket $socket\n" );
						$result = !undef;
					}
					else
					{
						Error( "Got bogus response\n" );
					}
					last;
				}
				else
				{
					Error( "Got message for camera ".(($resp[0]-0x80)>>4)."\n" );
				}
			}
			if ( (time() - $now) > $max_wait )
			{
				Warning( "Response timeout\n" );
				last;
			}
		}
	}
}

my $sync = 0xff;

sub remoteReset
{
	Debug( "Remote Reset\n" );
	my @msg = ( $sync, $address, 0x00, 0x0f, 0x00, 0x00 );
	sendCmd( \@msg );
}

sub resetDefaults
{
	Debug( "Reset Defaults\n" );
	my @msg = ( $sync, $address, 0x00, 0x29, 0x00, 0x00 );
	sendCmd( \@msg );
}

sub cameraOff
{
	Debug( "Camera Off\n" );
	my @msg = ( $sync, $address, 0x08, 0x00, 0x00, 0x00 );
	sendCmd( \@msg );
}

sub cameraOn
{
	Debug( "Camera On\n" );
	my @msg = ( $sync, $address, 0x88, 0x00, 0x00, 0x00 );
	sendCmd( \@msg );
}

sub autoScan
{
	Debug( "Auto Scan\n" );
	my @msg = ( $sync, $address, 0x90, 0x00, 0x00, 0x00 );
	sendCmd( \@msg );
}

sub manScan
{
	Debug( "Manual Scan\n" );
	my @msg = ( $sync, $address, 0x10, 0x00, 0x00, 0x00 );
	sendCmd( \@msg );
}

sub stop
{
	Debug( "Stop\n" );
	my @msg = ( $sync, $address, 0x00, 0x00, 0x00, 0x00 );
	sendCmd( \@msg );
}

sub moveUp
{
	Debug( "Move Up\n" );
	my $speed = shift || 0x3f;
	my @msg = ( $sync, $address, 0x00, 0x08, 0x00, $speed );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		stop();
	}
}

sub moveDown
{
	Debug( "Move Down\n" );
	my $speed = shift || 0x3f;
	my @msg = ( $sync, $address, 0x00, 0x10, 0x00, $speed );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		stop();
	}
}

sub moveLeft
{
	Debug( "Move Left\n" );
	my $speed = shift || 0x3f;
	my @msg = ( $sync, $address, 0x00, 0x04, $speed, 0x00 );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		stop();
	}
}

sub moveRight
{
	Debug( "Move Right\n" );
	my $speed = shift || 0x3f;
	my @msg = ( $sync, $address, 0x00, 0x02, $speed, 0x00 );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		stop();
	}
}

sub moveUpLeft
{
	Debug( "Move Up/Left\n" );
	my $panspeed = shift || 0x3f;
	my $tiltspeed = shift || 0x3f;
	my @msg = ( $sync, $address, 0x00, 0x0c, $panspeed, $tiltspeed );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		stop();
	}
}

sub moveUpRight
{
	Debug( "Move Up/Right\n" );
	my $panspeed = shift || 0x3f;
	my $tiltspeed = shift || 0x3f;
	my @msg = ( $sync, $address, 0x00, 0x0a, $panspeed, $tiltspeed );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		stop();
	}
}

sub moveDownLeft
{
	Debug( "Move Down/Left\n" );
	my $panspeed = shift || 0x3f;
	my $tiltspeed = shift || 0x3f;
	my @msg = ( $sync, $address, 0x00, 0x14, $panspeed, $tiltspeed );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		stop();
	}
}

sub moveDownRight
{
	Debug( "Move Down/Right\n" );
	my $panspeed = shift || 0x3f;
	my $tiltspeed = shift || 0x3f;
	my @msg = ( $sync, $address, 0x00, 0x12, $panspeed, $tiltspeed );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		stop();
	}
}

sub flip180
{
	Debug( "Flip 180\n" );
	my @msg = ( $sync, $address, 0x00, 0x07, 0x00, 0x21 );
	sendCmd( \@msg );
}

sub zeroPan
{
	Debug( "Zero Pan\n" );
	my @msg = ( $sync, $address, 0x00, 0x07, 0x00, 0x22 );
	sendCmd( \@msg );
}

sub setZoomSpeed
{
	my $speed = shift;
	my @msg = ( $sync, $address, 0x00, 0x25, 0x00, $speed );
	sendCmd( \@msg );
}

sub zoomStop
{
	stop();
	setZoomSpeed( 0 );
}

sub zoomTele
{
	Debug( "Zoom Tele\n" );
	my $speed = shift || 0x01;
	setZoomSpeed( $speed );
	usleep( 250000 );
	my @msg = ( $sync, $address, 0x00, 0x20, 0x00, 0x00 );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		zoomStop();
	}
}

sub zoomWide
{
	Debug( "Zoom Wide\n" );
	my $speed = shift || 0x01;
	setZoomSpeed( $speed );
	usleep( 250000 );
	my @msg = ( $sync, $address, 0x00, 0x40, 0x00, 0x00 );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		zoomStop();
	}
}

sub setFocusSpeed
{
	my $speed = shift;
	my @msg = ( $sync, $address, 0x00, 0x27, 0x00, $speed );
	sendCmd( \@msg );
}

sub focusNear
{
	Debug( "Focus Near\n" );
	my $speed = shift || 0x03;
	setFocusSpeed( $speed );
	usleep( 250000 );
	my @msg = ( $sync, $address, 0x01, 0x00, 0x00, 0x00 );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		setFocusSpeed( 0 );
	}
}

sub focusFar
{
	Debug( "Focus Far\n" );
	my $speed = shift || 0x03;
	setFocusSpeed( $speed );
	usleep( 250000 );
	my @msg = ( $sync, $address, 0x00, 0x80, 0x00, 0x00 );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		setFocusSpeed( 0 );
	}
}

sub focusAuto
{
	Debug( "Focus Auto\n" );
	my @msg = ( $sync, $address, 0x00, 0x2b, 0x00, 0x00 );
	sendCmd( \@msg );
}

sub focusMan
{
	Debug( "Focus Man\n" );
	my @msg = ( $sync, $address, 0x00, 0x2b, 0x00, 0x02 );
	sendCmd( \@msg );
}

sub irisClose
{
	Debug( "Iris Close\n" );
	my @msg = ( $sync, $address, 0x04, 0x00, 0x00, 0x00 );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		setIrisSpeed( 0 );
	}
}

sub irisOpen
{
	Debug( "Iris Open\n" );
	my @msg = ( $sync, $address, 0x02, 0x80, 0x00, 0x00 );
	sendCmd( \@msg );
	if ( $autostop )
	{
		usleep( $autostop );
		setIrisSpeed( 0 );
	}
}

sub irisAuto
{
	Debug( "Iris Auto\n" );
	my @msg = ( $sync, $address, 0x00, 0x2d, 0x00, 0x00 );
	sendCmd( \@msg );
}

sub irisMan
{
	Debug( "Iris Man\n" );
	my @msg = ( $sync, $address, 0x00, 0x2d, 0x00, 0x02 );
	sendCmd( \@msg );
}

sub writeScreen
{
	my $string = shift;
	Debug( "Writing '$string' to screen\n" );
	
	my @chars = unpack( "C*", $string );
	for ( my $i = 0; $i < length($string); $i++ )
	{
		#printf( "0x%02x\n", $chars[$i] );
		my @msg = ( $sync, $address, 0x00, 0x15, $i, $chars[$i] );
		sendCmd( \@msg );
	}
}

sub clearScreen
{
	Debug( "Clear Screen\n" );
	my @msg = ( $sync, $address, 0x00, 0x17, 0x00, 0x00 );
	sendCmd( \@msg );
}

sub clearPreset
{
	my $preset = shift || 1;
	Debug( "Clear Preset $preset\n" );
	my @msg = ( $sync, $address, 0x00, 0x05, 0x00, $preset );
	sendCmd( \@msg );
}

sub presetSet
{
	my $preset = shift || 1;
	Debug( "Set Preset $preset\n" );
	my @msg = ( $sync, $address, 0x00, 0x03, 0x00, $preset );
	sendCmd( \@msg );
}

sub presetGoto
{
	my $preset = shift || 1;
	Debug( "Goto Preset $preset\n" );
	my @msg = ( $sync, $address, 0x00, 0x07, 0x00, $preset );
	sendCmd( \@msg );
}

sub presetHome
{
	Debug( "Home Preset\n" );
	my @msg = ( $sync, $address, 0x00, 0x07, 0x00, 0x22 );
	sendCmd( \@msg );
}

if ( $command eq "reset" )
{
	remoteReset();
	resetDefaults();
}
elsif ( $command eq "wake" )
{
	cameraOn();
}
elsif ( $command eq "sleep" )
{
	cameraOff();
}
elsif ( $command eq "move_con_up" )
{
	moveUp( $tiltspeed );
}
elsif ( $command eq "move_con_down" )
{
	moveDown( $tiltspeed );
}
elsif ( $command eq "move_con_left" )
{
	moveLeft( $panspeed );
}
elsif ( $command eq "move_con_right" )
{
	moveRight( $panspeed );
}
elsif ( $command eq "move_con_upleft" )
{
	moveUpLeft( $panspeed, $tiltspeed );
}
elsif ( $command eq "move_con_upright" )
{
	moveUpRight( $panspeed, $tiltspeed );
}
elsif ( $command eq "move_con_downleft" )
{
	moveDownLeft( $panspeed, $tiltspeed );
}
elsif ( $command eq "move_con_downright" )
{
	moveDownRight( $panspeed, $tiltspeed );
}
elsif ( $command eq "move_stop" )
{
	stop();
}
elsif ( $command eq "zoom_con_tele" )
{
	zoomTele( $speed );
}
elsif ( $command eq "zoom_con_wide" )
{
	zoomWide( $speed );
}
elsif ( $command eq "zoom_stop" )
{
	zoomStop();
}
elsif ( $command eq "focus_con_near" )
{
	focusNear();
}
elsif ( $command eq "focus_con_far" )
{
	focusFar();
}
elsif ( $command eq "focus_stop" )
{
	stop();
	#setFocusSpeed( 0 );
}
elsif ( $command eq "focus_auto" )
{
	focusAuto();
}
elsif ( $command eq "focus_man" )
{
	focusMan();
}
elsif ( $command eq "iris_con_close" )
{
	irisClose();
}
elsif ( $command eq "iris_con_open" )
{
	irisOpen();
}
elsif ( $command eq "iris_stop" )
{
	stop();
}
elsif ( $command eq "iris_auto" )
{
	irisAuto();
}
elsif ( $command eq "iris_man" )
{
	irisMan();
}
elsif ( $command eq "preset_home" )
{
	presetHome();
}
elsif ( $command eq "preset_set" )
{
	presetSet( $preset );
}
elsif ( $command eq "preset_goto" )
{
	presetGoto( $preset );
}
else
{
	Error( "Can't handle command $command\n" );
}

$serial_port->close();
