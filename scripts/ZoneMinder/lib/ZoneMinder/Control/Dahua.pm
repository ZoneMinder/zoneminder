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
    if ( exists($self->{$name}) )
    {
        return( $self->{$name} );
    }
    Fatal( "Can't access $name member of object of class $class" );
}

sub open
{
    my $self = shift;
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
    $self->{ua} = LWP::UserAgent->new;
    $self->{ua}->agent("ZoneMinder Control Agent/".$ZoneMinder::Base::ZM_VERSION);
    $self->{state} = 'closed';
#   credentials:  ("ip:port" (no prefix!), realm (string), username (string), password (string)
    Debug("sendCmd credentials control address:'".$ADDRESS
           ."'  realm:'" . $REALM
           . "'  username:'" . $USERNAME
           . "'  password:'".$PASSWORD
           ."'"
    ); 
    $self->{ua}->credentials($ADDRESS, $REALM, $USERNAME, $PASSWORD);

    # Detect REALM
    my $get_config_url = $PROTOCOL . $ADDRESS . "/cgi-bin/configManager.cgi?action=getConfig&name=Ptz";
    my $req = HTTP::Request->new(GET=>$get_config_url);
    my $res = $self->{ua}->request($req);

    if ($res->is_success) {
        $self->{state} = 'open';
        return;
    }

    if ( $res->status_line() eq '401 Unauthorized' ) {
        my $headers = $res->headers();
        foreach my $k (keys %$headers) {
            Debug("Initial Header $k => $$headers{$k}");
        }

        if ($$headers{'www-authenticate'}) {
            my ($auth, $tokens) = $$headers{'www-authenticate'} =~ /^(\w+)\s+(.*)$/;
            if ($tokens =~ /\w+="([^"]+)"/i) {
                if ($REALM ne $1) {
                    $REALM = $1;
                    Debug("Changing REALM to '" . $REALM . "'");
                    $self->{ua}->credentials($ADDRESS, $REALM, $USERNAME, $PASSWORD);
                    my $req = HTTP::Request->new(GET=>$get_config_url);
                    $res = $self->{ua}->request($req);
                    if ($res->is_success()) {
                        $self->{state} = 'open';
                        return;
                    }
                    Debug('Authentication still failed after updating REALM' . $res->status_line);
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
            Error('No WWW-Authenticate Header');
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

sub sendGetRequest {
    my $self = shift;
    my $url_path = shift;

    my $result = undef;

    my $url = $PROTOCOL . $ADDRESS . $url_path;
    my $req = HTTP::Request->new(GET=>$url);

    my $res = $self->{ua}->request($req);

    if ($res->is_success) {
        $result = !undef;
    } else {
        if ($res->status_line() eq '401 Unauthorized') {
            Debug("Error check failed, trying again: USERNAME: $USERNAME realm: $REALM password: " . $PASSWORD);
            Debug("Content was " . $res->content() );
            my $res = $self->{ua}->request($req);
            if ($res->is_success) {
                $result = !undef;
            } else {
                Error("Content was " . $res->content() );
            }
        }
        if ( ! $result ) {
            Error("Error check failed: '".$res->status_line());
        }
    }
    return($result);
}

sub sendPtzCommand
{
    my $self = shift;
    my $action = shift;
    my $command_code = shift;
    my $arg1 = shift;
    my $arg2 = shift;
    my $arg3 = shift;

    my $channel = $self->{dahua_channel_number};

    my $url_path = "/cgi-bin/ptz.cgi?";
    $url_path .= "action=" . $action . "&";
    $url_path .= "channel=" . $channel . "&";
    $url_path .= "code=" . $command_code . "&";
    $url_path .= "arg1=" . $arg1 . "&";
    $url_path .= "arg2=" . $arg2 . "&";
    $url_path .= "arg3=" . $arg3;
    $self->sendGetRequest($url_path);
}
sub sendMomentaryPtzCommand
{
    my $self = shift;
    my $command_code = shift;
    my $arg1 = shift;
    my $arg2 = shift;
    my $arg3 = shift;
    my $duration_ms = shift;

    $self->sendPtzCommand("start", $command_code, $arg1, $arg2, $arg3);
    my $duration_ns = $duration_ms * 1000;
    usleep($duration_ns);
    $self->sendPtzCommand("stop", $command_code, $arg1, $arg2, $arg3);
}

sub moveRelUpLeft
{
    my $self = shift;
    Debug("Move Up Left");
    $self->sendMomentaryPtzCommand("LeftUp", 4, 4, 0, 500);
}

sub moveRelUp
{
    my $self = shift;
    Debug("Move Up");
    $self->sendMomentaryPtzCommand("Up", 0, 4, 0, 500);
}

sub moveRelUpRight
{
    my $self = shift;
    Debug("Move Up Right");
    $self->sendMomentaryPtzCommand("RightUp", 0, 4, 0, 500);
}

sub moveRelLeft
{
    my $self = shift;
    Debug("Move Left");
    $self->sendMomentaryPtzCommand("Left", 0, 4, 0, 500);
}

sub moveRelRight
{
    my $self = shift;
    Debug("Move Right");
    $self->sendMomentaryPtzCommand("Right", 0, 4, 0, 500);
}

sub moveRelDownLeft
{
    my $self = shift;
    Debug("Move Down Left");
    $self->sendMomentaryPtzCommand("LeftDown", 4, 4, 0, 500);
}

sub moveRelDown
{
    my $self = shift;
    Debug("Move Down");
    $self->sendMomentaryPtzCommand("Down", 0, 4, 0, 500);
}

sub moveRelDownRight
{
    my $self = shift;
    Debug("Move Down Right");
    $self->sendMomentaryPtzCommand("RightDown", 4, 4, 0, 500);
}

sub zoomRelTele
{
    my $self = shift;
    Debug("Zoom Relative Tele");
    $self->sendMomentaryPtzCommand("ZoomTele", 0, 0, 0, 500);
}

sub zoomRelWide
{
    my $self = shift;
    Debug("Zoom Relative Wide");
    $self->sendMomentaryPtzCommand("ZoomWide", 0, 0, 0, 500);
}


sub presetClear
{
    my $self = shift;
    my $params = shift;
    my $preset_id = $self->getParam($params, 'preset');
    $self->sendPtzCommand("start", "ClearPreset", 0, $preset_id, 0);
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

    $self->sendPtzCommand("start", "SetPreset", 0, $preset_id, 0);
    $self->sendPtzCommand("start", "SetPresetName", $preset_id, $new_label_name, 0);
}

sub presetGoto
{
    my $self = shift;
    my $params = shift;
    my $preset_id = $self->getParam($params, 'preset');

    $self->sendPtzCommand("start", "GotoPreset", 0, $preset_id, 0);
}

1;
__END__

=head1 NAME

ZoneMinder::Control::Dahua - Perl module for Dahua cameras

=head1 SYNOPSIS

use ZoneMinder::Control::Dahua;
place this in /usr/share/perl5/ZoneMinder/Control

=head1 DESCRIPTION

This module is an implementation of the Dahua IP camera HTTP control API.

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
