# ==========================================================================
#
# ZoneMinder Monitor Module, $Date$, $Revision$
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
# This module contains the common definitions and functions used by the rest
# of the ZoneMinder scripts
#
package ZoneMinder::Monitor;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Object;
require ZoneMinder::Storage;
require ZoneMinder::Server;

#our @ISA = qw(Exporter ZoneMinder::Base);
use parent qw(ZoneMinder::Object);

use vars qw/ $table $primary_key %fields $serial %defaults $debug/;
$table = 'Monitors';
$serial = $primary_key = 'Id';
%fields = map { $_ => $_ } qw(
  Id
  Name
  Notes
  ServerId
  StorageId
  Type
  Function
  Enabled
  LinkedMonitors
  Triggers
  Device
  Channel
  Format
  V4LMultiBuffer
  V4LCapturesPerFrame
  Protocol
  Method
  Host
  Port
  SubPath
  Path
  Options
  User
  Pass
  Width
  Height
  Colours
  Palette
  Orientation
  Deinterlacing
  DecoderHWAccelName
  DecoderHWAccelDevice
  SaveJPEGs
  VideoWriter
  OutputCodec
  OutputContainer
  EncoderParameters
  RecordAudio
  RTSPDescribe
  Brightness
  Contrast
  Hue
  Colour
  EventPrefix
  LabelFormat
  LabelX
  LabelY
  LabelSize
  ImageBufferCount
  WarmupCount
  PreEventCount
  PostEventCount
  StreamReplayBuffer
  AlarmFrameCount
  SectionLength
  MinSectionLength
  FrameSkip
  MotionFrameSkip
  AnalysisFPSLimit
  AnalysisUpdateDelay
  MaxFPS
  AlarmMaxFPS
  FPSReportInterval
  RefBlendPerc
  AlarmRefBlendPerc
  Controllable
  ControlId
  ControlDevice
  ControlAddress
  AutoStopTimeout
  TrackMotion
  TrackDelay
  ReturnLocation
  ReturnDelay
  DefaultRate
  DefaultScale
  SignalCheckPoints
  SignalCheckColour
  WebColour
  Exif
  Sequence
  );

%defaults = (
    ServerId => 0,
    StorageId => 0,
    Type      => 'Ffmpeg',
    Function  => 'Mocord',
    Enabled   => 1,
    LinkedMonitors => undef,
    Device  =>  '',
    Channel =>  0,
    Format  =>  0,
    V4LMultiBuffer  =>  undef,
    V4LCapturesPerFrame =>  1,
    Protocol  =>  undef,
    Method  =>  '',
    Host  =>  undef,
    Port  =>  '',
    SubPath =>  '',
    Path  =>  undef,
    Options =>  undef,
    User  =>  undef,
    Pass  =>  undef,
    Width => undef,
    Height => undef,
    Colours => 4,
    Palette =>  0,
    Orientation => undef,
    Deinterlacing =>  0,
    DecoderHWAccelName  =>  undef,
    DecoderHWAccelDevice  =>  undef,
    SaveJPEGs =>  3,
    VideoWriter =>  0,
    OutputCodec =>  undef,
    OutputContainer => undef,
    EncoderParameters => "# Lines beginning with # are a comment \n# For changing quality, use the crf option\n# 1 is best, 51 is worst quality\n#crf=23\n",
    RecordAudio=>0,
    RTSPDescribe=>0,
    Brightness  =>  -1,
    Contrast    =>  -1,
    Hue         =>  -1,
    Colour      =>  -1,
    EventPrefix =>  'Event-',
    LabelFormat => '%N - %d/%m/%y %H:%M:%S',
    LabelX      =>  0,
    LabelY      =>  0,
    LabelSize   =>  1,
    ImageBufferCount =>  20,
    WarmupCount =>  0,
    PreEventCount =>  5,
    PostEventCount  =>  5,
    StreamReplayBuffer  => 0,
    AlarmFrameCount     =>  1,
    SectionLength      =>  600,
    MinSectionLength    =>  10,
    FrameSkip           =>  0,
    MotionFrameSkip     =>  0,
    AnalysisFPSLimit  =>  undef,
    AnalysisUpdateDelay  =>  0,
    MaxFPS => undef,
    AlarmMaxFPS => undef,
    FPSReportInterval  =>  100,
    RefBlendPerc        =>  6,
    AlarmRefBlendPerc   =>  6,
    Controllable        =>  0,
    ControlId =>  undef,
    ControlDevice =>  undef,
    ControlAddress  =>  undef,
    AutoStopTimeout => undef,
    TrackMotion     =>  0,
    TrackDelay      =>  undef,
    ReturnLocation  =>  -1,
    ReturnDelay     =>  undef,
    DefaultRate =>  100,
    DefaultScale  =>  100,
    SignalCheckPoints =>  0,
    SignalCheckColour =>  '#0000BE',
    WebColour   =>  '#ff0000',
    Exif    =>  0,
    Sequence  =>  undef,
    );

sub Server {
	return new ZoneMinder::Server( $_[0]{ServerId} );
} # end sub Server

sub Storage {
	return new ZoneMinder::Storage( $_[0]{StorageId} );
} # end sub Storage

1;
__END__

=head1 NAME

ZoneMinder::Monitor - Perl Class for Monitors

=head1 SYNOPSIS

use ZoneMinder::Monitor;

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2017  ZoneMinder LLC

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.


=cut
