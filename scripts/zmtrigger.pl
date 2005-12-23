#!/usr/bin/perl -wT
#
# ==========================================================================
#
# ZoneMinder External Trigger Script, $Date$, $Revision$
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
# This script is used to trigger and cancel alarms from external connections
# using an arbitrary text based format
#
use strict;
use bytes;

# ==========================================================================
#
# User config
#
# ==========================================================================

use constant DBG_ID => "zmtrigger"; # Tag that appears in debug to identify source
use constant DBG_LEVEL => 1; # 0 is errors, warnings and info only, > 0 for debug

use constant MAX_CONNECT_DELAY => 10;
use constant MONITOR_RELOAD_INTERVAL => 300;

# Now define the trigger connections, can be inet socket, unix socket or file based
# The isub field should point to a subroutine to convert input messages if necessary
# The osub field should point to a subroutine to convert output messages if necessary

my @conns = (
	{
		name => "S1",
		type => "inet",
		port => "6802",
		in   => !undef,
		out  => !undef,
		isub => sub { $_[0] =~ s/-/|/g; return( $_[0] ); },
		osub => sub { $_[0] =~ s/\|/-/g; return( $_[0] ); },
		ids  => [ 1 ],
	},
	{
		name => "S2",
		type => "unix",
		path => "/tmp/test.sock",
		in   => !undef,
		out  => !undef,
		isub => undef,
		osub => undef,
	},
	{
		name => "S3",
		type => "file",
		path => "/dev/ttyS0",
		in   => undef,
		out  => undef,
		isub => undef,
		osub => undef,
	},
);

# ==========================================================================
#
# Don't change anything from here on down
#
# ==========================================================================

use ZoneMinder;
use DBI;
use POSIX;
use Fcntl;
use Socket;
use IO::Handle;
use Data::Dumper;

use constant LOG_FILE => ZM_PATH_LOGS.'/zmtrigger.log';

$| = 1;

$ENV{PATH}  = '/bin:/usr/bin';
$ENV{SHELL} = '/bin/sh' if exists $ENV{SHELL};
delete @ENV{qw(IFS CDPATH ENV BASH_ENV)};

zmDbgInit( DBG_ID, DBG_LEVEL );

open( LOG, ">>".LOG_FILE ) or die( "Can't open log file: $!" );
open(STDOUT, ">&LOG") || die( "Can't dup stdout: $!" );
select( STDOUT ); $| = 1;
open(STDERR, ">&LOG") || die( "Can't dup stderr: $!" );
select( STDERR ); $| = 1;
select( LOG ); $| = 1;

Info( "Trigger daemon starting\n" );

my $dbh = DBI->connect( "DBI:mysql:database=".ZM_DB_NAME.";host=".ZM_DB_HOST, ZM_DB_USER, ZM_DB_PASS );

$SIG{HUP} = \&status;

my $base_rin = '';
foreach my $conn ( @conns )
{
	Info( "Opening conn '$conn->{name}'\n" );
	if ( $conn->{type} eq "inet" )
	{
		local *sfh;
		my $saddr = sockaddr_in( $conn->{port}, INADDR_ANY );
		socket( *sfh, PF_INET, SOCK_STREAM, getprotobyname('tcp') ) or die( "Can't open socket: $!" );
		setsockopt( *sfh, SOL_SOCKET, SO_REUSEADDR, 1 );
		bind( *sfh, $saddr ) or die( "Can't bind: $!" );
		listen( *sfh, SOMAXCONN ) or die( "Can't listen: $!" );
		$conn->{handle} = *sfh;
	}
	elsif ( $conn->{type} eq "unix" )
	{
		local *sfh;
		unlink( $conn->{path} );
		my $saddr = sockaddr_un( $conn->{path} );
		socket( *sfh, PF_UNIX, SOCK_STREAM, 0 ) or die( "Can't open socket: $!" );
		bind( *sfh, $saddr ) or die( "Can't bind: $!" );
		listen( *sfh, SOMAXCONN ) or die( "Can't listen: $!" );
		$conn->{handle} = *sfh;
	}
	elsif ( $conn->{type} eq "file" )
	{
		local *sfh;
		#sysopen( *sfh, $conn->{path}, O_NONBLOCK|O_RDONLY ) or die( "Can't sysopen: $!" );
		#open( *sfh, "<".$conn->{path} ) or die( "Can't open: $!" );
		open( *sfh, "+<".$conn->{path} ) or die( "Can't open: $!" );
		$conn->{handle} = *sfh;
	}
	else
	{
		die( "Bogus connection type '$conn->{type}' found for '$conn->{name}'" );
	}
}

