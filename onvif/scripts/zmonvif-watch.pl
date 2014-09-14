#!/usr/bin/perl -w
#
# ==========================================================================
#
# ZoneMinder ONVIF Event Watch Script
# Copyright (C) Jan M. Hochstein
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
#
# This module contains the implementation of the ONVIF event watcher
#

use ZoneMinder;
use DBI;

use Getopt::Long qw(:config no_auto_abbrev no_ignore_case bundling);

require ONVIF::Client;

require WSNotification::Interfaces::WSBaseNotificationSender::NotificationProducerPort;
require WSNotification::Interfaces::WSBaseNotificationSender::SubscriptionManagerPort;
require WSNotification::Types::TopicExpressionType;

use SOAP::Lite; # +trace;
use SOAP::Transport::HTTP;

use Time::HiRes qw( usleep );
use Data::Dump qw(dump);

# ========================================================================
# Constants

# in seconds
use constant SUBSCRIPTION_RENEW_INTERVAL => 60; #3600;
use constant SUBSCRIPTION_RENEW_EARLY => 5;     #300;
use constant MONITOR_RELOAD_INTERVAL => 300;

# ========================================================================
# Globals

my $verbose = 0;
my $client;
my $daemon_pid;
my $dbh;

my %monitors;
my $monitor_reload_time = 0;

my @EXTRA_SOCK_OPTS = (
    'ReuseAddr' => '1',
    'ReusePort' => '1'
);

# =========================================================================
# signal handling

sub handler { # 1st argument is signal name
  my($sig) = @_;
  print "Caught a SIG$sig -- shutting down\n";
  if(defined $daemon_pid){
    kill($daemon_pid);
  }
  exit(0);
}

$SIG{'INT'} = \&handler;
$SIG{'HUP'} = \&handler;
$SIG{'QUIT'} = \&handler;
$SIG{'TERM'} = \&handler;
$SIG{__DIE__} = \&handler;

# =========================================================================
# internal methods

sub xs_duration
{
  use integer;
  my ($seconds) = @_;
  my $s = $seconds % 60;
  $seconds /= 60;
  my $m = $seconds % 60;
  $seconds /= 60;
  my $h = $seconds % 24;
  $seconds /= 24;
  my $d = $seconds;
  my $str;
  if($d > 0) {
    $str = "P". $d;
  }
  else {
    $str = "P";
  }
  $str = $str . "T";
  if($h > 0) {
    $str = $str . $h . "H";
  }
  if($m > 0) {
    $str = $str . $m . "M";
  }
  if($s > 0) {
    $str = $str . $s . "S";
  }
  return $str;
}

# =========================================================================
### ZoneMinder integration

sub initZm()
{
  $dbh = zmDbConnect();
}

sub loadMonitors
{
	Debug( "Loading monitors\n" );
	$monitor_reload_time = time();

	my %new_monitors = ();

	my $sql = "select * from Monitors where find_in_set( Function, 'Modect,Mocord,Nodect' ) and ConfigType='ONVIF'";
	my $sth = $dbh->prepare_cached( $sql ) or Fatal( "Can't prepare '$sql': ".$dbh->errstr() );
	my $res = $sth->execute() or Fatal( "Can't execute: ".$sth->errstr() );
	while( my $monitor = $sth->fetchrow_hashref() )
	{
		next if ( !zmMemVerify( $monitor ) ); # Check shared memory ok

		if ( defined($monitors{$monitor->{Id}}->{LastState}) )
		{
			$monitor->{LastState} = $monitors{$monitor->{Id}}->{LastState};
		}
		else
		{
			$monitor->{LastState} = zmGetMonitorState( $monitor );
		}
		if ( defined($monitors{$monitor->{Id}}->{LastEvent}) )
		{
			$monitor->{LastEvent} = $monitors{$monitor->{Id}}->{LastEvent};
		}
		else
		{
			$monitor->{LastEvent} = zmGetLastEvent( $monitor );
		}
		$new_monitors{$monitor->{Id}} = $monitor;
	}
	%monitors = %new_monitors;
}


