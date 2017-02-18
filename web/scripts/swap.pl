#!/usr/bin/perl -w
#
# ==========================================================================
#
# ZoneMinder Find and Replace Utility, $Date$, $Revision$
# Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
# ==========================================================================
#
# General purpose find and replace utility.
#
# ==========================================================================
#

use strict;

$| = 1;

exit if ( @ARGV < 3 );
my $from = shift @ARGV;
my $to = shift @ARGV;
foreach my $file ( @ARGV )
{
	my $tmpfile = "${file}.tmp.swap";
	open( FROMFILE, $file ) or die( "Can't open '$file' for reading" );
	open( TOFILE, ">${tmpfile}" ) or die( "Can't open '$tmpfile' for writing" );
	my $count = 0;
	while( <FROMFILE> )
	{
		$count += s/$from/$to/g;
		print( TOFILE );
	}
	close( FROMFILE );
	close( TOFILE );
	if ( $count )
	{
		rename( $tmpfile, $file ) or die( "Can't rename '$tmpfile' to '$file'" );
	}
	else
	{
		unlink( $tmpfile ) or die( "Can'delete rename '$tmpfile' to '$file'" );
	}
	if ( $count )
	{
		print( "Processed $file" );
		print( ": $count changes" );
		print( "\n" );
	}
}
