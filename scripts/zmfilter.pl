#!/usr/bin/perl -wT
#
# ==========================================================================
#
# ZoneMinder Event Filter Script, $Date$, $Revision$
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
# This script continuously monitors the recorded events for the given
# monitor and applies any filters which would delete and/or upload 
# matching events
#
use strict;
use bytes;

# ==========================================================================
#
# These are the elements you can edit to suit your installation
#
# ==========================================================================

use constant DBG_ID => "zmfilter"; # Tag that appears in debug to identify source
use constant DBG_LEVEL => 0; # 0 is errors, warnings and info only, > 0 for debug

use constant START_DELAY => 5; # How long to wait before starting

# ==========================================================================
#
# You shouldn't need to change anything from here downwards
#
# ==========================================================================

use ZoneMinder;
use DBI;
use POSIX;
use Time::HiRes qw/gettimeofday/;
use Date::Manip;
use Data::Dumper;
use Getopt::Long;

use constant EVENT_PATH => ZM_PATH_WEB.'/'.ZM_DIR_EVENTS;

zmDbgInit( DBG_ID, level=>DBG_LEVEL );

if ( ZM_OPT_UPLOAD )
{
	# Comment these out if you don't have them and don't want to upload
	# or don't want to use that format
	if ( ZM_UPLOAD_ARCH_FORMAT eq "zip" )
	{
		require Archive::Zip;
		import Archive::Zip qw( :ERROR_CODES :CONSTANTS );
	}
	else
	{
		require Archive::Tar;
	}
	require Net::FTP;
}

my $email_subject;
my $email_body;
if ( ZM_OPT_EMAIL )
{
	if ( ZM_NEW_MAIL_MODULES )
	{
		require MIME::Lite;
		require Net::SMTP;
	}
	else
	{
		require MIME::Entity;
	}
	( $email_subject, $email_body ) = ZM_EMAIL_TEXT =~ /subject\s*=\s*"([^\n]*)".*body\s*=\s*"(.*)"/ms;
}

my $message_subject;
my $message_body;
if ( ZM_OPT_MESSAGE )
{
	if ( ZM_NEW_MAIL_MODULES )
	{
		require MIME::Lite;
		require Net::SMTP;
	}
	else
	{
		require MIME::Entity;
	}

	( $message_subject, $message_body ) = ZM_MESSAGE_TEXT =~ /subject\s*=\s*"([^\n]*)".*body\s*=\s*"(.*)"/ms;
}


$| = 1;

$ENV{PATH}  = '/bin:/usr/bin';
$ENV{SHELL} = '/bin/sh' if exists $ENV{SHELL};
delete @ENV{qw(IFS CDPATH ENV BASH_ENV)};

my $delay = ZM_FILTER_EXECUTE_INTERVAL;
my $event_id = 0;