my @in_conns = grep { $_->{in} } @conns;
my @out_conns = grep { $_->{out} } @conns;

foreach my $conn ( @in_conns )
{
	vec( $base_rin, fileno($conn->{handle}), 1 ) = 1;
	$conn->{iohandle} = $conn->{handle} if ( $conn->{type} eq "file" );
}

my $sigset = POSIX::SigSet->new;
my $blockset = POSIX::SigSet->new( SIGCHLD );
sigprocmask( SIG_BLOCK, $blockset, $sigset ) or die( "Can't block SIGCHLD: $!" );

my %conns;
my %monitors;

my $monitor_reload_time = 0;

$! = undef;
my $rin = '';
my $win = $rin;
my $ein = $win;
my $timeout = 0.25;
my %actions;
while( 1 )
{
	$rin = $base_rin;
	foreach my $fileno ( keys(%conns) )
	{
		vec( $rin, $fileno,1) = 1;
	}
	my $nfound = select( my $rout = $rin, undef, my $eout = $ein, $timeout );
	if ( $nfound > 0 )
	{
		Debug( "Got input from $nfound connections\n" );
		foreach my $conn ( @in_conns )
		{
			if ( vec( $rout, fileno($conn->{handle}),1) )
			{
				Debug( "Got input from connection $conn->{name} (".fileno($conn->{handle}).")\n" );
				if ( $conn->{type} eq "inet" || $conn->{type} eq "unix" )
				{
					local *cfh;
					my $paddr = accept( *cfh, $conn->{handle} );
					$conn->{iohandle} = *cfh;
					$conns{fileno(*cfh)} = $conn;
					Debug( "Added new connection (".fileno(*cfh)."), ".int(keys(%conns))." conns\n" );
				}
				else
				{
					my $buffer;
					my $nbytes = sysread( $conn->{iohandle}, $buffer, POSIX::BUFSIZ );
					if ( !$nbytes )
					{
						die( "Got unexpected close on connection $conn->{name}" );
					}
					else
					{
						Debug( "Got '$buffer' ($nbytes bytes)\n" );
						handleMessages( $conn, $buffer );
					}
				}
			}
		}
		foreach my $conn ( values(%conns) )
		{
			if ( vec( $rout, fileno($conn->{iohandle}),1) )
			{
				Debug( "Got input from connection on ".$conn->{name}." (".fileno($conn->{iohandle}).")\n" );
				my $buffer;
				my $nbytes = sysread( $conn->{iohandle}, $buffer, POSIX::BUFSIZ );
				if ( !$nbytes )
				{
					delete( $conns{fileno($conn->{iohandle})} );
					Debug( "Removed connection (".fileno($conn->{iohandle})."), ".int(keys(%conns))." conns\n" );
					close( $conn->{iohandle} );
					$conn->{iohandle} = undef;
				}
				else
				{
					Debug( "Got '$buffer' ($nbytes bytes)\n" );
					handleMessages( $conn, $buffer );
				}
			}
		}
	}
	elsif ( $nfound < 0 )
	{
		if ( $! == EINTR )
		{
			# No comment
		}
		else
		{
			die( "Can't select: $!" );
		}
	}
	else
	{
		if ( (time() - $monitor_reload_time) > MONITOR_RELOAD_INTERVAL )
		{
			loadMonitors();
		}

		# Check for alarms that might have happened
		foreach my $monitor ( values(%monitors) )
		{
			my ( $state, $last_event ) = zmShmRead( $monitor, [ "shared_data:state", "shared_data:last_event" ] );

			my $message = undef;
			if ( $state == STATE_ALARM || $state == STATE_ALERT ) # In alarm state
			{
				if ( !defined($monitor->{LastEvent}) || ($last_event != $monitor->{LastEvent}) ) # A new event
				{
					$message = $monitor->{Id}."|on|".time()."|".$last_event;
				}
				else # The same one as last time, so ignore it
				{
					# Do nothing
				}
			}
			elsif ( $state == STATE_IDLE && $monitor->{LastState} > STATE_IDLE ) # Out of alarm state
			{
				$message = $monitor->{Id}."|off|".time()."|".$last_event;
			}
			elsif ( defined($monitor->{LastEvent}) && ($last_event != $monitor->{LastEvent}) ) # We've missed a whole event
			{
				$message = $monitor->{Id}."|on|".time()."|".$last_event;
				$message .= "\n";
				$message .= $monitor->{Id}."|off|".time()."|".$last_event;
			}
			$monitor->{LastState} = $state;
			$monitor->{LastEvent} = $last_event;

			if ( $message )
			{
				foreach my $conn ( @out_conns )
				{
					sendMessages( $conn, $message ) if ( defined($conn->{iohandle}) );
				}
			}
		}
		
		Debug( "Checking for timed actions\n" ) if ( int(keys(%actions)) );
		my $now = time();
		foreach my $action_time ( sort( grep { $_ < $now } keys( %actions ) ) )
		{
			Info( "Found actions expiring at $action_time\n" );
			foreach my $action ( @{$actions{$action_time}} )
			{
				Info( "Found action '$action'\n" );
				handleMessage( $action );
			}
			delete( $actions{$action_time} );
		}
	}
}
Info( "Trigger daemon exiting\n" );
exit;

