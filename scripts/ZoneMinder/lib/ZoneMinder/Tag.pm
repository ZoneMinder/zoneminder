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
package ZoneMinder::Tag;

use 5.006;
use strict;
use warnings;
use Time::HiRes qw(usleep);

require ZoneMinder::Base;
require ZoneMinder::Object;
use ZoneMinder::Logger qw(:all);

use parent qw(ZoneMinder::Object);

use vars qw/ $table $primary_key %fields $serial %defaults $debug %transforms/;
$table = 'Tags';
$serial = $primary_key = 'Id';
%fields = map { $_ => $_ } qw(
  Id
  Name
  CreateDate
  CreatedBy
  LastAssignedDate
  );

%defaults = (
    );
%transforms = (
  Name          =>  [ 's/\s+//' ],
);


1;
__END__

=head1 NAME

ZoneMinder::Tag - Perl Class for Tags

=head1 SYNOPSIS

use ZoneMinder::Tag;

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2022  ZoneMinder Inc

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
