# ==========================================================================
#
# ZoneMinder Common Module, $Date$, $Revision$
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
package ZoneMinder;

use 5.006;
use strict;
use warnings;

require Exporter;
use ZoneMinder::Base qw(:all);
use ZoneMinder::Config qw(:all);
use ZoneMinder::Logger qw(:all);
use ZoneMinder::General qw(:all);
use ZoneMinder::Database qw(:all);
use ZoneMinder::Memory qw(:all);

our @ISA = qw(
    Exporter
    ZoneMinder::Base
    ZoneMinder::Config
    ZoneMinder::Logger
    ZoneMinder::General
    ZoneMinder::Database
    ZoneMinder::Memory
);

# Items to export into callers namespace by default. Note: do not export
# names by default without a very good reason. Use EXPORT_OK instead.
# Do not simply export all your public functions/methods/constants.

# This allows declaration   use ZoneMinder ':all';
# If you do not need this, moving things directly into @EXPORT or @EXPORT_OK
# will save memory.
our %EXPORT_TAGS = (
    'base' => [ 
        @ZoneMinder::Base::EXPORT_OK
    ],
    'config' => [ 
        @ZoneMinder::Config::EXPORT_OK
    ],
    'debug' => [ 
        @ZoneMinder::Logger::EXPORT_OK
    ],
    'general' => [ 
        @ZoneMinder::General::EXPORT_OK
    ],
    'database' => [ 
        @ZoneMinder::Database::EXPORT_OK
    ],
    'memory' => [ 
        @ZoneMinder::Memory::EXPORT_OK
    ],
);
push( @{$EXPORT_TAGS{all}}, @{$EXPORT_TAGS{$_}} ) foreach keys %EXPORT_TAGS;

our @EXPORT_OK = @{ $EXPORT_TAGS{'all'} };

our @EXPORT = ( @EXPORT_OK );

our $VERSION = $ZoneMinder::Base::VERSION;

1;
__END__

=head1 NAME

ZoneMinder - Container module for common ZoneMinder modules

=head1 SYNOPSIS

  use ZoneMinder;

=head1 DESCRIPTION

This module is a convenience container module that uses the
ZoneMinder::Base, ZoneMinder::Common, ZoneMinder::Logger,
ZoneMinder::Database and ZoneMinder::Memory modules. It also
exports by default all symbols provided by the 'all' tag of 
each of the modules.

Thus 'use'ing this module is equivalent to the following 

  use ZoneMinder::Base qw(:all);
  use ZoneMinder::Config qw(:all);
  use ZoneMinder::Logger qw(:all);
  use ZoneMinder::Database qw(:all);
  use ZoneMinder::Memory qw(:all);

but is somewhat easier.

=head2 EXPORT

All symbols exported by the 'all' tag of each of the included
modules.

=head1 SEE ALSO

ZoneMinder::Base, ZoneMinder::Common, ZoneMinder::Logger,
ZoneMinder::Database, ZoneMinder::Memory

http://www.zoneminder.com

=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2005 by Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
