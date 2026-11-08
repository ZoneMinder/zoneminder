'use strict';

// The audioMotion-analyzer library is not shipped with ZoneMinder (it is
// AGPL-3.0-or-later), so the admin installs it by hand following instructions
// that live in four places: the skin's PHP feature probe, the dynamic import in
// the skin JS, the help.txt beside the install location, and the translated
// help/strings. They drifted apart once already, sending people to a URL that
// serves a different build of the library than the one the import expects.
// These tests pin them to each other.

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '../..');
const read = (p) => fs.readFileSync(path.join(root, p), 'utf8');

const INSTALL_PATH = 'assets/audioMotion-analyzer/src/audioMotion-analyzer.js';

const functionsPhp = read('web/skins/classic/includes/functions.php');
const analyzerJs = read('web/skins/classic/js/audioMotionAnalyzer.js');
const helpTxt = read('web/skins/classic/' + INSTALL_PATH.replace(/[^/]+$/, 'help.txt'));
const assetsVersion = read('web/skins/classic/assets/version');
const enGb = read('web/lang/en_gb.php');
const ruRu = read('web/lang/ru_ru.php');

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + e.message);
    failed++;
  }
}

console.log('install path agreement');

test('AUDIO_MOTION_ENABLED probes the path the skin JS imports', () => {
  const probe = functionsPhp.match(/define\("AUDIO_MOTION_ENABLED",\s*file_exists\("([^"]+)"\)\)/);
  assert.ok(probe, 'AUDIO_MOTION_ENABLED define not found in functions.php');
  // skins/$skin/assets/... -> assets/...
  const probed = probe[1].replace(/^skins\/\$skin\//, '');
  assert.strictEqual(probed, INSTALL_PATH);

  const imported = analyzerJs.match(/import\('([^']+audioMotion-analyzer\.js)'\)/);
  assert.ok(imported, 'dynamic import of the library not found in audioMotionAnalyzer.js');
  // The import is relative to skins/<skin>/js/, so ../assets/... is assets/...
  assert.strictEqual(imported[1].replace(/^\.\.\//, ''), INSTALL_PATH);
});

test('help.txt tells the admin the path the code actually looks at', () => {
  assert.ok(
      helpTxt.includes('skins/classic/' + INSTALL_PATH),
      'help.txt does not name skins/classic/' + INSTALL_PATH);
});

test('the help text does not use a placeholder skin name', () => {
  for (const [name, text] of [['help.txt', helpTxt], ['en_gb.php', enGb], ['ru_ru.php', ruRu]]) {
    assert.ok(!/MySkin/.test(text), name + ' still refers to the "MySkin" placeholder');
  }
});

console.log('download instructions');

// The package root serves "main", the minified UMD bundle in dist/. Only the
// explicit /src/ path serves the ES module that the dynamic import needs, so
// the bare URL may appear only on a line that warns against using it.
const LINK = /https:\/\/cdn\.jsdelivr\.net\/npm\/audiomotion-analyzer@[^\s'",)]*/g;
// The Russian phrase is written with \u escapes so this file stays ASCII
// and passes utils/check-homoglyphs.py.
const RU_DO_NOT_USE = '\u041d\u0435 \u0438\u0441\u043f\u043e\u043b\u044c\u0437\u0443\u0439\u0442\u0435';
const isWarning = (line) => /\bbare\b|Do not use/i.test(line) || line.includes(RU_DO_NOT_USE);

test('every jsDelivr link offered for download points at src/audioMotion-analyzer.js', () => {
  for (const [name, text] of [['help.txt', helpTxt], ['en_gb.php', enGb], ['ru_ru.php', ruRu], ['assets/version', assetsVersion]]) {
    let offered = 0;
    for (const line of text.split('\n')) {
      for (const match of line.match(LINK) || []) {
        if (isWarning(line)) continue;
        // "~~" is the lang catalogues' line-break marker, not part of the URL.
        const link = match.replace(/~~$/, '');
        offered++;
        assert.ok(
            link.endsWith('/src/audioMotion-analyzer.js'),
            name + ' offers ' + link + ', which serves the UMD bundle, not the ES module');
      }
    }
    assert.ok(offered, name + ' offers no jsDelivr download link');
  }
});

test('isWarning only exempts lines that tell you not to use the URL', () => {
  assert.ok(isWarning('Do not use the bare https://cdn.jsdelivr.net/npm/audiomotion-analyzer@X.X.X URL'));
  assert.ok(!isWarning('https://cdn.jsdelivr.net/npm/audiomotion-analyzer@X.X.X/src/audioMotion-analyzer.js'));
});

console.log('version agreement');

const supported = analyzerJs.match(/SUPPORTED_AUDIO_MOTION_ANALYZER_VERSION\s*=\s*'([^']+)'/);

test('audioMotionAnalyzer.js declares the supported version', () => {
  assert.ok(supported, 'SUPPORTED_AUDIO_MOTION_ANALYZER_VERSION not found');
  assert.match(supported[1], /^\d+\.\d+\.\d+$/);
});

test('assets/version documents the same version the code requires', () => {
  const entry = assetsVersion.match(/audioMotion-analyzer - (\d+\.\d+\.\d+)/);
  assert.ok(entry, 'assets/version has no audioMotion-analyzer entry');
  assert.strictEqual(entry[1], supported[1]);
});

test('help.txt does not hard-code a second copy of the version', () => {
  assert.ok(
      !helpTxt.includes(supported[1]),
      'help.txt repeats the version ' + supported[1] + '; it should point at ' +
      'SUPPORTED_AUDIO_MOTION_ANALYZER_VERSION instead');
});

console.log('translation keys');

test('en_gb and ru_ru define the same AudioMotion keys', () => {
  const keys = (text) => (text.match(/'(AudioMotion\w+|RequiresAudioMotionEnabled)'\s*=>/g) || [])
      .map((m) => m.match(/'([^']+)'/)[1]).sort();
  assert.deepStrictEqual(keys(enGb), keys(ruRu));
  assert.ok(keys(enGb).length >= 4, 'expected the AudioMotion* strings to be present');
});

test('the version-check strings use the placeholders monitor.js substitutes', () => {
  const monitorJs = read('web/skins/classic/views/js/monitor.js');
  const substituted = (monitorJs.match(/replaceAll\('\{(\w+)\}'/g) || [])
      .map((m) => m.match(/\{(\w+)\}/)[1]);
  assert.ok(substituted.length, 'monitor.js substitutes no AudioMotion placeholders');
  for (const [name, text] of [['en_gb.php', enGb], ['ru_ru.php', ruRu]]) {
    const used = new Set((text.match(/\{(AudioMotionVersion\w+)\}/g) || []).map((m) => m.slice(1, -1)));
    for (const ph of used) {
      assert.ok(
          substituted.includes(ph),
          name + ' uses {' + ph + '}, which monitor.js never substitutes');
    }
  }
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
