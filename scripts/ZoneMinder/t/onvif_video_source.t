use strict;
use warnings;
use Test::More tests => 15;

require_ok('ZoneMinder::Control::ONVIF');

my $P = 'ZoneMinder::Control::ONVIF';

# The imaging calls (GetImagingSettings/SetImagingSettings) address a VideoSource
# token, which is a different namespace from the media ProfileToken carried in
# ControlDevice. ONVIF.pm used to hardcode '000'; cameras that number their
# sources differently reject that with "The requested VideoSource does not
# exist." (e.g. AMLINK AL5M-T5171EW, which uses '00000'). The token is therefore
# discovered from GetVideoSources at open() -- parsed by the pure function below
# so it is testable without a camera.

# --- real AMLINK AL5M-T5171EW GetVideoSources response -------------------------

my $amlink = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
  .'<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope"'
  .' xmlns:tt="http://www.onvif.org/ver10/schema"'
  .' xmlns:trt="http://www.onvif.org/ver10/media/wsdl"><s:Header/><s:Body>'
  .'<trt:GetVideoSourcesResponse><trt:VideoSources token="00000">'
  .'<tt:Framerate>25</tt:Framerate>'
  .'<tt:Resolution><tt:Width>2304</tt:Width><tt:Height>1296</tt:Height></tt:Resolution>'
  .'</trt:VideoSources></trt:GetVideoSourcesResponse></s:Body></s:Envelope>';

is($P->can('video_source_token_from_xml')->($amlink), '00000',
  'reads the VideoSource token from an AMLINK GetVideoSources response');

# --- the conventional '000' most cameras use -----------------------------------

my $plain = '<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">'
  .'<s:Body><trt:GetVideoSourcesResponse'
  .' xmlns:trt="http://www.onvif.org/ver10/media/wsdl">'
  .'<trt:VideoSources token="000"/>'
  .'</trt:GetVideoSourcesResponse></s:Body></s:Envelope>';

is($P->can('video_source_token_from_xml')->($plain), '000',
  'reads a conventional 000 token');

# --- first source wins on multi-sensor devices ---------------------------------

my $multi = '<trt:GetVideoSourcesResponse>'
  .'<trt:VideoSources token="VideoSource_1"><tt:Framerate>30</tt:Framerate></trt:VideoSources>'
  .'<trt:VideoSources token="VideoSource_2"><tt:Framerate>30</tt:Framerate></trt:VideoSources>'
  .'</trt:GetVideoSourcesResponse>';

is($P->can('video_source_token_from_xml')->($multi), 'VideoSource_1',
  'returns the first source on a multi-sensor device');

# --- namespace prefixes vary between vendors -----------------------------------

is($P->can('video_source_token_from_xml')->('<GetVideoSourcesResponse>'
    .'<VideoSources token="src0"/></GetVideoSourcesResponse>'), 'src0',
  'handles an unprefixed response');

is($P->can('video_source_token_from_xml')->('<tds:VideoSources token="0"/>'), '0',
  'handles an arbitrary vendor prefix');

# --- single quotes are legal XML attribute delimiters --------------------------

is($P->can('video_source_token_from_xml')->("<trt:VideoSources token='00000'/>"), '00000',
  'handles single-quoted attribute values');

# --- failure cases must be undef so the caller can fall back to '000' -----------

is($P->can('video_source_token_from_xml')->(undef), undef,
  'undef input yields undef');
is($P->can('video_source_token_from_xml')->(''), undef,
  'empty response yields undef');
is($P->can('video_source_token_from_xml')->(
    '<s:Envelope><s:Body><s:Fault><s:Reason><s:Text>Unauthorized</s:Text>'
    .'</s:Reason></s:Fault></s:Body></s:Envelope>'), undef,
  'a SOAP fault yields undef rather than a bogus token');

# --- the config get/set paths must use the same discovered token ---------------
#
# %config_types is a file-scoped lexical, so its request bodies cannot be reached
# from here. Assert on the source instead: the point of these is to catch a new
# imaging call being added with a literal token again, which is exactly how the
# get_config/set_config pair reintroduced the bug after the original fix.

my $src = do {
  my $path = $INC{'ZoneMinder/Control/ONVIF.pm'};
  open(my $fh, '<', $path) or die "cannot read $path: $!";
  local $/;
  <$fh>;
};

unlike($src, qr/<VideoSourceToken>000</,
  'no imaging request hardcodes a literal 000 video source token');

my $placeholders = () = $src =~ /__VIDEO_SOURCE_TOKEN__/g;
cmp_ok($placeholders, '>=', 3,
  'the imaging config bodies and their substitution use the placeholder');

like($src, qr/GetImagingSettings[^']*<VideoSourceToken>__VIDEO_SOURCE_TOKEN__</,
  'the ImagingSettings query carries the placeholder');
like($src, qr/GetOptions[^']*<VideoSourceToken>__VIDEO_SOURCE_TOKEN__</,
  'the ImagingOptions query carries the placeholder');

like($src, qr/s\/__VIDEO_SOURCE_TOKEN__\/\$vs_token\/g/,
  'get_config substitutes the placeholder before sending');

