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
use constant SELECT_TIMEOUT => 0.25;

#
# Define classes for any channels that triggers may go in and/or out over
#

# Base channel class
package Channel;

use ZoneMinder::Debug;

our $AUTOLOAD;

sub new
{
	my $class = shift;
	my $self = {};
	$self->{readable} = !undef;
	$self->{writeable} = !undef;
	$self->{selectable} = undef;
	$self->{state} = 'closed';
	bless( $self, $class );
	return $self;
}

sub clone
{
	my $self = shift;
	my $clone = { %$self };
	bless $clone, ref $self;
}

sub open()
{
	my $self = shift;
	my $class = ref($self) or die( "Can't get class for non object $self" );
	die( "Abstract base class method called for object of class $class" );
}

sub close()
{
	my $self = shift;
	my $class = ref($self) or die( "Can't get class for non object $self" );
	die( "Abstract base class method called for object of class $class" );
}

sub getState()
{
	my $self = shift;
	return( $self->{state} );
}

sub isOpen()
{
	my $self = shift;
	return( $self->{state} eq "open" );
}

sub isConnected()
{
	my $self = shift;
	return( $self->{state} eq "connected" );
}

sub DESTROY
{
}

sub AUTOLOAD
{
	my $self = shift;
	my $class = ref($self) || die( "$self not object" );
	my $name = $AUTOLOAD;
	$name =~ s/.*://;
	if ( !exists($self->{$name}) )
	{
		die( "Can't access $name member of object of class $class" );
	}
	return( $self->{$name} );
}

# Handle based channel
package Channel::Handle;
our @ISA = qw(Channel);

use ZoneMinder::Debug qw(:all);
use POSIX;

sub new
{
	my $class = shift;
	my $port = shift;
	my $self = Channel->new();
	$self->{handle} = undef;
	bless( $self, $class );
	return $self;
}

sub close()
{
	my $self = shift;
	close( $self->{handle} );
	$self->{state} = 'closed';
	$self->{handle} = undef;
}

sub read()
{
	my $self = shift;
	my $buffer;
	my $nbytes = sysread( $self->{handle}, $buffer, POSIX::BUFSIZ );
	if ( !$nbytes )
	{
		return( undef );
	}
	Debug( "Read '$buffer' ($nbytes bytes)\n" );
	return( $buffer );
}

sub write()
{
	my $self = shift;
	my $buffer = shift;
	my $nbytes = syswrite( $self->{handle}, $buffer );
	if ( !defined( $nbytes) || $nbytes < length($buffer) )
	{
		Error( "Unable to write buffer '".$buffer.", expected ".length($buffer)." bytes, sent ".$nbytes.": $!\n" );
		return( undef );
	}
	Debug( "Wrote '$buffer' ($nbytes bytes)\n" );
	return( !undef );
}

sub fileno()
{
	my $self = shift;
	return( defined($self->{handle})?fileno($self->{handle}):-1 );
}

# Spawning selectable channels
package Channel::Spawning;
our @ISA = qw(Channel::Handle);

sub new
{
	my $class = shift;
	my $port = shift;
	my $self = Channel::Handle->new();
	$self->{spawns} = !undef;
	bless( $self, $class );
	return $self;
}

# Inet TCP socket channel
package Channel::Inet;
our @ISA = qw(Channel::Spawning);

use Socket;

sub new
{
	my $class = shift;
	my %params = @_;
	my $self = Channel::Spawning->new();
	$self->{selectable} = !undef;
	$self->{port} = $params{port};
	bless( $self, $class );
	return $self;
}

sub open()
{
	my $self = shift;
	local *sfh;
	my $saddr = sockaddr_in( $self->{port}, INADDR_ANY );
	socket( *sfh, PF_INET, SOCK_STREAM, getprotobyname('tcp') ) or die( "Can't open socket: $!" );
	setsockopt( *sfh, SOL_SOCKET, SO_REUSEADDR, 1 );
	bind( *sfh, $saddr ) or die( "Can't bind: $!" );
	listen( *sfh, SOMAXCONN ) or die( "Can't listen: $!" );
	$self->{state} = 'open';
	$self->{handle} = *sfh;
}

sub _spawn( $ )
{
	my $self = shift;
	my $new_handle = shift;
	my $clone = $self->clone();
	$clone->{handle} = $new_handle;
	$clone->{state} = 'connected';
	return( $clone );
}

sub accept()
{
	my $self = shift;
	local *cfh;
	my $paddr = accept( *cfh, $self->{handle} );
	return( $self->_spawn( *cfh ) );
}

# Unix socket channel
package Channel::Unix;
our @ISA = qw(Channel::Spawning);

