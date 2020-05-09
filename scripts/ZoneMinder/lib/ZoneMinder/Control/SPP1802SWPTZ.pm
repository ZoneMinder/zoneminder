# ==========================================================================
#
# ZoneMinder SunEyes SP-P1802SWPTZ IP Control Protocol Module, $Date: 2017-03-19 23:00:00 +1000 (Sat, 19 March 2017) $, $Revision: 0002 $
# Copyright (C) 2001-2008 Philip Coombes
# Modified for use with Foscam FI8918W IP Camera by Dave Harris
# Modified Feb 2011 by Howard Durdle (http://durdl.es/x) to:
#      fix horizontal panning, add presets and IR on/off
#      use Control Device field to pass username and password
# Modified May 2014 by Arun Horne (http://arunhorne.co.uk) to:
#      use HTTP basic auth as required by firmware 11.37.x.x upward
# Modified on Sep 28 2015 by Bobby Billingsley
# Changes made
#	- Copied FI8918W.pm to SPP1802SWPTZ.pm
#	- modified to control a SunEyes SP-P1802SWPTZ
# Modified on 13 March 2017 by Steve Gilvarry
#	-Address license and copyright issues
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
# This module contains the implementation of the SunEyes SP-P1802SWPTZ IP
# camera control protocol
#

package MyAgent;

use base 'LWP::UserAgent';


package ZoneMinder::Control::SPP1802SWPTZ;
 
use 5.006;
use strict;
use warnings;
 
require ZoneMinder::Base;
require ZoneMinder::Control;
 
our @ISA = qw(ZoneMinder::Control);
 
our $VERSION = $ZoneMinder::Base::VERSION;
 
# ==========================================================================
#
# SunEyes SP-P1802SWPTZ IP Control Protocol
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
	my $res = $self->{ua}->request($req);
 
	if ( $res->is_success )
	{
		$result = !undef;
	}
	else
	{
		Error( "Error really, REALLY check failed:'".$res->status_line()."'" );
		Error ("Cmd:".$cmd);
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
	my $cmd = "cgi-bin/hi3510/ptzctrl.cgi?&-chn=0&-act=up";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Down Arrow
sub moveConDown
{
	my $self = shift;
	Debug( "Move Down" );
	my $cmd = "cgi-bin/hi3510/ptzctrl.cgi?&-chn=0&-act=down";
	$self->sendCmd( $cmd );
}
 
#Left Arrow
sub moveConLeft
{
	my $self = shift;
	Debug( "Move Left" );
	my $cmd = "cgi-bin/hi3510/ptzctrl.cgi?&-chn=0&-act=left";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Right Arrow
sub moveConRight
{
	my $self = shift;
	Debug( "Move Right" );
	my $cmd = "cgi-bin/hi3510/ptzctrl.cgi?&-chn=0&-act=right";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Diagonally Up Right Arrow
sub moveConUpRight
{
	my $self = shift;
	Debug( "Move Diagonally Up Right" );
	foreach my $dir ("up","right") {
		my $cmd = "cgi-bin/hi3510/ptzctrl.cgi?&-chn=0&-act=$dir";
		$self->sendCmd( $cmd );
		$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
	}
}
 
#Diagonally Down Right Arrow
sub moveConDownRight
{
	my $self = shift;
	Debug( "Move Diagonally Down Right" );
	foreach my $dir ("down","right") {
		my $cmd = "cgi-bin/hi3510/ptzctrl.cgi?&-chn=0&-act=$dir";
		$self->sendCmd( $cmd );
		$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
	}
}
 
#Diagonally Up Left Arrow
sub moveConUpLeft
{
	my $self = shift;
	Debug( "Move Diagonally Up Left" );
	foreach my $dir ("up","left") {
		my $cmd = "cgi-bin/hi3510/ptzctrl.cgi?&-chn=0&-act=$dir";
		$self->sendCmd( $cmd );
		$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
	}
}
 
#Diagonally Down Left Arrow
sub moveConDownLeft
{
	my $self = shift;
	Debug( "Move Diagonally Down Left" );
	foreach my $dir ("down","left") {
		my $cmd = "cgi-bin/hi3510/ptzctrl.cgi?&-chn=0&-act=$dir";
		$self->sendCmd( $cmd );
		$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
	}
}
 
#Stop
sub moveStop
{
	my $self = shift;
	Debug( "Move Stop" );
	my $cmd = "cgi-bin/hi3510/ptzctrl.cgi?&-chn=0&-act=stop";
	$self->sendCmd( $cmd );
}

# PP - imported from 9831 - autostop after usleep
sub autoStop
{
	my $self = shift;
	my $autostop = shift;
	if( $autostop ) {
		Debug( "Auto Stop" );
		usleep( $autostop );
		my $cmd = "cgi-bin/hi3510/ptzctrl.cgi?&-chn=0&-act=stop";
		$self->sendCmd( $cmd );
	}
}
 
#Move Camera to Home Position
sub presetHome
{
	my $self = shift;
	Debug( "Home Preset" );
	my $cmd = "ptzgotopoint.cgi?&-chn=0&-point=1";
	$self->sendCmd( $cmd );
}
 
#Set preset
sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    my $presetCmd = "cgi-bin/hi3510/ptzsetpoint.cgi?&-chn=0&-point=$preset";
    Debug( "Set Preset $preset with cmd $presetCmd" );
    my $cmd = $presetCmd;
    $self->sendCmd( $cmd );
}
 
#Goto preset
sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    my $presetCmd = "cgi-bin/hi3510/ptzgotopoint.cgi?&-chn=0&-point=$preset";

    Debug( "Set Preset $preset with cmd $presetCmd" );
    my $cmd = $presetCmd;
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

=head1 SPP1802SWPTZ

ZoneMinder::Database - Perl extension for SunEyes SP-P1802SWPTZ

=head1 SYNOPSIS

Control script for SunEyes SP-P1802SWPTZ cameras. 

=head1 DESCRIPTION

You can set "-speed=x" in the ControlDevice field of the control tab for
that monitor. x should be an integer between 0 and 64
Auto TimeOut should be 1. Don't set it to less - processes
start crashing :)

=head2 EXPORT

None by default.



=head1 SEE ALSO

=head1 AUTHOR

Bobby Billingsley, E<lt>bobby(at)bofh(dot)dkE<gt>
based on the work of:
Pliable Pixels, https://github.com/pliablepixels

git checkout -b SunEyes_sp-p1802swptz

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2015-  Bobby Billingsley

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut

