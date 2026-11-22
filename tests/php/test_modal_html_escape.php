<?php
/*
 * Modals must HTML-escape values that come from the database before echoing
 * them into the page. A storage area name and a filter's debug SQL are both
 * attacker-influenced, so a raw echo would inject markup.
 *
 * Run: php tests/php/test_modal_html_escape.php [root]
 * Pass a worktree root as the first argument to test another copy of the file.
 */
namespace ZM {
  class Filter {
    function __construct($id = null) {}
    function set($f) {}
    function canView() { return true; }
    function Id() { return 4; }
    function debugSql() {
      return "SELECT * FROM Events WHERE Name = '<img src=x onerror=alert(1)>'\nLIMIT 1";
    }
  }
}

namespace {
  $root = isset($argv[1]) ? rtrim($argv[1], '/') : __DIR__.'/../..';

  function canEdit($a) { return true; }
  function canView($a) { return true; }
  function validInt($i) { return (int)$i; }
  function validHtmlStr($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
  function translate($s) { return $s; }
  function ajaxError($m) { throw new RuntimeException($m); }
  function getCSRFinputHTML() { return ''; }
  function dbFetchAll($sql) { return array(); }
  function htmlSelect($name, $options, $selected) { return ''; }

  $tests = 0;
  $failures = 0;
  function check($label, $cond) {
    global $tests, $failures;
    $tests++;
    if ($cond) {
      echo "  ok  - $label\n";
    } else {
      $failures++;
      echo "  FAIL - $label\n";
    }
  }

  $tmpDir = sys_get_temp_dir().'/zm_modal_esc_'.getmypid();
  $includesDir = $tmpDir.'/includes';
  if (!is_dir($includesDir)) mkdir($includesDir, 0777, true);

  file_put_contents($includesDir.'/Server.php', "<?php\n");
  file_put_contents($includesDir.'/Filter.php', "<?php\n");
  file_put_contents($includesDir.'/Storage.php', <<<'PHP'
<?php
namespace ZM;
class Storage {
  static function find_one($p) { return new Storage(); }
  function __call($n, $a) {
    return $n === 'Name' ? '<script>alert(1)</script>' : '';
  }
}
PHP
  );

  $oldIncludePath = get_include_path();
  set_include_path($tmpDir.PATH_SEPARATOR.$oldIncludePath);

  function zm_modal_esc_rmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $entry) {
      if ($entry === '.' || $entry === '..') continue;
      $path = $dir.'/'.$entry;
      if (is_dir($path)) zm_modal_esc_rmdir($path);
      else @unlink($path);
    }
    @rmdir($dir);
  }

  register_shutdown_function(function() use ($tmpDir, $oldIncludePath) {
    set_include_path($oldIncludePath);
    zm_modal_esc_rmdir($tmpDir);
  });

  // (a) Storage modal: the storage area name must be escaped in the header.
  $_REQUEST = array('id' => '3');
  $Servers = array();
  ob_start();
  try {
    include $root.'/web/ajax/modals/storage.php';
  } catch (Throwable $e) {
    // Later parts of the modal may call helpers that are not stubbed here.
  }
  $html = ob_get_clean();
  check('storage name is escaped',
    strpos($html, '&lt;script&gt;alert(1)&lt;/script&gt;') !== false);
  check('storage name is not emitted raw',
    strpos($html, '<script>alert(1)</script>') === false);

  // (b) Filter debug modal: the SQL text must be escaped, newlines still become
  // <br/>.
  $_REQUEST = array('fid' => '4');
  ob_start();
  try {
    include $root.'/web/ajax/modals/filterdebug.php';
  } catch (Throwable $e) {
    // Same rationale as above.
  }
  $html = ob_get_clean();
  check('debug SQL markup is not emitted raw', strpos($html, '<img src=x') === false);
  check('debug SQL markup is escaped', strpos($html, '&lt;img src=x') !== false);
  check('debug SQL newlines still become <br/>', strpos($html, '<br/>') !== false);

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
