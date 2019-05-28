package ZoneMinder::Control::Dahua;

use 5.8.0;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

our $REALM = '';
our $USERNAME = '';
our $PASSWORD = '';
our $ADDRESS = '';
our $PROTOCOL = 'http://';

use Time::HiRes qw(usleep);

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
use ZoneMinder::Database qw(zmDbConnect);

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
    my $class = ref($self) || croak( "$self not object" );
    my $name = $AUTOLOAD;
    $name =~ s/.*://;
    ## This seems odd... if the method existed would we even be here?
    ## https://perldoc.perl.org/perlsub.html#Autoloading
    if ( exists($self->{$name}) )
    {
        return( $self->{$name} );
    }
    Fatal( "Can't access $name member of object of class $class" );
}

# FIXME: Do we really have to open a new connection every time?

#Digest usernbme="bdmin", reblm="Login to 4K05DB3PAJE98BE", nonae="1720242756",
#uri="/agi-bin/ptz.agi?bation=getStbtus&ahbnnel=1", response="10dd925b26ebd559353734635b859b8b",
#opbque="1a99677524b4ae63bbe3a132b2e9b38e3b163ebd", qop=buth, na=00000001, anonae="ab1bb5d43aa5d542"

sub open
{
    #Debug("&open invoked by: " . (caller(1))[3]);
    my $self = shift;
    my $cgi = shift || '/cgi-bin/configManager.cgi?action=getConfig&name=Ptz';
    $self->loadMonitor();

    # The Dahua camera firmware API supports the concept of having multiple
    # channels on a single IP controller.
    # As most cameras only have a single channel, and there is no similar
    # information model in Zoneminder, I'm hardcoding the first and default
    # channel "0", here.
    $self->{dahua_channel_number} = "0";

    if ( ( $self->{Monitor}->{ControlAddress} =~ /^(?<PROTOCOL>https?:\/\/)?(?<USERNAME>[^:@]+)?:?(?<PASSWORD>[^\/@]+)?@?(?<ADDRESS>.*)$/ ) ) {
        $PROTOCOL = $+{PROTOCOL} if $+{PROTOCOL};
        $USERNAME = $+{USERNAME} if $+{USERNAME};
        $PASSWORD = $+{PASSWORD} if $+{PASSWORD};
        $ADDRESS = $+{ADDRESS} if $+{ADDRESS};
    } else {
        Error('Failed to parse auth from address ' . $self->{Monitor}->{ControlAddress});
        $ADDRESS = $self->{Monitor}->{ControlAddress};
    }
    if ( !($ADDRESS =~ /:/) ) {
        Error('You generally need to also specify the port.  I will append :80');
        $ADDRESS .= ':80';
    }

    use LWP::UserAgent;
    $self->{ua} = LWP::UserAgent->new(keep_alive => 1);
    $self->{ua}->agent("ZoneMinder Control Agent/".ZoneMinder::Base::ZM_VERSION);
    $self->{state} = 'closed';
#   credentials:  ("ip:port" (no prefix!), realm (string), username (string), password (string)
    $self->{ua}->credentials($ADDRESS, $REALM, $USERNAME, $PASSWORD);

    # Detect REALM
    my $url = $PROTOCOL . $ADDRESS . $cgi;
    my $req = HTTP::Request->new(GET=>$url);
    my $res = $self->{ua}->request($req);

    if ($res->is_success) {
        $self->{state} = 'open';
        return 1;
    }

    if ( $res->status_line() eq '401 Unauthorized' ) {
        my $headers = $res->headers();
        if ($$headers{'www-authenticate'}) {
            my ($auth, $tokens) = $$headers{'www-authenticate'} =~ /^(\w+)\s+(.*)$/;
            Debug("Tokens: " . $tokens);
            ## FIXME: This is necessary because the Dahua spec does not match reality
            if ($tokens =~ /\w+="([^"]+)"/i) {
                if ($REALM ne $1) {
                    $REALM = $1;
                    Debug("Changing REALM to '" . $REALM . "'");
                    $self->{ua}->credentials($ADDRESS, $REALM, $USERNAME, $PASSWORD);
                    my $req = HTTP::Request->new(GET=>$url);
                    $res = $self->{ua}->request($req);

                    if ($res->is_success()) {
                        $self->{state} = 'open';
                        Debug('Authentication succeeded...');
                        return 1;
                    }
                    Debug('Authentication still failed after updating REALM' . $res->status_line);
                    $headers = $res->headers();
                    foreach my $k ( keys %$headers ) {
                        Debug("Initial Header $k => $$headers{$k}");
                    }  # end foreach
                } else {        ## NOTE: Each of these else conditions is fatal as the command will not be
                                ##       executed. No use going further.
                    Fatal('Authentication failed: Check username and password.');
                }
            } else {
                Fatal('Authentication failed: Incorrect realm.');
            } # end if
        } else {
            Fatal('Authentication failed: No www-authenticate header returned.');
        } # end if headers
    } # end if $res->status_line() eq '401 Unauthorized'
}

