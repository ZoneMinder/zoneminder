# ==========================================================================
#
# ZoneMinder GrandSteam Control Protocol Module
# Copyright (C) 2021 ZoneMinder Inc
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
# This module contains the implementation of the Vivotek ePTZ camera control
# protocol
#
package ZoneMinder::Control::Grandstream;

use 5.006;
use strict;
use warnings;

require ZoneMinder::Base;
require ZoneMinder::Control;

our @ISA = qw(ZoneMinder::Control);

# ==========================================================================
#
# Vivotek ePTZ Control Protocol
#
# ==========================================================================

use ZoneMinder::Logger qw(:all);
use ZoneMinder::Config qw(:all);
use ZoneMinder::General qw(:all);

use Time::HiRes qw( usleep );
use URI::Encode qw(uri_encode);
use XML::LibXML;
use Digest::MD5 qw(md5 md5_hex md5_base64);


our $REALM = '';
our $PROTOCOL = 'https://';
our $USERNAME = 'admin';
our $PASSWORD = '';
our $ADDRESS = '';
our $BASE_URL = '';

my %config_types = (
    upgrade => {
      P6767 => { default_value=>1, desc=>'Firmware Upgrade Method http' },
      P192 => { desc=>'Firmware Server Path' },
    },
    date => {
      P64 => { desc=>'Timezone' },
      P5006 => { default_value=>1, desc=>'Enable NTP' },
      P30 =>  { desc=>'NTP Server', },
    },
    access => {
      P12053 => {default_value=>1, desc=>'Enable UPnP Search' },
    },
    cmos => {
      #P12314=> { value=>0, desc=>'Power Frequency' },
    },
    video => {
      #P12306  => { value=>'26', desc=>'primary codec' },# 26: h264, 96: mjpeg, 98: h265
      P12313    =>  { desc=>'primary profile' },# 0: baseline, 1: main, 2: high
      P12307    =>  { desc=>'primary resolution' }, # 1025: 1920x1080 1023: 1280x960, 1022: 1280x720
      P12904    =>  { desc=>'primary fps', }, # fps 5,10,15,20,25,30
      P12311    =>  { desc=>'Image quality', }, # 0 very high, 4 very low
      P12312    =>  { desc=>'Iframe interval', }, # i-frame interval 5-100
    },
    osd => {
      P10044    => { default_value=> 1, desc=>'Display Time' },
      #P10045    => { value=> 1, desc=>'Display Text' },
      P10001    => { default_value=> 1, desc=>'OSD Date Format' },
      #P10040    => { value=>'', desc=> 'OSD Text' },
    },
    audio       => {
      P14000    => { default_value=>1, desc=>'Audio codec' }, # 1,2
      P14003    => { default_value=>0, desc=>'Audio out volume' }, # 0-6
      P14002    => { default_value=>0, desc=>'Audio in volume' }, # 0-6
    },
    debug       => {
      P8042     => { default_value=>0, desc=>'Debug log protocol' }, # 0: UDP 1: SSL/TLS
      P207      => { desc=>'Debug Log Server' },
      P208      => { desc=>'Debug Log Level' },
    },
  );

