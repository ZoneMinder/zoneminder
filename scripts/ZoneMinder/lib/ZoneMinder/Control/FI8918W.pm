# Modified on Jun 19 2016 by PP
# Changes made
# 	- modified command to work properly and pick up credentials from Control Device
#	- the old script did not stop moving- added autostop 
#	  (note that mjpeg cameras have onestep but that is too granular)
#	-  You need to set "user=xxx&pwd=yyy" in the ControlDevice field (NOT usr like in Foscam HD)

# ==========================================================================
#
# ZoneMinder Foscam FI8918W IP Control Protocol Module, $Date: 2009-11-25 09:20:00 +0000 (Wed, 04 Nov 2009) $, $Revision: 0001 $
# Copyright (C) 2001-2008 Philip Coombes
# Modified for use with Foscam FI8918W IP Camera by Dave Harris
# Modified Feb 2011 by Howard Durdle (http://durdl.es/x) to:
#      fix horizontal panning, add presets and IR on/off
#      use Control Device field to pass username and password
# Modified May 2014 by Arun Horne (http://arunhorne.co.uk) to:
#      use HTTP basic auth as required by firmware 11.37.x.x upward
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
# This module contains the implementation of the Foscam FI8918W IP camera control
# protocol
#

package MyAgent;

use base 'LWP::UserAgent';


package ZoneMinder::Control::FI8918W;
 
use 5.006;
use strict;
use warnings;
 
require ZoneMinder::Base;
require ZoneMinder::Control;
 
our @ISA = qw(ZoneMinder::Control);
 
our $VERSION = $ZoneMinder::Base::VERSION;
 
# ==========================================================================
#
# Foscam FI8918W IP Control Protocol
#
# ==========================================================================
 
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
 
use Time::HiRes qw( usleep );
 
our $stop_command;
 
sub open
{
	my $self = shift;
 
	$self->loadMonitor();
 
	$self->{ua} = MyAgent->new;
	$self->{ua}->agent( "ZoneMinder Control Agent/" );
 
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
	printMsg( $cmd, "Tx" );
 
	# PP Old cameras also support onstep=1 but it is too granular. Instead using moveCon and stop after interval
	# PP - cleaned up URL to take it properly from Control device
	# Control device needs to be of format user=xxx&pwd=yyy
	my $req = HTTP::Request->new( GET=>"http://".$self->{Monitor}->{ControlAddress}."/$cmd"."&".$self->{Monitor}->{ControlDevice});
	print ("Sending $req\n");
	my $res = $self->{ua}->request($req);
 
	if ( $res->is_success )
	{
		$result = !undef;
	}
	else
	{
		Error( "Error REALLY check failed:'".$res->status_line()."'" );
		Error ("Cmd:".$req);
	}
 
	return( $result );
}
 
sub reset
{
	my $self = shift;
	Debug( "Camera Reset" );
	my $cmd = "reboot.cgi?";
	$self->sendCmd( $cmd );
}

# PP - in all move operations, added auto stop after timeout

#Up Arrow
sub moveConUp
{
	my $self = shift;
	Debug( "Move Up" );
	my $cmd = "decoder_control.cgi?command=0";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Down Arrow
sub moveConDown
{
	my $self = shift;
	Debug( "Move Down" );
	my $cmd = "decoder_control.cgi?command=2";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Left Arrow
sub moveConLeft
{
	my $self = shift;
	Debug( "Move Left" );
	my $cmd = "decoder_control.cgi?command=6";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Right Arrow
sub moveConRight
{
	my $self = shift;
	Debug( "Move Right" );
	my $cmd = "decoder_control.cgi?command=4";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Diagonally Up Right Arrow
sub moveConUpRight
{
	my $self = shift;
	Debug( "Move Diagonally Up Right" );
	my $cmd = "decoder_control.cgi?command=90";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );

}
 
#Diagonally Down Right Arrow
sub moveConDownRight
{
	my $self = shift;
	Debug( "Move Diagonally Down Right" );
	my $cmd = "decoder_control.cgi?command=92";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Diagonally Up Left Arrow
sub moveConUpLeft
{
	my $self = shift;
	Debug( "Move Diagonally Up Left" );
	my $cmd = "decoder_control.cgi?command=91";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Diagonally Down Left Arrow
sub moveConDownLeft
{
	my $self = shift;
	Debug( "Move Diagonally Down Left" );
	my $cmd = "decoder_control.cgi?command=93";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Stop
sub moveStop
{
	my $self = shift;
	Debug( "Move Stop" );
	my $cmd = "decoder_control.cgi?command=1";
	$self->sendCmd( $cmd );
}

# PP - imported from 9831 - autostop after usleep
sub autoStop
{
    my $self = shift;
    my $autostop = shift;
    if( $autostop )
    {
       Debug( "Auto Stop" );
       usleep( $autostop );
	my $cmd = "decoder_control.cgi?command=1";
       $self->sendCmd( $cmd );
    }
}
 
#Move Camera to Home Position
sub presetHome
{
	my $self = shift;
	Debug( "Home Preset" );
	my $cmd = "decoder_control.cgi?command=25";
	$self->sendCmd( $cmd );
}
 
#Set preset
sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
	my $presetCmd = 30 + ($preset*2);
    Debug( "Set Preset $preset with cmd $presetCmd" );
    my $cmd = "decoder_control.cgi?command=$presetCmd";
    $self->sendCmd( $cmd );
}
 
#Goto preset
sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    my $presetCmd = 31 + ($preset*2);
    Debug( "Goto Preset $preset with cmd $presetCmd" );
    my $cmd = "decoder_control.cgi?command=$presetCmd";
    $self->sendCmd( $cmd );
}
 
#Turn IR on
sub wake
{
	my $self = shift;
	Debug( "Wake - IR on" );
	my $cmd = "decoder_control.cgi?command=95";
	$self->sendCmd( $cmd );
}
 
#Turn IR off
sub sleep
{
	my $self = shift;
	Debug( "Sleep - IR off" );
	my $cmd = "decoder_control.cgi?command=94";
	$self->sendCmd( $cmd );
}
 
1;
__END__

=head1 FI8918W

ZoneMinder::Database - Perl extension for FOSCAM FI8918W

=head1 SYNOPSIS

Control script for Foscam MJPEG 8918W cameras. 

=head1 DESCRIPTION

You need to set "user=xxx&pwd=yyy" in the ControlDevice field
of the control tab for that monitor.
Auto TimeOut should be 1. Don't set it to less - processes
start crashing :)
NOTE: unlike HD foscam cameras, this one uses "user" not "usr"
in the control device

=head2 EXPORT

None by default.



=head1 SEE ALSO

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut

