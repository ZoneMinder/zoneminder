<?php

namespace ZM {
  class Monitor {
    private $row;
    public function __construct($row) { $this->row = $row; }
    public function Source() {
      return preg_replace('/^.*@/', '', $this->row['Host']).':'.$this->row['Port'];
    }
    public function Path() { return $this->row['Path']; }
  }
}

namespace {
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
      echo '    got:  '.var_export($got, true)."\n";
      echo '    want: '.var_export($want, true)."\n";
    }
  }

  $src = file_get_contents(__DIR__.'/../../web/ajax/console.php');
  if (!preg_match('/^function monitorMatchesSource\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find monitorMatchesSource() in web/ajax/console.php\n";
    exit(1);
  }
  eval($m[0]);
  // monitorMatchesSource calls this helper from the same file
  if (!preg_match('/^function pathWithoutQuery\(.*?\n\}/ms', $src, $m)) {
    fwrite(STDERR, "could not find pathWithoutQuery in web/ajax/console.php\n");
    exit(1);
  }
  eval($m[0]);

  $remote = array('Host'=>'admin:secret@10.0.0.5', 'Port'=>'8080', 'Path'=>'', 'Device'=>null);

  check('displayed host and port', monitorMatchesSource($remote, '/10\.0\.0\.5:8080/i') ? true : false, true);
  check('anchored displayed source', monitorMatchesSource($remote, '/^10\.0\.0\.5:8080$/i') ? true : false, true);
  check('credentials are not matched', monitorMatchesSource($remote, '/secret/i') ? true : false, false);
  check('no match', monitorMatchesSource($remote, '/10\.0\.0\.9/i') ? true : false, false);

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
