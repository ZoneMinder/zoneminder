############################################################################
#
# ZoneMinder Logger Module
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
############################################################################
#
# This module contains the debug definitions and functions used by the rest
# of the ZoneMinder scripts
#
package ZoneMinder::Logger;

use 5.006;
use strict;
use warnings;

require Exporter;
require ZoneMinder::Base;

our @ISA = qw(Exporter ZoneMinder::Base);

# Items to export into callers namespace by default. Note: do not export
# names by default without a very good reason. Use EXPORT_OK instead.
# Do not simply export all your public functions/methods/constants.

# This allows declaration   use ZoneMinder ':all';
# If you do not need this, moving things directly into @EXPORT or @EXPORT_OK
# will save memory.
our %EXPORT_TAGS = (
    constants => [ qw(
      DEBUG
      INFO
      WARNING
      ERROR
      FATAL
      PANIC
      NOLOG
      ) ],
    functions => [ qw(
      logInit
      logReinit
      logTerm
      logSetSignal
      logClearSignal
      logDebugging
      logLevel
      logTermLevel
      logDatabaseLevel
      logFileLevel
      logSyslogLevel
      Mark
      Dump
      Debug
      Info
      Warning
      Error
      Fatal
      Panic
      ) ]
    );

push( @{$EXPORT_TAGS{all}}, @{$EXPORT_TAGS{$_}} ) foreach keys %EXPORT_TAGS;

our @EXPORT_OK = ( @{ $EXPORT_TAGS{all} } );

our @EXPORT = qw();

our $VERSION = $ZoneMinder::Base::VERSION;

############################################################################
#
# Logger Facilities
#
############################################################################

require ZoneMinder::Config;

use DBI;
use Carp;
require POSIX;
use IO::Handle;
use Data::Dumper;
use Time::HiRes qw/gettimeofday/;
use Sys::Syslog;

use constant {
  DEBUG => 1,
  INFO => 0,
  WARNING => -1,
  ERROR => -2,
  FATAL => -3,
  PANIC => -4,
  NOLOG => -5
};

our %codes = (
    &DEBUG => 'DBG',
    &INFO => 'INF',
    &WARNING => 'WAR',
    &ERROR => 'ERR',
    &FATAL => 'FAT',
    &PANIC => 'PNC',
    &NOLOG => 'OFF'
    );

our %priorities = (
    &DEBUG => 'debug',
    &INFO => 'info',
    &WARNING => 'warning',
    &ERROR => 'err',
    &FATAL => 'err',
    &PANIC => 'err'
    );

our $logger;
our $LOGFILE;
our $do_log_rotate = 0;

sub new {
  my $class = shift;
  my $this = {};

  $this->{initialised} = undef;

  ( $this->{id} ) = $0 =~ m|^(?:.*/)?([^/]+?)(?:\.[^/.]+)?$|;
  $this->{idRoot} = $this->{id};
  $this->{idArgs} = '';

  $this->{level} = INFO;

  # Detect if we are running in a terminal session, if so, default log level to INFO
  $this->{hasTerm} = -t STDERR;
  if ( $this->{hasTerm} ) {
    $this->{termLevel} = INFO;
  } else {
    $this->{termLevel} = NOLOG;
  }
  $this->{databaseLevel} = NOLOG;
  $this->{fileLevel} = NOLOG;
  $this->{syslogLevel} = NOLOG;
  $this->{effectiveLevel} = INFO;

  $this->{autoFlush} = 1;

  ( $this->{fileName} = $0 ) =~ s|^.*/||;
  $this->{logPath} = $ZoneMinder::Config::Config{ZM_PATH_LOGS};
  $this->{logFile} = $this->{logPath}.'/'.$this->{id}.'.log';
  ($this->{logFile}) = $this->{logFile} =~ /^([\w\.\/]+)$/;

  $this->{trace} = 0;

  bless( $this, $class );
  return $this;
}

