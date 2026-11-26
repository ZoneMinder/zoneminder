<?php
// The name/reference fields in these modals and views had no maxlength, so a
// browser would happily submit a name longer than the column it is stored in.
// A maxlength attribute on each <input> caps it before the request is sent.
// This reads the templates as text (they need a full web environment to run)
// and checks that the input carrying the given name/id has the right cap.
//
// Run as: php tests/php/test_name_inputs_maxlength.php [repo root]
//   argv[1] defaults to __DIR__.'/../..' so the check can be pointed at a
//   checkout, e.g. a temp root holding the pre-change copies from git show.

$root = isset($argv[1]) ? rtrim($argv[1], '/') : realpath(__DIR__.'/../..');

// file, attribute that identifies the input, expected maxlength, label
$cases = array(
  array('web/ajax/modals/group.php',            'name="newGroup[Name]"',   64, 'group name'),
  array('web/ajax/modals/server.php',           'name="newServer[Name]"',  64, 'server name'),
  array('web/ajax/modals/state.php',            'id="newState"',           64, 'state name'),
  array('web/ajax/modals/storage.php',          'name="newStorage[Name]"', 64, 'storage name'),
  array('web/ajax/modals/storage.php',          'name="newStorage[Path]"', 64, 'storage path'),
  array('web/ajax/modals/eventdetail.php',      'name="newEvent[Name]"',   64, 'event name'),
  array('web/skins/classic/views/snapshot.php', 'name="snapshot[Name]"',   64, 'snapshot name'),
  array('web/skins/classic/views/zone.php',     'name="newZone[Name]"',    64, 'zone name'),
  array('web/skins/classic/views/report.php',   'name="Report[Name]"',     30, 'report name'),
);

$passes = 0;
$failures = 0;

function check($ok, $label, $detail = '') {
  global $passes, $failures;
  if ($ok) {
    $passes++;
    echo "ok - $label\n";
  } else {
    $failures++;
    echo "FAIL - $label".($detail === '' ? '' : " ($detail)")."\n";
  }
}

foreach ($cases as $case) {
  list($file, $needle, $want, $label) = $case;
  $path = $root.'/'.$file;

  if (!is_file($path)) {
    check(false, $label, "$path not found");
    continue;
  }

  $text = file_get_contents($path);
  if ($text === false) {
    check(false, $label, "$file unreadable");
    continue;
  }

  // Attribute values are PHP echoes, and the '>' that ends a PHP block would
  // end a naive tag match early. Replace the PHP blocks with a plain token
  // first; the attributes being checked here are static HTML around them.
  $text = preg_replace('/<\?(?:php\b|=).*?\?>/s', 'PHP', $text);

  // Collect the <input ...> tags that carry this name/id.
  preg_match_all('/<input\b[^>]*>/i', $text, $matches);
  $inputs = array();
  foreach ($matches[0] as $tag) {
    if (strpos($tag, $needle) !== false) $inputs[] = $tag;
  }

  if (count($inputs) !== 1) {
    check(false, $label, 'expected 1 input with '.$needle.', found '.count($inputs)." in $file");
    continue;
  }

  if (!preg_match('/\bmaxlength\s*=\s*["\']?(\d+)/i', $inputs[0], $m)) {
    check(false, $label, "no maxlength on the input in $file");
    continue;
  }

  check((int)$m[1] === $want, $label, "maxlength is $m[1], want $want in $file");
}

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
