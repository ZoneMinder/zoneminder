<?php
// Tests which zm.conf / conf.d values loadConfig() in web/includes/config.php.in
// marks Private. A Private value is kept out of the browser (skin.js.php) and
// blanked by the API's Configs controller, so a value clients need must stay
// public. ZM_PATH_ZMS is the URL path of nph-zms: API clients read it from
// /api/configs/viewByName/ZM_PATH_ZMS.json to build stream URLs, and a blank
// answer sends them to the default path. refs #8237
//
// Run: php tests/php/test_config_file_private.php
//
// config.php needs zm.conf and a database, so the block that sets the flag is
// lifted out of the file and run on a stub $initial_config.
$src = file_get_contents(__DIR__.'/../../web/includes/config.php.in');
if ($src === false or !preg_match('/^\s*\$public_file_config = .*?^\s*}$/ms', $src, $m)) {
  echo "FAIL the \$public_file_config block was not found in config.php.in\n";
  exit(1);
}

$initial_config = array(
  'ZM_PATH_ZMS' => array('Name' => 'ZM_PATH_ZMS', 'Value' => '/cgi-bin-zm/nph-zms', 'File' => 'x'),
  'ZM_DIR_SOUNDS' => array('Name' => 'ZM_DIR_SOUNDS', 'Value' => 'sounds', 'File' => 'x'),
  'ZM_DB_PASS' => array('Name' => 'ZM_DB_PASS', 'Value' => 'secret', 'File' => 'x'),
);
eval($m[0]);

$failures = 0;
foreach (array('ZM_PATH_ZMS' => 0, 'ZM_DIR_SOUNDS' => 0, 'ZM_DB_PASS' => 1) as $name => $want) {
  $got = $initial_config[$name]['Private'];
  if ($got === $want) {
    echo "ok - $name Private=$want\n";
  } else {
    $failures++;
    echo "FAIL - $name Private=$got, want $want\n";
  }
}
exit($failures ? 1 : 0);
