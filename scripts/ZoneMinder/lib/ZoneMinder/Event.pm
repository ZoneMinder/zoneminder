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
require File::Path;
require File::Copy;
require File::Basename;
require Number::Bytes::Human;

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
  SaveJPEGs
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
  Scheme
);

use POSIX;

sub Time {
  if ( @_ > 1 ) {
    $_[0]{Time} = $_[1];
  }
  if ( ! defined $_[0]{Time} ) {
    if ( $_[0]{StartTime} ) {
      $_[0]{Time} = Date::Parse::str2time( $_[0]{StartTime} );
    }
  }
  return $_[0]{Time};
}

sub Name {
  if ( @_ > 1 ) {
    $_[0]{Name} = $_[1];
  }
  return $_[0]{Name};
} # end sub Name

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
  if ( exists $sql_filters{Id} ) {
    push @sql_filters , ' Id = ? ';
    push @sql_values, $sql_filters{Id};
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

  if ( @_ ) {
    $$event{Path} = $_[0];
    if ( $$event{Path} and ! -e $$event{Path} ) {
      Error("Setting path for event $$event{Id} to $_[0] but does not exist!");
    }
  }

  if ( ! $$event{Path} ) {
    my $Storage = $event->Storage();
    $$event{Path} = join('/', $Storage->Path(), $event->RelativePath() );
  }
  return $$event{Path};
}

sub Scheme {
  my $self = shift;
  $$self{Scheme} = shift if @_;

  if ( ! $$self{Scheme} ) {
    if ( $$self{RelativePath} ) {
      if ( $$self{RelativePath} =~ /^\d+\/\d{4}\-\d{2}\-\d{2}\/\d+$/ ) {
        $$self{Scheme} = 'Medium';
      } elsif ( $$self{RelativePath} =~ /^\d+\/\d{2}\/\d{2}\/\d{2}\/\d{2}\/\d{2}\/\d{2}\/$/ ) {
        $$self{Scheme} = 'Deep';
      }
    } # end if RelativePath
  }
  return $$self{Scheme};
}

sub RelativePath {
  my $event = shift;
  if ( @_ ) {
    $$event{RelativePath} = $_[0];
  }

  if ( ! $$event{RelativePath} ) {
    if ( $$event{Scheme} eq 'Deep' ) {
      if ( $event->Time() ) {
        $$event{RelativePath} = join('/',
            $event->{MonitorId},
            strftime( '%y/%m/%d/%H/%M/%S',
              localtime($event->Time())
              ),
            );
      } else {
        Error("Event $$event{Id} has no value for Time(), unable to determine path");
        $$event{RelativePath} = '';
      }
    } elsif ( $$event{Scheme} eq 'Medium' ) {
      if ( $event->Time() ) {
        $$event{RelativePath} = join('/',
            $event->{MonitorId},
            strftime( '%Y-%m-%d', localtime($event->Time())),
            $event->{Id},
            );
      } else {
        Error("Event $$event{Id} has no value for Time(), unable to determine path");
        $$event{RelativePath} = '';
      }
    } else { # Shallow
      $$event{RelativePath} = join('/',
          $event->{MonitorId},
          $event->{Id},
          );
    } # end if Scheme
  } # end if ! Path

  return $$event{RelativePath};
}

sub LinkPath {
  my $event = shift;
  if ( @_ ) {
    $$event{LinkPath} = $_[0];
  }

  if ( ! $$event{LinkPath} ) {
    if ( $$event{Scheme} eq 'Deep' ) {
      if ( $event->Time() ) {
        $$event{LinkPath} = join('/',
            $event->{MonitorId},
            strftime( '%y/%m/%d',
              localtime($event->Time())
              ),
            '.'.$$event{Id}
            );
      } elsif ( $$event{Path} ) {
        if ( ( $event->RelativePath() =~ /^(\d+\/\d{4}\/\d{2}\/\d{2})/ ) ) {
          $$event{LinkPath} = $1.'/.'.$$event{Id};
        } else {
          Error("Unable to get LinkPath from Path for $$event{Id} $$event{Path}");
          $$event{LinkPath} = '';
        }
      } else {
        Error("Event $$event{Id} $$event{Path} has no value for Time(), unable to determine link path");
        $$event{LinkPath} = '';
      }
    } # end if Scheme
  } # end if ! Path

  return $$event{LinkPath};
} # end sub LinkPath

sub createPath {
  makePath($_[0]->Path());
}

