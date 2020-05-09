package ZoneMinder::General;

use 5.006;
use strict;
use warnings;

require Exporter;
require ZoneMinder::Base;
require ZoneMinder::Storage;

our @ISA = qw(Exporter ZoneMinder::Base);

# Items to export into callers namespace by default. Note: do not export
# names by default without a very good reason. Use EXPORT_OK instead.
# Do not simply export all your public functions/methods/constants.

# This allows declaration   use ZoneMinder ':all';
# If you do not need this, moving things directly into @EXPORT or @EXPORT_OK
# will save memory.
our %EXPORT_TAGS = (
    functions => [ qw(
      executeShellCommand
      getCmdFormat
      runCommand
      setFileOwner
      createEventPath
      createEvent
      makePath
      jsonEncode
      jsonDecode
      ) ]
    );
push( @{$EXPORT_TAGS{all}}, @{$EXPORT_TAGS{$_}} ) foreach keys %EXPORT_TAGS;

our @EXPORT_OK = ( @{ $EXPORT_TAGS{all} } );

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

# For running general shell commands
sub executeShellCommand {
  my $command = shift;
  my $output = qx($command);
  my $status = $? >> 8;
  if ( $status || logDebugging() ) {
    $output = '' if !defined($output);
    chomp($output);
    Debug("Command: $command Output: $output");
  }
  return $status;
}

sub getCmdFormat {
  Debug('Testing valid shell syntax');

  my ( $name ) = getpwuid( $> );
  if ( $name eq $Config{ZM_WEB_USER} ) {
    Debug("Running as '$name', su commands not needed");
    return '';
  }

  my $null_command = 'true';

  my $prefix = 'sudo -u '.$Config{ZM_WEB_USER}.' ';
  my $suffix = '';
  my $command = $prefix.$null_command.$suffix;
  Debug("Testing \"$command\"");
  my $output = qx($command 2>&1);
  my $status = $? >> 8;
  $output //= $!;
  
  if ( !$status ) {
    Debug("Test ok, using format \"$prefix<command>$suffix\"");
    return( $prefix, $suffix );
  } else {
    chomp( $output );
    Debug("Test failed, '$output'");

    $prefix = 'su '.$Config{ZM_WEB_USER}.q` --shell=/bin/sh --command='`;
    $suffix = q`'`;
    $command = $prefix.$null_command.$suffix;
    Debug("Testing \"$command\"");
    my $output = qx($command 2>&1);
    my $status = $? >> 8;
    $output //= $!;
    
    if ( !$status ) {
      Debug("Test ok, using format \"$prefix<command>$suffix\"");
      return( $prefix, $suffix );
    } else {
      chomp($output);
      Debug("Test failed, '$output'");

      $prefix = 'su '.$Config{ZM_WEB_USER}.' -c \'';
      $suffix = '\'';
      $command = $prefix.$null_command.$suffix;
      Debug("Testing \"$command\"");
      $output = qx($command 2>&1);
      $status = $? >> 8;
      $output //= $!;
      
      if ( !$status ) {
        Debug("Test ok, using format \"$prefix<command>$suffix\"");
        return( $prefix, $suffix );
      } else {
        chomp($output);
        Debug("Test failed, '$output'");
      }
    }
  }
  Error('Unable to find valid su syntax');
  exit -1;
} # end sub getCmdFormat

our $testedShellSyntax = 0;
our ( $cmdPrefix, $cmdSuffix );

# For running ZM daemons etc
sub runCommand {
  if ( !$testedShellSyntax ) {
# Determine the appropriate syntax for the su command
    ( $cmdPrefix, $cmdSuffix ) = getCmdFormat();
    $testedShellSyntax = !undef;
  }

  my $command = shift;
  $command = $Config{ZM_PATH_BIN}.'/'.$command;
  if ( $cmdPrefix ) {
    $command = $cmdPrefix.$command.$cmdSuffix;
  }
  Debug("Command: $command");
  my $output = qx($command);
  my $status = $? >> 8;
  chomp($output);
  if ( $status || logDebugging() ) {
    if ( $status ) {
      Error("Unable to run \"$command\", output is \"$output\", status is $status");
    } else {
      Debug("Output: $output");
    }
  }
  return $output;
} # end sub runCommand

sub createEventPath {
  my $event = shift;
  my $eventPath = $event->Path();
  $event->createPath();
  $event->createIdFile();
  $event->createLinkPath();

  return $eventPath;
}

use Data::Dumper;

our $_setFileOwner = undef;
our ( $_ownerUid, $_ownerGid );

