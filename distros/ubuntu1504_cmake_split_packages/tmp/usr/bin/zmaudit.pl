#!/usr/bin/perl -wT
#
# ==========================================================================
#
# ZoneMinder Audit Script, $Date$, $Revision$
# Copyright (C) 2001-2008 Philip Coombes
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

=head1 NAME

zmaudit.pl - ZoneMinder event file system and database consistency checker

=head1 SYNOPSIS

 zmaudit.pl [-r,-report|-i,-interactive]

=head1 DESCRIPTION

This script checks for consistency between the event filesystem and
the database. If events are found in one and not the other they are
deleted (optionally). Additionally any monitor event directories that
do not correspond to a database monitor are similarly disposed of.
However monitors in the database that don't have a directory are left
alone as this is valid if they are newly created and have no events
yet.

=head1 OPTIONS

 -r, --report               - Just report don't actually do anything
 -i, --interactive          - Ask before applying any changes
 -c, --continuous           - Run continuously
 -v, --version              - Print the installed version of ZoneMinder

=cut
use strict;
use bytes;

# ==========================================================================
#
# These are the elements you can edit to suit your installation
#
# ==========================================================================

use constant MAX_AGED_DIRS => 10; # Number of event dirs to check age on
use constant RECOVER_TAG => "(r)"; # Tag to append to event name when recovered
use constant RECOVER_TEXT => "Recovered."; # Text to append to event notes when recovered

# ==========================================================================
#
# You shouldn't need to change anything from here downwards
#
# ==========================================================================

# Include from system perl paths only
use ZoneMinder;
use DBI;
use POSIX;
use File::Find;
use Time::HiRes qw/gettimeofday/;
use Getopt::Long;
use autouse 'Pod::Usage'=>qw(pod2usage);

use constant IMAGE_PATH => $Config{ZM_PATH_WEB}.'/'.$Config{ZM_DIR_IMAGES};
use constant EVENT_PATH => ($Config{ZM_DIR_EVENTS}=~m|/|)
                           ? $Config{ZM_DIR_EVENTS}
                           : ($Config{ZM_PATH_WEB}.'/'.$Config{ZM_DIR_EVENTS})
;

$| = 1;

$ENV{PATH}  = '/bin:/usr/bin';
$ENV{SHELL} = '/bin/sh' if exists $ENV{SHELL};
delete @ENV{qw(IFS CDPATH ENV BASH_ENV)};

my $report = 0;
my $interactive = 0;
my $continuous = 0;
my $version;

logInit();
logSetSignal();

GetOptions(
    'report'        =>\$report,
    'interactive'   =>\$interactive,
    'continuous'    =>\$continuous,
    'version'       =>\$version
) or pod2usage(-exitstatus => -1);

if ( $version ) {
    print( ZoneMinder::Base::ZM_VERSION . "\n");
    exit(0);
}
if ( ($report + $interactive + $continuous) > 1 )
{
    print( STDERR "Error, only one option may be specified\n" );
    pod2usage(-exitstatus => -1);
}

my $dbh = zmDbConnect();

chdir( EVENT_PATH );

my $max_image_age = 6/24; # 6 hours
my $max_swap_age = 24/24; # 24 hours
my $image_path = IMAGE_PATH;

