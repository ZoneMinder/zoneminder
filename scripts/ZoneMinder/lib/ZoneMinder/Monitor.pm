# ==========================================================================
#
# ZoneMinder Monitor Module, $Date$, $Revision$
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
package ZoneMinder::Monitor;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Object;
require ZoneMinder::Storage;
require ZoneMinder::Server;

#our @ISA = qw(Exporter ZoneMinder::Base);
use parent qw(ZoneMinder::Object);

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
$table = 'Monitors';
$primary_key = 'Id';

sub Server {
	return new ZoneMinder::Server( $_[0]{ServerId} );
} # end sub Server

sub Storage {
	return new ZoneMinder::Storage( $_[0]{StorageId} );
} # end sub Storage

1;
__END__

=head1 NAME

ZoneMinder::Monitor - Perl Class for Monitors

=head1 SYNOPSIS

use ZoneMinder::Monitor;

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2017  ZoneMinder LLC

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
