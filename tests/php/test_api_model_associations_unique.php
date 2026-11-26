<?php
// API model fixes:
//  * CameraModel joins its Manufacturer through ManufacturerId (it joined
//    Manufacturer.Id = CameraModel.Id, so /cameramodels returned the wrong
//    manufacturer)
//  * EncoderTemplate uniqueness is checked when an edit sends only the Name
//    or only the Encoder (both used to skip it and fail on the DB unique key)
//  * Role names are unique (User_Roles has UNIQUE KEY UC_Name)
//
//   php tests/php/test_api_model_associations_unique.php [model dir]

class App { public static function uses($class, $package) {} }

// Minimal stand-in for CakePHP's Model: an in-memory table behind find('count') and field().
class AppModel {
  public $id = false;
  public $data = array();
  public $alias;
  public static $rows = array();
  public function __construct() { $this->alias = get_class($this); }
  public function field($name) {
    foreach (self::$rows as $row) if ($row['Id'] == $this->id) return $row[$name];
    return false;
  }
  public function find($type, $options) {
    $n = 0;
    foreach (self::$rows as $row) {
      $match = true;
      foreach ($options['conditions'] as $key => $value) {
        $parts = explode('.', $key);
        $col = end($parts);
        if (substr($col, -3) == ' !=') {
          if ($row[substr($col, 0, -3)] == $value) $match = false;
        } else if ($row[$col] != $value) {
          $match = false;
        }
      }
      if ($match) $n++;
    }
    return $n;
  }
}

$dir = isset($argv[1]) ? $argv[1] : __DIR__.'/../../web/api/app/Model';
foreach (array('EncoderTemplate', 'CameraModel', 'Role') as $c) require_once $dir.'/'.$c.'.php';

$passes = 0;
$failures = 0;
function check($ok, $what) {
  global $passes, $failures;
  if ($ok) { $passes++; echo "ok - $what\n"; } else { $failures++; echo "not ok - $what\n"; }
}

// CameraModel -> Manufacturer
$m = new CameraModel();
check(!isset($m->hasOne['Manufacturer']), 'CameraModel has no hasOne Manufacturer (it joins Manufacturer.Id = CameraModel.Id)');
check(isset($m->belongsTo['Manufacturer']) and $m->belongsTo['Manufacturer']['foreignKey'] == 'ManufacturerId',
  'CameraModel belongsTo Manufacturer through ManufacturerId');

// Role name is unique
$r = new Role();
check(isset($r->validate['Name']['unique']['rule']) and $r->validate['Name']['unique']['rule'] == array('isUnique'),
  'Role.Name has an isUnique rule');

// EncoderTemplate
AppModel::$rows = array(
  array('Id' => 1, 'Encoder' => 'libx264', 'Name' => 'fast'),
  array('Id' => 2, 'Encoder' => 'libx264', 'Name' => 'slow'),
  array('Id' => 3, 'Encoder' => 'libx265', 'Name' => 'fast'),
);
function unique($id, $data) {
  $t = new EncoderTemplate();
  $t->id = $id;
  $t->data = array('EncoderTemplate' => $data);
  // Cake passes the field that owns the rule: Name when it is sent, else Encoder.
  return $t->isUniqueByEncoder(isset($data['Name']) ? array('Name' => $data['Name']) : array('Encoder' => $data['Encoder']));
}
check(unique(false, array('Encoder' => 'libx264', 'Name' => 'fast')) === false, 'add: duplicate Encoder+Name is refused');
check(unique(false, array('Encoder' => 'libx265', 'Name' => 'slow')) === true, 'add: new Encoder+Name is accepted');
check(unique(2, array('Name' => 'fast')) === false, 'edit: renaming to a name the encoder already has is refused');
check(unique(2, array('Name' => 'medium')) === true, 'edit: renaming to a free name is accepted');
check(unique(2, array('Name' => 'slow')) === true, 'edit: keeping its own name is accepted');
check(unique(1, array('Encoder' => 'libx265')) === false, 'edit: moving to an encoder that has the name is refused');
check(unique(2, array('Encoder' => 'libx265')) === true, 'edit: moving to an encoder without the name is accepted');
$t = new EncoderTemplate();
check(isset($t->validate['Encoder']['unique']['rule']), 'Encoder carries the uniqueness rule so an Encoder-only edit is checked');

echo "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
?>
