# ==========================================================================
#
# ZoneMinder Server Module, $Date$, $Revision$
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
package ZoneMinder::Server;

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
      CpuLoad
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

sub new {
  my ( $parent, $id, $data ) = @_;

  my $self = {};
  bless $self, $parent;
  if ( ( $$self{Id} = $id ) or $data ) {
#$log->debug("loading $parent $id") if $debug or DEBUG_ALL;
    $self->load( $data );
  }
  return $self;
} # end sub new

sub load {
  my ( $self, $data ) = @_;
  my $type = ref $self;
  if ( ! $data ) {
#$log->debug("Object::load Loading from db $type");
    $data = $ZoneMinder::Database::dbh->selectrow_hashref( 'SELECT * FROM Servers WHERE Id=?', {}, $$self{Id} );
    if ( ! $data ) {
      if ( $ZoneMinder::Database::dbh->errstr ) {
        Error( "Failure to load Server record for $$self{id}: Reason: " . $ZoneMinder::Database::dbh->errstr );
      } # end if
    } # end if
  } # end if ! $data
  if ( $data and %$data ) {
    @$self{keys %$data} = values %$data;
  } # end if
} # end sub load

sub Name {
  if ( @_ > 1 ) {
    $_[0]{Name} = $_[1];
  }
  return $_[0]{Name};
} # end sub Name

sub Hostname {
  if ( @_ > 1 ) {
    $_[0]{Hostname} = $_[1];
  }
  return $_[0]{Hostname};
} # end sub Hostname

sub CpuLoad {
  my $output = qx(uptime);
  my @sysloads = split ', ', (split ': ', $output)[-1];

  if (join(', ',@sysloads) =~ /(\d+\.\d+)\s*,\s+(\d+\.\d+)\s*,\s+(\d+\.\d+)\s*$/) {
    return @sysloads;
  }

  return (undef, undef, undef);
} # end sub CpuLoad

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Database - Perl extension for blah blah blah

=head1 SYNOPSIS

use ZoneMinder::Server;
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
