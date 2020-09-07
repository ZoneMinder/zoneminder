# ==========================================================================
#
# ZoneMinder D-Link DCS-3415 IP Control Protocol Module, 2018-03-04, 0.1
# Copyright (C) 2015-2018 Habib Kamei
#
# Modified for use with D-Link DCS-3415 IP Camera by Habib Kamei
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
# ==========================================================================
#
# This module contains the implementation of the D-Link DCS-3415 device control protocol
#
#===========================================================================

package ZoneMinder::Control::DCS3415;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# D-Link DCS-3415 Control Protocol
#
# On ControlAddress use the format :
#   USERNAME:PASSWORD@ADDRESS:PORT
#   eg : admin:@10.1.2.1:80
#        zoneuser:zonepass@192.168.0.20:40000
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

use Time::HiRes qw( usleep );

sub new
{
    my $class = shift;
    my $id = shift;
    my $self = ZoneMinder::Control->new( $id );
    bless( $self, $class );
    srand( time() );
    return $self;
}

our $AUTOLOAD;

sub AUTOLOAD
{
    my $self = shift;
    my $class = ref( ) || croak( "$self not object" );
    my $name = $AUTOLOAD;
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
    my $result = undef;
    printMsg( $cmd, "Tx" );

    my $req = HTTP::Request->new( GET=>"http://".$self->{Monitor}->{ControlAddress}."/cgi-bin/viewer/$cmd" );
    my $res = $self->{ua}->request($req);


    if ( $res->is_success )
    {
        $result = !undef;
    }
    else
    {
        Error( "Error check failed:'".$res->status_line()."'" );
    }

    return( $result );
}

#Zoom In
sub Tele
{
    my $self = shift;
    Debug( "Zoom Tele" );
    my $cmd = "camctrl.cgi?channel=0&camid=1&zoom=tele";
    $self->sendCmd( $cmd );
}

#Zoom Out
sub Wide
{
    my $self = shift;
    Debug( "Zoom Wide" );
    my $cmd = "camctrl.cgi?channel=0&camid=1&zoom=wide";
    $self->sendCmd( $cmd );
}

#Focus Near
sub Near
{
    my $self = shift;
    Debug( "Focus Near" );
    my $cmd = "camctrl.cgi?channel=0&camid=1&focus=near";
    $self->sendCmd( $cmd );
}

#Focus Far
sub Far
{
    my $self = shift;
    Debug( "Focus Far" );
    my $cmd = "camctrl.cgi?channel=0&camid=1&focus=far";
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
This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.
=cut
