# ==========================================================================
#
# ZoneMinder Trigger Channel Handle Module, $Date$, $Revision$
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
# This module contains the class definition of the simple file based trigger
# channel class
#
package ZoneMinder::Trigger::Channel::File;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Trigger::Channel::Handle;

our @ISA = qw(ZoneMinder::Trigger::Channel::Handle);

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Simple file based trigger channel
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);

use Carp;
use Fcntl;

sub new
{
    my $class = shift;
    my %params = @_;
    my $self = ZoneMinder::Trigger::Channel::Handle->new;
    $self->{path} = $params{path};
    bless( $self, $class );
    return $self;
}

sub open
{
    my $self = shift;
    local *sfh;
    #sysopen( *sfh, $conn->{path}, O_NONBLOCK|O_RDONLY ) or croak( "Can't sysopen: $!" );
    #open( *sfh, "<".$conn->{path} ) or croak( "Can't open: $!" );
    if ( ! open( *sfh, "+<", $self->{path} ) ) {
		Error( "Can't open file at $$self{path}: $!" );
		croak( "Can't open file at $$self{path}: $!" );
	}
    $self->{state} = 'open';
    $self->{handle} = *sfh;
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Trigger::Channel::File - ZOneMinder object for a file based trigger channel

=head1 SYNOPSIS

  use ZoneMinder::Database;
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
