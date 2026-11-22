<?php
// Every translation the training view JS reads must be passed by training.js.php
// and have an en_gb entry.
// Run: php tests/php/test_lang_training_keys.php
$root = dirname(__DIR__, 2);
$SLANG = array();
include $root.'/web/lang/en_gb.php';

$phpSrc = file_get_contents($root.'/web/skins/classic/views/js/training.js.php');
$jsSrc = file_get_contents($root.'/web/skins/classic/views/js/training.js');

if (!preg_match('/\$translationKeys = \[(.*?)\];/s', $phpSrc, $m)) {
  fwrite(STDERR, "FAIL: cannot find \$translationKeys\n");
  exit(1);
}
preg_match_all("/'(\w+)'/", $m[1], $mm);
$passed = array_flip($mm[1]);

// JS reads through this.translations / self.translations, or the local alias `t`
// (const t = this.translations).
preg_match_all('/\b(?:translations|t)\.(Training\w+|[A-Z]\w+)\b/', $jsSrc, $rm);
$read = array_unique($rm[1]);
sort($read);

$fail = 0;
foreach ($read as $k) {
  if (!isset($passed[$k])) {
    echo "FAIL: training.js reads translations.$k but training.js.php does not pass it\n";
    $fail++;
  }
}
foreach (array('TrainingSavedLoaded', 'TrainingEditAnnotation') as $k) {
  if (!isset($SLANG[$k])) {
    echo "FAIL: $k missing from en_gb.php\n";
    $fail++;
  }
}
if ($fail) exit(1);
echo "OK: ".count($read)." keys read by training.js are all passed\n";
