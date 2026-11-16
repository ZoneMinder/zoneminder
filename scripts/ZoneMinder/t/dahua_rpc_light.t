use strict;
use warnings;
use Test::More tests => 5;
use JSON::MaybeXS qw(encode_json);

require_ok('ZoneMinder::Control::Dahua_RPC');

# coaxial_white_payload is a pure function: ($io) -> RPC params hashref.
my $on  = ZoneMinder::Control::Dahua_RPC::coaxial_white_payload(1);
my $off = ZoneMinder::Control::Dahua_RPC::coaxial_white_payload(2);

is_deeply(
  $on,
  { channel => 0, info => [ { Type => 1, IO => 1, TriggerMode => 1 } ] },
  'on payload targets white light (Type 1) with IO 1'
);
is($off->{info}[0]{IO}, 2, 'off payload uses IO 2');

# IO must encode as a JSON number, not a string ("On"/"Off" are rejected by the camera).
like(encode_json($on),  qr/"IO":1(?!")/, 'on IO encodes as numeric 1');
like(encode_json($off), qr/"IO":2(?!")/, 'off IO encodes as numeric 2');
