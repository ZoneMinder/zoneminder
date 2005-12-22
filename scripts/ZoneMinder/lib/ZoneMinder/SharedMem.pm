# ==========================================================================
#
# ZoneMinder Shared Memory Module, $Date$, $Revision$
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
# This module contains the common definitions and functions used by the rest 
# of the ZoneMinder scripts
#
package ZoneMinder::SharedMem;

use 5.006;
use strict;
use warnings;

require Exporter;
require ZoneMinder::Base;

our @ISA = qw(Exporter ZoneMinder::Base);

# Items to export into callers namespace by default. Note: do not export
# names by default without a very good reason. Use EXPORT_OK instead.
# Do not simply export all your public functions/methods/constants.

# This allows declaration	use ZoneMinder ':all';
# If you do not need this, moving things directly into @EXPORT or @EXPORT_OK
# will save memory.
our %EXPORT_TAGS = (
	'constants' => [ qw(
		STATE_IDLE
		STATE_PREALARM
		STATE_ALARM
		STATE_ALERT
		STATE_TAPE
		ACTION_GET
		ACTION_SET
		ACTION_SUSPEND
		ACTION_RESUME
		TRIGGER_CANCEL
		TRIGGER_ON
		TRIGGER_OFF 
	) ],
	'functions' => [ qw(
		zmShmGet
		zmShmVerify
		zmShmRead
		zmShmWrite
		zmGetMonitorState
		zmGetLastEventId
		zmGetAlarmLocation
		zmIsAlarmed
		zmInAlarm
		zmHasAlarmed
		zmGetLastImageTime
		zmGetMonitorActions
		zmMonitorSuspend
		zmMonitorResume
		zmTriggerEventOn
		zmTriggerEventOff
		zmTriggerEventCancel
		zmTriggerShowtext
	) ],
);
push( @{$EXPORT_TAGS{all}}, @{$EXPORT_TAGS{$_}} ) foreach keys %EXPORT_TAGS;

our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );

our @EXPORT = qw();

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Shared Memory Facilities
#
# ==========================================================================

use ZoneMinder::Config qw(:all);
use ZoneMinder::Debug qw(:all);

use constant STATE_IDLE     => 0;
use constant STATE_PREALARM => 1;
use constant STATE_ALARM    => 2;
use constant STATE_ALERT    => 3;
use constant STATE_TAPE     => 4;

use constant ACTION_GET     => 1;
use constant ACTION_SET     => 2;
use constant ACTION_SUSPEND => 4;
use constant ACTION_RESUME  => 8;

use constant TRIGGER_CANCEL => 0;
use constant TRIGGER_ON     => 1;
use constant TRIGGER_OFF    => 2;

our $shm_data =
{
	"shared_data" => { "offset"=>0, "size"=>56, "type"=>"SharedData", "contents"=> {
		"size"             => { "offset"=>0,  "size"=>4, "type"=>"int" },
		"valid"            => { "offset"=>4,  "size"=>4, "type"=>"bool" },
		"state"            => { "offset"=>8,  "size"=>4, "type"=>"enum"},
		"last_write_index" => { "offset"=>12, "size"=>4, "type"=>"int" },
		"last_read_index"  => { "offset"=>16, "size"=>4, "type"=>"int" },
		"last_image_time"  => { "offset"=>20, "size"=>4, "type"=>"time_t" },
		"last_event"       => { "offset"=>24, "size"=>4, "type"=>"int" },
		"action"           => { "offset"=>28, "size"=>4, "type"=>"set" },
		"brightness"       => { "offset"=>32, "size"=>4, "type"=>"int" },
		"hue"              => { "offset"=>36, "size"=>4, "type"=>"int" },
		"colour"           => { "offset"=>40, "size"=>4, "type"=>"int" },
		"contrast"         => { "offset"=>44, "size"=>4, "type"=>"int" },
		"alarm_x"          => { "offset"=>48, "size"=>4, "type"=>"int" },
		"alarm_y"          => { "offset"=>52, "size"=>4, "type"=>"int" },
		}
	},
	"trigger_data" => { "offset"=>56, "size"=>332, "type"=>"TriggerData", "contents"=> {
		"size"             => { "offset"=>56+0,   "size"=>4,   "type"=>"int" },
		"trigger_state"    => { "offset"=>56+4,   "size"=>4,   "type"=>"enum" },
		"trigger_score"    => { "offset"=>56+8,   "size"=>4,   "type"=>"int" },
		"trigger_cause"    => { "offset"=>56+12,  "size"=>32,  "type"=>"char[]" },
		"trigger_text"     => { "offset"=>56+44,  "size"=>256, "type"=>"char[]" },
		"trigger_showtext" => { "offset"=>56+300, "size"=>32,  "type"=>"char[]" },
		}
	},
	"end" => { "offset"=>56+332, "size"=> 0 }
};

