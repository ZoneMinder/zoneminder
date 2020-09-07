# ==========================================================================
#
# ZoneMinder Vivotek ePTZ Control Protocol Module
# Copyright (C) 2015 Robin Daermann
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
# This module contains the implementation of the Vivotek ePTZ camera control
# protocol
#
package ZoneMinder::Control::Vivotek_ePTZ;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Vivotek ePTZ Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );

sub open
{
    my $self = shift;

    $self->loadMonitor();
    Debug( "Camera open" );
    use LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new;
    $self->{ua}->agent( "ZoneMinder Control Agent/".ZoneMinder::Base::ZM_VERSION );

    $self->{state} = 'open';
}

sub close
{
    my $self = shift;
    $self->{state} = 'closed';
}

sub printMsg
{
    my $msg = shift;
    my $msg_len = length($msg);

    Debug( $msg."[".$msg_len."]" );
}

sub sendCmd
{
    my ($self, $cmd, $speedcmd) = @_;

    my $result = undef;

    printMsg( $speedcmd, "Tx" );
    printMsg( $cmd, "Tx" );

    my $req = HTTP::Request->new( GET => "http://" . $self->{Monitor}->{ControlAddress} . "/cgi-bin/camctrl/eCamCtrl.cgi?stream=0&$speedcmd&$cmd" );
    my $res = $self->{ua}->request($req);

    if ( $res->is_success )
    {
        $result = !undef;
    }
    else
    {
        Error( "Request failed: '" . $res->status_line() . "' (URI: '" . $req->as_string() . "')" );
    }

    return( $result );
}

sub moveConUp
{
    my ($self, $params) = @_;
    my $speed = 'speedtilt=' . ($params->{tiltspeed} - 6);
    Debug( "Move Up" );
    $self->sendCmd( 'move=up', $speed );
}

sub moveConDown
{
    my ($self, $params) = @_;
    my $speed = 'speedtilt=' . ($params->{tiltspeed} - 6);
    Debug( "Move Down" );
    $self->sendCmd( 'move=down', $speed );
}

sub moveConLeft
{
    my ($self, $params) = @_;
    my $speed = 'speedpan=-' . $params->{panspeed};
    Debug( "Move Left" );
    $self->sendCmd( 'move=left', $speed );
}

sub moveConRight
{
    my ($self, $params) = @_;
    my $speed = 'speedpan=' . ($params->{panspeed} - 6);
    Debug( "Move Right" );
    $self->sendCmd( 'move=right', $speed );
}

sub moveStop
{
    my $self = shift;
    Debug( "Move Stop" );
    # not implemented
}

sub zoomConTele
{
    my ($self, $params) = @_;
    my $speed = 'speedzoom=' . ($params->{speed} - 6);
    Debug( "Zoom In" );
    $self->sendCmd( 'zoom=tele', $speed );
}

sub zoomConWide
{
    my ($self, $params) = @_;
    my $speed = 'speedzoom=' . ($params->{speed} - 6);
    Debug( "Zoom Out" );
    $self->sendCmd( 'zoom=wide', $speed );
}

sub reset
{
    my $self = shift;
    Debug( "Camera Reset" );
    $self->sendCmd( 'move=home' );
}

1;
__END__

=head1 NAME

ZoneMinder::Control::Vivotek_ePTZ - ZoneMinder Perl extension for Vivotek ePTZ
camera control protocol

=head1 SYNOPSIS

  use ZoneMinder::Control::Vivotek_ePTZ;

=head1 DESCRIPTION

This module implements the ePTZ protocol used in various Vivotek IP cameras,
developed with a Vivotek IB8369 model.

Currently, only simple pan, tilt and zoom function is implemented. Presets will
follow later.

=head2 EXPORT

None.

=head1 SEE ALSO

I would say, see ZoneMinder::Control documentation. But it is a stub.

=head1 AUTHOR

Robin Daermann E<lt>r.daermann@ids-services.deE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2015 by Robin Daermann

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.

=cut
