# ==========================================================================
#
# ZoneMinder Event Module, $Date$, $Revision$
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
package ZoneMinder::Event;

use 5.006;
use strict;
use warnings;

require Exporter;
require ZoneMinder::Base;
require Date::Manip;

our @ISA = qw(Exporter ZoneMinder::Base);

# Items to export into callers namespace by default. Note: do not export
# names by default without a very good reason. Use EXPORT_OK instead.
# Do not simply export all your public functions/methods/constants.

# This allows declaration   use ZoneMinder ':all';
# If you do not need this, moving things directly into @EXPORT or @EXPORT_OK
# will save memory.
our %EXPORT_TAGS = (
    'functions' => [ qw(
    ) ]
);
push( @{$EXPORT_TAGS{all}}, @{$EXPORT_TAGS{$_}} ) foreach keys %EXPORT_TAGS;

our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );

our @EXPORT = qw();

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# General Utility Functions
#
# ==========================================================================

use ZoneMinder::Config qw(:all);
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Database qw(:all);

use POSIX;

sub new {
    my ( $parent, $id, $data ) = @_;

	my $self = {};
	bless $self, $parent;
    $$self{dbh} = $ZoneMinder::Database::dbh;
#zmDbConnect();
	if ( ( $$self{Id} = $id ) or $data ) {
#$log->debug("loading $parent $id") if $debug or DEBUG_ALL;
		$self->load( $data );
	}
	return $self;
} # end sub new

sub load {
	my ( $self, $data ) = @_;
	my $type = ref $self;
	if ( ! $data ) {
#$log->debug("Object::load Loading from db $type");
		$data = $$self{dbh}->selectrow_hashref( 'SELECT * FROM Events WHERE Id=?', {}, $$self{Id} );
		if ( ! $data ) {
				Error( "Failure to load Event record for $$self{Id}: Reason: " . $$self{dbh}->errstr );
		} else {
			Debug( 3, "Loaded Event $$self{Id}" );	
		} # end if
	} # end if ! $data
	if ( $data and %$data ) {
		@$self{keys %$data} = values %$data;
	} # end if
} # end sub load

sub Name {
	if ( @_ > 1 ) {
		$_[0]{Name} = $_[1];
	}
	return $_[0]{Name};
} # end sub Path

sub find {
	shift if $_[0] eq 'ZoneMinder::Event';
	my %sql_filters = @_;

	my $sql = 'SELECT * FROM Events';
	my @sql_filters;
	my @sql_values;

    if ( exists $sql_filters{Name} ) {
        push @sql_filters , ' Name = ? ';
		push @sql_values, $sql_filters{Name};
    }

	$sql .= ' WHERE ' . join(' AND ', @sql_filters ) if @sql_filters;
	$sql .= ' LIMIT ' . $sql_filters{limit} if $sql_filters{limit};

	my $sth = $ZoneMinder::Database::dbh->prepare_cached( $sql )
        or Fatal( "Can't prepare '$sql': ".$ZoneMinder::Database::dbh->errstr() );
    my $res = $sth->execute( @sql_values )
            or Fatal( "Can't execute '$sql': ".$sth->errstr() );

	my @results;

	while( my $db_filter = $sth->fetchrow_hashref() ) {
		my $filter = new ZoneMinder::Event( $$db_filter{Id}, $db_filter );
		push @results, $filter;
	} # end while
	return @results;
}

sub find_one {
	my @results = find(@_);
	return $results[0] if @results;
}

sub getEventPath
{
    my $event = shift;

    my $event_path = "";
    if ( $Config{ZM_USE_DEEP_STORAGE} )
    {
        $event_path = $Config{ZM_DIR_EVENTS}
                      .'/'.$event->{MonitorId}
                      .'/'.strftime( "%y/%m/%d/%H/%M/%S",
                                     localtime($event->{Time})
                                   )
        ;
    }
    else
    {
        $event_path = $Config{ZM_DIR_EVENTS}
                      .'/'.$event->{MonitorId}
                      .'/'.$event->{Id}
        ;
    }

    if ( index($Config{ZM_DIR_EVENTS},'/') != 0 ){
        $event_path = $Config{ZM_PATH_WEB}
                      .'/'.$event_path
        ;
    }
    return( $event_path );
}

sub GenerateVideo {
	my ( $self, $rate, $fps, $scale, $size, $overwrite, $format ) = @_;

	my $event_path = getEventPath( $self );
	chdir( $event_path );
	( my $video_name = $self->{Name} ) =~ s/\s/_/g;

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
		Info( "Creating video file $video_file for event $self->{Id}\n" );

		my $frame_rate = sprintf( "%.2f", $self->{Frames}/$self->{FullLength} );
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

		my $width = $self->{MonitorWidth};
		my $height = $self->{MonitorHeight};
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
		my $command = $Config{ZM_PATH_FFMPEG}
		." -y -r $frame_rate "
			.$Config{ZM_FFMPEG_INPUT_OPTIONS}
		." -i %0"
			.$Config{ZM_EVENT_IMAGE_DIGITS}
		."d-capture.jpg -s $video_size "
#. " -f concat -i /tmp/event_files.txt"
			." -s $video_size "
			.$Config{ZM_FFMPEG_OUTPUT_OPTIONS}
		." '$video_file' > ffmpeg.log 2>&1"
			;
		Debug( $command."\n" );
		my $output = qx($command);

		my $status = $? >> 8;
		if ( $status )
		{
			Error( "Unable to generate video, check "
					.$event_path."/ffmpeg.log for details"
				 );
			return;
		}

		Info( "Finished $video_file\n" );
		return $event_path.'/'.$video_file;
	} else {
		Info( "Video file $video_file already exists for event $self->{Id}\n" );
		return $event_path.'/'.$video_file;
	}
	return;	
} # end sub GenerateVideo

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Database - Perl extension for blah blah blah

=head1 SYNOPSIS

  use ZoneMinder::Event;
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

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