sub _checkProcessOwner {
  if ( !defined($_setFileOwner) ) {
    my ( $processOwner ) = getpwuid( $> );
    if ( $processOwner ne $Config{ZM_WEB_USER} ) {
# Not running as web user, so should be root in which case chown
# the temporary directory
      ( my $ownerName, my $ownerPass, $_ownerUid, $_ownerGid )
        = getpwnam( $Config{ZM_WEB_USER} )
        or Fatal( "Can't get user details for web user '"
            .$Config{ZM_WEB_USER}."': $!"
            );
      $_setFileOwner = 1;
    } else {
      $_setFileOwner = 0;
    }
  }
  return $_setFileOwner;
}

sub setFileOwner {
  my $file = shift;

  if ( _checkProcessOwner() ) {
    chown($_ownerUid, $_ownerGid, $file)
      or Fatal("Can't change ownership of file '$file' to '"
          .$Config{ZM_WEB_USER}.':'.$Config{ZM_WEB_GROUP}."': $!"
          );
  }
}

our $_hasImageInfo = undef;

sub _checkForImageInfo {
  if ( !defined($_hasImageInfo) ) {
    my $result = eval {
      require Image::Info;
      Image::Info->import();
    };
    $_hasImageInfo = $@?0:1;
  }
  return $_hasImageInfo;
}

