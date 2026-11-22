'use strict';

// The Username field on the user view carries an HTML pattern. A browser will
// not submit the form while the value fails it, so a user whose name the server
// accepts but the pattern does not (e.g. one created through the API) could not
// be saved from the web UI at all, not even to change the password.
//
// includes/actions/user.php keeps word characters, '.', '@' and space
// (preg_replace('/[^\w\.@ ]/', '', ...)), so the pattern must accept those.
// Browsers compile the attribute as ^(?:pattern)$ with the v flag.

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const view = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/user.php'), 'utf8');

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

const m = view.match(/name="user\[Username\]"[^>]*?pattern="([^"]*)"/);
assert.ok(m, 'no pattern on the user[Username] input');
const pattern = new RegExp('^(?:' + m[1] + ')$', 'v');

// Same character set the save action keeps.
function serverKeeps(name) {
  return name.replace(/[^\w.@ ]/g, '') === name;
}

const names = ['admin', 'home_assistant', 'zm_viewer', 'first.last', 'me@example.com', 'Night Shift', 'cam2'];

names.forEach(function(name) {
  test(name + ' is accepted by the server and the form', () => {
    assert.ok(serverKeeps(name), 'server would strip characters from ' + name);
    assert.ok(pattern.test(name), 'pattern ' + m[1] + ' rejects ' + name);
  });
});

test('characters the server strips are still refused', () => {
  ['bob!', 'a<b', 'x/y'].forEach(function(name) {
    assert.ok(!pattern.test(name), 'pattern accepts ' + name);
  });
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
