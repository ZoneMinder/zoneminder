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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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

require ZoneMinder::Base;
require ZoneMinder::Object;
require ZoneMinder::Storage;
require Date::Manip;
require File::Find;

#our @ISA = qw(ZoneMinder::Object);
use parent qw(ZoneMinder::Object);

# ==========================================================================
#
# General Utility Functions
#
# ==========================================================================

use ZoneMinder::Config qw(:all);
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Database qw(:all);
require Date::Parse;

use vars qw/ $table $primary_key %fields $serial @identified_by/;
$table = 'Events';
@identified_by = ('Id');
$serial = $primary_key = 'Id';
%fields = map { $_, $_ } qw(
  Id
  MonitorId
  StorageId
  Name
  Cause
  StartTime
  EndTime
  Width
  Height
  Length
  Frames
  AlarmFrames
  DefaultVideo
  TotScore
  AvgScore
  MaxScore
  Archived
  Videoed
  Uploaded
  Emailed
  Messaged
  Executed
  Notes
  StateId
  Orientation
  DiskSpace
);

use POSIX;

sub Time {
  if ( @_ > 1 ) {
    $_[0]{Time} = $_[1];
  }
  if ( ! defined $_[0]{Time} ) {

    $_[0]{Time} = Date::Parse::str2time( $_[0]{StartTime} );
  }
  return $_[0]{Time};
}

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
  $sth->finish();
  return @results;
}

sub find_one {
  my @results = find(@_);
  return $results[0] if @results;
}

sub getPath {
  return Path( @_ );
}
sub Path {
  my $event = shift;

  if ( @_ > 1 ) {
    $$event{Path} = $_[1];
    if ( ! -e $$event{Path} ) {
      Error("Setting path for event $$event{Id} to $_[1] but does not exist!");
    }
  }

  if ( ! $$event{Path} ) {
    my $Storage = $event->Storage();

    if ( $Config{ZM_USE_DEEP_STORAGE} ) {
      if ( $event->Time() ) {
        $$event{Path} = join('/',
            $Storage->Path(),
            $event->{MonitorId},
            strftime( "%y/%m/%d/%H/%M/%S",
              localtime($event->Time())
              ),
            );
      } else {
        Error("Event $$event{Id} has no value for Time(), unable to determine path");
        $$event{Path} = '';
      }
    } else {
      $$event{Path} = join('/',
          $Storage->Path(),
          $event->{MonitorId},
          $event->{Id},
          );
    }
  } # end if

  return $$event{Path};
}