sub BEGIN {
# Fake the config variables that are used in case they are not defined yet
# Only really necessary to support upgrade from previous version
  if ( !eval('defined($ZoneMinder::Config::Config{ZM_LOG_DEBUG})') ) {
    no strict 'subs';
    no strict 'refs';
    my %dbgConfig = (
        ZM_LOG_LEVEL_DATABASE => 0,
        ZM_LOG_LEVEL_FILE => 0,
        ZM_LOG_LEVEL_SYSLOG => 0,
        ZM_LOG_DEBUG => 0,
        ZM_LOG_DEBUG_TARGET => '',
        ZM_LOG_DEBUG_LEVEL => 1,
        ZM_LOG_DEBUG_FILE => '' 
        );
    while ( my ( $name, $value ) = each( %dbgConfig ) ) {
      *{$name} = sub { $value };
    }
    use strict 'subs';
    use strict 'refs';
  }
}

sub DESTROY {
  my $this = shift;
  $this->terminate();
}

sub initialise( @ ) {
  my $this = shift;
  my %options = @_;

  $this->{hasTerm} = -t STDERR;
  $this->{id} = $options{id} if defined($options{id});

  $this->{logPath} = $options{logPath} if defined($options{logPath});

  my $tempLogFile;
  $tempLogFile = $this->{logPath}.'/'.$this->{id}.'.log';
  $tempLogFile = $options{logFile} if defined($options{logFile});
  if ( my $logFile = $this->getTargettedEnv('LOG_FILE') ) {
    $tempLogFile = $logFile;
  }
  ($tempLogFile) = $tempLogFile =~ /^([\w\.\/]+)$/;

  my $tempLevel = INFO;
  my $tempTermLevel = $this->{termLevel};
  my $tempDatabaseLevel = $this->{databaseLevel};
  my $tempFileLevel = $this->{fileLevel};
  my $tempSyslogLevel = $this->{syslogLevel};

  $tempTermLevel = $options{termLevel} if defined($options{termLevel});
  if ( defined($options{databaseLevel}) ) {
    $tempDatabaseLevel = $options{databaseLevel};
  } else {
    $tempDatabaseLevel = $ZoneMinder::Config::Config{ZM_LOG_LEVEL_DATABASE};
  }
  if ( defined($options{fileLevel}) ) {
    $tempFileLevel = $options{fileLevel};
  } else {
    $tempFileLevel = $ZoneMinder::Config::Config{ZM_LOG_LEVEL_FILE};
  }
  if ( defined($options{syslogLevel}) ) {
    $tempSyslogLevel = $options{syslogLevel};
  } else {
    $tempSyslogLevel = $ZoneMinder::Config::Config{ZM_LOG_LEVEL_SYSLOG};
  }

  if ( defined($ENV{LOG_PRINT}) ) {
    $tempTermLevel = $ENV{LOG_PRINT}? DEBUG : NOLOG;
  }

  my $level;
  $tempLevel = $level if defined($level = $this->getTargettedEnv('LOG_LEVEL'));
  $tempTermLevel = $level if defined($level = $this->getTargettedEnv('LOG_LEVEL_TERM'));
  $tempDatabaseLevel = $level if defined($level = $this->getTargettedEnv('LOG_LEVEL_DATABASE'));
  $tempFileLevel = $level if defined($level = $this->getTargettedEnv('LOG_LEVEL_FILE'));
  $tempSyslogLevel = $level if defined($level = $this->getTargettedEnv('LOG_LEVEL_SYSLOG'));

  if ( $ZoneMinder::Config::Config{ZM_LOG_DEBUG} ) {
    # Splitting on an empty string doesn't return an empty string, it returns an empty array
    foreach my $target ( $ZoneMinder::Config::Config{ZM_LOG_DEBUG_TARGET} ? split(/\|/, $ZoneMinder::Config::Config{ZM_LOG_DEBUG_TARGET}) : '' ) {
      if ( $target eq $this->{id}
          || $target eq '_'.$this->{id}
          || $target eq $this->{idRoot}
          || $target eq '_'.$this->{idRoot}
          || $target eq ''
         ) {
        if ( $ZoneMinder::Config::Config{ZM_LOG_DEBUG_LEVEL} > NOLOG ) {
          $tempLevel = $this->limit( $ZoneMinder::Config::Config{ZM_LOG_DEBUG_LEVEL} );
          if ( $ZoneMinder::Config::Config{ZM_LOG_DEBUG_FILE} ne '' ) {
            $tempLogFile = $ZoneMinder::Config::Config{ZM_LOG_DEBUG_FILE};
            $tempFileLevel = $tempLevel;
          }
        }
      }
    }
  }

  $this->logFile( $tempLogFile );

  $this->termLevel( $tempTermLevel );
  $this->databaseLevel( $tempDatabaseLevel );
  $this->fileLevel( $tempFileLevel );
  $this->syslogLevel( $tempSyslogLevel );

  $this->level( $tempLevel );

  $this->{trace} = $options{trace} if defined($options{trace});

  $this->{autoFlush} = $ENV{LOG_FLUSH}?1:0 if defined($ENV{LOG_FLUSH});

  $this->{initialised} = !undef;

  # this function can get called on a previously initialized log Object, so clean any sth's
  $this->{sth} = undef;

  Debug( 'LogOpts: level='.$codes{$this->{level}}
      .'/'.$codes{$this->{effectiveLevel}}
      .', screen='.$codes{$this->{termLevel}}
      .', database='.$codes{$this->{databaseLevel}}
      .', logfile='.$codes{$this->{fileLevel}}
      .'->'.$this->{logFile}
      .', syslog='.$codes{$this->{syslogLevel}}
      );
}

