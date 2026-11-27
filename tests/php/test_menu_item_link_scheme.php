<?php
/**
 * A custom navbar menu item's Link was used as the href as saved, so a
 * System-edit user could save a javascript: link that runs script in the
 * session of every user who clicks it. menuItemEffectiveLink() in
 * web/skins/classic/includes/functions.php now falls back to the ?view= link
 * when the Link has a scheme other than http(s).
 */

$root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';
$file = $root.'/web/skins/classic/includes/functions.php';

$src = @file_get_contents($file);
if ($src === false) {
  fwrite(STDERR, "FAIL - could not read $file\n");
  exit(1);
}

$names = ['getViewMenuKeyMap', 'getMenuKeyHrefMap', 'menuItemEffectiveLink'];
foreach ($names as $name) {
  if (!preg_match('/^function '.$name.'\(.*?^\}/ms', $src, $m)) {
    fwrite(STDERR, "FAIL - could not extract $name from $file\n");
    exit(1);
  }
  eval($m[0]);
}

if (!class_exists('FakeItem')) {
  class FakeItem {
    public $l;
    public $k;

    function __construct($link, $key) {
      $this->l = $link;
      $this->k = $key;
    }

    function Link() {
      return $this->l;
    }

    function MenuKey() {
      return $this->k;
    }
  }
}

$failures = 0;

function check($label, $expected, $actual) {
  global $failures;
  if ($expected === $actual) {
    echo "ok - $label\n";
  } else {
    echo 'FAIL - '.$label.' (got '.var_export($actual, true).")\n";
    $failures++;
  }
}

check('relative view link stays', '?view=console', menuItemEffectiveLink(new FakeItem('?view=console', 'Console')));
check('https link stays', 'https://example.com/x', menuItemEffectiveLink(new FakeItem('https://example.com/x', 'Foo')));
check('uppercase HTTP link stays', 'HTTP://example.com', menuItemEffectiveLink(new FakeItem('HTTP://example.com', 'Foo')));
check('site path stays', '/zm/index.php?view=log', menuItemEffectiveLink(new FakeItem('/zm/index.php?view=log', 'Log')));
check('javascript scheme falls back', '?view=Foo', menuItemEffectiveLink(new FakeItem('javascript:alert(1)', 'Foo')));
check('tab-obfuscated javascript falls back', '?view=Foo', menuItemEffectiveLink(new FakeItem("java\tscript:alert(1)", 'Foo')));
check('mixed-case padded javascript falls back', '?view=Foo', menuItemEffectiveLink(new FakeItem(' JaVaScRiPt:alert(1)', 'Foo')));
check('data scheme falls back', '?view=Foo', menuItemEffectiveLink(new FakeItem('data:text/html,<script>alert(1)</script>', 'Foo')));
check('null link falls back', '?view=console', menuItemEffectiveLink(new FakeItem(null, 'Console')));

exit($failures > 0 ? 1 : 0);
