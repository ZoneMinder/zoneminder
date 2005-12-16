#!/usr/bin/perl -wT
#
# ==========================================================================
#
# ZoneMinder WatchDog Script, $Date$, $Revision$
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
# This does some basic setup for ZoneMinder to run and then periodically
# checks the fps output of the active daemons to check they haven't 
# locked up. If they have then they are killed and restarted
#
use strict;
use bytes;

# ==========================================================================
#
# These are the elements you can edit to suit your installation
#
# ==========================================================================

use constant START_DELAY => 30; # To give everything else time to start
use constant DBG_LEVEL => 0; # 0 is errors, warnings and info only, > 0 for debug


# ==========================================================================
#
# Don't change anything below here
#
# ==========================================================================

use ZoneMinder;
use POSIX;
use DBI;
use Data::Dumper;

use constant WATCH_LOG_FILE => ZM_PATH_LOGS.'/zmwatch.log';

$| = 1;

$ENV{PATH}  = '/bin:/usr/bin';
$ENV{SHELL} = '/bin/sh' if exists $ENV{SHELL};
delete @ENV{qw(IFS CDPATH ENV BASH_ENV)};

sub Usage
{
    print( "
Usage: zmwatch.pl
");
	exit( -1 );
}

open( LOG, '>>'.WATCH_LOG_FILE ) or die( "Can't open log file: $!" );
open( STDOUT, ">&LOG" ) || die( "Can't dup stdout: $!" );
select( STDOUT ); $| = 1;
open( STDERR, ">&LOG" ) || die( "Can't dup stderr: $!" );
select( STDERR ); $| = 1;
select( LOG ); $| = 1;
Info( "Watchdog starting at ".strftime( '%y/%m/%d %H:%M:%S', localtime() )."\n" );
Info( "Watchdog pausing for ".START_DELAY." seconds\n" );
sleep( START_DELAY );

my $dbh = DBI->connect( "DBI:mysql:database=".ZM_DB_NAME.";host=".ZM_DB_HOST, ZM_DB_USER, ZM_DB_PASS );

my $sql = "select * from Monitors";
my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );

while( 1 )
{
	my $now = time();
	my $res = $sth->execute() or die( "Can't execute: ".$sth->errstr() );
	my $shm_size = 24; # We only need the first 24 bytes really for the last event time
	while( my $monitor = $sth->fetchrow_hashref() )
	{
		if ( $monitor->{Function} ne 'None' )
		{
			# Check we have got an image recently
			$monitor->{ShmKey} = hex(ZM_SHM_KEY)|$monitor->{Id};
			$monitor->{ShmId} = shmget( $monitor->{ShmKey}, $shm_size, 0 );
			if ( !defined($monitor->{ShmId}) )
			{
				Error( "Can't get shared memory id '$monitor->{ShmKey}': $!\n" );
				next;
			}
			my $image_time;
			if ( !shmread( $monitor->{ShmId}, $image_time, 20, 4 ) )
			{
				Error( "Can't read from shared memory '$monitor->{ShmKey}/$monitor->{ShmId}': $!\n" );
				next;
			}
			$image_time = unpack( "l", $image_time );

			#my $command = ZM_PATH_BIN."/zmu -m ".$monitor->{Id}." -t";
			#Debug( "Getting last image time for monitor $monitor->{Id} ('$command')\n" );
			#my $image_time = qx( $command );
			#chomp($image_time);

			if ( !$image_time )
			{
				# We can't get the last capture time so can't be sure it's died.
				next;
			}

			my $max_image_delay = (($monitor->{MaxFPS}>0)&&($monitor->{MaxFPS}<1))?(3/$monitor->{MaxFPS}):ZM_WATCH_MAX_DELAY;
			my $image_delay = $now-$image_time;
			Debug( "Monitor $monitor->{Id} last captured $image_delay seconds ago, max is $max_image_delay\n" );
			if ( $image_delay <= $max_image_delay )
			{
				# Yes, so continue
				next;
			}

			my $command;
			# If we are here then something bad has happened
			if ( $monitor->{Type} eq 'Local' )
			{
				$command = ZM_PATH_BIN."/zmdc.pl restart zmc -d $monitor->{Device}";
			}
			else
			{
				$command = ZM_PATH_BIN."/zmdc.pl restart zmc -m $monitor->{Id}";
			}
			Info( "Restarting capture daemon ('$command'), time since last capture $image_delay seconds ($now-$image_time)\n" );
			Info( qx( $command ) );
		}
	}
	sleep( ZM_WATCH_CHECK_INTERVAL );
}
Info( "Watchdog exiting at ".strftime( '%y/%m/%d %H:%M:%S', localtime() )."\n" );
exit();