sub GenerateVideo {
  my ( $self, $rate, $fps, $scale, $size, $overwrite, $format ) = @_;

  my $event_path = $self->getPath( );
  chdir( $event_path );
  ( my $video_name = $self->{Name} ) =~ s/\s/_/g;

  my @file_parts;
  if ( $rate ) {
    my $file_rate = $rate;
    $file_rate =~ s/\./_/;
    $file_rate =~ s/_00//;
    $file_rate =~ s/(_\d+)0+$/$1/;
    $file_rate = 'r'.$file_rate;
    push( @file_parts, $file_rate );
  } elsif ( $fps ) {
    my $file_fps = $fps;
    $file_fps =~ s/\./_/;
    $file_fps =~ s/_00//;
    $file_fps =~ s/(_\d+)0+$/$1/;
    $file_fps = 'R'.$file_fps;
    push( @file_parts, $file_fps );
  }

  if ( $scale ) {
    my $file_scale = $scale;
    $file_scale =~ s/\./_/;
    $file_scale =~ s/_00//;
    $file_scale =~ s/(_\d+)0+$/$1/;
    $file_scale = 's'.$file_scale;
    push( @file_parts, $file_scale );
  } elsif ( $size ) {
    my $file_size = 'S'.$size;
    push( @file_parts, $file_size );
  }
  my $video_file = "$video_name-".$file_parts[0]."-".$file_parts[1].".$format";
  if ( $overwrite || !-s $video_file ) {
    Info( "Creating video file $video_file for event $self->{Id}\n" );

    my $frame_rate = sprintf( "%.2f", $self->{Frames}/$self->{FullLength} );
    if ( $rate ) {
      if ( $rate != 1.0 ) {
        $frame_rate *= $rate;
      }
    } elsif ( $fps ) {
      $frame_rate = $fps;
    }

    my $width = $self->{MonitorWidth};
    my $height = $self->{MonitorHeight};
    my $video_size = " ${width}x${height}";

    if ( $scale ) {
      if ( $scale != 1.0 ) {
        $width = int($width*$scale);
        $height = int($height*$scale);
        $video_size = " ${width}x${height}";
      }
    } elsif ( $size ) {
      $video_size = $size;
    }
    my $command = $Config{ZM_PATH_FFMPEG}
    ." -y -r $frame_rate "
      .$Config{ZM_FFMPEG_INPUT_OPTIONS}
    .' -i ' . ( $$self{DefaultVideo} ? $$self{DefaultVideo} : '%0'.$Config{ZM_EVENT_IMAGE_DIGITS} .'d-capture.jpg' )
#. " -f concat -i /tmp/event_files.txt"
      ." -s $video_size "
      .$Config{ZM_FFMPEG_OUTPUT_OPTIONS}
    ." '$video_file' > ffmpeg.log 2>&1"
      ;
    Debug( $command."\n" );
    my $output = qx($command);

    my $status = $? >> 8;
    if ( $status ) {
      Error( "Unable to generate video, check $event_path/ffmpeg.log for details");
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

sub delete {
  my $event = $_[0];
  if ( ! ( $event->{Id} and $event->{MonitorId} and $event->{StartTime} ) ) {
    my ( $caller, undef, $line ) = caller;
    Warning( "Can't Delete event $event->{Id} from Monitor $event->{MonitorId} $event->{StartTime} from $caller:$line\n" );
    return;
  }
  Info( "Deleting event $event->{Id} from Monitor $event->{MonitorId} $event->{StartTime}\n" );
  $ZoneMinder::Database::dbh->ping();
# Do it individually to avoid locking up the table for new events
  my $sql = 'delete from Events where Id = ?';
  my $sth = $ZoneMinder::Database::dbh->prepare_cached( $sql )
    or Fatal( "Can't prepare '$sql': ".$ZoneMinder::Database::dbh->errstr() );
  my $res = $sth->execute( $event->{Id} )
    or Fatal( "Can't execute '$sql': ".$sth->errstr() );
  $sth->finish();

  if ( ! $Config{ZM_OPT_FAST_DELETE} ) {
    my $sql = 'delete from Frames where EventId = ?';
    my $sth = $ZoneMinder::Database::dbh->prepare_cached( $sql )
      or Fatal( "Can't prepare '$sql': ".$ZoneMinder::Database::dbh->errstr() );
    my $res = $sth->execute( $event->{Id} )
      or Fatal( "Can't execute '$sql': ".$sth->errstr() );
    $sth->finish();

    $sql = 'delete from Stats where EventId = ?';
    $sth = $ZoneMinder::Database::dbh->prepare_cached( $sql )
      or Fatal( "Can't prepare '$sql': ".$ZoneMinder::Database::dbh->errstr() );
    $res = $sth->execute( $event->{Id} )
      or Fatal( "Can't execute '$sql': ".$sth->errstr() );
    $sth->finish();

    $event->delete_files( );
  } else {
    Debug('Not deleting frames, stats and files for speed.');
  }
} # end sub delete

sub delete_files {

  my $Storage = new ZoneMinder::Storage( $_[0]{StorageId} );
  my $storage_path = $Storage->Path();

  if ( ! $storage_path ) {
    Fatal("Empty storage path when deleting files for event $_[0]{Id} with storage id $_[0]{StorageId} ");
    return;
  }

  chdir ( $storage_path );

  if ( $Config{ZM_USE_DEEP_STORAGE} ) {
    if ( ! $_[0]{MonitorId} ) {
      Error("No monitor id assigned to event $_[0]{Id}");
      return;
    }
    Debug("Deleting files for Event $_[0]{Id} from $storage_path.");
    my $link_path = $_[0]{MonitorId}."/*/*/*/.".$_[0]{Id};
#Debug( "LP1:$link_path" );
    my @links = glob($link_path);
#Debug( "L:".$links[0].": $!" );
    if ( @links ) {
      ( $link_path ) = ( $links[0] =~ /^(.*)$/ ); # De-taint
#Debug( "LP2:$link_path" );

        ( my $day_path = $link_path ) =~ s/\.\d+//;
#Debug( "DP:$day_path" );
      my $event_path = $day_path.readlink( $link_path );
      ( $event_path ) = ( $event_path =~ /^(.*)$/ ); # De-taint
#Debug( "EP:$event_path" );
        my $command = "/bin/rm -rf $event_path";
#Debug( "C:$command" );
      ZoneMinder::General::executeShellCommand( $command );

      unlink( $link_path ) or Error( "Unable to unlink '$link_path': $!" );

      my @path_parts = split( /\//, $event_path );
      for ( my $i = int(@path_parts)-2; $i >= 1; $i-- ) {
        my $delete_path = join( '/', @path_parts[0..$i] );
#Debug( "DP$i:$delete_path" );
        my @has_files = glob( join('/', $storage_path,$delete_path,'*' ) );
#Debug( "HF1:".$has_files[0] ) if ( @has_files );
        last if ( @has_files );
        @has_files = glob( join('/', $storage_path, $delete_path, '.[0-9]*' ) );
#Debug( "HF2:".$has_files[0] ) if ( @has_files );
        last if ( @has_files );
        my $command = "/bin/rm -rf $storage_path/$delete_path";
        ZoneMinder::General::executeShellCommand( $command );
      }
    }
  } else {
    my $command = "/bin/rm -rf $storage_path/$_[0]{MonitorId}/$_[0]{Id}";
    ZoneMinder::General::executeShellCommand( $command );
  }
} # end sub delete_files

sub Storage {
  return new ZoneMinder::Storage( $_[0]{StorageId} );
}

sub check_for_in_filesystem {
  my $path = $_[0]->Path();
  if ( $path ) {
    my @files = glob( $path . '/*' );
Debug("Checking for files for event $_[0]{Id} at $path using glob $path/* found " . scalar @files . " files");
    return 1 if @files;
  }
Debug("Checking for files for event $_[0]{Id} at $path using glob $path/* found no files");
  return 0;
}

sub age {
  if ( ! $_[0]{age} ) {
    if ( -e $_[0]->Path() ) { 
      # $^T is the time the program began running. -M is program start time - file modification time in days
      $_[0]{age} = (time() - ($^T - ((-M $_[0]->Path() ) * 24*60*60)));
    } else {
      Warning($_[0]->Path() . ' does not appear to exist.');
    }
  }
  return $_[0]{age};
}

sub DiskSpace {
  if ( @_ > 1 ) {
    Debug("Cleared DiskSpace, was $_[0]{DiskSpace}");
    $_[0]{DiskSpace} = $_[1];
  }
  if ( ! defined $_[0]{DiskSpace} ) {
    my $size = 0;
    File::Find::find( { wanted=>sub { $size += -f $_ ? -s _ : 0 }, untaint=>1 }, $_[0]->Path() );
    $_[0]{DiskSpace} = $size;
    Debug("DiskSpace for event $_[0]{Id} at $_[0]{Path} Updated to $size bytes");
  }
}

1;
__END__

=head1 NAME

ZoneMinder::Event - Perl Class for events

=head1 SYNOPSIS

use ZoneMinder::Event;

=head1 DESCRIPTION

The Event class has everything you need to deal with events from Perl.

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2017  ZoneMinder LLC

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
