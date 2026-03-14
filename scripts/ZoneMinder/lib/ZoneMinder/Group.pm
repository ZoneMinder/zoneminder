# ==========================================================================
#
# ZoneMinder Group Module
# Copyright (C) 2025 ZoneMinder Inc.
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

package ZoneMinder::Group;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Object;
require ZoneMinder::General;

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

use vars qw/ $serial $table $primary_key %fields $debug/;
$serial = $primary_key = 'Id';
$debug = 1;
$table = 'Groups';
%fields = map { $_ => $_ } qw( Id Name ParentId);

1;
__END__

=head1 NAME

ZoneMinder::Group - Perl modules for Group objects

=head1 SYNOPSIS

  use ZoneMinder::Group;
  my $Group = ZoneMinder::Group->find_one(Name=>'Default');
  etc...

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

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2022 ZoneMinder Inc

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
