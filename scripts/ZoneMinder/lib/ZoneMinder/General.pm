# ==========================================================================
#
# ZoneMinder General Utility Module, $Date$, $Revision$
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
package ZoneMinder::General;

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
    'functions' => [ qw(
        executeShellCommand
        getCmdFormat
        runCommand
        setFileOwner
        getEventPath
        createEventPath
        createEvent
        deleteEventFiles
        makePath
        jsonEncode
        jsonDecode
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

# For running general shell commands
sub executeShellCommand( $ )
{
    my $command = shift;
    my $output = qx( $command );
    my $status = $? >> 8;
    if ( $status || logDebugging() )
    {
        Debug( "Command: $command\n" );
        chomp( $output );
        Debug( "Output: $output\n" );
    }
    return( $status );
}

sub getCmdFormat()
{
    Debug( "Testing valid shell syntax\n" );

    my ( $name ) = getpwuid( $> );
    if ( $name eq $Config{ZM_WEB_USER} )
    {
        Debug( "Running as '$name', su commands not needed\n" );
        return( "" );
    }

    my $null_command = "true";

    my $prefix = "sudo -u ".$Config{ZM_WEB_USER}." ";
    my $suffix = "";
    my $command = $prefix.$null_command.$suffix;
    Debug( "Testing \"$command\"\n" );
    $command .= " > /dev/null 2>&1"; 
    my $output = qx($command);
    my $status = $? >> 8;
    if ( !$status )
    {
        Debug( "Test ok, using format \"$prefix<command>$suffix\"\n" );
        return( $prefix, $suffix );
    }
    else
    {
        chomp( $output );
        Debug( "Test failed, '$output'\n" );

        $prefix = "su ".$Config{ZM_WEB_USER}." --shell=/bin/sh --command='";
        $suffix = "'";
        $command = $prefix.$null_command.$suffix;
        Debug( "Testing \"$command\"\n" );
        my $output = qx($command);
        my $status = $? >> 8;
        if ( !$status )
        {
            Debug( "Test ok, using format \"$prefix<command>$suffix\"\n" );
            return( $prefix, $suffix );
        }
        else
        {
            chomp( $output );
            Debug( "Test failed, '$output'\n" );

            $prefix = "su ".$Config{ZM_WEB_USER}." -c '";
            $suffix = "'";
            $command = $prefix.$null_command.$suffix;
            Debug( "Testing \"$command\"\n" );
            $output = qx($command);
            $status = $? >> 8;
            if ( !$status )
            {
                Debug( "Test ok, using format \"$prefix<command>$suffix\"\n" );
                return( $prefix, $suffix );
            }
            else
            {
                chomp( $output );
                Debug( "Test failed, '$output'\n" );
            }
        }
    }
    Error( "Unable to find valid 'su' syntax\n" );
    exit( -1 );
}

our $testedShellSyntax = 0;
our ( $cmdPrefix, $cmdSuffix );

# For running ZM daemons etc
sub runCommand( $ )
{
    if ( !$testedShellSyntax )
    {
        # Determine the appropriate syntax for the su command
        ( $cmdPrefix, $cmdSuffix ) = getCmdFormat();
        $testedShellSyntax = !undef;
    }

    my $command = shift;
    $command = $Config{ZM_PATH_BIN}."/".$command;
    if ( $cmdPrefix )
    {
        $command = $cmdPrefix.$command.$cmdSuffix;
    }
    Debug( "Command: $command\n" );
    my $output = qx($command);
    my $status = $? >> 8;
    chomp( $output );
    if ( $status || logDebugging() )
    {
        if ( $status )
        {
            Error( "Unable to run \"$command\", output is \"$output\"\n" );
            exit( -1 );
        }
        else
        {
            Debug( "Output: $output\n" );
        }
    }
    return( $output );
}

sub getEventPath( $ )
{
    my $event = shift;

    my $event_path = "";
    if ( $Config{ZM_USE_DEEP_STORAGE} )
    {
        $event_path = $Config{ZM_DIR_EVENTS}.'/'.$event->{MonitorId}.'/'.strftime( "%y/%m/%d/%H/%M/%S", localtime($event->{Time}) );
    }
    else
    {
        $event_path = $Config{ZM_DIR_EVENTS}.'/'.$event->{MonitorId}.'/'.$event->{Id};
    }
    $event_path = $Config{ZM_PATH_WEB}.'/'.$event_path if ( index($Config{ZM_DIR_EVENTS},'/') != 0 );
    return( $event_path );
}

sub createEventPath( $ )
{
    #
    # WARNING assumes running from events directory
    #
    my $event = shift;
    my $eventRootPath = ($Config{ZM_DIR_EVENTS}=~m|/|)?$Config{ZM_DIR_EVENTS}:($Config{ZM_PATH_WEB}.'/'.$Config{ZM_DIR_EVENTS});
    my $eventPath = $eventRootPath.'/'.$event->{MonitorId};

    if ( $Config{ZM_USE_DEEP_STORAGE} )
    {
        my @startTime = localtime( $event->{StartTime} );

        my @datetimeParts = ();
        $datetimeParts[0] = sprintf( "%02d", $startTime[5]-100 );
        $datetimeParts[1] = sprintf( "%02d", $startTime[4]+1 );
        $datetimeParts[2] = sprintf( "%02d", $startTime[3] );
        $datetimeParts[3] = sprintf( "%02d", $startTime[2] );
        $datetimeParts[4] = sprintf( "%02d", $startTime[1] );
        $datetimeParts[5] = sprintf( "%02d", $startTime[0] );

        my $datePath = join('/',@datetimeParts[0..2]);
        my $timePath = join('/',@datetimeParts[3..5]);

        makePath( $datePath, $eventPath );
        $eventPath .= '/'.$datePath;

        # Create event id symlink
        my $idFile = sprintf( "%s/.%d", $eventPath, $event->{Id} );
        symlink( $timePath, $idFile ) or Fatal( "Can't symlink $idFile -> $eventPath: $!" );

        makePath( $timePath, $eventPath );
        $eventPath .= '/'.$timePath;
        setFileOwner( $idFile ); # Must come after directory has been created

        # Create empty id tag file
        $idFile = sprintf( "%s/.%d", $eventPath, $event->{Id} );
        open( ID_FP, ">$idFile" ) or Fatal( "Can't open $idFile: $!" );
        close( ID_FP );
        setFileOwner( $idFile );
    }
    else
    {
        makePath( $event->{Id}, $eventPath );
        $eventPath .= '/'.$event->{Id};

        my $idFile = sprintf( "%s/.%d", $eventPath, $event->{Id} );
        open( ID_FP, ">$idFile" ) or Fatal( "Can't open $idFile: $!" );
        close( ID_FP );
        setFileOwner( $idFile );
    }
    return( $eventPath );
}

use Data::Dumper;

our $_setFileOwner = undef;
our ( $_ownerUid, $_ownerGid );

sub _checkProcessOwner()
{
    if ( !defined($_setFileOwner) )
    {
        my ( $processOwner ) = getpwuid( $> );
        if ( $processOwner ne $Config{ZM_WEB_USER} )
        {
            # Not running as web user, so should be root in whch case chown the temporary directory
            ( my $ownerName, my $ownerPass, $_ownerUid, $_ownerGid ) = getpwnam( $Config{ZM_WEB_USER} ) or Fatal( "Can't get user details for web user '".$Config{ZM_WEB_USER}."': $!" );
            $_setFileOwner = 1;
        }
        else
        {
            $_setFileOwner = 0;
        }
    }
    return( $_setFileOwner );
}

sub setFileOwner( $ )
{
    my $file = shift;

    if ( _checkProcessOwner() )
    {
        chown( $_ownerUid, $_ownerGid, $file ) or Fatal( "Can't change ownership of file '$file' to '".$Config{ZM_WEB_USER}.":".$Config{ZM_WEB_GROUP}."': $!" );
    }
}

our $_hasImageInfo = undef;

sub _checkForImageInfo()
{
    if ( !defined($_hasImageInfo) )
    {
        my $result = eval
        {
            require Image::Info;
            Image::Info->import();
        };
        $_hasImageInfo = $@?0:1;
    }
    return( $_hasImageInfo );
}

sub createEvent( $;$ )
{
    my $event = shift;

    Debug( "Creating event" );
    #print( Dumper( $event )."\n" );

    _checkForImageInfo();

    my $dbh = zmDbConnect();

    if ( $event->{monitor} )
    {
        $event->{MonitorId} = $event->{monitor}->{Id};
    }
    elsif ( $event->{MonitorId} )
    {
        my $sql = "select * from Monitors where Id = ?";
        my $sth = $dbh->prepare_cached( $sql ) or Fatal( "Can't prepare sql '$sql': ".$dbh->errstr() );
        my $res = $sth->execute( $event->{MonitorId} ) or Fatal( "Can't execute sql '$sql': ".$sth->errstr() );
        $event->{monitor} = $sth->fetchrow_hashref() or Fatal( "Unable to create event, can't load monitor with id '".$event->{MonitorId}."'" );
        $sth->finish();
    }
    else
    {
        Fatal( "Unable to create event, no monitor or monitor id supplied" );
    }
    $event->{Name} = "New Event" unless( $event->{Name} );
    $event->{Frames} = int(@{$event->{frames}});
    $event->{TotScore} = $event->{MaxScore} = 0;

    my $lastTimestamp = 0.0;
    foreach my $frame ( @{$event->{frames}} )
    {
        if ( !$event->{Width} )
        {
            if ( $_hasImageInfo )
            {
                my $imageInfo = Image::Info::image_info( $frame->{imagePath} );
                if ( $imageInfo->{error} )
                {
                    Error( "Unable to extract image info from '".$frame->{imagePath}."': ".$imageInfo->{error} );
                }
                else
                {
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
    while ( my ( $field, $value ) = each( %$event ) )
    {
        next unless $field =~ /^[A-Z]/;
        push( @fields, $field );
        push( @formats, ($formats{$field} or '?') );
        push( @values, $event->{$field} );
    }

    my $sql = "insert into Events (".join(',',@fields).") values (".join(',',@formats).")";
    my $sth = $dbh->prepare_cached( $sql ) or Fatal( "Can't prepare sql '$sql': ".$dbh->errstr() );
    my $res = $sth->execute( @values ) or Fatal( "Can't execute sql '$sql': ".$sth->errstr() );
    $event->{Id} = $dbh->{mysql_insertid};
    Info( "Created event ".$event->{Id} );

    if ( $event->{EndTime} )
    {
        $event->{Name} = $event->{monitor}->{EventPrefix}.$event->{Id} if ( $event->{Name} eq 'New Event' );
        my $sql = "update Events set Name = ? where Id = ?";
        my $sth = $dbh->prepare_cached( $sql ) or Fatal( "Can't prepare sql '$sql': ".$dbh->errstr() );
        my $res = $sth->execute( $event->{Name}, $event->{Id} ) or Fatal( "Can't execute sql '$sql': ".$sth->errstr() );
    }

    my $eventPath = createEventPath( $event );

    my %frameFormats = (
        TimeStamp => 'from_unixtime(?)',
    );
    my $frameId = 1;
    foreach my $frame ( @{$event->{frames}} )
    {
        $frame->{EventId} = $event->{Id};
        $frame->{FrameId} = $frameId++;

        my ( @fields, @formats, @values );
        while ( my ( $field, $value ) = each( %$frame ) )
        {
            next unless $field =~ /^[A-Z]/;
            push( @fields, $field );
            push( @formats, ($frameFormats{$field} or '?') );
            push( @values, $frame->{$field} );
        }

        my $sql = "insert into Frames (".join(',',@fields).") values (".join(',',@formats).")";
        my $sth = $dbh->prepare_cached( $sql ) or Fatal( "Can't prepare sql '$sql': ".$dbh->errstr() );
        my $res = $sth->execute( @values ) or Fatal( "Can't execute sql '$sql': ".$sth->errstr() );
        #$frame->{FrameId} = $dbh->{mysql_insertid};
        if ( $frame->{imagePath} )
        {
            $frame->{capturePath} = sprintf( "%s/%0".$Config{ZM_EVENT_IMAGE_DIGITS}."d-capture.jpg", $eventPath, $frame->{FrameId} );
            rename( $frame->{imagePath}, $frame->{capturePath} ) or Fatal( "Can't copy ".$frame->{imagePath}." to ".$frame->{capturePath}.": $!" );
            setFileOwner( $frame->{capturePath} );
            if ( 0 && $Config{ZM_CREATE_ANALYSIS_IMAGES} )
            {
                $frame->{analysePath} = sprintf( "%s/%0".$Config{ZM_EVENT_IMAGE_DIGITS}."d-analyse.jpg", $eventPath, $frame->{FrameId} );
                link( $frame->{capturePath}, $frame->{analysePath} ) or Fatal( "Can't link ".$frame->{capturePath}." to ".$frame->{analysePath}.": $!" );
                setFileOwner( $frame->{analysePath} );
            }
        }
    }
}

sub addEventImage( $$ )
{
    my $event = shift;
    my $frame = shift;

    # TBD
}

sub updateEvent( $ )
{
    my $event = shift;

    if ( !$event->{EventId} )
    {
        Error( "Unable to update event, no event id supplied" );
        return( 0 );
    }

    my $dbh = zmDbConnect();

    $event->{Name} = $event->{monitor}->{EventPrefix}.$event->{Id} if ( $event->{Name} eq 'New Event' );

    my %formats = (
        StartTime => 'from_unixtime(?)',
        EndTime => 'from_unixtime(?)',
    );

    my ( @values, @sets );
    while ( my ( $field, $value ) = each( %$event ) )
    {
        next if ( $field eq 'Id' );
        push( @values, $event->{$field} );
        push( @sets, $field." = ".($formats{$field} or '?') );
    }
    my $sql = "update Events set ".join(',',@sets)." where Id = ?";
    push( @values, $event->{Id} );

    my $sth = $dbh->prepare_cached( $sql ) or Fatal( "Can't prepare sql '$sql': ".$dbh->errstr() );
    my $res = $sth->execute( @values ) or Fatal( "Can't execute sql '$sql': ".$sth->errstr() );
}

sub deleteEventFiles( $;$ )
{
    #
    # WARNING assumes running from events directory
    #
    my $event_id = shift;
    my $monitor_id = shift;
    $monitor_id = '*' if ( !defined($monitor_id) );

    if ( $Config{ZM_USE_DEEP_STORAGE} )
    {
        my $link_path = $monitor_id."/*/*/*/.".$event_id;
        #Debug( "LP1:$link_path" );
        my @links = glob($link_path);
        #Debug( "L:".$links[0].": $!" );
        if ( @links )
        {
            ( $link_path ) = ( $links[0] =~ /^(.*)$/ ); # De-taint
            #Debug( "LP2:$link_path" );

            ( my $day_path = $link_path ) =~ s/\.\d+//;
            #Debug( "DP:$day_path" );
            my $event_path = $day_path.readlink( $link_path );
            ( $event_path ) = ( $event_path =~ /^(.*)$/ ); # De-taint
            #Debug( "EP:$event_path" );
            my $command = "/bin/rm -rf ".$event_path;
            #Debug( "C:$command" );
            executeShellCommand( $command );

            unlink( $link_path ) or Error( "Unable to unlink '$link_path': $!" );
            my @path_parts = split( /\//, $event_path );
            for ( my $i = int(@path_parts)-2; $i >= 1; $i-- )
            {
                my $delete_path = join( '/', @path_parts[0..$i] );
                #Debug( "DP$i:$delete_path" );
                my @has_files = glob( $delete_path."/*" );
                #Debug( "HF1:".$has_files[0] ) if ( @has_files );
                last if ( @has_files );
                @has_files = glob( $delete_path."/.[0-9]*" );
                #Debug( "HF2:".$has_files[0] ) if ( @has_files );
                last if ( @has_files );
                my $command = "/bin/rm -rf ".$delete_path;
                executeShellCommand( $command );
            }
        }
    }
    else
    {
        my $command = "/bin/rm -rf $monitor_id/$event_id";
        executeShellCommand( $command );
    }
}

sub makePath( $;$ )
{
    my $path = shift;
    my $root = shift;
    $root = (( $path =~ m|^/| )?'':'.' ) unless( $root );

    Debug( "Creating path '$path' in $root'\n" );
    my @parts = split( '/', $path );
    my $fullPath = $root;
    foreach my $dir ( @parts )
    {
        $fullPath .= '/'.$dir;
        if ( !-d $fullPath )
        {
            if ( -e $fullPath )
            {
                Fatal( "Can't create '$fullPath', already exists as non directory" );
            }
            else
            {
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

sub _testJSON
{
    return if ( $testedJSON );
    my $result = eval
    {
        require JSON::Any;
        JSON::Any->import();
    };
    $testedJSON = 1;
    $hasJSONAny = 1 if ( $result );
}

sub _getJSONType( $ )
{
    my $value = shift;
    return( 'null' ) unless( defined($value) );
    return( 'integer' ) if ( $value =~ /^\d+$/ );
    return( 'double' ) if ( $value =~ /^\d+$/ );
    return( 'hash' ) if ( ref($value) eq 'HASH' );
    return( 'array' ) if ( ref($value) eq 'ARRAY' );
    return( 'string' );
}

sub jsonEncode( $ );

sub jsonEncode( $ )
{
    my $value = shift;

    _testJSON();
    if ( $hasJSONAny )
    {
        my $string = eval { JSON::Any->objToJson( $value ) };
        Fatal( "Unable to encode object to JSON: $@" ) unless( $string );
        return( $string );
    }

    my $type = _getJSONType($value);
    if ( $type eq 'integer' || $type eq 'double' )
    {
        return( $value );
    }
    elsif ( $type eq 'boolean' )
    {
        return( $value?'true':'false' );
    }
    elsif ( $type eq 'string' )
    {
        $value =~ s|(["\\/])|\\$1|g;
        $value =~ s|\r?\n|\n|g;
        return( '"'.$value.'"' );
    }
    elsif ( $type eq 'null' )
    {
        return( 'null' );
    }
    elsif ( $type eq 'array' )
    {
        return( '['.join( ',', map { jsonEncode( $_ ) } @$value ).']' );
    }
    elsif ( $type eq 'hash' )
    {
        my $result = '{';
        while ( my ( $subKey=>$subValue ) = each( %$value ) )
        {
            $result .= ',' if ( $result ne '{' );
            $result .= '"'.$subKey.'":'.jsonEncode( $subValue );
        }
        return( $result.'}' );
    }
    else
    {
        Fatal( "Unexpected type '$type'" );
    }
}

sub jsonDecode( $ )
{
    my $value = shift;

    _testJSON();
    if ( $hasJSONAny )
    {
        my $object = eval { JSON::Any->jsonToObj( $value ) };
        Fatal( "Unable to decode JSON string '$value': $@" ) unless( $object );
        return( $object );
    }

    my $comment = 0;
    my $unescape = 0;
    my $out = '';
    my @chars = split( //, $value );
    for ( my $i = 0; $i < @chars; $i++ )
    {
        if ( !$comment )
        {
            if ( $chars[$i] eq ':' )
            {
                $out .= '=>';
            }
            else
            {
                $out .= $chars[$i];         
            }
        }
        elsif ( !$unescape )
        {
            if ( $chars[$i] eq '\\' )
            {
                $unescape = 1;
            }
            else
            {
                $out .= $chars[$i];
            }
        }
        else
        {
            if ( $chars[$i] ne '/' )
            {
                $out .= '\\';
            }
            $out .= $chars[$i];
            $unescape = 0;
        }
        if ( $chars[$i] eq '"' )
        {
            $comment = !$comment;
        }
    }
    $out =~ s/=>true/=>1/g;
    $out =~ s/=>false/=>0/g;
    $out =~ s/=>null/=>undef/g;
    $out =~ s/`/'/g;
    $out =~ s/qx/qq/g;
    ( $out ) = $out =~ m/^({.+})$/; # Detaint and check it's a valid object syntax
    my $result = eval $out;
    Fatal( $@ ) if ( $@ );
    return( $result );
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Database - Perl extension for blah blah blah

=head1 SYNOPSIS

  use ZoneMinder::Database;
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
