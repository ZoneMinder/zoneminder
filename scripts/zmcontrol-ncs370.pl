#!/usr/bin/perl -wT
#
# ==========================================================================
#
# ZoneMinder Neu-Fusion Control Script, $Date$, $Revision$
# Copyright (C) 2005 Richard Yeardley
# Portions Copyright (C) 2003, 2004, 2005  Philip Coombes
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

use constant DBG_ID => "zmctrl-ncs370"; # Tag that appears in debug to identify source
use constant DBG_LEVEL => 0; # 0 is errors, warnings and info only, > 0 for debug

use ZoneMinder;
use Getopt::Long;
use Device::SerialPort;

$| = 1;

$ENV{PATH}  = '/bin:/usr/bin';
$ENV{SHELL} = '/bin/sh' if exists $ENV{SHELL};
delete @ENV{qw(IFS CDPATH ENV BASH_ENV)};

sub Usage
{
	print( "
Usage: zmcontrol-ncs370.pl <various options>
");
	exit( -1 );
}

zmDbgInit( DBG_ID, level=>DBG_LEVEL );

my $arg_string = join( " ", @ARGV );

my $address;
my $command;
my ( $speed, $step );
my ( $xcoord, $ycoord );
my ( $width, $height );
my ( $panspeed, $tiltspeed );
my ( $panstep, $tiltstep );
my $preset;

if ( !GetOptions(
	'address=s'=>\$address,
	'command=s'=>\$command,
	'speed=i'=>\$speed,
	'step=i'=>\$step,
	'xcoord=i'=>\$xcoord,
	'ycoord=i'=>\$ycoord,
	'width=i'=>\$width,
	'height=i'=>\$height,
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

if ( !$address )
{
	Usage();
}

Debug( $arg_string."\n" );

srand( time() );

sub printMsg
{
	my $msg = shift;
	my $msg_len = length($msg);

	Debug( $msg."[".$msg_len."]\n" );
}

sub sendCmd
{
	my $cmd = shift;

	my $result = undef;

	printMsg( $cmd, "Tx" );

	use LWP::UserAgent;
	my $ua = LWP::UserAgent->new;
	$ua->agent( "ZoneMinder Control Agent/".ZM_VERSION );

	my $req = HTTP::Request->new( POST=>"http://$address/PANTILTCONTROL.CGI" );
	$req->content($cmd); 
	my $res = $ua->request($req);

	if ( $res->is_success )
	{
		$result = !undef;
	}
	else
	{
		Error( "Error check failed: '".$res->status_line()."'\n" );
	}

	return( $result );
}

sub cameraReset
{
	Debug( "Camera Reset\n" );
	my $cmd = "nphRestart?PAGE=Restart&Restart=OK";
	sendCmd( $cmd );
}

sub moveUp
{
	Debug( "Move Up\n" );
	my $cmd = "PanSingleMoveDegree=1\nTiltSingleMoveDegree=1\nPanTiltSingleMove=1";
	sendCmd( $cmd );
}

sub moveDown
{
	Debug( "Move Down\n" );
	my $cmd = "PanSingleMoveDegree=1\nTiltSingleMoveDegree=1\nPanTiltSingleMove=7";
	sendCmd( $cmd );
}

sub moveLeft
{
	Debug( "Move Left\n" );
	my $cmd = "PanSingleMoveDegree=1\nTiltSingleMoveDegree=1\nPanTiltSingleMove=3";
	sendCmd( $cmd );
}

sub moveRight
{
	Debug( "Move Right\n" );
	my $cmd = "PanSingleMoveDegree=1\nTiltSingleMoveDegree=1\nPanTiltSingleMove=5";
	sendCmd( $cmd );
}

sub moveUpRight
{
	moveUp();
	moveRight();
}

sub moveUpLeft
{
	moveUp();
	moveLeft();
}

sub moveDownRight
{
	moveDown();
	moveRight();
}

sub moveDownLeft
{
	moveDown();
	moveLeft();
}

sub moveMap
{
	my ( $xcoord, $ycoord, $width, $height ) = @_;
	Debug( "Move Map to $xcoord,$ycoord\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?center=$xcoord,$ycoord&imagewidth=$width&imageheight=$height";
	sendCmd( $cmd );
}

sub stepUp
{
	my $step = shift;
	Debug( "Step Up $step\n" );
	my $cmd = "PanSingleMoveDegree=1\nTiltSingleMoveDegree=$step\nPanTiltSingleMove=1";
	sendCmd( $cmd );
}

sub presetClear
{
	my $preset = shift || 1;
	Debug( "Clear Preset $preset\n" );
	my $cmd = "nphPresetNameCheck?Data=$preset";
	sendCmd( $cmd );
}

sub presetSet
{
	my $preset = shift || 1;
	Debug( "Set Preset $preset\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?setserverpresetno=$preset";
	sendCmd( $cmd );
}

sub presetGoto
{
	my $preset = shift || 1;
	Debug( "Goto Preset $preset\n" );
	my $cmd = "PanTiltPresetPositionMove=$preset";
	sendCmd( $cmd );
}

sub presetHome
{
	Debug( "Home Preset\n" );
	my $cmd = "PanSingleMoveDegree=1\nTiltSingleMoveDegree=1\nPanTiltSingleMove=4";
	sendCmd( $cmd );
}

if ( $command eq "move_con_up" )
{
	moveUp();
}
elsif ( $command eq "move_con_down" )
{
	moveDown();
}
elsif ( $command eq "move_con_left" )
{
	moveLeft();
}
elsif ( $command eq "move_con_right" )
{
	moveRight();
}
elsif ( $command eq "move_con_upleft" )
{
	moveUpLeft();
}
elsif ( $command eq "move_con_upright" )
{
	moveUpRight();
}
elsif ( $command eq "move_con_downleft" )
{
	moveDownLeft();
}
elsif ( $command eq "move_con_downright" )
{
	moveDownRight();
}
elsif ( $command eq "move_map" )
{
#	moveMap( $xcoord, $ycoord, $width, $height );
}
elsif ( $command eq "preset_home" )
{
	presetHome();
}
elsif ( $command eq "preset_set" )
{
#	presetSet( $preset );
}
elsif ( $command eq "preset_goto" )
{
	presetGoto( $preset );
}
else
{
	Error( "Can't handle command $command\n" );
}