use Socket;

sub new
{
	my $class = shift;
	my %params = @_;
	my $self = Channel->new;
	$self->{selectable} = !undef;
	$self->{path} = $params{path};
	bless( $self, $class );
	return $self;
}

sub open()
{
	my $self = shift;
	local *sfh;
	unlink( $self->{path} );
	my $saddr = sockaddr_un( $self->{path} );
	socket( *sfh, PF_UNIX, SOCK_STREAM, 0 ) or die( "Can't open socket: $!" );
	bind( *sfh, $saddr ) or die( "Can't bind: $!" );
	listen( *sfh, SOMAXCONN ) or die( "Can't listen: $!" );
	$self->{handle} = *sfh;
}

# Simple file channel
package Channel::File;
our @ISA = qw(Channel::Handle);

use Fcntl;

sub new
{
	my $class = shift;
	my %params = @_;
	my $self = Channel::Handle->new;
	$self->{path} = $params{path};
	bless( $self, $class );
	return $self;
}

sub open()
{
	my $self = shift;
	local *sfh;
	#sysopen( *sfh, $conn->{path}, O_NONBLOCK|O_RDONLY ) or die( "Can't sysopen: $!" );
	#open( *sfh, "<".$conn->{path} ) or die( "Can't open: $!" );
	open( *sfh, "+<".$self->{path} ) or die( "Can't open: $!" );
	$self->{state} = 'open';
	$self->{handle} = *sfh;
}

# Serial device channel
package Channel::Serial;
our @ISA = qw(Channel);

use ZoneMinder::Debug qw(:all);
use Device::SerialPort;

sub new
{
	my $class = shift;
	my %params = @_;
	my $self = Channel->new;
	$self->{path} = $params{path};
	bless( $self, $class );
	return $self;
}

sub open()
{
	my $self = shift;
	my $device = new Device::SerialPort( $self->{path} );
	$device->baudrate(9600);
	$device->databits(8);
	$device->parity('none');
	$device->stopbits(1);
	$device->handshake('none');

	$device->read_const_time(50);
	$device->read_char_time(10);

	$self->{device} = $device;
	$self->{state} = 'open';
	$self->{state} = 'connected';
}

sub close()
{
	my $self = shift;
	$self->{device}->close();
	$self->{state} = 'closed';
}

sub read()
{
	my $self = shift;
	my $buffer = $self->{device}->lookfor();
	if ( !$buffer || !length($buffer) )
	{
		return( undef );
	}
	Debug( "Read '$buffer' (".length($buffer)." bytes)\n" );
	return( $buffer );
}

sub write()
{
	my $self = shift;
	my $buffer = shift;
	my $nbytes = $self->{device}->write( $buffer );
	$self->{device}->write_drain();
	if ( !defined( $nbytes) || $nbytes < length($buffer) )
	{
		Error( "Unable to write buffer '".$buffer.", expected ".length($buffer)." bytes, sent ".$nbytes.": $!\n" );
		return( undef );
	}
	Debug( "Wrote '$buffer' ($nbytes bytes)\n" );
	return( !undef );
}


package Connection;
use ZoneMinder::Debug;

our $AUTOLOAD;

sub new
{
	my $class = shift;
	my %params = @_;
	my $self = {};
	$self->{name} = $params{name};
	$self->{channel} = $params{channel};
	$self->{input} = $params{mode} =~ /r/i;
	$self->{output} = $params{mode} =~ /w/i;
	bless( $self, $class );
	return $self;
}

sub clone
{
	my $self = shift;
	my $clone = { %$self };
	bless $clone, ref $self;
	return( $clone );
}

sub _spawn( $ )
{
	my $self = shift;
	my $new_channel = shift;
	my $clone = $self->clone();
	$clone->{channel} = $new_channel;
	return( $clone );
}

sub accept()
{
	my $self = shift;
	my $new_channel = $self->{channel}->accept();
	return( $self->_spawn( $new_channel ) );
}

sub open()
{
	my $self = shift;
	return( $self->{channel}->open() );
}

sub close()
{
	my $self = shift;
	return( $self->{channel}->close() );
}

sub fileno()
{
	my $self = shift;
	return( $self->{channel}->fileno() );
}

sub isOpen()
{
	my $self = shift;
	return( $self->{channel}->isOpen() );
}

sub isConnected()
{
	my $self = shift;
	return( $self->{channel}->isConnected() );
}

sub canRead()
{
	my $self = shift;
	return( $self->{input} && $self->isConnected() );
}

sub canWrite()
{
	my $self = shift;
	return( $self->{output} && $self->isConnected() );
}