our $shm_size = $shm_data->{end}->{offset};

our $shm_verified = {};

sub zmShmGet( $ )
{
	my $monitor = shift;
	if ( !defined($monitor->{ShmId}) )
	{
		my $shm_key = hex(ZM_SHM_KEY)|$monitor->{Id};
		my $shm_id = shmget( $shm_key, $shm_size, 0 );
		if ( !defined($shm_id) )
		{
    		Error( "Can't get shared memory id '%x', %d: $!\n", $shm_key, $monitor->{Id}, $! );
			return( undef );
		}
		$monitor->{ShmKey} = $shm_key;
		$monitor->{ShmId} = $shm_id;
		zmShmVerify( $monitor );
	}
	return( !undef );
}

sub zmShmVerify( $ )
{
	my $monitor = shift;
	my $shm_key = $monitor->{ShmKey};

	if ( !defined($shm_verified->{$shm_key}) )
	{
		my $shm_id = $monitor->{ShmId};
		my $sd_size = zmShmRead( $monitor, "shared_data:size" );
		if ( $sd_size != $shm_data->{shared_data}->{size} )
		{
			Error( "Shared memory size conflict in shared_data, expected ".$shm_data->{shared_data}->{size}.", got ".$sd_size );
			return( undef );
		}
		my $td_size = zmShmRead( $monitor, "trigger_data:size" );
		if ( $td_size != $shm_data->{trigger_data}->{size} )
		{
			Error( "Shared memory size conflict in trigger_data, expected ".$shm_data->{triggger_data}->{size}.", got ".$td_size );
			return( undef );
		}
		$shm_verified->{$shm_key} = !undef;
	}
	return( !undef );
}

sub zmShmRead( $$ )
{
	my $monitor = shift;
	my $fields = shift;

	if ( !zmShmGet( $monitor ) )
	{
		return( undef );
	}

	my $shm_key = $monitor->{ShmKey};
	my $shm_id = $monitor->{ShmId};
	
	if ( !ref($fields) )
	{
		$fields = [ $fields ];
	}
	my @values;
	foreach my $field ( @$fields )
	{
		my ( $section, $element ) = split( /[\/:.]/, $field );
		Fatal( "Invalid shm selector '$field'" ) if ( !$section || !$element );

		my $offset = $shm_data->{$section}->{contents}->{$element}->{offset};
		my $type = $shm_data->{$section}->{contents}->{$element}->{type};
		my $size = $shm_data->{$section}->{contents}->{$element}->{size};

		my $data;
		if ( !shmread( $shm_id, $data, $offset, $size ) )
		{
			Error( "Can't read '$field' from shared memory '$shm_key/$shm_id': $!" );
			return( undef );
		}
	
		my $value;
		if ( $type eq "char" )
		{
			( $value ) = unpack( "c", $data );
		}
		elsif ( $type eq "short" )
		{
			( $value ) = unpack( "s", $data );
		}
		elsif ( $type eq "int" || $type eq "bool" || $type eq "time_t" || $type eq "enum" || $type eq "set" )
		{
			( $value ) = unpack( "l", $data );
		}
		elsif ( $type eq "char[]" )
		{
			( $value ) = unpack( "Z".$size, $data );
		}
		push( @values, $value );
	}
	if ( wantarray() )
	{
		return( @values )
	}
	return( $values[0] );
}

sub zmShmWrite( $$ )
{
	my $monitor = shift;
	my $field_values_ref = shift;

	if ( !zmShmGet( $monitor ) )
	{
		return( undef );
	}

	my $shm_key = $monitor->{ShmKey};
	my $shm_id = $monitor->{ShmId};
	
	if ( ref($field_values_ref) eq "HASH" )
	{
		$field_values_ref = [ %$field_values_ref ];
	}
	my %field_values = @$field_values_ref;
	while ( my ( $field, $value ) = each( %field_values ) )
	{
		my ( $section, $element ) = split( /[\/:.]/, $field );
		die( "Invalid shm selector '$field'" ) if ( !$section || !$element );

		my $offset = $shm_data->{$section}->{contents}->{$element}->{offset};
		my $type = $shm_data->{$section}->{contents}->{$element}->{type};
		my $size = $shm_data->{$section}->{contents}->{$element}->{size};

		my $data;
		if ( $type eq "char" )
		{
			$data = pack( "c", $value );
		}
		elsif ( $type eq "short" )
		{
			$data = pack( "s", $value );
		}
		elsif ( $type eq "int" || $type eq "bool" || $type eq "time_t" || $type eq "enum" || $type eq "set" )
		{
			$data = pack( "l", $value );
		}
		elsif ( $type eq "char[]" )
		{
			$data = pack( "Z".$size, $value );
		}

		if ( !shmwrite( $shm_id, $data, $offset, $size ) )
		{
			Error( "Can't write value '$value' to '$field' in shared memory '$shm_key/$shm_id': $!" );
			return( undef );
		}
	}
	return( !undef );
}

