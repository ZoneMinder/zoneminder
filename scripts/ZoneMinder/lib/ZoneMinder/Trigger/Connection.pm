# ==========================================================================
#
# ZoneMinder Trigger Connection Module, $Date$, $Revision$
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
# This module contains the base class definition of the trigger connection
# class tree
#
package ZoneMinder::Trigger::Connection;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Base connection class
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);

use Carp;

our $AUTOLOAD;

sub new
{
    my $class = shift;
    my %params = @_;
    my $self = {};
    $self->{name} = $params{name};
    $self->{channel} = $params{channel};
    $self->{input} = $params{mode} =~ /r/i;
    $self->{output} = $params{mode} =~ /w/i;
    bless( $self, $class );
    return $self;
}

sub clone
{
    my $self = shift;
    my $clone = { %$self };
    bless $clone, ref $self;
    return( $clone );
}

sub spawns
{
    my $self = shift;
    return( $self->{channel}->spawns() );
}

sub _spawn
{
    my $self = shift;
    my $new_channel = shift;
    my $clone = $self->clone();
    $clone->{channel} = $new_channel;
    return( $clone );
}

sub accept
{
    my $self = shift;
    my $new_channel = $self->{channel}->accept();
    return( $self->_spawn( $new_channel ) );
}

sub open
{
    my $self = shift;
    return( $self->{channel}->open() );
}

sub close
{
    my $self = shift;
    return( $self->{channel}->close() );
}

sub fileno
{
    my $self = shift;
    return( $self->{channel}->fileno() );
}

sub isOpen
{
    my $self = shift;
    return( $self->{channel}->isOpen() );
}

sub isConnected
{
    my $self = shift;
    return( $self->{channel}->isConnected() );
}

sub canRead
{
    my $self = shift;
    return( $self->{input} && $self->isConnected() );
}

sub canWrite
{
    my $self = shift;
    return( $self->{output} && $self->isConnected() );
}

sub getMessages
{
    my $self = shift;
    my $buffer = $self->{channel}->read();

    return( undef ) if ( !defined($buffer) );

    my @messages = split( /\r?\n/, $buffer );
    return( \@messages );
}

sub putMessages
{
    my $self = shift;
    my $messages = shift;

    if ( @$messages )
    {
        my $buffer = join( "\n", @$messages );
        $buffer .= "\n";
        if ( !$self->{channel}->write( $buffer ) )
        {
            Error( "Unable to write buffer '".$buffer
                  ." to connection "
                  .$self->{name}
                  ." ("
                  .$self->fileno()
                  .")\n"
            );
        }
    }
    return( undef );
}

sub timedActions
{
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
    if ( exists($self->{$name}) )
    {
        return( $self->{$name} );
    }
    elsif ( defined($self->{channel}) )
    {
        if ( exists($self->{channel}->{$name}) )
        {
            return( $self->{channel}->{$name} );
        }
    }
    croak( "Can't access $name member of object of class $class" );
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
