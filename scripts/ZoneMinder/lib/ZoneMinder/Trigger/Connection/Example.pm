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
# This module contains an example overridden trigger connection class
#
package ZoneMinder::Trigger::Connection::Example;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Trigger::Connection;

our @ISA = qw(ZoneMinder::Trigger::Connection);

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Example overridden connection class
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);

sub new
{
    my $class = shift;
    my $path = shift;
    my $self = ZoneMinder::Trigger::Connection->new( @_ );
    bless( $self, $class );
    return $self;
}

sub getMessages
{
    my $self = shift;
    my $buffer = $self->{channel}->read();

    return( undef ) if ( !defined($buffer) );

    Debug( "Handling buffer '$buffer'\n" );
    my @messages = grep { s/-/|/g; 1; } split( /\r?\n/, $buffer );
    return( \@messages );
}

sub putMessages
{
    my $self = shift;
    my $messages = shift;

    if ( @$messages )
    {
        my $buffer = join( "\n", grep{ s/\|/-/; 1; } @$messages );
        $buffer .= "\n";
        if ( !$self->{channel}->write( $buffer ) )
        {
            Error( "Unable to write buffer '".$buffer." to connection ".$self->{name}." (".$self->fileno().")\n" );
        }
    }
    return( undef );
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
