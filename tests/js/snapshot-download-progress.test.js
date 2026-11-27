'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/snapshot.js'), 'utf8');

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

// The progress markup from views/snapshot.php: an h2 holding a text span and a
// ticker span. Setting text() on an element drops its children, as jQuery does.
function load() {
  const nodes = {};
  function node(id, parent) {
    const n = {id: id, text: '', children: [], detached: false};
    nodes[id] = n;
    if (parent) parent.children.push(n);
    return n;
  }
  function detach(n) {
    n.children.forEach(function(c) {
      c.detached = true;
      detach(c);
    });
    n.children = [];
  }
  const h2 = node('downloadProgress');
  node('downloadProgressText', h2);
  node('downloadProgressTicker', h2);

  const handlers = {};
  let done = null;
  const jq = function(sel) {
    const n = (typeof sel === 'string' && nodes[sel.slice(1)] && !nodes[sel.slice(1)].detached) ?
      nodes[sel.slice(1)] : null;
    return {
      length: n ? 1 : 0,
      prop() {
        return this;
      },
      addClass() {
        return this;
      },
      removeClass() {
        return this;
      },
      text(v) {
        if (v === undefined) return n ? n.text : '';
        if (n) {
          n.text = String(v);
          detach(n);
        }
        return this;
      },
      append(v) {
        if (n) n.text += v;
        return this;
      },
      ready() {},
    };
  };
  jq.getJSON = function() {
    return {
      done(cb) {
        done = cb;
        return this;
      },
      fail() {
        return this;
      },
    };
  };
  const ctx = {
    $j: jq,
    console: console,
    thisUrl: 'index.php',
    canView: {Snapshots: true},
    canEdit: {Snapshots: true},
    snapshot: {Id: 4, EventIds: [10, 11]},
    downloadProgressString: 'Downloading',
    downloadSucceededString: 'Download Succeeded',
    bindButton(sel, ev, data, fn) {
      handlers[sel] = fn;
    },
    logAjaxFail() {},
    setInterval: () => 1,
    clearInterval() {},
    setTimeout() {},
    document: {referrer: ''},
    window: {location: {assign() {}, replace() {}}},
  };
  vm.createContext(ctx);
  vm.runInContext(src + '\n;this.initPage = initPage;', ctx);
  ctx.initPage();
  handlers['#downloadBtn']({preventDefault() {}});
  return {jq: jq, done: done};
}

test('progress text shows while downloading and the ticker is kept', () => {
  const page = load();
  assert.strictEqual(page.jq('#downloadProgressText').text(), 'Downloading');
  assert.strictEqual(page.jq('#downloadProgressTicker').length, 1);
});

test('a failed download shows the server message', () => {
  const page = load();
  page.done({result: 'Error', message: 'No events to export'});
  assert.strictEqual(page.jq('#downloadProgressTicker').text(), 'No events to export');
});

test('a finished download says so', () => {
  const page = load();
  page.done({result: 'Ok', exportFile: '%3Fview%3Darchive'});
  assert.strictEqual(page.jq('#downloadProgressTicker').text(), 'Download Succeeded');
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
