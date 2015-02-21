# ==========================================================================
#
# ZoneMinder Foscam IP Camera Control Protocol Module, $Date$, $Revision$
# Copyright (C) 2001-2008 Philip Coombes
# Modified for use with Foscam FI8908W IP Camera by Dave Harris
# Modified to add preset, autostop, and IR on/off support by Daniel Rich
# Converted into a general Foscam IP Camera module with zoom, iris, and focus
#	Added handling for inverted cameras
#	by Daniel Rich
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
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
#
# ==========================================================================
#
package ZoneMinder::Control::FoscamIPCam;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Foscam IP Camera Control Protocol
#
# The Foscam IP Camera protocol is described at:
#    http://www.foscam.es/descarga/ipcam_cgi_sdk.pdf
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );

sub new
{ 
	my $class = shift;
	my $id = shift;
	my $self = ZoneMinder::Control->new( $id );
	bless( $self, $class );
	srand( time() );
	return $self;
}

our %FoscamCommands = (
	'moveConUp'        => 0,
	'moveStop'         => 1,
	'moveConDown'      => 2,
	'moveConLeft'      => 6,	# left/right reversed from the spec
	'moveConRight'     => 4,	# left/right reversed from the spec
	'moveConUpLeft'    => 91,	# left/right reversed from the spec
	'moveConUpRight'   => 90,	# left/right reversed from the spec
	'moveConDownLeft'  => 93,	# left/right reversed from the spec
	'moveConDownRight' => 92,	# left/right reversed from the spec
	'irisConClose'     => 8,
	'irisConOpen'      => 10,
	'irisStop'         => 9,
	'focusConNear'     => 12,
	'focusConFar'      => 14,
	'focusStop'        => 15,
	'zoomConTele'      => 16,
	'zoomConWide'      => 18,
	'zoomStop'         => 17,
	'presetSet'        => 30,
	'presetGoto'       => 31,
	'sleep'            => 94,	# IR off
	'wake'             => 95,	# IR on
);

our $AUTOLOAD;

sub AUTOLOAD
{
	my $self = shift;
	my $class = ref($self) || croak( "$self not object" );
	my $name = $AUTOLOAD;
	$name =~ s/.*://;
	if ( exists($self->{$name}) )
	{
		return( $self->{$name} );
	}
	elsif ( defined($FoscamCommands{$name}) )
	{
		return( $self->handleCommand($name) );
	}
	Fatal( "Can't access $name member of object of class $class" );
}

sub open
{
	my $self = shift;

	$self->loadMonitor();

	use LWP::UserAgent;
	$self->{ua} = LWP::UserAgent->new;
	$self->{ua}->agent( "ZoneMinder Control Agent/".ZoneMinder::Base::ZM_VERSION );

	$self->{state} = 'open';
}

sub close
{ 
	my $self = shift;
	$self->{state} = 'closed';
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

	my ($user, $password) = split /:/, $self->{Monitor}->{ControlDevice};

	if ( !defined $password ) {
		# If value of "Control device" does not consist of two parts, then only password is given and we fallback to default user:
		$password = $user;
		$user = 'admin';
	}

	$cmd .= "user=$user&pwd=$password";

	printMsg( $cmd, "Tx" );

	my $req = HTTP::Request->new( GET=>"http://".$self->{Monitor}->{ControlAddress}."/$cmd" );
	my $res = $self->{ua}->request($req);

	if ( $res->is_success )
	{
		$result = !undef;
	}
	else
	{
		Error( "Error check failed: '".$res->status_line()."' for URL ".$req->uri() );
	}

	return( $result );
}

sub reset
{
	my $self = shift;
	Debug( "Camera Reset" );
	$self->sendCmd( 'reboot.cgi?' );
}

# General Foscam command handler
sub handleCommand
{
	my $self = shift;
	my $command = shift;

	# Inverted camera, flip left/right, up/down move commands
	if ( $self->{Monitor}->{Orientation} == 180 and $command =~ /^move/ )
	{
		if ($command =~ /Up/)
		{
			$command  =~ s/Up/Down/;
		}
		else 
		{
			$command  =~ s/Down/Up/;
		}
		if ($command =~ /Left/)
		{
			$command  =~ s/Left/Right/;
		}
		else 
		{
			$command  =~ s/Right/Left/;
		}
	}
	Debug( $command );
	$self->sendCmd( 'decoder_control.cgi?command='. $FoscamCommands{$command} .'&' );
	if ( $self->{Monitor}->{AutoStopTimeout} )
	{
		usleep( $self->{Monitor}->{AutoStopTimeout} );
		my $stopCmd = $command;	# Figure out stop command from 
		$stopCmd =~ s/[A-Z].*$/Stop/;	#   base of current command
		if ( defined $FoscamCommands{$stopCmd} )
		{
			Debug( 'Autostop triggered' );
			$self->sendCmd( 'decoder_control.cgi?command='. $FoscamCommands{$stopCmd} .'&' );
		}
	}
}

#Move Camera to Home Position
sub presetHome
{
	my $self = shift;
	Debug( "Home Preset" );
	$self->sendCmd( 'decoder_control.cgi?command=25&' );
}

#Set preset position
sub presetSet
{
	my $self = shift;
	my $params = shift;
	my $preset = $self->getParam( $params, 'preset' );
	Debug( "Set Preset $preset" );
	my $cmdnum = $FoscamCommands{'presetSet'} + (($preset-1)*2);
	$self->sendCmd( 'decoder_control.cgi?command='. $cmdnum. '&' );
}

#Goto preset position
sub presetGoto
{
	my $self = shift;
	my $params = shift;
	my $preset = $self->getParam( $params, 'preset' );
	Debug( "Goto Preset $preset" );
	my $cmdnum = $FoscamCommands{'presetGoto'} + (($preset-1)*2);
	$self->sendCmd( 'decoder_control.cgi?command='. $cmdnum. '&' );
}

1;

__END__
=pod

=head1 DESCRIPTION

This module contains the implementation of the Foscam IP camera control
protocol.

The module uses "Control Device" value to retrieve user and password. User and password should
be separated by colon, e.g. user:password. If colon is not provided, then "admin" is used
as a fallback value for the user.
=cut
