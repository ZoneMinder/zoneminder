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
use Time::HiRes qw(usleep);

require ZoneMinder::Base;
require ZoneMinder::Object;
require ZoneMinder::Storage;
require ZoneMinder::Server;
require ZoneMinder::Memory;
require ZoneMinder::Monitor_Status;
require ZoneMinder::Event_Summary;
require ZoneMinder::Zone;
require ZoneMinder::Manufacturer;
require ZoneMinder::Model;
use ZoneMinder::Logger qw(:all);

use parent qw(ZoneMinder::Object);

use vars qw/ $table $primary_key %fields $serial %defaults $debug/;
$debug = 0;
$table = 'Monitors';
$serial = $primary_key = 'Id';
%fields = map { $_ => $_ } qw(
  Id
  Name
  Deleted
  Notes
  ServerId
  StorageId
  ManufacturerId
  ModelId
  Type
  Capturing
  Analysing
  AnalysisSource
  AnalysisImage
  Recording
  Decoding
  JanusEnabled
  JanusAudioEnabled
  Janus_Profile_Override
  Janus_Use_RTSP_Restream
  Janus_RTSP_User
  Janus_RTSP_Session_Timeout
  LinkedMonitors
  Triggers
  EventStartCommand
  EventEndCommand
  ONVIF_URL
  ONVIF_Username
  ONVIF_Password
  ONVIF_Options
  ONVIF_Event_Listener
  ONVIF_Alarm_Text
  use_Amcrest_API
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
  SecondPath
  Options
  User
  Pass
  Width
  Height
  Colours
  Palette
  Orientation
  Deinterlacing
  Decoder
  DecoderHWAccelName
  DecoderHWAccelDevice
  SaveJPEGs
  VideoWriter
  OutputCodec
  OutputContainer
  EncoderParameters
  RecordAudio
  RecordingSource
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
  MaxImageBufferCount
  WarmupCount
  PreEventCount
  PostEventCount
  StreamReplayBuffer
  AlarmFrameCount
  SectionLength
  SectionLengthWarn
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
  ModectDuringPTZ
  DefaultRate
  DefaultScale
  DefaultCodec
  SignalCheckPoints
  SignalCheckColour
  WebColour
  Exif
  Sequence
  ZoneCount
  Refresh
  DefaultCodec
  Latitude
  Longitude
  RTSPServer
  RTSPStreamName
  Importance
  MQTT_Enabled
  MQTT_Subscriptions
  );

%defaults = (
    Name => q`'Monitor'`,
    Deleted => 0,
    ServerId => 0,
    StorageId => 0,
    ManufacturerId => undef,
    ModelId => undef,
    Type      => q`'Ffmpeg'`,
    Capturing => q`'Always'`,
    Analysing => q`'Always'`,
    AnalysisSource => q`'Primary'`,
    AnalysisImage => q`'FullColour'`,
    Recording => q`'Always'`,
    Decoding => q`'Always'`,
    JanusEnabled => 0,
    JanusAudioEnabled => 0,
    Janus_Profile_Override => q`''`,
    Janus_Use_RTSP_Restream => 0,
    Janus_RTSP_User => undef,
    Janus_RTSP_Session_Timeout => 0,
    LinkedMonitors => undef,
    Triggers => '',
    EventStartCommand => '',
    EventEndCommand => '',
    ONVIF_URL => q`''`,
    ONVIF_Username => q`''`,
    ONVIF_Password => q`''`,
    ONVIF_Options => q`''`,
    ONVIF_Event_Listener => 0,
    ONVIF_Alarm_Text => q`'MotionAlarm'`,
    use_Amcrest_API => 0,
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
    SecondPath  => undef,
    Options =>  undef,
    User  =>  undef,
    Pass  =>  undef,
    Width => undef,
    Height => undef,
    Colours => 4,
    Palette =>  0,
    Orientation => q`'ROTATE_0'`,
    Deinterlacing =>  0,
    Decoder   => undef,
    DecoderHWAccelName  =>  undef,
    DecoderHWAccelDevice  =>  undef,
    SaveJPEGs =>  3,
    VideoWriter =>  0,
    OutputCodec =>  undef,
    OutputContainer => undef,
    EncoderParameters => '',
    RecordAudio=>0,
    RecordingSource  => q`'Primary'`,
    RTSPDescribe=>0,
    Brightness  =>  -1,
    Contrast    =>  -1,
    Hue         =>  -1,
    Colour      =>  -1,
    EventPrefix =>  q`'Event-'`,
    LabelFormat => '',
    LabelX      =>  0,
    LabelY      =>  0,
    LabelSize   =>  1,
    ImageBufferCount =>  20,
    MaxImageBufferCount =>  121,
    WarmupCount =>  0,
    PreEventCount =>  5,
    PostEventCount  =>  5,
    StreamReplayBuffer  => 0,
    AlarmFrameCount     =>  1,
    SectionLength      =>  600,
    SectionLengthWarn => 1,
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
    ModectDuringPTZ =>  0,
    DefaultRate =>  100,
    DefaultScale  =>  100,
    DefaultCodec =>  q`'auto'`,
    SignalCheckPoints =>  0,
    SignalCheckColour =>  q`'#0000BE'`,
    WebColour   =>  q`'#ff0000'`,
    Exif    =>  0,
    Sequence  =>  undef,
    ZoneCount =>  0,
    Refresh => undef,
    DefaultCodec  => q`'auto'`,
    Latitude  =>  undef,
    Longitude =>  undef,
    RTSPStreamName => '',
    RTSPServer => 0,
    Importance => 0,
    MQTT_Enabled => 0,
    MQTT_Subscriptions => q`''`,
    );

