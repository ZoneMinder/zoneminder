# ==========================================================================
#
# ZoneMinder Event_Summary Module
# Copyright (C) 2020 ZoneMinder
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
package ZoneMinder::Event_Summary;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Object;

#our @ISA = qw(Exporter ZoneMinder::Base);
use parent qw(ZoneMinder::Object);

use vars qw/ $table $primary_key %fields $serial %defaults $debug/;
$table = 'Event_Summaries';
$serial = $primary_key = 'MonitorId';
%fields = map { $_ => $_ } qw(
  MonitorId
  TotalEvents
  TotalEventDiskSpace
  HourEvents
  HourEventDiskSpace
  DayEvents
  DayEventDiskSpace
  WeekEvents
  WeekEventDiskSpace
  MonthEvents
  MonthEventDiskSpace
  ArchivedEvents
  ArchivedEventDiskSpace
  );

%defaults = (
    TotalEvents => undef,
    TotalEventDiskSpace => undef,
    HourEvents =>   undef,
    HourEventDiskSpace =>  undef,
    DayEvents =>  undef,
    DayEventDiskSpace =>  undef,
    WeekEvents =>  undef,
    WeekEventDiskSpace =>  undef,
    MonthEvents =>  undef,
    MonthEventDiskSpace =>  undef,
    ArchivedEvents =>  undef,
    ArchivedEventDiskSpace =>  undef,
    );

sub Monitor {
	return new ZoneMinder::Monitor( $_[0]{MonitorId} );
} # end sub Monitor

1;
__END__

=head1 NAME

ZoneMinder::Event_Summary - Perl Class for Event Summaries

=head1 SYNOPSIS

use ZoneMinder::Event_Summary;

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2017  ZoneMinder LLC

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
