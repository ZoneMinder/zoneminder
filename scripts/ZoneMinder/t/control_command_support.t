use strict;
use warnings;
use Test::More tests => 13;

# zmcontrol dispatches ui and action commands straight onto the control object
# with $control->$command(...). A monitor's actions are configured separately
# from its control protocol, so nothing stops a LightOn action pointing at a
# protocol that has no lightOn - which is exactly what swapping a camera for a
# different make leaves behind. That used to kill the control server with
# "Can't locate object method"; zmcontrol now checks can() first, so this
# pins down the behaviour can() must have for that guard to work.

require_ok('ZoneMinder::Control');
require_ok('ZoneMinder::Control::ONVIF');
require_ok('ZoneMinder::Control::AMLink');

# The guard's whole basis: can() must answer honestly for inherited, local and
# absent methods alike.
ok(ZoneMinder::Control::ONVIF->can('moveConUp'), 'can() sees a method the protocol defines');
ok(ZoneMinder::Control::ONVIF->can('open'), 'and one it inherits');
ok(!ZoneMinder::Control::ONVIF->can('lightOn'),
  'ONVIF has no lightOn - the case that took the control server down');
ok(!ZoneMinder::Control::ONVIF->can('no_such_command_at_all'),
  'and an invented name is not claimed');

# AMLink does have the light commands, so the guard must not block them.
ok(ZoneMinder::Control::AMLink->can('lightOn'), 'AMLink still offers lightOn');
ok(ZoneMinder::Control::AMLink->can('lightOff'), 'and lightOff');

# can() must work on an instance, not just the class, since that is how
# zmcontrol holds the control object.
my $obj = bless {}, 'ZoneMinder::Control::ONVIF';
ok($obj->can('moveConUp'), 'can() works on a blessed instance too');
ok(!$obj->can('lightOn'), 'and still refuses the missing one');

# A command name is taken from json off a socket, so it can be anything.
# can() must not be fooled into dispatching something dangerous.
ok(!ZoneMinder::Control::ONVIF->can(''), 'an empty command name is not a method');
ok(!ZoneMinder::Control::ONVIF->can('0'), 'nor is "0"');
