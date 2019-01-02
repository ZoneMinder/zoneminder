# ==========================================================================
#
# ZoneMinder iPhone Control Protocol Module, $Date: 2018-07-15 00:20:00 +0000 $, $Revision: 0003 $
# Copyright (C) 2001-2008  Philip Coombes
#
# Modified for iPhone ipcamera for IOS BY PETER ZARGLIS n 2018-06-09 13:45:00
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
# This module contains the implementation of the iPhone ipcamera for IOS
# control protocol.
#
# ==========================================================================
package ZoneMinder::Control::IPCAMIOS;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# iPhone ipcamera for IOS Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
use Time::HiRes qw( usleep );

my $loopfactor=100000;

sub new
{
	my $class = shift;
	my $id = shift;
	my $self = ZoneMinder::Control->new( $id );
    my $logindetails = "";
    bless( $self, $class );
    srand( time() );
    return $self;
}

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
    Fatal( "Can't access $name member of object of class $class" );
}

sub open
{
    my $self = shift;

    $self->loadMonitor();

    use LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new;
    $self->{ua}->agent( "ZoneMinder Control Agent" );


    $self->{state} = 'open';
}

sub close
{
    my $self = shift;
    $self->{state} = 'closed';
}

sub sendCmd
{
    my $self = shift;
    my $cmd = shift;
    my $result = undef;
    my $req = HTTP::Request->new( GET=>"http://".$self->{Monitor}->{ControlAddress}."/$cmd" );
    my $res = $self->{ua}->request($req);
    if ( $res->is_success )
    {
        $result = $res->decoded_content;
    }
    else
    {
        Error( "Error check failed: '".$res->status_line()."'" );
    }
    return( $result );
}
sub getDisplayAttr
{
    my $self = shift;
    my $param = shift;
    my $cmdget = "parameters?";
    my $resp = $self->sendCmd( $cmdget );
    my @fields = split(',',$resp);
    my $response=$fields[$param];
    my @buffer=split(':',$response);
    my $response2=$buffer[1];
    return ($response2);
}

sub sleep
{

}
# Flip image vertically -> Horz -> off
sub moveConUp
{
    my $self = shift;
    my $params = shift;
    Debug( "Flip Image" );
    my $dvalue=$self->getDisplayAttr(3);
    if ( $dvalue == 2 )
    {
    $dvalue=0;
    my $cmd = "parameters?flip=$dvalue";
    $self->sendCmd( $cmd );
    }
    else
    {
    $dvalue=$dvalue+1;
    my $cmd = "parameters?flip=$dvalue";
    $self->sendCmd( $cmd );
    }
}
# Change camera (front facing or back)
sub moveConDown
{
   	my $self = shift;
    my $params = shift;
    Debug( "Change Camera" );
    my $dvalue=$self->getDisplayAttr(7);
    if ( $dvalue == 0 )
    {
    my $cmd = "parameters?camera=1";
    $self->sendCmd( $cmd );
    }
	else
	{
	my $cmd = "parameters?camera=0";
    $self->sendCmd( $cmd );
	}
}
# Picture Orientation Clockwise
sub moveConRight
{
	my $self = shift;
	my $params = shift;
	Debug( "Orientation" );
	my $dvalue=$self->getDisplayAttr(10);
	if ( $dvalue == 1 )
	{
	$dvalue=4;
	my $cmd = "parameters?rotation=$dvalue";
	$self->sendCmd( $cmd );
	}
	else 
	{	
	$dvalue=$dvalue-1;
	my $cmd = "parameters?rotation=$dvalue";
	$self->sendCmd( $cmd );
	}
}
# Picture Orientation Anti-Clockwise
sub moveConLeft
{
    my $self = shift;
    my $params = shift;
    Debug( "Orientation" );
    my $dvalue=$self->getDisplayAttr(10);
    if ( $dvalue == 4 )
	{
	$dvalue=1;
	my $cmd = "parameters?rotation=$dvalue";
	$self->sendCmd( $cmd );
	}
	else
	{
	$dvalue=$dvalue+1;
	my $cmd = "parameters?rotation=$dvalue";
	$self->sendCmd( $cmd );
	}
}

# presetHome is used to turn off Torch, unlock Focus, unlock Exposure, unlock white-balance, rotation, image flipping
# Just basically reset all the little variables and set it to medium quality
# Rotation = 0 means it will autoselect using built in detection
sub presetHome
{
	my $self = shift;
	Debug( "Home Preset" );
	my $cmd = "parameters?torch=0&focus=0&wb=0&exposure=0&rotation=0&flip=0&quality=0.5";
	$self->sendCmd( $cmd );
}

sub focusAbsNear
# Focus Un/Lock
{
	my $self = shift;
	my $params = shift;
	Debug( "Focus Un/Lock" );
	my $dvalue=$self->getDisplayAttr(2);
	if ( $dvalue == 0 )
	{
	my $cmd = "parameters?focus=1";
	$self->sendCmd( $cmd );
	}
	else
	{
	my $cmd = "parameters?focus=0";
	$self->sendCmd( $cmd );
	}
}

sub focusAbsFar
# Exposure Un/Lock
{
	my $self = shift;
	my $params = shift;
	Debug( "Exposure Un/Lock" );
	my $dvalue=$self->getDisplayAttr(11);
	if ( $dvalue == 0 )
	{
	my $cmd = "parameters?exposure=1";
	$self->sendCmd( $cmd );
	}
	else
	{
	my $cmd = "parameters?exposure=0";
	$self->sendCmd( $cmd );
	}
}
# Increase stream Quality (from 0 to 10)
sub irisAbsOpen
{
	my $self = shift;
	my $params = shift;
	Debug( "Quality" );
	my $dvalue=$self->getDisplayAttr(8);
	if ( $dvalue < 1 )
	{
	$dvalue=$dvalue+0.1;
	my $cmd = "parameters?quality=$dvalue";
	$self->sendCmd( $cmd );
	}
}

# Decrease stream Quality (from 10 to 0)
sub irisAbsClose
{
	my $self = shift;
	my $params = shift;
	Debug( "Quality" );
	my $dvalue=$self->getDisplayAttr(8);
	if ( $dvalue > 0 )
	{
	$dvalue=$dvalue-0.1;
	my $cmd = "parameters?quality=$dvalue";
	$self->sendCmd( $cmd );
	}
}
# White Balance Un/Lock
sub whiteAbsIn
{
	my $self = shift;
	my $params = shift;
	Debug( "White Balance" );
	my $dvalue=$self->getDisplayAttr(9);
	if ( $dvalue == 0 )
	{
	my $cmd = "parameters?wb=1";
	$self->sendCmd( $cmd );
	}
	else
	{
	my $cmd = "parameters?wb=0";
	$self->sendCmd( $cmd );
	}
}

# Torch control on/off
sub whiteAbsOut
{
	my $self = shift;
	my $params = shift;
	Debug( "Torch" );
	my $dvalue=$self->getDisplayAttr(5);
	if ( $dvalue == 0 )
	{
	my $cmd = "parameters?torch=1";
	$self->sendCmd( $cmd );
	}
	else
	{
	my $cmd = "parameters?torch=0";
	$self->sendCmd( $cmd );
	}
}

1;
