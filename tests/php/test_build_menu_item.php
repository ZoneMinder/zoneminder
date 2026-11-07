<?php
// Tests buildMenuItem(), which renders every left-sidebar menu entry.
//
// It puts $id, $href and the link class into HTML attributes. Callers used to
// have to escape them first; the Montage Review link did not, and a request
// value ended the href. buildMenuItem() escapes them itself now, and keeps
// entities a caller already wrote, so a pre-escaped href (&amp;) is not
// escaped twice.
//
// The real skin function library is loaded with the helpers it calls from
// web/includes stubbed, so no database or zm.conf is needed.
//
// Run as: php tests/php/test_build_menu_item.php   (from the repo root)

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
$view = 'console';
require_once(__DIR__.'/../../web/skins/classic/includes/functions.php');

// The <li> and <a> a browser would build from the fragment.
function parseItem($html) {
  $doc = new DOMDocument();
  libxml_use_internal_errors(true);
  $doc->loadHTML('<?xml encoding="UTF-8"><ul>'.$html.'</ul>');
  libxml_clear_errors();
  $li = $doc->getElementsByTagName('li')->item(0);
  $a = $doc->getElementsByTagName('a')->item(0);
  $tags = array();
  foreach ($doc->getElementsByTagName('*') as $el) $tags[$el->nodeName] = true;
  return array(
    'id' => $li ? $li->getAttribute('id') : null,
    'href' => $a ? $a->getAttribute('href') : null,
    'class' => $a ? $a->getAttribute('class') : null,
    'tags' => implode(',', array_keys($tags)),
  );
}

function item($id, $href, $class = '') {
  return parseItem(buildMenuItem('x', $id, 'Label', $href, 'movie', $class, '', true));
}

// A constant href, as most callers pass.
$r = item('getEventsHTML', '?view=events');
check('plain href unchanged', $r['href'], '?view=events');
check('plain id unchanged', $r['id'], 'getEventsHTML');

// Raw values with markup stay inside their attributes.
$r = item('i"><b x="', 'x"><base href="http://example.invalid/"><i x="', 'c" onclick="y');
check('href kept whole', $r['href'], 'x"><base href="http://example.invalid/"><i x="');
check('id kept whole', $r['id'], 'i"><b x="');
check('class kept whole', $r['class'], 'c" onclick="y');
check('no injected elements', $r['tags'], 'html,body,ul,li,a,span,i');
$r = item('a', "x' onmouseover='y");
check('single quote stays in the href', $r['href'], "x' onmouseover='y");

// Raw ampersands are encoded.
$html = buildMenuItem('x', 'a', 'Label', '?view=a&b=1', 'movie', '', '', true);
check('raw & is written &amp;', strpos($html, 'href="?view=a&amp;b=1"') !== false, true);

// Callers that already escape are not escaped twice.
$html = buildMenuItem('watch', 'getCycleHTML', 'Cycle', '?view=watch&amp;cycle=true', 'repeat', '', '', true);
check('pre-escaped &amp; not doubled', strpos($html, 'href="?view=watch&amp;cycle=true"') !== false, true);
check('pre-escaped href reads back', parseItem($html)['href'], '?view=watch&cycle=true');
$custom = htmlspecialchars('https://example.invalid/a?b=1&c="d"');
check('htmlspecialchars caller reads back', item('menuItem-custom-3', $custom)['href'], 'https://example.invalid/a?b=1&c="d"');
$montage = validHtmlStr('?view=montagereview&fit=1&minTime='.urlencode('x"><i').'&maxTime=y&live=0');
$r = item('getMontageReviewHTML', $montage);
check('validHtmlStr caller reads back', $r['href'], '?view=montagereview&fit=1&minTime=x%22%3E%3Ci&maxTime=y&live=0');
check('validHtmlStr caller: no injected elements', $r['tags'], 'html,body,ul,li,a,span,i');

// The Montage Review sidebar entry end to end.
$_REQUEST = array('filter' => array('Query' => array('terms' => array(
  'attr' => array('attr' => 'StartDateTime', 'op' => '>=', 'val' => 'x"><base href="http://example.invalid/">'),
  0 => array('attr' => 'StartDateTime', 'op' => '<=', 'val' => '2026-10-02 00:00:00'),
))));
$r = parseItem(getMontageReviewHTML('console', true));
check('montage review sidebar: href', $r['href'],
  '?view=montagereview&fit=1&minTime='.urlencode('x"><base href="http://example.invalid/">').'&maxTime='.urlencode('2026-10-02 00:00:00').'&live=0');
check('montage review sidebar: no injected elements', $r['tags'], 'html,body,ul,li,a,span,i');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
