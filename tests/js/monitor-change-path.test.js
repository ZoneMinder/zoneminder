'use strict';

// change_Path() copies the "user:pass" at the start of a Path into the User
// and Pass fields. It read the first "@" anywhere after "://" and the first
// ":" after that, so an "@" in the path or query (rtsp://h:554/live?token=a@b)
// was taken for the auth separator and the host and port overwrote the saved
// credentials. Only an "@" inside the authority counts.
//
// Run: node tests/js/monitor-change-path.test.js [path/to/monitor.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.join(__dirname, '../..');
// An optional argument lets a test run against another revision of the file.
const srcPath = process.argv[2] ||
    path.join(root, 'web/skins/classic/views/js/monitor.js');
const src = fs.readFileSync(srcPath, 'utf8');

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
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

test('an @ in the path or query leaves the saved credentials alone', () => {
  const p = page('rtsp://10.0.0.5:554/live?token=a@b', 'admin', 'secret');
  p.changePath({type: 'keyup'});
  assert.strictEqual(p.els['newMonitor[User]'][0].value, 'admin');
  assert.strictEqual(p.els['newMonitor[Pass]'][0].value, 'secret');
});

test('user:pass in the authority fills User and Pass', () => {
  const p = page('rtsp://u:p@host/x', 'old', 'old');
  p.changePath({type: 'keyup'});
  assert.strictEqual(p.els['newMonitor[User]'][0].value, 'u');
  assert.strictEqual(p.els['newMonitor[Pass]'][0].value, 'p');
});

test('a raw # in the password is read as credentials', () => {
  const p = page('rtsp://admin:pa#ss@10.0.0.1/stream', 'old', 'old');
  p.changePath({type: 'keyup'});
  assert.strictEqual(p.els['newMonitor[User]'][0].value, 'admin');
  assert.strictEqual(p.els['newMonitor[Pass]'][0].value, 'pa#ss');
});

test('a raw / in the password is read as credentials', () => {
  const p = page('rtsp://admin:a/b@h/x', 'old', 'old');
  p.changePath({type: 'keyup'});
  assert.strictEqual(p.els['newMonitor[User]'][0].value, 'admin');
  assert.strictEqual(p.els['newMonitor[Pass]'][0].value, 'a/b');
});

test('a host:port authority with an @ in the query is not credentials', () => {
  const p = page('rtsp://h:554/live?token=a@b', 'admin', 'secret');
  p.changePath({type: 'keyup'});
  assert.strictEqual(p.els['newMonitor[User]'][0].value, 'admin');
  assert.strictEqual(p.els['newMonitor[Pass]'][0].value, 'secret');
});

test('a raw @ in the password splits at the first colon and last @', () => {
  const p = page('rtsp://u:p@ss@host/x', 'old', 'old');
  p.changePath({type: 'keyup'});
  assert.strictEqual(p.els['newMonitor[User]'][0].value, 'u');
  assert.strictEqual(p.els['newMonitor[Pass]'][0].value, 'p@ss');
});

test('on blur matching credentials are still removed from the path', () => {
  const p = page('rtsp://u:p@host/x', 'u', 'p');
  p.changePath({type: 'blur'});
  assert.strictEqual(p.els['newMonitor[Path]'][0].value, 'rtsp://host/x');
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
