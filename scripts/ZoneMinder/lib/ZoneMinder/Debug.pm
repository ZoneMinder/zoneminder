# ==========================================================================
#
# ZoneMinder Debug Module, $Date$, $Revision$
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
# This module contains the debug definitions and functions used by the rest 
# of the ZoneMinder scripts
#
package ZoneMinder::Debug;

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
our %EXPORT_TAGS = ( 'all' => [ qw(
	
) ] );

our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );

our @EXPORT = qw(
	Debug
	Info
	Warning
	Error
	Fatal
);

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Debug Facilities
#
# ==========================================================================

use Carp;
use POSIX;
use Sys::Syslog;
use Time::HiRes qw/gettimeofday/;

use constant CARP_DEBUG => 0;

our $dbg_initialised = undef;
our $dbg_id = "zm?";
our $dbg_level = 0;

sub zmDbgInit
{
	my $id = shift;
	my $level = shift;

	if ( $id )
	{
		$dbg_id = $id;
	}
	else
	{
		$dbg_id = main::DBG_ID;
	}
	if ( defined($level) )
	{
		$dbg_level = $level;
	}
	else
	{
		$dbg_level = main::DBG_LEVEL;
	}

	openlog( $dbg_id, "pid,ndelay", "local1" );
	$dbg_initialised = !undef;
}

sub dbgPrint
{
	my $code = shift;
	my $string = shift;
	my $line = shift;

	$string =~ s/[\r\n]+$//g;

	if ( !$dbg_initialised )
	{
		zmDbgInit();
	}

	my ($seconds, $microseconds) = gettimeofday();
	if ( $line )
	{
		my $file = __FILE__;
		$file =~ s|^.*/||g;
		if ( CARP_DEBUG )
		{
			print( STDERR Carp::shortmess( sprintf( "%s.%06d %s[%d].%s-%s/%d [%s]", strftime( "%x %H:%M:%S", localtime( $seconds ) ), $microseconds, $dbg_id, $$, $file, $line, $code, $string ) ) );
		}
		else
		{
			printf( STDERR "%s.%06d %s[%d].%s-%s/%d [%s]\n", strftime( "%x %H:%M:%S", localtime( $seconds ) ), $microseconds, $dbg_id, $$, $file, $line, $code, $string );
		}
	}
	else
	{
		if ( CARP_DEBUG )
		{
			printf( STDERR Carp::shortmess( sprintf( "%s.%06d %s[%d].%s [%s]", strftime( "%x %H:%M:%S", localtime( $seconds ) ), $microseconds, $dbg_id, $$, $code, $string ) ) );
		}
		else
		{
			printf( STDERR "%s.%06d %s[%d].%s [%s]\n", strftime( "%x %H:%M:%S", localtime( $seconds ) ), $microseconds, $dbg_id, $$, $code, $string );
		}
	}
}

sub Debug
{
	dbgPrint( "DBG", $_[0] ) if ( $dbg_level >= 1 );
}

sub Info
{
	dbgPrint( "INF", $_[0] ) if ( $dbg_level >= 0 );
	syslog( "info", "INF [%s]", $_[0] );
}

sub Warning
{
	dbgPrint( "WAR", $_[0] ) if ( $dbg_level >= -1 );
	syslog( "warning", "WAR [%s]", $_[0] );
}

sub Error
{
	dbgPrint( "ERR", $_[0] ) if ( $dbg_level >= -2 );
	syslog( "err", "ERR [%s]", $_[0] );
}

sub Fatal
{
	dbgPrint( "FAT", $_[0] ) if ( $dbg_level >= -3 );
	syslog( "err", "ERR [%s]", $_[0] );
	confess( $_[0] );
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Debug - Perl extension for blah blah blah

=head1 SYNOPSIS

  use ZoneMinder::Debug;
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
