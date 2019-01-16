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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
# ==========================================================================
#
# This module contains the common definitions and functions used by the rest
# of the ZoneMinder scripts
#
package ZoneMinder::Frame;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Object;

use parent qw(ZoneMinder::Object);

use vars qw/ $table $primary_key %fields /;
$table = 'Frames';
$primary_key = 'Id';

%fields = (
        Id      => 'Id',
        EventId => 'EventId',
        FrameId => 'FrameId',
        Type    => 'Type',
        TimeStamp =>  'TimeStamp',
        Delta     =>  'Delta',
        Score     =>  'Score',
);

sub Event {
	return new ZoneMinder::Event( $_[0]{EventId} );
} # end sub Event

1;
__END__

=head1 NAME

ZoneMinder::Frame - Perl Class for Frames

=head1 SYNOPSIS

use ZoneMinder::Frame;

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2017  ZoneMinder LLC

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
