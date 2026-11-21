'use strict';

// Clicking Export (export view) or Download (snapshot view) again before the
// first request answers started a second progress ticker and lost the id of
// the first. Only the second was cleared on completion, so the first kept
// replacing the result message with dots for as long as the page was open.

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
  const texts = {};
  const clicks = {};
  let responded = [];
  const jq = function(sel) {
    const id = (typeof sel === 'string') ? sel.slice(1) : '';
    const el = {
      text(v) {
        if (v === undefined) return texts[id] || '';
        texts[id] = String(v);
        return el;
      },
      append(v) {
        texts[id] = (texts[id] || '') + v;
        return el;
      },
      addClass: () => el,
      removeClass: () => el,
      prop: () => el,
      serialize: () => '',
      click: () => el,
      ready() {},
    };
    return el;
  };
  const request = function(opts) {
    const p = {
      done(cb) {
        responded.push(cb); return p;
      },
      fail() {
        return p;
      },
    };
    if (opts && opts.success) responded.push(opts.success);
    return p;
  };
  jq.ajax = request;
  jq.getJSON = request;

  const intervals = new Map();
  let nextId = 1;
  const ctx = {
    console: {log() {}, warn() {}, error() {}},
    $j: jq,
    thisUrl: 'index.php',
    exportProgressString: 'Exporting',
    exportSucceededString: 'Export Succeeded',
    downloadProgressString: 'Downloading',
    downloadSucceededString: 'Download Succeeded',
    canView: {Snapshots: true},
    canEdit: {Snapshots: true},
    snapshot: {Id: 3, EventIds: [7]},
    setInterval(fn) {
      intervals.set(nextId, fn);
      return nextId++;
    },
    clearInterval(id) {
      intervals.delete(id);
    },
    setTimeout: () => 0,
    bindButton(sel, ev, data, fn) {
      clicks[sel] = fn;
    },
    document: {getElementById: () => ({addEventListener() {}}), referrer: ''},
    window: {addEventListener() {}, location: {replace() {}}},
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  return {
    ctx, texts, clicks,
    respond(obj) {
      const cbs = responded;
      responded = [];
      cbs.forEach((cb) => cb(obj));
    },
    tick() {
      [...intervals.values()].forEach((fn) => fn());
    },
  };
}

console.log('export view');
test('a second Export click leaves no ticker running after the result', () => {
  const page = load('export.js');
  page.ctx.exportEvents();
  page.ctx.exportEvents();
  page.respond({result: 'Ok', exportFile: '?view=archive&file=a.zip'});
  page.tick();
  page.tick();
  assert.strictEqual(page.texts.exportProgressTicker, 'Export Succeeded');
});

console.log('snapshot view');
test('a second Download click leaves no ticker running after the result', () => {
  const page = load('snapshot.js');
  page.ctx.initPage();
  const evt = {preventDefault() {}};
  page.clicks['#downloadBtn'](evt);
  page.clicks['#downloadBtn'](evt);
  page.respond({result: 'Ok', exportFile: '?view=archive&file=a.zip'});
  page.tick();
  page.tick();
  assert.strictEqual(page.texts.downloadProgressTicker, 'Download Succeeded');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
