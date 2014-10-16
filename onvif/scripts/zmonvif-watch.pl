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

## SETUP:
##    chmod g+rw /dev/shm/zm*
##    chgrp users /dev/shm/zm*
##    systemctl stop iptables

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
use Carp;

# ========================================================================
# Constants

# in seconds
use constant SUBSCRIPTION_RENEW_INTERVAL => 60; #3600;
use constant SUBSCRIPTION_RENEW_EARLY => 5;     #300;
use constant MONITOR_RELOAD_INTERVAL => 300;

# ========================================================================
# Globals

my $verbose = 0;
my $daemon_pid;
my $dbh;

my %monitors;
my $monitor_reload_time = 0;

# this does not work on all architectures
my @EXTRA_SOCK_OPTS = (
    'ReuseAddr' => '1',
    'ReusePort' => '1'
);


# =========================================================================
# signal handling

sub handler { # 1st argument is signal name
  my($sig) = @_;
  print "Caught a SIG$sig -- shutting down\n";
  confess();
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

#	my $sql = "select * from Monitors where find_in_set( Function, 'Modect,Mocord,Nodect' )>0 and ConfigType='ONVIF'";
	my $sql = "select * from Monitors where ConfigType='ONVIF'";
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
    
    print "URL: " .$monitor->{ConfigURL}. "\n";
    
    ## set up ONVIF client for monitor
    next if( ! $monitor->{ConfigURL} );

    my $soap_version;
    if($monitor->{ConfigOptions} =~ /SOAP1([12])/) {
      $soap_version = "1.$1";
    }
    else {
      $soap_version = "1.1";
    }
    my $client = ONVIF::Client->new( { 
      'url_svc_device' => $monitor->{ConfigURL}, 
      'soap_version' => $soap_version } );

    if($monitor->{User}) {
      $client->set_credentials($monitor->{User}, $monitor->{Pass}, 0);
    }
    
    $client->create_services();
    $monitor->{onvif_client} = $client;

		$new_monitors{$monitor->{Id}} = $monitor;
	}
	%monitors = %new_monitors;
}

sub freeMonitors
{
	foreach my $monitor ( values(%monitors) )
  {
    # Free up any used memory handle
    zmMemInvalidate( $monitor );
  }
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
  my ($client, $localaddr, $topic_str, $duration) = @_;

# for debugging:  
#  $client->get_endpoint('events')->no_dispatch(1);

  my $result = $client->get_endpoint('events')->Subscribe( {
    ConsumerReference =>  { # WSNotification::Types::EndpointReferenceType
      Address =>  { value => 'http://' . $localaddr . '/' },
#      ReferenceParameters =>  { # WSNotification::Types::ReferenceParametersType
#      },
#      Metadata =>  { # WSNotification::Types::MetadataType
#      },
    },
    Filter =>  { # WSNotification::Types::FilterType
       TopicExpression => { # WSNotification::Types::TopicExpressionType
         xmlattr => { 
           Dialect => "http://www.onvif.org/ver10/tev/topicExpression/ConcreteSet",
        },
        value => $topic_str,
      },
#      MessageContent =>  { # WSNotification::Types::QueryExpressionType
#      },      
    },
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

  Info( "ONVIF Trigger daemon starting\n" );

  if(!defined $localip) {
    $localip = '192.168.0.2';
    $localport = '0';
  }

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
    
    print "Daemon uses local address " . $localaddr . "\n";

    # This value is passed as the LocalAddr argument to IO::Socket::INET.
    my $transport = SOAP::Transport::HTTP::Client->new( 
#      'local_address' => $localaddr );     ## REUSE port
       'local_address' => $localip );    

    
    loadMonitors();
    
    foreach $monitor (values(%monitors)) {
    
      my $client = $monitor->{onvif_client};
      my $event_svc = $client->get_endpoint('events');
      $event_svc->set_transport($transport);
#      print "Sending from local address " . 
#        $event_svc->get_transport()->local_address . "\n";

      my $submgr_svc = subscribe($client, $localaddr, 'tns1:RuleEngine//.', SUBSCRIPTION_RENEW_INTERVAL);
    
      if(!$submgr_svc) {
        print "Subscription failed\n";
        next;
      }
    
      $monitor->{submgr_svc} = $submgr_svc;
    }
    
    while(1) {
      print "Sleeping for " . (SUBSCRIPTION_RENEW_INTERVAL - SUBSCRIPTION_RENEW_EARLY) . " seconds\n";
      sleep(SUBSCRIPTION_RENEW_INTERVAL - SUBSCRIPTION_RENEW_EARLY);
      print "Renewal\n";
      foreach $monitor (%monitors) {
        if(defined $monitor->{submgr_svc}) {
          renew($monitor->{submgr_svc}, SUBSCRIPTION_RENEW_INTERVAL + SUBSCRIPTION_RENEW_EARLY);
        }
      }
    };
    
    Info( "ONVIF Trigger daemon exited\n" );
    
    foreach $monitor (values(%monitors)) {
      if(defined $monitor->{submgr_svc}) {
         unsubscribe($monitor->{submgr_svc});
      }
    }
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
  print $fh "Usage: " . __FILE__ . " <parameters>\n";
  print $fh  <<EOF
  Parameters:
    -v              - increase verbosity
    -l|local-addr   - listen on address (host[:port])
EOF
}

# ========================================================================
# MAIN
  
my ($localaddr, $localip, $localport);

logInit();
logSetSignal();

if(!GetOptions(
      'local-addr|l=s'    => \$localaddr,
      'verbose|v=s'       => \$verbose,
      )) {
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

  events($localip, $localport);
}
