#!/usr/bin/perl -wT
#
# ==========================================================================
#
# ZoneMinder Video Creation Script, $Date$, $Revision$
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
# This script is used to create MPEG videos of events for the web pages
# or as email attachments.
#
use strict;
use bytes;

# ==========================================================================
#
# These are the elements you can edit to suit your installation
#
# ==========================================================================

use constant LOG_FILE => ZM_PATH_LOGS.'/zmvideo.log';
use constant VERBOSE => 0; # Whether to output more verbose debug

# ==========================================================================
#
# You shouldn't need to change anything from here downwards
#
# ==========================================================================

use ZoneMinder;
use DBI;
use Data::Dumper;
use Getopt::Long qw(:config no_ignore_case );

$| = 1;

$ENV{PATH}  = '/bin:/usr/bin';
$ENV{SHELL} = '/bin/sh' if exists $ENV{SHELL};
delete @ENV{qw(IFS CDPATH ENV BASH_ENV)};

my $event_id;
my $format = 'mpg';
my $rate = '';
my $scale = '';
my $fps = '';
my $size = '';
my $overwrite = 0;

my @formats = split( '/\s+/', ZM_FFMPEG_FORMATS );
for ( my $i = 0; $i < @formats; $i++ )
{
	if ( $i =~ /^(.+)\*$/ )
	{
		$format = $formats[$i] = $1;
	}
}

