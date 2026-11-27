use strict;
use warnings;
use Test::More tests => 8;

use lib ($ENV{ZM_LIB} // 'scripts/ZoneMinder/lib');

BEGIN {
  $INC{'URI/Encode.pm'} = 1;
  package URI::Encode;
  sub new { bless {}, shift }
  sub encode { $_[1] }
  sub decode { $_[1] }
}

require LWP::UserAgent;
use HTTP::Response;

my @created;
{
  no warnings 'redefine';
  my $orig_new = \&LWP::UserAgent::new;
  *LWP::UserAgent::new = sub {
    my $ua = $orig_new->(@_);
    push @created, $ua;
    return $ua;
  };
}

# The modules answer in milliseconds when the camera is there, so the timeout
# only ever bounds the case where it is not. LWP defaults to 180s, which would
# stall the control daemon for every action queued behind an absent camera.
sub stub_lwp {
  no warnings 'redefine';
  no warnings 'once';
  local *LWP::UserAgent::get = sub { HTTP::Response->new(500, 'x') };
  local *LWP::UserAgent::post = sub { HTTP::Response->new(500, 'x') };
  local *LWP::UserAgent::request = sub { HTTP::Response->new(500, 'x') };
  local *LWP::UserAgent::simple_request = sub { HTTP::Response->new(500, 'x') };
  return $_[0]->();
}

# --- FoscamHD -----------------------------------------------------------------

require ZoneMinder::Control::FoscamHD;

my $foscam = bless {}, 'ZoneMinder::Control::FoscamHD';
@created = ();
eval { $foscam->_ua };
is($created[-1] ? $created[-1]->timeout : undef, 10, 'FoscamHD ua timeout');

# --- Amcrest_HTTP -------------------------------------------------------------

require ZoneMinder::Control::Amcrest_HTTP;

@created = ();
eval { stub_lwp(sub { ZoneMinder::Control::Amcrest_HTTP::probe('127.0.0.1', 'u', 'p') }) };
ok(@created > 0, 'amcrest probe built a ua');
is_deeply([map { $_->timeout } @created], [(10) x @created], 'amcrest probe ua timeouts');

# --- ONVIF --------------------------------------------------------------------

require ZoneMinder::Control::ONVIF;

@created = ();
eval { stub_lwp(sub { ZoneMinder::Control::ONVIF::probe('127.0.0.1', 'u', 'p') }) };
ok(@created > 0, 'onvif probe built a ua');
is_deeply([map { $_->timeout } @created], [(10) x @created], 'onvif probe ua timeouts');

# --- the constants ------------------------------------------------------------

for my $pkg (qw(ZoneMinder::Control::FoscamHD
                ZoneMinder::Control::Amcrest_HTTP
                ZoneMinder::Control::ONVIF)) {
  is(eval { $pkg->can('HTTP_TIMEOUT') ? $pkg->can('HTTP_TIMEOUT')->() : undef },
    10, "$pkg HTTP_TIMEOUT");
}