# =========================================================================
### (experimental) send email

sub send_picture_email
{
  
#  'ffmpeg -i "rtsp://admin:admin123@192.168.0.70:554/Streaming/Channels/1?transportmode=mcast&profile=Profile_1" -y -frames 1 -vf scale=1024:-1 /tmp/pic2.jpg'
}

# =========================================================================
### Consumer for Notify messages

$SOAP::Constants::DO_NOT_CHECK_MUSTUNDERSTAND = 1;

{
  package _Consumer;
  use base qw(Class::Std::Fast SOAP::Server::Parameters);

  #
  # called on http://docs.oasis-open.org/wsn/bw-2/NotificationConsumer/Notify
  #
  sub Notify
  {
    my ($self, $unknown, $req) = @_;

    print "### Notify " . "\n";
    my $action = $req->valueof("/Envelope/Header/Action");
    print "  Action = ". $action ."\n";
    my $msg = $req->match("/Envelope/Body/Notify/NotificationMessage");
    my $topic = $msg->valueof("Topic");
    my $msg2  = $msg->match("Message/Message");
    Data::Dump::dump($msg2->dataof("")->attr);
    my $time  = $msg2->dataof("")->attr->{"UtcTime"};
    my (%source, %data);
    foreach my $item ($msg2->dataof('Source/SimpleItem')) {
      $source{$item->attr->{Name}} = $item->attr->{Value};
  #    print $item->attr->{Name} ."=>". $item->attr->{Value} ."\n";
    }
    foreach my $item ($msg2->dataof('Data/SimpleItem')) {
      $data{$item->attr->{Name}} = $item->attr->{Value};
    }
    print "$topic, $time, $source{Rule}\n";
    return ( );  # @results
  }
}

# =========================================================================

sub daemon
{
  my ($localip, $localport) = @_;

=comment
    ### deserializer
    my $event_svc = $client->get_endpoint('events');
    my $deserializer = $event_svc->get_deserializer();

    if(! $deserializer) {
      $deserializer = SOAP::WSDL::Factory::Deserializer->get_deserializer({
        soap_version => $event_svc->get_soap_version(),
        %{ $event_svc->get_deserializer_args() },
      });
    }
    # set class resolver if serializer supports it
    $deserializer->set_class_resolver( $event_svc->get_class_resolver() )
        if ( $deserializer->can('set_class_resolver') );
=cut
    ### daemon
    my $daemon = SOAP::Transport::HTTP::Daemon->new(
        'LocalAddr' => $localip, 
        'LocalPort' => $localport,
#        'deserializer' => $deserializer,
        @EXTRA_SOCK_OPTS
    );

    ## handling

    # we only handle one method
    $daemon->on_dispatch( sub {
      return ( "http://docs.oasis-open.org/wsn/bw-2/NotificationConsumer", "Notify" );
    });
  
 	$daemon_pid = fork();
  die "fork() failed: $!" unless defined $daemon_pid;
  if ($daemon_pid) {
    my $consumer = _Consumer->new();
    $daemon->dispatch_with({
#       "http://docs.oasis-open.org/wsn/bw-2" => $consumer,
       "http://docs.oasis-open.org/wsn/bw-2/NotificationConsumer"  => $consumer,
    });
    $daemon->handle();
  }
  else {
    return $daemon;
  }
}

require WSNotification::Types::EndpointReferenceType;
require WSNotification::Types::FilterType;
require WSNotification::Elements::TopicExpression;
require WSNotification::Elements::MessageContent;
require WSNotification::Types::AbsoluteOrRelativeTimeType;
require WSNotification::Elements::Metadata;
require WSNotification::Types::ReferenceParametersType;
require WSNotification::Types::AttributedURIType;
require WSNotification::Elements::Subscribe;


