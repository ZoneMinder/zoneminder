# ==========================================================================
#
# ZoneMinder Foscam HD (CGIProxy.fcgi) settings module
# Copyright (C) 2001-2008 Philip Coombes
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
# ==========================================================================
#
# Settings support for the Foscam HD cameras that speak the CGIProxy.fcgi
# API - everything from the FI98xx generation onward.  The PTZ modules for
# those cameras (FI9821W_Y2k, FI9831W, FOSCAMR2C) inherit from this so they
# gain get_config/set_config without changing how they move.
#
# It is also usable on its own for the fixed cameras of the same generation,
# which have no PTZ at all but the same settings API.
#
# Verified against an FI9853EP running firmware 2.22.2.15 (hardware
# 1.5.3.15, package 2015-08-11).
#

package ZoneMinder::Control::FoscamHD;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

our $VERSION = $ZoneMinder::Base::VERSION;

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
use URI::Escape qw(uri_escape uri_unescape);

# ==========================================================================
#
# The settings API.
#
# Every command is cmd=<name> on /cgi-bin/CGIProxy.fcgi with usr= and pwd=
# in the query string, and answers a flat <CGI_Result> document whose
# <result> is 0 for success, -1 for "parameter refused" and -3 for "this
# firmware does not have that command".
#
# 'set' is the whole-section writer.  Those writers are NOT merges: a
# setSystemTime that leaves out timeFormat and timeZone sets both of them to
# 0 rather than leaving them alone (measured - a partial write silently
# reset timeFormat 1 -> 0 and timeZone 14400 -> 0).  set_config() therefore
# reads the section back and writes the merged result, never the diff alone.
#
# 'field_set' is for the sections the firmware exposes only as one command
# per field, where there is nothing to merge.
#
# A section with neither is read-only.
#
# ==========================================================================

my %SECTIONS = (
  DevInfo          => {get => 'getDevInfo'},
  DevState         => {get => 'getDevState'},
  ProductAllInfo   => {get => 'getProductAllInfo'},
  SystemTime       => {get => 'getSystemTime',         set => 'setSystemTime'},
  DevName          => {get => 'getDevName',            set => 'setDevName'},
  PortInfo         => {get => 'getPortInfo',           set => 'setPortInfo'},
  OSDSetting       => {get => 'getOSDSetting',         set => 'setOSDSetting'},
  InfraLedConfig   => {get => 'getInfraLedConfig',     set => 'setInfraLedConfig'},
  UPnPConfig       => {get => 'getUPnPConfig',         set => 'setUPnPConfig'},
  DDNSConfig       => {get => 'getDDNSConfig',         set => 'setDDNSConfig'},
  MotionDetectConfig => {get => 'getMotionDetectConfig', set => 'setMotionDetectConfig'},

  # One command per field on this firmware.  setDenoiseLevel answers -3, so
  # denoiseLevel is readable but not writable and is left out of field_set.
  ImageSetting => {
    get => 'getImageSetting',
    field_set => {
      brightness => 'setBrightness',
      contrast   => 'setContrast',
      hue        => 'setHue',
      saturation => 'setSaturation',
      sharpness  => 'setSharpness',
    },
  },

  # Read-only on purpose.  getVideoStreamParam answers resolution0..3 but
  # setVideoStreamParam takes a single streamType plus unsuffixed
  # resolution/bitRate/frameRate/GOP/isVBR, so the read shape cannot be
  # merged back into a write.
  VideoStreamParam => {get => 'getVideoStreamParam'},

  # Read-only on purpose.  setIpInfo works but a wrong value takes the
  # camera off the network, where this module can no longer reach it.
  IPInfo => {get => 'getIPInfo'},
);

# Fields that setSystemTime accepts but that only mean anything when
# timeSource is 1 (manual).  Under NTP the camera ignores them.
my @MANUAL_TIME_FIELDS = qw(year mon day hour minute sec);

sub new {
  my $class = shift;
  my $id = shift;
  my $self = ZoneMinder::Control->new($id);
  bless($self, $class);
  return $self;
}

sub open {
  my $self = shift;

  $self->loadMonitor();
  $self->_ua();

  if (!$self->cgi('getDevInfo')) {
    $self->{state} = 'closed';
    return undef;
  }
  $self->{state} = 'open';
  return !undef;
}

# The PTZ subclasses have their own open() which builds a UserAgent but does
# not work out the address or credentials, so everything below resolves what
# it needs on demand instead of relying on open() having run.
sub _ua {
  my $self = shift;
  if (!$self->{ua}) {
    require LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new;
    $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);
  }
  return $self->{ua};
}