sub loadMonitors
{
	Debug( "Loading monitors\n" );
	my %new_monitors = ();

	my $sql = "select * from Monitors where find_in_set( Function, 'Modect,Mocord,Nodect' )";
	my $sth = $dbh->prepare_cached( $sql ) or Fatal( "Can't prepare '$sql': ".$dbh->errstr() );
	my $res = $sth->execute() or Fatal( "Can't execute: ".$sth->errstr() );
	while( my $monitor = $sth->fetchrow_hashref() )
	{
		next if ( !zmShmGet( $monitor ) ); # Check shared memory ok

		if ( defined($monitors{$monitor->{Id}}->{LastState}) )
		{
			$monitor->{LastState} = $monitors{$monitor->{Id}}->{LastState};
		}
		else
		{
			$monitor->{LastState} = zmGetMonitorState( $monitor );
		}
		if ( defined($monitors{$monitor->{Id}}->{LastEvent}) )
		{
			$monitor->{LastEvent} = $monitors{$monitor->{Id}}->{LastEvent};
		}
		else
		{
			$monitor->{LastEvent} = zmGetLastEvent( $monitor );
		}
		$new_monitors{$monitor->{Id}} = $monitor;
	}
	%monitors = %new_monitors;
	$monitor_reload_time = time();
}

sub sendMessages
{
	my $conn = shift;
	my $buffer = shift;
	#chomp( $buffer );

	Debug( "Sending buffer '$buffer'\n" );
	foreach my $message ( split( /\r?\n/, $buffer ) )
	{
		next if ( !$message );
		Debug( "Sending message '$message'\n" );
		my $osub = $conn->{osub};
		if ( defined($osub) )
		{
			$message = &$osub( $message );
			Debug( "Converted message '$message'\n" );
		}
		sendMessage( $conn, $message );
	}
}

