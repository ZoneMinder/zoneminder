<?php
// The Home View list on views/user.php offers seven views, but getHomeView() takes any view,
// optionally with parameters (timeline, watch&mid=3, ...), and the API stores whatever it is
// given. A user saved with one of those saw Default selected, and saving the form for any other
// reason reset their home view. The list now keeps the saved value.
//
// Run: php tests/php/test_user_home_view_options.php [root]

$root = $argv[1] ?? dirname(__DIR__, 2);
$src = file_get_contents($root.'/web/skins/classic/views/user.php');
if (!preg_match('/^\s*(\$homeview_options = \[.*?)^echo htmlSelect\(\'user\[HomeView\]\'/ms', $src, $m)) {
  echo "FAIL - could not find the home view list in user.php\n";
  exit(1);
}
$block = $m[1];

function translate($s) { return $s; }
function htmlSelect($name, $options, $selected, $attrs = false) {
  $GLOBALS['rendered'] = array('options' => $options, 'selected' => $selected);
}
class StubUser {
  private $home;
  function __construct($home) { $this->home = $home; }
  function HomeView() { return $this->home; }
}

function render($home) {
  global $block, $rendered;
  $User = new StubUser($home);
  $homeview_options = null;
  eval($block);
  htmlSelect('user[HomeView]', $homeview_options, $User->HomeView());
  return $rendered;
}

$failures = 0;
function check($name, $ok) {
  global $failures;
  if ($ok) {
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name\n";
  }
}

$r = render('');
check('no saved home view lists the seven views', count($r['options']) == 7 && isset($r['options']['']));
$r = render('montage');
check('a listed view is not added twice', count($r['options']) == 7 && $r['selected'] === 'montage');
$r = render('timeline');
check('a saved view outside the list is offered', isset($r['options']['timeline']));
check('and it is the selected one', $r['selected'] === 'timeline' && isset($r['options'][$r['selected']]));
$r = render('watch&mid=3');
check('a saved view with parameters is offered', isset($r['options']['watch&mid=3']) && isset($r['options'][$r['selected']]));
exit($failures ? 1 : 0);
