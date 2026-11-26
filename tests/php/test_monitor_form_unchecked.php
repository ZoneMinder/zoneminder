<?php
// The monitor form is posted to itself when Source Type or Method changes,
// to draw the fields that type needs. A checkbox left unticked is not part of
// a posted form, so the view treated it as "not posted" and kept the stored
// value: unticking Exif or Restream and then changing the Source Type brought
// the box back ticked, and the next save stored 1.
//
// monitorFillUncheckedFields() gives the view the same "absent means off"
// rule the save action applies. Extracts the functions by name so the test
// needs no database:
// php tests/php/test_monitor_form_unchecked.php

define('ZM_OPT_CONTROL', true);
$hasControl = true;
function canView($p) { global $hasControl; return $hasControl; }

$src = file_get_contents(__DIR__.'/../../web/includes/functions.php');
foreach (array('monitorFormUncheckedDefaults', 'monitorFillUncheckedFields') as $fn) {
  if (!preg_match('/^function '.$fn.'\(.*?^}\n/ms', $src, $m)) {
    echo "FAIL - $fn is not defined in functions.php\n";
    exit(1);
  }
  eval($m[0]);
}

$failures = 0;
$passes = 0;
function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$posted = array('Name' => 'Front', 'Type' => 'Remote', 'Restream' => '1');
$filled = monitorFillUncheckedFields($posted);
check('unticked Exif becomes 0', $filled['Exif'], 0);
check('ticked Restream is kept', $filled['Restream'], '1');
check('unticked Go2RTCEnabled becomes 0', $filled['Go2RTCEnabled'], 0);
check('no trigger ticked becomes an empty list', $filled['Triggers'], array());
check('no group selected becomes an empty list', $filled['GroupIds'], array());
check('no linked monitors becomes an empty list', $filled['LinkedMonitors'], array());
check('a plain field is left alone', $filled['Name'], 'Front');
check('Method is not invented', isset($filled['Method']), false);
check('V4LMultiBuffer is not invented', isset($filled['V4LMultiBuffer']), false);
check('a field that was posted as empty string is kept', monitorFillUncheckedFields(array('Exif' => ''))['Exif'], '');

$hasControl = false;
$noControl = monitorFillUncheckedFields(array('Name' => 'Front'));
check('Controllable is not touched without Control permission', isset($noControl['Controllable']), false);
check('TrackMotion is not touched without Control permission', isset($noControl['TrackMotion']), false);
check('other checkboxes still default without Control permission', $noControl['Exif'], 0);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
