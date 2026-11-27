<?php
// Tests that the header storage dropdown and the Storage options tab show how
// much space is free, using the cached total/used values from Storage and the
// existing human_filesize() formatter. A storage area whose total is unknown
// (0) must not show a made-up free figure.
//
// The real skin function library and the real _options_storage.php partial are
// loaded with ZM\Storage and the helpers they call stubbed, so no database or
// zm.conf is needed.
//
// Run as: php tests/php/test_storage_free_space.php   (from the repo root)

namespace ZM {
  class Filter {
    public function addTerm($term) { return $this; }
    public function querystring() { return ''; }
  }

  // Stand-in for web/includes/Storage.php with the total/used values fixed.
  class Storage {
    public static $areas = array();
    public $id;
    public $name;
    public $total;
    public $used;

    public function __construct($id, $name, $total, $used) {
      $this->id = $id;
      $this->name = $name;
      $this->total = $total;
      $this->used = $used;
    }
    public static function find($parameters = array(), $options = array()) {
      return self::$areas;
    }
    public function Id() { return $this->id; }
    public function Name($new = null) { return $this->name; }
    public function Path() { return '/events/'.$this->id; }
    public function Type() { return 'local'; }
    public function Scheme() { return 'Medium'; }
    public function Server() { return new Server(); }
    public function disk_total_space() { return $this->total; }
    public function disk_used_space() { return $this->used; }
    public function disk_usage_percent() {
      return $this->total ? round(($this->used / $this->total) * 100) : 0;
    }
    public function event_disk_space() { return $this->used; }
    public function EventCount() { return 0; }
    public function MonitorCount() { return 0; }
  }

  class Server {
    public function Name() { return 'localhost'; }
  }
}

namespace {
  $failures = 0;
  $tests = 0;
  function check($label, $cond, $detail = '') {
    global $tests, $failures;
    $tests++;
    if ($cond) {
      echo "  ok  - $label\n";
    } else {
      $failures++;
      echo "  NOT OK - $label".($detail === '' ? '' : " ($detail)")."\n";
    }
  }

  function canView($area) { return true; }
  function canEdit($area) { return true; }
  function translate($string) { return $string; }
  function validHtmlStr($input) {
    if (is_null($input)) return '';
    return htmlspecialchars($input, ENT_QUOTES);
  }
  function makeLink($url, $label, $condition = 1, $options = '') {
    if ($condition) return '<a href="'.$url.'"'.($options ? ' '.$options : '').'>'.$label.'</a>';
    return $label;
  }

  // Load the real human_filesize() so the test uses the same formatter the
  // pages do, without pulling in the whole web function library and its DB use.
  function extract_function($src, $name) {
    $sig = strpos($src, 'function '.$name.'(');
    if ($sig === false) return null;
    $brace = strpos($src, '{', $sig);
    $depth = 0;
    for ($i = $brace; $i < strlen($src); $i++) {
      if ($src[$i] === '{') $depth++;
      elseif ($src[$i] === '}') {
        $depth--;
        if ($depth === 0) return substr($src, $sig, $i - $sig + 1);
      }
    }
    return null;
  }
  $root = dirname(__DIR__, 2);
  $webFunctions = file_get_contents($root.'/web/includes/functions.php');
  $human = extract_function($webFunctions, 'human_filesize');
  if ($human === null) {
    fwrite(STDERR, "Could not extract human_filesize() from web/includes/functions.php\n");
    exit(2);
  }
  eval($human);

  $skin = 'classic';
  require_once($root.'/web/skins/classic/includes/functions.php');

  $GB = 1024 * 1024 * 1024;
  \ZM\Storage::$areas = array(
    new \ZM\Storage(1, 'Tiny', 100 * $GB, 97 * $GB),   // 97% used, 3.00GB free
    new \ZM\Storage(2, 'Big', 500 * $GB, 100 * $GB),   // 20% used, 400.00GB free
    new \ZM\Storage(3, 'Unknown', 0, 0),               // unknown total
  );

  // --- header dropdown -----------------------------------------------------
  $html = getStorageHTML();
  check('header shows free space for 97% area', strpos($html, '(3.00GB free)') !== false, $html);
  check('header shows free space for 20% area', strpos($html, '(400.00GB free)') !== false, $html);

  if (preg_match('/>Unknown: 0%[^<]*<\/a>/', $html, $m)) {
    check('header omits free space when total is unknown', strpos($m[0], 'free') === false, $m[0]);
  } else {
    check('header has the unknown-total area', false, $html);
  }

  // --- Storage options tab -------------------------------------------------
  $view = 'options';
  $tab = 'storage';
  $canEdit = false;
  $user = new class {
    public function unviewableMonitorIds() { return array(); }
    public function viewableMonitorIds() { return array(); }
  };

  ob_start();
  include($root.'/web/skins/classic/views/_options_storage.php');
  $tabHtml = ob_get_clean();

  check('options tab has a Free column', strpos($tabHtml, 'colFree') !== false, $tabHtml);

  // Pull the Free cell out of the row for each storage area.
  $freeCell = function($name) use ($tabHtml) {
    if (!preg_match('/<tr>(?:(?!<\/tr>).)*?'.preg_quote($name, '/').'(?:(?!<\/tr>).)*?<td class="colFree">(.*?)<\/td>/s', $tabHtml, $m)) {
      return null;
    }
    return trim($m[1]);
  };

  check('options tab shows 3.00GB free for Tiny', $freeCell('Tiny') === '3.00GB', var_export($freeCell('Tiny'), true));
  check('options tab shows 400.00GB free for Big', $freeCell('Big') === '400.00GB', var_export($freeCell('Big'), true));
  check('options tab shows no free figure when total is unknown', $freeCell('Unknown') === '', var_export($freeCell('Unknown'), true));

  echo "\n$tests tests, $failures failures\n";
  exit($failures ? 1 : 0);
}