sub terminate {
  my $this = shift;
  return unless ( $this->{initialised} );
  $this->syslogLevel( NOLOG );
  $this->fileLevel( NOLOG );
  $this->databaseLevel( NOLOG );
  $this->termLevel( NOLOG );
}

sub reinitialise {
  my $this = shift;

  # So if the logger is initialized, we just return.  Since the logger is NORMALLY initialized... the rest of this function never executes.
  return unless $this->{initialised};

# Bit of a nasty hack to reopen connections to log files and the DB
  my $syslogLevel = $this->syslogLevel();
  $this->syslogLevel(NOLOG);
  $this->syslogLevel($syslogLevel) if $syslogLevel > NOLOG;

  my $logfileLevel = $this->fileLevel();
  $this->fileLevel(NOLOG);
  $this->fileLevel($logfileLevel) if $logfileLevel > NOLOG;

  my $databaseLevel = $this->databaseLevel();
  $this->databaseLevel(NOLOG);
  $this->databaseLevel($databaseLevel) if $databaseLevel > NOLOG;

  $this->{hasTerm} = -t STDERR;
  my $termLevel = $this->termLevel();
  $this->termLevel(NOLOG);
  $this->termLevel($termLevel) if $termLevel > NOLOG;
}

# Prevents undefined logging levels
sub limit {
  my $this = shift;
  my $level = shift;
  return(DEBUG) if $level > DEBUG;
  return(NOLOG) if $level < NOLOG;
  return($level);
}

sub getTargettedEnv {
  my $this = shift;
  my $name = shift;
  my $envName = $name.'_'.$this->{id};
  my $value;
  $value = $ENV{$envName} if defined($ENV{$envName});
  if ( !defined($value) and ($this->{id} ne $this->{idRoot}) ) {
    $envName = $name.'_'.$this->{idRoot};
    $value = $ENV{$envName} if defined($ENV{$envName});
  }
  if ( !defined($value) ) {
    $value = $ENV{$name} if defined($ENV{$name});
  }
  if ( defined($value) ) {
    ( $value ) = $value =~ m/(.*)/;
  }
  return $value;
}

sub fetch {
  if ( !$logger ) {
    $logger = ZoneMinder::Logger->new();
    $logger->initialise( syslogLevel=>INFO, databaseLevel=>INFO );
  }
  return $logger;
}

