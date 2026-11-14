<?php
namespace ZM;

$failures = 0;
$passes = 0;

function check($name, $got, $want) {
  global $failures, $passes;
  if ($got === $want) {
    $passes++;
    echo "ok - $name\n";
  } else {
    $failures++;
    echo "FAIL - $name (got ".var_export($got, true).", want ".var_export($want, true).")\n";
  }
}

$stubdir = sys_get_temp_dir().'/zm_virtual_frame_stubs_'.getmypid();
@mkdir($stubdir, 0700, true);
foreach (array('database.php', 'Storage.php', 'functions.php', 'Event_Tag.php', 'Tag.php') as $stub) {
  file_put_contents($stubdir.'/'.$stub, "<?php\n");
}
set_include_path($stubdir.PATH_SEPARATOR.__DIR__.'/../../web/includes'.PATH_SEPARATOR.get_include_path());

function Warning($s) {}
function Error($s) {}
function Debug($s) {}
function Info($s) {}

$bulk = array(
  array('FrameId' => 1, 'Delta' => 0.1),
  array('FrameId' => 20, 'Delta' => 1.9),
);
function dbFetchOne($sql, $col = null, $values = null) {
  global $bulk;
  $fid = $values[1];
  $found = false;
  foreach ($bulk as $row) {
    if (strpos($sql, 'FrameId < ?') !== false and $row['FrameId'] < $fid) $found = $row;
    if (strpos($sql, 'FrameId > ?') !== false and $row['FrameId'] > $fid and !$found) $found = $row;
  }
  return $found;
}

class Frame {
  private $data;
  public function __construct($data) { $this->data = $data; }
  public function FrameId($new = null) {
    if ($new !== null) $this->data['FrameId'] = $new;
    return $this->data['FrameId'];
  }
  public function Delta($new = null) {
    if ($new !== null) $this->data['Delta'] = $new;
    return $this->data['Delta'];
  }
}

require_once __DIR__.'/../../web/includes/Event.php';

$event = new Event(array('Id' => 5, 'Frames' => 30, 'Length' => 3.0));

$_REQUEST = array();
$frame = $event->find_virtual_frame(25);
check('frame after the last bulk frame keeps its own id', $frame->FrameId(), 25);
check('frame after the last bulk frame is placed between that frame and the end', $frame->Delta(), 2.45);
check('later frame gets a later delta', $event->find_virtual_frame(29)->Delta(), 2.89);
check('frame between bulk frames is interpolated', $event->find_virtual_frame(10)->Delta(), 0.95);

echo "\n$passes passed, $failures failed\n";
array_map('unlink', glob($stubdir.'/*'));
@rmdir($stubdir);
exit($failures ? 1 : 0);