use constant CAPTURING_NONE     => 1;
use constant CAPTURING_ONDEMAND => 2;
use constant CAPTURING_ALWAYS   => 3;
use constant ANALYSING_ALWAYS   => 2;
use constant ANALYSING_NONE     => 1;

sub save {
  my $self = shift;

  my $manufacturer = $self->Manufacturer();
  my $model = $self->Model();

  if ($manufacturer->Name() and !$self->ManufacturerId()) {
    if ($manufacturer->save()) {
      $$self{ManufacturerId} = $manufacturer->Id();
      if ($model->Name()) {
        $model->ManufacturerId($$self{ManufacturerId});
      }
    }
  }
  if ($model->Name() and !$self->ModelId()) {
    if ($model->save()) {
      $$self{ModelId} = $model->Id()
    }
  }

  my $error = $self->SUPER::save( );
  return $error;
} # end sub save


sub Server {
	return new ZoneMinder::Server( $_[0]{ServerId} );
} # end sub Server

sub Storage {
	return new ZoneMinder::Storage( $_[0]{StorageId} );
} # end sub Storage

sub Zones {
  if (! exists $_[0]{Zones}) {
    $_[0]{Zones} = [ $_[0]{Id} ? ZoneMinder::Zone->find(MonitorId=>$_[0]{Id}) : () ];
  }
  return wantarray ? @{$_[0]{Zones}} : $_[0]{Zones};
}

sub control {
  my $monitor = shift;
  my $command = shift;
  my $process = shift;

  if ($command eq 'stop') {
    if ($process) {
      ZoneMinder::General::runCommand("zmdc.pl stop $process -m $$monitor{Id}");
    } else {
      if ($monitor->{Type} eq 'Local') {
        ZoneMinder::General::runCommand('zmdc.pl stop zmc -d '.$monitor->{Device});
      } else {
        ZoneMinder::General::runCommand('zmdc.pl stop zmc -m '.$monitor->{Id});
      }
    }
  } elsif ($command eq 'start') {
    if ( $process ) {
      ZoneMinder::General::runCommand("zmdc.pl start $process -m $$monitor{Id}");
    } else {
      if ($monitor->{Type} eq 'Local') {
        ZoneMinder::General::runCommand('zmdc.pl start zmc -d '.$monitor->{Device});
      } else {
        ZoneMinder::General::runCommand('zmdc.pl start zmc -m '.$monitor->{Id});
      }
    } # end if
  } elsif ( $command eq 'restart' ) {
    if ( $process ) {
      ZoneMinder::General::runCommand("zmdc.pl restart $process -m $$monitor{Id}");
    } else {
      if ($monitor->{Type} eq 'Local') {
        ZoneMinder::General::runCommand('zmdc.pl restart zmc -d '.$monitor->{Device});
      } else {
        ZoneMinder::General::runCommand('zmdc.pl restart zmc -m '.$monitor->{Id});
      }
    }
  }
} # end sub control