sub id {
  my $this = shift;
  my $id = shift;
  if ( defined($id) and ($this->{id} ne $id) ) {
# Remove whitespace
    $id =~ s/\S//g;
# Replace non-alphanum with underscore
    $id =~ s/[^a-zA-Z_]/_/g;

    if ( $this->{id} ne $id ) {
      $this->{id} = $this->{idRoot} = $id;
      if ( $id =~ /^([^_]+)_(.+)$/ ) {
        $this->{idRoot} = $1;
        $this->{idArgs} = $2;
      }
    }
  }
  return $this->{id};
}

sub level {
  my $this = shift;
  my $level = shift;
  if ( defined($level) ) {
    $this->{level} = $this->limit($level);

    # effectiveLevel is the highest logging level used by any of the outputs.
    $this->{effectiveLevel} = NOLOG;
    $this->{effectiveLevel} = $this->{termLevel} if $this->{termLevel} > $this->{effectiveLevel};
    $this->{effectiveLevel} = $this->{databaseLevel} if $this->{databaseLevel} > $this->{effectiveLevel};
    $this->{effectiveLevel} = $this->{fileLevel} if $this->{fileLevel} > $this->{effectiveLevel};
    $this->{effectiveLevel} = $this->{syslogLevel} if $this->{syslogLevel} > $this->{effectiveLevel};

    # ICON: I am remarking this out because I don't see the point of having an effective level, if we are just going to set it to level.
    #$this->{effectiveLevel} = $this->{level} if ( $this->{level} > $this->{effectiveLevel} );
    # ICON: The point is that LOG_DEBUG can be set either in db or in env var and will get passed in here.
    # So this will turn on debug, even if not output has Debug level turned on.  I think it should be the other way around

    # ICON: Let's try this line instead.  effectiveLevel is 1 DEBUG from above, but LOG_DEBUG is off, then $this->level will be 0, and
    # so effectiveLevel will become 0
    $this->{effectiveLevel} = $this->{level} if ( $this->{level} < $this->{effectiveLevel} );
  }
  return $this->{level};
}

sub debugOn {
  my $this = shift;
  return $this->{effectiveLevel} >= DEBUG;
}

sub trace {
  my $this = shift;
  $this->{trace} = $_[0] if @_;
  return $this->{trace};
}

sub termLevel {
  my $this = shift;
  my $termLevel = shift;
  if ( defined($termLevel) ) {
    # What is the point of this next lint if we are just going to overwrite it with the next line? I propose we move it down one line or remove it altogether
    $termLevel = NOLOG if !$this->{hasTerm};
    $termLevel = $this->limit($termLevel);
    if ( $this->{termLevel} != $termLevel ) {
      $this->{termLevel} = $termLevel;
    }
  }
  return $this->{termLevel};
}

sub databaseLevel {
  my $this = shift;
  my $databaseLevel = shift;
  if ( defined($databaseLevel) ) {
    $databaseLevel = $this->limit($databaseLevel);
    if ( $databaseLevel > NOLOG ) {
      $this->{dbh} = ZoneMinder::Database::zmDbConnect();
    } else {
      undef($this->{dbh});
    }
    $this->{sth} = undef;
    $this->{databaseLevel} = $databaseLevel;
  }
  return $this->{databaseLevel};
}

sub fileLevel {
  my $this = shift;
  my $fileLevel = shift;
  if ( defined($fileLevel) ) {
    $fileLevel = $this->limit($fileLevel);
    # The filename might have changed, so always close and re-open
    $this->closeFile() if ( $this->{fileLevel} > NOLOG );
    $this->{fileLevel} = $fileLevel;
    $this->openFile() if ( $this->{fileLevel} > NOLOG );
  }
  return $this->{fileLevel};
}