sub subscribe
{
  my ($localaddr, $topic_str, $duration) = @_;
  
#  my $topic = WSNotification::Types::TopicExpressionType->new( 
#    xmlattr => { 
#      Dialect => "http://www.onvif.org/ver10/tev/topicExpression/ConcreteSet",
#    },
#    value => $topic_str
#  );

#  $client->get_endpoint('events')->no_dispatch(1);

  my $result = $client->get_endpoint('events')->Subscribe( {
    ConsumerReference =>  { # WSNotification::Types::EndpointReferenceType
      Address =>  { value => 'http://' . $localaddr . '/' },
#      ReferenceParameters =>  { # WSNotification::Types::ReferenceParametersType
#      },
#      Metadata =>  { # WSNotification::Types::MetadataType
#      },
    },
#    Filter =>  { # WSNotification::Types::FilterType
#       TopicExpression => { # WSNotification::Types::TopicExpressionType
#         xmlattr => { 
#           Dialect => "http://www.onvif.org/ver10/tev/topicExpression/ConcreteSet",
#        },
#        value => "tns1:RuleEngine//.",
#      },
#      MessageContent =>  { # WSNotification::Types::QueryExpressionType
#      },      
#    },
    InitialTerminationTime => xs_duration($duration), # AbsoluteOrRelativeTimeType
#    SubscriptionPolicy =>  {
#    },
  },,
 );

  die $result if not $result;
# print $result . "\n";

### build Subscription Manager
  my $submgr_addr = $result->get_SubscriptionReference()->get_Address()->get_value();
  print "Subscription Manager at $submgr_addr\n";
    
  my $serializer = $client->service('device', 'ep')->get_serializer();
  
  my $submgr_svc = WSNotification::Interfaces::WSBaseNotificationSender::SubscriptionManagerPort->new({
    serializer => $serializer,
    proxy => $submgr_addr,
  });

  return $submgr_svc;
}

sub unsubscribe
{
  my ($submgr_svc) = @_;
  
  $submgr_svc->Unsubscribe( { },, );
}

sub renew
{
  my ($submgr_svc, $duration) = @_;
  
  my $result = $submgr_svc->Renew( { 
      TerminationTime => xs_duration($duration), # AbsoluteOrRelativeTimeType
    },, 
  );
  die $result if not $result;
}


sub events
{
  my ($localip, $localport) = @_;

  Info( "Trigger daemon starting\n" );

  if(!defined $localip) {
    $localip = '192.168.0.2';
    $localport = '0';
  }


  
    my $event_svc = $client->get_endpoint('events');

    # re-use local address/port
#   @LWP::Protocol::http::EXTRA_SOCK_OPTS =
    *LWP::Protocol::http::_extra_sock_opts = sub 
    {
#      print "### extra_sock_opts ########################################\n";
      @EXTRA_SOCK_OPTS;
    };

#*LWP::Protocol::http::_check_sock = sub 
#{
#    my($self, $req, $sock) = @_;
#    print "### check_sock ########################################\n";
#    dump($sock);
#};

    my $daemon = daemon($localip, $localport);
    my $port = $daemon->url;
    $port =~ s|^.*:||;
    $port =~ s|/.*$||;
    $localaddr = $localip . ':' . $port;
    
    
#    ReuseAddr, ReusePort
    
#    print "Contact to SOAP server at ", $daemon->url, "\n";
    print "Server using local address " . $localaddr . "\n";

    # This value is passed as the LocalAddr argument to IO::Socket::INET.
    my $transport = SOAP::Transport::HTTP::Client->new( 
      'local_address' => $localaddr );
    
#    dump( $transport );
    
    $event_svc->set_transport($transport);
    print "Sending from local address " . 
      $event_svc->get_transport()->local_address . "\n";

#print xs_duration(89);
#exit(1);

    my $submgr_svc = subscribe($localaddr, 'tns1:RuleEngine//.', SUBSCRIPTION_RENEW_INTERVAL);
    
    if(!$submgr_svc) {
      exit;
    }

    while(1) {
      print "Sleeping for " . (SUBSCRIPTION_RENEW_INTERVAL - SUBSCRIPTION_RENEW_EARLY) . " seconds\n";
      sleep(SUBSCRIPTION_RENEW_INTERVAL - SUBSCRIPTION_RENEW_EARLY);
      print "Renewal\n";
      renew($submgr_svc, SUBSCRIPTION_RENEW_INTERVAL + SUBSCRIPTION_RENEW_EARLY);
    };
    
    unsubscribe($submgr_svc);
}

