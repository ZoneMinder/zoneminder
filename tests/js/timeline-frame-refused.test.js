'use strict';

// The timeline preview's frame and event replies come from ajax/status.php,
// which answers an error with result 'Error' and no frameimage/event.
// frameDataResponse read respObj.frameimage.FrameId regardless and threw.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.TIMELINE_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/timeline.js'), 'utf8');
const skin = fs.readFileSync(path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');

function extract(text, name) {
  const m = text.match(new RegExp('function ' + name + '\\([^)]*\\) \\{[\\s\\S]*?\\n\\}\\n'));
  assert.ok(m, name + ' not found');
  return m[0];
}

const errors = [];
const sandbox = {zmError: (m) => errors.push(m), console: {log() {}}};
vm.createContext(sandbox);
vm.runInContext(extract(skin, 'checkStreamForErrors') + extract(src, 'frameDataResponse') +
    extract(src, 'eventDataResponse'), sandbox);

const refused = {result: 'Error', message: 'Permission denied'};
assert.doesNotThrow(() => sandbox.frameDataResponse(refused), 'a refused frame reply does not throw');
assert.doesNotThrow(() => sandbox.eventDataResponse(refused), 'a refused event reply does not throw');
assert.strictEqual(errors.length, 2, 'both refusals are logged');
console.log('ok - timeline handles refused frame and event replies');
