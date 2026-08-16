use strict;
use warnings;
use Test::More tests => 16;

require_ok('ZoneMinder::Control::HikVision');

my $P = 'ZoneMinder::Control::HikVision';

# --- mode selection (pure, capability-driven) ---------------------------------

# ColorVu model: white light available -> "on" uses it, "off" restores smart auto.
is($P->can('light_on_mode')->(qw(eventIntelligence colorVuWhiteLight irLight close)),
  'colorVuWhiteLight', 'on prefers colorVuWhiteLight when present');
is($P->can('light_on_mode')->(qw(irLight close)),
  'irLight', 'on falls back to irLight on IR-only models');
is($P->can('light_on_mode')->(qw(close)),
  undef, 'on is undef when the model exposes no illuminator');

is($P->can('light_off_mode')->(qw(eventIntelligence colorVuWhiteLight irLight close)),
  'eventIntelligence', 'off restores eventIntelligence so night IR keeps working');
is($P->can('light_off_mode')->(qw(irLight close)),
  'irLight', 'off falls back to irLight when there is no smart mode');
is($P->can('light_off_mode')->(qw(colorVuWhiteLight close)),
  'close', 'off falls back to close as a last resort');

# --- parsing real LTS/std-cgi camera XML --------------------------------------

my $doc = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
  .'<SupplementLight version="2.0" xmlns="http://www.std-cgi.com/ver20/XMLSchema">'
  .'<supplementLightMode>eventIntelligence</supplementLightMode>'
  .'<mixedLightBrightnessRegulatMode>auto</mixedLightBrightnessRegulatMode>'
  .'<whiteLightBrightness>100</whiteLightBrightness>'
  .'</SupplementLight>';

is($P->can('light_mode_from_xml')->($doc), 'eventIntelligence',
  'reads the active supplementLightMode from a camera document');

my $caps = '<SupplementLight><supplementLightMode '
  .'opt="eventIntelligence,colorVuWhiteLight,irLight,close">eventIntelligence'
  .'</supplementLightMode></SupplementLight>';
is_deeply([$P->can('light_modes_from_caps')->($caps)],
  [qw(eventIntelligence colorVuWhiteLight irLight close)],
  'reads the advertised mode list from a capabilities document');

# --- the GET-modify-PUT rewrite preserves everything but the mode -------------

my $white = $P->can('light_apply_mode')->($doc, 'colorVuWhiteLight');
like($white, qr{<supplementLightMode>colorVuWhiteLight</supplementLightMode>},
  'mode is rewritten to the requested value');
like($white, qr{xmlns="http://www\.std-cgi\.com/ver20/XMLSchema"},
  'namespace is preserved through the rewrite');
like($white, qr{<whiteLightBrightness>100</whiteLightBrightness>},
  'sibling fields the firmware requires are preserved');

# --- toggle-button status mapping ---------------------------------------------

is($P->can('light_status_from')->('colorVuWhiteLight',
    qw(eventIntelligence colorVuWhiteLight irLight close)),
  'On', 'white light active reports On');
is($P->can('light_status_from')->('eventIntelligence',
    qw(eventIntelligence colorVuWhiteLight irLight close)),
  'Off', 'smart default reports Off');
is($P->can('light_status_from')->('irLight', qw(irLight close)),
  'On', 'IR active on an IR-only model reports On');
is($P->can('light_status_from')->(undef, qw(irLight close)),
  undef, 'unknown current mode reports undef');
