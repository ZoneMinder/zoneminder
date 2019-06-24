# ==========================================================================
#
# ZoneMinder Maginon Supra IPC Camera Control Protocol Module,
# Copyright (C) 2017  Martin Gutenbrunner (martin.gutenbrunner@SPAMsonic.net)
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
# This module contains the implementation of the Maginon Supra IPC camera
# procotol version.
#
package ZoneMinder::Control::MaginonIPC;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
#   Maginon Supra IPC IP Camera Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );

sub open
{
    my $self = shift;

    $self->loadMonitor();

    use LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new;
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

    #my $result = undef;

    printMsg( $cmd, "Tx" );

    my $url = "http://".$self->{Monitor}->{ControlAddress}."$cmd";
#    Info($url);
    my $req = HTTP::Request->new( GET=>$url );
    my $res = $self->{ua}->request($req);
    return( !undef );
}

sub moveStop
{
   Debug("moveStop");
   my $self = shift;
   my $params = shift;
   my $cmd = "/decoder_control.cgi?command=1";
   $self->sendCmd( $cmd );
}

sub moveConUp
{
   Debug("moveConUp");
   my $self = shift;
   my $params = shift;
   my $cmd = "/decoder_control.cgi?command=0";
   $self->sendCmd( $cmd );
   my $autostop = $self->getParam( $params, 'autostop', 0 );
   if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
   {
      usleep( $self->{Monitor}->{AutoStopTimeout} );
      $self->moveStop( $params );
   }
}

sub moveConDown
{
   Debug("moveConDown");
   my $self = shift;
   my $params = shift;
   my $cmd = "/decoder_control.cgi?command=2";
   $self->sendCmd( $cmd );
   my $autostop = $self->getParam( $params, 'autostop', 0 );
   if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
   {
      usleep( $self->{Monitor}->{AutoStopTimeout} );
      $self->moveStop( $params );
   }
}

sub moveConLeft
{
   Debug("moveConLeft");
   my $self = shift;
   my $params = shift;
   my $cmd = "/decoder_control.cgi?command=4";
   $self->sendCmd( $cmd );
   my $autostop = $self->getParam( $params, 'autostop', 0 );
   if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
   {
      usleep( $self->{Monitor}->{AutoStopTimeout} );
      $self->moveStop( $params );
   }
}

sub moveConRight
{
   Debug("moveConRight");
   my $self = shift;
   my $params = shift;
   my $cmd = "/decoder_control.cgi?command=6";
   $self->sendCmd( $cmd );
   my $autostop = $self->getParam( $params, 'autostop', 0 );
   if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
   {
      usleep( $self->{Monitor}->{AutoStopTimeout} );
      $self->moveStop( $params );
   }
}

sub moveConUpRight
{
   Debug("moveConUpRight");
   my $self = shift;
   my $params = shift;
   my $cmd = "/decoder_control.cgi?command=91";
   $self->sendCmd( $cmd );
   my $autostop = $self->getParam( $params, 'autostop', 0 );
   if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
   {
      usleep( $self->{Monitor}->{AutoStopTimeout} );
      $self->moveStop( $params );
   }
}

sub moveConUpLeft
{
   Debug("moveConUpLeft");
   my $self = shift;
   my $params = shift;
   my $cmd = "/decoder_control.cgi?command=90";
   $self->sendCmd( $cmd );
   my $autostop = $self->getParam( $params, 'autostop', 0 );
   if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
   {
      usleep( $self->{Monitor}->{AutoStopTimeout} );
      $self->moveStop( $params );
   }
}

sub moveConDownRight
{
   Debug("moveConDownRight");
   my $self = shift;
   my $params = shift;
   my $cmd = "/decoder_control.cgi?command=93";
   $self->sendCmd( $cmd );
   my $autostop = $self->getParam( $params, 'autostop', 0 );
   if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
   {
      usleep( $self->{Monitor}->{AutoStopTimeout} );
      $self->moveStop( $params );
   }
}

sub moveConDownLeft
{
   Debug("moveConDownLeft");
   my $self = shift;
   my $params = shift;
   my $cmd = "/decoder_control.cgi?command=92";
   $self->sendCmd( $cmd );
   my $autostop = $self->getParam( $params, 'autostop', 0 );
   if ( $autostop && $self->{Monitor}->{AutoStopTimeout} )
   {
      usleep( $self->{Monitor}->{AutoStopTimeout} );
      $self->moveStop( $params );
   }
}

sub presetSet
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Info( "Set Preset $preset" );

    my $cmdNum;
    if ($preset == 1) {
      $cmdNum = 30;
    } elsif ($preset == 2) {
      $cmdNum = 32;
    } elsif ($preset == 3) {
      $cmdNum = 34;
    } elsif ($preset == 4) {
      $cmdNum = 36;
    } else {
      $cmdNum = 36;
    }

    my $cmd = "/decoder_control.cgi?command=$cmdNum";
    $self->sendCmd( $cmd );
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset = $self->getParam( $params, 'preset' );
    Info( "Goto Preset $preset" );

    my $cmdNum;
    if ($preset == 1) {
      $cmdNum = 31;
    } elsif ($preset == 2) {
      $cmdNum = 33;
    } elsif ($preset == 3) {
      $cmdNum = 35;
    } elsif ($preset == 4) {
      $cmdNum = 37;
    } else {
      $cmdNum = 37;
    }

    my $cmd = "/decoder_control.cgi?command=$cmdNum";
    $self->sendCmd( $cmd );
}

1;
__END__
# Below is stub documentation for your module. You'd better edit it!

=head1 NAME

ZoneMinder::Control::MaginonIPC - Zoneminder PTZ control module for the Maginon Supra-IPC 40 IP Camera

=head1 SYNOPSIS

  use ZoneMinder::Control::MaginonIPC;
  blah blah blah

=head1 DESCRIPTION

   This is for Zoneminder PTZ control module for the Maginon Supra-IPC 40 camera. It probably also works with other models.

=head2 EXPORT

None by default.



=head1 SEE ALSO

www.zoneminder.com

=head1 AUTHOR

Martin Gutenbrunner, E<lt>martin.gutenbrunner@gmx.atE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2017 by Martin Gutenbrunner

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
