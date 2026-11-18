'use strict';

// stringToLocaleString() localises the numbers in the pagination text, but
// Number(' ') is 0, so whitespace sitting between two numbers became '0'.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const skin = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');
const start = skin.indexOf('const stringToLocaleString');
assert.ok(start >= 0);
const code = skin.slice(start, skin.indexOf('\n};\n', start) + 4);
const ctx = {};
vm.createContext(ctx);
vm.runInContext(code + '\nthis.f = stringToLocaleString;', ctx);

assert.strictEqual(ctx.f('1 2'), '1 2');
assert.strictEqual(ctx.f('Showing 1 to 10 of 2000 rows'), 'Showing 1 to 10 of ' + (2000).toLocaleString() + ' rows');
assert.strictEqual(ctx.f('12  34'), '12  34');
assert.strictEqual(ctx.f(''), '');
console.log('ok');
