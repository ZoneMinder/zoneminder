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
		getEventPath
		deleteEventFiles
        makePath
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
