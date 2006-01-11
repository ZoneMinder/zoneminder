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
		ACTION_RELOAD
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
		zmGetLastEvent
		zmGetLastImageTime
		zmGetMonitorActions
		zmMonitorEnable
		zmMonitorDisable
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
use constant ACTION_RELOAD  => 4;
use constant ACTION_SUSPEND => 16;
use constant ACTION_RESUME  => 32;

use constant TRIGGER_CANCEL => 0;
use constant TRIGGER_ON     => 1;
use constant TRIGGER_OFF    => 2;

# Native architecture
our $arch = int(3.2*length(~0));
our $native = $arch/8;
our $shm_seq = 0;

our $shm_data =
{
	"shared_data" => { "type"=>"SharedData", "seq"=>$shm_seq++, "contents"=> {
		"size"             => { "type"=>"int", "seq"=>$shm_seq++ },
		"valid"            => { "type"=>"bool1", "seq"=>$shm_seq++ },
		"active"           => { "type"=>"bool1", "seq"=>$shm_seq++ },
		"state"            => { "type"=>"enum", "seq"=>$shm_seq++},
		"last_write_index" => { "type"=>"int", "seq"=>$shm_seq++ },
		"last_read_index"  => { "type"=>"int", "seq"=>$shm_seq++ },
		"last_image_time"  => { "type"=>"time_t", "seq"=>$shm_seq++ },
		"last_event"       => { "type"=>"int", "seq"=>$shm_seq++ },
		"action"           => { "type"=>"enum", "seq"=>$shm_seq++ },
		"brightness"       => { "type"=>"int", "seq"=>$shm_seq++ },
		"hue"              => { "type"=>"int", "seq"=>$shm_seq++ },
		"colour"           => { "type"=>"int", "seq"=>$shm_seq++ },
		"contrast"         => { "type"=>"int", "seq"=>$shm_seq++ },
		"alarm_x"          => { "type"=>"int", "seq"=>$shm_seq++ },
		"alarm_y"          => { "type"=>"int", "seq"=>$shm_seq++ },
		}
	},
	"trigger_data" => { "type"=>"TriggerData", "seq"=>$shm_seq++, "contents"=> {
		"size"             => { "type"=>"int", "seq"=>$shm_seq++ },
		"trigger_state"    => { "type"=>"enum", "seq"=>$shm_seq++ },
		"trigger_score"    => { "type"=>"int", "seq"=>$shm_seq++ },
		"trigger_cause"    => { "type"=>"char[32]", "seq"=>$shm_seq++ },
		"trigger_text"     => { "type"=>"char[256]", "seq"=>$shm_seq++ },
		"trigger_showtext" => { "type"=>"char[32]", "seq"=>$shm_seq++ },
		}
	},
	"end" => { "seq"=>$shm_seq++, "size"=> 0 }
};

our $shm_size = 0;

our $shm_verified = {};

sub zmShmInit
{
	my $offset = 0;

	foreach my $section_data ( sort { $a->{seq} <=> $b->{seq} } values( %$shm_data ) )
	{
		$section_data->{offset} = $offset;
		$section_data->{align} = 4;

		if ( $section_data->{align} > 1 )
		{
			my $rem = $offset % $section_data->{align};
			if ( $rem > 0 )
			{
				$offset += ($section_data->{align} - $rem);
			}
		}
		foreach my $member_data ( sort { $a->{seq} <=> $b->{seq} } values( %{$section_data->{contents}} ) )
		{
			if ( $member_data->{type} eq "long" || $member_data->{type} eq "time_t" || $member_data->{type} eq "size_t" || $member_data->{type} eq "bool8" )
			{
				$member_data->{size} = $member_data->{align} = $native;
			}
			elsif ( $member_data->{type} eq "int" || $member_data->{type} eq "enum" || $member_data->{type} eq "bool4" )
			{
				$member_data->{size} = $member_data->{align} = 4;
			}
			elsif ( $member_data->{type} eq "short" )
			{
				$member_data->{size} = $member_data->{align} = 2;
			}
			elsif ( $member_data->{type} eq "char" || $member_data->{type} eq "bool1" )
			{
				$member_data->{size} = $member_data->{align} = 1;
			}
			elsif ( $member_data->{type} =~ /^char\[(\d+)\]$/ )
			{
				$member_data->{size} = $1;
				$member_data->{align} = 1;
			}
			else
			{
				Fatal( "Unexpected type '".$member_data->{type}."' found in shared memory definition." );
			}

			if ( $member_data->{align} > 1 && ($offset%$member_data->{align}) > 0 )
			{
				$offset += ($member_data->{align} - ($offset%$member_data->{align}));
			}
			$member_data->{offset} = $offset;
			$offset += $member_data->{size}
		}
		$section_data->{size} = $offset - $section_data->{offset};
	}

	$shm_size = $offset;
}

&zmShmInit();

