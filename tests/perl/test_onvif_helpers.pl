#!/usr/bin/perl
#
# Test ZoneMinder::ONVIF helper functions and edge cases.
#
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../scripts/ZoneMinder/lib";

BEGIN {
  $INC{'ZoneMinder/Base.pm'} = 1;
  $INC{'ONVIF/Client.pm'} = 1;
  $INC{'WSDiscovery10/Interfaces/WSDiscovery/WSDiscoveryPort.pm'} = 1;
  $INC{'WSDiscovery10/Elements/Header.pm'} = 1;
  $INC{'WSDiscovery10/Elements/Types.pm'} = 1;
  $INC{'WSDiscovery10/Elements/Scopes.pm'} = 1;
  $INC{'WSDiscovery/TransportUDP.pm'} = 1;
  $INC{'XML/LibXML.pm'} = 1;
}

require "$FindBin::Bin/../../scripts/ZoneMinder/lib/ZoneMinder/ONVIF.pm.in";

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '') eq (defined $want ? $want : '')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

# -------------------------------------------------------------
# Test 1: deserialize_message with exception does not die on blessed
# -------------------------------------------------------------
{
  my $mock_client = bless {}, 'MockWsdlClient';
  my $mock_deser = bless {}, 'MockDeserializer';
  {
    no warnings 'redefine', 'once';
    *MockWsdlClient::get_deserializer = sub { return $mock_deser; };
    *MockDeserializer::can = sub { 0 };
    *MockDeserializer::deserialize = sub { die "Malformed XML response\n"; };
    *MockDeserializer::generate_fault = sub {
      my ($self, $args) = @_;
      return { fault => 1, message => $args->{message} };
    };
  }

  my $res = eval { ZoneMinder::ONVIF::deserialize_message($mock_client, "<bad>xml") };
  my $err = $@ ? $@ : '';
  check('deserialize_message does not crash on undeclared blessed', $err, '');
  check('deserialize_message returns generated fault', ref($res) eq 'HASH' ? $res->{fault} : 0, 1);
}

# -------------------------------------------------------------
# Test 2: interpret_messages scope regex parses multi-slash paths
# -------------------------------------------------------------
{
  my $mock_pm = bless {}, 'MockProbeMatch';
  my $mock_result = bless { pm => $mock_pm }, 'MockProbeResult';
  my $mock_svc = bless {}, 'MockSvc';
  {
    no warnings 'redefine', 'once';
    *MockSvc::get_soap_version = sub { '1.2' };
    *MockProbeResult::get_ProbeMatch = sub { $_[0]->{pm} };
    *MockProbeMatch::get_XAddrs = sub { 'http://192.168.1.50:80/onvif/device_service' };
    *MockProbeMatch::get_Scopes = sub {
      'onvif://www.onvif.org/type/Network_Video_Transmitter ' .
      'onvif://www.onvif.org/location/country/city/building ' .
      'onvif://www.onvif.org/name/Front_Door';
    };
    *ZoneMinder::ONVIF::deserialize_message = sub { return $mock_result; };
  }

  my %services;
  my @results = ZoneMinder::ONVIF::interpret_messages($mock_svc, \%services, ('dummy_xml'));
  check('interpret_messages returns 1 result', scalar @results, 1);
  my $scopes = $results[0]->{scopes} || {};
  check('scope category location extracted cleanly', $scopes->{location}, 'country/city/building');
  check('scope category type extracted cleanly', $scopes->{type}, 'Network_Video_Transmitter');
  check('scope category name extracted cleanly', $scopes->{name}, 'Front_Door');
}

# -------------------------------------------------------------
# Test 3: interpret_messages handles multiple ProbeMatches (arrayref)
# -------------------------------------------------------------
{
  my $mock_pm1 = bless { xaddr => 'http://192.168.1.51:80/onvif/device_service', name => 'Cam1' }, 'MockPMItem';
  my $mock_pm2 = bless { xaddr => 'http://192.168.1.52:80/onvif/device_service', name => 'Cam2' }, 'MockPMItem';
  my $mock_result_multi = bless { pm => [ $mock_pm1, $mock_pm2 ] }, 'MockProbeResultMulti';
  my $mock_svc = bless {}, 'MockSvc';
  {
    no warnings 'redefine', 'once';
    *MockProbeResultMulti::get_ProbeMatch = sub { $_[0]->{pm} };
    *MockPMItem::get_XAddrs = sub { $_[0]->{xaddr} };
    *MockPMItem::get_Scopes = sub { "onvif://www.onvif.org/name/$_[0]->{name}" };
    *ZoneMinder::ONVIF::deserialize_message = sub { return $mock_result_multi; };
  }

  my %services;
  my @results;
  my $err = '';
  eval {
    @results = ZoneMinder::ONVIF::interpret_messages($mock_svc, \%services, ('dummy_xml'));
  };
  $err = $@ if $@;
  check('interpret_messages does not crash on arrayref ProbeMatch', $err, '');
  check('interpret_messages processed both probe matches', scalar @results, 2);
  check('interpret_messages captured first xaddr', $results[0] ? $results[0]->{xaddr} : '', 'http://192.168.1.51:80/onvif/device_service');
  check('interpret_messages captured second xaddr', $results[1] ? $results[1]->{xaddr} : '', 'http://192.168.1.52:80/onvif/device_service');
}

