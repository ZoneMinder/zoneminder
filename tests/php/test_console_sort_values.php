<?php

namespace ZM {
  class Monitor {
    private $row;
    public function __construct($row) { $this->row = $row; }
    public function Source() { return 'src:'.$this->row['Path']; }
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

  class Named {
    private $name;
    public function __construct($name) { $this->name = $name; }
    public function Name() { return $this->name; }
  }

  $path = __DIR__.'/../../web/ajax/console.php';
  $src = file_get_contents($path);
  if (!preg_match('/^function monitorSortValue\(.*?\n\}/ms', $src, $m)) {
    echo "Could not find monitorSortValue() in $path\n";
    exit(1);
  }
  eval($m[0]);

  $servers = array(2=>new Named('beta'));
  $storage = array(5=>new Named('Archive'));
  $monitor = array('Id'=>1, 'Name'=>'Front', 'Path'=>'rtsp://a', 'ServerId'=>2, 'StorageId'=>5,
    'Capturing'=>'Always', 'Analysing'=>'None', 'Recording'=>'Always', 'ZoneCount'=>3, 'TotalEvents'=>null);
  $V = function($sort, $row = null) use ($monitor, $servers, $storage) {
    return monitorSortValue($row ? $row : $monitor, $sort, $servers, $storage);
  };

  check('Source is the display source', $V('Source'), 'src:rtsp://a');
  check('Server is its name', $V('Server'), 'beta');
  check('Server with none assigned', $V('Server', array('ServerId'=>0) + $monitor), '');
  check('Storage is its name', $V('Storage'), 'Archive');
  check('Storage that was deleted', $V('Storage', array('StorageId'=>9) + $monitor), '');
  check('Function covers the three modes', $V('Function'), 'Always None Always');
  check('plain column', $V('Name'), 'Front');
  check('numeric column', $V('ZoneCount'), 3);
  check('event count without a summary row', $V('TotalEvents'), 0);
  check('event count missing from the row', $V('HourEvents'), 0);
  check('text column that is null stays text', $V('Manufacturer', array('Manufacturer'=>null) + $monitor), '');

  echo "\n$passes passed, $failures failed\n";
  exit($failures ? 1 : 0);
}
