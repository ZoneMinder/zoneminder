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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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

# This allows declaration   use ZoneMinder ':all';
# If you do not need this, moving things directly into @EXPORT or @EXPORT_OK
# will save memory.
our %EXPORT_TAGS = (
    functions => [ qw(
      zmDbConnect
      zmDbDisconnect
      zmDbGetMonitors
      zmDbGetMonitor
      zmDbGetMonitorAndControl
      zmDbDo
      ) ]
    );
push( @{$EXPORT_TAGS{all}}, @{$EXPORT_TAGS{$_}} ) foreach keys %EXPORT_TAGS;

our @EXPORT_OK = ( @{ $EXPORT_TAGS{all} } );

our @EXPORT = qw();

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Database Access
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);

require ZoneMinder::Config;

our $dbh = undef;

sub zmDbConnect {
  my $force = shift;
  if ( $force ) {
    zmDbDisconnect();
  }
  my $options = shift;

  if ( ( !defined($dbh) ) or ! $dbh->ping() ) {
    my ( $host, $portOrSocket ) = ( $ZoneMinder::Config::Config{ZM_DB_HOST} =~ /^([^:]+)(?::(.+))?$/ );
    my $socket;

    if ( defined($portOrSocket) ) {
      if ( $portOrSocket =~ /^\// ) {
        $socket = ';mysql_socket='.$portOrSocket;
      } else {
        $socket = ';host='.$host.';port='.$portOrSocket;
      }
    } else {
      $socket = ';host='.$ZoneMinder::Config::Config{ZM_DB_HOST}; 
    }

    my $sslOptions = '';
    if ( $ZoneMinder::Config::Config{ZM_DB_SSL_CA_CERT} ) {
      $sslOptions = join(';', '',
          'mysql_ssl=1',
          'mysql_ssl_ca_file='.$ZoneMinder::Config::Config{ZM_DB_SSL_CA_CERT},
          'mysql_ssl_client_key='.$ZoneMinder::Config::Config{ZM_DB_SSL_CLIENT_KEY},
          'mysql_ssl_client_cert='.$ZoneMinder::Config::Config{ZM_DB_SSL_CLIENT_CERT}
          );
    }

    eval {
      $dbh = DBI->connect(
        'DBI:mysql:database='.$ZoneMinder::Config::Config{ZM_DB_NAME}
        .$socket . $sslOptions . ($options?join(';', '', map { $_.'='.$$options{$_} } keys %{$options} ) : '')
        , $ZoneMinder::Config::Config{ZM_DB_USER}
        , $ZoneMinder::Config::Config{ZM_DB_PASS}
        );
    };
    if ( !$dbh or $@ ) {
      Error("Error reconnecting to db: errstr:$DBI::errstr error val:$@");
    } else {
      $dbh->{AutoCommit} = 1;
      Fatal('Can\'t set AutoCommit on in database connection')
        unless $dbh->{AutoCommit};
      $dbh->{mysql_auto_reconnect} = 1;
      Fatal('Can\'t set mysql_auto_reconnect on in database connection')
        unless $dbh->{mysql_auto_reconnect};
      $dbh->trace( 0 );
    } # end if success connecting
  } # end if ! connected
  return $dbh;
} # end sub zmDbConnect

sub zmDbDisconnect {
  if ( defined($dbh) ) {
    $dbh->disconnect() or Error('Error disconnecting db? ' . $dbh->errstr());
    $dbh = undef;
  }
}

use constant DB_MON_ALL => 0; # All monitors
use constant DB_MON_CAPT => 1; # All monitors that are capturing
use constant DB_MON_ACTIVE => 2; # All monitors that are active
use constant DB_MON_MOTION => 3; # All monitors that are doing motion detection
use constant DB_MON_RECORD => 4; # All monitors that are doing unconditional recording
use constant DB_MON_PASSIVE => 5; # All monitors that are in nodect state

