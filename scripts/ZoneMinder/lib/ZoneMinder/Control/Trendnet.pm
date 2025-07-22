package ZoneMinder::Control::Trendnet;

use 5.006;
use strict;
use warnings;
use Time::HiRes qw(usleep);

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

#  You do not need to change the REALM, but you can get slightly faster response
#  by setting so that the first auth request succeeds.
#
#  The username and password should be passed in the ControlAddress field but you
#  can set them here if you want.
#

our $REALM = '';
our $PROTOCOL = 'http://';
our $USERNAME = 'admin';
our $PASSWORD = '';
our $ADDRESS = '';

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
use URI;
use LWP::UserAgent;

sub credentials {
  my $self = shift;
  ($USERNAME, $PASSWORD) = @_;
  Debug("Setting credentials to $USERNAME/$PASSWORD");
}

sub open {
  my $self = shift;
  $self->loadMonitor();

  if ($self->{Monitor}{ControlAddress}
      and
    $self->{Monitor}{ControlAddress} ne 'user:pass@ip'
      and
    $self->{Monitor}{ControlAddress} ne 'user:port@ip'
      and
    ($self->{Monitor}->{ControlAddress} =~ /^(?<PROTOCOL>https?:\/\/)?(?<USERNAME>[^:@]+)?:?(?<PASSWORD>[^\/@]+)?@?(?<ADDRESS>.*)$/)
  ) {
    $PROTOCOL = $+{PROTOCOL} if $+{PROTOCOL};
    $USERNAME = $+{USERNAME} if $+{USERNAME};
    $PASSWORD = $+{PASSWORD} if $+{PASSWORD};
    $ADDRESS = $+{ADDRESS} if $+{ADDRESS};
  } elsif ($self->{Monitor}{Path}) {
    Debug("Using Path for credentials: $self->{Monitor}{Path}");
    my $uri = URI->new($self->{Monitor}{Path});
    if ($uri->userinfo()) {
      Debug("Using Path for credentials: $self->{Monitor}{Path}");
      ( $USERNAME, $PASSWORD ) = split(/:/, $uri->userinfo());
    } elsif ($self->{Monitor}->{User} ) {
      Debug('Using User/Pass for credentials');
      ( $USERNAME, $PASSWORD ) = ($self->{Monitor}->{User}, $self->{Monitor}->{Pass});
    }
    $ADDRESS = $uri->host();

  } else {
    Error('Failed to parse auth from address ' . $self->{Monitor}->{ControlAddress});
    $ADDRESS = $self->{Monitor}->{ControlAddress};
  }
  if ( !($ADDRESS =~ /:/) ) {
    $ADDRESS .= ':80';
  }

  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);
  $self->{state} = 'closed';
  #   credentials:  ("ip:port" (no prefix!), realm (string), username (string), password (string)
  Debug("sendCmd credentials control address:'".$ADDRESS
    ."'  realm:'" . $REALM
    . "'  username:'" . $USERNAME
    . "'  password:'".$PASSWORD
    ."'"
  );

  # Detect REALM
  $REALM = $self->detect_realm($PROTOCOL, $ADDRESS, $REALM, $USERNAME, $PASSWORD, '/cgi/ptdc.cgi');
  if ($REALM) {
    $self->{state} = 'open';
    return !undef;
  }
  return undef;
} # end sub open

sub detect_realm {
  my ($self, $protocol, $address, $realm, $username, $password, $url) = @_;

  $self->{ua}->credentials($address, $realm, $username, $password);
  my $res = $self->{ua}->get($protocol.$address.$url);

  if ($res->is_success) {
    Debug(1, 'Success opening without realm detection for '.$url);
    return $realm;
  }

  if ($res->status_line() ne '401 Unauthorized') {
    return $realm;
  }

  my $headers = $res->headers();
  foreach my $k ( keys %$headers ) {
    Debug("Initial Header $k => $$headers{$k}");
  }

  if ($$headers{'www-authenticate'}) {
    my ( $auth, $tokens ) = $$headers{'www-authenticate'} =~ /^(\w+)\s+(.*)$/;
    if ( $tokens =~ /\w+="([^"]+)"/i ) {
      if ($realm ne $1) {
        $realm = $1;
        Debug("Changing REALM to $realm");
        $self->{ua}->credentials($address, $realm, $username, $password);
        $res = $self->{ua}->get($protocol.$address.$url);
        if ($res->status_line() ne '401 Unauthorized') {
          return $realm;
        }
        Error('Authentication still failed after updating REALM' . $res->status_line);
        $headers = $res->headers();
        foreach my $k ( keys %$headers ) {
          Debug("Initial Header $k => $$headers{$k}");
        }  # end foreach
      } else {
        Error('Authentication failed, not a REALM problem');
      }
    } else {
      Error('Failed to match realm in tokens');
    } # end if
  } else {
    Debug('No headers line');
  } # end if headers
  return '';
}

