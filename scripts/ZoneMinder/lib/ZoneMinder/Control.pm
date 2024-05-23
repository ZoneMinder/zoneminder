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
require ZoneMinder::Object;
require ZoneMinder::Monitor;

our $VERSION = $ZoneMinder::Base::VERSION;

# ==========================================================================
#
# Base control class
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Database qw(:all);

use parent qw(ZoneMinder::Object);

use vars qw/ $table $primary_key %fields $serial %defaults $debug/;
$table = 'Controls';
$serial = $primary_key = 'Id';
%fields = map { $_ => $_ } qw(
  Id
  Name
  Type
  Protocol
  CanWake
  CanSleep
  CanReset
  CanReboot
  CanZoom
  CanAutoZoom
  CanZoomAbs
  CanZoomRel
  CanZoomCon
  MinZoomRange
  MaxZoomRange
  MinZoomStep
  MaxZoomStep
  HasZoomSpeed
  MinZoomSpeed
  MaxZoomSpeed
  CanFocus
  CanAutoFocus
  CanFocusAbs
  CanFocusRel
  CanFocusCon
  MinFocusRange
  MaxFocusRange
  MinFocusStep
  MaxFocusStep
  HasFocusSpeed
  MinFocusSpeed
  MaxFocusSpeed
  CanIris
  CanAutoIris
  CanIrisAbs
  CanIrisRel
  CanIrisCon
  MinIrisRange
  MaxIrisRange
  MinIrisStep
  MaxIrisStep
  HasIrisSpeed
  MinIrisSpeed
  MaxIrisSpeed
  CanGain
  CanAutoGain
  CanGainAbs
  CanGainRel
  CanGainCon
  MinGainRange
  MaxGainRange
  MinGainStep
  MaxGainStep
  HasGainSpeed
  MinGainSpeed
  MaxGainSpeed
  CanWhite
  CanAutoWhite
  CanWhiteAbs
  CanWhiteRel
  CanWhiteCon
  MinWhiteRange
  MaxWhiteRange
  MinWhiteStep
  MaxWhiteStep
  HasWhiteSpeed
  MinWhiteSpeed
  MaxWhiteSpeed
  HasPresets
  NumPresets
  HasHomePreset
  CanSetPresets
  CanMove
  CanMoveDiag
  CanMoveMap
  CanMoveAbs
  CanMoveRel
  CanMoveCon
  CanPan
  MinPanRange
  MaxPanRange
  MinPanStep
  MaxPanStep
  HasPanSpeed
  MinPanSpeed
  MaxPanSpeed
  HasTurboPan
  TurboPanSpeed
  CanTilt
  MinTiltRange
  MaxTiltRange
  MinTiltStep
  MaxTiltStep
  HasTiltSpeed
  MinTiltSpeed
  MaxTiltSpeed
  HasTurboTilt
  TurboTiltSpeed
  CanAutoScan
  NumScanPaths
  );
%defaults = (
	Name => '',
    Type => q`'Ffmpeg'`,
    CanWake => '0',
    CanSleep => '0',
    CanReset => '0',
    CanReboot => '0',
    CanZoom => '0',
    CanAutoZoom => '0',
    CanZoomAbs => '0',
    CanZoomRel =>'0',
    CanZoomCon => '0',
    MinZoomRange  => undef,
    MaxZoomRange  => undef,
    MinZoomStep   => undef,
    MaxZoomStep => undef,
    HasZoomSpeed  => 0,
    MinZoomSpeed  => 0,
    MaxZoomSpeed => 0,
    CanFocus  => 0,
    CanAutoFocus  => 0,
    CanFocusAbs => 0,
    CanFocusRel => 0,
    CanFocusCon   => 0,
    MinFocusRange => undef,
    MaxFocusRange => undef,
    MinFocusStep  => undef,
    MaxFocusStep => undef,
    HasFocusSpeed => 0,
    MinFocusSpeed => undef,
    MaxFocusSpeed => undef,
    CanIris => 0,
    CanAutoIris => 0,
    CanIrisAbs=> 0,
    CanIrisRel => 0,
    CanIrisCon => 0,
    MinIrisRange => undef,
    MaxIrisRange => undef,
    MinIrisStep => undef,
    MaxIrisStep => undef,
    HasIrisSpeed => 0,
    MinIrisSpeed => undef,
    MaxIrisSpeed => undef,
    CanGain => 0,
    CanAutoGain => 0,
    CanGainAbs => 0,
    CanGainRel => 0,
    CanGainCon => 0,
    MinGainRange  => undef,
    MaxGainRange  => undef,
    MinGainStep => undef,
    MaxGainStep => undef,
    HasGainSpeed => 0,
    MinGainSpeed => undef,
    MaxGainSpeed => undef,
    CanWhite => 0,
    CanAutoWhite => 0,
    CanWhiteAbs => 0,
    CanWhiteRel => 0,
    CanWhiteCon => 0,
    MinWhiteRange => undef,
    MaxWhiteRange => undef,
    MinWhiteStep => undef,
    MaxWhiteStep => undef,
    HasWhiteSpeed => 0,
    MinWhiteSpeed => undef,
    MaxWhiteSpeed => undef,
    HasPresets => 0,
    NumPresets => 0,
    HasHomePreset => 0,
    CanSetPresets => 0,
    CanMove => 0,
    CanMoveDiag => 0,
    CanMoveMap => 0,
    CanMoveAbs => 0,
    CanMoveRel => 0,
    CanMoveCon => 0,
    CanPan => 0,
    MinPanRange => undef,
    MaxPanRange => undef,
    MinPanStep => undef,
    MaxPanStep => undef,
    HasPanSpeed => 0,
    MinPanSpeed => undef,
    MaxPanSpeed => undef,
    HasTurboPan => 0,
    TurboPanSpeed => undef,
    CanTilt => 0,
    MinTiltRange => undef,
    MaxTiltRange => undef,
    MinTiltStep => undef,
    MaxTiltStep => undef,
    HasTiltSpeed => 0,
    MinTiltSpeed => undef,
    MaxTiltSpeed => undef,
    HasTurboTilt => 0,
    TurboTiltSpeed => undef,
    CanAutoScan => 0,
    NumScanPaths => 0,
    );

our $AUTOLOAD;

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
  return $self->{Id};
}

sub open {
  my $self = shift;
  Fatal('No open method defined for protocol '.$self->{Protocol});
}

sub close {
  my $self = shift;
  $self->{state} = 'closed';
  Debug('No close method defined for protocol '.$self->{Protocol});
}

sub loadMonitor {
  my $self = shift;
  if ( !$self->{Monitor} ) {
    if ( !($self->{Monitor} = ZoneMinder::Monitor->find_one(Id=>$self->{MonitorId})) ) {
      Fatal('Monitor id '.$self->{id}.' not found');
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
  Error("Missing mandatory parameter '$name'");
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
