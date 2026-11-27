<?php
// The Email Server box on views/filter.php was type="email", but it holds an SMTP host name such
// as smtp.example.com or localhost. A browser rejects that as "missing an @", and the Save,
// Save As and Execute buttons submit with requestSubmit(), which runs that check, so a filter
// with a mail server could not be saved or run. The box is a plain text input.
//
// Run: php tests/php/test_filter_email_server_input.php [root]

$root = $argv[1] ?? dirname(__DIR__, 2);
$src = file_get_contents($root.'/web/skins/classic/views/filter.php');

function input_tag($src, $name) {
  // One line each; the value attribute holds php, so the tag cannot be cut at the first >.
  if (!preg_match('/^[^\n]*<input\b[^\n]*name="'.preg_quote($name, '/').'"[^\n]*\/>/m', $src, $m)) return null;
  return $m[0];
}
function input_type($tag) {
  return preg_match('/\stype="([^"]*)"/', $tag, $m) ? $m[1] : 'text';
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

$server = input_tag($src, 'filter[EmailServer]');
check('the Email Server input is in filter.php', $server !== null);
check('the Email Server input is not type=email, a host name has no @', $server !== null and input_type($server) !== 'email');
check('the Email Server input keeps its placeholder', $server !== null and strpos($server, 'placeholder=') !== false);

$to = input_tag($src, 'filter[EmailTo]');
check('the Email To input is still type=email, it holds addresses', $to !== null and input_type($to) === 'email');
exit($failures ? 1 : 0);
