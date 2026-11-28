use strict;
use warnings;

use lib ($ENV{ZM_LIB} // 'scripts/ZoneMinder/lib');

use Test::More;

# The control modules sit between zmcontrol and the camera. A missing HTTP
# timeout means a wedged camera hangs the control daemon forever, so every
# module that builds its own UserAgent must bound the wait to HTTP_TIMEOUT.
# These modules each had a bare LWP::UserAgent->new (LWP's default is 180s);
# the timeouts are now explicit and this pins them.

my $ZM_LIB = $ENV{ZM_LIB} // 'scripts/ZoneMinder/lib';

# Optional CPAN modules that the control modules use but that need not be
# installed to exercise them. Stubbed under %INC only when absent, so a real
# install is always preferred.
BEGIN {
  my %stubs = (
    'URI::Encode' => <<'STUB',
package URI::Encode;
use Exporter 'import';
our @EXPORT_OK = qw(uri_encode uri_decode);
sub uri_encode {
  my $s = shift // '';
  $s =~ s/([^A-Za-z0-9_.~-])/sprintf('%%%02X', ord($1))/ge;
  return $s;
}
sub uri_decode {
  my $s = shift // '';
  $s =~ s/%([0-9A-Fa-f]{2})/chr(hex($1))/ge;
  return $s;
}
1;
STUB
    'XML::LibXML' => <<'STUB',
package XML::LibXML;
sub import { }
sub load_xml { die "XML::LibXML stub: cannot parse XML\n" }
1;
STUB
    'JSON::MaybeXS' => <<'STUB',
package JSON::MaybeXS;
use Exporter 'import';
our @EXPORT_OK = qw(encode_json decode_json);
sub encode_json { return '{}' }
sub decode_json { return {} }
1;
STUB
  );

  for my $mod (sort keys %stubs) {
    (my $file = "$mod.pm") =~ s{::}{/}g;
    next if eval { require $file; 1 };
    eval $stubs{$mod};
    die "cannot install stub for $mod: $@" if $@;
    $INC{$file} = __FILE__;
  }
}

require LWP::UserAgent;
require HTTP::Response;
require ZoneMinder::Control;
require ZoneMinder::Database;

# Record every UserAgent the modules create, and make all network calls
# return a plain 500 so nothing touches the cameras. The real new() is still
# called, so the timeout under test is whatever the module actually set.
my @created;
{
  no warnings 'redefine';
  my $real_new = \&LWP::UserAgent::new;
  *LWP::UserAgent::new = sub {
    my $ua = $real_new->(@_);
    push @created, $ua;
    return $ua;
  };
  my $dead = sub { return HTTP::Response->new(500, 'x') };
  *LWP::UserAgent::get = $dead;
  *LWP::UserAgent::post = $dead;
  *LWP::UserAgent::request = $dead;
  *LWP::UserAgent::simple_request = $dead;
}

my @modules = (
  [ 'ZoneMinder::Control::3S',                    'Control/3S.pm' ],
  [ 'ZoneMinder::Control::AxisV2',                'Control/AxisV2.pm' ],
  [ 'ZoneMinder::Control::DCS3415',               'Control/DCS3415.pm' ],
  [ 'ZoneMinder::Control::DCS5020L',              'Control/DCS5020L.pm' ],
  [ 'ZoneMinder::Control::DericamP2',             'Control/DericamP2.pm' ],
  [ 'ZoneMinder::Control::FI8620_Y2k',            'Control/FI8620_Y2k.pm' ],
  [ 'ZoneMinder::Control::FI9821W_Y2k',           'Control/FI9821W_Y2k.pm' ],
  [ 'ZoneMinder::Control::FoscamCGI',             'Control/FoscamCGI.pm' ],
  [ 'ZoneMinder::Control::HiSilicon_Hi3510_CGI',  'Control/HiSilicon_Hi3510_CGI.pm' ],
  [ 'ZoneMinder::Control::HikVision',             'Control/HikVision.pm' ],
  [ 'ZoneMinder::Control::IPCAMIOS',              'Control/IPCAMIOS.pm' ],
  [ 'ZoneMinder::Control::Keekoon',               'Control/Keekoon.pm' ],
  [ 'ZoneMinder::Control::M8640',                 'Control/M8640.pm' ],
  [ 'ZoneMinder::Control::MaginonIPC',            'Control/MaginonIPC.pm' ],
  [ 'ZoneMinder::Control::Netcat',                'Control/Netcat.pm' ],
  [ 'ZoneMinder::Control::PSIA',                  'Control/PSIA.pm' ],
  [ 'ZoneMinder::Control::Reolink',               'Control/Reolink.pm' ],
  [ 'ZoneMinder::Control::Sony',                  'Control/Sony.pm' ],
  [ 'ZoneMinder::Control::TapoC520WS_ONVIF',      'Control/TapoC520WS_ONVIF.pm' ],
  [ 'ZoneMinder::Control::Toshiba_IK_WB11A',      'Control/Toshiba_IK_WB11A.pm' ],
  [ 'ZoneMinder::Control::Serial_HEX_PelcoD',     'Control/Serial_HEX_PelcoD.pm' ],
);

for my $entry (@modules) {
  my ($pkg, $rel) = @$entry;
  (my $name = $pkg) =~ s/^ZoneMinder::Control:://;
  $name = 'Control' if $name eq 'ZoneMinder::Control';

  # a) load it; a missing optional dependency is a skip, not a failure.
  (my $reqfile = $pkg) =~ s{::}{/}g;
  my $loaded = eval { require "$reqfile.pm"; 1 };
  unless ($loaded) {
    my $err = $@ || 'unknown error';
    diag("$pkg could not be loaded: $err");
    SKIP: { skip("$pkg missing dependencies", 3); }
    next;
  }

  # b) the constant the open() code uses for its UserAgent.
  is($pkg->can('HTTP_TIMEOUT') ? $pkg->can('HTTP_TIMEOUT')->() : undef,
     10, "$pkg HTTP_TIMEOUT");

  # c) behaviour: open() (or host() for the base class) must put the
  # timeout on every UserAgent it creates.
  @created = ();
  my $obj = bless {
    Monitor => {
      Id               => 1,
      ControlAddress   => 'user:pass@127.0.0.1:80',
      ControlDevice    => '',
      AutoStopTimeout  => 0,
    },
    address => '127.0.0.1',
    host    => '127.0.0.1',
    port    => 80,
    state   => 'closed',
  }, $pkg;

  {
    no warnings 'redefine';
    local *ZoneMinder::Control::loadMonitor = sub { 1 };
    local *ZoneMinder::Control::guess_credentials = sub { 1 };
    local *ZoneMinder::Control::sendCmd = sub { 1 };
    local *ZoneMinder::Control::printMsg = sub { 1 };
    my $method = $pkg eq 'ZoneMinder::Control' ? 'host' : 'open';
    eval { $obj->$method(); 1 };
  }

  if (@created) {
    is_deeply([ map { $_->timeout } @created ], [ (10) x @created ],
              "$pkg creates UserAgent(s) with timeout 10");
  } else {
    ok(1, "$pkg creates no UserAgent without network; static check covers it");
  }

  # d) static: no LWP::UserAgent->new call in the module may omit timeout.
  my $path = "$ZM_LIB/ZoneMinder/$rel";
  my $src = do {
    open my $fh, '<', $path or die "open $path: $!";
    local $/;
    <$fh>;
  };
  my $bare = () = $src =~ /LWP::UserAgent->new\b(?!\([^)]*timeout)/g;
  is($bare, 0, "$pkg has no LWP::UserAgent->new without timeout");
}

done_testing();
