<?php

$repoRoot = $argv[1] ?? realpath(__DIR__.'/../..');

$functionsFile = $repoRoot.'/web/includes/functions.php';
$source = file_get_contents($functionsFile);
if ($source === false) {
  fwrite(STDERR, "Could not read $functionsFile\n");
  exit(1);
}

foreach (['validHtmlStr', 'html_input'] as $functionName) {
  $pattern = '/function\s+'.preg_quote($functionName, '/').'\b.*?\n}\n/s';
  if (!preg_match($pattern, $source, $matches)) {
    fwrite(STDERR, "Could not extract $functionName from $functionsFile\n");
    exit(1);
  }
  eval($matches[0]);
}

$failures = 0;

function check($label, $cond) {
  global $failures;
  if ($cond) {
    echo "  ok  - $label\n";
  } else {
    echo "  NOT OK - $label\n";
    $failures++;
  }
}

$html = html_input('filter[Query][terms][0][op]', 'hidden', '"><script>alert(1)</script>');
check(
    'script tag in value is escaped',
    strpos($html, '<script>') === false
    && strpos($html, '&quot;&gt;&lt;script&gt;') !== false
);

$html = html_input('a', 'hidden', 'x" onfocus="alert(1)');
check(
    'double quote breakout in value is escaped',
    strpos($html, 'onfocus="') === false && substr_count($html, '"') === 6
);

$html = html_input('a"b', 'hidden', 'v');
check('name is escaped', strpos($html, 'name="a&quot;b"') !== false);

$html = html_input('a', 'text', 'v', ['class' => 'x"y']);
check('option values are escaped', strpos($html, 'class="x&quot;y"') !== false);

$html = html_input('n', 'hidden', 'v');
check(
    'normal output is unchanged',
    $html === '<input name="n" value="v" type="hidden"/>'
);

echo "5 tests, $failures failures\n";
exit($failures === 0 ? 0 : 1);
