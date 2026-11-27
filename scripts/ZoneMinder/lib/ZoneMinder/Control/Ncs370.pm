# ==========================================================================
#
# ZoneMinder Neu-Fusion Control Protocol Module, $Date$, $Revision$
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
# This module contains the implementation of the Neu-Fusion NCS370 IP camera
# control protocol
#
package ZoneMinder::Control::Ncs370;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Ncs370 IP Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

# Seconds. The camera answers in milliseconds when it is there at all, so this
# only ever bounds the case where it is not.
use constant HTTP_TIMEOUT => 10;

use Time::HiRes qw( usleep );

sub open
{
    my $self = shift;

    $self->loadMonitor();

    use LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new(timeout => HTTP_TIMEOUT);
    $self->{ua}->agent( "ZoneMinder Control Agent/".ZoneMinder::Base::ZM_VERSION );

    $self->{state} = 'open';
}

sub printMsg
{
    my $self = shift;
    my $msg = shift;
    my $msg_len = length($msg);

    Debug( $msg."[".$msg_len."]" );
}

sub sendCmd
{
    my $self = shift;
    my $cmd = shift;

    my $result = undef;

    $self->printMsg( $cmd, "Tx" );

    my $req = HTTP::Request->new( POST=>"http://".$self->{Monitor}->{ControlAddress}."/PANTILTCONTROL.CGI" );
    $req->content_type( "application/x-www-form-urlencoded" );
    $req->content( $cmd );
    my $res = $self->{ua}->request($req);

    if ( $res->is_success )
    {
        $result = !undef;
    }
    else
    {
        Error( "Error check failed: '".$res->status_line()."'" );
    }

    return( $result );
}

sub moveConUp
{
    my $self = shift;
    Debug( "Move Up" );
    my $cmd = "PanSingleMoveDegree=1&TiltSingleMoveDegree=1&PanTiltSingleMove=1";
    $self->sendCmd( $cmd );
}

sub moveConDown
{
    my $self = shift;
    Debug( "Move Down" );
    my $cmd = "PanSingleMoveDegree=1&TiltSingleMoveDegree=1&PanTiltSingleMove=7";
    $self->sendCmd( $cmd );
}

sub moveConLeft
{
    my $self = shift;
    Debug( "Move Left" );
    my $cmd = "PanSingleMoveDegree=1&TiltSingleMoveDegree=1&PanTiltSingleMove=3";
    $self->sendCmd( $cmd );
}

sub moveConRight
{
    my $self = shift;
    Debug( "Move Right" );
    my $cmd = "PanSingleMoveDegree=1&TiltSingleMoveDegree=1&PanTiltSingleMove=5";
    $self->sendCmd( $cmd );
}

sub moveConUpRight
{
    my $self = shift;
    $self->moveConUp();
    $self->moveConRight();
}

sub moveConUpLeft
{
    my $self = shift;
    $self->moveConUp();
    $self->moveConLeft();
}

sub moveConDownRight
{
    my $self = shift;
    $self->moveConDown();
    $self->moveConRight();
}

sub moveConDownLeft
{
    my $self = shift;
    $self->moveConDown();
    $self->moveConLeft();
}

sub presetHome
{
    my $self = shift;
    Debug( "Home Preset" );
    my $cmd = "PanSingleMoveDegree=1&TiltSingleMoveDegree=1&PanTiltSingleMove=4";
    $self->sendCmd( $cmd );
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset', 1 );
    Debug( "Goto Preset $preset" );
    my $cmd = "PanTiltPresetPositionMove=$preset";
    $self->sendCmd( $cmd );
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

Licensed under the GNU General Public License v2 or later; see the COPYING
file distributed with ZoneMinder for the full text.


=cut
