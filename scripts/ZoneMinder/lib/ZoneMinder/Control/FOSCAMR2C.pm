# Modified Jan 2019 for use with Foscam R2C IP Camera by Erik Schoepplenberg
# The presets work with names so adds table to ZM db to track preset names and deletes and adds presets
# in the camera and modifies the ZM db entries.The camera has 16 presets with 1-4
# occupied with TopMost, Bottomost, LeftMost, RightMost so configure for 12.
# the camera stores presets in available spot until full. the script first deletes 
# a preset then sets one using the now avialable spot.
#
# ==========================================================================
#
# ZoneMinder Foscam FI8918W IP Control Protocol Module, $Date: 2009-11-25 09:20:00 +0000 (Wed, 04 Nov 2009) $, $Revision: 0001 $
# Copyright (C) 2001-2008 Philip Coombes
# 
# Modified for use with Foscam FI8918W IP Camera by Dave Harris
# Modified Feb 2011 by Howard Durdle (http://durdl.es/x) to:
#      fix horizontal panning, add presets and IR on/off
#      use Control Device field to pass username and password
# Modified May 2014 by Arun Horne (http://arunhorne.co.uk) to:
#      use HTTP basic auth as required by firmware 11.37.x.x upward
# Modified Jan 2019 for use with Foscam R2C IP Camera by Erik Schoepplenberg
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
# This module contains the implementation of the Foscam R2C camera control
# protocol
#

package MyAgent;

use base 'LWP::UserAgent';


package ZoneMinder::Control::FOSCAMR2C;
 
use 5.006;
use strict;
use warnings;
 
require ZoneMinder::Base;
require ZoneMinder::Control;
 
our @ISA = qw(ZoneMinder::Control);
 
our $VERSION = $ZoneMinder::Base::VERSION;
 
# ==========================================================================
#
# Foscam R2C IP Control Protocol
#
# ==========================================================================
 
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
use ZoneMinder::Database qw(zmDbConnect); 
use Time::HiRes qw( usleep );
 
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
	# Control device needs to be of format usr=xxx&pwd=yyy
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
	my $cmd = "CGIProxy.fcgi?cmd=ptzReset";
	$self->sendCmd( $cmd );
}

# PP - in all move operations, added auto stop after timeout