sub syslogLevel {
  my $this = shift;
  my $syslogLevel = shift;
  if ( defined($syslogLevel) ) {
    $syslogLevel = $this->limit($syslogLevel);
    if ( $this->{syslogLevel} != $syslogLevel ) {
      $this->closeSyslog() if ( $syslogLevel <= NOLOG && $this->{syslogLevel} > NOLOG );
      $this->openSyslog() if ( $syslogLevel > NOLOG && $this->{syslogLevel} <= NOLOG );
      $this->{syslogLevel} = $syslogLevel;
    }
  }
  return( $this->{syslogLevel} );
}

sub openSyslog {
  my $this = shift;
  openlog( $this->{id}, 'pid', 'local1' );
}

sub closeSyslog {
  my $this = shift;
  closelog();
}

sub logFile {
  my $this = shift;
  my $logFile = shift;
  if ( $logFile =~ /^(.+)\+$/ ) {
    $this->{logFile} = $1.'.'.$$;
  } else {
    $this->{logFile} = $logFile;
  }
}

sub openFile {
  my $this = shift;
  if ( open($LOGFILE, '>>', $this->{logFile}) ) {
    $LOGFILE->autoflush() if $this->{autoFlush};

    my $webUid = (getpwnam($ZoneMinder::Config::Config{ZM_WEB_USER}))[2];
    Error("Can't get uid for $ZoneMinder::Config::Config{ZM_WEB_USER}") if ! defined $webUid;
    my $webGid = (getgrnam($ZoneMinder::Config::Config{ZM_WEB_GROUP}))[2];
    Error("Can't get gid for $ZoneMinder::Config::Config{ZM_WEB_USER}") if ! defined $webGid;
    if ( $> == 0 ) {
      # If we are root, we want to make sure that www-data or whatever owns the file
      chown($webUid, $webGid, $this->{logFile} ) or
        Error("Can't change permissions on log file $$this{logFile}: $!");
    } # end if are root
  } else {
    $this->fileLevel(NOLOG);
    $this->termLevel(INFO);
    Error("Can't open log file $$this{logFile}: $!");
  }
}

sub closeFile {
  #my $this = shift;
  close($LOGFILE) if fileno($LOGFILE);
}

sub logPrint {
  my $this = shift;
  my $level = shift;
  my $string = shift;

  if ( $do_log_rotate ) {
    $do_log_rotate = 0;
    # Too heavy to do this in the signal handler,
    # so we just set a flag and logs will be rotated on the next call to log something
    $this->reinitialise();
    # Don't know why this would be needed
    #logSetSignal();
  }

  my ($caller, undef, $line) = @_ ? @_ : caller;

  if ( $level <= $this->{effectiveLevel} ) {
    $string =~ s/[\r\n]+$//g;
    if ( $level <= $this->{syslogLevel} ) {
      syslog($priorities{$level}, $codes{$level}.' [%s]', $string);
    }

    my ($seconds, $microseconds) = gettimeofday();
    if ( $level <= $this->{fileLevel} or $level <= $this->{termLevel} ) {
      my $message = sprintf(
          '%s.%06d %s[%d].%s [%s:%d] [%s]'
          , POSIX::strftime('%x %H:%M:%S', localtime($seconds))
          , $microseconds
          , $this->{id}
          , $$
          , $codes{$level}
          , $caller
          , $line
          , $string
          );
      if ( $this->{trace} ) {
        $message = Carp::shortmess($message);
      } else {
        $message = $message."\n";
      }
      print($LOGFILE $message) if $level <= $this->{fileLevel};
      print(STDERR $message) if $level <= $this->{termLevel};
    }

    if ( $level <= $this->{databaseLevel} ) {
      if ( ! ( $ZoneMinder::Database::dbh and $ZoneMinder::Database::dbh->ping() ) ) {
        $this->{sth} = undef;
        # Turn this off because zDbConnect will do logging calls.
        my $oldlevel = $this->{databaseLevel};
        $this->{databaseLevel} = NOLOG;
        if ( ! ZoneMinder::Database::zmDbConnect() ) {
          #print(STDERR "Can't log to database: ");
          return;
        }
        $this->{databaseLevel} = $oldlevel;
      }

      my $sql = 'INSERT INTO Logs ( TimeKey, Component, ServerId, Pid, Level, Code, Message, File, Line ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, NULL )';
      $this->{sth} = $ZoneMinder::Database::dbh->prepare_cached($sql) if ! $this->{sth};
      if ( !$this->{sth} ) {
        $this->{databaseLevel} = NOLOG;
        Error("Can't prepare log entry '$sql': ".$ZoneMinder::Database::dbh->errstr());
        return;
      } 

      my $res = $this->{sth}->execute(
        $seconds+($microseconds/1000000.0),
           $this->{id},
           ($ZoneMinder::Config::Config{ZM_SERVER_ID} ? $ZoneMinder::Config::Config{ZM_SERVER_ID} : undef),
           $$,
           $level,
           $codes{$level},
           $string,
           $this->{fileName},
          );
      if ( !$res ) {
        $this->{databaseLevel} = NOLOG;
        Error("Can't execute log entry '$sql': ".$ZoneMinder::Database::dbh->errstr());
      }
    } # end if doing db logging
  } # end if level < effectivelevel
}

