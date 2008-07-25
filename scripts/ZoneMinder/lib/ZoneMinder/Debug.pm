# ==========================================================================
#
# ZoneMinder Debug Module, $Date$, $Revision$
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
our %EXPORT_TAGS = (
	'constants' => [ qw(
		DBG_DEBUG
		DBG_INFO
		DBG_WARNING
		DBG_ERROR
		DBG_FATAL
		DBG_NOSYSLOG
	) ],
	'functions' => [ qw(
		zmDbgInit
		zmDbgTerm
		zmDbgReinit
		zmDbgSetSignal
		zmDbgClearSignal
		zmDbgId
		zmDbgLevel
		zmDbgCarp
		zmDbgDebugOn
		Debug
		Info
		Warning
		Error
		Fatal
		Panic
	) ]
);
push( @{$EXPORT_TAGS{all}}, @{$EXPORT_TAGS{$_}} ) foreach keys %EXPORT_TAGS;

our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );

our @EXPORT = qw();

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Debug Facilities
#
# ==========================================================================

use ZoneMinder::Config qw(:all);

use Carp;
use POSIX;
use IO::Handle;
use Time::HiRes qw/gettimeofday/;
use Sys::Syslog qw(:DEFAULT setlogsock);

use constant DBG_DEBUG => 1;
use constant DBG_INFO => 0;
use constant DBG_WARNING => -1;
use constant DBG_ERROR => -2;
use constant DBG_FATAL => -3;
use constant DBG_NOSYSLOG => -4;

our $dbg_initialised = undef;
our $dbg_id = "zmundef";
our $dbg_level = DBG_INFO;
our $dbg_carp = 0;
our $dbg_to_log = 1;
our $dbg_to_term = 0;
our $dbg_to_syslog = DBG_INFO;

our $dbg_log_file = "";

our $_dbg_has_term = 0;

our %dbg_codes = (
	1 => "DBG",
	0 => "INF",
	-1 => "WAR",
	-2 => "ERR",
	-3 => "FAT",
);

our %dbg_priorities = (
	1 => "debug",
	0 => "info",
	-1 => "warning",
	-2 => "err",
	-3 => "err",
);

sub zmDbgInit
{
	my $id = shift;
	my %options = @_;

	if ( $dbg_initialised )
	{
		_dbgCloseLog();
		_dbgCloseSyslog();
	}

	$dbg_id = $id if ( $id );
	$dbg_level = $options{level} if ( defined($options{level}) );
	$dbg_level = $options{carp} if ( defined($options{carp}) );
	$dbg_to_log = $options{to_log} if ( defined($options{to_log}) );
	$dbg_to_term = $options{to_term} if ( defined($options{to_term}) );
	$dbg_to_syslog = $options{to_syslog} if ( defined($options{to_syslog}) );

	_dbgOpenSyslog();
	_dbgOpenLog();

	$_dbg_has_term = -t STDERR;
	$dbg_initialised = !undef;
}

sub zmDbgTerm
{
	if ( $dbg_initialised )
	{
		_dbgCloseLog();
		_dbgCloseSyslog();
	}
	$dbg_initialised = undef;
}

sub zmDbgReinit
{
    my $saved_errno = $!;
	if ( $dbg_initialised )
	{
		_dbgCloseLog();
		#_dbgCloseSyslog();

	    #_dbgOpenSyslog();
	    _dbgOpenLog();
    }
    zmDbgSetSignal();
    $! = $saved_errno;
}

sub zmDbgSetSignal
{
    $SIG{HUP} = \&zmDbgReinit;
}

sub zmDbgClearSignal
{
    $SIG{HUP} = 'DEFAULT';
}

sub zmDbgId
{
	$dbg_id = $_[0] if ( @_ );
	return( $dbg_id );
}

sub zmDbgLevel
{
	$dbg_level = $_[0] if ( @_ );
	return( $dbg_level );
}

sub zmDbgCarp
{
	$dbg_carp = $_[0] if ( @_ );
	return( $dbg_carp );
}

sub zmDbgToTerm
{
	$dbg_to_term = $_[0] if ( @_ );
	return( $dbg_to_term );
}

sub zmDbgToLog
{
	if ( @_ )
	{
		if ( $dbg_to_log != $_[0] )
		{
			_dbgCloseLog();
			$dbg_to_log = $_[0];
			_dbgOpenLog();
		}
	}
	return( $dbg_to_log );
}

sub zmDbgToSyslog
{
	if ( @_ )
	{
		if ( $dbg_to_syslog != $_[0] )
		{
			_dbgCloseSyslog();
			$dbg_to_syslog = $_[0];
			_dbgOpenSyslog();
		}
	}
	return( $dbg_to_syslog );
}

sub zmDbgDebugOk
{
	return ( $dbg_level >= DBG_DEBUG );
}

sub _dbgOpenSyslog
{
	if ( $dbg_to_syslog > DBG_NOSYSLOG )
	{
		#setlogsock( "stream", "/tmp/xxx.log" );
		openlog( $dbg_id, "pid,ndelay", "local1" )
	}
}

sub _dbgCloseSyslog
{
	if ( $dbg_to_syslog > DBG_NOSYSLOG )
	{
		closelog();
	}
}

sub _dbgOpenLog
{
	if ( $dbg_to_log )
	{
		$dbg_log_file = ZM_PATH_LOGS."/".$dbg_id.".log";
		if ( open( LOG, ">>".$dbg_log_file ) )
		{
			LOG->autoflush();

			my $web_uid = (getpwnam( ZM_WEB_USER ))[2];
			my $web_gid = (getgrnam( ZM_WEB_GROUP ))[2];
			if ( $> == 0 )
			{
				chown( $web_uid, $web_gid, $dbg_log_file ) or croak( "Can't change permissions on log file: $!" )
			}
		}
		else
		{
			warn( "Can't open log file '$dbg_log_file': $!" );
			$dbg_to_log = 0;
		}
	}
}