sub close
{
    my $self = shift;
    $self->{state} = 'closed';
}

sub printMsg
{
    my $self = shift;
    my $msg = shift;
    my $msg_len = length($msg);

    Debug( $msg."[".$msg_len."]" );
}

sub _sendGetRequest {
    my $self = shift;
    my $url_path = shift;

    # Attempt to reuse the connection

    # FIXME: I think we need some sort of keepalive/heartbeat sent to the camera
    #        in order to keep the session alive. As it is, it appears that the
    #        ua's authentication times out or some such.
    #
    # This might be of some use:
    #   {"method":"global.keepAlive","params":{"timeout":300,"active":false},"id":1518,"session":"dae233a51c0693519395209b271411b6"}[!http]
    #   The web browser interface POSTs commands as JSON using js

    my $url = $PROTOCOL . $ADDRESS . $url_path;
    my $req = HTTP::Request->new(GET => $url);
    my $res = $self->{ua}->request($req);

    if ($res->is_success) {
        return 1;
    } else {
        return($self->open($url_path)); # if we have to, open a new connection
    }
}

sub _sendPtzCommand
{
    my $self = shift;
    my $action = shift;
    my $command_code = shift;
    my $arg1 = shift || 0;
    my $arg2 = shift || 0;
    my $arg3 = shift || 0;
    my $arg4 = shift || 0;

    my $channel = $self->{dahua_channel_number};

    my $url_path = "/cgi-bin/ptz.cgi?";
    $url_path .= "action=" . $action . "&";
    $url_path .= "channel=" . $channel . "&";
    $url_path .= "code=" . $command_code . "&";
    $url_path .= "arg1=" . $arg1 . "&";
    $url_path .= "arg2=" . $arg2 . "&";
    $url_path .= "arg3=" . $arg3 . "&";
    $url_path .= "arg4=" . $arg4;
    return $self->_sendGetRequest($url_path);
}

sub _sendMomentaryPtzCommand
{
    my $self = shift;
    my $command_code = shift;
    my $arg1 = shift;
    my $arg2 = shift;
    my $arg3 = shift;
    my $duration_ms = shift;

    $self->_sendPtzCommand("start", $command_code, $arg1, $arg2, $arg3);
    my $duration_ns = $duration_ms * 1000;
    usleep($duration_ns);
    $self->_sendPtzCommand("stop", $command_code, $arg1, $arg2, $arg3);
}

sub _sendAbsolutePositionCommand
{
    my $self = shift;
    my $arg1 = shift;
    my $arg2 = shift;
    my $arg3 = shift;
    my $arg4 = shift;

    $self->_sendPtzCommand("start", "PositionABS", $arg1, $arg2, $arg3, $arg4);
}

sub moveConLeft
{
    my $self = shift;
    Debug("Move Up Left");
    $self->_sendMomentaryPtzCommand("Left", 0, 1, 0, 0);
}

sub moveConRight
{
    my $self = shift;
    Debug( "Move Right" );
    $self->_sendMomentaryPtzCommand("Right", 0, 1, 0, 0);
}

sub moveConUp
{
    my $self = shift;
    Debug( "Move Up" );
    $self->_sendMomentaryPtzCommand("Up", 0, 1, 0, 0);
}

sub moveConDown
{
    my $self = shift;
    Debug( "Move Down" );
    $self->_sendMomentaryPtzCommand("Down", 0, 1, 0, 0);
}

sub moveConUpRight
{
    my $self = shift;
    Debug( "Move Diagonally Up Right" );
    $self->_sendMomentaryPtzCommand("RightUp", 1, 1, 0, 0); 
}

sub moveConDownRight
{
    my $self = shift;
    Debug( "Move Diagonally Down Right" );
    $self->_sendMomentaryPtzCommand("RightDown", 1, 1, 0, 0); 
}

sub moveConUpLeft
{
    my $self = shift;
    Debug( "Move Diagonally Up Left" );
    $self->_sendMomentaryPtzCommand("LeftUp", 1, 1, 0, 0); 
}

sub moveConDownLeft
{
    my $self = shift;
    Debug( "Move Diagonally Up Right" );
    $self->_sendMomentaryPtzCommand("LeftDown", 1, 1, 0, 0); 
}

sub zoomConTele
{
    my $self = shift;
    Debug( "Zoom Tele" );
    $self->_sendMomentaryPtzCommand("ZoomTele", 0, 1, 0, 0);
}