sub createLinkPath {
  my $LinkPath = $_[0]->LinkPath();
  my $EventPath = $_[0]->EventPath();
  if ( $LinkPath ) {
    if ( !symlink($EventPath, $LinkPath) ) {
      Error("Failed symlinking $EventPath to $LinkPath");
    }
  }
}

sub idPath {
  return sprintf('%s/.%d', $_[0]->Path(), $_[0]->{Id});
}

sub createIdFile {
  my $event = shift;
  my $idFile = $event->idPath();
  open( my $ID_FP, '>', $idFile )
    or Error("Can't open $idFile: $!");
  close($ID_FP);
  setFileOwner($idFile); 
}

sub GenerateVideo {
  my ( $self, $rate, $fps, $scale, $size, $overwrite, $format ) = @_;

  my $event_path = $self->Path( );
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
  my $video_file = join('-', $video_name, $file_parts[0], $file_parts[1] ).'.'.$format;
  if ( $overwrite || !-s $video_file ) {
    Info("Creating video file $video_file for event $self->{Id}");

    my $frame_rate = sprintf('%.2f', $self->{Frames}/$self->{FullLength});
    if ( $rate ) {
      if ( $rate != 1.0 ) {
        $frame_rate *= $rate;
      }
    } elsif ( $fps ) {
      $frame_rate = $fps;
    }

    my $width = $self->{Width};
    my $height = $self->{Height};
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
    Debug($command);
    my $output = qx($command);

    my $status = $? >> 8;
    if ( $status ) {
      Error("Unable to generate video, check $event_path/ffmpeg.log for details");
      return;
    }

    Info("Finished $video_file");
    return $event_path.'/'.$video_file;
  } else {
    Info("Video file $video_file already exists for event $self->{Id}");
    return $event_path.'/'.$video_file;
  }
  return;
} # end sub GenerateVideo

sub delete {
  my $event = $_[0];

  my $in_zmaudit = ( $0 =~ 'zmaudit.pl$');

  if ( ! $in_zmaudit ) {
    if ( ! ( $event->{Id} and $event->{MonitorId} and $event->{StartTime} ) ) {
      # zmfilter shouldn't delete anything in an odd situation. zmaudit will though.
      my ( $caller, undef, $line ) = caller;
      Warning("$0 Can't Delete event $event->{Id} from Monitor $event->{MonitorId} StartTime:".
        (defined($event->{StartTime})?$event->{StartTime}:'undef')." from $caller:$line");
      return;
    }
    if ( !($event->Storage()->Path() and -e $event->Storage()->Path()) ) {
      Warning('Not deleting event because storage path doesn\'t exist');
      return;
    }
  }

  if ( $$event{Id} ) {
    # Need to have an event Id if we are to delete from the db.  
    Info("Deleting event $event->{Id} from Monitor $event->{MonitorId} StartTime:$event->{StartTime}");
    $ZoneMinder::Database::dbh->ping();

    $ZoneMinder::Database::dbh->begin_work();
    #$event->lock_and_load();

    ZoneMinder::Database::zmDbDo('DELETE FROM Frames WHERE EventId=?', $$event{Id});
    if ( $ZoneMinder::Database::dbh->errstr() ) {
      $ZoneMinder::Database::dbh->commit();
      return;
    }
    ZoneMinder::Database::zmDbDo('DELETE FROM Stats WHERE EventId=?', $$event{Id});
    if ( $ZoneMinder::Database::dbh->errstr() ) {
      $ZoneMinder::Database::dbh->commit();
      return;
    }

    # Do it individually to avoid locking up the table for new events
    ZoneMinder::Database::zmDbDo('DELETE FROM Events WHERE Id=?', $$event{Id});
    $ZoneMinder::Database::dbh->commit();
  }

  if ( ( $in_zmaudit or (!$Config{ZM_OPT_FAST_DELETE})) and $event->Storage()->DoDelete() ) {
    $event->delete_files();
  } else {
    Debug('Not deleting event files from '.$event->Path().' for speed.');
  }
} # end sub delete

