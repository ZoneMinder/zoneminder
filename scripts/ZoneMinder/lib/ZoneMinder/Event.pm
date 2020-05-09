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
require ZoneMinder::Frame;
require Date::Manip;
require File::Find;
require File::Path;
require File::Copy;
require File::Basename;
require Number::Bytes::Human;
require Date::Parse;
require POSIX;
use Date::Format qw(time2str);
use Time::HiRes qw(gettimeofday tv_interval);

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

use vars qw/ $table $primary_key %fields $serial @identified_by %defaults $debug/;
$debug = 0;
$table = 'Events';
@identified_by = ('Id');
$serial = $primary_key = 'Id';
%fields = map { $_, $_ } qw(
  Id
  MonitorId
  StorageId
  SecondaryStorageId
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
  SaveJPEGs
  Scheme
);
%defaults = (
  Cause =>  q`'Unknown'`,
  DefaultVideo  => q`''`,
  TotScore => '0',
  Archived  =>  '0',
  Videoed  =>  '0',
  Uploaded  =>  '0',
  Emailed   =>  '0',
  Messaged  =>  '0',
  Executed  =>  '0',
);


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

sub getPath {
  return Path(@_);
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
		if ( (!$$event{StorageId}) or defined $Storage->Id() ) {
			$$event{Path} = join('/', $Storage->Path(), $event->RelativePath());
		} else {
			Error("Storage area for $$event{StorageId} no longer exists in db.");
		}
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

  $$event{RelativePath} = shift if @_;

  if ( ! $$event{RelativePath} ) {
    if ( $$event{Scheme} eq 'Deep' ) {
      if ( $event->Time() ) {
        $$event{RelativePath} = join('/',
            $event->{MonitorId},
            POSIX::strftime(
              '%y/%m/%d/%H/%M/%S',
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
            POSIX::strftime('%Y-%m-%d', localtime($event->Time())),
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

  $$event{LinkPath} = shift if @_;

  if ( ! $$event{LinkPath} ) {
    if ( $$event{Scheme} eq 'Deep' ) {
      if ( $event->Time() ) {
        $$event{LinkPath} = join('/',
            $event->{MonitorId},
            POSIX::strftime(
              '%y/%m/%d',
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

  my $event_path = $self->Path();
  chdir($event_path);
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
    push @file_parts, $file_scale;
  } elsif ( $size ) {
    my $file_size = 'S'.$size;
    push @file_parts, $file_size;
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
    Info("Deleting event $event->{Id} from Monitor $event->{MonitorId} StartTime:$event->{StartTime} from ".$event->Path());
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

  foreach my $Storage (
    @_ ? ($_[0]) : (
      new ZoneMinder::Storage($$event{StorageId}),
      ( $$event{SecondaryStorageId} ? new ZoneMinder::Storage($$event{SecondaryStorageId}) : () ),
    ) ) {
    my $storage_path = $Storage->Path();

    if ( !$storage_path ) {
      Error("Empty storage path when deleting files for event $$event{Id} with storage id $$event{StorageId}");
      return;
    }

    if ( !$$event{MonitorId} ) {
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
        my $url = $$Storage{Url};
        $url =~ s/^(s3|s3fs):\/\///ig;
        my ( $aws_id, $aws_secret, $aws_host, $aws_bucket, $subpath ) = ( $url =~ /^\s*([^:]+):([^@]+)@([^\/]*)\/([^\/]+)(\/.+)?\s*$/ );
        Debug("S3 url parsed to id:$aws_id secret:$aws_secret host:$aws_host, bucket:$aws_bucket, subpath:$subpath\n from $url");
        eval {
          require Net::Amazon::S3;
          my $s3 = Net::Amazon::S3->new( {
              aws_access_key_id     => $aws_id,
              aws_secret_access_key => $aws_secret,
              ( $aws_host ? ( host => $aws_host ) : () ),
              authorization_method => 'Net::Amazon::S3::Signature::V4',
            });
          my $bucket = $s3->bucket($aws_bucket);
          if ( ! $bucket ) {
            Error("S3 bucket $bucket not found.");
            die;
          }
          if ( $bucket->delete_key($subpath.$event_path) ) {
            $deleted = 1;
          } else {
            Error('Failed to delete from S3:'.$s3->err . ': ' . $s3->errstr);
          }
        };
        Error($@) if $@;
      } # end if s3fs
      if ( !$deleted ) {
        my $command = "/bin/rm -rf $storage_path/$event_path";
        ZoneMinder::General::executeShellCommand($command);
      }
    } else {
      Error('No event path in delete files. ' . $event->to_string());
    } # end if event_path

    if ( $event->Scheme() eq 'Deep' ) {
      my $link_path = $event->LinkPath();
      Debug("Deleting link for Event $$event{Id} from $storage_path/$link_path.");
      if ( $link_path ) {
        ( $link_path ) = ( $link_path =~ /^(.*)$/ ); # De-taint
        unlink($storage_path.'/'.$link_path) or Error("Unable to unlink '$storage_path/$link_path': $!");
      }
    } # end if Scheme eq Deep

    # Now check for empty directories and delete them.
    my @path_parts = split('/', $event_path);
    pop @path_parts;
    # Guaranteed the first part is the monitor id
    Debug("Initial path_parts: @path_parts");
    while ( @path_parts > 1 ) {
      my $path = join('/', $storage_path, @path_parts);
      my $dh;
      if ( !opendir($dh, $path) ) {
        Warning("Fail to open $path");
        last;
      }
      my @dir =  readdir($dh);
      closedir($dh);
      if ( scalar(grep { $_ ne '.' and $_ ne '..' } @dir) == 0 ) {
        Debug("Removing empty dir at $path");
        if ( !rmdir $path ) {
          Warning("Fail to rmdir $path: $!");
          last;
        }
      } else {
        Debug("Dir $path is not empty @dir");
        last;
      }
      pop @path_parts;
    } # end while path_parts

  } # end foreach Storage
} # end sub delete_files

sub StorageId {
  my $event = shift;
  if ( @_ ) {
    $$event{StorageId} = shift;
    delete $$event{Storage};
    $event->Path(undef);
  }
  return $$event{StorageId};
}

sub Storage {
  if ( @_ > 1 ) {
    $_[0]{Storage} = $_[1];
    if ( $_[0]{Storage} ) {
      $_[0]{StorageId} = $_[0]{Storage}->Id();
      $_[0]->Path(undef);
    }
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

sub CopyTo {
  my ( $self, $NewStorage ) = @_;

  my $OldStorage = $self->Storage(undef);
  my ( $OldPath ) = ( $self->Path() =~ /^(.*)$/ ); # De-taint
  if ( ! -e $OldPath ) {
    return "Src path $OldPath does not exist.";
  }
  # First determine if we can move it to the dest.
  # We do this before bothering to lock the event
  my ( $NewPath ) = ( $NewStorage->Path() =~ /^(.*)$/ ); # De-taint
  if ( ! $$NewStorage{Id} ) {
    return 'New storage does not have an id.  Moving will not happen.';
  } elsif ( $$NewStorage{Id} == $$self{StorageId} ) {
    return 'Event is already located at ' . $NewPath;
  } elsif ( !$NewPath ) {
    return "New path ($NewPath) is empty.";
  } elsif ( ($$NewStorage{Type} ne 's3fs' ) and ! -e $NewPath ) {
    if ( ! mkdir($NewPath) ) {
      return "New path $NewPath does not exist.";
    }
  } else {
    Debug("$NewPath is good");
  }

  $ZoneMinder::Database::dbh->begin_work();
  $self->lock_and_load();
  # data is reloaded, so need to check that the move hasn't already happened.
  if ( $$self{StorageId} == $$NewStorage{Id} ) {
    $ZoneMinder::Database::dbh->commit();
    return 'Event has already been moved by someone else.';
  }

  if ( $$OldStorage{Id} != $$self{StorageId} ) {
    $ZoneMinder::Database::dbh->commit();
    return 'Old Storage path changed, Event has moved somewhere else.';
  }

  Debug("Relative Path: " . $self->RelativePath());
	$NewPath .= '/'.$self->RelativePath();
	($NewPath) = ( $NewPath =~ /^(.*)$/ ); # De-taint
  if ( $NewPath eq $OldPath ) {
    $ZoneMinder::Database::dbh->commit();
    return "New path and old path are the same! $NewPath";
  }
  Debug("Copying event $$self{Id} from $OldPath to $NewPath");

  my $moved = 0;

  if ( $$NewStorage{Type} eq 's3fs' ) {
    if ( $$NewStorage{Url} ) {
      my $url = $$NewStorage{Url};
      $url =~ s/^(s3|s3fs):\/\///ig;
      my ( $aws_id, $aws_secret, $aws_host, $aws_bucket, $subpath ) = ( $url =~ /^\s*([^:]+):([^@]+)@([^\/]*)\/([^\/]+)(\/.+)?\s*$/ );
      Debug("S3 url parsed to id:$aws_id secret:$aws_secret host:$aws_host, bucket:$aws_bucket, subpath:$subpath\n from $url");
      if ( $aws_id and $aws_secret and $aws_host and $aws_bucket ) {
        eval {
          require Net::Amazon::S3;
          require File::Slurp;
          my $s3 = Net::Amazon::S3->new( {
              aws_access_key_id     => $aws_id,
              aws_secret_access_key => $aws_secret,
              ( $aws_host ? ( host => $aws_host ) : () ),
              authorization_method => 'Net::Amazon::S3::Signature::V4',
            });
          my $bucket = $s3->bucket($aws_bucket);
          if ( !$bucket ) {
            Error("S3 bucket $bucket not found.");
            die;
          }

          my $event_path = $subpath.$self->RelativePath();
          if ( 0 ) { # Not neccessary
            Debug("Making directory $event_path/");
            if ( !$bucket->add_key($event_path.'/', '') ) {
              Warning("Unable to add key for $event_path/ :". $s3->err . ': '. $s3->errstr());
            }
          }

          my @files = glob("$OldPath/*");
          Debug("Files to move @files");
          foreach my $file ( @files ) {
            next if $file =~ /^\./;
            ( $file ) = ( $file =~ /^(.*)$/ ); # De-taint
            my $starttime = [gettimeofday];
            Debug("Moving file $file to $NewPath");
            my $size = -s $file;
            if ( ! $size ) {
              Info('Not moving file with 0 size');
            }
            if ( 0 ) {
              my $file_contents = File::Slurp::read_file($file);
              if ( ! $file_contents ) {
                die 'Loaded empty file, but it had a size. Giving up';
              }

              my $filename = $event_path.'/'.File::Basename::basename($file);
              if ( ! $bucket->add_key($filename, $file_contents) ) {
                die "Unable to add key for $filename : ".$s3->err . ': ' . $s3->errstr;
              }
            } else {
              my $filename = $event_path.'/'.File::Basename::basename($file);
              if ( ! $bucket->add_key_filename($filename, $file) ) {
                die "Unable to add key for $filename " . $s3->err . ': '. $s3->errstr;
              }
            }

            my $duration = tv_interval($starttime);
            Debug('PUT to S3 ' . Number::Bytes::Human::format_bytes($size) . " in $duration seconds = " . Number::Bytes::Human::format_bytes($duration?$size/$duration:$size) . '/sec');
          } # end foreach file.

          $moved = 1;
        };
        Error($@) if $@;
      } else {
        Error("Unable to parse S3 Url into it's component parts.");
      }
      #die $@ if $@;
    } # end if Url
  } # end if s3

  my $error = '';
  if ( !$moved ) {
    File::Path::make_path($NewPath, {error => \my $err});
    if ( @$err ) {
      for my $diag (@$err) {
        my ($file, $message) = %$diag;
        next if $message eq 'File exists';
        if ( $file eq '' ) {
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
      return 'No files to move.';
    }

    for my $file (@files) {
      next if $file =~ /^\./;
      ( $file ) = ( $file =~ /^(.*)$/ ); # De-taint
      my $starttime = [gettimeofday];
      Debug("Moving file $file to $NewPath");
      my $size = -s $file;
      if ( ! File::Copy::copy( $file, $NewPath ) ) {
        $error .= "Copy failed: for $file to $NewPath: $!";
        last;
      }
      my $duration = tv_interval($starttime);
      Debug('Copied ' . Number::Bytes::Human::format_bytes($size) . " in $duration seconds = " . ($duration?Number::Bytes::Human::format_bytes($size/$duration):'inf') . '/sec');
    } # end foreach file.
  } # end if ! moved

  if ( $error ) {
    $ZoneMinder::Database::dbh->commit();
    return $error;
  }
} # end sub CopyTo

sub MoveTo {

  my ( $self, $NewStorage ) = @_;
  my $OldStorage = $self->Storage(undef);

  my $error = $self->CopyTo($NewStorage);
  return $error if $error;

  # Succeeded in copying all files, so we may now update the Event.
  $$self{StorageId} = $$NewStorage{Id};
  $self->Storage($NewStorage);
  $error .= $self->save();
  if ( $error ) {
    $ZoneMinder::Database::dbh->commit();
    return $error;
  }
  $ZoneMinder::Database::dbh->commit();
  $self->delete_files($OldStorage);
  return $error;
} # end sub MoveTo

# Assumes $path is absolute
#
sub recover_timestamps {
  my ( $Event, $path ) = @_;
  $path = $Event->Path() if ! $path;

  if ( !opendir(DIR, $path) ) {
    Error("Can't open directory '$path': $!");
    return;
  }
  my @contents = readdir(DIR);
  Debug('Have ' . @contents . " files in $path");
  closedir(DIR);

  my @mp4_files = grep( /^\d+\-video\.mp4$/, @contents);
  if ( @mp4_files ) {
    $$Event{DefaultVideo} = $mp4_files[0];
  }

  my @analyse_jpgs = grep( /^\d+\-analyse\.jpg$/, @contents);
  if ( @analyse_jpgs ) {
    $$Event{Save_JPEGs} |= 2;
  }

  my @capture_jpgs = grep( /^\d+\-capture\.jpg$/, @contents);
  if ( @capture_jpgs ) {
    $$Event{Frames} = scalar @capture_jpgs;
    $$Event{Save_JPEGs} |= 1;
    # can get start and end times from stat'ing first and last jpg
    @capture_jpgs = sort { $a cmp $b } @capture_jpgs;
    my $first_file = "$path/$capture_jpgs[0]";
    ( $first_file ) = $first_file =~ /^(.*)$/;
    my $first_timestamp = (stat($first_file))[9];

    my $last_file = "$path/$capture_jpgs[@capture_jpgs-1]";
    ( $last_file ) = $last_file =~ /^(.*)$/;
    my $last_timestamp = (stat($last_file))[9];

    my $duration = $last_timestamp - $first_timestamp;
    $Event->Length($duration);
    $Event->StartTime( Date::Format::time2str('%Y-%m-%d %H:%M:%S', $first_timestamp) );
    $Event->EndTime( Date::Format::time2str('%Y-%m-%d %H:%M:%S', $last_timestamp) );
    Debug("From capture Jpegs have duration $duration = $last_timestamp - $first_timestamp : $$Event{StartTime} to $$Event{EndTime}");
    $ZoneMinder::Database::dbh->begin_work();
    foreach my $jpg ( @capture_jpgs ) {
      my ( $id ) = $jpg =~ /^(\d+)\-capture\.jpg$/;

      if ( ! ZoneMinder::Frame->find_one( EventId=>$$Event{Id}, FrameId=>$id ) ) {
        my $file = "$path/$jpg";
        ( $file ) = $file =~ /^(.*)$/;
        my $timestamp = (stat($file))[9];
        my $Frame = new ZoneMinder::Frame();
        $Frame->save({
            EventId=>$$Event{Id}, FrameId=>$id,
            TimeStamp=>Date::Format::time2str('%Y-%m-%d %H:%M:%S',$timestamp),
            Delta => $timestamp - $first_timestamp,
            Type=>'Normal',
            Score=>0,
          });
      }
    }
    $ZoneMinder::Database::dbh->commit();
  } elsif ( @mp4_files ) {
    my $file = "$path/$mp4_files[0]";
    ( $file ) = $file =~ /^(.*)$/;

    my $first_timestamp = (stat($file))[9];
    my $output = `ffprobe $file 2>&1`;
    my ($duration) = $output =~ /Duration: [:\.0-9]+/gm;
    Debug("From mp4 have duration $duration, start: $first_timestamp");

    my ( $h, $m, $s, $u );
      if ( $duration =~ m/(\d+):(\d+):(\d+)\.(\d+)/ ) {
        ( $h, $m, $s, $u ) = ($1, $2, $3, $4 );
        Debug("( $h, $m, $s, $u ) from /^(\\d{2}):(\\d{2}):(\\d{2})\.(\\d+)/");
      }
    my $seconds = ($h*60*60)+($m*60)+$s;
    $Event->Length($seconds.'.'.$u);
    $Event->StartTime( Date::Format::time2str('%Y-%m-%d %H:%M:%S', $first_timestamp) );
    $Event->EndTime( Date::Format::time2str('%Y-%m-%d %H:%M:%S', $first_timestamp+$seconds) );
  }
  if ( @mp4_files ) {
    $Event->DefaultVideo($mp4_files[0]);
  }
}

sub files {
	my $self = shift;

	if ( ! $$self{files} ) {
		if ( ! opendir(DIR, $self->Path() ) ) {
			Error("Can't open directory '$$self{Path}': $!");
			return;
		}
		@{$$self{files}} = readdir(DIR);
		Debug('Have ' . @{$$self{files}} . " files in $$self{Path}");
		closedir(DIR);
	}
	return @{$$self{files}};
}

sub has_capture_jpegs {
	@{$_[0]{capture_jpegs}} = grep(/^\d+\-capture\.jpg$/, $_[0]->files());
	Debug("have " . @{$_[0]{capture_jpegs}} . " capture jpegs");
	return @{$_[0]{capture_jpegs}} ? 1 : 0;
}

sub has_analyse_jpegs {
	@{$_[0]{analyse_jpegs}} = grep(/^\d+\-analyse\.jpg$/, $_[0]->files());
	Debug("have " . @{$_[0]{analyse_jpegs}} . " analyse jpegs");
	return @{$_[0]{analyse_jpegs}} ? 1 : 0;
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
