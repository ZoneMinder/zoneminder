'use strict';

// The run-as user select in filter.php must refresh the Save/Execute buttons
// like the other filter inputs do (issue 4624).
const assert = require('assert');
const fs = require('fs');
const path = require('path');

const viewSrc = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/filter.php'), 'utf8');
const call = viewSrc.match(/htmlSelect\('filter\[UserId\]'[\s\S]*?\);/);
assert.ok(call, 'filter[UserId] select is rendered');
assert.ok(/'data-on-change-this'\s*=>\s*'updateButtons'/.test(call[0]),
    'filter[UserId] select calls updateButtons on change');
console.log('ok filter userid hook');