sub delete_files {
  my $event = shift;

  my $Storage = @_ ? $_[0] : new ZoneMinder::Storage($$event{StorageId});
  my $storage_path = $Storage->Path();

  if ( ! $storage_path ) {
    Error("Empty storage path when deleting files for event $$event{Id} with storage id $$event{StorageId}");
    return;
  }

  if ( ! $$event{MonitorId} ) {
    Error("No monitor id assigned to event $$event{Id}");
    return;
  }
  my $event_path = $event->RelativePath();
  Debug("Deleting files for Event $$event{Id} from $storage_path/$event_path, scheme is $$event{Scheme}.");
  if ( $event_path ) {
    ( $storage_path ) = ( $storage_path =~ /^(.*)$/ ); # De-taint
    ( $event_path ) = ( $event_path =~ /^(.*)$/ ); # De-taint

    my $deleted = 0;
    if ( $$Storage{Type} and ( $$Storage{Type} eq 's3fs' ) ) {
      my ( $aws_id, $aws_secret, $aws_host, $aws_bucket ) = ( $$Storage{Url} =~ /^\s*([^:]+):([^@]+)@([^\/]*)\/(.+)\s*$/ );
      eval {
        require Net::Amazon::S3;
        my $s3 = Net::Amazon::S3->new( {
             aws_access_key_id     => $aws_id,
             aws_secret_access_key => $aws_secret,
             ( $aws_host ? ( host => $aws_host ) : () ),
             });
        my $bucket = $s3->bucket($aws_bucket);
        if ( ! $bucket ) {
          Error("S3 bucket $bucket not found.");
          die;
        }
        if ( $bucket->delete_key($event_path) ) {
          $deleted = 1;
        } else {
          Error('Failed to delete from S3:'.$s3->err . ': ' . $s3->errstr);
        }
      };
      Error($@) if $@;
    }
    if ( !$deleted ) {
      my $command = "/bin/rm -rf $storage_path/$event_path";
      ZoneMinder::General::executeShellCommand($command);
    }
  }

  if ( $event->Scheme() eq 'Deep' ) {
    my $link_path = $event->LinkPath();
    Debug("Deleting link for Event $$event{Id} from $storage_path/$link_path.");
    if ( $link_path ) {
      ( $link_path ) = ( $link_path =~ /^(.*)$/ ); # De-taint
        unlink($storage_path.'/'.$link_path) or Error("Unable to unlink '$storage_path/$link_path': $!");
    }
  }
} # end sub delete_files

sub Storage {
  if ( @_ > 1 ) {
    $_[0]{Storage} = $_[1];
  }
  if ( ! $_[0]{Storage} ) {
    $_[0]{Storage} = new ZoneMinder::Storage($_[0]{StorageId});
  }
  return $_[0]{Storage};
}

sub check_for_in_filesystem {
  my $path = $_[0]->Path();
  if ( $path ) {
    if ( -e $path ) {
      my @files = glob "$path/*";
      Debug("Checking for files for event $_[0]{Id} at $path using glob $path/* found " . scalar @files . ' files');
      return 1 if @files;
    } else {
      Warning("Path not found for Event $_[0]{Id} at $path");
    }
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
    Debug("Cleared DiskSpace, was $_[0]{DiskSpace}") if $_[0]{DiskSpace};
    $_[0]{DiskSpace} = $_[1];
  }
  if ( ! defined $_[0]{DiskSpace} ) {
    if ( -e $_[0]->Path() ) {
      my $size = 0;
      File::Find::find( { wanted=>sub { $size += -f $_ ? -s _ : 0 }, untaint=>1 }, $_[0]->Path() );
      $_[0]{DiskSpace} = $size;
      Debug("DiskSpace for event $_[0]{Id} at $_[0]{Path} Updated to $size bytes");
    } else {
      Warning("DiskSpace: Event does not exist at $_[0]{Path}:" . $_[0]->to_string() );
    }
  } # end if ! defined DiskSpace
  return $_[0]{DiskSpace};
}