sub _dbgCloseLog
{
	if ( $dbg_to_log )
	{
		close( LOG );
	}
}

sub _dbgPrint
{
	my $level = shift;
	my $string = shift;
	my $carp = shift;

	if ( $level <= $dbg_level )
	{
		if ( !$dbg_initialised )
		{
			zmDbgInit( $dbg_id );
		}

		$string =~ s/[\r\n]+$//g;

		my $code = $dbg_codes{$level};

		my ($seconds, $microseconds) = gettimeofday();
		my $message = sprintf( "%s.%06d %s[%d].%s [%s]", strftime( "%x %H:%M:%S", localtime( $seconds ) ), $microseconds, $dbg_id, $$, $code, $string );
		if ( $dbg_carp || $carp )
		{
			$message = Carp::shortmess( $message );
		}
		else
		{
			$message = $message."\n";
		}
		print( STDERR $message ) if ( $dbg_to_term == 1 || ($dbg_to_term == 2 && $_dbg_has_term) );
		print( LOG $message ) if ( $dbg_to_log );
		syslog( $dbg_priorities{$level}, $code." [%s]", $string ) if ( $level <= $dbg_to_syslog );
	}
}

sub Debug
{
	_dbgPrint( DBG_DEBUG, @_ );
}

sub Info
{
	_dbgPrint( DBG_INFO, @_ );
}

sub Warning
{
	_dbgPrint( DBG_WARNING, @_ );
}

sub Error
{
	_dbgPrint( DBG_ERROR, @_ );
}

sub Fatal
{
	_dbgPrint( DBG_FATAL, @_ );
	confess( $_[0] );
}

sub Panic
{
	Fatal( @_ );
}

1;
__END__

=head1 NAME

ZoneMinder::Debug - ZoneMinder Debug module

=head1 SYNOPSIS

  use ZoneMinder::Debug;
  use ZoneMinder::Debug qw(:all);

  zmDbgInit( "myproc", DBG_DEBUG );

  Debug( "This is what is happening" );
  Info( "Something interesting is happening" );
  Warning( "Something might be going wrong." );
  Error( "Something has gone wrong!!" );
  Fatal( "Something has gone badly wrong, gotta stop!!" );

=head1 DESCRIPTION

The ZoneMinder:Debug module contains the common debug and error reporting routines used by the ZoneMinder scripts.

To use debug in your scripts you need to include this module, and call zmDbgInit. Thereafter you can sprinkle Debug or Error calls etc throughout the code safe in the knowledge that they will be reported to your error log, and possibly the syslogger, in a meaningful and consistent format.

Debug is discussed in terms of levels where 1 and above (currently only 1 for scripts) is considered debug, 0 is considered as informational, -1 is a warning, -2 is an error and -3 is a fatal error or panic. Where levels are mentioned below as thresholds the value given and anything with a lower level (ie. more serious) will be included.

=head1 METHODS

=over 4

=item zmDbgInit ( $id, %options );

Initialises the debug and prepares the logging for forthcoming operations. If not called explicitly it will be called by the first debug call in your script, but with default (and probably meaningless) options. The only compulsory arguments are $id which must be a string that will identify debug coming from this script in mixed logs. Other options may be provided as below,

 Option       Default        Description
 ---------    ---------      -----------
 level        DBG_INFO       The initial debug level which defines which statements are output and which are ignored
 carp         0              Whether to use the Carp::shortmess format in debug statements to identify where the debug was emitted from
 to_log       1              Whether to write debug to a log file of the format of <id>.log in the standard log directory
 to_term      0              Whether to write debug to terminal standard error, 0 is no, 1 is yes, 2 is write only if terminal
 to_syslog    DBG_INFO       At what level debug is written to syslog. To disable entirely set this to DBG_NOSYSLOG

=item zmDbgTerm ();

Used to end the debug session and close any logs etc. Not usually necessary.

=item $id        = zmDbgId ( [$id] );

=item $level     = zmDbgLevel ( [$level] );

=item $carp      = zmDbgId ( [$carp] );

=item $to_log    = zmDbgToLog ( [$to_log] );

=item $to_term   = zmDbgToTerm ( [$to_term] );

=item $to_syslog = zmDbgToSyslog ( [$to_syslog] );

These methods can be used to get and set the current settings as defined in zmDbgInit.

=item Debug( $string );

This method will output a debug message if the current debug level permits it, otherwise does nothing. This message will be tagged with the DBG string in the logs.

=item Info( $string );

This method will output an informational message if the current debug level permits it, otherwise does nothing. This message will be tagged with the INF string in the logs.

=item Warning( $string );

This method will output a warning message if the current debug level permits it, otherwise does nothing. This message will be tagged with the WAR string in the logs.

=item Error( $string );

This method will output an error message if the current debug level permits it, otherwise does nothing. This message will be tagged with the ERR string in the logs.

=item Fatal( $string );

This method will output a fatal error message and then die if the current debug level permits it, otherwise does nothing. This message will be tagged with the FAT string in the logs.

=item Panic( $string );

Synonym for Fatal.

=head2 EXPORT

None by default.
The :constants tag will export the debug constants which define the various levels of debug
The :variables tag will export variables containing the current debug id and level
The :functions tag will export the debug functions. This or :all is what you would normally use.
The :all tag will export all above symbols.


=head1 SEE ALSO

Carp
Sys::Syslog

The ZoneMinder README file Troubleshooting section for an extended discussion on the use and configuration of syslog with ZoneMinder.

http://www.zoneminder.com

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