sub sendCmd {
  # This routine is used for all moving, which are all GET commands...
  my $self = shift;

  if (!$self->{Monitor}->{ModectDuringPTZ}) {
    $$self{Monitor}->suspendMotionDetection();
  }

  my $cmd = shift;
  my $url = $PROTOCOL.$ADDRESS.'/cgi/ptdc.cgi?command='.$cmd;
  my $res = $self->{ua}->get($url);
  Debug('sendCmd command: ' . $url);
  if (!$res->is_success) {
    Error("sendCmdPost Error check failed: '".$res->status_line()."' cmd:");
    my $new_realm = $self->detect_realm($PROTOCOL, $ADDRESS, $REALM, $USERNAME, $PASSWORD, $url);
    if (defined($new_realm) and ($new_realm ne $REALM)) {
      Debug("Success after re-detecting realm. New realm is $new_realm");
      return !undef;
    }
  }

  if (!$self->{Monitor}->{ModectDuringPTZ}) {
    usleep(10000);
    $$self{Monitor}->resumeMotionDetection();
  }
  if ($res->is_success) {
    Debug($res->content);
    return !undef;
  }
  Error("Error check failed: '".$res->status_line()."' cmd:'".$cmd."'");
  return;
}

sub sendCmdPost {

  #
  #   This routine is used for setting/clearing presets and IR commands, which are POST commands...
  #

  my $self = shift;
  my $url = shift;
  my $form = shift;

  if (!$url) {
    Error('no url passed to sendCmdPost');
    return -1;
  }

  Debug('sendCmdPost url: ' . $PROTOCOL.$ADDRESS.$url);

  my $res = $self->{ua}->post(
    $PROTOCOL.$ADDRESS.$url,
    Referer=>$PROTOCOL.$ADDRESS.$url,
    Content=>$form
  );

  Debug("sendCmdPost credentials control to: $PROTOCOL$ADDRESS$url realm:'" . $REALM . "'  username:'" . $USERNAME . "' password:'".$PASSWORD."'");

  if (!$res->is_success) {
    Error("sendCmdPost Error check failed: '".$res->status_line()."' cmd:");
    my $new_realm = $self->detect_realm($PROTOCOL, $ADDRESS, $REALM, $USERNAME, $PASSWORD, $url);
    if (defined($new_realm) and ($new_realm ne $REALM)) {
      Debug("Success after re-detecting realm. New realm is $new_realm");
      return !undef;
    }
    Warning('Failed to reboot');
    return undef;
  }
  Debug($res->content);

  return !undef;
} # end sub sendCmdPost

sub move {
  my $self = shift;
  my $panSteps = shift;
  my $tiltSteps = shift;

  my $cmd = "set_relative_pos&posX=$panSteps&posY=$tiltSteps";
  $self->sendCmd($cmd);
}

sub moveRelUpLeft {
  my $self = shift;
  Debug('Move Up Left');
  $self->move(-3, 3);
}

sub moveRelUp {
  my $self = shift;
  Debug('Move Up');
  $self->move(0, 3);
}

sub moveRelUpRight {
  my $self = shift;
  Debug('Move Up Right');
  $self->move(3, 3);
}

sub moveRelLeft {
  my $self = shift;
  Debug('Move Left');
  $self->move(-3, 0);
}

sub moveRelRight {
  my $self = shift;
  Debug('Move Right');
  $self->move(3, 0);
}

sub moveRelDownLeft {
  my $self = shift;
  Debug('Move Down Left');
  $self->move(-3, -3);
}

sub moveRelDown {
  my $self = shift;
  Debug('Move Down');
  $self->move(0, -3);
}

sub moveRelDownRight {
  my $self = shift;
  Debug('Move Down Right');
  $self->move(3, -3);
}

# moves the camera to center on the point that the user clicked on in the video image. 
# This isn't mega accurate but good enough for most purposes 

sub moveMap {

  # If the camera moves too much, increase hscale and vscale. (...if it doesn't move enough, try decreasing!)
  # They scale the movement and are here to compensate for manufacturing variation.
  # It's never going to be perfect, so just get somewhere in the ballpark and call it a day.
  #  (Don't forget to kill the zmcontrol process while tweaking!)

  #  1280x800
  my $hscale = 31;
  my $vscale = 25;

  #  1280x800 with fisheye
  #my $hscale = 15;
  #my $vscale = 15;

  #  640x400
  #my $hscale = 14;
  #my $vscale = 12;


  my $self = shift;
  my $params = shift;
  my $xcoord = $self->getParam( $params, 'xcoord' );
  my $ycoord = $self->getParam( $params, 'ycoord' );

  my $hor = ($xcoord - ($self->{Monitor}->{Width} / 2))/$hscale;
  my $ver = ($ycoord - ($self->{Monitor}->{Height} / 2))/$vscale;

  $hor = int($hor);
  $ver = -1 * int($ver);

  Debug("Move Map to $xcoord,$ycoord, hor=$hor, ver=$ver");
  $self->move($hor, $ver);
}