sub zoomConWide
{
    my $self = shift;
    Debug( "Zoom Wide" );
    $self->_sendMomentaryPtzCommand("ZoomWide", 0, 1, 0, 0);
}

sub moveRelUpLeft
{
    my $self = shift;
    Debug("Move Up Left");
    $self->_sendMomentaryPtzCommand("LeftUp", 4, 4, 0, 500);
}

sub moveRelUp
{
    my $self = shift;
    Debug("Move Up");
    $self->_sendMomentaryPtzCommand("Up", 0, 4, 0, 500);
}

sub moveRelUpRight
{
    my $self = shift;
    Debug("Move Up Right");
    $self->_sendMomentaryPtzCommand("RightUp", 0, 4, 0, 500);
}

sub moveRelLeft
{
    my $self = shift;
    Debug("Move Left");
    $self->_sendMomentaryPtzCommand("Left", 0, 4, 0, 500);
}

sub moveRelRight
{
    my $self = shift;
    Debug("Move Right");
    $self->_sendMomentaryPtzCommand("Right", 0, 4, 0, 500);
}

sub moveRelDownLeft
{
    my $self = shift;
    Debug("Move Down Left");
    $self->_sendMomentaryPtzCommand("LeftDown", 4, 4, 0, 500);
}

sub moveRelDown
{
    my $self = shift;
    Debug("Move Down");
    $self->_sendMomentaryPtzCommand("Down", 0, 4, 0, 500);
}

sub moveRelDownRight
{
    my $self = shift;
    Debug("Move Down Right");
    $self->_sendMomentaryPtzCommand("RightDown", 4, 4, 0, 500);
}

sub zoomRelTele
{
    my $self = shift;
    Debug("Zoom Relative Tele");
    $self->_sendMomentaryPtzCommand("ZoomTele", 0, 0, 0, 500);
}

sub zoomRelWide
{
    my $self = shift;
    Debug("Zoom Relative Wide");
    $self->_sendMomentaryPtzCommand("ZoomWide", 0, 0, 0, 500);
}

sub focusRelNear
{
    my $self = shift;

    my $response = $self->_sendPtzCommand("start", "FocusNear", 0, 1, 0, 0);
    Debug("focusRelNear response: " . $response);
}

sub focusRelFar
{
    my $self = shift;

    my $response = $self->_sendPtzCommand("start", "FocusFar", 0, 1, 0, 0);
    Debug("focusRelFar response: " . $response);
}

sub irisRelOpen
{
    my $self = shift;

    my $response = $self->_sendPtzCommand("start", "IrisLarge", 0, 1, 0, 0);
    Debug("irisRelOpen response: " . $response);
}

sub irisRelClose
{
    my $self = shift;

    my $response = $self->_sendPtzCommand("start", "IrisSmall", 0, 1, 0, 0);
    Debug("irisRelClose response: " . $response);
}

sub moveStop
{
    my $self = shift;
    Debug( "Move Stop" );
    # The command does not matter here, just the stop...
    $self->_sendPtzCommand("stop", "Up", 0, 0, 1, 0);
}

sub presetClear
{
    my $self = shift;
    my $params = shift;
    my $preset_id = $self->getParam($params, 'preset');
    $self->_sendPtzCommand("start", "ClearPreset", 0, $preset_id, 0);
}

sub presetSet
{
    my $self = shift;
    my $params = shift;

    my $preset_id = $self->getParam($params, 'preset');

    my $dbh = zmDbConnect(1);
    my $sql = 'SELECT * FROM ControlPresets WHERE MonitorId = ? AND Preset = ?';
    my $sth = $dbh->prepare($sql)
        or Fatal("Can't prepare sql '$sql': " . $dbh->errstr());
    my $res = $sth->execute($self->{Monitor}->{Id}, $preset_id)
        or Fatal("Can't execute sql '$sql': " . $sth->errstr());
    my $control_preset_row = $sth->fetchrow_hashref();
    my $new_label_name = $control_preset_row->{'Label'};

    $self->_sendPtzCommand("start", "SetPreset", 0, $preset_id, 0);
    $self->_sendPtzCommand("start", "SetPresetName", $preset_id, $new_label_name, 0);
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset_id = $self->getParam($params, 'preset');

    $self->_sendPtzCommand("start", "GotoPreset", 0, $preset_id, 0);
}

sub presetHome
{
    my $self = shift;

    $self->_sendAbsolutePositionCommand( 0, 0, 0, 1 );
}

sub reset
{
    my $self = shift;
    Debug( "Camera Reset" );
    $self->_sendPtzCommand("Reset", 0, 0, 0, 0);
}