sub zmGetMonitorState( $ )
{
	my $monitor = shift;

	return( zmShmRead( $monitor, "shared_data:state" ) );
}

sub zmGetLastEventId( $ )
{
	my $monitor = shift;

	return( zmShmRead( $monitor, "shared_data:last_event" ) );
}

sub zmGetAlarmLocation( $ )
{
	my $monitor = shift;

	return( zmShmRead( $monitor, [ "shared_data:alarm_x", "shared_data:alarm_y" ] ) );
}

sub zmIsAlarmed( $ )
{
	my $monitor = shift;

	my $state = zmGetMonitorState( $monitor );

	return( $state == STATE_ALARM );
}

sub zmInAlarm( $ )
{
	my $monitor = shift;

	my $state = zmGetMonitorState( $monitor );

	return( $state == STATE_ALARM || $state == STATE_ALERT );
}

sub zmHasAlarmed( $$ )
{
	my $monitor = shift;
	my $last_event_id = shift;

	my ( $state, $last_event ) = zmShmRead( $monitor, [ "shared_data:state", "shared_data:last_event" ] );

	if ( $state == STATE_ALARM || $state == STATE_ALERT )
	{
		return( !undef );
	}
	elsif( $last_event != $last_event_id )
	{
		return( !undef );
	}
	return( undef );
}

sub zmGetLastImageTime( $ )
{
	my $monitor = shift;

	return( zmShmRead( $monitor, "shared_data:last_image_time" ) );
}

sub zmGetMonitorActions( $ )
{
	my $monitor = shift;

	return( zmShmRead( $monitor, "shared_data:action" ) );
}

sub zmMonitorSuspend( $ )
{
	my $monitor = shift;

	my $action = zmShmRead( $monitor, "shared_data:action" );
	$action |= ACTION_SUSPEND;
	zmShmWrite( $monitor, { "shared_data:action" => $action } );
}

sub zmMonitorResume( $ )
{
	my $monitor = shift;

	my $action = zmShmRead( $monitor, "shared_data:action" );
	$action |= ACTION_RESUME;
	zmShmWrite( $monitor, { "shared_data:action" => $action } );
}

sub zmGetTriggerState( $ )
{
	my $monitor = shift;

	return( zmShmRead( $monitor, "trigger_data:trigger_state" ) );
}

sub zmTriggerEventOn( $$$;$$ )
{
	my $monitor = shift;
	my $score = shift;
	my $cause = shift;
	my $text = shift;
	my $showtext = shift;

	my @values = (
		( "trigger_data:trigger_score" => $score ),
		( "trigger_data:trigger_cause" => $cause ),
	);
	push( @values, ( "trigger_data:trigger_text" => $text ) ) if ( defined($text) );
	push( @values, ( "trigger_data:trigger_showtext" => $showtext ) ) if ( defined($showtext) );
	push( @values, ( "trigger_data:trigger_state" => TRIGGER_ON ) ); # Write state last so event not read incomplete

	zmShmWrite( $monitor, \@values );
}

sub zmTriggerEventOff( $ )
{
	my $monitor = shift;

	my @values = (
		( "trigger_data:trigger_state"    => TRIGGER_OFF ),
		( "trigger_data:trigger_score"    => 0 ),
		( "trigger_data:trigger_cause"    => "" ),
		( "trigger_data:trigger_text"     => "" ),
		( "trigger_data:trigger_showtext" => "" ),
	);

	zmShmWrite( $monitor, \@values );
}

sub zmTriggerEventCancel( $ )
{
	my $monitor = shift;

	my @values = (
		( "trigger_data:trigger_state"    => TRIGGER_CANCEL ),
		( "trigger_data:trigger_score"    => 0 ),
		( "trigger_data:trigger_cause"    => "" ),
		( "trigger_data:trigger_text"     => "" ),
		( "trigger_data:trigger_showtext" => "" ),
	);

	zmShmWrite( $monitor, \@values );
}

sub zmTriggerShowtext( $$ )
{
	my $monitor = shift;
	my $showtext = shift;

	my @values = (
		( "trigger_data:trigger_showtext" => $showtext ),
	);

	zmShmWrite( $monitor, \@values );
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder - Perl extension for blah blah blah

=head1 SYNOPSIS

  use ZoneMinder;
  blah blah blah

=head1 DESCRIPTION

Stub documentation for ZoneMinder, created by h2xs. It looks like the
author of the extension was negligent enough to leave the stub
unedited.

Blah blah blah.

=head2 EXPORT

None by default.



=head1 SEE ALSO

Mention other useful documentation such as the documentation of
related modules or operating system documentation (such as man pages
in UNIX), or any relevant external documentation such as RFCs or
standards.

If you have a mailing list set up for your module, mention it here.

If you have a web site set up for your module, mention it here.

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2005 by Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
