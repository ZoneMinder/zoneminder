# ==========================================================================
#
# ZoneMinder Amcrest HTTP API Control Protocol Module
#
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
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
#
# ==========================================================================

package ZoneMinder::Control::Amcrest_HTTP;

use 5.006;
use strict;
use warnings;

use Time::HiRes qw( usleep );

require ZoneMinder::Base;
require ZoneMinder::Control;
require ZoneMinder::General;
require LWP::UserAgent;

use URI::Encode qw();

# Values we put in a query string routinely contain reserved characters -
# passwords especially - so always escape those too.
sub uri_encode {
  return URI::Encode::uri_encode($_[0] // '', {encode_reserved => 1});
}

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Amcrest HTTP API Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);

sub open {
  my $self = shift;

  $self->loadMonitor();
  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->cookie_jar( {} );
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);

  $self->guess_credentials() if !$$self{username};

  my $url = 'cgi-bin/magicBox.cgi?action=getDeviceType';
  # Detect REALM, has to be /cgi-bin/ptz.cgi because just / accepts no auth
  if ($self->get_realm($url)) {
    $self->{state} = 'open';
    return !undef;
  }
  if ($self->{Monitor}->{ControlAddress}) {
    $self->parse_Path();
    # Detect REALM, has to be /cgi-bin/ptz.cgi because just / accepts no auth
    if ($self->get_realm($url)) {
      $self->{state} = 'open';
      return !undef;
    }
  }

  $self->{state} = 'closed';
  return undef;
}

sub close {
  my $self = shift;
  $self->{state} = 'closed';
}

sub sendCmd {
  my $self = shift;
  my $cmd = shift;
  my $result = undef;

  $self->printMsg($cmd, 'Tx');

  my $res = $self->get($cmd);

  if ( $res->is_success ) {
    $result = !undef;
    # Command to camera appears successful, write Info item to log
    Info('Camera control: \''.$res->status_line().'\' for URL '.$$self{BaseURL}.$cmd);
    # TODO: Add code to retrieve $res->message_decode or some such. Then we could do things like check the camera status.
  } else {
    # Have seen on some HikVision cams that whatever cookie LWP uses times out and it never refreshes, so we have to actually create a new LWP object.
    $self->{ua} = LWP::UserAgent->new();
    $self->{ua}->cookie_jar( {} );
    $self->{ua}->credentials($$self{address}, $$self{realm}, $$self{username}, $$self{password});

    # Try again
    $res = $self->get($cmd);
    if ( $res->is_success ) {
      # Command to camera appears successful, write Info item to log
      Info('Camera control 2: \''.$res->status_line().'\' for URL '.$$self{BaseURL}.$cmd);
    } else {
      Error('Camera control command FAILED: \''.$res->status_line().'\' for URL '.$$self{BaseURL}.$cmd);
      $res = $self->get('http://'.$self->{Monitor}->{ControlAddress}.'/'.$cmd);
    }
  }

  return $result;
}

sub reset {
  my $self = shift;
  # This reboots the camera effectively resetting it
  $self->sendCmd('cgi-bin/magicBox.cgi?action=reboot');
}

# NOTE: I'm putting this in, but absolute camera movement does not seem to be well supported in the classic skin ATM.
# Reading www/skins/classic/include/control_functions.php seems to indicate a faulty implementation, unless I'm
# reading it wrong. I see nowhere where the user is able to specify the absolute location to move to. Rather,
# the call is passed back movement in increments of 1 unit. At least with the Amcrest/Duhua API this would result
# in the camera moving to the 1* or 0* etc. position.

sub moveAbs ## Up, Down, Left, Right, etc. ??? Doesn't make sense here...
{
  my $self = shift;
  my $pan_degrees = shift || 0;
  my $tilt_degrees = shift || 0;
  my $speed = shift || 1;
  Debug('Move ABS');
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&code=PositionABS&channel=0&arg1='.$pan_degrees.'&arg2='.$tilt_degrees.'&arg3=0&arg4='.$speed);
}