sub Status {
  my $self = shift;
  $$self{Status} = shift if @_;
  if ( ! $$self{Status} ) {
    $$self{Status} = ZoneMinder::Monitor_Status->find_one(MonitorId=>$$self{Id});
  }
  return $$self{Status};
}

sub Event_Summary {
  my $self = shift;
  $$self{Event_Summary} = shift if @_;
  if ( ! $$self{Event_Summary} ) {
    $$self{Event_Summary} = ZoneMinder::Event_Summary->find_one(MonitorId=>$$self{Id});
  }
  return $$self{Event_Summary};
}

sub connect {
  my $self = shift;
  if (!ZoneMinder::Memory::zmMemVerify($self)) {
    $self->disconnect();
    return undef;
  }
  return !undef;
}

sub disconnect {
  my $self = shift;
  ZoneMinder::Logger::Debug(4, "Disconnecting");
  ZoneMinder::Memory::zmMemInvalidate($self); # Close our file handle to the zmc process we are about to end
}

sub suspendMotionDetection {
  my $self = shift;
  return 0 if ! ZoneMinder::Memory::zmMemVerify($self);
  return if $$self{Capturing} eq 'None' or $$self{Analysing} eq 'None';
  my $count = 50;
  while ($count and 
    ( ZoneMinder::Memory::zmMemRead($self, 'shared_data:analysing', 1) != ANALYSING_NONE)
  ) {
    ZoneMinder::Logger::Debug(1, 'Suspending motion detection');
    ZoneMinder::Memory::zmMonitorSuspend($self);
    usleep(100000);
    $count -= 1;
  }
  if (!$count) {
    ZoneMinder::Logger::Error('Unable to suspend motion detection after 5 seconds.');
    ZoneMinder::Memory::zmMemInvalidate($self); # Close our file handle to the zmc process we are about to end
  } else {
    ZoneMinder::Logger::Debug(1, 'shared_data:analysing='.ZoneMinder::Memory::zmMemRead($self, 'shared_data:analysing', 1));
  }
}

sub resumeMotionDetection {
  my $self = shift;
  return 0 if ! ZoneMinder::Memory::zmMemVerify($self);
  return if $$self{Capturing} eq 'None' or $$self{Analysing} eq 'None';
  my $count = 50;
  while ($count and !ZoneMinder::Memory::zmMemRead($self, 'shared_data:analysing', 1)) {
    ZoneMinder::Logger::Debug(1, 'Resuming motion detection');
    ZoneMinder::Memory::zmMonitorResume($self);
    usleep(100000);
    $count -= 1;
  }
  if (!$count) {
    ZoneMinder::Logger::Error('Unable to resume motion detection after 5 seconds.');
    ZoneMinder::Memory::zmMemInvalidate($self); # Close our file handle to the zmc process we are about to end
  }
  return 1;
}

sub Control {
  my $self = shift;
  if (!exists $$self{Control}) {
    if ($$self{ControlId}) {
      require ZoneMinder::Control;
      my $Control = ZoneMinder::Control->find_one(Id=>$$self{ControlId});
      if ($Control) {
        my $Protocol = $$Control{Protocol};

        if (!$Protocol) {
          Debug("No protocol set in control $$Control{Id}, trying Name $$Control{Name}");
          $Protocol = $$Control{Name};
        }
        require Module::Load::Conditional;
        if (!Module::Load::Conditional::can_load(modules => {'ZoneMinder::Control::'.$Protocol => undef})) {
          Error("Can't load ZoneMinder::Control::$Protocol\n$Module::Load::Conditional::ERROR");
          return undef;
        }
        $Control = $Control->clone(); # Because this object is not per monitor specific
        bless $Control, 'ZoneMinder::Control::'.$Protocol;
        $$Control{MonitorId} = $$self{Id};
        $$Control{Monitor} = $self;
        $$self{Control} = $Control;
      } else {
        Error("Unable to load control for control $$self{ControlId} for monitor $$self{Id}");
      }
    } else {
      Info("No ControlId set in monitor $$self{Id}")
    }
  }
  return $$self{Control};
}