#Up Arrow
sub moveConUp
{
	my $self = shift;
	Debug( "Move Up" );
	my $cmd = "CGIProxy.fcgi?cmd=ptzMoveUp";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Down Arrow
sub moveConDown
{
	my $self = shift;
	Debug( "Move Down" );
	my $cmd = "CGIProxy.fcgi?cmd=ptzMoveDown";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Left Arrow
sub moveConLeft
{
	my $self = shift;
	Debug( "Move Left" );
	my $cmd = "CGIProxy.fcgi?cmd=ptzMoveLeft";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Right Arrow
sub moveConRight
{
	my $self = shift;
	Debug( "Move Right" );
	my $cmd = "CGIProxy.fcgi?cmd=ptzMoveRight";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Diagonally Up Right Arrow
sub moveConUpRight
{
	my $self = shift;
	Debug( "Move Diagonally Up Right" );
	my $cmd = "CGIProxy.fcgi?cmd=ptzMoveTopRight";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );

}
 
#Diagonally Down Right Arrow
sub moveConDownRight
{
	my $self = shift;
	Debug( "Move Diagonally Down Right" );
	my $cmd = "CGIProxy.fcgi?cmd=ptzMoveBottomRight";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Diagonally Up Left Arrow
sub moveConUpLeft
{
	my $self = shift;
	Debug( "Move Diagonally Up Left" );
	my $cmd = "CGIProxy.fcgi?cmd=ptzMoveTopLeft";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Diagonally Down Left Arrow
sub moveConDownLeft
{
	my $self = shift;
	Debug( "Move Diagonally Down Left" );
	my $cmd = "CGIProxy.fcgi?cmd=ptzMoveBottomLeft";
	$self->sendCmd( $cmd );
	$self->autoStop( $self->{Monitor}->{AutoStopTimeout} );
}
 
#Stop
sub moveStop
{
	my $self = shift;
	Debug( "Move Stop" );
	my $cmd = "CGIProxy.fcgi?cmd=ptzStopRun";
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
	my $cmd = "CGIProxy.fcgi?cmd=ptzStopRun";
       $self->sendCmd( $cmd );
    }
}
 
#Move Camera to Home Position
sub presetHome
{
	my $self = shift;
	Debug( "Home Preset" );
	my $cmd = "CGIProxy.fcgi?cmd=ptzReset";
	$self->sendCmd( $cmd );
}
 
#Set preset
sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    my $dbh = zmDbConnect(1);
	my $sth = $dbh->prepare("SELECT `Label` FROM `ControlPresets` WHERE `Preset` = $preset");
    $sth->execute();
    my $ref = ($sth->fetchrow_hashref());
    my $label = $ref->{'Label'};
    $sth = $dbh->prepare("CREATE TABLE IF NOT EXISTS `ControlPresetNames` (`Preset` int(10) unsigned NOT NULL,`Label2` varchar(64) NOT NULL, UNIQUE KEY (`Label2`))");
    $sth->execute();    
    $sth = $dbh->prepare("SELECT `Label2` FROM `ControlPresetNames` WHERE `Preset` = $preset");  
    $sth->execute();
    $ref = ($sth->fetchrow_hashref());
    my $label2 = $ref->{'Label2'};
    Debug( "Delete Preset $preset with name $label2 from camera" );
    my $cmd = "CGIProxy.fcgi?cmd=ptzDeletePresetPoint&name=$label2";
    $self->sendCmd( $cmd );
    Debug( "Set Preset $preset with cmd $label in camera" );
    $cmd = "CGIProxy.fcgi?cmd=ptzAddPresetPoint&name=$label";
    $self->sendCmd( $cmd );
    Debug( "Delete row Preset $preset with Label2 $label2 from db" );
    $sth = $dbh->prepare("DELETE FROM `ControlPresetNames` WHERE `Preset` = $preset");
    $sth->execute();
    Debug( "Insert Preset $preset with cmd $label in db" );
    $sth = $dbh->prepare("INSERT INTO `ControlPresetNames`(`Preset`, `Label2`) VALUES ('$preset','$label')");
    $sth->execute();
    $sth->finish();


}
 
#Goto preset
sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
	my $dbh = zmDbConnect(1);
	my $sth = $dbh->prepare("SELECT `Label` FROM `ControlPresets` WHERE `Preset` = $preset");
    $sth->execute();
    my $ref = ($sth->fetchrow_hashref());
    my $label = $ref->{'Label'};
    $sth->finish();
    Debug( "Goto Preset $preset with cmd $label" );
    my $cmd = "CGIProxy.fcgi?cmd=ptzGotoPresetPoint&name=$label";
    $self->sendCmd( $cmd );
}
 
#Turn IR on
sub wake
{
	my $self = shift;
	Debug( "Wake - IR on" );
	my $cmd = "CGIProxy.fcgi?cmd=openInfraLed";
	$self->sendCmd( $cmd );
}
 
#Turn IR off
sub sleep
{
	my $self = shift;
	Debug( "Sleep - IR off" );
	my $cmd = "CGIProxy.fcgi?cmd=closeInfraLed";
	$self->sendCmd( $cmd );
}
 
1;
__END__

=head1 R2C

ZoneMinder::Database - Perl extension for FOSCAM R2C

=head1 SYNOPSIS

Control script for Foscam R2C cameras. 

=head1 DESCRIPTION

You need to set "usr=xxx&pwd=yyy" in the ControlDevice field
of the control tab for that monitor.

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

