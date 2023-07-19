# ==========================================================================
#
# ZoneMinder GPS Reading Module
# Copyright (C) 2023 ZoneMinder Inc
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
 
package ZoneMinder::GPSReading;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Object;

use parent qw(ZoneMinder::Object);

use vars qw/ $table $primary_key %fields %defaults /;
$table = 'GPSReading';
$primary_key = 'Id';

%fields = (
	Id      => 'Id',
	ObjectId => 'ObjectId',
	ObjectTypeId => 'ObjectTypeId',
	TimeStamp         => 'TimeStamp',
	Latitude          => 'Latitude',
	Longitude         => 'Longitude'
	Accuracy          => 'Accuracy',
	Altitude          => 'Altitude',
	AltitudeAccuracy  => 'AltitudeAccuracy',
	Heading           => 'Heading',
	Speed             => 'Speed',
);
%defaults => (
	'ObjectId'          => undef,
	'ObjectTypeId'      => undef,
	'TimeStamp'         => 0,
	'Latitude'          => '',
	'Longitude'         => '',
	'Accuracy'          => undef,
	'Altitude'          => undef,
    'AltitudeAccuracy'  => undef,
    'Heading'           => undef,
    'Speed'             => undef,
  );

1;
__END__

=head1 NAME

ZoneMinder::GPSReading - Perl Class for GPSReadings

=head1 SYNOPSIS

use ZoneMinder::GPSReading;

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2017  ZoneMinder LLC

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
