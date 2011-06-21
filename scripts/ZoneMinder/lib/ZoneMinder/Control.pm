# ==========================================================================
#
# ZoneMinder Base Control Module, $Date$, $Revision$
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
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
# ==========================================================================
#
# This module contains the base class definitions for the camera control 
# protocol implementations
#
package ZoneMinder::Control;

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
use ZoneMinder::Database qw(:all);

our $AUTOLOAD;

sub new
{
	my $class = shift;
	my $id = shift;
	my $self = {};
	$self->{name} = "PelcoD";
    if ( !defined($id) )
    {
        Fatal( "No monitor defined when invoking protocol ".$self->{name} );
    }
	$self->{id} = $id;
	bless( $self, $class );
	return $self;
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
	croak( "Can't access $name member of object of class $class" );
}

sub getKey()
{
	my $self = shift;
    return( $self->{id} );
}

sub open
{
	my $self = shift;
    Fatal( "No open method defined for protocol ".$self->{name} );
}

sub close
{
	my $self = shift;
    Fatal( "No close method defined for protocol ".$self->{name} );
}

sub loadMonitor
{
    my $self = shift;
    if ( !$self->{Monitor} )
    {
        if ( !($self->{Monitor} = zmDbGetMonitor( $self->{id} )) )
        {
            Fatal( "Monitor id ".$self->{id}." not found or not controllable" );
        }
        if ( defined($self->{Monitor}->{AutoStopTimeout}) )
        {
            # Convert to microseconds.
            $self->{Monitor}->{AutoStopTimeout} = int(1000000*$self->{Monitor}->{AutoStopTimeout});
        }
    }
}

sub getParam
{
    my $self = shift;
    my $params = shift;
    my $name = shift;
    my $default = shift;

    if ( defined($params->{$name}) )
    {
        return( $params->{$name} );
    }
    elsif ( defined($default) )
    {
        return( $default );
    }
    Fatal( "Missing mandatory parameter '$name'" );
}

sub executeCommand
{
    my $self = shift;
    my $params = shift;

    $self->loadMonitor();

    my $command = $params->{command};
    delete $params->{command};

    #if ( !defined($self->{$command}) )
    #{
        #Fatal( "Unsupported command '$command'" );
    #}
    &{$self->{$command}}( $self, $params );
}

sub printMsg()
{
	my $self = shift;
    Fatal( "No printMsg method defined for protocol ".$self->{name} );
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
