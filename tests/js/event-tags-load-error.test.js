'use strict';

// getAvailableTags() stored data.response, which an error reply from the tags
// handler doesn't have, so availableTags became undefined and the tag input
// and Ctrl+ArrowDown shortcut threw on availableTags.map / availableTags[0].

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.EVENT_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/event.js'), 'utf8');
const match = src.match(/function getAvailableTags\(\) \{[\s\S]*?\n\}\n/);
assert.ok(match, 'getAvailableTags() not found in event.js');

function load(reply) {
  const $j = () => ({});
  $j.getJSON = () => {
    const p = {
      done(cb) {
        cb(reply); return p;
      },
      fail() {
        return p;
      },
    };
    return p;
  };
  const sandbox = {$j, thisUrl: '?', logAjaxFail() {}};
  vm.createContext(sandbox);
  vm.runInContext('var availableTags = [];\n' + match[0], sandbox);
  vm.runInContext('getAvailableTags()', sandbox);
  return vm.runInContext('availableTags', sandbox);
}

const tags = [{Id: 1, Name: 'person'}];
assert.deepStrictEqual(load({result: 'Ok', response: tags}), tags, 'tags are loaded');
const onError = load({result: 'Error', message: 'Not allowed'});
assert.ok(Array.isArray(onError), 'an error reply leaves availableTags a list');
assert.strictEqual(onError.length, 0, 'and an empty one');
console.log('ok - getAvailableTags keeps a list when the request is refused');
