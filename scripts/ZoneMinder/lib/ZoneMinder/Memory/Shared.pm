# ==========================================================================
#
# ZoneMinder Shared Memory Access Module, $Date: 2007-08-29 19:11:09 +0100 (Wed, 29 Aug 2007) $, $Revision: 2175 $
# Copyright (C) 2001-2008  Philip Coombes
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
package ZoneMinder::Memory::Shared;

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
	'functions' => [ qw(
        zmMemKey
        zmMemAttach
        zmMemDetach
        zmMemGet
        zmMemPut
        zmMemClean
	) ],
);
push( @{$EXPORT_TAGS{all}}, @{$EXPORT_TAGS{$_}} ) foreach keys %EXPORT_TAGS;

our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );

our @EXPORT = @EXPORT_OK;

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Shared Memory Facilities
#
# ==========================================================================

use ZoneMinder::Config qw(:all);
use ZoneMinder::Debug qw(:all);

sub zmMemKey( $ )
{
	my $monitor = shift;
	return( $monitor->{ShmKey} );
}

sub zmMemAttach( $$ )
{
	my $monitor = shift;
	my $size = shift;
	if ( !defined($monitor->{ShmId}) )
	{
		my $shm_key = (hex(ZM_SHM_KEY)&0xffff0000)|$monitor->{Id};
		my $shm_id = shmget( $shm_key, $size, 0 );
		if ( !defined($shm_id) )
		{
    		Error( sprintf( "Can't get shared memory id '%x', %d: $!\n", $shm_key, $monitor->{Id} ) );
			return( undef );
		}
		$monitor->{ShmKey} = $shm_key;
		$monitor->{ShmId} = $shm_id;
	}
	return( !undef );
}

sub zmMemDetach( $ )
{
	my $monitor = shift;

	delete $monitor->{ShmId};
}

sub zmMemGet( $$$ )
{
	my $monitor = shift;
	my $offset = shift;
	my $size = shift;

	my $shm_key = $monitor->{ShmKey};
	my $shm_id = $monitor->{ShmId};
	
    my $data;
    if ( !shmread( $shm_id, $data, $offset, $size ) )
    {
        Error( sprintf( "Can't read from shared memory '%x/%d': $!", $shm_key, $shm_id ) );
        return( undef );
    }
	return( $data );
}

sub zmMemPut( $$$$ )
{
	my $monitor = shift;
	my $offset = shift;
	my $size = shift;
	my $data = shift;

	my $shm_key = $monitor->{ShmKey};
	my $shm_id = $monitor->{ShmId};
	
    if ( !shmwrite( $shm_id, $data, $offset, $size ) )
    {
        Error( sprintf( "Can't write to shared memory '%x/%d': $!", $shm_key, $shm_id ) );
        return( undef );
    }
	return( !undef );
}

sub zmMemClean
{
    Debug( "Removing shared memory\n" );
    # Find ZoneMinder shared memory
    my $command = "ipcs -m | grep '^".substr( sprintf( "0x%x", hex(ZM_SHM_KEY) ), 0, -2 )."'";
    Debug( "Checking for shared memory with '$command'\n" );
    open( CMD, "$command |" ) or Fatal( "Can't execute '$command': $!" );
    while( <CMD> )
    {
        chomp;
        my ( $key, $id ) = split( /\s+/ );
        if ( $id =~ /^(\d+)/ )
        {
            $id = $1;
            $command = "ipcrm shm $id";
            Debug( "Removing shared memory with '$command'\n" );
            qx( $command );
        }
    }
    close( CMD );
}

1;
__END__
