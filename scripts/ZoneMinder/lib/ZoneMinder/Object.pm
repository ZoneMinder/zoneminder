# ==========================================================================
#
# ZoneMinder Object Module, $Date$, $Revision$
# Copyright (C) 2001-2017  ZoneMinder LLC
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
package ZoneMinder::Object;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;

our @ISA = qw(ZoneMinder::Base);

# ==========================================================================
#
# General Utility Functions
#
# ==========================================================================

use ZoneMinder::Config qw(:all);
use ZoneMinder::Logger qw(:all);
use ZoneMinder::Database qw(:all);

use vars qw/ $AUTOLOAD /;

sub new {
  my ( $parent, $id, $data ) = @_;

  my $self = {};
  bless $self, $parent;
  no strict 'refs';
  my $primary_key = ${$parent.'::primary_key'};
  if ( ! $primary_key ) {
    Error( 'NO primary_key for type ' . $parent );
    return;
  } # end if
  if ( ( $$self{$primary_key} = $id ) or $data ) {
#$log->debug("loading $parent $id") if $debug or DEBUG_ALL;
    $self->load( $data );
  }
  return $self;
} # end sub new

sub load {
  my ( $self, $data ) = @_;
  my $type = ref $self;
  if ( ! $data ) {
    no strict 'refs';
    my $table = ${$type.'::table'};
    if ( ! $table ) {
      Error( 'NO table for type ' . $type );
      return;
    } # end if
    my $primary_key = ${$type.'::primary_key'};
    if ( ! $primary_key ) {
      Error( 'NO primary_key for type ' . $type );
      return;
    } # end if

    if ( ! $$self{$primary_key} ) { 
      my ( $caller, undef, $line ) = caller;
      Error( (ref $self) . "::load called without $primary_key from $caller:$line");
    } else {
#$log->debug("Object::load Loading from db $type");
      Debug("Loading $type from $table WHERE $primary_key = $$self{$primary_key}");
      $data = $ZoneMinder::Database::dbh->selectrow_hashref( "SELECT * FROM $table WHERE $primary_key=?", {}, $$self{$primary_key} );
      if ( ! $data ) {
        if ( $ZoneMinder::Database::dbh->errstr ) {
          Error( "Failure to load Object record for $$self{$primary_key}: Reason: " . $ZoneMinder::Database::dbh->errstr );
        } else {
          Debug("No Results Loading $type from $table WHERE $primary_key = $$self{$primary_key}");
        } # end if
      } # end if
    } # end if
  } # end if ! $data
  if ( $data and %$data ) {
    @$self{keys %$data} = values %$data;
  } # end if
} # end sub load

sub AUTOLOAD {
  my ( $self, $newvalue ) = @_;
  my $type = ref($_[0]);
  my $name = $AUTOLOAD;
  $name =~ s/.*://;
  if ( @_ > 1 ) {
    return $_[0]{$name} = $_[1];
  }
  return $_[0]{$name};
}


1;
__END__

# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Object

=head1 SYNOPSIS

  use parent ZoneMinder::Object;
  
  This package should likely not be used directly, as it is meant mainly to be a parent for all other ZoneMinder classes.

=head1 DESCRIPTION

  A base Object to act as parent for other ZoneMinder Objects.

=head2 EXPORT

None by default.

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2017  ZoneMinder LLC

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