# -------------------------------------------------------------
# Test 4: discover ProbeOp hash preserves SOAP 1.1 Types
# -------------------------------------------------------------
{
  my %captured_args;
  my $mock_port = bless {}, 'MockDiscoveryPort';
  {
    no warnings 'redefine', 'once';
    *WSDiscovery10::Interfaces::WSDiscovery::WSDiscoveryPort::new = sub { return $mock_port; };
    *WSDiscovery10::Elements::Header::new = sub { bless {}, 'MockHeader'; };
    *MockDiscoveryPort::set_soap_version = sub {};
    *MockDiscoveryPort::ProbeOp = sub {
      my ($self, $probe_type, $header) = @_;
      %captured_args = %$probe_type;
      return bless {}, 'MockResult';
    };
    *ZoneMinder::ONVIF::interpret_messages = sub { return (); };
  }

  ZoneMinder::ONVIF::discover('1.1');
  check('SOAP 1.1 Types is full QNameList and not overwritten',
    $captured_args{Types},
    'http://www.onvif.org/ver10/network/wsdl:NetworkVideoTransmitter http://www.onvif.org/ver10/device/wsdl:Device');

  ZoneMinder::ONVIF::discover('1.2');
  check('SOAP 1.2 Types is dn:NetworkVideoTransmitter',
    $captured_args{Types}, 'dn:NetworkVideoTransmitter');
  check('SOAP 1.2 includes xmlns:dn xmlattr',
    $captured_args{xmlattr} ? $captured_args{xmlattr}->{'xmlns:dn'} : '',
    'http://www.onvif.org/ver10/network/wsdl');
}

# -------------------------------------------------------------
# Test 5: profiles handles single profile response (non-arrayref)
# -------------------------------------------------------------
{
  my $mock_attr = bless {}, 'MockAttr';
  my $mock_res = bless {}, 'MockRes';
  my $mock_rate = bless {}, 'MockRate';
  my $mock_venc = bless {}, 'MockVenc';
  my $mock_profile = bless {}, 'MockSingleProfile';
  my $mock_profiles_resp = bless { prof => $mock_profile }, 'MockGetProfilesResp';
  my $mock_stream_uri = bless {}, 'MockStreamUri';
  my $mock_media_uri = bless {}, 'MockMediaUri';
  my $mock_media_ep = bless {}, 'MockMediaEp';
  my $mock_client = bless {}, 'MockClient';

  {
    no warnings 'redefine', 'once';
    *MockClient::get_endpoint = sub { return $mock_media_ep; };
    *MockMediaEp::GetProfiles = sub { return $mock_profiles_resp; };
    *MockGetProfilesResp::get_Profiles = sub { return $_[0]->{prof}; }; # Single object, NOT arrayref
    *MockSingleProfile::attr = sub { return $mock_attr; };
    *MockAttr::get_token = sub { 'ProfileToken1' };
    *MockSingleProfile::get_Name = sub { 'MainProfile' };
    *MockSingleProfile::get_VideoEncoderConfiguration = sub { return $mock_venc; };
    *MockVenc::get_Resolution = sub { return $mock_res; };
    *MockRes::get_Width = sub { 1920 };
    *MockRes::get_Height = sub { 1080 };
    *MockVenc::get_RateControl = sub { return $mock_rate; };
    *MockRate::get_FrameRateLimit = sub { 30 };
    *MockVenc::get_Encoding = sub { 'H264' };
    *MockMediaEp::GetStreamUri = sub { return $mock_stream_uri; };
    *MockStreamUri::get_MediaUri = sub { return $mock_media_uri; };
    *MockMediaUri::get_Uri = sub { 'rtsp://192.168.1.50/live' };
  }

  my @profiles;
  my $err = '';
  eval {
    @profiles = ZoneMinder::ONVIF::profiles($mock_client);
  };
  $err = $@ if $@;
  check('profiles() does not crash on single profile object', $err, '');
  check('profiles() returned parsed stream profiles', scalar @profiles > 0 ? 1 : 0, 1);
  check('profiles() token is ProfileToken1', $profiles[0] ? $profiles[0]->[0] : '', 'ProfileToken1');
}

# -------------------------------------------------------------
# Test 6: move handles missing PTZ endpoint gracefully
# -------------------------------------------------------------
{
  my $mock_client_no_ptz = bless {}, 'MockClientNoPTZ';
  {
    no warnings 'redefine', 'once';
    *MockClientNoPTZ::get_endpoint = sub { return undef; };
  }

  my $err = '';
  eval {
    ZoneMinder::ONVIF::move($mock_client_no_ptz, 'up');
  };
  $err = $@ if $@;
  check('move() handles undef ptz endpoint without dying', $err, '');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
