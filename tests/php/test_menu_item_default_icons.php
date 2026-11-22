<?php
// Every built-in menu entry needs a default icon in MenuItem::$defaultIcons.
// renderMenuItems() overrides the icon each getXxxHTML() passes with
// effectiveIcon(), so a key missing there showed the generic 'menu' icon.
//
// Run as: php tests/php/test_menu_item_default_icons.php

namespace ZM;

$failures = 0;

$stubdir = sys_get_temp_dir().'/zm_menu_icon_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
file_put_contents($stubdir.'/database.php', "<?php\n");
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) {}
function Error($s) {}
function Debug($s) {}

require_once 'MenuItem.php';

// The rows db/zm_create.sql.in seeds Menu_Items with.
$sql = file_get_contents(__DIR__.'/../../db/zm_create.sql.in');
preg_match('/INSERT INTO `Menu_Items` \(`MenuKey`, `Enabled`, `SortOrder`\) VALUES(.*?);/s', $sql, $m);
preg_match_all("/\('(\w+)',/", $m[1], $keys);
if (!count($keys[1])) {
  $failures++;
  echo "FAIL - no Menu_Items rows found in zm_create.sql.in\n";
}
foreach ($keys[1] as $key) {
  $item = new MenuItem(array('Id' => 1, 'MenuKey' => $key, 'Enabled' => 1, 'Label' => null, 'SortOrder' => 0, 'Icon' => null, 'IconType' => 'material', 'Link' => null));
  if ($item->effectiveIcon() !== 'menu') {
    echo "ok - $key has a default icon\n";
  } else {
    $failures++;
    echo "FAIL - $key falls back to the generic menu icon\n";
  }
}

array_map('unlink', glob($stubdir.'/*'));
rmdir($stubdir);
exit($failures ? 1 : 0);
