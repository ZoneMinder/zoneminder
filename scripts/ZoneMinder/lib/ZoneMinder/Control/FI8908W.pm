# ==========================================================================
#
# ZoneMinder Foscam FI8908W / FI8918W IP Control Protocol Module, $Date$, $Revision$
# Copyright (C) 2001-2008 Philip Coombes
# Modified for use with Foscam FI8908W IP Camera by Dave Harris
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
package ZoneMinder::Control::FI8908W;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Foscam FI8908W IP Control Protocol
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

#Up Arrow
sub moveConUp
{
    my $self = shift;
    Debug( "Move Up" );
    $self->sendCmd( 'decoder_control.cgi?command=0&' );
}

#Down Arrow
sub moveConDown
{
    my $self = shift;
    Debug( "Move Down" );
    $self->sendCmd( 'decoder_control.cgi?command=2&' );
}

#Left Arrow
sub moveConLeft
{
    my $self = shift;
    Debug( "Move Left" );
    $self->sendCmd( 'decoder_control.cgi?command=6&' );
}

#Right Arrow
sub moveConRight
{
    my $self = shift;
    Debug( "Move Right" );
    $self->sendCmd( 'decoder_control.cgi?command=4&' );
}

#Diagonally Up Right Arrow
sub moveConUpRight
{
    my $self = shift;
    Debug( "Move Diagonally Up Right" );
    $self->sendCmd( 'decoder_control.cgi?command=90&' );
}

#Diagonally Down Right Arrow
sub moveConDownRight
{
    my $self = shift;
    Debug( "Move Diagonally Down Right" );
    $self->sendCmd( 'decoder_control.cgi?command=92&' );
}

#Diagonally Up Left Arrow
sub moveConUpLeft
{
    my $self = shift;
    Debug( "Move Diagonally Up Left" );
    $self->sendCmd( 'decoder_control.cgi?command=91&' );
}

#Diagonally Down Left Arrow
sub moveConDownLeft
{
    my $self = shift;
    Debug( "Move Diagonally Down Left" );
    $self->sendCmd( 'decoder_control.cgi?command=93&' );
}

#Stop
sub moveStop
{
    my $self = shift;
    Debug( "Move Stop" );
    $self->sendCmd( 'decoder_control.cgi?command=1&' );
}

#Move Camera to Home Position
sub presetHome
{
    my $self = shift;
    Debug( "Home Preset" );
    $self->sendCmd( 'decoder_control.cgi?command=25&' );
}

sub moveRelUp
{
    my $self = shift;
    Debug( "Move Up" );
    $self->sendCmd( 'decoder_control.cgi?command=0&onestep=1&' );
}

#Down Arrow
sub moveRelDown
{
    my $self = shift;
    Debug( "Move Down" );
    $self->sendCmd( 'decoder_control.cgi?command=2&onestep=1&' );
}

#Left Arrow
sub moveRelLeft
{
    my $self = shift;
    Debug( "Move Left" );
    $self->sendCmd( 'decoder_control.cgi?command=6&onestep=1&' );
}

#Right Arrow
sub moveRelRight
{
    my $self = shift;
    Debug( "Move Right" );
    $self->sendCmd( 'decoder_control.cgi?command=4&onestep=1&' );
}

#Go to preset
sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    my $result = undef;
    if ( $preset > 0 && $preset <= 32 ) {
        my $command=31+(($preset-1) * 2);
        Debug( "Goto Preset $preset with command $command" );
        $result=$self->sendCmd( 'decoder_control.cgi?command=' . $command . '&' );
    }
    else {
        Error( "Unsupported preset $preset : must be between 1 and 32" );
    }
    return $result;
}

1;

__END__
=pod

=head1 NAME

ZoneMinder::Control::FI8908W - Foscam FI8908W camera control

=head1 DESCRIPTION

This module contains the implementation of the Foscam FI8908W / FI8918W IP
camera control protocol.

The module uses "Control Device" value to retrieve user and password. User
and password should be separated by colon, e.g. user:password. If colon is
not provided, then "admin" is used as a fallback value for the user.

=cut
