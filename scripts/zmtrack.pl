#!/usr/bin/perl -wT
#
# ==========================================================================
#
# ZoneMinder Experimental PTZ Tracking Script, $Date$, $Revision$
# Copyright (C) 2003, 2004, 2005  Philip Coombes
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
# This script is used to trigger and cancel alarms from external sources
# using an arbitrary text based format
#
use strict;
use bytes;

# ==========================================================================
#
# User config
#
# ==========================================================================

use constant DBG_ID => "zmtrack"; # Tag that appears in debug to identify source
use constant DBG_LEVEL => 1; # 0 is errors, warnings and info only, > 0 for debug

use constant SLEEP_TIME => 10000; # In microseconds

# ==========================================================================
#
# Don't change anything from here on down
#
# ==========================================================================

use ZoneMinder;
use DBI;
use POSIX;
use Data::Dumper;
use Getopt::Long;
use Time::HiRes qw( usleep );

$| = 1;

$ENV{PATH}  = '/bin:/usr/bin';
$ENV{SHELL} = '/bin/sh' if exists $ENV{SHELL};
delete @ENV{qw(IFS CDPATH ENV BASH_ENV)};

my $mid = 0;

sub Usage
{
	print( "
		Usage: zmtrack.pl -m <monitor>,--monitor=<monitor>]
		Parameters are :-
		-m<monitor>, --monitor=<monitor>   - Id of the monitor to track
		");
	exit( -1 );
}   

if ( !GetOptions( 'monitor=s'=>\$mid ) )
{
	Usage();
}

zmDbgInit( DBG_ID, level=>DBG_LEVEL );

my ( $detaint_mid ) = $mid =~ /^(\d+)$/;
$mid = $detaint_mid;

print( "Tracker daemon $mid (experimental) starting at ".strftime( '%y/%m/%d %H:%M:%S', localtime() )."\n" );

my $dbh = DBI->connect( "DBI:mysql:database=".ZM_DB_NAME.";host=".ZM_DB_HOST, ZM_DB_USER, ZM_DB_PASS );

my $sql = "select C.*,M.* from Monitors as M left join Controls as C on M.ControlId = C.Id where M.Id = ?";
my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );

my $res = $sth->execute( $mid ) or die( "Can't execute '$sql': ".$sth->errstr() );
my $monitor = $sth->fetchrow_hashref();

if ( !$monitor )
{
	print( "Can't find monitor '$mid'\n" );
	exit( -1 );
}
if ( !$monitor->{Controllable} )
{
	print( "Monitor '$mid' is not controllable\n" );
	exit( -1 );
}
if ( !$monitor->{TrackMotion} )
{
	print( "Monitor '$mid' is not configured to track motion\n" );
	exit( -1 );
}

if ( !$monitor->{CanMoveMap} )
{
	print( "Monitor '$mid' cannot move in map mode" );
	if ( $monitor->{CanMoveRel} )
	{
		print( ", falling back to pseudo map mode\n" );
	}
	else
	{
		print( "\n" );
		exit( -1 );
	}
}

Debug( "Found monitor for id '$monitor'\n" );
exit( -1 ) if ( !zmShmVerify( $monitor ) );

sub Suspend
{
	my $monitor = shift;
	zmMonitorSuspend( $monitor );
}

sub Resume
{
	my $monitor = shift;
	sleep( $monitor->{TrackDelay} );
	zmMonitorResume( $monitor );
}

sub Track
{
	my $monitor = shift;
	my ( $x, $y ) = @_;
	my ( $detaint_x ) = $x =~ /^(\d+)$/; $x = $detaint_x;
	my ( $detaint_y ) = $y =~ /^(\d+)$/; $y = $detaint_y;
	my $move_cmd = $monitor->{Command};
	$move_cmd = ZM_PATH_BIN.'/'.$move_cmd if ( $move_cmd !~ m|^/| );
	$move_cmd .= " --device=".$monitor->{ControlDevice} if ( $monitor->{ControlDevice} );
	$move_cmd .= " --address=".$monitor->{ControlAddress} if ( $monitor->{ControlAddress} );
	$move_cmd .= " --command=".($monitor->{CanMoveMap}?"move_map":"move_pseudo_map")." --xcoord=$x --ycoord=$y --width=".$monitor->{Width}." --height=".$monitor->{Height};
	qx( $move_cmd );
}

sub Return
{
	my $monitor = shift;
	my $move_cmd = $monitor->{Command};
	$move_cmd = ZM_PATH_BIN.'/'.$move_cmd if ( $move_cmd !~ m|^/| );
	$move_cmd .= " --device=".$monitor->{ControlDevice} if ( $monitor->{ControlDevice} );
	$move_cmd .= " --address=".$monitor->{ControlAddress} if ( $monitor->{ControlAddress} );
	$move_cmd .= " --command=".($monitor->{ReturnLocation}?"preset1":"preset_home");
	qx( $move_cmd );
}

my $last_alarm = 0;
if ( ($monitor->{ReturnLocation} >= 0) )
{
	Suspend( $monitor );
	Return( $monitor );
	Resume( $monitor );
}

my $alarmed = undef;
while( 1 )
{
	if ( zmIsAlarmed( $monitor ) )
	{
		my ( $alarm_x, $alarm_y ) = zmGetAlarmLocation( $monitor );
		if ( $alarm_x >= 0 && $alarm_y >= 0 )
		{
			Debug( "Got alarm at $alarm_x, $alarm_y\n" );
			Suspend( $monitor );
			Track( $monitor, $alarm_x, $alarm_y );
			Resume( $monitor );
			$last_alarm = time();
			$alarmed = !undef;
		}
	}
	else
	{
		if ( DBG_LEVEL > 0 && $alarmed )
		{
			print( "Left alarm state\n" );
			$alarmed = undef;
		}
		if ( ($monitor->{ReturnLocation} >= 0) && ($last_alarm > 0) && ((time()-$last_alarm) > $monitor->{ReturnDelay}) )
		{
			Debug( "Returning to location ".$monitor->{ReturnLocation}."\n" );
			Suspend( $monitor );
			Return( $monitor );
			Resume( $monitor );
			$last_alarm = 0;
		}
	}
	usleep( SLEEP_TIME );
}
