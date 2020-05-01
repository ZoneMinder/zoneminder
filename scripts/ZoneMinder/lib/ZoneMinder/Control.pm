# ==========================================================================
#
# ZoneMinder Base Control Module
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

sub new {
  my $class = shift;
  my $id = shift;
  if ( !defined($id) ) {
    Fatal('No monitor defined when invoking protocol '.$class);
  }
  my $self = {};
  $self->{name} = $class;
  $self->{id} = $id;
  bless($self, $class);
  return $self;
}

sub DESTROY {
}

sub AUTOLOAD {
  my $self = shift;
  my $class = ref($self);
  if ( !$class ) {
    my ( $caller, undef, $line ) = caller;
    Fatal("$self not object from $caller:$line");
  }

  my $name = $AUTOLOAD;
  $name =~ s/.*://;
  if ( exists($self->{$name}) ) {
    return $self->{$name};
  }
  my ( $caller, undef, $line ) = caller;
  Error("Can't access name:$name AUTOLOAD:$AUTOLOAD member of object of class $class from $caller:$line");
}

sub getKey {
  my $self = shift;
  return $self->{id};
}

sub open {
  my $self = shift;
  Fatal('No open method defined for protocol '.$self->{name});
}

sub close {
  my $self = shift;
  $self->{state} = 'closed';
  Debug('No close method defined for protocol '.$self->{name});
}

sub loadMonitor {
  my $self = shift;
  if ( !$self->{Monitor} ) {
    if ( !($self->{Monitor} = zmDbGetMonitor($self->{id})) ) {
      Fatal('Monitor id '.$self->{id}.' not found or not controllable');
    }
    if ( defined($self->{Monitor}->{AutoStopTimeout}) ) {
# Convert to microseconds.
      $self->{Monitor}->{AutoStopTimeout} = int(1000000*$self->{Monitor}->{AutoStopTimeout});
    }
  }
}

sub getParam {
  my $self = shift;
  my $params = shift;
  my $name = shift;
  my $default = shift;

  if ( defined($params->{$name}) ) {
    return $params->{$name};
  } elsif ( defined($default) ) {
    return $default;
  }
  Fatal("Missing mandatory parameter '$name'");
}

sub executeCommand {
  my $self = shift;
  my $params = shift;

  $self->loadMonitor();

  my $command = $params->{command};
  delete $params->{command};

#if ( !defined($self->{$command}) )
#{
#Fatal( "Unsupported command '$command'" );
#}
  &{$self->{$command}}($self, $params);
}

sub printMsg {
  my $self = shift;
  my $msg = shift;
  my $msg_len = length($msg);

  Debug($msg.'['.$msg_len.']');
}

1;
__END__

=head1 NAME

ZoneMinder::Control - Parent class defining Control API

=head1 SYNOPSIS

use ZoneMinder::Control;

This should be used as the parent class for packages implementing control
apis for various cameras.

=head1 DESCRIPTION



=head2 EXPORT

None by default.


=head1 AUTHOR

Philip Coombes, E<lt>philip.coombes@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008  Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
