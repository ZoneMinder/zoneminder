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
# This module contains the class definition of the serial port trigger channel
# class
#
package ZoneMinder::Trigger::Channel::Serial;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Trigger::Channel;

our @ISA = qw(ZoneMinder::Trigger::Channel);

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Serial access trigger channel
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use Device::SerialPort;

sub new
{
    my $class = shift;
    my %params = @_;
    my $self = ZoneMinder::Trigger::Channel->new;
    $self->{path} = $params{path};
    bless( $self, $class );
    return $self;
}

sub open
{
    my $self = shift;
    my $device = new Device::SerialPort( $self->{path} );
    if ( ! $device )
    {
        Error( "Unable to open $$self{path}: $!" );
        $self->{state} = 'closed';
        return;
    }
    $device->baudrate(9600);
    $device->databits(8);
    $device->parity('none');
    $device->stopbits(1);
    $device->handshake('none');

    $device->read_const_time(50);
    $device->read_char_time(10);

    $self->{device} = $device;
    $self->{state} = 'open';
    $self->{state} = 'connected';
}

sub close
{
    my $self = shift;
    $self->{device}->close();
    $self->{state} = 'closed';
}

sub read
{
    my $self = shift;
    my $buffer = $self->{device}->lookfor();
    if ( !$buffer || !length($buffer) )
    {
        return( undef );
    }
    Debug( "Read '$buffer' (".length($buffer)." bytes)\n" );
    return( $buffer );
}

sub write
{
    my $self = shift;
    my $buffer = shift;
    my $nbytes = $self->{device}->write( $buffer );
    $self->{device}->write_drain();
    if ( !defined( $nbytes) || $nbytes < length($buffer) )
    {
        Error( "Unable to write buffer '".$buffer
               .", expected "
               .length($buffer)
               ." bytes, sent "
               .$nbytes
               .": $!\n"
        );
        return( undef );
    }
    Debug( "Wrote '$buffer' ($nbytes bytes)\n" );
    return( !undef );
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