sub createEvent {
  my $event = shift;

  Debug('Creating event');
#print( Dumper( $event )."\n" );

  _checkForImageInfo();

  my $dbh = zmDbConnect();

  if ( $event->{monitor} ) {
    $event->{MonitorId} = $event->{monitor}->{Id};
  } elsif ( $event->{MonitorId} ) {
    my $sql = "select * from Monitors where Id = ?";
    my $sth = $dbh->prepare_cached( $sql )
      or Fatal( "Can't prepare sql '$sql': ".$dbh->errstr() );
    my $res = $sth->execute( $event->{MonitorId} )
      or Fatal( "Can't execute sql '$sql': ".$sth->errstr() );
    $event->{monitor} = $sth->fetchrow_hashref()
      or Fatal( "Unable to create event, can't load monitor with id '"
          .$event->{MonitorId}."'"
          );
    $sth->finish();
  } else {
    Fatal( "Unable to create event, no monitor or monitor id supplied" );
  }
  $event->{Name} = "New Event" unless( $event->{Name} );
  $event->{Frames} = int(@{$event->{frames}});
  $event->{TotScore} = $event->{MaxScore} = 0;

  my $lastTimestamp = 0.0;
  foreach my $frame ( @{$event->{frames}} ) {
    if ( !$event->{Width} ) {
      if ( $_hasImageInfo ) {
        my $imageInfo = Image::Info::image_info( $frame->{imagePath} );
        if ( $imageInfo->{error} ) {
          Error( "Unable to extract image info from '"
              .$frame->{imagePath}."': ".$imageInfo->{error}
              );
        } else {
          ( $event->{Width}, $event->{Height} ) = Image::Info::dim( $imageInfo );
        }
      }
    }
    $frame->{Type} = $frame->{Score}>0?'Alarm':'Normal' unless( $frame->{Type} );
    $frame->{Delta} = $lastTimestamp?($frame->{TimeStamp}-$lastTimestamp):0.0;
    $event->{StartTime} = $frame->{TimeStamp} unless ( $event->{StartTime} );
    $event->{TotScore} += $frame->{Score};
    $event->{MaxScore} = $frame->{Score} if ( $frame->{Score} > $event->{MaxScore} );
    $event->{AlarmFrames}++ if ( $frame->{Type} eq 'Alarm' );
    $event->{EndTime} = $frame->{TimeStamp};
    $lastTimestamp = $frame->{TimeStamp};
  }
  $event->{Width} = $event->{monitor}->{Width} unless( $event->{Width} );
  $event->{Height} = $event->{monitor}->{Height} unless( $event->{Height} );
  $event->{AvgScore} = $event->{TotScore}/int($event->{AlarmFrames});
  $event->{Length} = $event->{EndTime} - $event->{StartTime};

  my %formats = (
      StartTime => 'from_unixtime(?)',
      EndTime => 'from_unixtime(?)',
      );

  my ( @fields, @formats, @values );
  while ( my ( $field, $value ) = each( %$event ) ) {
    next unless $field =~ /^[A-Z]/;
    push( @fields, $field );
    push( @formats, ($formats{$field} or '?') );
    push( @values, $event->{$field} );
  }

  my $sql = "INSERT INTO Events (".join(',',@fields)
    .") VALUES (".join(',',@formats).")"
    ;
  my $sth = $dbh->prepare_cached( $sql )
    or Fatal( "Can't prepare sql '$sql': ".$dbh->errstr() );
  my $res = $sth->execute( @values )
    or Fatal( "Can't execute sql '$sql': ".$sth->errstr() );
  $event->{Id} = $dbh->{mysql_insertid};
  Info( "Created event ".$event->{Id} );

  if ( $event->{EndTime} ) {
    $event->{Name} = $event->{monitor}->{EventPrefix}.$event->{Id}
    if ( $event->{Name} eq 'New Event' );
    my $sql = "update Events set Name = ? where Id = ?";
    my $sth = $dbh->prepare_cached( $sql )
      or Fatal( "Can't prepare sql '$sql': ".$dbh->errstr() );
    my $res = $sth->execute( $event->{Name}, $event->{Id} )
      or Fatal( "Can't execute sql '$sql': ".$sth->errstr() );
  }

  my $eventPath = createEventPath( $event );

  my %frameFormats = (
      TimeStamp => 'from_unixtime(?)',
      );
  my $frameId = 1;
  foreach my $frame ( @{$event->{frames}} ) {
    $frame->{EventId} = $event->{Id};
    $frame->{FrameId} = $frameId++;

    my ( @fields, @formats, @values );
    while ( my ( $field, $value ) = each( %$frame ) ) {
      next unless $field =~ /^[A-Z]/;
      push( @fields, $field );
      push( @formats, ($frameFormats{$field} or '?') );
      push( @values, $frame->{$field} );
    }

    my $sql = "insert into Frames (".join(',',@fields)
      .") values (".join(',',@formats).")"
      ;
    my $sth = $dbh->prepare_cached( $sql )
      or Fatal( "Can't prepare sql '$sql': ".$dbh->errstr() );
    my $res = $sth->execute( @values )
      or Fatal( "Can't execute sql '$sql': ".$sth->errstr() );
#$frame->{FrameId} = $dbh->{mysql_insertid};
    if ( $frame->{imagePath} ) {
      $frame->{capturePath} = sprintf(
          "%s/%0".$Config{ZM_EVENT_IMAGE_DIGITS}
          ."d-capture.jpg"
          , $eventPath
          , $frame->{FrameId}
          );
      rename( $frame->{imagePath}, $frame->{capturePath} )
        or Fatal( "Can't copy ".$frame->{imagePath}
            ." to ".$frame->{capturePath}.": $!"
            );
      setFileOwner( $frame->{capturePath} );
      if ( 0 && $Config{ZM_CREATE_ANALYSIS_IMAGES} ) {
        $frame->{analysePath} = sprintf(
            "%s/%0".$Config{ZM_EVENT_IMAGE_DIGITS}
            ."d-analyse.jpg"
            , $eventPath
            , $frame->{FrameId}
            );
        link( $frame->{capturePath}, $frame->{analysePath} )
          or Fatal( "Can't link ".$frame->{capturePath}
              ." to ".$frame->{analysePath}.": $!"
              );
        setFileOwner( $frame->{analysePath} );
      }
    }
  }
}

sub addEventImage {
  my $event = shift;
  my $frame = shift;

# TBD
}

sub updateEvent {
  my $event = shift;

  if ( !$event->{EventId} ) {
    Error( "Unable to update event, no event id supplied" );
    return( 0 );
  }

  my $dbh = zmDbConnect();

  $event->{Name} = $event->{monitor}->{EventPrefix}.$event->{Id}
  if ( $event->{Name} eq 'New Event' );

  my %formats = (
      StartTime => 'from_unixtime(?)',
      EndTime => 'from_unixtime(?)',
      );

  my ( @values, @sets );
  while ( my ( $field, $value ) = each( %$event ) ) {
    next if ( $field eq 'Id' );
    push( @values, $event->{$field} );
    push( @sets, $field." = ".($formats{$field} or '?') );
  }
  my $sql = "update Events set ".join(',',@sets)." where Id = ?";
  push( @values, $event->{Id} );

  my $sth = $dbh->prepare_cached( $sql )
    or Fatal( "Can't prepare sql '$sql': ".$dbh->errstr() );
  my $res = $sth->execute( @values )
    or Fatal( "Can't execute sql '$sql': ".$sth->errstr() );
}