sub zmDbGetMonitors {
  zmDbConnect();

  my $function = shift || DB_MON_ALL;
  my $sql = 'SELECT * FROM Monitors';

  if ( $function ) {
    if ( $function == DB_MON_CAPT ) {
      $sql .= " where Function >= 'Monitor'";
    } elsif ( $function == DB_MON_ACTIVE ) {
      $sql .= " where Function > 'Monitor'";
    } elsif ( $function == DB_MON_MOTION ) {
      $sql .= " where Function = 'Modect' or Function = 'Mocord'";
    } elsif ( $function == DB_MON_RECORD ) {
      $sql .= " where Function = 'Record' or Function = 'Mocord'";
    } elsif ( $function == DB_MON_PASSIVE ) {
      $sql .= " where Function = 'Nodect'";
    }
  }
  my $sth = $dbh->prepare_cached( $sql );
  if ( ! $sth ) {
    Error("Can't prepare '$sql': ".$dbh->errstr());
    return undef;
  }
  my $res = $sth->execute();
  if ( ! $res ) {
    Error("Can't execute '$sql': ".$sth->errstr());
    return undef;
  }

  my @monitors;
  while( my $monitor = $sth->fetchrow_hashref() ) {
    push( @monitors, $monitor );
  }
  $sth->finish();
  return \@monitors;
}

sub zmSQLExecute {
  my $sql = shift;

  my $sth = $dbh->prepare_cached( $sql );
  if ( ! $sth ) {
    Error("Can't prepare '$sql': ".$dbh->errstr());
    return undef;
  }
  my $res = $sth->execute( @_ );
  if ( ! $res ) {
    Error("Can't execute '$sql': ".$sth->errstr());
    return undef;
  }
  return 1;
}

sub zmDbGetMonitor {
  zmDbConnect();

  my $id = shift;

  if ( !defined($id) ) {
    Error('Undefined id in zmDbgetMonitor');
    return undef ;
  }

  my $sql = 'SELECT * FROM Monitors WHERE Id = ?';
  my $sth = $dbh->prepare_cached($sql);
  if ( !$sth ) {
    Error("Can't prepare '$sql': ".$dbh->errstr());
    return undef;
  }
  my $res = $sth->execute($id);
  if ( !$res ) {
    Error("Can't execute '$sql': ".$sth->errstr());
    return undef;
  }
  my $monitor = $sth->fetchrow_hashref();
  $sth->finish();
  return $monitor;
}

sub zmDbGetMonitorAndControl {
  zmDbConnect();

  my $id = shift;

  return undef if !defined($id);

  my $sql = 'SELECT C.*,M.*,C.Protocol
    FROM Monitors as M
    INNER JOIN Controls as C on (M.ControlId = C.Id)
    WHERE M.Id = ?'
    ;
  my $sth = $dbh->prepare_cached($sql);
  if ( !$sth ) {
    Error("Can't prepare '$sql': ".$dbh->errstr());
    return undef;
  }
  my $res = $sth->execute( $id );
  if ( !$res ) {
    Error("Can't execute '$sql': ".$sth->errstr());
    return undef;
  }
  my $monitor = $sth->fetchrow_hashref();
  $sth->finish();
  return $monitor;
}

sub start_transaction {
	my $d = shift;
	$d = $dbh if ! $d;
	my $ac = $d->{AutoCommit};
	$d->{AutoCommit} = 0;
	return $ac;
} # end sub start_transaction

sub end_transaction {
  my ( $d, $ac ) = @_;
  if ( ! defined $ac ) {
    Error("Undefined ac");
  }
	$d = $dbh if ! $d;
	if ( $ac ) {
		$d->commit();
	} # end if
	$d->{AutoCommit} = $ac;
} # end sub end_transaction

# Basic execution of $dbh->do but with some pretty logging of the sql on error.
# Returns 1 on success, 0 on error
sub zmDbDo {
	my $sql = shift;
	if ( ! $dbh->do($sql, undef, @_) ) {
		$sql =~ s/\?/'%s'/;
		Error(sprintf("Failed $sql :", @_).$dbh->errstr());
    return 0;
	}
  return 1;
}

1;
__END__

=head1 NAME

ZoneMinder::Database - Perl module containing database functions used in ZM

=head1 SYNOPSIS

use ZoneMinder::Database;

=head1 DESCRIPTION


=head2 EXPORT

zmDbConnect
zmDbDisconnect
zmDbGetMonitors
zmDbGetMonitor
zmDbGetMonitorAndControl
zmDbDo

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=cut