sub logInit( ;@ ) {
  my %options = @_ ? @_ : ();
  $logger = ZoneMinder::Logger->new() if !$logger;
  $logger->initialise(%options);
}

sub logReinit {
  fetch()->reinitialise();
}

sub logTerm {
  return unless $logger;
  $logger->terminate();
  $logger = undef;
}

sub logHupHandler {
  $do_log_rotate = 1;
}

sub logSetSignal {
  $SIG{HUP} = \&logHupHandler;
}

sub logClearSignal {
  $SIG{HUP} = 'DEFAULT';
}

sub logLevel {
  return fetch()->level(@_);
}

sub logDebugging {
  return fetch()->debugOn();
}

sub logTermLevel {
  return fetch()->termLevel(@_);
}

sub logDatabaseLevel {
  return fetch()->databaseLevel(@_);
}

sub logFileLevel {
  return fetch()->fileLevel(@_);
}

sub logSyslogLevel {
  return fetch()->syslogLevel(@_);
}

sub Mark {
  my $level = shift;
  $level = DEBUG unless defined($level);
  my $tag = 'Mark';
  fetch()->logPrint($level, $tag);
}

sub Dump {
  my $var = shift;
  my $label = shift;
  $label = 'VAR' unless defined($label);
  fetch()->logPrint(DEBUG, Data::Dumper->Dump([ $var ], [ $label ]));
}

sub debug {
  my $log = shift;
  $log->logPrint(DEBUG, @_, caller);
}

sub Debug( @ ) {
  fetch()->logPrint(DEBUG, @_, caller);
}

sub Info( @ ) {
  fetch()->logPrint(INFO, @_, caller);
}
sub info {
  my $log = shift;
  $log->logPrint(INFO, @_, caller);
}

sub Warning( @ ) {
  fetch()->logPrint(WARNING, @_, caller);
}
sub warn {
  my $log = shift;
  $log->logPrint(WARNING, @_, caller);
}

sub Error( @ ) {
  fetch()->logPrint(ERROR, @_, caller);
}
sub error {
  my $log = shift;
  $log->logPrint(ERROR, @_, caller);
}

sub Fatal( @ ) {
  my $this = fetch();
  $this->logPrint(FATAL, @_, caller);
  if ( $SIG{TERM} and ( $SIG{TERM} ne 'DEFAULT' ) ) {
    $SIG{TERM}();
  }
  if ( $$this{sth} ) {
    $$this{sth}->finish();
    $$this{sth} = undef;
  }
  # I think if we don't disconnect we will leave sockets around in TIME_WAIT
  ZoneMinder::Database::zmDbDisconnect();
  exit(-1);
}

sub Panic( @ ) {
  fetch()->logPrint(PANIC, @_, caller);
  confess($_[0]);
}

1;
__END__

=head1 NAME

ZoneMinder::Logger - ZoneMinder Logger module

=head1 SYNOPSIS

use ZoneMinder::Logger;
use ZoneMinder::Logger qw(:all);

