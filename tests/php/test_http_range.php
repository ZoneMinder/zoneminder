<?php
// Tests parseHttpRange(), which decides what web/views/view_video.php sends
// for a Range request.
//
// view_video.php used to take the end of the range straight from the request:
//
//   if (!empty($matches[2])) $end = intval($matches[2]);
//   $length = $end - $begin + 1;
//
// with no clamp to the end of the file, no 416 path, and no check that begin
// came before end. A range running past EOF therefore answered 206 with a
// Content-Length larger than the body could ever be, and the read loop simply
// stopped at EOF -- a truncated response, which a player reports as a broken
// file rather than as a bad request. A reversed range produced a negative
// Content-Length, and "bytes=-500" (the last 500 bytes) did not match the
// pattern at all, so the whole file came back instead.
//
// This matters for the byte-range HLS manifests VideoStore writes, where every
// fragment is fetched as a Range against one mp4.
//
// Run: php tests/php/test_http_range.php   (from the repo root)

require_once(__DIR__.'/../../web/includes/HttpRange.php');

$passed = 0;
$failed = 0;
function check($label, $got, $want) {
  global $passed, $failed;
  if ($got === $want) {
    $passed++;
    echo "  ok $label\n";
  } else {
    $failed++;
    echo "  FAIL $label\n";
    echo "       got:  ".var_export($got, true)."\n";
    echo "       want: ".var_export($want, true)."\n";
  }
}

$size = 1000;  // a representation of 1000 bytes, so valid offsets are 0..999

echo "ordinary ranges\n";
check('a closed range is taken as given', parseHttpRange('bytes=0-99', $size), array(0, 99));
check('an open ended range runs to the last byte', parseHttpRange('bytes=900-', $size), array(900, 999));
check('the whole thing can be asked for explicitly', parseHttpRange('bytes=0-999', $size), array(0, 999));
check('a single byte is a legal range', parseHttpRange('bytes=500-500', $size), array(500, 500));

echo "ranges that run past the end\n";
// The client is entitled to ask for more than is there and gets what is
// there. Answering with the length it asked for is what truncated the body.
check('an end past EOF is clamped to the last byte', parseHttpRange('bytes=0-99999', $size), array(0, 999));
check('clamping still applies mid file', parseHttpRange('bytes=990-1500', $size), array(990, 999));
check('an end exactly at EOF is already correct', parseHttpRange('bytes=0-1000', $size), array(0, 999));

echo "suffix ranges\n";
// "-N" is the LAST n bytes. The old pattern required a digit before the dash,
// so these fell through and the entire file was sent.
check('a suffix range is the last n bytes', parseHttpRange('bytes=-100', $size), array(900, 999));
check('a suffix larger than the file is the whole file', parseHttpRange('bytes=-5000', $size), array(0, 999));
check('a suffix of zero asks for nothing and cannot be satisfied', parseHttpRange('bytes=-0', $size), null);

echo "ranges that cannot be satisfied\n";
// null means 416. Serving 206 for these is what left a player waiting on
// bytes that were never going to arrive.
check('a start at EOF is unsatisfiable', parseHttpRange('bytes=1000-', $size), null);
check('a start past EOF is unsatisfiable', parseHttpRange('bytes=5000-6000', $size), null);
check('a reversed range is unsatisfiable, not a negative length',
  parseHttpRange('bytes=500-100', $size), null);
check('any range against an empty file is unsatisfiable', parseHttpRange('bytes=0-10', 0), null);

echo "requests with no range to honour\n";
// false means "send the whole thing, 200". A server may always ignore a range
// it cannot make sense of.
check('no header at all', parseHttpRange('', $size), false);
check('a null header', parseHttpRange(null, $size), false);
check('an unknown range unit', parseHttpRange('items=0-99', $size), false);
check('a unit with no range', parseHttpRange('bytes=', $size), false);
check('a dash naming neither end', parseHttpRange('bytes=-', $size), false);
check('junk where the numbers should be', parseHttpRange('bytes=abc-def', $size), false);

echo "tolerated spelling\n";
check('case does not matter', parseHttpRange('BYTES=0-99', $size), array(0, 99));
check('whitespace is allowed around the parts', parseHttpRange("bytes = 0 - 99", $size), array(0, 99));

echo "multiple ranges\n";
// A multipart/byteranges body is not worth building here, and falling back to
// the whole representation is not an option for a file this size. The first
// range is served and Content-Range names it exactly, so the client can ask
// for the rest.
check('the first range is served', parseHttpRange('bytes=0-99,200-299', $size), array(0, 99));
check('and is still clamped', parseHttpRange('bytes=900-99999,0-10', $size), array(900, 999));

echo "the length the caller derives is always deliverable\n";
// The property the whole thing exists for: whatever comes back, the bytes it
// names are inside the file, so Content-Length can never promise more than
// the body holds.
$headers = array(
  'bytes=0-99', 'bytes=900-', 'bytes=-100', 'bytes=-5000', 'bytes=0-99999',
  'bytes=990-1500', 'bytes=500-500', 'bytes=0-999', 'bytes=0-99,200-299',
  'bytes=1000-', 'bytes=5000-6000', 'bytes=500-100', 'bytes=-0', 'bytes=-',
  'bytes=abc', 'items=0-5', '', 'bytes=0-0',
);
$bad = array();
foreach ($headers as $h) {
  foreach (array(0, 1, 999, 1000, 1048576) as $sz) {
    $r = parseHttpRange($h, $sz);
    if (!is_array($r)) continue;   // false and null are not ranges
    list($b, $e) = $r;
    if (($b < 0) or ($e < $b) or ($e > $sz - 1)) {
      $bad[] = "$h against $sz gave [$b, $e]";
    }
  }
}
check('every returned range lies inside the file', $bad, array());

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
