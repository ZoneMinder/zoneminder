# ==========================================================================
#
# ZoneMinder Database Module, $Date$, $Revision$
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
package ZoneMinder::Database;

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
		zmDbConnect
		zmDbDisconnect
		zmDbGetMonitors
		zmDbGetMonitor
		zmDbGetMonitorAndControl
	) ]
);
push( @{$EXPORT_TAGS{all}}, @{$EXPORT_TAGS{$_}} ) foreach keys %EXPORT_TAGS;

our @EXPORT_OK = ( @{ $EXPORT_TAGS{'all'} } );

our @EXPORT = qw();

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Database Access
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Carp;

our $dbh = undef;

sub zmDbConnect( ;$ )
{
	my $force = shift;
	if ( $force )
	{
		 zmDbDisconnect();
	}
	if ( !defined( $dbh ) )
	{
        my ( $host, $port ) = ( $Config{ZM_DB_HOST} =~ /^([^:]+)(?::(.+))?$/ );

        if ( defined($port) )
        {
		    $dbh = DBI->connect( "DBI:mysql:database=".$Config{ZM_DB_NAME}.";host=".$host.";port=".$port, $Config{ZM_DB_USER}, $Config{ZM_DB_PASS} );
        }
        else
        {
		    $dbh = DBI->connect( "DBI:mysql:database=".$Config{ZM_DB_NAME}.";host=".$Config{ZM_DB_HOST}, $Config{ZM_DB_USER}, $Config{ZM_DB_PASS} );
        }
        $dbh->trace( 0 );
	}
	return( $dbh );
}

sub zmDbDisconnect()
{
	if ( defined( $dbh ) )
	{
		$dbh->disconnect();
		$dbh = undef;
	}
}

use constant DB_MON_ALL => 0; # All monitors
use constant DB_MON_CAPT => 1; # All monitors that are capturing
use constant DB_MON_ACTIVE => 2; # All monitors that are active
use constant DB_MON_MOTION => 3; # All monitors that are doing motion detection
use constant DB_MON_RECORD => 4; # All monitors that are doing unconditional recording
use constant DB_MON_PASSIVE => 5; # All monitors that are in nodect state

sub zmDbGetMonitors( ;$ )
{
	zmDbConnect();

	my $function = shift || DB_MON_ALL;
	my $sql = "select * from Monitors";

	if ( $function )
	{
		if ( $function == DB_MON_CAPT )
		{
			$sql .= " where Function >= 'Monitor'";
		}
		elsif ( $function == DB_MON_ACTIVE )
		{
			$sql .= " where Function > 'Monitor'";
		}
		elsif ( $function == DB_MON_MOTION )
		{
			$sql .= " where Function = 'Modect' or Function = 'Mocord'";
		}
		elsif ( $function == DB_MON_RECORD )
		{
			$sql .= " where Function = 'Record' or Function = 'Mocord'";
		}
		elsif ( $function == DB_MON_PASSIVE )
		{
			$sql .= " where Function = 'Nodect'";
		}
	}
	my $sth = $dbh->prepare_cached( $sql ) or croak( "Can't prepare '$sql': ".$dbh->errstr() );
	my $res = $sth->execute() or croak( "Can't execute '$sql': ".$sth->errstr() );

	my @monitors;
    while( my $monitor = $sth->fetchrow_hashref() )
    {
		push( @monitors, $monitor );
	}
	$sth->finish();
	return( \@monitors );
}

sub zmDbGetMonitor( $ )
{
	zmDbConnect();

	my $id = shift;

	return( undef ) if ( !defined($id) );

	my $sql = "select * from Monitors where Id = ?";
	my $sth = $dbh->prepare_cached( $sql ) or croak( "Can't prepare '$sql': ".$dbh->errstr() );
	my $res = $sth->execute( $id ) or croak( "Can't execute '$sql': ".$sth->errstr() );
    my $monitor = $sth->fetchrow_hashref();

	return( $monitor );
}

sub zmDbGetMonitorAndControl( $ )
{
	zmDbConnect();

	my $id = shift;

	return( undef ) if ( !defined($id) );

    my $sql = "select C.*,M.*,C.Protocol from Monitors as M inner join Controls as C on (M.ControlId = C.Id) where M.Id = ?";
	my $sth = $dbh->prepare_cached( $sql ) or Fatal( "Can't prepare '$sql': ".$dbh->errstr() );
	my $res = $sth->execute( $id ) or Fatal( "Can't execute '$sql': ".$sth->errstr() );
    my $monitor = $sth->fetchrow_hashref();

	return( $monitor );
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