sub zmShmGet( $ )
{
	my $monitor = shift;
	if ( !defined($monitor->{ShmId}) )
	{
		my $shm_key = hex(ZM_SHM_KEY)|$monitor->{Id};
		my $shm_id = shmget( $shm_key, $shm_size, 0 );
		if ( !defined($shm_id) )
		{
    		Error( sprintf( "Can't get shared memory id '%x', %d: $!\n", $shm_key, $monitor->{Id}, $! ) );
			return( undef );
		}
		$monitor->{ShmKey} = $shm_key;
		$monitor->{ShmId} = $shm_id;
	}
	return( !undef );
}

sub zmShmVerify( $ )
{
	my $monitor = shift;
	if ( !zmShmGet( $monitor ) )
	{
		return( undef );
	}

	my $shm_key = $monitor->{ShmKey};
	if ( !defined($shm_verified->{$shm_key}) )
	{
		my $shm_id = $monitor->{ShmId};
		my $sd_size = zmShmRead( $monitor, "shared_data:size", 1 );
		if ( $sd_size != $shm_data->{shared_data}->{size} )
		{
			if ( $sd_size )
			{
				Error( "Shared memory size conflict in shared_data, expected ".$shm_data->{shared_data}->{size}.", got ".$sd_size );
			}
			else
			{
				Debug( "Shared memory size conflict in shared_data, expected ".$shm_data->{shared_data}->{size}.", got ".$sd_size );
			}
			return( undef );
		}
		my $td_size = zmShmRead( $monitor, "trigger_data:size", 1 );
		if ( $td_size != $shm_data->{trigger_data}->{size} )
		{
			if ( $td_size )
			{
				Error( "Shared memory size conflict in trigger_data, expected ".$shm_data->{triggger_data}->{size}.", got ".$td_size );
			}
			else
			{
				Debug( "Shared memory size conflict in trigger_data, expected ".$shm_data->{triggger_data}->{size}.", got ".$td_size );
			}
			return( undef );
		}
		$shm_verified->{$shm_key} = !undef;
	}
	return( !undef );
}

sub zmShmRead( $$;$ )
{
	my $monitor = shift;
	my $fields = shift;
	my $nocheck = shift;

	if ( !$nocheck && !zmShmVerify( $monitor ) )
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
		if ( $type eq "long" || $type eq "time_t" || $type eq "size_t" || $type eq "bool8" )
		{
			( $value ) = unpack( "l!", $data );
		}
		elsif ( $type eq "int" || $type eq "enum" || $type eq "bool4" )
		{
			( $value ) = unpack( "l", $data );
		}
		elsif ( $type eq "short" )
		{
			( $value ) = unpack( "s", $data );
		}
		elsif ( $type eq "char" || $type eq "bool1" )
		{
			( $value ) = unpack( "c", $data );
		}
		elsif ( $type =~ /^char[\d+]$/ )
		{
			( $value ) = unpack( "Z".$size, $data );
		}
		else
		{
			Fatal( "Unexpected type '".$type."' found for '".$field."'" );
		}
		push( @values, $value );
	}
	if ( wantarray() )
	{
		return( @values )
	}
	return( $values[0] );
}

sub zmShmWrite( $$;$ )
{
	my $monitor = shift;
	my $field_values_ref = shift;
	my $nocheck = shift;

	if ( !$nocheck && !zmShmVerify( $monitor ) )
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
		Fatal( "Invalid shm selector '$field'" ) if ( !$section || !$element );

		my $offset = $shm_data->{$section}->{contents}->{$element}->{offset};
		my $type = $shm_data->{$section}->{contents}->{$element}->{type};
		my $size = $shm_data->{$section}->{contents}->{$element}->{size};

		my $data;
		if ( $type eq "long" || $type eq "time_t" || $type eq "size_t" || $type eq "bool8" )
		{
			$data = pack( "l!", $value );
		}
		elsif ( $type eq "int" || $type eq "enum" || $type eq "bool4" )
		{
			$data = pack( "l", $value );
		}
		elsif ( $type eq "short" )
		{
			$data = pack( "s", $value );
		}
		elsif ( $type eq "char" || $type eq "bool1" )
		{
			$data = pack( "c", $value );
		}
		elsif ( $type =~ /^char[\d+]$/ )
		{
			$data = pack( "Z".$size, $value );
		}
		else
		{
			Fatal( "Unexpected type '".$type."' found for '".$field."'" );
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
		return( $last_event );
	}
	elsif( $last_event != $last_event_id )
	{
		return( $last_event );
	}
	return( undef );
}

sub zmGetLastEvent( $ )
{
	my $monitor = shift;

	return( zmShmRead( $monitor, "shared_data:last_event" ) );
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

sub zmMonitorEnable( $ )
{
	my $monitor = shift;

	my $action = zmShmRead( $monitor, "shared_data:action" );
	$action |= ACTION_SUSPEND;
	zmShmWrite( $monitor, { "shared_data:action" => $action } );
}

sub zmMonitorDisable( $ )
{
	my $monitor = shift;

	my $action = zmShmRead( $monitor, "shared_data:action" );
	$action |= ACTION_RESUME;
	zmShmWrite( $monitor, { "shared_data:action" => $action } );
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