sub Usage
{
	print( "
Usage: zmvideo.pl -e <event_id>,--event=<event_id> [--format <format>] [--rate=<rate>] [--scale=<scale>] [--fps=<fps>] [--size=<size>] [--overwrite]
Parameters are :-
-e<event_id>, --event=<event_id>  - What event to create the video for
-f<format>, --format=<format>     - What format to create the video in, default is mpg. For ffmpeg only.
-r<rate>, --rate=<rate>           - Relative rate , 1 = realtime, 2 = double speed , 0.5 = half speed etc
-s<scale>, --scale=<scale>        - Scale, 1 = normal, 2 = double size, 0.5 = half size etc
-F<fps>, --fps=<fps>              - Absolute frame rate, in frames per second
-S<size>, --size=<size>           - Absolute video size, WxH or other specification supported by ffmpeg
-o, --overwrite                   - Whether to overwrite an existing file, off by default.
");
	exit( -1 );
}

if ( !GetOptions( 'event=i'=>\$event_id, 'format|f=s'=>\$format, 'rate|r=f'=>\$rate, 'scale|s=f'=>\$scale, 'fps|F=f'=>\$fps, 'size|S=s'=>\$size, 'overwrite'=>\$overwrite ) )
{
	Usage();
}

if ( !$event_id || $event_id < 0 )
{
	print( STDERR "Please give a valid event id\n" );
	Usage();
}

if ( ZM_OPT_MPEG eq "no" )
{
	print( STDERR "Mpeg encoding is not currently enabled\n" );
	exit(-1);
}

if ( ZM_OPT_MPEG eq "mpeg_encode" && $rate != 1.0 )
{
	print( STDERR "Variable rate not supported with mpeg_encode\n" );
	exit(-1);
}

if ( $format ne 'mpg' && ZM_OPT_MPEG eq "mpeg_encode" )
{
	print( STDERR "Format not supported for mpeg_encode\n" );
	Usage();
}

if ( !$rate && !$fps )
{
	$rate = 1;
}

if ( !$scale && !$size )
{
	$scale = 1;
}

if ( $rate && ($rate < 0.25 || $rate > 100) )
{
	print( STDERR "Rate is out of range, 0.25 >= rate <= 100\n" );
	Usage();
}

if ( $scale && ($scale < 0.25 || $scale > 4) )
{
	print( STDERR "Scale is out of range, 0.25 >= scale <= 4\n" );
	Usage();
}

if ( $fps && ($fps > 30) )
{
	print( STDERR "FPS is out of range, <= 30\n" );
	Usage();
}

my ( $detaint_format ) = $format =~ /^(\w+)$/;
my ( $detaint_rate ) = $rate =~ /^(-?\d+(?:\.\d+)?)$/;
my ( $detaint_scale ) = $scale =~ /^(-?\d+(?:\.\d+)?)$/;
my ( $detaint_fps ) = $fps =~ /^(-?\d+(?:\.\d+)?)$/;
my ( $detaint_size ) = $size =~ /^(\w+)$/;

$format = $detaint_format;
$rate = $detaint_rate;
$scale = $detaint_scale;
$fps = $detaint_fps;
$size = $detaint_size;

my $log_file = LOG_FILE;
open( LOG, ">>$log_file" ) or die( "Can't open log file: $!" );
#open( STDOUT, ">&LOG" ) || die( "Can't dup stdout: $!" );
#select( STDOUT ); $| = 1;
open( STDERR, ">&LOG" ) || die( "Can't dup stderr: $!" );
select( STDERR ); $| = 1;
select( LOG ); $| = 1;

my $dbh = DBI->connect( "DBI:mysql:database=".ZM_DB_NAME.";host=".ZM_DB_SERVER, ZM_DB_USER, ZM_DB_PASS );

my @filters;
my $sql = "select max(F.Delta)-min(F.Delta) as FullLength, E.*, M.Name as MonitorName, M.Width as MonitorWidth, M.Height as MonitorHeight, M.Palette from Frames as F inner join Events as E on F.EventId = E.Id inner join Monitors as M on E.MonitorId = M.Id where EventId = '$event_id' group by F.EventId";
my $sth = $dbh->prepare_cached( $sql ) or die( "Can't prepare '$sql': ".$dbh->errstr() );
my $res = $sth->execute() or die( "Can't execute: ".$sth->errstr() );
my $event = $sth->fetchrow_hashref();
$sth->finish();
chdir( ZM_PATH_WEB.'/'.ZM_DIR_EVENTS.'/'.$event->{MonitorId}.'/'.$event->{Id} );
( my $video_name = $event->{Name} ) =~ s/\s/_/g; 

my @file_parts;
if ( $rate )
{
	my $file_rate = $rate;
	$file_rate =~ s/\./_/;
	$file_rate =~ s/_00//;
	$file_rate =~ s/(_\d+)0+$/$1/;
	$file_rate = 'r'.$file_rate;
	push( @file_parts, $file_rate );
}
elsif ( $fps )
{
	my $file_fps = $fps;
	$file_fps =~ s/\./_/;
	$file_fps =~ s/_00//;
	$file_fps =~ s/(_\d+)0+$/$1/;
	$file_fps = 'R'.$file_fps;
	push( @file_parts, $file_fps );
}

if ( $scale )
{
	my $file_scale = $scale;
	$file_scale =~ s/\./_/;
	$file_scale =~ s/_00//;
	$file_scale =~ s/(_\d+)0+$/$1/;
	$file_scale = 's'.$file_scale;
	push( @file_parts, $file_scale );
}
elsif ( $size )
{
	my $file_size = 'S'.$size;
	push( @file_parts, $file_size );
}
my $video_file = "$video_name-".$file_parts[0]."-".$file_parts[1].".$format";

if ( $overwrite || !-s $video_file )
{
	print( LOG "Creating video file $video_file for event $event->{Id}\n" );

	if ( ZM_OPT_MPEG eq "mpeg_encode" )
	{
		my $param_file = "$video_name.mpe";
		open( PARAMS, ">$param_file" ) or die( "Can't open '$param_file': $!" );

		print( PARAMS "PATTERN		IBBPBBPBBPBBPBB\n" );
		print( PARAMS "FORCE_ENCODE_LAST_FRAME\n" );
		print( PARAMS "OUTPUT		$video_file\n" );

		print( PARAMS "BASE_FILE_FORMAT	JPEG\n" );
		print( PARAMS "GOP_SIZE	30\n" );
		print( PARAMS "SLICES_PER_FRAME	1\n" );

		print( PARAMS "PIXEL		HALF\n" );
		print( PARAMS "RANGE		10\n" );
		print( PARAMS "PSEARCH_ALG	LOGARITHMIC\n" );
		print( PARAMS "BSEARCH_ALG	CROSS2\n" );
		print( PARAMS "IQSCALE		8\n" );
		print( PARAMS "PQSCALE		10\n" );
		print( PARAMS "BQSCALE		25\n" );

		print( PARAMS "REFERENCE_FRAME	ORIGINAL\n" );
		print( PARAMS "FRAME_RATE 24\n" );

		my $scale_conversion = "";
		if ( $scale != 1 )
		{
			if ( $scale > 1 )
			{
				$scale_conversion = ZM_PATH_NETPBM."/pnmscale $scale";
			}
			else
			{
				$scale_conversion = ZM_PATH_NETPBM."/pnmscale ".(1/$scale);
			}
			if ( $event->{Palette} == 1 && !ZM_COLOUR_JPEG_FILES )
			{
				print( PARAMS "INPUT_CONVERT	".ZM_PATH_NETPBM."/jpegtopnm * | ".$scale_conversion." | ".ZM_PATH_NETPBM."/pgmtoppm white | ".ZM_PATH_NETPBM."/ppmtojpeg\n" );
			}
			else
			{
				print( PARAMS "INPUT_CONVERT	".ZM_PATH_NETPBM."/jpegtopnm * | ".$scale_conversion." | ".ZM_PATH_NETPBM."/ppmtojpeg\n" );
			}
		}
		else
		{
			if ( $event->{Palette} == 1 && !ZM_COLOUR_JPEG_FILES )
			{
				print( PARAMS "INPUT_CONVERT	".ZM_PATH_NETPBM."/jpegtopnm * | ".ZM_PATH_NETPBM."/pgmtoppm white | ".ZM_PATH_NETPBM."/ppmtojpeg\n" );
			}
			else
			{
				print( PARAMS "INPUT_CONVERT	*\n" );
			}
		}
		print( PARAMS "INPUT_DIR	.\n" );

		print( PARAMS "INPUT\n" );
		for ( my $i = 1; $i <= $event->{Frames}; $i++ )
		{
			printf( PARAMS "%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg\n", $i );
		}
		print( PARAMS "END_INPUT\n" );
		close( PARAMS );

		my $command = ZM_PATH_MPEG_ENCODE." $param_file >mpeg_encode.log";
		print( LOG $command."\n" );
		my $output = qx($command);
		print( LOG $output."\n" );
	}
	elsif ( ZM_OPT_MPEG eq "ffmpeg" )
	{
		my $frame_rate = sprintf( "%.2f", $event->{Frames}/$event->{FullLength} );
		if ( $rate )
		{
			if ( $rate != 1.0 )
			{
				$frame_rate *= $rate;
			}
		}
		elsif ( $fps )
		{
			$frame_rate = $fps;
		}

		my $width = $event->{MonitorWidth};
		my $height = $event->{MonitorHeight};
		my $video_size = " ${width}x${height}";

		if ( $scale )
		{
			if ( $scale != 1.0 )
			{
				$width = int($width*$scale);
				$height = int($height*$scale);
				$video_size = " ${width}x${height}";
			}
		}
		elsif ( $size )
		{
			$video_size = $size;
		}


		my $command = ZM_PATH_FFMPEG." -y -r $frame_rate ".ZM_FFMPEG_INPUT_OPTIONS." -i %0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg -s $video_size ".ZM_FFMPEG_OUTPUT_OPTIONS." $video_file > ffmpeg.log";
		print( LOG $command."\n" );
		my $output = qx($command);
		print( LOG $output."\n" );
	}
	else
	{
		die( "Bogus mpeg option ".ZM_OPT_MPEG."\n" );
	}
	
	my $status = $? >> 8;
	if ( $status )
	{
		die( "Error: $status" );
	}

	print( LOG "Finished $video_file\n" );
}
else
{
	print( LOG "Video file $video_file already exists for event $event->{Id}\n" );
}
#print( STDOUT $event->{MonitorId}.'/'.$event->{Id}.'/'.$video_file."\n" );
print( STDOUT $video_file."\n" );
exit( 0 );
