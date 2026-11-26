'use strict';

// human_filesize() printed "NaNB" for NaN (the monitor RAM estimate with a
// blank Width or Height), "NaN" past yottabytes, and never returned for
// Infinity (a huge Width), freezing the page.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.SKIN_JS ||
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');
const match = src.match(/function human_filesize\(size, precision = 2\) \{[\s\S]*?\n\}\n/);
assert.ok(match, 'human_filesize() not found in skin.js');

const sandbox = {};
vm.createContext(sandbox);
vm.runInContext(match[0], sandbox);
const hf = (v) => vm.runInContext('human_filesize(' + v + ')', sandbox, {timeout: 1000});

assert.strictEqual(hf(1536), '1.5kB', 'normal sizes are unchanged');
assert.strictEqual(hf(0), '0B', 'zero is unchanged');
assert.strictEqual(hf('NaN'), 'n/a', 'NaN is not printed as NaNB');
assert.strictEqual(hf('Infinity'), 'n/a', 'Infinity returns instead of looping');
assert.ok(hf('1e30').endsWith('YB'), 'sizes past YB stay in YB');
console.log('ok - human_filesize handles NaN, Infinity and huge sizes');