# =========================================================================

sub metadata
{

  my $result = $client->get_endpoint('device')->GetServices( {
    IncludeCapability =>  'true', # boolean
    },,
  );

  die $result if not $result;
  print $result . "\n";

  $result = $client->get_endpoint('media')->GetMetadataConfigurations( { } ,, );
  die $result if not $result;
  print $result . "\n";

  $result = $client->get_endpoint('media')->GetVideoAnalyticsConfigurations( { } ,, );
#  die $result if not $result;
  print $result . "\n";

  $result = $client->get_endpoint('analytics')->GetServiceCapabilities( { } ,, );
  die $result if not $result;
  print $result . "\n";

#  $result = $client->get_endpoint('deviceio')->GetServiceCapabilities( { } ,, );
#  die $result if not $result;
#  print $result . "\n";

  
#  $result = $client->get_endpoint('analytics')->GetSupportedAnalyticsModules( { 
#    ConfigurationToken => 'VideoAnalyticsToken' 
#  } ,, );
#  die $result if not $result;
#  print $result . "\n";
#  
#  $result = $client->get_endpoint('rules')->GetSupportedRules( { 
#    ConfigurationToken => 'VideoAnalyticsToken' 
#  } ,, );
#  die $result if not $result;
#  print $result . "\n";
}

# ========================================================================
# options processing

sub HELP_MESSAGE
{
  my ($fh, $pkg, $ver, $opts) = @_;
  print $fh "Usage: " . __FILE__ . " [-v] <command> <device URI> <soap version> <user> <password>\n";
  print $fh  <<EOF
  Commands are:
    metadata  - print some of the device's configuration settings
    events    - listen for events
  Common parameters:
    -v        - increase verbosity
  Device access parameters (for all commands):
    device URL    - the ONVIF Device service URL
    soap version  - SOAP version (1.1 or 1.2)
    user          - username of a user with access to the device
    password      - password for the user
EOF
}

# ========================================================================
# MAIN
  
my $action;
my $url_svc_device;
my $soap_version;
my ($username, $password);
my ($localaddr, $localip, $localport);

logInit();
logSetSignal();

if(!GetOptions(
      'command|c=s'       => \$action,
      'device|d=s'        => \$url_svc_device,
      'soap-version|s=s'  => \$soap_version,
      'username|u=s'      => \$username,
      'password|p=s'      => \$password,
      'local-addr|l=s'    => \$localaddr,
      'verbose|v=s'       => \$verbose,
      )) {
  HELP_MESSAGE(\*STDOUT);
  exit(1);
}

if(!defined $action) {
  HELP_MESSAGE(\*STDOUT);
  exit(1);
}

if(defined $localaddr) {
  if($localaddr =~ /(.*):(.*)/)
  {
    ($localip, $localport) = ($1, $2);
  }
  else {
    $localip = $localaddr;
    $localport = '0';
  }
}
{
  initZm();

# all actions need URI and credentials
  $client = ONVIF::Client->new( { 
      'url_svc_device' => $url_svc_device, 
      'soap_version' => $soap_version } );

  $client->set_credentials($username, $password, 1);
  
  $client->create_services();
  
  if($action eq "metadata") {
    metadata();
  }
  elsif($action eq "events") {
    
    events($localip, $localport);
  }
  else {
    print("Error: Unknown command\"$action\"");
    exit(1);
  }
}
