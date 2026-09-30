<?php
// Tests the Content-Disposition value download.php sends for a generated
// export, and the download URL downloadEvents() hands back for it.
//
// Merged exports are named '<Monitor> <start> to <end>.mp4', which carries
// spaces and colons. Emitted as a bare unquoted filename= parameter, browsers
// that parse Content-Disposition strictly (Chrome) find no usable filename and
// name the download after the last path segment of the URL instead - index.php.
//
// download_functions.php declares functions only, so this runs from a source
// checkout: php tests/php/test_download_content_disposition.php

require_once __DIR__.'/../../web/includes/download_functions.php';

$failures = 0;
$passes = 0;

function check($name, $got, $expected) {
  global $failures, $passes;
  if ($got === $expected) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name (got ".var_export($got, true)." expected ".var_export($expected, true).")\n";
  }
}

// ---- contentDispositionAttachment() ----

// The regression: a merged export name reached the browser unquoted.
$merged = 'Front Door 2026-09-03 10:11:12 to 2026-09-03 10:15:00.mp4';
check('merged export name is quoted',
  contentDispositionAttachment($merged),
  'attachment; filename="Front Door 2026-09-03 10_11_12 to 2026-09-03 10_15_00.mp4"'
  ."; filename*=UTF-8''".rawurlencode($merged));

// A name needing nothing carries no filename*: it is already unambiguous.
check('plain name needs no filename*',
  contentDispositionAttachment('zmDownload-12345.zip'),
  'attachment; filename="zmDownload-12345.zip"');

// Spaces alone are legal inside the quoted-string, so they survive as-is.
check('spaces are kept inside the quotes',
  contentDispositionAttachment('Front Door.mp4'),
  'attachment; filename="Front Door.mp4"');

// A quote or backslash would end or escape the quoted-string early, handing the
// browser a truncated name and a stray header parameter.
check('quote cannot terminate the quoted-string',
  contentDispositionAttachment('od"d.mp4'),
  'attachment; filename="od_d.mp4"; filename*=UTF-8\'\'od%22d.mp4');
check('backslash cannot escape out of the quoted-string',
  contentDispositionAttachment('od\\d.mp4'),
  'attachment; filename="od_d.mp4"; filename*=UTF-8\'\'od%5Cd.mp4');

// The rest of the characters Windows rejects in a filename.
check('windows-illegal characters are replaced',
  contentDispositionAttachment('a*b<c>d?e|f.mp4'),
  'attachment; filename="a_b_c_d_e_f.mp4"; filename*=UTF-8\'\'a%2Ab%3Cc%3Ed%3Fe%7Cf.mp4');

// A CR or LF in the value would split the header entirely.
check('control characters are replaced',
  contentDispositionAttachment("a\r\nb.mp4"),
  'attachment; filename="a_b.mp4"; filename*=UTF-8\'\'a%0D%0Ab.mp4');

// A unicode monitor name survives in filename*, where it is encoded, and is
// only degraded in the ASCII-only fallback.
$unicode = 'Zoo Küche.mp4';
check('non-ascii name is preserved in filename*',
  contentDispositionAttachment($unicode),
  'attachment; filename="Zoo K_che.mp4"'."; filename*=UTF-8''".rawurlencode($unicode));

// The header names the file, never a path.
check('a path is reduced to its basename',
  contentDispositionAttachment('/etc/passwd'),
  'attachment; filename="passwd"');

// ---- the download URL ----

// The same name has to survive as a query parameter. Round-tripping it the way
// the browser will is what proves it: '&' or '+' unescaped in the monitor name
// would otherwise split the parameter or decode as a space.
$link = '?view=download&type=mp4&file='.urlencode($merged).'&export_root='.urlencode('zmDownload-1');
parse_str(substr($link, 1), $params);
check('the file parameter round-trips', $params['file'], $merged);
check('the export_root parameter round-trips', $params['export_root'], 'zmDownload-1');

$awkward = 'Front & Back +1 2026-09-03 10:11:12 to 2026-09-03 10:15:00.mp4';
parse_str('file='.urlencode($awkward), $params);
check('ampersands and pluses in a monitor name round-trip', $params['file'], $awkward);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
