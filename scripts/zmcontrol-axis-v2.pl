#!/usr/bin/perl -wT
#
# ==========================================================================
#
# ZoneMinder Axis HTTP API v2 Control Script, $Date$, $Revision$
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

use constant DBG_ID => "zmctrl-axis"; # Tag that appears in debug to identify source
use constant DBG_LEVEL => 0; # 0 is errors, warnings and info only, > 0 for debug

# ==========================================================================

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
Usage: zmcontrol-axis-v2.pl <various options>
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

	#print( "http://$address/$cmd\n" );
	my $req = HTTP::Request->new( GET=>"http://$address/$cmd" );
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
	my $cmd = "/axis-cgi/com/ptz.cgi?move=up";
	sendCmd( $cmd );
}

sub moveDown
{
	Debug( "Move Down\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?move=down";
	sendCmd( $cmd );
}

sub moveLeft
{
	Debug( "Move Left\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?move=left";
	sendCmd( $cmd );
}

sub moveRight
{
	Debug( "Move Right\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?move=right";
	sendCmd( $cmd );
}

sub moveUpRight
{
	Debug( "Move Up/Right\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?move=upright";
	sendCmd( $cmd );
}

sub moveUpLeft
{
	Debug( "Move Up/Left\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?move=upleft";
	sendCmd( $cmd );
}

sub moveDownRight
{
	Debug( "Move Down/Right\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?move=downright";
	sendCmd( $cmd );
}

sub moveDownLeft
{
	Debug( "Move Down/Left\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?move=downleft";
	sendCmd( $cmd );
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
	my $cmd = "/axis-cgi/com/ptz.cgi?rtilt=$step";
	sendCmd( $cmd );
}

sub stepDown
{
	my $step = shift;
	Debug( "Step Down $step\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?rtilt=-$step";
	sendCmd( $cmd );
}

sub stepLeft
{
	my $step = shift;
	Debug( "Step Left $step\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?rpan=-$step";
	sendCmd( $cmd );
}

sub stepRight
{
	my $step = shift;
	Debug( "Step Right $step\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?rpan=$step";
	sendCmd( $cmd );
}

sub stepUpRight
{
	my $panstep = shift;
	my $tiltstep = shift;
	Debug( "Step Up/Right $tiltstep/$panstep\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?rpan=$panstep&rtilt=$tiltstep";
	sendCmd( $cmd );
}

sub stepUpLeft
{
	my $panstep = shift;
	my $tiltstep = shift;
	Debug( "Step Up/Left $tiltstep/$panstep\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?rpan=-$panstep&rtilt=$tiltstep";
	sendCmd( $cmd );
}

sub stepDownRight
{
	my $panstep = shift;
	my $tiltstep = shift;
	Debug( "Step Down/Right $tiltstep/$panstep\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?rpan=$panstep&rtilt=-$tiltstep";
	sendCmd( $cmd );
}

sub stepDownLeft
{
	my $panstep = shift;
	my $tiltstep = shift;
	Debug( "Step Down/Left $tiltstep/$panstep\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?rpan=-$panstep&rtilt=-$tiltstep";
	sendCmd( $cmd );
}

sub zoomTele
{
	my $step = shift;
	Debug( "Zoom Tele\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?rzoom=$step";
	sendCmd( $cmd );
}

sub zoomWide
{
	my $step = shift;
	Debug( "Zoom Wide\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?rzoom=-$step";
	sendCmd( $cmd );
}

sub focusNear
{
	my $step = shift;
	Debug( "Focus Near\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?rfocus=-$step";
	sendCmd( $cmd );
}

sub focusFar
{
	my $step = shift;
	Debug( "Focus Far\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?rfocus=$step";
	sendCmd( $cmd );
}

sub focusAuto
{
	Debug( "Focus Auto\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?autofocus=on";
	sendCmd( $cmd );
}

sub focusMan
{
	Debug( "Focus Manual\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?autofocus=off";
	sendCmd( $cmd );
}

sub irisOpen
{
	my $step = shift;
	Debug( "Iris Open\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?riris=$step";
	sendCmd( $cmd );
}

sub irisClose
{
	my $step = shift;
	Debug( "Iris Close\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?riris=-$step";
	sendCmd( $cmd );
}

sub irisAuto
{
	Debug( "Iris Auto\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?autoiris=on";
	sendCmd( $cmd );
}

sub irisMan
{
	Debug( "Iris Manual\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?autoiris=off";
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
	my $cmd = "/axis-cgi/com/ptz.cgi?gotoserverpresetno=$preset";
	sendCmd( $cmd );
}

sub presetHome
{
	Debug( "Home Preset\n" );
	my $cmd = "/axis-cgi/com/ptz.cgi?move=home";
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
	moveDownLeft();
}
elsif ( $command eq "move_map" )
{
	moveMap( $xcoord, $ycoord, $width, $height );
}
elsif ( $command eq "move_rel_up" )
{
	stepUp( $tiltstep );
}
elsif ( $command eq "move_rel_down" )
{
	stepDown( $tiltstep );
}
elsif ( $command eq "move_rel_left" )
{
	stepLeft( $panstep );
}
elsif ( $command eq "move_rel_right" )
{
	stepRight( $panstep );
}
elsif ( $command eq "move_rel_upleft" )
{
	stepUpLeft( $panstep, $tiltstep );
}
elsif ( $command eq "move_rel_upright" )
{
	stepUpRight( $panstep, $tiltstep );
}
elsif ( $command eq "move_rel_downleft" )
{
	stepDownLeft( $panstep, $tiltstep );
}
elsif ( $command eq "move_rel_downright" )
{
	stepDownRight( $panstep, $tiltstep );
}
elsif ( $command eq "zoom_rel_tele" )
{
	zoomTele( $step );
}
elsif ( $command eq "zoom_rel_wide" )
{
	zoomWide( $step );
}
elsif ( $command eq "focus_rel_near" )
{
	focusNear( $step );
}
elsif ( $command eq "focus_rel_far" )
{
	focusFar( $step );
}
elsif ( $command eq "focus_auto" )
{
	focusAuto();
}
elsif ( $command eq "focus_man" )
{
	focusMan();
}
elsif ( $command eq "iris_rel_open" )
{
	irisOpen( $step );
}
elsif ( $command eq "iris_rel_close" )
{
	irisClose( $step );
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
