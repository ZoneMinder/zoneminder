# ==========================================================================
#
# ZoneMinder ONVIF IP Speaker Control Module
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# ==========================================================================
#
# Control an ONVIF/SIP IP speaker that exposes the /api/spkplay and
# /cgi-bin/CGI interfaces.  Developed against an "IP Speaker" reporting
# firmware CS20-V3.3.45N (build Nov 27 2024), ONVIF scope
# onvif://www.onvif.org/type/IPSpeaker.
#
# The speaker plays sound files it already holds; ZoneMinder selects one by
# id and starts or stops it.  Nothing is streamed to the device.
#
package ZoneMinder::Control::IPSpeaker;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
use JSON::MaybeXS qw(decode_json);
use LWP::UserAgent;

our $VERSION = $ZoneMinder::Base::VERSION;

# The firmware accepts two windows of file ids: a fixed set of built-in
# sounds and a set of slots the operator uploads into.  Anything else is
# refused with result -2, and an id inside a window with no file uploaded is
# refused with result -3.
use constant SYSTEM_FILE_MIN => 10;
use constant SYSTEM_FILE_MAX => 14;
use constant USER_FILE_MIN   => 20;
use constant USER_FILE_MAX   => 30;

use constant VOLUME_MIN  => 0;
use constant VOLUME_MAX  => 100;
use constant VOLUME_STEP => 5;

# config=audio.set replaces the whole audio section: any field left out of the
# POST reverts to a firmware default, silently taking the microphone, codec
# list and echo-cancellation settings with it.  Every write therefore reads the
# section first and echoes it back.  This is the field list audio.set accepts;
# audio.get also returns 'outmute', which audio.set rejects, so it is excluded.
our @AUDIO_FIELDS = qw(
  micgain micenable micvolume outvolume audiojitterbuffer codec playring
  automute aecenable aectime agcenable agcmode agctarget agcgain nrenable
  nrlevel michpfenable outhpfenable outnrenable outnrlevel
);

# --------------------------------------------------------------------------
# Pure helpers.  These carry the firmware's rules and are unit tested without
# a device (see t/ip_speaker.t).
# --------------------------------------------------------------------------

sub valid_fileid {
  my ($id) = @_;
  return 0 if !defined $id;
  return 0 if $id !~ /^\d+$/;
  return 1 if $id >= SYSTEM_FILE_MIN and $id <= SYSTEM_FILE_MAX;
  return 1 if $id >= USER_FILE_MIN and $id <= USER_FILE_MAX;
  return 0;
}

sub clamp_volume {
  my ($v) = @_;
  return VOLUME_MIN if $v < VOLUME_MIN;
  return VOLUME_MAX if $v > VOLUME_MAX;
  return $v;
}

# Returns undef when the current volume could not be read, so a caller never
# writes a guessed level over whatever the device actually has.
sub stepped_volume {
  my ($current, $delta) = @_;
  return undef if !defined $current;
  return undef if $current !~ /^-?\d+$/;
  return clamp_volume($current + $delta);
}

sub spk_path {
  my ($action, $fileid) = @_;
  return '/api/spkplay?action='.$action.'&fileid='.$fileid;
}

sub audio_set_form {
  my ($current, %override) = @_;
  my %form;
  foreach my $field (@AUDIO_FIELDS) {
    $form{$field} = $current->{$field} if exists $current->{$field};
    $form{$field} = $override{$field} if exists $override{$field};
  }
  return \%form;
}

# --------------------------------------------------------------------------
# Device conversation
# --------------------------------------------------------------------------

sub open {
  my $self = shift;
  $self->loadMonitor();

  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION());

  if (!$self->guess_credentials()) {
    Error('IPSpeaker: unable to determine the speaker address from ControlAddress or Path');
    return undef;
  }
  # get() concatenates BaseURL with a path that already starts with a slash.
  $$self{BaseURL} =~ s{/$}{} if $$self{BaseURL};

  $self->{state} = 'open';
  return 1;
}