sub sendMessage
{
	my $conn = shift;
	my $message = shift;

	$message .= "\n";
	my $nbytes = syswrite( $conn->{iohandle}, $message );
	if ( !defined( $nbytes) || $nbytes < length($message) )
	{
		Error( "Unable to write message '".$message." to connection $conn->{name} (".fileno($conn->{handle})."), expected ".length($message)." bytes, got ".$nbytes.": $!\n" );
	}
}

sub handleMessages
{
	my $conn = shift;
	my $buffer = shift;
	#chomp( $buffer );

	Debug( "Handling buffer '$buffer'\n" );
	foreach my $message ( split( /\r?\n/, $buffer ) )
	{
		next if ( !$message );
		Debug( "Handling message '$message'\n" );
		print( Dumper( $conn ) );
		my $isub = $conn->{isub};
		print( Dumper( $isub ) );
		if ( defined($isub) )
		{
			$message = &$isub( $message );
			Debug( "Converted message '$message'\n" );
		}
		handleMessage( $message );
	}
}

sub handleMessage
{
	my $message = shift;
	#chomp( $buffer );

	my ( $id, $action, $score, $cause, $text, $showtext ) = split( /\|/, $message );
	$score = 0 if ( !defined($score) );
	$cause = "" if ( !defined($cause) );
	$text = "" if ( !defined($text) );

	my $monitor = $monitors{$id};
	if ( !$monitor )
	{
		Warning( "Can't find monitor '$id' for message '$message'\n" );
		return;
	}
	Debug( "Found monitor for id '$id'\n" );

	next if ( !zmShmGet( $monitor ) );

	Debug( "Handling action '$action'\n" );
	if ( $action =~ /^(enable|disable)(?:\+(\d+))?$/ )
	{
		my $state = $1;
		my $delay = $2;
		if ( $state eq "enable" )
		{
			zmMonitorEnable( $monitor );
		}
		else
		{
			zmMonitorDisable( $monitor );
		}
		# Force a reload
		$monitor_reload_time = 0;
		Info( "Set monitor to $state\n" );
		if ( $delay )
		{
			my $action_time = time()+$delay;
			my $action_text = $id."|".(($state eq "enable")?"disable":"enable");
			my $action_array = $actions{$action_time};
			if ( !$action_array )
			{
				$action_array = $actions{$action_time} = [];
			}
			push( @$action_array, $action_text );
			Debug( "Added timed event '$action_text', expires at $action_time (+$delay secs)\n" );
		}
	}
	elsif ( $action =~ /^(on|off)(?:\+(\d+))?$/ )
	{
		next if ( !$monitor->{Enabled} );

		my $trigger = $1;
		my $delay = $2;
		my $trigger_data;
		if ( $trigger eq "on" )
		{
			zmTriggerEventOn( $monitor, $score, $cause, $text );
		}
		else
		{
			zmTriggerEventOff( $monitor );
		}
		zmTriggerShowText( $showtext ) if defined($showtext);
		Info( "Triggered event '$trigger' '$cause'\n" );
		if ( $delay )
		{
			my $action_time = time()+$delay;
			my $action_text = $id."|cancel|0|".$cause."|".$text;
			my $action_array = $actions{$action_time};
			if ( !$action_array )
			{
				$action_array = $actions{$action_time} = [];
			}
			push( @$action_array, $action_text );
			Debug( "Added timed event '$action_text', expires at $action_time (+$delay secs)\n" );
		}
	}
	elsif( $action eq "cancel" )
	{
		zmTriggerEventCancel( $monitor );
		zmTriggerShowText( $showtext ) if defined($showtext);
		Info( "Cancelled event '$cause'\n" );
	}
	elsif( $action eq "show" )
	{
		zmTriggerShowText( $showtext );
		Info( "Updated show text to '$showtext'\n" );
	}
	else
	{
		Error( "Unrecognised action '$action' in message '$message'\n" );
	}
}
