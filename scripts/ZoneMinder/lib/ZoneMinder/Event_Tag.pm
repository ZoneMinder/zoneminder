# ==========================================================================
#
# ZoneMinder Tag Module
# Copyright (C) 2022 ZoneMinder Inc
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
package ZoneMinder::Event_Tag;

use 5.006;
use strict;
use warnings;
use Time::HiRes qw(usleep);

require ZoneMinder::Base;
require ZoneMinder::Object;
require ZoneMinder::Event;
require ZoneMinder::Tag;
use ZoneMinder::Logger qw(:all);

use parent qw(ZoneMinder::Object);

use vars qw/ $table %fields @identified_by %defaults $debug /;
$table = 'Events_Tags';
@identified_by = ('TagId','EventId');
%fields = map { $_ => $_ } qw(
  TagId
  EventId
  AssignedDate
  AssignedBy
  );

%defaults = (
);

sub Event {
  return new ZoneMinder::Event($_[0]{EventId});
}

sub Tag {
  return new ZoneMinder::Tag($_[0]{TagId});
}

1;
__END__

=head1 NAME

ZoneMinder::Event_Tag - Perl Class for Event Tags

=head1 SYNOPSIS

use ZoneMinder::Event_Tag;

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2022  ZoneMinder Inc

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
