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
require_once(__DIR__.'/../../web/skins/classic/includes/functions.php');

// The query string a crafted link would carry: 'attr' as a key of terms is
// what the function looks for.
function setTerms($min, $max) {
  $_REQUEST = array('filter' => array('Query' => array('terms' => array(
    'attr' => array('attr' => 'StartDateTime', 'op' => '>=', 'val' => $min),
    0 => array('attr' => 'StartDateTime', 'op' => '<=', 'val' => $max),
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

// release-1.36's getMontageReviewHTML() has no left-sidebar path (no buildMenuItem()),
// so only the top-navbar link is exercised here.
foreach (array(false => 'top navbar') as $forLeftBar => $where) {
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
