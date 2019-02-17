# ==========================================================================
#
# ZoneMinder Storage Module, $Date$, $Revision$
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
package ZoneMinder::Storage;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Object;
require ZoneMinder::Server;

use parent qw(Exporter ZoneMinder::Object);

# ==========================================================================
#
# General Utility Functions
#
# ==========================================================================

use ZoneMinder::Config qw(:all);
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Database qw(:all);

use POSIX;

use vars qw/ $table $primary_key /;
$table = 'Storage';
$primary_key = 'Id';
#__PACKAGE__->table('Storage');
#__PACKAGE__->primary_key('Id');

sub find {
  shift if $_[0] eq 'ZoneMinder::Storage';
  my %sql_filters = @_;

  my $sql = 'SELECT * FROM Storage';
  my @sql_filters;
  my @sql_values;

  if ( exists $sql_filters{Id} ) {
    push @sql_filters , ' Id=? ';
    push @sql_values, $sql_filters{Id};
  }
  if ( exists $sql_filters{Name} ) {
    push @sql_filters , ' Name = ? ';
    push @sql_values, $sql_filters{Name};
  }
  if ( exists $sql_filters{ServerId} ) {
    push @sql_filters, ' ServerId = ?';
    push @sql_values, $sql_filters{ServerId};
  }


  $sql .= ' WHERE ' . join(' AND ', @sql_filters) if @sql_filters;
  $sql .= ' LIMIT ' . $sql_filters{limit} if $sql_filters{limit};

  my $sth = $ZoneMinder::Database::dbh->prepare_cached( $sql )
    or Fatal( "Can't prepare '$sql': ".$ZoneMinder::Database::dbh->errstr() );
  my $res = $sth->execute( @sql_values )
    or Fatal( "Can't execute '$sql': ".$sth->errstr() );

  my @results;

  while( my $db_filter = $sth->fetchrow_hashref() ) {
    my $filter = new ZoneMinder::Storage( $$db_filter{Id}, $db_filter );
    push @results, $filter;
  } # end while
  Debug("SQL: $sql returned " . @results . ' results');
  return @results;
}

sub find_one {
  my @results = find(@_);
  return $results[0] if @results;
}

sub Path {
  if ( @_ > 1 ) {
    $_[0]{Path} = $_[1];
  }
  if ( ! ( $_[0]{Id} or $_[0]{Path} ) ) {
    $_[0]{Path} = ($Config{ZM_DIR_EVENTS}=~/^\//) ? $Config{ZM_DIR_EVENTS} : ($Config{ZM_PATH_WEB}.'/'.$Config{ZM_DIR_EVENTS})
  }
  return $_[0]{Path};
} # end sub Path

sub Name {
  if ( @_ > 1 ) {
    $_[0]{Name} = $_[1];
  }
  return $_[0]{Name};
} # end sub Path

sub DoDelete {
  my $self = shift;
  $$self{DoDelete} = shift if @_;
  if ( ! defined $$self{DoDelete} ) {
    $$self{DoDelete} = 1;
  }
  return $$self{DoDelete};
}

sub Server {
  my $self = shift;
  if ( ! $$self{Server} ) {
    $$self{Server} = new ZoneMinder::Server( $$self{ServerId} );
  }
  return $$self{Server};
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Database - Perl extension for blah blah blah

=head1 SYNOPSIS

  use ZoneMinder::Storage;
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