sub makePath {
  my $path = shift;
  my $root = shift;
  $root = (( $path =~ m|^/| )?'':'.' ) unless( $root );

  Debug( "Creating path '$path' in $root'\n" );
  my @parts = split( '/', $path );
  my $fullPath = $root;
  foreach my $dir ( @parts ) {
    $fullPath .= '/'.$dir;
    if ( !-d $fullPath ) {
      if ( -e $fullPath ) {
        Fatal( "Can't create '$fullPath', already exists as non directory" );
      } else {
        Debug( "Creating '$fullPath'\n" );
        mkdir( $fullPath, 0755 ) or Fatal( "Can't mkdir '$fullPath': $!" );
        setFileOwner( $fullPath );
      }
    }
  }
  return( $fullPath );
}

our $testedJSON = 0;
our $hasJSONAny = 0;

sub _testJSON {
  return if ( $testedJSON );
  my $result = eval {
    require JSON::MaybeXS;
    JSON::MaybeXS->import();
  };
  $testedJSON = 1;
  $hasJSONAny = 1 if ( $result );
}

sub _getJSONType {
  my $value = shift;
  return 'null' unless defined($value);
  return 'integer' if $value =~ /^\d+$/;
  return 'double' if $value =~ /^\d+$/;
  return 'hash' if ref($value) eq 'HASH';
  return 'array' if ref($value) eq 'ARRAY';
  return 'string';
}

sub jsonEncode;

sub jsonEncode {
  my $value = shift;

  _testJSON();
  if ( $hasJSONAny ) {
    my $string = eval { JSON::MaybeXS->encode_json( $value ) };
    Fatal( "Unable to encode object to JSON: $@" ) unless( $string );
    return( $string );
  }

  my $type = _getJSONType($value);
  if ( $type eq 'integer' || $type eq 'double' ) {
    return( $value );
  } elsif ( $type eq 'boolean' ) {
    return( $value?'true':'false' );
  } elsif ( $type eq 'string' ) {
    $value =~ s|(["\\/])|\\$1|g;
    $value =~ s|\r?\n|\n|g;
    return( '"'.$value.'"' );
  } elsif ( $type eq 'null' ) {
    return( 'null' );
  } elsif ( $type eq 'array' ) {
    return( '['.join( ',', map { jsonEncode( $_ ) } @$value ).']' );
  } elsif ( $type eq 'hash' ) {
    my $result = '{';
    while ( my ( $subKey=>$subValue ) = each( %$value ) ) {
      $result .= ',' if ( $result ne '{' );
      $result .= '"'.$subKey.'":'.jsonEncode( $subValue );
    }
    return( $result.'}' );
  } else {
    Fatal( "Unexpected type '$type'" );
  }
}

sub jsonDecode {
  my $value = shift;

  _testJSON();
  if ( $hasJSONAny ) {
    my $object = eval { JSON::MaybeXS->decode_json($value) };
    Fatal("Unable to decode JSON string '$value': $@") unless $object;
    return $object;
  }

  my $comment = 0;
  my $unescape = 0;
  my $out = '';
  my @chars = split(//, $value);
  for ( my $i = 0; $i < @chars; $i++ ) {
    if ( !$comment ) {
      if ( $chars[$i] eq ':' ) {
        $out .= '=>';
      } else {
        $out .= $chars[$i];         
      }
    } elsif ( !$unescape ) {
      if ( $chars[$i] eq '\\' ) {
        $unescape = 1;
      } else {
        $out .= $chars[$i];
      }
    } else {
      if ( $chars[$i] ne '/' ) {
        $out .= '\\';
      }
      $out .= $chars[$i];
      $unescape = 0;
    }
    if ( $chars[$i] eq '"' ) {
      $comment = !$comment;
    }
  }
  $out =~ s/=>true/=>1/g;
  $out =~ s/=>false/=>0/g;
  $out =~ s/=>null/=>undef/g;
  $out =~ s/`/'/g;
  $out =~ s/qx/qq/g;
  ( $out ) = $out =~ m/^(\{.+\})$/; # Detaint and check it's a valid object syntax
  my $result = eval $out;
  Fatal($@) if $@;
  return $result;
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::General - Utility Functions for ZoneMinder

=head1 SYNOPSIS

use ZoneMinder::General;
blah blah blah

=head1 DESCRIPTION

This module contains the common definitions and functions used by the rest
of the ZoneMinder scripts

=head2 EXPORT

    functions => [ qw(
      executeShellCommand
      getCmdFormat
      runCommand
      setFileOwner
      createEventPath
      createEvent
      makePath
      jsonEncode
      jsonDecode
      ) ]


=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

=cut
