<?php
// The montage review page script printed every storage area as Storage[<id>] = {...};
// using ZM\Storage::to_json(), which includes Url. For an s3fs area Url holds
// s3://KEY:SECRET@host/bucket, so the access key and secret were written into the page
// for anyone who could view a montage. The script only reads Id, Name and ServerId, so
// Url must not be printed at all.
//
// The page script is generated inline; here its storage loop is extracted and run with a
// fake ZM\Storage whose to_json() still includes Url, so the old code leaks it.
// Run as: php tests/php/test_montagereview_storage_hides_url.php [root]   (root defaults to the repo)

namespace ZM {
  class Storage {
    public static $all = array();
    private $id;
    private $name;
    private $server;
    private $url;

    function __construct($id=0, $name='Default', $server=0, $url='') {
      $this->id = $id;
      $this->name = $name;
      $this->server = $server;
      $this->url = $url;
    }

    static function find() {
      return self::$all;
    }

    function Id() {
      return $this->id;
    }

    function Name() {
      return $this->name;
    }

    function ServerId() {
      return $this->server;
    }

    function Url() {
      return $this->url;
    }

    function to_json() {
      return json_encode(array(
        'Id' => $this->id,
        'Name' => $this->name,
        'ServerId' => $this->server,
        'Url' => $this->url,
      ));
    }
  }
}

namespace {
  $failures = 0;
  function check($name, $got, $want) {
    global $failures;
    if ($got === $want) echo "ok - $name\n";
    else { $failures++; echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n"; }
  }

  $root = isset($argv[1]) ? $argv[1] : __DIR__.'/../..';
  $source = file_get_contents($root.'/web/skins/classic/views/js/montagereview.js.php');
  if (!preg_match('/^echo "\\\\nvar Storage = \[\];\\\\n";$.*?(?=^echo "\\\\nconst monitorData = \[\];\\\\n";$)/ms', $source, $m)) {
    echo "FAIL - web/skins/classic/views/js/montagereview.js.php has no Storage loop\n";
    exit(1);
  }

  ZM\Storage::$all = array(
    new ZM\Storage(1, 'Local', 0, ''),
    new ZM\Storage(2, 'Bucket', 5, 's3://AKIAKEY:SECRETKEY@host/bucket'),
  );

  ob_start();
  eval($m[0]);
  $output = ob_get_clean();

  $storage = array();
  preg_match_all('/^Storage\[(\d+)\] = (.*);$/m', $output, $matches, PREG_SET_ORDER);
  foreach ($matches as $match) {
    $storage[(int)$match[1]] = json_decode($match[2], true);
  }

  check('the fallback entry Storage[0] is printed', array_key_exists(0, $storage), true);
  check('Storage[1] is printed', array_key_exists(1, $storage), true);
  check('Storage[2] is printed', array_key_exists(2, $storage), true);
  check('Storage[2] keeps its ServerId', isset($storage[2]['ServerId']) ? $storage[2]['ServerId'] : null, 5);
  check('Storage[2] keeps its Name', isset($storage[2]['Name']) ? $storage[2]['Name'] : null, 'Bucket');
  check('the secret is not printed', strpos($output, 'SECRETKEY'), false);
  check('the access key is not printed', strpos($output, 'AKIAKEY'), false);
  check('the Url key is not printed', strpos($output, 'Url'), false);

  echo $failures ? "\n$failures failed\n" : "\nall passed\n";
  exit($failures ? 1 : 0);
}