sub getMessages
{
	my $self = shift;
	my $buffer = $self->{channel}->read();

	return( undef ) if ( !defined($buffer) );

	my @messages = split( /\r?\n/, $buffer );
	return( \@messages );
}

sub putMessages
{
	my $self = shift;
	my $messages = shift;

	if ( @$messages )
	{
		my $buffer = join( "\n", @$messages );
		$buffer .= "\n";
		if ( !$self->{channel}->write( $buffer ) )
		{
			Error( "Unable to write buffer '".$buffer." to connection ".$self->{name}." (".$self->fileno().")\n" );
		}
	}
	return( undef );
}

sub DESTROY
{
}

sub AUTOLOAD
{
	my $self = shift;
	my $class = ref($self) || die( "$self not object" );
	my $name = $AUTOLOAD;
	$name =~ s/.*://;
	if ( exists($self->{$name}) )
	{
		return( $self->{$name} );
	}
	elsif ( defined($self->{channel}) )
	{
		if ( exists($self->{channel}->{$name}) )
		{
			return( $self->{channel}->{$name} );
		}
	}
	die( "Can't access $name member of object of class $class" );
}

package Connection::Special;
our @ISA = qw(Connection);

sub new
{
	my $class = shift;
	my $path = shift;
	my $self = Connection->new( @_ );
	bless( $self, $class );
	return $self;
}

sub getMessages
{
	my $self = shift;
	my $buffer = $self->{channel}->read();

	return( undef ) if ( !defined($buffer) );

	Debug( "Handling buffer '$buffer'\n" );
	my @messages = grep { s/-/|/g; 1; } split( /\r?\n/, $buffer );
	return( \@messages );
}

sub putMessages
{
	my $self = shift;
	my $messages = shift;

	if ( @$messages )
	{
		my $buffer = join( "\n", grep{ s/\|/-/; 1; } @$messages );
		$buffer .= "\n";
		if ( !$self->{channel}->write( $buffer ) )
		{
			Error( "Unable to write buffer '".$buffer." to connection ".$self->{name}." (".$self->fileno().")\n" );
		}
	}
	return( undef );
}

package main;

my @connections;
push( @connections, Connection->new( name=>"Chan1", channel=>Channel::Inet->new( port=>6802 ), mode=>"rw" ) );
push( @connections, Connection->new( name=>"Chan2", channel=>Channel::Unix->new( path=>'/tmp/test.sock' ), mode=>"rw" ) );
#push( @connections, Connection->new( name=>"Chan3", channel=>Channel::File->new( path=>'/tmp/zmtrigger.out' ), mode=>"w" ) );
push( @connections, Connection->new( name=>"Chan4", channel=>Channel::Serial->new( path=>'/dev/ttyS0' ), mode=>"rw" ) );

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
foreach my $connection ( @connections )
{
	Info( "Opening connection '$connection->{name}'\n" );
	$connection->open();
}

my @in_select_connections = grep { $_->input() && $_->selectable() } @connections;
my @in_poll_connections = grep { $_->input() && !$_->selectable() } @connections;
my @out_connections = grep { $_->output() } @connections;

foreach my $connection ( @in_select_connections )
{
	print( "FN:".$connection->fileno()."\n" );
	vec( $base_rin, $connection->fileno(), 1 ) = 1;
}

#my $sigset = POSIX::SigSet->new;
#my $blockset = POSIX::SigSet->new( SIGCHLD );
#sigprocmask( SIG_BLOCK, $blockset, $sigset ) or die( "Can't block SIGCHLD: $!" );

my %spawned_connections;
my %monitors;

my $monitor_reload_time = 0;

