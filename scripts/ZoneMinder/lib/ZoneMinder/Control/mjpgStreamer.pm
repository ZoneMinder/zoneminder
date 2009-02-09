# ==========================================================================
#
# ZoneMinder mjpg STreamer Control Protocol Module, $Date: 2007-11-04 17:30:29 +0000 (Sun, 04 Nov 2007) $, $Revision: 2229 $
# Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
# ==========================================================================
#
# This module contains the implementation of the mjpg streamer camera control
# protocol
#
package ZoneMinder::Control::mjpgStreamer;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# mjpgSTreamer Control Protocol
#
# ==========================================================================

use ZoneMinder::Debug qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );

sub new
{
    my $class = shift;
    my $id = shift;
    my $self = ZoneMinder::Control->new( $id );
    Debug( "Camera New" );
    bless( $self, $class );
    srand( time() );
    return $self;
}

our $AUTOLOAD;

sub AUTOLOAD
{
    my $self = shift;
    my $class = ref($self) || croak( "$self not object" );
    my $name = $AUTOLOAD;
    Debug( "Camera AUTOLOAD" );
    $name =~ s/.*://;
    if ( exists($self->{$name}) )
    {
        return( $self->{$name} );
    }
    Fatal( "Can't access $name member of object of class $class" );
}

sub open
{
    my $self = shift;

    $self->loadMonitor();
    Debug( "Camera open" );
    use LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new;
    $self->{ua}->agent( "ZoneMinder Control Agent/".ZM_VERSION );

    $self->{state} = 'open';
}

sub close
{
    my $self = shift;
    $self->{state} = 'closed';
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

    printMsg( $cmd, "Tx" );

    my $req = HTTP::Request->new( GET=>"http://".$self->{Monitor}->{ControlAddress}."/$cmd" );
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

sub Up
{
   my $self = shift;
   $self->moveConUp();	
}

sub Down
{
   my $self = shift;
   $self->moveConDown();	
}

sub Left
{
   my $self = shift;
   $self->moveConLeft();	
}

sub Right
{
   my $self = shift;
   $self->moveConRight();	
}


sub reset
{
   my $self = shift;
   $self->cameraReset();	
}



sub cameraReset
{
    my $self = shift;
    Debug( "Camera Reset" );
    my $cmd = "?action=command&command=reset_pan_tilt";
    $self->sendCmd( $cmd );
}

sub moveConUp
{
    my $self = shift;
    Debug( "Move Up" );
    my $cmd = "?action=command&command=tilt_minus";
    $self->sendCmd( $cmd );
}

sub moveConDown
{
    my $self = shift;
    Debug( "Move Down" );
    my $cmd = "?action=command&command=tilt_plus";
    $self->sendCmd( $cmd );
}

sub moveConLeft
{
    my $self = shift;
    Debug( "Move Left" );
    my $cmd = "?action=command&command=pan_plus";
    $self->sendCmd( $cmd );
}

sub moveConRight
{
    my $self = shift;
    Debug( "Move Right" );
    my $cmd = "?action=command&command=pan_minus";
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

Copyright (C) 2005 by Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
