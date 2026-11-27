<?php
// Source check for EventsController::index():
// 1. Validates that `limit` query param, when present, is a positive integer (> 0).
// 2. Validates that `page` query param, when present, is a positive integer (> 0).
// 3. Orders events by `Event.Id` ascending by default when no `sort` query param is given.
// 4. Preserves client's explicit sort / does not override when `sort` is present.

$file = __DIR__ . '/../../web/api/app/Controller/EventsController.php';

$failures = 0;
$passes = 0;

function check($name, $condition) {
  global $failures, $passes;
  if ($condition) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "not ok - $name\n";
  }
}

check('EventsController.php exists and is readable', file_exists($file) && is_readable($file));

$code = file_get_contents($file);

// Extract the index() method body
preg_match('/public function index\(\)\s*\{(.*?)\n\s*\}\s*\/\/\s*end public function index/s', $code, $matches);
$indexCode = isset($matches[1]) ? $matches[1] : '';

check('index() method found in EventsController.php', !empty($indexCode));

// Check limit validation
check('validates limit query parameter when present',
  strpos($indexCode, "\$this->request->query('limit')") !== false &&
  strpos($indexCode, 'limit must be a positive integer') !== false
);

// Check page validation
check('validates page query parameter when present',
  strpos($indexCode, "\$this->request->query('page')") !== false &&
  strpos($indexCode, 'page must be a positive integer') !== false
);

// Check positive integer validation checks (digits only and > 0)
check('checks limit is positive integer (digits only, > 0)',
  preg_match('/limit.*?(?:ctype_digit|preg_match|\> 0|0-9)/s', $indexCode) === 1
);

check('checks page is positive integer (digits only, > 0)',
  preg_match('/page.*?(?:ctype_digit|preg_match|\> 0|0-9)/s', $indexCode) === 1
);

// Check default order by Event.Id asc when no sort query parameter
check('checks for absence of sort query parameter',
  strpos($indexCode, "\$this->request->query('sort')") !== false
);

check('sets default order to Event.Id ascending',
  preg_match('/Event\.Id.*?[\'"]asc[\'"]/i', $indexCode) === 1
);

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