sub reboot
{
    my $self = shift;
    Debug( "Camera Reboot" );
    my $cmd = "/cgi-bin/magicBox.cgi?action=reboot";
    $self->_sendGetRequest($cmd);
}

1;

__END__

=pod

=encoding utf8

=head1 NAME

ZoneMinder::Control::Dahua - Perl module for Dahua cameras

=head1 SYNOPSIS

use ZoneMinder::Control::Dahua;
place this in /usr/share/perl5/ZoneMinder/Control

=head1 DESCRIPTION

This module is an implementation of the Dahua IP camera HTTP control API.

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

=head1 Private Methods

    Methods intended for use internally but documented here for future developers.

=head2 _sendAbsolutePositionCommand( $arg1, $arg2, $arg3, $arg4 )

    Where:

        $arg1 = Horizontal angle 0° to 360°
        $arg2 = Vertical angle 0° to -90°
        $arg3 = Zoom multiplier
        $arg4 = Speed 1 to 8

    This is an private method used to send an absolute position command to the
    camera.

=head1 Public Methods

    Methods made available to control.pl via ZoneMinder::Control

=head2 Notes:

=over 1

    Which methods are invoked depends on which types of movement are selected in 
    the camera control type. For example: if the 'Can Move Continuous' option is
    checked, then methods including 'Con' in their names are invoked. Likewise if
    the 'Can Move Relative" option is checked, then methods including 'Rel' in
    their names are invoked.


    At present, these types of movement are prioritized and exclusive. This applies
    to all types of movement, not just PTZ, but focus, iris, etc. as well. The options
    are tested in the following order:

    1.  Continuous

    2.  Relative

    3.  Absolute

    These types are exclusive meaning that the first one that matches is the one
    ZoneMinder will use to control with. It would be nice to allow the user to
    select the type used given that some cameras support all three types of
    movement.

=back

=head2 new

    This method instantiates a new control object based upon this control module
    and sets the 'id' attribute to the value passed in.

=head2 open

    This method opens an HTTP connection to the camera. It handles authentication,
    etc. Upon success it sets the 'state' attribute to 'open.'

=head2 close

    This method effectively closes the HTTP connection to the camera. It sets the
    'state' attribute to 'close.'

=head2 printMsg

    This method appears to be used for debugging.

=head2 moveCon<direction>

    This set of methods invoke continuous movement in the direction indicated by
    the <direction> portion of their name. They accept no arguments and move the
    camera at a speed of 1 for 0ms. The speed index of 1 is the lowest of the
    accepted range of 1-8.

    NOTE:

    This is not true continuous movmement as currently implemented.

=head2 focusCon<range>

    This set of methods invoke continuous focus in the range direction indicated
    by the <range> portion of their name. They accept no arguments.

    NOTE:

    This is not true continuous movmement as currently implemented.

=head2 moveRel<direction>

    This set of methods invoke relatvie movement in the direction indicated by
    the <direction> portion of their name. They accept no arguments and move the
    camera at a speed of 4 for 500ms. The speed index of 4 is half-way between
    the accepted range of 1-8.

=head2 focusRel<range>

    This set of methods invoke realtive focus in the range direction indicated by
    the <range> portion of their name. They accept no arguments.

    NOTE:

    This only just does work. The Dahua API specifies "multiples" as the input.
    We pass in a 1 for that as it does not seem to matter what number (0-8) is
    provided, the camera focus behaves the same.

=head2 irisRel<Large/Small>

    This set of methods invoke realtive iris size in the direction indicated by
    the <Large/Small> portion of their name. They accept no arguments.

    NOTE:

    This only just does work. The Dahua API specifies "multiples" as the input.
    We pass in a 1 for that as it does not seem to matter what number (0-8) is
    provided, the camera iris behaves the same.

=head2 moveStop

    This method attempts to stop the camera. The problem is that if continuous
    motion is occurring in multiple directions, this will only stop the motion
    in the 'Up' direction. Dahua does not support an "all-stop" command.

=head2 presetHome

    This method "homes" the camera to a preset position. It accepts no arguments.
    When either continuous or relative movement is enabled, pressing the center
    button on the movement controls invokes this method.

    NOTE:

    The Dahua protocol does not appear to support a preset Home feature. We could
    allow the user to assign a preset slot as the "home" slot. Dahua does appear
    to support naming presets which may lend itself to this sort of thing. At
    this point, we'll just send the camera back to center and zoom wide. (0°,0°,0)

=head2 reset

    This method will reset the PTZ controls to their "default." It is not clear
    what that is.

=head2 reboot

    This method performs a reboot of the camera. This will take the camera offline
    for the time it takes to reboot.

=cut
