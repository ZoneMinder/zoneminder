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

# This allows declaration	use ZoneMinder ':all';
# If you do not need this, moving things directly into @EXPORT or @EXPORT_OK
# will save memory.
our %EXPORT_TAGS = (
    'functions' => [ qw(
		executeShellCommand
		getCmdFormat
		runCommand
		getEventPath
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
use ZoneMinder::Debug qw(:all);

use POSIX;

# For running general shell commands
sub executeShellCommand( $ )
{
    my $command = shift;
    my $output = qx( $command );
    my $status = $? >> 8;
    if ( $status || zmDbgLevel() > 0 )
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
	if ( $name eq ZM_WEB_USER )
	{
		Debug( "Running as '$name', su commands not needed\n" );
		return( "" );
	}

	my $null_command = "true";

	my $prefix = "sudo -u ".ZM_WEB_USER." ";
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

		$prefix = "su ".ZM_WEB_USER." --shell=/bin/sh --command='";
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

			$prefix = "su ".ZM_WEB_USER." -c '";
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
	$command = ZM_PATH_BIN."/".$command;
	if ( $cmdPrefix )
	{
		$command = $cmdPrefix.$command.$cmdSuffix;
	}
	Debug( "Command: $command\n" );
	my $output = qx($command);
	my $status = $? >> 8;
	chomp( $output );
	if ( $status || zmDbgLevel() > 0 )
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
    if ( ZM_USE_DEEP_STORAGE )
    {
        $event_path = ZM_PATH_WEB.'/'.ZM_DIR_EVENTS.'/'.$event->{MonitorId}.'/'.strftime( "%y/%m/%d/%H/%M/%S", localtime($event->{Time}) );
    }
    else
    {
        $event_path = ZM_PATH_WEB.'/'.ZM_DIR_EVENTS.'/'.$event->{MonitorId}.'/'.$event->{Id};
    }
    return( $event_path );
}

sub deleteEventFiles( $;$ )
{
    #
    # WARNING assumes running from events directory
    #

    my $event_id = shift;
    my $monitor_id = shift;
    $monitor_id = '*' if ( !defined($monitor_id) );

    if ( ZM_USE_DEEP_STORAGE )
    {
        my $link_path = $monitor_id."/*/*/*/.".$event_id;
        Debug( "LP1:$link_path" );
        my @links = glob($link_path);
        Debug( "L:".$links[0].": $!" );
        if ( @links )
        {
            ( $link_path ) = ( $links[0] =~ /^(.*)$/ ); # De-taint
            Debug( "LP2:$link_path" );

            ( my $day_path = $link_path ) =~ s/\.\d+//;
            Debug( "DP:$day_path" );
            my $event_path = $day_path.readlink( $link_path );
            ( $event_path ) = ( $event_path =~ /^(.*)$/ ); # De-taint
            Debug( "EP:$event_path" );
            my $command = "/bin/rm -rf ".$event_path;
            Debug( "C:$command" );
            executeShellCommand( $command );

            unlink( $link_path ) or Error( "Unable to unlink '$link_path': $!" );
            my @path_parts = split( /\//, $event_path );
            for ( my $i = int(@path_parts)-2; $i >= 1; $i-- )
            {
                my $delete_path = join( '/', @path_parts[0..$i] );
                Debug( "DP$i:$delete_path" );
                my @has_files = glob( $delete_path."/*" );
                Debug( "HF1:".$has_files[0] );
                last if ( @has_files );
                @has_files = glob( $delete_path."/.[0-9]*" );
                Debug( "HF2:".$has_files[0] );
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

sub makePath( $$ )
{
    my $root = shift;
    my $path = shift;

    Debug( "Creating path '$path'\n" );
    my @parts = split( '/', $path );
    my $subpath = $root;
    foreach my $dir ( @parts )
    {
        $subpath .= '/'.$dir;
        if ( !-d $subpath )
        {
            if ( -e $subpath )
            {
                die( "Can't create '$subpath', already exists as non directory" );
            }
            else
            {
                Debug( "Creating '$subpath'\n" );
                mkdir( $subpath ) or die( "Can't mkdir '$subpath': $!" );
            }
        }
    }
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
    $hasJSONAny = 1 if( $result );

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
    return( JSON::Any->objToJson( $value ) ) if ( $hasJSONAny );

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
        die( "Unexpected type '$type'" );
    }
}

sub jsonDecode( $ )
{
    my $value = shift;

    _testJSON();
    return( JSON::Any->jsonToObj( $value ) ) if ( $hasJSONAny );

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
    my $result = eval $out;
    die( $@ ) if ( $@ );
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