sub zmcControl {
  my $self = shift;
  my $mode = shift;

  if (!$$self{Id}) {
    Warning('Attempt to control a monitor with no Id');
    return;
  }
  my $zmcArgs = '';

  if ((!$ZoneMinder::Config{ZM_SERVER_ID}) or ( $$self{ServerId} and ($ZoneMinder::Config{ZM_SERVER_ID}==$$self{ServerId}) )) {
    if ($$self{Type} eq 'Local') {
      $zmcArgs .= '-d '.$self->{Device};
    } else {
      $zmcArgs .= '-m '.$self->{Id};
    }

    `/usr/bin/zmdc.pl $mode zmc $zmcArgs`;
  } elsif ($self->ServerId()) {
    my $Server = $self->Server();

    my $url = $Server->UrlToApi().'/monitors/daemonControl/'.$self->{'Id'}.'/'.$mode.'/zmc.json';
    if ($ZoneMinder::Config{ZM_OPT_USE_AUTH}) {
      if ($ZoneMinder::Config{ZM_AUTH_RELAY} eq 'hashed') {
        #$url .= '?auth='.generateAuthHash(ZM_AUTH_HASH_IPS);
      } elsif ($ZoneMinder::Config{ZM_AUTH_RELAY} == 'plain') {
        #$url .= '?user='.$_SESSION['username'];
        #$url .= '?pass='.$_SESSION['password'];
      } else {
        Error('Multi-Server requires AUTH_RELAY be either HASH or PLAIN');
        return;
      }
    }
    Debug('sending command to '.$url);
    my $ua = LWP::UserAgent->new();
    $ua->ssl_opts(verify_hostname => 0, SSL_verify_mode => 0x00);
    my $response = $ua->get($url);
    if (!$response->is_success()) {
      Error("Unable to restart remote monitor: " .$response->status_line);
    }
  } # end if local or remote
} # end sub zmcControl

sub ImportanceNumber {
  my $self = shift;
  if ($$self{Importance} eq 'Not') {
    return 2;
  } elsif ($$self{Importance} eq 'Less') {
    return 1;
  } elsif ($$self{Importance} eq 'Normal') {
    return 0;
  }
  Warning("Weird value for Importance $$self{Importance}");
  return 0;
}

sub Manufacturer {
  my $self = shift;
  $$self{Manufacturer} = shift if @_;
  if (!$$self{Manufacturer}) {
    $$self{Manufacturer} = new ZoneMinder::Manufacturer($$self{ManufacturerId});
  }
  return $$self{Manufacturer};
}

sub manufacturer {
  my $self = shift;
  if (@_) {
    my $new = shift;
    $$self{Manufacturer} = ZoneMinder::Manufacturer->find_one(Name=>$new);
    if (!$$self{Manufacturer}) {
      $$self{Manufacturer} = new ZoneMinder::Manufacturer();
      $$self{Manufacturer}->Name($new);
    }
  }
  if (!$$self{Manufacturer}) {
    $$self{Manufacturer} = new ZoneMinder::Manufacturer($$self{ManufacturerId});
  }
  return $$self{Manufacturer}->Name();
}

sub Model {
  my $self = shift;
  if (@_) {
    $$self{Model} = shift;
    $$self{ModelId} = $$self{Model} 
  }
  if (!$$self{Model}) {
    $$self{Model} = new ZoneMinder::Model($$self{ModelId});
  }
  return $$self{Model};
}

sub model {
  my $self = shift;
  
  if (@_) {
    my $new = shift;
    if ($new ne $$self{Model}->Name()) {
      $$self{Model} = ZoneMinder::Model->find_one(Name=>$new);
      if (!$$self{Model}) {
        $$self{Model} = new ZoneMinder::Model();
        $$self{Model}->Name($new);
      }
      $$self{ModelId} = $$self{Model}->Id(); 
    }
  }
  if (!$$self{Model}) {
    $$self{Model} = new ZoneMinder::Model($$self{ModelId});
  }
  return $$self{Model}->Name();
}

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
