<?php
// i18n() (the data-locale of the tables) must follow the user's Language,
// the same one loadLanguage() translates the page into.
// Run: php tests/php/test_i18n_user_language.php <web root>
$root = $argv[1] ?? dirname(__DIR__, 2).'/web';
$src = file_get_contents($root.'/includes/functions.php');
if (!preg_match('/^function i18n\(\) \{.*?^\}/ms', $src, $m)) {
  fwrite(STDERR, "FAIL: cannot find i18n()\n");
  exit(1);
}
eval($m[0]);
chdir($root);
define('ZM_LANG_DEFAULT', 'en_gb');
class FakeUser { function __construct($l) { $this->l = $l; } function Language() { return $this->l; } }
$fail = 0;
foreach ([[null, 'en-GB'], [new FakeUser(''), 'en-GB'], [new FakeUser('de_de'), 'de-DE'], [new FakeUser('nope_xx'), 'en-GB']] as [$user, $want]) {
  $got = i18n();
  if ($got !== $want) { echo "FAIL: expected $want got $got\n"; $fail++; }
}
if ($fail) exit(1);
echo "OK: i18n follows the user language\n";
