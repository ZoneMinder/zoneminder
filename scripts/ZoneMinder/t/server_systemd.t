use strict;
use warnings;
use Test::More tests => 15;

require_ok('ZoneMinder::Server');

my $in_service = ZoneMinder::Server->can('cgroup_in_service');
ok($in_service, 'cgroup_in_service is available');
ok(ZoneMinder::Server->can('systemdRunning'), 'systemdRunning is available');
ok(ZoneMinder::Server->can('calledBysystem'), 'calledBysystem is available');

# --- cgroup v2, the single "0::/path" line ----------------------------------

ok(!$in_service->('0::/system.slice/apache2.service', 'zoneminder'),
  'started from the web ui is not the zoneminder service, so we delegate');
ok($in_service->('0::/system.slice/zoneminder.service', 'zoneminder'),
  'systemd started us, so we do the work rather than asking for ourselves again');
ok(!$in_service->('0::/user.slice/user-1000.slice/session-3.scope', 'zoneminder'),
  'an admin shell is not the service');

# --- cgroup v1, several "id:controller:/path" lines --------------------------

ok($in_service->("12:pids:/system.slice/zoneminder.service\n"
    ."11:name=systemd:/system.slice/zoneminder.service", 'zoneminder'),
  'any one of the v1 lines naming the service is enough');
ok(!$in_service->("12:pids:/system.slice/apache2.service\n"
    ."11:name=systemd:/system.slice/apache2.service", 'zoneminder'),
  'v1 lines naming another service are not ours');

# --- a name that merely starts with ours is a different unit -----------------

ok(!$in_service->('0::/system.slice/zoneminder-cgtest.service', 'zoneminder'),
  'a longer unit name is not our service');
ok(!$in_service->('0::/system.slice/zoneminder.serviceX', 'zoneminder'),
  'a longer suffix is not our service either');
ok($in_service->('0::/system.slice/zoneminder.service/foo', 'zoneminder'),
  'a delegated sub-cgroup is still inside the service');

# --- degenerate input --------------------------------------------------------

ok(!$in_service->('', 'zoneminder'), 'empty cgroup text is not the service');
ok(!$in_service->(undef, 'zoneminder'), 'undef cgroup text is not the service');
ok(!$in_service->('0::/system.slice/zoneminder.service', ''),
  'an empty unit name matches nothing');