# Decode a firmware reply.  Every endpoint answers
# {"result":0, "reason":"OK", ...}, with a negative result and a human
# readable reason on failure.
sub decode_reply {
  my ($self, $res, $what) = @_;

  if (!$res or !$res->is_success) {
    Error("IPSpeaker: $what failed: ".($res ? $res->status_line : 'no response'));
    return undef;
  }
  my $data = eval { decode_json($res->decoded_content) };
  if (!$data) {
    Error("IPSpeaker: $what returned unparseable content: ".$res->decoded_content);
    return undef;
  }
  if (defined $data->{result} and $data->{result} != 0) {
    Error("IPSpeaker: $what refused: result=$data->{result} ".($data->{reason} // ''));
    return undef;
  }
  return $data;
}

sub getAudioConfig {
  my $self = shift;
  my $data = $self->decode_reply($self->get('/cgi-bin/CGI?config=audio.get'), 'audio.get');
  return $data ? $data->{data} : undef;
}

sub setAudioConfig {
  my ($self, %override) = @_;

  my $current = $self->getAudioConfig();
  if (!$current) {
    Error('IPSpeaker: refusing to write the audio section without reading it first');
    return undef;
  }
  my $form = audio_set_form($current, %override);
  return $self->decode_reply(
    $self->post('/cgi-bin/CGI?config=audio.set', $form), 'audio.set');
}

# --------------------------------------------------------------------------
# Control commands
# --------------------------------------------------------------------------

sub audioPlay {
  my ($self, $params) = @_;

  my $fileid = $self->getParam($params, 'file', SYSTEM_FILE_MIN);
  if (!valid_fileid($fileid)) {
    Error("IPSpeaker: refusing to play file id '$fileid': outside the "
      .SYSTEM_FILE_MIN.'-'.SYSTEM_FILE_MAX.' and '
      .USER_FILE_MIN.'-'.USER_FILE_MAX.' windows this firmware accepts');
    return undef;
  }
  Debug("IPSpeaker: playing file $fileid");
  my $data = $self->decode_reply($self->get(spk_path('start', $fileid)), "play $fileid");
  # Remember what was started so stop can name it without the ui having to.
  $$self{playing} = $fileid if $data;
  return $data ? 1 : undef;
}

sub audioStop {
  my ($self, $params) = @_;

  # The firmware wants a file id on stop too; it stops playback whichever
  # valid id is given, so fall back to whatever we last started.
  my $fileid = $self->getParam($params, 'file', $$self{playing} // SYSTEM_FILE_MIN);
  $fileid = SYSTEM_FILE_MIN if !valid_fileid($fileid);

  Debug("IPSpeaker: stopping playback (file $fileid)");
  my $data = $self->decode_reply($self->get(spk_path('stop', $fileid)), 'stop');
  delete $$self{playing} if $data;
  return $data ? 1 : undef;
}

sub audioVolumeUp   { my ($self, $params) = @_; $self->stepVolume($params, 1); }
sub audioVolumeDown { my ($self, $params) = @_; $self->stepVolume($params, -1); }

sub stepVolume {
  my ($self, $params, $direction) = @_;

  my $step = $self->getParam($params, 'step', VOLUME_STEP);
  my $current = $self->getAudioConfig();
  if (!$current) {
    Error('IPSpeaker: cannot change volume without reading the current level');
    return undef;
  }
  my $wanted = stepped_volume($current->{outvolume}, $direction * $step);
  if (!defined $wanted) {
    Error('IPSpeaker: device reported an unreadable outvolume '
      .($current->{outvolume} // 'undef'));
    return undef;
  }
  Debug("IPSpeaker: volume $current->{outvolume} -> $wanted");
  return $self->setAudioConfig(outvolume => $wanted) ? 1 : undef;
}

# Opt-in query used by sendControlCommandWithResponse so the ui can show the
# level the device actually holds rather than one ZoneMinder assumed.
sub audioVolumeStatus {
  my $self = shift;
  my $current = $self->getAudioConfig();
  return defined $current ? $current->{outvolume} : undef;
}

1;
__END__

=head1 NAME

ZoneMinder::Control::IPSpeaker - control an ONVIF/SIP IP speaker

=head1 DESCRIPTION

Plays and stops sound files already stored on an IP speaker and adjusts its
output volume, over the device's own HTTP interface.

Developed against a device reporting ONVIF C<Manufacturer> "IPSpeaker",
C<Model> "IP Speaker" and firmware C<CS20-V3.3.45N (build Nov 27 2024)>,
advertising the C<onvif://www.onvif.org/type/IPSpeaker> scope.  Playback is
driven over the vendor HTTP interface rather than ONVIF, because the ONVIF
audio output service on this firmware only describes the output; it offers no
way to start a stored file.

=head2 File ids

Sound files are addressed by a numeric id in one of two windows: 10-14 for the
built-in sounds, and 20-30 for slots the operator uploads into through the
device's own web interface.  An id outside both windows is refused, and an id
in a window whose slot is empty is refused separately.  Set C<MinAudioFile>
and C<MaxAudioFile> on the Controls entry to the range that should appear in
the ZoneMinder ui.

=head2 Volume

C<audioVolumeUp> and C<audioVolumeDown> step C<outvolume> by C<--step>
(default 5), clamped to 0-100.  Each write reads the device's whole audio
section and echoes it back with the new level, because this firmware treats
C<config=audio.set> as a replacement of the entire section: a partial write
resets the microphone, codec and echo-cancellation settings.

Note that the volume the device reports is the configured level.  The
loudspeaker's acoustic output was not confirmed against it, so verify by ear
before relying on a particular value.

=head1 METHODS

=over 4

=item B<audioPlay> - start a stored sound; takes C<--file=N>.

=item B<audioStop> - stop playback.

=item B<audioVolumeUp> / B<audioVolumeDown> - step the output volume by C<--step=N>.

=item B<audioVolumeStatus> - return the configured output volume.

=back

=head1 AUTHOR

The ZoneMinder project

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2026 ZoneMinder LLC

This library is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License version 2 or later.

=cut
