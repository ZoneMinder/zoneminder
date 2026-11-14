<?php
function translate($v) { return '['.$v.']'; }

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

function render($request) {
  $_REQUEST = $request;
  ob_start();
  include __DIR__.'/../../web/ajax/modals/delconfirm.php';
  return ob_get_clean();
}

// jQuery sends an undefined param as an empty string, as getDelConfirmModal does when called without a title
$html = render(array('key' => 'ConfirmDeleteDevices', 'title' => ''));
check('an empty title falls back to the default title', strpos($html, '<h5 class="modal-title">[ConfirmDeleteTitle]</h5>') !== false, true);
check('the given key is used', strpos($html, '<p>[ConfirmDeleteDevices]</p>') !== false, true);

$html = render(array('key' => '', 'title' => 'ConfirmDeleteUserTitle'));
check('an empty key falls back to the default text', strpos($html, '<p>[ConfirmDeleteEvents]</p>') !== false, true);
check('the given title is used', strpos($html, '<h5 class="modal-title">[ConfirmDeleteUserTitle]</h5>') !== false, true);

$html = render(array());
check('no parameters give both defaults', strpos($html, '[ConfirmDeleteTitle]') !== false and strpos($html, '[ConfirmDeleteEvents]') !== false, true);

echo "$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
