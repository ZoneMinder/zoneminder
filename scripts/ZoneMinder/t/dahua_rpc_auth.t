use strict;
use warnings;
use Test::More tests => 2;

require_ok('ZoneMinder::Control::Dahua_RPC');

# Expected value computed independently of the module's own algorithm so a logic
# bug (swapped args, missing uc, wrong separator) is actually caught:
#   python3 -c "import hashlib
#   h1=hashlib.md5(b'admin:Login to TESTREALM:s3cr3t').hexdigest().upper()
#   print(hashlib.md5(('admin:abc-123-def:'+h1).encode()).hexdigest().upper())"
#   => EE1510230EC8B041CED529CEF3EC29D7
my ($user, $realm, $random, $pass) = ('admin', 'Login to TESTREALM', 'abc-123-def', 's3cr3t');
my $expected = 'EE1510230EC8B041CED529CEF3EC29D7';

is(
  ZoneMinder::Control::Dahua_RPC::compute_login_hash($user, $realm, $random, $pass),
  $expected,
  'compute_login_hash matches Dahua two-step MD5 scheme'
);