my $loop = 1;
my $cleaned = 0;
MAIN: while( $loop ) {
    while ( ! ( $dbh and $dbh->ping() ) ) {
        $dbh = zmDbConnect();

        last if $dbh;
        if ( $continuous ) {
            # if we are running continuously, then just skip to the next
            # interval, otherwise we are a one off run, so wait a second and
            # retry until someone kills us.
            sleep( $Config{ZM_AUDIT_CHECK_INTERVAL} );
        } else {
            sleep 1;
        } # end if
    } # end while can't connect to the db

	if ( ! exists $Config{ZM_AUDIT_MIN_AGE} ) {
        Fatal("ZM_AUDIT_MIN_AGE is not set in config.");
    }

<<<<<<< HEAD
	my %db_events;

    my $db_monitors;
=======
	# This hash stores the ages of events inddxed first my monitor_id, then by event_id
	my %db_event_ages;

    my %db_monitors;
>>>>>>> pointone
    my $monitorSelectSql = "select Id,ServerHost from Monitors order by Id";
    my $monitorSelectSth = $dbh->prepare_cached( $monitorSelectSql )
        or Fatal( "Can't prepare '$monitorSelectSql': ".$dbh->errstr() );
    my $eventSelectSql = "SELECT Id, (unix_timestamp() - unix_timestamp(StartTime)) as Age
                          FROM Events WHERE MonitorId = ? ORDER BY Id";
    my $eventSelectSth = $dbh->prepare_cached( $eventSelectSql )
        or Fatal( "Can't prepare '$eventSelectSql': ".$dbh->errstr() );

	# Some time can pass between the loading of event data from db and filesystem, 
    # so this is used as a last check before we delete an event from the filesystem
    my $eventExistsSql = 'SELECT Id FROM Events WHERE MonitorId = ? AND Id = ?';
    my $eventExistsSth = $dbh->prepare_cached( $eventExistsSql ) 
        or Fatal( "Can't prepare '$eventExistsSql': ".$dbh->errstr() );

    $cleaned = 0;
	my $res = $monitorSelectSth->execute( ) or Fatal( "Can't execute: ".$monitorSelectSth->errstr() );
    while( my $monitor = $monitorSelectSth->fetchrow_hashref() ) {
		$db_monitors{$$monitor{Id}} = $monitor;
		if ( $Config{ZM_SERVER_HOST} ) {
			if ( $db_monitors{$$monitor{Id}}->{ServerHost} ne $Config{ZM_SERVER_HOST} ) {
				Debug( "Skipping monitor $$monitor{Id} since it is handled by " . $db_monitors{$$monitor{Id}}->{ServerHost} . "\n" );
				next;
			} # end if same serverhost
		} # end if  there is a defined serverhost
        my $res = $eventSelectSth->execute( $$monitor{Id} ) or Fatal( "Can't execute: ".$eventSelectSth->errstr() );
		$db_event_ages{$$monitor{Id}} = { map { @{$_} } @{ $eventSelectSth->fetchall_arrayref() } };
        Debug( "Got ".int(keys(%{$db_event_ages{$monitor->{Id}}}))." database events for Monitor $$monitor{Id}\n" );
    } # end each monitor

    my %fs_monitors;
    foreach my $monitor_id ( glob("[0-9]*") ) {
        Debug( "Found filesystem monitor '$monitor_id'" );
        my $fs_event_ages = $fs_monitors{$monitor_id} = {};
		if ( $Config{ZM_SERVER_HOST} ) {
			if ( $db_monitors{$monitor_id}->{ServerHost} ne $Config{ZM_SERVER_HOST} ) {
				Debug( "Skipping monitor $monitor_id since it has handled by " . $db_monitors{$monitor_id}->{ServerHost} . "\n" );
				next;
			} # end if same serverhost
		} # end if  there is a defined serverhost
        ( my $monitor_dir ) = ( $monitor_id =~ /^(.*)$/ ); # De-taint

        if ( $Config{ZM_USE_DEEP_STORAGE} ) {
            foreach my $day_dir ( glob("$monitor_dir/*/*/*") ) {
                Debug( "Checking $day_dir" );
                ( $day_dir ) = ( $day_dir =~ /^(.*)$/ ); # De-taint
                chdir( $day_dir );
                opendir( DIR, "." )
                    or Fatal( "Can't open directory '$day_dir': $!" );
				my @dir_contents = readdir( DIR );
				if ( ! @dir_contents ) {
					Debug("Dir $day_dir is empty, deleting.");
					`rmdir $day_dir`;
					next;
				}
                my @event_links = sort { $b <=> $a } grep { -l $_ }@dir_contents;
                closedir( DIR );
                my $count = 0;
                foreach my $event_link ( @event_links ) {
                    Debug( "Checking link $event_link" );
                    ( my $event = $event_link ) =~ s/^.*\.//;
                    my $event_path = readlink( $event_link );
                    if ( $count++ > MAX_AGED_DIRS ) {
                        $fs_event_ages->{$event} = -1;
                    } else {
                        if ( !-e $event_path ) {
                            aud_print( "Event link $day_dir/$event_link does not point to valid target" );
                            if ( confirm() ) {
                                ( $event_link ) = ( $event_link =~ /^(.*)$/ ); # De-taint
                                unlink( $event_link );
                                $cleaned = 1;
                            }
                        } else {
                            $fs_event_ages->{$event} = (time() - ($^T - ((-M $event_path) * 24*60*60)));
                        }
                    }
                }
                chdir( EVENT_PATH );
            } # end foreach day_dir
        } else {
            chdir( $monitor_dir );
            opendir( DIR, "." ) or Fatal( "Can't open directory '$monitor_dir': $!" );
            my @temp_events = sort { $b <=> $a } grep { -d $_ && $_ =~ /^\d+$/ } readdir( DIR );
            closedir( DIR );
            my $count = 0;
            foreach my $event ( @temp_events ) {
                if ( $count++ > MAX_AGED_DIRS ) {
                    $fs_event_ages->{$event} = -1;
                } else {
                    $fs_event_ages->{$event} = (time() - ($^T - ((-M $event) * 24*60*60)));
                }
            }
            chdir( EVENT_PATH );
        } # end USE_DEEP_STORAGE
        Debug( "Got ".int(keys(%$fs_event_ages))." events from filesystem for Monitor $monitor_id\n" );
    } # end foreach monitor_id
    redo MAIN if ( $cleaned );

    $cleaned = 0;
<<<<<<< HEAD
    while ( my ( $fs_monitor, $fs_events ) = each(%$fs_monitors) ) {
        if ( my $db_events = $db_events{$fs_monitor} ) {
            if ( ! $fs_events ) {
				Debug("No fs events for monitor $fs_monitor");
				next;
			} # end if ! fs_events

			while ( my ( $fs_event, $age ) = each(%$fs_events ) ) {
                if ( !defined($db_events->{$fs_event}) && ($age < 0 || ($age > $Config{ZM_AUDIT_MIN_AGE})) )
					# Since all this processing could take a long time, we need to double check that it doesn't exist in the db.
					my $rv = $eventExistsSth->execute( $fs_monitor, $fs_event );
					if ( ! $eventExistsSth->fetchall_arrayref() ) {
						aud_print( "Filesystem event '$fs_monitor / $fs_event' does not exist in database" );
						if ( confirm() ) {
							deleteEventFiles( $fs_event, $fs_monitor );
							$cleaned = 1;
						}
					} # Event really does not exist in the db.
=======
    while ( my ( $monitor_id, $fs_event_ages ) = each(%fs_monitors) ) {
        if ( my $db_event_ages = $db_event_ages{$monitor_id} ) {
            if ( ! $fs_event_ages ) {
				Debug("No fs events for monitor $monitor_id");
				next;
			} # end if ! fs_event_ages

			while ( my ( $event_id, $age ) = each %$fs_event_ages ) {
				if ( !defined($$db_event_ages{$event_id}) ) {
					if ( $age < 0 || $age > $Config{ZM_AUDIT_MIN_AGE} ) {
# Since all this processing could take a long time, we need to double check that it doesn't exist in the db.
						my $rv = $eventExistsSth->execute( $monitor_id, $event_id );
						if ( ! $eventExistsSth->rows() ) {
							aud_print( "Filesystem event '$monitor_id / $event_id' does not exist in database" );
							if ( confirm() ) {
								deleteEventFiles( $event_id, $monitor_id );
								$cleaned = 1;
							}
						} else {
							aud_print( "Filesystem event '$monitor_id / $event_id' did not exist in database but does now.\n" );
						} # Event really does not exist in the db.
					} else {
						aud_print( "Filesystem event '$monitor_id / $event_id' does not exist in database but is too young $age < $Config{ZM_AUDIT_MIN_AGE}\n" );
					}
				} else {
					aud_print( "Filesystem event '$monitor_id / $event_id' exists in database\n" );
>>>>>>> pointone
				}
			} # end while each fs_event_ages
		} elsif ( ! $db_monitors{$monitor_id} ) {
			aud_print( "Filesystem monitor '$monitor_id' does not exist in database" );
			if ( confirm() ) {
				my $command = "rm -rf $monitor_id";
				executeShellCommand( $command );
				$cleaned = 1;
			}
<<<<<<< HEAD
        } elsif ( ! $db_monitors->{$fs_monitor} ) {
            aud_print( "Filesystem monitor '$fs_monitor' does not exist in database" );
            if ( confirm() ) {
                my $command = "rm -rf $fs_monitor";
                executeShellCommand( $command );
                $cleaned = 1;
            }
        }
    }
=======
		}
	} # end while each fs_monitors
>>>>>>> pointone

    my $monitor_links;
    foreach my $link ( glob("*") )
    {
        next if ( !-l $link );
        next if ( -e $link );

        aud_print( "Filesystem monitor link '$link' does not point to valid monitor directory" );
        if ( confirm() )
        {
            ( $link ) = ( $link =~ /^(.*)$/ ); # De-taint
            my $command = "rm $link";
            executeShellCommand( $command );
            $cleaned = 1;
        }
    }
    redo MAIN if ( $cleaned );

    $cleaned = 0;
    my $deleteMonitorSql = "delete low_priority from Monitors where Id = ?";
    my $deleteMonitorSth = $dbh->prepare_cached( $deleteMonitorSql )
        or Fatal( "Can't prepare '$deleteMonitorSql': ".$dbh->errstr() );
    my $deleteEventSql = "delete low_priority from Events where Id = ?";
    my $deleteEventSth = $dbh->prepare_cached( $deleteEventSql )
        or Fatal( "Can't prepare '$deleteEventSql': ".$dbh->errstr() );
    my $deleteFramesSql = "delete low_priority from Frames where EventId = ?";
    my $deleteFramesSth = $dbh->prepare_cached( $deleteFramesSql )
        or Fatal( "Can't prepare '$deleteFramesSql': ".$dbh->errstr() );
    my $deleteStatsSql = "delete low_priority from Stats where EventId = ?";
    my $deleteStatsSth = $dbh->prepare_cached( $deleteStatsSql )
        or Fatal( "Can't prepare '$deleteStatsSql': ".$dbh->errstr() );
<<<<<<< HEAD
    while ( my ( $db_monitor, $db_events ) = each(%$db_monitors) )
    {
        if ( my $fs_events = $fs_monitors->{$db_monitor} )
        {
            if ( $db_events )
            {
                while ( my ( $db_event, $age ) = each(%$db_events ) )
                {
                    if ( !defined($fs_events->{$db_event}) ) {
=======

    while ( my ( $monitor_id, $monitor ) = each(%db_monitors) ) {
		my $db_event_ages = $db_event_ages{$monitor_id};
        if ( my $fs_event_ages = $fs_monitors{$monitor_id} ) {
            if ( $db_event_ages and %{$db_event_ages} ) {
                while ( my ( $event_id, $age ) = each(%$db_event_ages ) ) {
                    if ( !defined($fs_event_ages->{$event_id}) ) {
>>>>>>> pointone
						if ( $age > $Config{ZM_AUDIT_MIN_AGE} ) {
							aud_print( "Database event '$monitor_id/$event_id' does not exist in filesystem" );
							if ( confirm() ) {
								my $res = $deleteEventSth->execute( $event_id )
									or Fatal( "Can't execute: ".$deleteEventSth->errstr() );
								$res = $deleteFramesSth->execute( $event_id )
									or Fatal( "Can't execute: ".$deleteFramesSth->errstr() );
								$res = $deleteStatsSth->execute( $event_id )
									or Fatal( "Can't execute: ".$deleteStatsSth->errstr() );
								$cleaned = 1;
							}
						} else {
							aud_print( "Database event '$monitor_id/$event_id' does not exist in filesystem but too young to delete.\n" );
						}
                    }
                }
            }
        } else {
            aud_print( "Database monitor '$monitor_id' does not exist in filesystem" );
            #if ( confirm() )
            #{
                # We don't actually do this in case it's new
                #my $res = $deleteMonitorSth->execute( $db_monitor )
                #   or Fatal( "Can't execute: ".$deleteMonitorSth->errstr() );
                #$cleaned = 1;
            #}
        }
    }
    redo MAIN if ( $cleaned );

    # Remove orphaned events (with no monitor)
    $cleaned = 0;
    my $selectOrphanedEventsSql = "SELECT Events.Id, Events.Name
                                   FROM Events LEFT JOIN Monitors ON (Events.MonitorId = Monitors.Id)
                                   WHERE isnull(Monitors.Id)";
    my $selectOrphanedEventsSth = $dbh->prepare_cached( $selectOrphanedEventsSql )
        or Fatal( "Can't prepare '$selectOrphanedEventsSql': ".$dbh->errstr() );
    $res = $selectOrphanedEventsSth->execute()
        or Fatal( "Can't execute: ".$selectOrphanedEventsSth->errstr() );
    while( my $event = $selectOrphanedEventsSth->fetchrow_hashref() )
    {
        aud_print( "Found orphaned event with no monitor '$event->{Id}'" );
        if ( confirm() )
        {
            $res = $deleteEventSth->execute( $event->{Id} )
                or Fatal( "Can't execute: ".$deleteEventSth->errstr() );
            $cleaned = 1;
        }
    }
    redo MAIN if ( $cleaned );

    # Remove empty events (with no frames)
    $cleaned = 0;
    my $selectEmptyEventsSql = "SELECT * FROM Events as E LEFT JOIN Frames as F ON (E.Id = F.EventId)
                                WHERE isnull(F.EventId) AND now() - interval ".$Config{ZM_AUDIT_MIN_AGE}." second > E.StartTime";
    my $selectEmptyEventsSth = $dbh->prepare_cached( $selectEmptyEventsSql )
        or Fatal( "Can't prepare '$selectEmptyEventsSql': ".$dbh->errstr() );
    $res = $selectEmptyEventsSth->execute()
        or Fatal( "Can't execute: ".$selectEmptyEventsSth->errstr() );
    while( my $event = $selectEmptyEventsSth->fetchrow_hashref() ) {
        aud_print( "Found empty event with no frame records '$event->{Id}'" );
        if ( confirm() ) {
            $res = $deleteEventSth->execute( $event->{Id} )
                or Fatal( "Can't execute: ".$deleteEventSth->errstr() );
            $cleaned = 1;
        }
    }
    redo MAIN if ( $cleaned );

    # Remove orphaned frame records
    $cleaned = 0;
    my $selectOrphanedFramesSql = "SELECT DISTINCT EventId FROM Frames
                                   WHERE EventId NOT IN (SELECT Id FROM Events)";
    my $selectOrphanedFramesSth = $dbh->prepare_cached( $selectOrphanedFramesSql )
        or Fatal( "Can't prepare '$selectOrphanedFramesSql': ".$dbh->errstr() );
    $res = $selectOrphanedFramesSth->execute()
        or Fatal( "Can't execute: ".$selectOrphanedFramesSth->errstr() );
    while( my $frame = $selectOrphanedFramesSth->fetchrow_hashref() )
    {
        aud_print( "Found orphaned frame records for event '$frame->{EventId}'" );
        if ( confirm() )
        {
            $res = $deleteFramesSth->execute( $frame->{EventId} )
                or Fatal( "Can't execute: ".$deleteFramesSth->errstr() );
            $cleaned = 1;
        }
    }
    redo MAIN if ( $cleaned );

    # Remove orphaned stats records
    $cleaned = 0;
    my $selectOrphanedStatsSql = "SELECT DISTINCT EventId FROM Stats
                                  WHERE EventId NOT IN (SELECT Id FROM Events)";
    my $selectOrphanedStatsSth = $dbh->prepare_cached( $selectOrphanedStatsSql )
        or Fatal( "Can't prepare '$selectOrphanedStatsSql': ".$dbh->errstr() );
    $res = $selectOrphanedStatsSth->execute()
        or Fatal( "Can't execute: ".$selectOrphanedStatsSth->errstr() );
    while( my $stat = $selectOrphanedStatsSth->fetchrow_hashref() )
    {
        aud_print( "Found orphaned statistic records for event '$stat->{EventId}'" );
        if ( confirm() )
        {
            $res = $deleteStatsSth->execute( $stat->{EventId} )
                or Fatal( "Can't execute: ".$deleteStatsSth->errstr() );
            $cleaned = 1;
        }
    }
    redo MAIN if ( $cleaned );

    # New audit to close any events that were left open for longer than MIN_AGE seconds
    my $selectUnclosedEventsSql =
       "SELECT E.Id,
               max(F.TimeStamp) as EndTime,
               unix_timestamp(max(F.TimeStamp)) - unix_timestamp(E.StartTime) as Length,
               max(F.FrameId) as Frames,
               count(if(F.Score>0,1,NULL)) as AlarmFrames,
               sum(F.Score) as TotScore,
               max(F.Score) as MaxScore,
               M.EventPrefix as Prefix
        FROM Events as E
        LEFT JOIN Monitors as M on E.MonitorId = M.Id
        INNER JOIN Frames as F on E.Id = F.EventId
        WHERE isnull(E.Frames) or isnull(E.EndTime)
        GROUP BY E.Id HAVING EndTime < (now() - interval ".$Config{ZM_AUDIT_MIN_AGE}." second)"
    ;
    my $selectUnclosedEventsSth = $dbh->prepare_cached( $selectUnclosedEventsSql )
        or Fatal( "Can't prepare '$selectUnclosedEventsSql': ".$dbh->errstr() );
    my $updateUnclosedEventsSql =
       "UPDATE low_priority Events
        SET Name = ?,
            EndTime = ?,
            Length = ?,
            Frames = ?,
            AlarmFrames = ?,
            TotScore = ?,
            AvgScore = ?,
            MaxScore = ?,
            Notes = concat_ws( ' ', Notes, ? )
        WHERE Id = ?"
    ;
    my $updateUnclosedEventsSth = $dbh->prepare_cached( $updateUnclosedEventsSql )
        or Fatal( "Can't prepare '$updateUnclosedEventsSql': ".$dbh->errstr() );
    $res = $selectUnclosedEventsSth->execute()
        or Fatal( "Can't execute: ".$selectUnclosedEventsSth->errstr() );
    while( my $event = $selectUnclosedEventsSth->fetchrow_hashref() )
    {
        aud_print( "Found open event '$event->{Id}'" );
        if ( confirm( 'close', 'closing' ) )
        {
            $res = $updateUnclosedEventsSth->execute
            (
                sprintf("%s%d%s",
                        $event->{Prefix},
                        $event->{Id},
                        RECOVER_TAG
                ),
                $event->{EndTime},
                $event->{Length},
                $event->{Frames},
                $event->{AlarmFrames},
                $event->{TotScore},
                $event->{AlarmFrames}
                    ? int($event->{TotScore} / $event->{AlarmFrames})
                    : 0
                ,
                $event->{MaxScore},
                RECOVER_TEXT,
                $event->{Id}
            ) or Fatal( "Can't execute: ".$updateUnclosedEventsSth->errstr() );
        }
    }

    # Now delete any old image files
    if ( my @old_files = grep { -M > $max_image_age } <$image_path/*.{jpg,gif,wbmp}> )
    {
        aud_print( "Deleting ".int(@old_files)." old images\n" );
        my $untainted_old_files = join( ";", @old_files );
        ( $untainted_old_files ) = ( $untainted_old_files =~ /^(.*)$/ );
        unlink( split( /;/, $untainted_old_files ) );
    }

    # Now delete any old swap files
    ( my $swap_image_root ) = ( $Config{ZM_PATH_SWAP} =~ /^(.*)$/ ); # De-taint
    File::Find::find( { wanted=>\&deleteSwapImage, untaint=>1 }, $swap_image_root );

    # Prune the Logs table if required
    if ( $Config{ZM_LOG_DATABASE_LIMIT} )
    {
        if ( $Config{ZM_LOG_DATABASE_LIMIT} =~ /^\d+$/ )
        {
            # Number of rows
            my $selectLogRowCountSql = "SELECT count(*) as Rows from Logs";
            my $selectLogRowCountSth = $dbh->prepare_cached( $selectLogRowCountSql )
                or Fatal( "Can't prepare '$selectLogRowCountSql': ".$dbh->errstr() );
            $res = $selectLogRowCountSth->execute()
                or Fatal( "Can't execute: ".$selectLogRowCountSth->errstr() );
            my $row = $selectLogRowCountSth->fetchrow_hashref();
            my $logRows = $row->{Rows};
            if ( $logRows > $Config{ZM_LOG_DATABASE_LIMIT} )
            {
                my $deleteLogByRowsSql = "DELETE low_priority FROM Logs ORDER BY TimeKey ASC LIMIT ?";
                my $deleteLogByRowsSth = $dbh->prepare_cached( $deleteLogByRowsSql )
                    or Fatal( "Can't prepare '$deleteLogByRowsSql': ".$dbh->errstr() );
                $res = $deleteLogByRowsSth->execute( $logRows - $Config{ZM_LOG_DATABASE_LIMIT} )
                    or Fatal( "Can't execute: ".$deleteLogByRowsSth->errstr() );
                if ( $deleteLogByRowsSth->rows() )
                {
                    aud_print( "Deleted ".$deleteLogByRowsSth->rows()
                              ." log table entries by count\n" )
                    ;
                }
            }
        }
        else
        {
            # Time of record
            my $deleteLogByTimeSql =
               "DELETE low_priority FROM Logs
                WHERE TimeKey < unix_timestamp(now() - interval ".$Config{ZM_LOG_DATABASE_LIMIT}.")";
            my $deleteLogByTimeSth = $dbh->prepare_cached( $deleteLogByTimeSql )
                or Fatal( "Can't prepare '$deleteLogByTimeSql': ".$dbh->errstr() );
            $res = $deleteLogByTimeSth->execute()
                or Fatal( "Can't execute: ".$deleteLogByTimeSth->errstr() );
            if ( $deleteLogByTimeSth->rows() ){
                aud_print( "Deleted ".$deleteLogByTimeSth->rows()
                          ." log table entries by time\n" )
                ;
            }
        }
    }
    $loop = $continuous;

    sleep( $Config{ZM_AUDIT_CHECK_INTERVAL} ) if $continuous;
};

exit( 0 );

sub aud_print
{
    my $string = shift;
    if ( !$continuous )
    {
        print( $string );
    }
    else
    {
        Info( $string );
    }
}

sub confirm
{
    my $prompt = shift || "delete";
    my $action = shift || "deleting";

    my $yesno = 0;
    if ( $report )
    {
        print( "\n" );
    }
    elsif ( $interactive )
    {
        print( ", $prompt y/n: " );
        my $char = <>;
        chomp( $char );
        if ( $char eq 'q' )
        {
            exit( 0 );
        }
        if ( !$char )
        {
            $char = 'y';
        }
        $yesno = ( $char =~ /[yY]/ );
    }
    else
    {
        if ( !$continuous )
        {
            print( ", $action\n" );
        }
        else
        {
            Info( $action );
        }
        $yesno = 1;
    }
    return( $yesno );
}

sub deleteSwapImage
{
    my $file = $_;

    if ( $file !~ /^zmswap-/ )
    {
        return;
    }

    # Ignore directories
    if ( -d $file )
    {
        return;
    }

    if ( -M $file > $max_swap_age )
    {
        Debug( "Deleting $file" );
        #unlink( $file );
    }
}
