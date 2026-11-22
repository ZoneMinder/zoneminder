'use strict';

// validateForm trimmed every non-SELECT field before submit, including the
// camera password fields (Pass, ONVIF_Password), which are text inputs masked
// by the masked-input class. A password with a leading or trailing space was
// silently changed, and the server does not trim it, so the camera login broke.
// Other fields, such as the monitor Name, are still trimmed.
//
// Run: node tests/js/monitor-password-not-trimmed.test.js [path/to/monitor.js.php]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/monitor.js.php');
const src = fs.readFileSync(file, 'utf8');
const start = src.indexOf('function validateForm(form) {');
const end = src.indexOf('function updateMethods(element) {');
assert(start > 0 && end > start, 'validateForm not found');
const code = src.substring(start, end)
    .replace(/<\?php echo translate\('([^']*)'\) \?>/g, '$1');

function run() {
  const ctx = {alert: () => {}, confirm: () => true, monitorNames: {}, rtspStreamNames: {},
    ZM_FfmpegOptions: {badEntries: () => []}};
  vm.createContext(ctx);
  vm.runInContext(code, ctx);
  const defs = [
    ['newMonitor[Name]', ' cam ', 'text', ''],
    ['newMonitor[Type]', 'WebSite', 'text', ''],
    ['newMonitor[Path]', ' http://example.com ', 'text', ''],
    ['newMonitor[Pass]', ' secret ', 'text', 'masked-input'],
    ['newMonitor[ONVIF_Password]', ' onvif ', 'text', 'masked-input'],
    ['realPassword', ' pw ', 'password', ''],
  ];
  const form = {elements: {length: defs.length}};
  defs.forEach((d, i) => {
    const e = {name: d[0], nodeName: 'INPUT', value: d[1], type: d[2],
      classList: {contains: (c) => c == d[3]}};
    form.elements[i] = e;
    form.elements[d[0]] = e;
  });
  const ok = vm.runInContext('validateForm', ctx)(form);
  return {ok, v: (n) => form.elements[n].value};
}

let failed = 0;
function test(label, fn) {
  try {
    fn();
    console.log('  ok ' + label);
  } catch (e) {
    console.error('  FAIL ' + label + '\n    ' + e.message);
    failed++;
  }
}

const r = run();
test('form validates', () => assert.strictEqual(r.ok, true));
test('name is still trimmed', () => assert.strictEqual(r.v('newMonitor[Name]'), 'cam'));
test('path is still trimmed', () => assert.strictEqual(r.v('newMonitor[Path]'), 'http://example.com'));
test('masked Pass keeps its spaces', () => assert.strictEqual(r.v('newMonitor[Pass]'), ' secret '));
test('masked ONVIF_Password keeps its spaces', () => assert.strictEqual(r.v('newMonitor[ONVIF_Password]'), ' onvif '));
test('type=password keeps its spaces', () => assert.strictEqual(r.v('realPassword'), ' pw '));

process.exit(failed ? 1 : 0);
