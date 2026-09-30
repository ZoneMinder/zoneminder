use strict;
use warnings;
use Test::More tests => 9;

require_ok('ZoneMinder::Control::Dahua_RPC');

# ---------------------------------------------------------------------------
# set_config input validation (pure logic, no camera needed)
# ---------------------------------------------------------------------------

# set_config requires a hashref with both 'name' and 'table' keys.
# With no live $self we can test the guard by calling it on a blessed stub
# whose rpc_call we override.
my $calls = 0;
my $stub = bless {
  session    => 'test',
  rpc_id     => 0,
  RPCBase    => 'http://127.0.0.1/',
  ptz_object => undef,
  ua         => undef,
}, 'ZoneMinder::Control::Dahua_RPC';

# Monkey-patch rpc_call so we can inspect what set_config sends without a camera.
no warnings 'redefine';
local *ZoneMinder::Control::Dahua_RPC::rpc_call = sub {
  my ($self, $method, $params, %opts) = @_;
  $calls++;
  return { result => 1, id => $calls, params => {} };
};
use warnings 'redefine';

# Missing 'table' key -> should return undef without calling rpc_call.
my $r = $stub->set_config({ name => 'Lighting' });
is($r, undef, 'set_config returns undef when table is missing');
is($calls, 0, 'set_config makes no RPC call when params are invalid');

# Missing 'name' key -> same guard.
$r = $stub->set_config({ table => [] });
is($r, undef, 'set_config returns undef when name is missing');
is($calls, 0, 'set_config still makes no RPC call');

# Valid call -> rpc_call is invoked once with configManager.setConfig.
my $last_method;
local *ZoneMinder::Control::Dahua_RPC::rpc_call = sub {
  my ($self, $method, $params, %opts) = @_;
  $calls++; $last_method = $method;
  return { result => 1, id => $calls, params => {} };
};
$r = $stub->set_config({ name => 'Lighting', table => [{Mode=>'Off'}] });
is($calls, 1, 'set_config makes exactly one RPC call for valid input');
is($last_method, 'configManager.setConfig', 'set_config calls configManager.setConfig');
ok($r, 'set_config returns truthy result on success');

# Stub rpc_call returning failure -> set_config returns undef/false.
local *ZoneMinder::Control::Dahua_RPC::rpc_call = sub {
  return { result => 0, error => { message => 'Permission denied' } };
};
$r = $stub->set_config({ name => 'Lighting', table => [] });
ok(!$r, 'set_config returns falsy when camera returns result=0');