logInit( 'myproc', DEBUG );

Debug( 'This is what is happening' );
Info( 'Something interesting is happening' );
Warning( 'Something might be going wrong.' );
Error( 'Something has gone wrong!!' );
Fatal( 'Something has gone badly wrong, gotta stop!!' );
Panic( 'Something fundamental has gone wrong, die with stack trace' );

=head1 DESCRIPTION

The ZoneMinder:Logger module contains the common debug and error reporting
routines used by the ZoneMinder scripts.

To use debug in your scripts you need to include this module, and call
logInit. Thereafter you can sprinkle Debug or Error calls etc throughout
the code safe in the knowledge that they will be reported to your error
log, and possibly the syslogger, in a meaningful and consistent format.

Debug is discussed in terms of levels where 1 and above (currently only 1
for scripts) is considered debug, 0 is considered as informational, -1 is a
warning, -2 is an error and -3 is a fatal error or panic. Where levels are
mentioned below as thresholds the value given and anything with a lower
level (ie. more serious) will be included.

=head1 METHODS

=over 4

=item logInit ( $id, %options );

Initialises the debug and prepares the logging for forthcoming operations.
If not called explicitly it will be called by the first debug call in your
script, but with default (and probably meaningless) options. The only
compulsory arguments are $id which must be a string that will identify
debug coming from this script in mixed logs. Other options may be provided
as below,

Option        Default        Description
---------     ---------      -----------
level         INFO       The initial debug level which defines which statements are output and which are ignored
trace         0          Whether to use the Carp::shortmess format in debug statements to identify where the debug was emitted from
termLevel     NOLOG      At what level debug is written to terminal standard error, 0 is no, 1 is yes, 2 is write only if terminal
databaseLevel INFO       At what level debug is written to the Log table in the database;
fileLevel     NOLOG      At what level debug is written to a log file of the format of <id>.log in the standard log directory.
syslogLevel   INFO       At what level debug is written to syslog.

To disable any of these action entirely set to NOLOG

=item logTerm ();

Used to end the debug session and close any logs etc. Not usually necessary.

=item $id            = logId ( [$id] );

=item $level         = logLevel ( [$level] );

=item $trace         = logTrace ( [$trace] );

=item $level         = logLevel ( [$level] );

=item $termLevel     = logTermLevel ( [$termLevel] );

=item $databaseLevel = logDatabaseLevel ( [$databaseLevel] );

=item $fileLevel     = logFileLevel ( [$fileLevel] );

=item $syslogLevel   = logSyslogLevel ( [$syslogLevel] );

These methods can be used to get and set the current settings as defined in logInit.

=item Debug( $string );

This method will output a debug message if the current debug level permits
it, otherwise does nothing. This message will be tagged with the DBG string
in the logs.

=item Info( $string );

This method will output an informational message if the current debug level
permits it, otherwise does nothing. This message will be tagged with the
INF string in the logs.

=item Warning( $string );

This method will output a warning message if the current debug level
permits it, otherwise does nothing. This message will be tagged with the
WAR string in the logs.

=item Error( $string );

This method will output an error message if the current debug level permits
it, otherwise does nothing. This message will be tagged with the ERR string
in the logs.

=item Fatal( $string );

This method will output a fatal error message and then die if the current
debug level permits it, otherwise does nothing. This message will be tagged
with the FAT string in the logs.

=item Panic( $string );

This method will output a panic error message and then die with a stack
trace if the current debug level permits it, otherwise does nothing. This
message will be tagged with the PNC string in the logs.

=back

=head2 EXPORT

None by default.
The :constants tag will export the debug constants which define the various levels of debug
The :variables tag will export variables containing the current debug id and level
The :functions tag will export the debug functions. This or :all is what you would normally use.
The :all tag will export all above symbols.


=head1 SEE ALSO

Carp
Sys::Syslog

The ZoneMinder README file Troubleshooting section for an extended
discussion on the use and configuration of syslog with ZoneMinder.

http://www.zoneminder.com

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