# usr/pwd go in the query string for this API, so we need them as values
# rather than as UserAgent credentials.  The older Foscam PTZ modules take
# them from ControlDevice as 'user:pass', so honour that first.
sub _auth {
  my $self = shift;
  return @$self{'username', 'password'} if defined $$self{username};

  my $monitor = $self->{Monitor};
  if ($monitor and $$monitor{ControlDevice}) {
    my ($user, $pass) = split(/:/, $$monitor{ControlDevice}, 2);
    if (defined($pass) and $pass ne '') {
      @$self{'username', 'password'} = ($user, $pass);
      return ($user, $pass);
    }
  }

  $self->_ua();
  $self->guess_credentials();
  return @$self{'username', 'password'};
}

sub _base_url {
  my $self = shift;
  return $$self{FoscamBaseURL} if $$self{FoscamBaseURL};

  $self->_ua();
  my $host = $self->host();
  if (!$host) {
    Error('Unable to work out the address of the camera');
    return undef;
  }
  my $port = $$self{port} || 80;
  $$self{FoscamBaseURL} = 'http://'.$host.($port == 80 ? '' : ':'.$port);
  return $$self{FoscamBaseURL};
}

# Send one command and return its answer as a hashref, or undef if the
# request failed or the camera reported a non-zero <result>.
sub cgi {
  my ($self, $cmd, $params) = @_;

  my $base = $self->_base_url() or return undef;
  my ($user, $pass) = $self->_auth();

  my $url = $base.'/cgi-bin/CGIProxy.fcgi?cmd='.uri_escape($cmd);
  foreach my $key (sort keys %{$params || {}}) {
    my $value = $$params{$key};
    $value = '' if !defined($value);
    $url .= '&'.uri_escape($key).'='.uri_escape($value);
  }
  $url .= '&usr='.uri_escape(defined($user) ? $user : '');
  $url .= '&pwd='.uri_escape(defined($pass) ? $pass : '');

  Debug("Foscam $cmd");
  my $response = $self->_ua()->get($url);
  if (!$response->is_success()) {
    Error("Foscam $cmd failed: ".$response->status_line());
    return undef;
  }

  my $values = _parse_cgi_result($response->decoded_content());
  if (!$values) {
    Error("Foscam $cmd returned no CGI_Result: ".$response->decoded_content());
    return undef;
  }

  my $result = $$values{result};
  if (!defined($result) or $result != 0) {
    $result = 'missing' if !defined($result);
    # -3 is "no such command on this firmware", which callers iterating over
    # the section list should be able to skip quietly.
    Debug("Foscam $cmd refused with result=$result");
    return undef;
  }
  delete $$values{result};
  return $values;
}

# The answers are a flat list of <tag>value</tag> under <CGI_Result>, with
# some values url-encoded by the camera.
sub _parse_cgi_result {
  my $content = shift;
  return undef if !defined($content) or $content !~ /<CGI_Result>/;

  my %values;
  while ($content =~ /<(\w+)>([^<]*)<\/\1>/g) {
    $values{$1} = uri_unescape($2);
  }
  return keys %values ? \%values : undef;
}

sub get_config {
  my $self = shift;
  my @requested = @_ ? @_ : sort keys %SECTIONS;
  my %config;

  foreach my $name (@requested) {
    if (!$SECTIONS{$name}) {
      Warning("No Foscam command known for section $name");
      next;
    }
    my $values = $self->cgi($SECTIONS{$name}{get});
    $config{$name} = $values if $values;
  }

  return keys %config ? \%config : undef;
} # end sub get_config

sub set_config {
  my ($self, $diff) = @_;
  my $ok = 1;

  foreach my $name (sort keys %$diff) {
    my $section = $SECTIONS{$name};
    if (!$section) {
      Warning("No Foscam command known for section $name");
      next;
    }
    if (ref $$diff{$name} ne 'HASH') {
      Warning("Don't know how to set $name, expected a hash of settings");
      next;
    }
    my %wanted = %{$$diff{$name}};

    if ($section->{field_set}) {
      foreach my $field (sort keys %wanted) {
        my $cmd = $section->{field_set}{$field};
        if (!$cmd) {
          Warning("$name.$field is not writable on this camera");
          next;
        }
        if (!$self->cgi($cmd, {$field => $wanted{$field}})) {
          Error("Failed to set $name.$field");
          $ok = 0;
        }
      }
      next;
    }

    if (!$section->{set}) {
      Warning("$name is read-only, not setting it");
      next;
    }

    # Whole-section writers zero anything left out, so start from what the
    # camera currently has and lay the wanted values on top.
    my $current = $self->cgi($section->{get});
    if (!$current) {
      Error("Failed to read $name back before setting it");
      $ok = 0;
      next;
    }
    my %merged = (%$current, %wanted);
    _prepare_section($name, \%merged);

    if (!$self->cgi($section->{set}, \%merged)) {
      Error("Failed to set $name");
      $ok = 0;
    }
  } # end foreach section

  return $ok;
} # end sub set_config

