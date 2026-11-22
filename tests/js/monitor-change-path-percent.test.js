'use strict';

// change_Path() copied the percent-encoded user and password of a Path URL
// into the User and Pass fields as they were typed. The server and zmc treat
// those fields as plain text (zmc encodes them again when it builds the URL),
// so rtsp://admin:p%40ss@cam/s saved the password "p%40ss" and the camera
// refused it. The fields must get the decoded values.
//
// Run: node tests/js/monitor-change-path-percent.test.js [path/to/monitor.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.join(__dirname, '../..');
const srcPath = process.argv[2] ||
    path.join(root, 'web/skins/classic/views/js/monitor.js');
const src = fs.readFileSync(srcPath, 'utf8');

let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + (e.stack || e.message));
    failed++;
  }
}

function extract(name) {
  const start = src.indexOf('function ' + name + '(');
  let depth = 0;
  for (let i = src.indexOf('{', start); ; i++) {
    if (src[i] === '{') depth++;
    if (src[i] === '}' && !--depth) return src.slice(start, i + 1);
  }
}

function page(pathValue, user, pass) {
  const els = {
    'newMonitor[Path]': [{value: pathValue}],
    'newMonitor[User]': [{value: user}],
    'newMonitor[Pass]': [{value: pass}],
  };
  const ctx = {
    document: {getElementsByName: (name) => els[name] || []},
    console: {log: () => {}, warn: () => {}},
  };
  vm.createContext(ctx);
  vm.runInContext(extract('change_Path') + ';this.change_Path = change_Path;', ctx);
  return {els, changePath: ctx.change_Path};
}

test('encoded user and password are decoded into the fields', () => {
  const p = page('rtsp://us%40er:p%3Ass%2F1@host/x', '', '');
  p.changePath({type: 'keyup'});
  assert.strictEqual(p.els['newMonitor[User]'][0].value, 'us@er');
  assert.strictEqual(p.els['newMonitor[Pass]'][0].value, 'p:ss/1');
});

test('on blur decoded credentials are removed from the path', () => {
  const p = page('rtsp://admin:p%40ss@host/x', 'admin', 'p@ss');
  p.changePath({type: 'blur'});
  assert.strictEqual(p.els['newMonitor[Path]'][0].value, 'rtsp://host/x');
});

test('a lone % in the password does not throw', () => {
  const p = page('rtsp://admin:100%@host/x', '', '');
  p.changePath({type: 'keyup'});
  assert.strictEqual(p.els['newMonitor[Pass]'][0].value, '100%');
  p.changePath({type: 'blur'});
  assert.strictEqual(p.els['newMonitor[Path]'][0].value, 'rtsp://host/x');
});

console.log(failed ? failed + ' failed' : 'all passed');
process.exit(failed ? 1 : 0);
