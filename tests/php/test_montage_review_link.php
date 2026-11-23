<?php
// Tests the Montage Review navbar link built by getMontageReviewHTML().
//
// When the request carries a StartDateTime >= / <= pair in
// filter[Query][terms], the link passes them on as minTime/maxTime. Those
// values come straight from the query string, so they have to be URL-encoded
// for the query and the whole href HTML-escaped for the attribute. Otherwise a
// value containing a double quote ends the href and the rest of it is parsed
// as markup in the navbar of whatever page the link was opened on.
//
// The real skin function library is loaded; only the helpers it calls from
// web/includes (canView, translate, validHtmlStr) are stubbed, so no database
// or zm.conf is needed.
//
// Run as: php tests/php/test_montage_review_link.php   (from the repo root)

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "  ok $name\n";
  } else {
    $failures++;
    echo "  FAIL $name\n";
    echo "    got:  " . var_export($got, true) . "\n";
    echo "    want: " . var_export($want, true) . "\n";
  }
}

function canView($area) { return true; }
function translate($string) { return $string; }
// Same as web/includes/functions.php.
function validHtmlStr($input) {
  if (is_null($input)) return '';
  return htmlspecialchars($input, ENT_QUOTES);
}

$skin = 'classic';
// Load the skin library under test. ZM_SKIN_FUNCTIONS_PHP lets the test run
// against another copy (e.g. the original file) instead of the working tree.
$functionsPhp = getenv('ZM_SKIN_FUNCTIONS_PHP');
if (empty($functionsPhp)) $functionsPhp = __DIR__.'/../../web/skins/classic/includes/functions.php';
require_once($functionsPhp);

// The query string a crafted link would carry. Real requests index terms
// numerically: terms[0][attr], terms[1][attr], ...
function setTerms($min, $max) {
  $_REQUEST = array('filter' => array('Query' => array('terms' => array(
    0 => array('attr' => 'StartDateTime', 'op' => '>=', 'val' => $min),
    1 => array('attr' => 'StartDateTime', 'op' => '<=', 'val' => $max),
  ))));
}

// Parses the navbar fragment the way a browser would and returns the links
// in it, plus every element name, so injected markup shows up as extra
// elements rather than as a string match.
function parseFragment($html) {
  $doc = new DOMDocument();
  libxml_use_internal_errors(true);
  $doc->loadHTML('<?xml encoding="UTF-8"><ul>'.$html.'</ul>');
  libxml_clear_errors();
  $links = array();
  foreach ($doc->getElementsByTagName('a') as $a) $links[] = $a->getAttribute('href');
  $tags = array();
  foreach ($doc->getElementsByTagName('*') as $el) $tags[$el->nodeName] = true;
  return array($links, array_keys($tags));
}

function queryOf($href) {
  $params = array();
  parse_str((string)parse_url($href, PHP_URL_QUERY), $params);
  return $params;
}

$payloads = array(
  'plain date' => array('2026-10-01 00:00:00', '2026-10-02 00:00:00'),
  'double quote and tags' => array('x"><base href="http://example.invalid/"><i x="', '2026-10-02 00:00:00'),
  'single quote and handler' => array("x' onmouseover='alert(1)", 'y"><script>alert(1)</script>'),
  'ampersand and equals' => array('a&live=1&view=console', 'b=c#d'),
);

foreach (array(false => 'top navbar', true => 'left sidebar') as $forLeftBar => $where) {
  foreach ($payloads as $name => list($min, $max)) {
    echo "$where: $name\n";
    setTerms($min, $max);
    $html = getMontageReviewHTML('console', (bool)$forLeftBar);
    list($links, $tags) = parseFragment($html);
    check('one link', count($links), 1);
    sort($tags);
    $want = array('a', 'body', 'html', 'i', 'li', 'span', 'ul');
    if (!$forLeftBar) $want = array('a', 'body', 'html', 'li', 'ul');
    check('no injected elements', $tags, $want);
    $q = queryOf($links[0] ?? '');
    check('view', $q['view'] ?? null, 'montagereview');
    check('minTime round-trips', $q['minTime'] ?? null, $min);
    check('maxTime round-trips', $q['maxTime'] ?? null, $max);
    check('live', $q['live'] ?? null, '0');
  }
}

// Behaviour check on its own: the numerically indexed pair real requests send
// (terms[0] StartDateTime >= min, terms[1] StartDateTime <= max) must put both
// bounds into the navbar link.
echo "numeric StartDateTime pair\n";
setTerms('2026-10-01 00:00:00', '2026-10-02 00:00:00');
$numericLinks = parseFragment(getMontageReviewHTML('console'))[0];
$numericQuery = queryOf($numericLinks[0] ?? '');
check('minTime carried', $numericQuery['minTime'] ?? null, '2026-10-01 00:00:00');
check('maxTime carried', $numericQuery['maxTime'] ?? null, '2026-10-02 00:00:00');

echo "malformed terms\n";
$_REQUEST = array('filter' => array('Query' => array('terms' => array('attr' => 'StartDateTime'))));
check('string term: plain link', parseFragment(getMontageReviewHTML('console'))[0], array('?view=montagereview'));
$_REQUEST = array('filter' => array('Query' => array('terms' => array(
  'attr' => array('attr' => 'StartDateTime', 'op' => '>=', 'val' => array('x')),
  0 => array('attr' => 'StartDateTime', 'op' => '<=', 'val' => 'y'),
))));
check('array value: plain link', parseFragment(getMontageReviewHTML('console'))[0], array('?view=montagereview'));
$_REQUEST = array();
check('no filter: plain link', parseFragment(getMontageReviewHTML('console'))[0], array('?view=montagereview'));

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
