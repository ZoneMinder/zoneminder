'use strict';

// Hiding the zones or zone page stops its streams after 15 s. Coming back
// before then must cancel that stop, or the streams die on a visible page.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

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

function load(file) {
  const src = fs.readFileSync(
      path.join(__dirname, '../../web/skins/classic/views/js/' + file), 'utf8');
  const chain = new Proxy(function() {}, {
    get: (t, p) => (p === 'length' ? 0 : () => chain),
    apply: () => chain,
  });
  // A hand driven clock: run(ms) fires whatever is due by then.
  let now = 0;
  let nextId = 1;
  const timers = new Map();
  const doc = {
    visibilityState: 'visible',
    querySelector: () => null,
    querySelectorAll: () => [],
    getElementById: () => null,
  };
  const ctx = {
    console, Math, JSON, parseInt, parseFloat, isFinite, Array, Object,
    $j: chain,
    window: {addEventListener() {}},
    document: doc,
    setTimeout(fn, ms) {
      timers.set(nextId, {fn, at: now + ms});
      return nextId++;
    },
    clearTimeout(id) {
      timers.delete(id);
    },
    monitorData: [{id: 1}],
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  const calls = [];
  const stream = {
    started: true,
    start() {
      calls.push('start'); this.started = true;
    },
    stop() {
      calls.push('stop'); this.started = false;
    },
    kill() {
      calls.push('kill'); this.started = false;
    },
  };
  vm.runInContext('monitors', ctx)[0] = stream;
  return {
    stream,
    calls,
    show(state) {
      doc.visibilityState = state;
      doc.onvisibilitychange();
    },
    run(ms) {
      now += ms;
      for (const [id, t] of [...timers]) {
        if (t.at <= now) {
          timers.delete(id);
          t.fn();
        }
      }
    },
  };
}

['zones.js', 'zone.js'].forEach(function(file) {
  console.log(file);

  test('coming back within 15 s keeps the stream running', () => {
    const page = load(file);
    page.show('hidden');
    page.run(5000);
    page.show('visible');
    page.run(20000);
    assert.deepStrictEqual(page.calls, []);
    assert.strictEqual(page.stream.started, true);
  });

  test('staying hidden for 15 s still stops it, and showing restarts it', () => {
    const page = load(file);
    page.show('hidden');
    page.run(15000);
    assert.strictEqual(page.stream.started, false);
    page.show('visible');
    assert.strictEqual(page.stream.started, true);
    assert.strictEqual(page.calls[page.calls.length - 1], 'start');
  });
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
