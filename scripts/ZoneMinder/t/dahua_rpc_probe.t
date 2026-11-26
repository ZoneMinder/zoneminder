use strict;
use warnings;
use Test::More tests => 9;

require_ok('ZoneMinder::Control::Dahua_RPC');

# probe() is documented as a read-only capability probe. Stub rpc_call so it
# never touches a camera, and record every method it asks for.
sub make_stub {
  return bless {
    session    => 'test',
    rpc_id     => 0,
    RPCBase    => 'http://127.0.0.1/',
    ptz_object => undef,
    ua         => undef,
  }, 'ZoneMinder::Control::Dahua_RPC';
}

my @methods;
my $stub = make_stub();

no warnings 'redefine';
local *ZoneMinder::Control::Dahua_RPC::rpc_call = sub {
  my ($self, $method, $params, %opts) = @_;
  push @methods, $method;
  return { result => 7, id => 1, params => {} } if $method eq 'ptz.factory.instance';
  if ($method eq 'system.listMethod') {
    # Dahua returns the supported method names in params.method.
    return { result => 1, id => 2, params => { method => [
      'magicBox.getDeviceType', 'ptz.getStatus', 'ptz.start',
      'configManager.getConfig',
    ] } };
  }
  return { result => 1, id => 3, params => {} };
};
use warnings 'redefine';

my $out = $stub->probe();

is(scalar(grep { $_ eq 'magicBox.reboot' } @methods), 0,
   'probe never calls magicBox.reboot');
is(scalar(grep { $_ eq 'ptz.start' } @methods), 0,
   'probe sends no ptz.start');
is(scalar(grep { $_ eq 'system.listMethod' } @methods), 1,
   'probe calls system.listMethod exactly once');
like($out->{methods}{'system.listMethod'}, qr/^available/,
   'probe reports the system.listMethod result');
is($out->{methods}{'magicBox.reboot'}, 'not in method list',
   'probe reports magicBox.reboot as absent from the method list');
is($out->{methods}{'ptz.start'}, 'present in method list',
   'probe finds methods in the list without calling them');

# Unknown list format: report listMethod only, do not guess about reboot.
my @methods2;
my $stub2 = make_stub();
$stub2->{ptz_object} = 7;
local *ZoneMinder::Control::Dahua_RPC::rpc_call = sub {
  my ($self, $method, $params, %opts) = @_;
  push @methods2, $method;
  return { result => 1, id => 1, params => 'opaque' } if $method eq 'system.listMethod';
  return { result => 1, id => 2, params => {} };
};

my $out2 = $stub2->probe();

is(scalar(grep { $_ eq 'magicBox.reboot' } @methods2), 0,
   'probe never calls magicBox.reboot with an unknown list format');
is($out2->{methods}{'magicBox.reboot'}, 'not tested',
   'unknown method list format leaves magicBox.reboot not tested');
