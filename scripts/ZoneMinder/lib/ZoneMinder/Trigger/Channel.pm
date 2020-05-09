# ==========================================================================
#
# ZoneMinder Trigger Channel Module, $Date$, $Revision$
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
# This module contains the base class definition of the trigger channel
# class tree
#
package ZoneMinder::Trigger::Channel;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Database Access
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);

use Carp;

our $AUTOLOAD;

sub new
{
    my $class = shift;
    my $self = {};
    $self->{readable} = !undef;
    $self->{writeable} = !undef;
    $self->{selectable} = undef;
    $self->{state} = 'closed';
    bless( $self, $class );
    return $self;
}

sub clone
{
    my $self = shift;
    my $clone = { %$self };
    bless $clone, ref $self;
}

sub open
{
    my $self = shift;
    my $class = ref($self) or croak( "Can't get class for non object $self" );
    croak( "Abstract base class method called for object of class $class" );
}

sub close
{
    my $self = shift;
    my $class = ref($self) or croak( "Can't get class for non object $self" );
    croak( "Abstract base class method called for object of class $class" );
}

sub getState
{
    my $self = shift;
    return( $self->{state} );
}

sub isOpen
{
    my $self = shift;
    return( $self->{state} eq "open" );
}

sub isConnected
{
    my $self = shift;
    return( $self->{state} eq "connected" );
}

sub DESTROY
{
}

sub AUTOLOAD
{
    my $self = shift;
    my $class = ref($self) || croak( "$self not object" );
    my $name = $AUTOLOAD;
    $name =~ s/.*://;
    if ( !exists($self->{$name}) )
    {
        croak( "Can't access $name member of object of class $class" );
    }
    return( $self->{$name} );
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Database - Perl extension for blah blah blah

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