#  ****  PRESETS  ****
#
#  OK, presets work a little funky but they DO work, provided you define them 
#  in order and don't skip any. 
#  
#  The problem is that when you load the web page for this camera, it gives a 
#  list of preset names tied to index numbers.
#  So let's say you have four presets...  A, B, C, and D, and defined them in 
#  that order.
#  So A is index 0, B is index 1, C is index 2, D is index 3.  When you tell 
#  the camera to go to a preset, you actually tell it by number, not by name. 
#  (So "Go to D" is really "go to index 3".)
#
#  Now let's say somebody deletes C via the camera's web GUI.  The camera 
#  re-numbers the existing presets A=0, B=1, D=2.  
#  There's really no easy way for ZM to discover this re-numbering, so 
#  zoneminder would still send "go to preset 3" thinking
#  it's telling the camera to go to point D.  In actuality it's telling the 
#  camera to go to a preset that no longer exists.
#  
#  As long as you define your presets in order (i.e. define preset 1, then 
#  preset 2, then preset 3, etc.) everything will work just 
#  fine in ZoneMinder.
#
#  (Home preset needs to be set via the camera's web gui, and is unaffected by 
#  any of this.)
#
#  So that's the limitation:  DEFINE YOUR PRESETS IN ORDER THROUGH (and only 
#  through!) ZM AND DON'T SKIP ANY.
#

sub presetClear {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  my $cmd = "presetName=$preset&command=del";
  my $url = '/eng/admin/cam_control.cgi';
  Debug('presetClear: ' . $preset . ' cmd: ' . $cmd);
  $self->sendCmdPost($url,{presetName=>$preset, command=>'del'});
}

sub presetSet {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  my $cmd = "presetName=$preset&command=add";
  my $url = '/eng/admin/cam_control.cgi';
  Debug('presetSet ' . $preset . ' cmd: ' . $cmd);
  $self->sendCmdPost($url,{presetName=>$preset, command=>'add', Submit=>'Add'});
}

sub presetGoto {
  my $self = shift;
  my $params = shift;
  my $preset = $self->getParam($params, 'preset');
  $preset = $preset - 1;
  Debug("Goto Preset $preset");
  my $cmd = "goto_preset_position&index=$preset";
  $self->sendCmd($cmd);
}

sub presetHome {
  my $self = shift;
  Debug('Home Preset');
  my $cmd = 'go_home';
  $self->sendCmd($cmd);
}

#
#  ****  IR CONTROLS  ****
#
#
#   Wake:  Force IR on, always.  (always night mode)
#
#   Sleep:  Force IR off, always.  (always day mode)
#
#   Reset:  Automatic IR mode.  (day/night mode determined by camera)
#

sub wake {
  #  force IR on  ("always night mode")

  my $self = shift;
  my $url = '/eng/admin/adv_audiovideo.cgi';
  my $cmd = 'irMode=3';

  Debug('Wake -- IR on');

  $self->sendCmdPost($url,$cmd);
}

sub sleep {
  #  force IR off ("always day mode")

  my $self = shift;
  my $url = '/eng/admin/adv_audiovideo.cgi';
  my $cmd = 'irMode=2';

  Debug('Sleep -- IR off');

  $self->sendCmdPost($url,$cmd);
}

sub reset {
  #  IR auto

  my $self=shift;
  my $url = '/eng/admin/adv_audiovideo.cgi';
  my $cmd = 'irMode=0';

  Debug('Reset -- IR auto');

  $self->sendCmdPost($url,$cmd);
}

sub reboot {
  my $self = shift;
  Debug('Camera Reboot');
  if ($$self{state} ne 'open') {
    Warning("Not open. opening. Should call ->open() before calling reboot()"); 
    return if !$self->open();
  }
  $self->sendCmdPost('/eng/admin/reboot.cgi', { reboot => 'true' });
  #$referer = 'http://'.$HI->ip().'/eng/admin/tools_default.cgi';
  #$initial_url = $HI->ip().'/eng/admin/tools_default.cgi';
}

sub ping {
  return -1 if ! $ADDRESS;

  require Net::Ping;

  my $p = Net::Ping->new();
  my $rv = $p->ping($ADDRESS);
  $p->close();
  return $rv;
}

1;
__END__

=head1 NAME

ZoneMinder::Control::Trendnet - Perl module for Trendnet cameras

=head1 SYNOPSIS

use ZoneMinder::Control::Trendnet;
place this in /usr/share/perl5/ZoneMinder/Control

=head1 DESCRIPTION

This module contains the implementation of the Trendnet # IP camera control
protocol. Has been tested with TV-IP862IC

Under control capability:

*  Main:  Can wake, can sleep, can reset
*  Move:  Can move, can move diagonally, can move mapped, can move relative
*  Pan:  Can pan
*  Tilt:  Can tilt
*  Presets:  Has presets, num presets 20, has home preset  (don't set presets via camera's web server, only set via ZM.)

Under control tab in the monitor itself:

Controllable
Control type is the name you gave it in control capability above
Control address is the camera's ip address AND web port.  example:  192.168.1.1:80 
You can also put the authentication information here and change the
protocol to https using something like https://admin:password@192.168.1.1:80

=head2 EXPORT

None by default.

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2018 ZoneMinder LLC

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

=cut