sub open {
  my $self = shift;
  $self->loadMonitor();

  if ($self->{Monitor}{ControlAddress}
      and
    $self->{Monitor}{ControlAddress} ne 'user:pass@ip'
      and
    $self->{Monitor}{ControlAddress} ne 'user:port@ip'
  ) {
    Debug("Getting connection details from Path " . $self->{Monitor}->{ControlAddress});
    if (($self->{Monitor}->{ControlAddress} =~ /^(?<PROTOCOL>https?:\/\/)?(?<USERNAME>[^:@]+)?:?(?<PASSWORD>[^\/@]+)?@?(?<ADDRESS>.*)$/)) {
      $PROTOCOL = $+{PROTOCOL} if $+{PROTOCOL};
      $USERNAME = $+{USERNAME} if $+{USERNAME};
      $PASSWORD = $+{PASSWORD} if $+{PASSWORD};
      $ADDRESS = $+{ADDRESS} if $+{ADDRESS};
    }
  } elsif ($self->{Monitor}->{Path}) {
    Debug("Getting connection details from Path " . $self->{Monitor}->{Path});
    if (($self->{Monitor}->{Path} =~ /^(?<PROTOCOL>(https?|rtsp):\/\/)?(?<USERNAME>[^:@]+)?:?(?<PASSWORD>[^\/@]+)?@?(?<ADDRESS>[^:\/]+)/)) {
      $USERNAME = $+{USERNAME} if $+{USERNAME};
      $PASSWORD = $+{PASSWORD} if $+{PASSWORD};
      $ADDRESS = $+{ADDRESS} if $+{ADDRESS};
    }
    Debug("username:$USERNAME password:$PASSWORD address:$ADDRESS");
  } else {
    Error('Failed to parse auth from address ' . $self->{Monitor}->{ControlAddress});
    $ADDRESS = $self->{Monitor}->{ControlAddress};
  }
  $BASE_URL = $PROTOCOL.$ADDRESS;

  use LWP::UserAgent;
  $self->{ua} = LWP::UserAgent->new;
  $self->{ua}->agent('ZoneMinder Control Agent/'.ZoneMinder::Base::ZM_VERSION);
  $self->{ua}->ssl_opts(verify_hostname => 0, SSL_verify_mode => 0x00);
  $self->{ua}->cookie_jar( {} );


  my $rescode = '';
  my $url = $BASE_URL.'/goform/login?cmd=login&type=0&user='.$USERNAME;
  my $response = $self->get($url);
  if ($response->is_success()) {
	  my $dom = XML::LibXML->load_xml(string => $response->content);
	  my $challengeString = $dom->getElementsByTagName('ChallengeCode')->string_value();
	  Debug('challengstring: '.$challengeString);
	  my $authcode = md5_hex($challengeString.':GSC36XXlZpRsFzCbM:'.$PASSWORD);
	  $url .= '&authcode='.$authcode;
	  $response = $self->get($url);
	  $dom = XML::LibXML->load_xml(string => $response->content);
	  $rescode = $dom->getElementsByTagName('ResCode');
  } else {
	  Warning("Falling back to old style");
	  $PROTOCOL = 'http://';
	  $BASE_URL = $PROTOCOL.$USERNAME.':'.$PASSWORD.'@'.$ADDRESS;
  }

  $self->{state} = 'open';
}

sub get {
  my $self = shift;
  my $url = shift;
  Debug("Getting $url");
  my $response = $self->{ua}->get($url);
  Debug('Response: '. $response->status_line . ' ' . $response->content);
  return $response;
}

sub close {
  my $self = shift;
  $self->{state} = 'closed';
}

sub sendCmd {
  my ($self, $cmd, $speedcmd) = @_;

  $self->printMsg( $speedcmd, 'Tx' );
  $self->printMsg( $cmd, 'Tx' );

  my $req = HTTP::Request->new( GET => $BASE_URL."/cgi-bin/camctrl/eCamCtrl.cgi?stream=0&$speedcmd&$cmd");
  my $res = $self->{ua}->request($req);

  if (!$res->is_success) {
    Error('Request failed: '.$res->status_line().' (URI: '.$req->as_string().')');
  }
  return $res->is_success;
}

sub get_config {
  my $self = shift;

  my %config;
  foreach my $category ( @_ ? @_ : keys %config_types ) {
    my $response = $self->get($BASE_URL.'/goform/config?cmd=get&type='.$category);
    my $dom = XML::LibXML->load_xml(string => $response->content);
    if (!$dom) {
      Error("No document from :".$response->content());
      return;
    }
    Debug($dom->toString(1));
    $config{$category} = {};
    my $Configuration = $dom->getElementsByTagName('Configuration');
    my $xml = $Configuration->get_node(0);
    if (!$xml) {
      Warning("UNable to get Configuration node from ".$response->content());
      return \%config;
    }
    foreach my $node ($xml->childNodes()) {
      $config{$category}{$node->nodeName} = {
	      value=>$node->textContent
      };
    }
  } # end foreach category
  return \%config;
} # end sub get_config

sub set_config {
  my $self = shift;
  my $updates = shift;

  my $url = join('&', $BASE_URL.'/goform/config?cmd=set',
    map { $_.'='.uri_encode(uri_encode($$updates{$_}{value}, { encode_reserved=>1} )) } keys %$updates );
  my $response = $self->get($url);
  return 0 if !$response->is_success();
  return 0 if ($response->content !~ /Successful/i);
  return 1;
}

sub reboot {
  my $self = shift;
  $self->get($BASE_URL.'/goform/config?cmd=reboot');
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

ZoneMinder::Control::Grandstream - ZoneMinder Perl extension for Grandstream
camera control protocol

=head1 SYNOPSIS

  use ZoneMinder::Control::Grandstream;

=head1 DESCRIPTION

This module implements the protocol used in various Grandstream IP cameras.

=head2 EXPORT

None.

=head1 SEE ALSO

I would say, see ZoneMinder::Control documentation. But it is a stub.

=head1 AUTHOR

Isaac Connor E<lt>isaac@zoneminder.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2021 by ZoneMinder Inc

This library is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.3 or,
at your option, any later version of Perl 5 you may have available.

=cut