sub Usage
{
	print( "
Usage: zmfilter.pl [-d <seconds>,--delay=<seconds>]
Parameters are :-
-d<seconds>, --delay=<seconds>          - How long to delay between each check, default ".ZM_FILTER_EXECUTE_INTERVAL."
");
	exit( -1 );
}

#
# More or less replicates the equivalent PHP function
#
sub strtotime
{
	my $dt_str = shift;
	return( UnixDate( $dt_str, '%s' ) );
}

#
# More or less replicates the equivalent PHP function
#
sub str_repeat
{
	my $string = shift;
	my $count = shift;
	return( ${string}x${count} );
}

# Formats a date into MySQL format
sub DateTimeToSQL
{
	my $dt_str = shift;
	my $dt_val = strtotime( $dt_str );
	if ( !$dt_val )
	{
		Error( "Unable to parse date string '$dt_str'\n" );
		return( undef );
	}
	return( strftime( "%Y-%m-%d %H:%M:%S", localtime( $dt_val ) ) );
}

if ( !GetOptions( 'delay=i'=>\$delay ) )
{
	Usage();
}

chdir( EVENT_PATH );

my $dbh = DBI->connect( "DBI:mysql:database=".ZM_DB_NAME.";host=".ZM_DB_HOST, ZM_DB_USER, ZM_DB_PASS );

Info( "Scanning for events\n" );

sleep( START_DELAY );

my $filters = getFilters();
my $last_action = 0;

while( 1 )
{
	if ( (time() - $last_action) > ZM_FILTER_RELOAD_DELAY )
	{
		Debug( "Reloading filters\n" );
		$last_action = time();
		$filters = getFilters();
	}

	foreach my $filter ( @$filters )
	{
		checkFilter( $filter );
	}

	Debug( "Sleeping for $delay seconds\n" );
	sleep( $delay );
}

sub getDiskPercent
{
	my $command = "df .";
	my $df = qx( $command );
	my $space = -1;
	if ( $df =~ /\s(\d+)%/ms )
	{
		$space = $1;
	}
	return( $space );
}

sub getDiskBlocks
{
	my $command = "df .";
	my $df = qx( $command );
	my $space = -1;
	if ( $df =~ /\s(\d+)\s+\d+\s+\d+%/ms )
	{
		$space = $1;
	}
	return( $space );
}

sub getFilters
{
	my @filters;
	my $sql = "select * from Filters where (AutoArchive = 1 or AutoVideo = 1 or AutoUpload = 1 or AutoEmail = 1 or AutoMessage = 1 or AutoExecute = 1 or AutoDelete = 1) order by Name";
	my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
	my $res = $sth->execute() or die( "Can't execute '$sql': ".$sth->errstr() );
	FILTER: while( my $filter_data = $sth->fetchrow_hashref() )
	{
		Debug( "Found filter '$filter_data->{Name}'\n" );
		my %filter_terms;
		foreach my $filter_parm ( split( '&', $filter_data->{Query} ) )
		{
			my( $key, $value ) = split( '=', $filter_parm, 2 );
			if ( $key )
			{
				$filter_terms{$key} = $value;
			}
		}
		#Debug( Dumper( %filter_terms ) );
		my $sql = "select E.Id,E.MonitorId,M.Name as MonitorName,M.DefaultRate,M.DefaultScale,E.Name,E.StartTime,unix_timestamp(E.StartTime) as Time,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived,E.Videoed,E.Uploaded,E.Emailed,E.Messaged,E.Executed from Events as E inner join Monitors as M on M.Id = E.MonitorId where not isnull(E.EndTime)";
		my $filter_sql = '';
		for ( my $i = 1; $i <= $filter_terms{trms}; $i++ )
		{
			my $conjunction_name = "cnj$i";
			my $obracket_name = "obr$i";
			my $cbracket_name = "cbr$i";
			my $attr_name = "attr$i";
			my $op_name = "op$i";
			my $value_name = "val$i";
			if ( $filter_terms{$conjunction_name} )
			{
				$filter_sql .= " ".$filter_terms{$conjunction_name}." ";
			}
			if ( $filter_terms{$obracket_name} )
			{
				$filter_sql .= str_repeat( "(", $filter_terms{$obracket_name} );
			}
			my $value = $filter_terms{$value_name};
			my @value_list;
			if ( $filter_terms{$attr_name} )
			{
				if ( $filter_terms{$attr_name} =~ '/^Monitor/' )
				{
					my ( $temp_attr_name ) = $filter_terms{$attr_name} =~ /^Monitor(.+)$/;
					$filter_sql .= "M.".$temp_attr_name;
				}
				elsif ( $filter_terms{$attr_name} eq 'DateTime' )
				{
					$filter_sql .= "E.StartTime";
				}
				elsif ( $filter_terms{$attr_name} eq 'Date' )
				{
					$filter_sql .= "to_days( E.StartTime )";
				}
				elsif ( $filter_terms{$attr_name} eq 'Time' )
				{
					$filter_sql .= "extract( hour_second from E.StartTime )";
				}
				elsif ( $filter_terms{$attr_name} eq 'Weekday' )
				{
					$filter_sql .= "weekday( E.StartTime )";
				}
				elsif ( $filter_terms{$attr_name} eq 'DiskPercent' )
				{
					$filter_sql .= "zmDiskPercent";
					$filter_data->{HasDiskPercent} = !undef;
				}
				elsif ( $filter_terms{$attr_name} eq 'DiskBlocks' )
				{
					$filter_sql .= "zmDiskBlocks";
					$filter_data->{HasDiskBlocks} = !undef;
				}
				else
				{
					$filter_sql .= "E.".$filter_terms{$attr_name};
				}

				( my $stripped_value = $value ) =~ s/^["\']+?(.+)["\']+?$/$1/;
				foreach my $temp_value ( split( '/["\'\s]*?,["\'\s]*?/', $stripped_value ) )
				{
					if ( $filter_terms{$attr_name} =~ '/^Monitor/' )
					{
						$value = "'$temp_value'";
					}
					elsif ( $filter_terms{$attr_name} eq 'DateTime' )
					{
						$value = DateTimeToSQL( $temp_value );
						if ( !$value )
						{
							Error( "Error parsing date/time '$temp_value', skipping filter '$filter_data->{Name}'\n" );
							next FILTER;
						}
						$value = "'$value'";
					}
					elsif ( $filter_terms{$attr_name} eq 'Date' )
					{
						$value = DateTimeToSQL( $temp_value );
						if ( !$value )
						{
							Error( "Error parsing date/time '$temp_value', skipping filter '$filter_data->{Name}'\n" );
							next FILTER;
						}
						$value = "to_days( '$value' )";
					}
					elsif ( $filter_terms{$attr_name} eq 'Time' )
					{
						$value = DateTimeToSQL( $temp_value );
						if ( !$value )
						{
							Error( "Error parsing date/time '$temp_value', skipping filter '$filter_data->{Name}'\n" );
							next FILTER;
						}
						$value = "extract( hour_second from '$value' )";
					}
					elsif ( $filter_terms{$attr_name} eq 'Weekday' )
					{
						$value = DateTimeToSQL( $temp_value );
						if ( !$value )
						{
							Error( "Error parsing date/time '$temp_value', skipping filter '$filter_data->{Name}'\n" );
							next FILTER;
						}
						$value = "weekday( '$value' )";
					}
					else
					{
						$value = $temp_value;
					}
					push( @value_list, $value );
				}
			}
			if ( $filter_terms{$op_name} )
			{
				if ( $filter_terms{$op_name} eq '=~' )
				{
					$filter_sql .= " regexp $value";
				}
				elsif ( $filter_terms{$op_name} eq '!~' )
				{
					$filter_sql .= " not regexp $value";
				}
				elsif ( $filter_terms{$op_name} eq '=[]' )
				{
					$filter_sql .= " in (".join( ",", @value_list ).")";
				}
				elsif ( $filter_terms{$op_name} eq '!~' )
				{
					$filter_sql .= " not in (".join( ",", @value_list ).")";
				}
				else
				{
					$filter_sql .= " ".$filter_terms{$op_name}." $value";
				}
			}
			if ( $filter_terms{$cbracket_name} )
			{
				$filter_sql .= str_repeat( ")", $filter_terms{$cbracket_name} );
			}
		}
		if ( $filter_sql )
		{
			$sql .= " and ( $filter_sql )";
		}
		my @auto_terms;
		if ( $filter_data->{AutoArchive} )
		{
			push( @auto_terms, "E.Archived = 0" )
		}
		if ( $filter_data->{AutoVideo} )
		{
			push( @auto_terms, "E.Videoed = 0" )
		}
		if ( $filter_data->{AutoUpload} )
		{
			push( @auto_terms, "E.Uploaded = 0" )
		}
		if ( $filter_data->{AutoEmail} )
		{
			push( @auto_terms, "E.Emailed = 0" )
		}
		if ( $filter_data->{AutoMessage} )
		{
			push( @auto_terms, "E.Messaged = 0" )
		}
		if ( $filter_data->{AutoExecute} )
		{
			push( @auto_terms, "E.Executed = 0" )
		}
		if ( @auto_terms )
		{
			$sql .= " and ( ".join( " or ", @auto_terms )." )";
		}
		if ( !$filter_terms{sort_field} )
		{
			$filter_terms{sort_field} = 'StartTime';
			$filter_terms{sort_asc} = 0;
		}
		my $sort_column = '';
		if ( $filter_terms{sort_field} eq 'Id' )
		{
            $sort_column = "E.Id"; 
		}
		elsif ( $filter_terms{sort_field} eq 'MonitorName' )
		{
            $sort_column = "M.Name";
		}
		elsif ( $filter_terms{sort_field} eq 'Name' )
		{
            $sort_column = "E.Name";
		}
		elsif ( $filter_terms{sort_field} eq 'StartTime' )
		{
            $sort_column = "E.StartTime";
		}
		elsif ( $filter_terms{sort_field} eq 'Secs' )
		{
            $sort_column = "E.Length";
		}
		elsif ( $filter_terms{sort_field} eq 'Frames' )
		{
            $sort_column = "E.Frames";
		}
		elsif ( $filter_terms{sort_field} eq 'AlarmFrames' )
		{
            $sort_column = "E.AlarmFrames";
		}
		elsif ( $filter_terms{sort_field} eq 'TotScore' )
		{
            $sort_column = "E.TotScore";
		}
		elsif ( $filter_terms{sort_field} eq 'AvgScore' )
		{
            $sort_column = "E.AvgScore";
		}
		elsif ( $filter_terms{sort_field} eq 'MaxScore' )
		{
            $sort_column = "E.MaxScore";
		}
		else
		{
            $sort_column = "E.StartTime";
    	}
		my $sort_order = $filter_terms{sort_asc}?"asc":"desc";
		$sql .= " order by ".$sort_column." ".$sort_order;
		if ( $filter_terms{limit} )
		{
			$sql .= " limit 0, ".$filter_terms{limit};
		}
		Debug( "SQL:$sql\n" );
		$filter_data->{Sql} = $sql;
		if ( $filter_data->{AutoExecute} )
		{
			my $script = $filter_data->{AutoExecuteCmd};
			$script =~ s/\s.*$//;
			if ( !-e $script )
			{
				Error( "Auto execute script '$script' not found, skipping filter '$filter_data->{Name}'\n" );
				next FILTER;

			}
			elsif ( !-x $script )
			{
				Error( "Auto execute script '$script' not executable, skipping filter '$filter_data->{Name}'\n" );
				next FILTER;
			}
		}
		push( @filters, $filter_data );
	}
	$sth->finish();

	return( \@filters );
}

sub checkFilter
{
	my $filter = shift;

	Debug( "Checking filter '$filter->{Name}'".
		($filter->{AutoDelete}?", delete":"").
		($filter->{AutoArchive}?", archive":"").
		($filter->{AutoVideo}?", video":"").
		($filter->{AutoUpload}?", upload":"").
		($filter->{AutoEmail}?", email":"").
		($filter->{AutoMessage}?", message":"").
		($filter->{AutoExecute}?", execute":"").
		"\n"
	);
	my $sql = $filter->{Sql};
	
	if ( $filter->{HasDiskPercent} )
	{
		my $disk_percent = getDiskPercent();
		$sql =~ s/zmDiskPercent/$disk_percent/g;
	}
	if ( $filter->{HasDiskBlocks} )
	{
		my $disk_blocks = getDiskBlocks();
		$sql =~ s/zmDiskBlocks/$disk_blocks/g;
	}

	my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
	my $res = $sth->execute() or die( "Can't execute '$sql': ".$sth->errstr() );

	while( my $event = $sth->fetchrow_hashref() )
	{
		Debug( "Checking event $event->{Id}\n" );
		my $delete_ok = !undef;
		if ( $filter->{AutoArchive} )
		{
			Info( "Archiving event $event->{Id}\n" );
			# Do it individually to avoid locking up the table for new events
			my $sql = "update Events set Archived = 1 where Id = ?";
			my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
			my $res = $sth->execute( $event->{Id} ) or die( "Can't execute '$sql': ".$sth->errstr() );
		}
		if ( ZM_OPT_MPEG ne "no" && $filter->{AutoVideo} )
		{
			if ( !$event->{Videoed} )
			{
				$delete_ok = undef if ( !generateVideo( $filter, $event ) );
			}
		}
		if ( ZM_OPT_EMAIL && $filter->{AutoEmail} )
		{
			if ( !$event->{Emailed} )
			{
				$delete_ok = undef if ( !sendEmail( $filter, $event ) );
			}
		}
		if ( ZM_OPT_MESSAGE && $filter->{AutoMessage} )
		{
			if ( !$event->{Messaged} )
			{
				$delete_ok = undef if ( !sendMessage( $filter, $event ) );
			}
		}
		if ( ZM_OPT_UPLOAD && $filter->{AutoUpload} )
		{
			if ( !$event->{Uploaded} )
			{
				$delete_ok = undef if ( !uploadArchFile( $filter, $event ) );
			}
		}
		if ( $filter->{AutoExecute} )
		{
			if ( !$event->{Execute} )
			{
				$delete_ok = undef if ( !executeCommand( $filter, $event ) );
			}
		}
		if ( $filter->{AutoDelete} )
		{
			if ( $delete_ok )
			{
				Info( "Deleting event $event->{Id}\n" );
				# Do it individually to avoid locking up the table for new events
				my $sql = "delete from Events where Id = ?";
				my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
				my $res = $sth->execute( $event->{Id} ) or die( "Can't execute '$sql': ".$sth->errstr() );

				if ( !ZM_OPT_FAST_DELETE )
				{
					my $sql = "delete from Frames where EventId = ?";
					my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
					my $res = $sth->execute( $event->{Id} ) or die( "Can't execute '$sql': ".$sth->errstr() );

					$sql = "delete from Stats where EventId = ?";
					$sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
					$res = $sth->execute( $event->{Id} ) or die( "Can't execute '$sql': ".$sth->errstr() );

					my $command = "rm -rf ".ZM_DIR_EVENTS."/*/".sprintf( "%d", $event->{Id} );
					my $output = qx($command);
					my $status = $? >> 8;
					if ( $status || DBG_LEVEL > 0 )
					{
						chomp( $output );
						Debug( "Output: $output\n" );
					}
				}
			}
			else
			{
				Error( "Unable to delete event $event->{Id} as previous operations failed\n" );
			}
		}
	}
	$sth->finish();
}

sub generateVideo
{
	my $filter = shift;
	my $event = shift;
	my $phone = shift;

	my $rate = $event->{DefaultRate}/100;
	my $scale = $event->{DefaultScale}/100;
	my $format;

	my @ffmpeg_formats = split( /\s+/, ZM_FFMPEG_FORMATS );
	my $default_video_format;
	my $default_phone_format;
	foreach my $ffmpeg_format( @ffmpeg_formats )
	{
		if ( $ffmpeg_format =~ /^(.+)\*\*$/ )
		{
			$default_phone_format = $1;
		}
		elsif ( $ffmpeg_format =~ /^(.+)\*$/ )
		{
			$default_video_format = $1;
		}
	}

	if ( $phone && $default_phone_format )
	{
		$format = $default_phone_format;
	}
	elsif ( $default_video_format )
	{
		$format = $default_video_format;
	}
	else
	{
		$format = $ffmpeg_formats[0];
	}

	my $command = ZM_PATH_BIN."/zmvideo.pl -e ".$event->{Id}." -r ".$rate." -s ".$scale." -f ".$format;
	my $output = qx($command);
	my $status = $? >> 8;
	if ( $status || DBG_LEVEL > 0 )
	{
		chomp( $output );
		Debug( "Output: $output\n" );
	}
	if ( $status )
	{
		Error( "Video generation '$command' failed with status: $status\n" );
		if ( wantarray() )
		{
			return( undef, undef );
		}
		return( 0 );
	}
	if ( wantarray() )
	{
		return( $format, sprintf( "%d/%d/%s", $event->{MonitorId}, $event->{Id}, $output ) );
	}
	return( 1 );
}

sub uploadArchFile
{
	my $filter = shift;
	my $event = shift;

	my $arch_file = ZM_UPLOAD_FTP_LOC_DIR.'/'.$event->{MonitorName}.'-'.$event->{Id};
	my $arch_image_path = "$event->{MonitorId}/$event->{Id}/".((ZM_UPLOAD_ARCH_ANALYSE)?'{*analyse,*capture}':'*capture').".jpg";
	my $arch_error;

	if ( ZM_UPLOAD_ARCH_FORMAT eq "zip" )
	{
		$arch_file .= '.zip';
		my $zip = Archive::Zip->new();
		Info( "Creating upload file '$arch_file'\n" );

		my $status = &AZ_OK;
		foreach my $image_file ( <*$arch_image_path> )
		{
			Info( "Adding $image_file\n" );
			my $member = $zip->addFile( $image_file );
			last unless ( $member );
			$member->desiredCompressionMethod( (ZM_UPLOAD_ARCH_COMPRESS)?&COMPRESSION_DEFLATED:&COMPRESSION_STORED );
		}
		$status = $zip->writeToFileNamed( $arch_file );

		if ( $arch_error = ($status != &AZ_OK) )
		{
			Error( "Zip error: $status\n " );
		}
	}
	elsif ( ZM_UPLOAD_ARCH_FORMAT eq "tar" )
	{
		if ( ZM_UPLOAD_ARCH_COMPRESS )
		{
			$arch_file .= '.tar.gz';
		}
		else
		{
			$arch_file .= '.tar';
		}
		Info( "Creating upload file '$arch_file'\n" );

		if ( $arch_error = !Archive::Tar->create_archive( $arch_file, ZM_UPLOAD_ARCH_COMPRESS, <*$arch_image_path> ) )
		{
			Error( "Tar error: ".Archive::Tar->error()."\n " );
		}
	}

	if ( $arch_error )
	{
		return( 0 );
	}
	else
	{
		Info( "Uploading to ".ZM_UPLOAD_FTP_HOST."\n" );
		my $ftp = Net::FTP->new( ZM_UPLOAD_FTP_HOST, Timeout=>ZM_UPLOAD_FTP_TIMEOUT, Passive=>ZM_UPLOAD_FTP_PASSIVE, Debug=>ZM_UPLOAD_FTP_DEBUG );
		if ( !$ftp )
		{
			warn( "Can't create ftp connection: $@" );
			return( 0 );
		}

		$ftp->login( ZM_UPLOAD_FTP_USER, ZM_UPLOAD_FTP_PASS ) or warn( "FTP - Can't login" );
		$ftp->binary() or warn( "FTP - Can't go binary" );
		$ftp->cwd( ZM_UPLOAD_FTP_REM_DIR ) or warn( "FTP - Can't cwd" );
		$ftp->put( $arch_file ) or warn( "FTP - Can't upload '$arch_file'" );
		$ftp->quit() or warn( "FTP - Can't quit" );
		unlink( $arch_file );
		my $sql = "update Events set Uploaded = 1 where Id = ?";
		my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
		my $res = $sth->execute( $event->{Id} ) or die( "Can't execute '$sql': ".$sth->errstr() );
	}
	return( 1 );
}

sub substituteTags
{
	my $text = shift;
	my $filter = shift;
	my $event = shift;
	my $attachments_ref = shift;

	# First we'd better check what we need to get
	# We have a filter and an event, do we need any more
	# monitor information?
	my $need_monitor = $text =~ /%(?:MET|MEH|MED|MEW|MEN|MEA)%/;

	my $monitor = {};
	if ( $need_monitor )
	{
		my $db_now = strftime( "%Y-%m-%d %H:%M:%S", localtime() );
		my $sql = "select M.Id, count(E.Id) as EventCount, count(if(E.Archived,1,NULL)) as ArchEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 HOUR && E.Archived = 0,1,NULL)) as HourEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 DAY && E.Archived = 0,1,NULL)) as DayEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 7 DAY && E.Archived = 0,1,NULL)) as WeekEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 MONTH && E.Archived = 0,1,NULL)) as MonthEventCount from Monitors as M left join Events as E on E.MonitorId = M.Id where MonitorId = ? group by E.MonitorId order by Id";
		my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
		my $res = $sth->execute( $event->{MonitorId} ) or die( "Can't execute '$sql': ".$sth->errstr() );
		$monitor = $sth->fetchrow_hashref();
		$sth->finish();
		return() if ( !$monitor );
	}

	# Do we need the image information too?
	my $need_images = $text =~ /%(?:EPI1|EPIM|EI1|EIM)%/;
	my $first_alarm_frame;
	my $max_alarm_frame;
	my $max_alarm_score = 0;
	if ( $need_images )
	{
		my $sql = "select * from Frames where EventId = ? and Type = 'Alarm' order by FrameId";
		my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
		my $res = $sth->execute( $event->{Id} ) or die( "Can't execute '$sql': ".$sth->errstr() );
		while( my $frame = $sth->fetchrow_hashref() )
		{
			if ( !$first_alarm_frame )
			{
				$first_alarm_frame = $frame;
			}
			if ( $frame->{Score} > $max_alarm_score )
			{
				$max_alarm_frame = $frame;
				$max_alarm_score = $frame->{Score};
			}
		}
		$sth->finish();
	}

	my $url = ZM_URL;
	$text =~ s/%ZP%/$url/g;
	$text =~ s/%MN%/$event->{MonitorName}/g;
	$text =~ s/%MET%/$monitor->{EventCount}/g;
	$text =~ s/%MEH%/$monitor->{HourEventCount}/g;
	$text =~ s/%MED%/$monitor->{DayEventCount}/g;
	$text =~ s/%MEW%/$monitor->{WeekEventCount}/g;
	$text =~ s/%MEM%/$monitor->{MonthEventCount}/g;
	$text =~ s/%MEA%/$monitor->{ArchEventCount}/g;
	$text =~ s/%MP%/$url?view=watch&mid=$event->{MonitorId}/g;
	$text =~ s/%MPS%/$url?view=watchfeed&mid=$event->{MonitorId}&mode=stream/g;
	$text =~ s/%MPI%/$url?view=watchfeed&mid=$event->{MonitorId}&mode=still/g;
	$text =~ s/%EP%/$url?view=event&mid=$event->{MonitorId}&eid=$event->{Id}/g;
	$text =~ s/%EPS%/$url?view=event&mode=stream&mid=$event->{MonitorId}&eid=$event->{Id}/g;
	$text =~ s/%EPI%/$url?view=event&mode=still&mid=$event->{MonitorId}&eid=$event->{Id}/g;
	$text =~ s/%EI%/$event->{Id}/g;
	$text =~ s/%EN%/$event->{Name}/g;
	$text =~ s/%ET%/$event->{StartTime}/g;
	$text =~ s/%ED%/$event->{Length}/g;
	$text =~ s/%EF%/$event->{Frames}/g;
	$text =~ s/%EFA%/$event->{AlarmFrames}/g;
	$text =~ s/%EST%/$event->{TotScore}/g;
	$text =~ s/%ESA%/$event->{AvgScore}/g;
	$text =~ s/%ESM%/$event->{MaxScore}/g;
	if ( $first_alarm_frame )
	{
		$text =~ s/%EPI1%/$url?view=frame&mid=$event->{MonitorId}&eid=$event->{Id}&fid=$first_alarm_frame->{FrameId}/g;
		$text =~ s/%EPIM%/$url?view=frame&mid=$event->{MonitorId}&eid=$event->{Id}&fid=$max_alarm_frame->{FrameId}/g;
		if ( $attachments_ref && $text =~ s/%EI1%//g )
		{
			push( @$attachments_ref, { type=>"image/jpeg", path=>sprintf( "%d/%d/%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $event->{MonitorId}, $event->{Id}, $first_alarm_frame->{FrameId} ) } );
		}
		if ( $attachments_ref && $text =~ s/%EIM%//g )
		{
			# Don't attach the same image twice
			if ( !@$attachments_ref || ($first_alarm_frame->{FrameId} != $max_alarm_frame->{FrameId} ) )
			{
				push( @$attachments_ref, { type=>"image/jpeg", path=>sprintf( "%d/%d/%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $event->{MonitorId}, $event->{Id}, $max_alarm_frame->{FrameId} ) } );
			}
		}
	}
	if ( $attachments_ref && ZM_OPT_MPEG ne "no" )
	{
		if ( $text =~ s/%EV%//g )
		{
			my ( $format, $path ) = generateVideo( $filter, $event );
			if ( !$format )
			{
				return( undef );
			}
			push( @$attachments_ref, { type=>"video/$format", path=>$path } );
		}
		if ( $text =~ s/%EVM%//g )
		{
			my ( $format, $path ) = generateVideo( $filter, $event, 1 );
			if ( !$format )
			{
				return( undef );
			}
			push( @$attachments_ref, { type=>"video/$format", path=>$path } );
		}
	}
	$text =~ s/%FN%/$filter->{Name}/g;
	( my $filter_name = $filter->{Name} ) =~ s/ /+/g;
	$text =~ s/%FP%/$url?view=filter&mid=$event->{MonitorId}&filter_name=$filter_name/g;
	
	return( $text );
}

sub sendEmail
{
	my $filter = shift;
	my $event = shift;

	if ( !ZM_FROM_EMAIL )
	{
		warn( "No 'from' email address defined, not sending email" );
		return( 0 );
	}
	if ( !ZM_EMAIL_ADDRESS )
	{
		warn( "No email address defined, not sending email" );
		return( 0 );
	}

	Info( "Creating notification email\n" );

	my $subject = substituteTags( $email_subject, $filter, $event );
	return( 0 ) if ( !$subject );
	my @attachments;
	my $body = substituteTags( $email_body, $filter, $event, \@attachments );
	return( 0 ) if ( !$body );

	Info( "Sending notification email '$subject'\n" );

	eval
	{
		if ( ZM_NEW_MAIL_MODULES )
		{
			### Create the multipart container
			my $mail = MIME::Lite->new (
				From => ZM_FROM_EMAIL,
				To => ZM_EMAIL_ADDRESS,
				Subject => $subject,
				Type => "multipart/mixed"
			);
			### Add the text message part
			$mail->attach (
				Type => "TEXT",
				Data => $body
			);
			### Add the attachments
			foreach my $attachment ( @attachments )
			{
				Info( "Attaching '$attachment->{path}\n" );
				$mail->attach(
					Path => $attachment->{path},
					Type => $attachment->{type},
					Disposition => "attachment"
				);
			}
			### Send the Message
			MIME::Lite->send( "smtp", ZM_EMAIL_HOST, Timeout=>60 );
			$mail->send();
        } 
		else
		{
			my $mail = MIME::Entity->build(
				From => ZM_FROM_EMAIL,
				To => ZM_EMAIL_ADDRESS,
				Subject => $subject,
				Type => (($body=~/<html>/)?'text/html':'text/plain'),
				Data => $body
			);

			foreach my $attachment ( @attachments )
			{
				Info( "Attaching '$attachment->{path}\n" );
				$mail->attach(
					Path => $attachment->{path},
					Type => $attachment->{type},
					Encoding => "base64"
				);
			}
			$mail->smtpsend( Host => ZM_EMAIL_HOST, MailFrom => ZM_FROM_EMAIL );
		}
	};
	if ( $@ )
	{
		warn( "Can't send email: $@" );
		return( 0 );
	}
	else
	{
		Info( "Notification email sent\n" );
	}
	my $sql = "update Events set Emailed = 1 where Id = ?";
	my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
	my $res = $sth->execute( $event->{Id} ) or die( "Can't execute '$sql': ".$sth->errstr() );

	return( 1 );
}

sub sendMessage
{
	my $filter = shift;
	my $event = shift;

	if ( !ZM_FROM_EMAIL )
	{
		warn( "No 'from' email address defined, not sending message" );
		return( 0 );
	}
	if ( !ZM_MESSAGE_ADDRESS )
	{
		warn( "No message address defined, not sending message" );
		return( 0 );
	}

	Info( "Creating notification message\n" );

	my $subject = substituteTags( $message_subject, $filter, $event );
	return( 0 ) if ( !$subject );
	my @attachments;
	my $body = substituteTags( $message_body, $filter, $event, \@attachments );
	return( 0 ) if ( !$body );

	Info( "Sending notification message '$subject'\n" );

	eval
	{
		if ( ZM_NEW_MAIL_MODULES )
		{
			### Create the multipart container
			my $mail = MIME::Lite->new (
				From => ZM_FROM_EMAIL,
				To => ZM_EMAIL_ADDRESS,
				Subject => $subject,
				Type => "multipart/mixed"
			);
			### Add the text message part
			$mail->attach (
				Type => "TEXT",
				Data => $body
			);
			### Add the attachments
			foreach my $attachment ( @attachments )
			{
				Info( "Attaching '$attachment->{path}\n" );
				$mail->attach(
					Path => $attachment->{path},
					Type => $attachment->{type},
					Disposition => "attachment"
				);
			}
			### Send the Message
			MIME::Lite->send( "smtp", ZM_EMAIL_HOST, Timeout=>60 );
			$mail->send();
        } 
		else
		{
			my $mail = MIME::Entity->build(
				From => ZM_FROM_EMAIL,
				To => ZM_MESSAGE_ADDRESS,
				Subject => $subject,
				Type => (($body=~/<html>/)?'text/html':'text/plain'),
				Data => $body
			);

			foreach my $attachment ( @attachments )
			{
				Info( "Attaching '$attachment->{path}\n" );
				$mail->attach(
					Path => $attachment->{path},
					Type => $attachment->{type},
					Encoding => "base64"
				);
			}
			$mail->smtpsend( Host => ZM_EMAIL_HOST, MailFrom => ZM_FROM_EMAIL );
		}
	};
	if ( $@ )
	{
		warn( "Can't send email: $@" );
		return( 0 );
	}
	else
	{
		Info( "Notification message sent\n" );
	}
	my $sql = "update Events set Messaged = 1 where Id = ?";
	my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
	my $res = $sth->execute( $event->{Id} ) or die( "Can't execute '$sql': ".$sth->errstr() );

	return( 1 );
}

sub executeCommand
{
	my $filter = shift;
	my $event = shift;

	my $event_path = "$event->{MonitorId}/$event->{Id}";

	my $command = $filter->{AutoExecuteCmd};
	$command .= " $event_path";

	Info( "Executing '$command'\n" );
	my $output = qx($command);
	my $status = $? >> 8;
	if ( $status || DBG_LEVEL > 0 )
	{
		chomp( $output );
		Debug( "Output: $output\n" );
	}
	if ( $status )
	{
		Error( "Command '$command' exited with status: $status\n" );
		return( 0 );
	}
	else
	{
		my $sql = "update Events set Executed = 1 where Id = ?";
		my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
		my $res = $sth->execute( $event->{Id} ) or die( "Can't execute '$sql': ".$sth->errstr() );
	}
	return( 1 );
}