sub MoveTo {
  my ( $self, $NewStorage ) = @_;

  my $OldStorage = $self->Storage(undef);
  my ( $OldPath ) = ( $self->Path() =~ /^(.*)$/ ); # De-taint
  if ( ! -e $OldPath ) {
    return "Old path $OldPath does not exist.";
  }
  # First determine if we can move it to the dest.
  # We do this before bothering to lock the event
  my ( $NewPath ) = ( $NewStorage->Path() =~ /^(.*)$/ ); # De-taint
  if ( ! $$NewStorage{Id} ) {
    return "New storage does not have an id.  Moving will not happen.";
  } elsif ( $$NewStorage{Id} == $$self{StorageId} ) {
    return "Event is already located at " . $NewPath;
  } elsif ( !$NewPath ) {
    return "New path ($NewPath) is empty.";
  } elsif ( ! -e $NewPath ) {
    return "New path $NewPath does not exist.";
  }

  $ZoneMinder::Database::dbh->begin_work();
  $self->lock_and_load();
  # data is reloaded, so need to check that the move hasn't already happened.
  if ( $$self{StorageId} == $$NewStorage{Id} ) {
    $ZoneMinder::Database::dbh->commit();
    return "Event has already been moved by someone else.";
  }

  if ( $$OldStorage{Id} != $$self{StorageId} ) {
    $ZoneMinder::Database::dbh->commit();
    return 'Old Storage path changed, Event has moved somewhere else.';
  }

  $$self{Storage} = $NewStorage;
  ( $NewPath ) = ( $self->Path(undef) =~ /^(.*)$/ ); # De-taint
  if ( $NewPath eq $OldPath ) {
    $ZoneMinder::Database::dbh->commit();
    return "New path and old path are the same! $NewPath";
  }
  Debug("Moving event $$self{Id} from $OldPath to $NewPath");

  my $moved = 0;

  if ( $$NewStorage{Type} eq 's3fs' ) {
    my ( $aws_id, $aws_secret, $aws_host, $aws_bucket ) = ( $$NewStorage{Url} =~ /^\s*([^:]+):([^@]+)@([^\/]*)\/(.+)\s*$/ );
    eval {
      require Net::Amazon::S3;
      require File::Slurp;
      my $s3 = Net::Amazon::S3->new( {
          aws_access_key_id     => $aws_id,
          aws_secret_access_key => $aws_secret,
          ( $aws_host ? ( host => $aws_host ) : () ),
          });
      my $bucket = $s3->bucket($aws_bucket);
      if ( ! $bucket ) {
        Error("S3 bucket $bucket not found.");
        die;
      }

      my $event_path = 'events/'.$self->RelativePath();
Info("Making dir ectory $event_path/");
      if ( ! $bucket->add_key( $event_path.'/','' ) ) {
        die "Unable to add key for $event_path/";
      }

      my @files = glob("$OldPath/*");
Debug("Files to move @files");
      for my $file (@files) {
        next if $file =~ /^\./;
        ( $file ) = ( $file =~ /^(.*)$/ ); # De-taint
         my $starttime = time;
        Debug("Moving file $file to $NewPath");
        my $size = -s $file;
        if ( ! $size ) {
          Info('Not moving file with 0 size');
        }
        my $file_contents = File::Slurp::read_file($file);
        if ( ! $file_contents ) {
          die 'Loaded empty file, but it had a size. Giving up';
        }

        my $filename = $event_path.'/'.File::Basename::basename($file);
        if ( ! $bucket->add_key( $filename, $file_contents ) ) {
          die "Unable to add key for $filename";
        }
        my $duration = time - $starttime;
        Debug('PUT to S3 ' . Number::Bytes::Human::format_bytes($size) . " in $duration seconds = " . Number::Bytes::Human::format_bytes($duration?$size/$duration:$size) . '/sec');
      } # end foreach file.

      $moved = 1;
    };
    Error($@) if $@;
    die $@ if $@;
  } # end if s3

  my $error = '';
  if ( ! $moved ) {
    File::Path::make_path( $NewPath, {error => \my $err} );
    if ( @$err ) {
      for my $diag (@$err) {
        my ($file, $message) = %$diag;
        next if $message eq 'File exists';
        if ($file eq '') {
          $error .= "general error: $message\n";
        } else {
          $error .= "problem making $file: $message\n";
        }
      }
    }
    if ( $error ) {
      $ZoneMinder::Database::dbh->commit();
      return $error;
    }
    my @files = glob("$OldPath/*");
    if ( ! @files ) {
      $ZoneMinder::Database::dbh->commit();
      return "No files to move.";
    }

    for my $file (@files) {
      next if $file =~ /^\./;
      ( $file ) = ( $file =~ /^(.*)$/ ); # De-taint
      my $starttime = time;
      Debug("Moving file $file to $NewPath");
      my $size = -s $file;
      if ( ! File::Copy::copy( $file, $NewPath ) ) {
        $error .= "Copy failed: for $file to $NewPath: $!";
        last;
      }
      my $duration = time - $starttime;
      Debug("Copied " . Number::Bytes::Human::format_bytes($size) . " in $duration seconds = " . ($duration?Number::Bytes::Human::format_bytes($size/$duration):'inf') . "/sec");
    } # end foreach file.
  } # end if ! moved

  if ( $error ) {
    $ZoneMinder::Database::dbh->commit();
    return $error;
  }

  # Succeeded in copying all files, so we may now update the Event.
  $$self{StorageId} = $$NewStorage{Id};
  $$self{Storage} = $NewStorage;
  $error .= $self->save();
  if ( $error ) {
    $ZoneMinder::Database::dbh->commit();
    return $error;
  }
Debug("Committing");
  $ZoneMinder::Database::dbh->commit();
  $self->delete_files( $OldStorage );
Debug("Done deleting files, returning");
  return $error;
} # end sub MoveTo

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