sub moveConUp {
  my $self = shift;
  Debug('Move Up');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $$self{LastCmd} = 'code=Up&channel=0&arg1=0&arg2=1&arg3=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

sub moveConDown {
  my $self = shift;
  Debug('Move Down');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $$self{LastCmd} = 'code=Down&channel=0&arg1=0&arg2=1&arg3=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

sub moveConLeft {
  my $self = shift;
  Debug('Move Left');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $$self{LastCmd} = 'code=Left&channel=0&arg1=0&arg2=1&arg3=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

sub moveConRight {
  my $self = shift;
  Debug('Move Right');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $$self{LastCmd} = 'code=Right&channel=0&arg1=0&arg2=1&arg3=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

sub moveConUpRight {
  my $self = shift;
  Debug('Move Diagonally Up Right');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $$self{LastCmd} = 'code=RightUp&channel=0&arg1=1&arg2=1&arg3=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

sub moveConDownRight {
  my $self = shift;
  Debug('Move Diagonally Down Right');
  $$self{LastCmd} = 'code=RightDown&channel=0&arg1=1&arg2=1&arg3=0';
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

sub moveConUpLeft {
  my $self = shift;
  Debug('Move Diagonally Up Left');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $$self{LastCmd} = 'code=LeftUp&channel=0&arg1=1&arg2=1&arg3=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

sub moveConDownLeft {
  my $self = shift;
  Debug('Move Diagonally Down Left');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $$self{LastCmd} = 'code=LeftDown&channel=0&arg1=1&arg2=1&arg3=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

# Stop is not "correctly" implemented as control_functions.php translates this to "Center"
# So we'll just send the camera to 0* Horz, 0* Vert, zoom out; Also, Amcrest does not seem to
# support a generic stop-all-current-action command.

sub moveStop {
  my $self = shift;
  if ($$self{LastCmd}) {
    if ( substr($$self{LastCmd},0,4) eq 'code' ) {
      # last command was a PTZ move
      Debug('Move Stop '.$$self{LastCmd});
      $self->sendCmd('cgi-bin/ptz.cgi?action=stop&'.$$self{LastCmd});
      $$self{LastCmd} = '';
      $$self{Monitor}->resumeMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
    } elsif ( substr($$self{LastCmd},0,5)  eq 'focus' ) {
      # last command was a focus adjustment
      Debug('focus Stop '.$$self{LastCmd});
      $self->sendCmd('cgi-bin/devVideoInput.cgi?action=adjustFocusContinuously&focus=0&zoom=0');
      $$self{LastCmd} = '';
      $$self{Monitor}->resumeMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
    } else {
      Debug('focus Stop '.$$self{LastCmd});
      Error('Unknown or unaccounted for lastcmd value: ' . $$self{LastCmd});
      $$self{LastCmd} = '';
    }
  } else {
    Debug('Move Stop/Center');
    $self->sendCmd('cgi-bin/ptz.cgi?action=start&code=PositionABS&channel=0&arg1=0&arg2=0&arg3=0&arg4=1');
  }
}

#new focus stuff
sub focusAuto {
  my $self = shift;
  Debug('Set AutoFocus on');
  $self->sendCmd('cgi-bin/devVideoInput.cgi?action=autoFocus');
}

# focusConNear, focusConFar, focusStop is implemented above in sub moveStop 

sub focusConFar {
  my $self = shift;
  Debug('Set Focus far');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $$self{LastCmd} = 'code=FocusFar&channel=0&arg1=0&arg2=1&arg3=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

sub focusConNear {
  my $self = shift;
  Debug('Set Focus near');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $$self{LastCmd} = 'code=FocusNear&channel=0&arg1=0&arg2=1&arg3=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

# end of new focus stuff
# Move Camera to Home Position
# The current API does not support a Home per se, so we'll just send the camera to preset #1
# NOTE: It goes without saying that the user must have set up preset #1 for this to work.

sub presetHome {
  my $self = shift;
  Debug('Home Preset');
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&channel=0&code=GotoPreset&&arg1=0&arg2=1&arg3=0&arg4=0');
}

sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Go To Preset $preset");
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&channel=0&code=GotoPreset&&arg1=0&arg2='.$preset.'&arg3=0&arg4=0');
}

sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug('Set Preset');
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&channel=0&code=SetPreset&arg1=0&arg2='.$preset.'&arg3=0&arg4=0');
}

# NOTE: This does not appear to be implemented in the classic skin. But we'll leave it here for later.

sub moveMap {
  my $self = shift;
  my $params = shift;

  my $xcoord = $self->getParam( $params, 'xcoord', $self->{Monitor}{Width}/2 );
  my $ycoord = $self->getParam( $params, 'ycoord', $self->{Monitor}{Height}/2 );
  # if the camera is mounted upside down, you may have to inverse these coordinates
  # just use 360 minus pan instead of pan, 90 minus tilt instead of tilt
  # Convert xcoord into pan position 0 to 359
  my $pan = int(360 * $xcoord / $self->{Monitor}{Width});
  # Convert ycoord into tilt position 0 to 89
  my $tilt = 90 - int(90 * $ycoord / $self->{Monitor}{Height});
  # Now get the following url:
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&code=PositionABS&channel=0&arg1='.$pan.'&arg2='.$tilt.'&arg3=1&arg4=1');
}

sub zoomConTele {
  my $self = shift;
  Debug('Zoom continuous tele');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $$self{LastCmd} = 'code=ZoomTele&channel=0&arg1=0&arg2=0&arg3=0&arg4=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

sub zoomConWide {
  my $self = shift;
  Debug('Zoom continuous wide');
  $$self{Monitor}->suspendMotionDetection() if !$self->{Monitor}->{ModectDuringPTZ};
  $$self{LastCmd} = 'code=ZoomWide&channel=0&arg1=0&arg2=0&arg3=0&arg4=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

# ==========================================================================
#
# Additional PTZ / auxiliary controls
#
# ==========================================================================

sub zoomStop {
  my $self = shift;
  Debug('Zoom stop');
  $self->sendCmd('cgi-bin/ptz.cgi?action=stop&code=ZoomTele&channel=0&arg1=0&arg2=0&arg3=0&arg4=0');
  $self->sendCmd('cgi-bin/ptz.cgi?action=stop&code=ZoomWide&channel=0&arg1=0&arg2=0&arg3=0&arg4=0');
  $$self{LastCmd} = '';
}

sub focusStop {
  my $self = shift;
  Debug('Focus stop');
  $self->sendCmd('cgi-bin/devVideoInput.cgi?action=adjustFocusContinuously&focus=0&zoom=0');
  $$self{LastCmd} = '';
}

sub irisConOpen {
  my $self = shift;
  Debug('Iris open');
  $$self{LastCmd} = 'code=IrisLarge&channel=0&arg1=0&arg2=1&arg3=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

sub irisConClose {
  my $self = shift;
  Debug('Iris close');
  $$self{LastCmd} = 'code=IrisSmall&channel=0&arg1=0&arg2=1&arg3=0';
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&'.$$self{LastCmd});
}

sub irisStop {
  my $self = shift;
  Debug('Iris stop');
  $self->sendCmd('cgi-bin/ptz.cgi?action=stop&'.$$self{LastCmd}) if $$self{LastCmd};
  $$self{LastCmd} = '';
}

sub autoScan {
  my $self = shift;
  Debug('Auto pan on');
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&channel=0&code=AutoPanOn&arg1=0&arg2=0&arg3=0&arg4=0');
}

sub autoStop {
  my $self = shift;
  Debug('Auto pan off');
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&channel=0&code=AutoPanOff&arg1=0&arg2=0&arg3=0&arg4=0');
}

sub presetClear {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  Debug("Clear Preset $preset");
  $self->sendCmd('cgi-bin/ptz.cgi?action=start&channel=0&code=ClearPreset&arg1=0&arg2='.$preset.'&arg3=0&arg4=0');
}

# Returns the presets the camera has stored as a list of
# { Index => n, Name => 'string' } hashrefs.
sub presetList {
  my $self = shift;
  my $presets = $self->get_name_value('cgi-bin/ptz.cgi?action=getPresets&channel=0');
  return () if !$presets;
  return values %{indexed_to_array($presets, 'presets')};
}

# status.Postion[0..2] = pan, tilt, zoom.  Note the camera really does
# spell it 'Postion'.
sub ptz_status {
  my $self = shift;
  return $self->get_name_value('cgi-bin/ptz.cgi?action=getStatus&channel=0');
}

# Coaxial IO drives the white light / siren on cameras that have one.
# Cameras without the hardware answer with an error, which we pass back.
sub lightOn {
  my $self = shift;
  Debug('White light on');
  return $self->sendCmd('cgi-bin/coaxialControlIO.cgi?action=control&channel=1&info[0].Type=WhiteLight&info[0].IO=on');
}

sub lightOff {
  my $self = shift;
  Debug('White light off');
  return $self->sendCmd('cgi-bin/coaxialControlIO.cgi?action=control&channel=1&info[0].Type=WhiteLight&info[0].IO=off');
}

sub sirenOn {
  my $self = shift;
  Debug('Siren on');
  return $self->sendCmd('cgi-bin/coaxialControlIO.cgi?action=control&channel=1&info[0].Type=Speaker&info[0].IO=on');
}

sub sirenOff {
  my $self = shift;
  Debug('Siren off');
  return $self->sendCmd('cgi-bin/coaxialControlIO.cgi?action=control&channel=1&info[0].Type=Speaker&info[0].IO=off');
}

# Report what auxiliary hardware this camera exposes.  Used by cameratool
# to work out whether lightOn/sirenOn are worth offering.
sub getAux {
  my $self = shift;
  my %aux;
  foreach my $pair (
    ['coaxial'  => 'cgi-bin/coaxialControlIO.cgi?action=getCaps&channel=1'],
    ['lighting' => 'cgi-bin/configManager.cgi?action=getConfig&name=Lighting_V2'],
    ['ptz'      => 'cgi-bin/ptz.cgi?action=getCurrentProtocolCaps&channel=0'],
  ) {
    my ($name, $url) = @$pair;
    my $values = $self->get_name_value($url);
    $aux{$name} = $values if $values;
  }
  return \%aux;
}

# ==========================================================================
#
# Configuration
#
# Everything here talks the documented Amcrest/Dahua HTTP API.  Responses
# are 'name=value' text, one setting per line, which
# ZoneMinder::General::parseNameEqualsValueToHash turns into a flat hash.
#
# ==========================================================================

# Categories that live behind their own cgi rather than configManager.cgi.
# These are read only; set_config will refuse to write them.
my %config_urls = (
  caps            => 'cgi-bin/encode.cgi?action=getCaps',
  encode1         => 'cgi-bin/encode.cgi?action=getConfigCaps&channel=1',
  videoincaps     => 'cgi-bin/devVideoInput.cgi?action=getCaps&channel=1',
  ptzcaps         => 'cgi-bin/ptz.cgi?action=getCurrentProtocolCaps&channel=0',
  presets         => 'cgi-bin/ptz.cgi?action=getPresets&channel=0',
  interfaces      => 'cgi-bin/networkInterface.cgi?action=getInterfaces',
  users           => 'cgi-bin/userManager.cgi?action=getUserInfoAll',
  groups          => 'cgi-bin/userManager.cgi?action=getGroupInfoAll',
  systeminfo      => 'cgi-bin/magicBox.cgi?action=getSystemInfo',
  devicetype      => 'cgi-bin/magicBox.cgi?action=getDeviceType',
  serialno        => 'cgi-bin/magicBox.cgi?action=getSerialNo',
  vendor          => 'cgi-bin/magicBox.cgi?action=getVendor',
  machinename     => 'cgi-bin/magicBox.cgi?action=getMachineName',
  hardwareversion => 'cgi-bin/magicBox.cgi?action=getHardwareVersion',
  softwareversion => 'cgi-bin/magicBox.cgi?action=getSoftwareVersion',
  currenttime     => 'cgi-bin/global.cgi?action=getCurrentTime',
);

# Sections fetched through configManager.cgi.  These are read/write.
# Not every model implements every section; the ones it doesn't answer
# with an error body, which we log at Debug rather than Warning.
#
# get_config() will happily fetch a section that isn't listed here - any
# name it doesn't recognise is tried as a configManager name - so a
# template file can pull in extras (WLan, NAS, Ftp, VideoInExposure,
# VideoInWhiteBalance, VideoInBacklight, PrivacyMasking, ...) without
# needing a change here.
my @configManager_names = (
  'General',
  'Locales',
  'NTP',
  'Network',
  'RTSP',
  'Multicast',
  'Email',
  'Encode',
  'Snap',
  'VideoWidget',
  'VideoColor',
  'VideoInOptions',
  'VideoInDayNight',
  'VideoImageControl',
  'MotionDetect',
  'RecordMode',
  'ChannelTitle',
  'Lighting',
  'PtzAutoMovement',
  'AutoMaintain',
  'UPnP',
  'DDNS',
  'AccessFilter',
  'Login',
);

sub configManager_url {
  return 'cgi-bin/configManager.cgi?action=getConfig&name='.$_[0];
}

# A stale digest nonce makes the first request come back 401.  Repeating
# it immediately succeeds because the 401 primed LWP with a fresh nonce.
# Don't make any other request in between, that would consume the nonce.
sub get_retry {
  my ($self, $url) = @_;
  my $response = $self->get($url);
  $response = $self->get($url) if !$response->is_success();
  return $response;
}

# GET a name=value endpoint and return it as a hashref, or undef on
# failure.  'table.' prefixes, which configManager.cgi puts on every key,
# are stripped so the caller sees the bare setting names.
sub get_name_value {
  my ($self, $url) = @_;

  my $response = $self->get_retry($url);
  if (!$response->is_success()) {
    # An unsupported name/section answers 400 with an 'Error' body.  That
    # is expected while probing, so don't shout about it.
    my $content = $response->decoded_content // '';
    if ($content =~ /^\s*Error/) {
      Debug("Not supported by this camera: $url");
    } else {
      Warning("Failed to get $url: ".$response->status_line());
    }
    return undef;
  }

  my $parsed = ZoneMinder::General::parseNameEqualsValueToHash($response->decoded_content);
  my %stripped;
  foreach my $key (keys %$parsed) {
    (my $clean_key = $key) =~ s/^table\.//;
    $stripped{$clean_key} = $$parsed{$key};
  }
  return \%stripped;
}

# Turn a flat hash of 'prefix[0].Field' => value into
# { 0 => { Field => value } }, which is how the camera reports lists of
# users, presets, etc.
sub indexed_to_array {
  my ($values, $prefix) = @_;
  my %array;
  foreach my $key (keys %$values) {
    next if $key !~ /^\Q$prefix\E\[(\d+)\]\.(.+)$/;
    $array{$1}{$2} = $$values{$key};
  }
  return \%array;
}

sub get_config {
  my $self = shift;
  my @requested = @_;
  my %config;

  # [ key, url ] pairs to fetch
  my @wanted;
  if (@requested) {
    # Anything we don't have a dedicated url for is tried as a
    # configManager section.  This lets a template drive what we fetch.
    foreach my $name (@requested) {
      push @wanted, [$name, $config_urls{$name} || configManager_url($name)];
    }
  } else {
    push @wanted, map { [$_, $config_urls{$_}] } keys %config_urls;
    push @wanted, map { [$_, configManager_url($_)] } @configManager_names;
  }

  foreach my $entry (@wanted) {
    my ($name, $url) = @$entry;
    my $values = $self->get_name_value($url);
    $config{$name} = $values if $values;
  }

  return keys %config ? \%config : undef;
} # end sub get_config

sub set_config {
  my $self = shift;
  my $diff = shift;

  foreach my $name (keys %$diff) {
    if ($config_urls{$name}) {
      Warning("$name is read-only, not setting it");
      next;
    }
    if (ref $$diff{$name} ne 'HASH') {
      Warning("Don't know how to set $name, expected a hash of settings");
      next;
    }

    my $url = 'cgi-bin/configManager.cgi?action=setConfig';
    foreach my $key (sort keys %{$$diff{$name}}) {
      # get_config only strips the 'table.' prefix, so the key still carries
      # its section and any array indices - 'Encode[0].MainFormat[0].Video.FPS'
      # - which is exactly the form setConfig wants.  Don't prepend $name again.
      $url .= '&'.$key.'='.uri_encode($$diff{$name}{$key});
    }

    my $response = $self->get_retry($url);
    if (!$response->is_success()) {
      Error("Failed to set $name: ".$response->status_line().' '.$response->decoded_content);
      return 0;
    }
    Debug("Set $name: ".$response->decoded_content);
  } # end foreach section

  return 1;
} # end sub set_config

# Reset a section back to the factory value.
sub restoreDefault {
  my $self = shift;
  my @names = @_;
  return 0 if !@names;
  my $url = 'cgi-bin/configManager.cgi?action=restoreDefault';
  for (my $i = 0; $i < @names; $i ++) {
    $url .= '&names['.$i.']='.uri_encode($names[$i]);
  }
  return $self->get_retry($url)->is_success();
}

# ==========================================================================
#
# Device information
#
# ==========================================================================

sub device_info {
  my $self = shift;
  my %info;
  foreach my $name (qw(devicetype serialno vendor machinename hardwareversion softwareversion systeminfo)) {
    my $values = $self->get_name_value($config_urls{$name});
    next if !$values;
    # These endpoints answer with a single key, eg 'type=IP2M-841B'
    foreach my $key (keys %$values) {
      $info{$key} = $$values{$key};
    }
  }
  return keys %info ? \%info : undef;
}

sub getModel {
  my $self = shift;
  my $values = $self->get_name_value($config_urls{devicetype});
  if (!$values or !$$values{type}) {
    Debug('Failed to get device type');
    return undef;
  }
  return $$values{type};
}

sub getVersion {
  my $self = shift;
  my $values = $self->get_name_value($config_urls{softwareversion});
  return undef if !$values;
  # version=2.400.0000000.28.R, build=2019-05-31
  return $$values{version};
}

# Amcrest publish no machine readable firmware feed, so this is a manual
# table keyed on the string magicBox.cgi?action=getDeviceType returns.
# Add entries as they are verified; an unknown model reports nothing,
# which cameratool prints as 'Model firmware unknown'.
my %latest_firmware = (
);

sub check_firmware {
  my $self = shift;
  my $model = $self->getModel();
  if (!$model) {
    Debug('No model, cannot check firmware');
    return;
  }
  if (!$latest_firmware{$model}) {
    Debug("We don't have a listing for latest firmware for ($model)");
    return;
  }
  my %result = %{$latest_firmware{$model}};
  $result{current_version} = $self->getVersion();
  $result{update_available} = (defined($result{current_version})
      and $result{current_version} lt $result{latest_version}) ? 1 : 0;
  return %result;
}

# NOTE: untested against real hardware.  cgi-bin/Firmware.cgi?action=upgrade
# is what the Dahua/Amcrest web ui posts the .bin to.  Flashing the wrong
# image will brick the camera.
sub update_firmware {
  my $self = shift;
  my $firmware = shift;
  if (!length($firmware)) {
    Error('No firmware data given to update_firmware');
    return 0;
  }
  my $response = $self->post('cgi-bin/Firmware.cgi?action=upgrade', $firmware,
    {'Content-Type' => 'application/octet-stream'});
  return $response->is_success();
}

sub reboot {
  my $self = shift;
  return $self->sendCmd('cgi-bin/magicBox.cgi?action=reboot');
}

sub shutdown {
  my $self = shift;
  return $self->sendCmd('cgi-bin/magicBox.cgi?action=shutdown');
}

# ==========================================================================
#
# Time
#
# ==========================================================================

sub get_time {
  my $self = shift;
  my $values = $self->get_name_value($config_urls{currenttime});
  return $values ? $$values{result} : undef;
}

# $time is 'YYYY-MM-DD hh:mm:ss'.  Defaults to this host's local time.
sub set_time {
  my $self = shift;
  my $time = shift;
  if (!$time) {
    my @t = localtime();
    $time = sprintf('%04d-%02d-%02d %02d:%02d:%02d',
      $t[5]+1900, $t[4]+1, $t[3], $t[2], $t[1], $t[0]);
  }
  return $self->get_retry('cgi-bin/global.cgi?action=setCurrentTime&time='.uri_encode($time))->is_success();
}

# ==========================================================================
#
# Users
#
# Amcrest keys users by name, not by a numeric id.  get_users reports the
# position in the camera's list as 'id' so that callers which work in ids
# have something to pass back; update_user/delete_user accept either.
#
# ==========================================================================

sub get_users {
  my $self = shift;

  my $values = $self->get_name_value($config_urls{users});
  return () if !$values;

  my $users = indexed_to_array($values, 'users');
  my @users;
  foreach my $id (sort { $a <=> $b } keys %$users) {
    my $user = $$users{$id};
    push @users, {
      id       => $id,
      userName => $$user{Name},
      userType => $$user{Group},
      memo     => $$user{Memo},
      sharable => $$user{Sharable},
      reserved => $$user{Reserved},
    };
  }
  return @users;
}

# Accepts either the name or the id reported by get_users.
sub resolve_user {
  my ($self, $wanted) = @_;
  foreach my $user ($self->get_users()) {
    return $user if $$user{userName} eq $wanted;
    return $user if $$user{id} eq $wanted;
  }
  return undef;
}

# ZoneMinder talks in administrator/operator/viewer, Amcrest has two
# groups: admin and user.
sub user_group {
  my $userType = shift || 'operator';
  return 'admin' if $userType =~ /^admin/i;
  return 'user';
}

sub add_user {
  my $self = shift;
  my $username = shift;
  my $password = shift;
  my $userType = shift || 'operator';

  if (!$username or !$password) {
    Error('add_user requires username and password');
    return undef;
  }
  if ($self->resolve_user($username)) {
    Error("User '$username' already exists");
    return undef;
  }

  my $url = 'cgi-bin/userManager.cgi?action=addUser'
    .'&user.Name='.uri_encode($username)
    .'&user.Password='.uri_encode($password)
    .'&user.Group='.user_group($userType)
    .'&user.Sharable=true'
    .'&user.Reserved=false'
    .'&user.Memo='.uri_encode('Added by ZoneMinder');

  my $response = $self->get_retry($url);
  if (!$response->is_success()) {
    Error("Failed to add user '$username': ".$response->status_line().' '.$response->decoded_content);
    return undef;
  }
  return 1;
}

# %updates takes password, userType and/or memo.
sub update_user {
  my $self = shift;
  my $wanted = shift;
  my %updates = @_;

  my $user = $self->resolve_user($wanted);
  if (!$user) {
    Error("No such user '$wanted'");
    return undef;
  }
  my $name = $$user{userName};

  # The password is changed through its own action, not modifyUser.
  if ($updates{password}) {
    my $url = 'cgi-bin/userManager.cgi?action=modifyPassword'
      .'&name='.uri_encode($name)
      .'&pwd='.uri_encode($updates{password})
      .'&pwdOld='.uri_encode($$self{password} // '');
    my $response = $self->get_retry($url);
    if (!$response->is_success()) {
      Error("Failed to change password for '$name': ".$response->status_line().' '.$response->decoded_content);
      return undef;
    }
  }

  my $url = 'cgi-bin/userManager.cgi?action=modifyUser&name='.uri_encode($name)
    .'&user.Name='.uri_encode($updates{userName} // $name);
  $url .= '&user.Group='.user_group($updates{userType}) if $updates{userType};
  $url .= '&user.Memo='.uri_encode($updates{memo}) if defined $updates{memo};

  # Nothing but the password changed, we're done.
  return 1 if !$updates{userType} and !defined($updates{memo}) and !$updates{userName};

  my $response = $self->get_retry($url);
  if (!$response->is_success()) {
    Error("Failed to update user '$name': ".$response->status_line().' '.$response->decoded_content);
    return undef;
  }
  return 1;
}

sub delete_user {
  my $self = shift;
  my $wanted = shift;

  my $user = $self->resolve_user($wanted);
  if (!$user) {
    Error("No such user '$wanted'");
    return undef;
  }
  if ($$user{reserved} and $$user{reserved} eq 'true') {
    Error("User '$$user{userName}' is reserved and cannot be deleted");
    return undef;
  }

  my $response = $self->get_retry('cgi-bin/userManager.cgi?action=deleteUser&name='.uri_encode($$user{userName}));
  if (!$response->is_success()) {
    Error("Failed to delete user '$$user{userName}': ".$response->status_line().' '.$response->decoded_content);
    return undef;
  }
  return 1;
}

# ==========================================================================
#
# Streams and discovery
#
# ==========================================================================

sub snapshot {
  my $self = shift;
  my $channel = shift || 1;
  my $response = $self->get_retry('cgi-bin/snapshot.cgi?channel='.$channel);
  return $response->is_success() ? $response->content : undef;
}

# subtype 0 is the main stream, 1 the sub stream.
sub rtsp_url {
  my ($self, $ip, $channel, $subtype) = @_;
  $channel = 1 if !defined $channel;
  $subtype = 0 if !defined $subtype;
  return 'rtsp://'.$ip.':554/cam/realmonitor?channel='.$channel.'&subtype='.$subtype;
}

# What the camera can actually encode, from encode.cgi?action=getCaps.
sub profiles {
  my $self = shift;
  my $caps = $self->get_name_value($config_urls{encode1});
  return () if !$caps;

  my $host = $$self{host} || ($$self{Monitor} ? $$self{Monitor}{ControlAddress} : '');

  my @profiles;
  foreach my $format (['MainFormat', 0], ['ExtraFormat', 1]) {
    my ($name, $subtype) = @$format;
    my $resolutions = $$caps{'caps[0].'.$name.'[0].Video.ResolutionTypes'};
    next if !$resolutions;
    push @profiles, {
      name        => $name,
      subtype     => $subtype,
      url         => $self->rtsp_url($host, 1, $subtype),
      resolutions => [split(/,/, $resolutions)],
      codecs      => [split(/,/, $$caps{'caps[0].'.$name.'[0].Video.CompressionTypes'} // '')],
      fps_max     => $$caps{'caps[0].'.$name.'[0].Video.FPSMax'},
    };
  }
  return @profiles;
}

sub probe {
  my ($ip, $username, $password) = @_;

  my $self = ZoneMinder::Control::Amcrest_HTTP->new();
  $self->{ua} = LWP::UserAgent->new();
  $self->{ua}->cookie_jar( {} );
  $$self{username} = $username;
  $$self{password} = $password;
  $$self{realm} = '';

  foreach my $port ( '80', '443' ) {
    $$self{port} = $port;
    $$self{host} = $ip;
    $$self{BaseURL} = "http://$ip:$port/";
    $$self{address} = "$ip:$port";
    $self->{ua}->credentials("$ip:$port", '', $username, $password);

    if ($self->get_realm('cgi-bin/magicBox.cgi?action=getDeviceType')) {
      return {
        url   => $self->rtsp_url($ip),
        realm => $$self{realm},
        model => $self->getModel(),
      };
    }
  } # end foreach port
  return undef;
}


1;

__END__

=pod

=head1 NAME

ZoneMinder::Control::Amcrest_HTTP - Amcrest camera control

=head1 DESCRIPTION

This module contains the implementation of the Amcrest Camera
controllable SDK API.

NOTE: This module implements interaction with the camera in clear text.

The login and password are transmitted from ZM to the camera in clear text,
and as such, this module should be used ONLY on a blind LAN implementation
where interception of the packets is very low risk.

The "usleep (X);" lines throughout the script may need adjustments for your
situation.  This is the time that the script waits between sending a "start"
and a "stop" signal to the camera.  For example, the pan left arrow would
result in the camera panning full to its leftmost position if there were no
stop signal.  So the usleep time sets how long the script waits to allow the
camera to start moving before issuing a stop.  The X value of usleep is in
microseconds, so "usleep (100000);" is equivalent to wait one second.

=head1 NON-PTZ API

Beyond PTZ this module wraps enough of the Amcrest/Dahua HTTP API for
cameratool.pl to inventory and configure a camera:

=over 4

=item Configuration

C<get_config()> with no arguments fetches every read-only category in
C<%config_urls> plus every read/write section in C<@configManager_names>.
Given a list of names it fetches just those, and any name it does not
recognise is tried as a C<configManager.cgi> section - so a template file
can pull in sections this module does not list (WLan, NAS, Ftp,
VideoInExposure, VideoInWhiteBalance, VideoInBacklight, ...) without a
code change.  C<set_config()> takes the same shape back and writes it
through C<configManager.cgi?action=setConfig>, refusing the read-only
categories.  C<restoreDefault()> resets sections to factory values.

=item Device information

C<device_info()>, C<getModel()>, C<getVersion()>, C<check_firmware()>,
C<update_firmware()>, C<reboot()>, C<shutdown()>.

=item Time

C<get_time()> and C<set_time()> via C<global.cgi>.

=item Users

C<get_users()>, C<add_user()>, C<update_user()>, C<delete_user()> via
C<userManager.cgi>.  Amcrest keys users by name rather than by a numeric
id; C<get_users()> reports the list position as C<id> and the accessors
accept either that or the name.  Amcrest only has two groups, admin and
user, so administrator maps to admin and everything else to user.

=item Streams

C<snapshot()>, C<rtsp_url()>, C<profiles()> and the class method
C<probe($ip, $username, $password)>.

=item Auxiliary hardware

C<lightOn()>/C<lightOff()> and C<sirenOn()>/C<sirenOff()> drive the white
light and siren through C<coaxialControlIO.cgi> on models that have them.
C<getAux()> reports what the camera admits to having.

=back

Not every model implements every section.  A section the camera does not
know answers 400 with an C<Error> body, which is logged at Debug rather
than Warning so that probing stays quiet.

C<update_firmware()> is written to the documented
C<Firmware.cgi?action=upgrade> endpoint but has not been verified against
real hardware.  Flashing the wrong image will brick the camera.

=head1 SEE ALSO

https://s3.amazonaws.com/amcrest-files/Amcrest+HTTP+API+3.2017.pdf

=head1 AUTHORS

Herndon Elliott alabamatoy at gmail dot com
Chris Nighswonger chris dot nighswonger at gmail dot com

=head1 COPYRIGHT AND LICENSE

(C) 2016 Herndon Elliott alabamatoy at gmail dot com
(C) 2018 Chris Nighswonger chris dot nighswonger at gmail dot com

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

=cut
