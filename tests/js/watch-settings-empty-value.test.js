'use strict';

// The settings modal sliders send each change to v4l2_settings, which answers
// with what zmu printed. When zmu fails it prints nothing, the value is '' and
// changeControl set the slider to '', which a range input turns into its
// midpoint, so the slider showed a value the camera never took.
//
// Run as: node tests/js/watch-settings-empty-value.test.js [path/to/watch.js]

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/watch.js');
const src = fs.readFileSync(file, 'utf8');

function load(response) {
  const sets = [];
  const own = {
    length: 1,
    val: (v) => sets.push(['val', v]),
    attr: (n, v) => sets.push(['attr', v]),
  };
  const element = new Proxy(function() {}, {
    get: (t, p) => (p === 'then' ? undefined : (p in own ? own[p] : element)),
    apply: () => element,
  });
  const chain = new Proxy(function() {}, {
    get: (t, p) => (p === 'then' ? undefined : chain),
    apply: () => chain,
  });
  const $j = Object.assign(function() {
    return element;
  }, {
    getJSON: () => {
      const req = {
        done(fn) {
          fn(response);
          return req;
        },
        fail: () => req,
      };
      return req;
    },
  });
  const globals = {
    $j, console: {log() {}, warn() {}, error() {}}, JSON, Object, Array,
    Math, monitorUrl: '', monitorId: 1, zmAuth: {appendTo: (u) => u},
    logAjaxFail() {}, setInterval: () => 1, clearInterval() {},
    setTimeout: () => 0, clearTimeout() {}, addEventListener() {},
    document: {
      getElementById: () => null, querySelector: () => null,
      querySelectorAll: () => [], addEventListener() {},
      createElement: () => ({}),
    },
    monitorData: [],
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (t, p) => (p === Symbol.unscopables ? undefined : (p in t ? t[p] : chain)),
  });
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'watch.js'});
  return {g: globals, sets};
}

let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('ok - ' + name);
  } catch (e) {
    failed++;
    console.log('FAIL - ' + name + ': ' + e.message);
  }
}

test('an empty zmu result leaves the slider alone', () => {
  const {g, sets} = load({result: 'Ok', controls: [{control: 'Hue', value: ''}]});
  g.changeControl({target: {name: 'newHue', value: '5'}});
  assert.deepStrictEqual(sets, []);
});

test('a reported value still updates the slider', () => {
  const {g, sets} = load({result: 'Ok', controls: [{control: 'Hue', value: '5'}]});
  g.changeControl({target: {name: 'newHue', value: '5'}});
  assert.deepStrictEqual(sets, [['val', '5'], ['attr', '5']]);
});

process.exit(failed ? 1 : 0);