$! = undef;
my $rin = '';
my $win = $rin;
my $ein = $win;
my $timeout = SELECT_TIMEOUT;
my %actions;
while( 1 )
{
	$rin = $base_rin;
	# Add the file descriptors of any spawned connections
	foreach my $fileno ( keys(%spawned_connections) )
	{
		vec( $rin, $fileno, 1 ) = 1;
	}

	my $nfound = select( my $rout = $rin, undef, my $eout = $ein, $timeout );
	if ( $nfound > 0 )
	{
		Debug( "Got input from $nfound connections\n" );
		foreach my $connection ( @in_select_connections )
		{
			if ( vec( $rout, $connection->fileno(), 1 ) )
			{
				Debug( "Got input from connection ".$connection->name()." (".$connection->fileno().")\n" );
				if ( $connection->spawns() )
				{
					my $new_connection = $connection->accept();
					$spawned_connections{$new_connection->fileno()} = $new_connection;
					Debug( "Added new spawned connection (".$new_connection->fileno()."), ".int(keys(%spawned_connections))." spawned connections\n" );
				}
				else
				{
					my $messages = $connection->getMessages();
					if ( defined($messages) )
					{
						foreach my $message ( @$messages )
						{
							handleMessage( $connection, $message );
						}
					}
				}
			}
		}
		foreach my $connection ( values(%spawned_connections) )
		{
			if ( vec( $rout, $connection->fileno(), 1 ) )
			{
				Debug( "Got input from spawned connection ".$connection->name()." (".$connection->fileno().")\n" );
				my $messages = $connection->getMessages();
				if ( defined($messages) )
				{
					foreach my $message ( @$messages )
					{
						handleMessage( $connection, $message );
					}
				}
				else
				{
					delete( $spawned_connections{$connection->fileno()} );
					Debug( "Removed spawned connection (".$connection->fileno()."), ".int(keys(%spawned_connections))." spawned connections\n" );
					$connection->close();
				}
			}
		}
	}
	elsif ( $nfound < 0 )
	{
		if ( $! == EINTR )
		{
			# Do nothing
		}
		else
		{
			die( "Can't select: $!" );
		}
	}

	# Check polled connections
	foreach my $connection ( @in_poll_connections )
	{
		my $messages = $connection->getMessages();
		if ( defined($messages) )
		{
			foreach my $message ( @$messages )
			{
				handleMessage( $connection, $message );
			}
		}
	}

	# Check for alarms that might have happened
	my @out_messages;
	foreach my $monitor ( values(%monitors) )
	{
		my ( $state, $last_event ) = zmShmRead( $monitor, [ "shared_data:state", "shared_data:last_event" ] );

		#print( "$monitor->{Id}: S:$state, LE:$last_event\n" );
		#print( "$monitor->{Id}: mS:$monitor->{LastState}, mLE:$monitor->{LastEvent}\n" );
		if ( $state == STATE_ALARM || $state == STATE_ALERT ) # In alarm state
		{
			if ( !defined($monitor->{LastEvent}) || ($last_event != $monitor->{LastEvent}) ) # A new event
			{
				push( @out_messages, $monitor->{Id}."|on|".time()."|".$last_event );
			}
			else # The same one as last time, so ignore it
			{
				# Do nothing
			}
		}
		elsif ( $state == STATE_IDLE && $monitor->{LastState} > STATE_IDLE ) # Out of alarm state
		{
			push( @out_messages, $monitor->{Id}."|off|".time()."|".$last_event );
		}
		elsif ( defined($monitor->{LastEvent}) && ($last_event != $monitor->{LastEvent}) ) # We've missed a whole event
		{
			push( @out_messages, $monitor->{Id}."|on|".time()."|".$last_event );
			push( @out_messages, $monitor->{Id}."|off|".time()."|".$last_event );
		}
		$monitor->{LastState} = $state;
		$monitor->{LastEvent} = $last_event;
	}
	foreach my $connection ( @out_connections )
	{
		if ( $connection->canWrite() )
		{
			$connection->putMessages( \@out_messages );
		}
	}
	foreach my $connection ( values(%spawned_connections) )
	{
		if ( $connection->canWrite() )
		{
			$connection->putMessages( \@out_messages );
		}
	}

	Debug( "Checking for timed actions\n" ) if ( int(keys(%actions)) );
	my $now = time();
	foreach my $action_time ( sort( grep { $_ < $now } keys( %actions ) ) )
	{
		Info( "Found actions expiring at $action_time\n" );
		foreach my $action ( @{$actions{$action_time}} )
		{
			my $connection = $action->{connection};
			my $message = $action->{message};
			Info( "Found action '$message'\n" );
			handleMessage( $connection, $message );
		}
		delete( $actions{$action_time} );
	}

	# If necessary reload monitors
	if ( (time() - $monitor_reload_time) > MONITOR_RELOAD_INTERVAL )
	{
		loadMonitors();
	}
}
Info( "Trigger daemon exiting\n" );
exit;

sub loadMonitors
{
	Debug( "Loading monitors\n" );
	$monitor_reload_time = time();

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
}

sub handleMessage
{
	my $connection = shift;
	my $message = shift;

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
			push( @$action_array, { connection=>$connection, message=>$action_text } );
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
			#my $action_text = $id."|cancel|0|".$cause."|".$text;
			my $action_text = $id."|cancel";
			my $action_array = $actions{$action_time};
			if ( !$action_array )
			{
				$action_array = $actions{$action_time} = [];
			}
			push( @$action_array, { connection=>$connection, message=>$action_text } );
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