# Drop the fields a section's reader returns but its writer will not take.
sub _prepare_section {
  my ($name, $values) = @_;

  if ($name eq 'SystemTime') {
    # Under NTP the camera works the clock out for itself and the read-back
    # values would only be a stale round trip.
    if (defined($$values{timeSource}) and $$values{timeSource} == 0) {
      delete $$values{$_} foreach @MANUAL_TIME_FIELDS;
    }
  } elsif ($name eq 'DevState' or $name eq 'DevInfo') {
    # not writable, guarded by the section table
  } elsif ($name eq 'DDNSConfig') {
    # factoryDDNS is the camera's own myfoscam.org name, reported but refused
    delete $$values{factoryDDNS};
  }
  return $values;
}

# ==========================================================================
#
# Time and NTP.
#
# The firmware refuses isDst=1 outright (setSystemTime answers -1), so a
# daylight saving offset has to be folded into timeZone instead of being
# expressed separately.  timeZone is seconds WEST of UTC - EDT, which is
# UTC-4, is 14400 - which is the sign POSIX uses and the opposite of the one
# a tm_gmtoff has.  Both were measured, not taken from the SDK document.
#
# Because DST lives in timeZone, set_time() has to be re-run when the offset
# changes.  It is idempotent, so running it from cron is fine.
#
# ==========================================================================

sub set_time {
  my ($self, %opts) = @_;

  my $ntp_server = $opts{ntp_server};
  if (!defined($ntp_server) or $ntp_server eq '') {
    Error('set_time needs an ntp_server');
    return undef;
  }

  my $timezone = defined($opts{timezone}) ? $opts{timezone} : local_timezone_offset();

  my %settings = (
    timeSource => 0,
    ntpServer  => $ntp_server,
    timeZone   => $timezone,
    isDst      => 0,
    dst        => 0,
  );
  $settings{dateFormat} = $opts{date_format} if defined $opts{date_format};
  $settings{timeFormat} = $opts{time_format} if defined $opts{time_format};

  return $self->set_config({SystemTime => \%settings});
} # end sub set_time

# Seconds west of UTC for this host right now, which is what timeZone wants.
# Callable either as a plain function or as a method, so drop a leading
# invocant - an object or a class name - before reading the argument.
# Otherwise it lands in $when and localtime() numifies it into a nonsense
# instant, which is how this first went wrong: two runs seconds apart worked
# out different offsets from the addresses of two different objects.
sub local_timezone_offset {
  shift if @_ and (ref($_[0]) or (defined($_[0]) and $_[0] !~ /^-?\d+$/));
  my $when = @_ ? shift : time();
  my @local = localtime($when);
  my @gm = gmtime($when);
  # timegm without pulling in Time::Local: the difference of the two
  # renderings of the same instant is the offset.
  my $offset = ($local[2] - $gm[2]) * 3600 + ($local[1] - $gm[1]) * 60 + ($local[0] - $gm[0]);
  my $day_difference = $local[7] - $gm[7];
  if ($local[5] != $gm[5]) {
    # different year, so we crossed new year rather than moved 364 days
    $day_difference = $local[5] < $gm[5] ? -1 : 1;
  } elsif ($day_difference > 1) {
    $day_difference = -1;
  } elsif ($day_difference < -1) {
    $day_difference = 1;
  }
  $offset += $day_difference * 86400;
  return -$offset;
}

sub get_time {
  my $self = shift;
  return $self->cgi('getSystemTime');
}

sub reboot {
  my $self = shift;
  Debug('Rebooting camera');
  return $self->cgi('rebootSystem');
}

# cameratool.pl's update_path uses this to work out what Path a monitor
# should have.  rtspPort is not always 554 on these - the FI9853EP measured
# had web, media and rtsp all on 80.
sub rtsp_url {
  my $self = shift;
  my $ip = @_ ? shift : $self->host();

  my $port = 554;
  my $ports = $self->cgi('getPortInfo');
  $port = $$ports{rtspPort} if $ports and $$ports{rtspPort};

  my ($user, $pass) = $self->_auth();
  my $auth = '';
  $auth = uri_escape($user).':'.uri_escape(defined($pass) ? $pass : '').'@'
    if defined($user) and $user ne '';

  return 'rtsp://'.$auth.$ip.($port == 554 ? '' : ':'.$port).'/videoMain';
}

1;
__END__

=head1 NAME

ZoneMinder::Control::FoscamHD - Foscam CGIProxy.fcgi settings support

=head1 DESCRIPTION

Reads and writes the settings of the Foscam HD cameras that speak the
CGIProxy.fcgi API, and points them at an NTP server.

The Foscam PTZ modules for that generation inherit from this module, so
C<get_config>, C<set_config> and C<set_time> are available on them as well
as on this module used directly for the fixed cameras.

=head1 SYNOPSIS

  my $control = $monitor->Control();
  $control->open();

  my $config = $control->get_config('SystemTime', 'OSDSetting');
  $control->set_time(ntp_server => '192.168.2.66');

=head1 AUTHOR

Isaac Connor, E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2001-2008 Philip Coombes

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.

=cut
