'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

function extractFunction(src, name) {
  const start = src.search(new RegExp('function\\s+' + name + '\\s*\\('));
  if (start < 0) throw new Error('function ' + name + ' not found');
  const open = src.indexOf('{', start);
  let depth = 0;
  let quote = null;
  for (let i = open; i < src.length; i++) {
    const c = src[i];
    if (quote) {
      if (c === '\\') i++;
      else if (c === quote) quote = null;
      continue;
    }
    if (c === "'" || c === '"' || c === '`') {
      quote = c;
    } else if (c === '{') {
      depth++;
    } else if (c === '}') {
      depth--;
      if (depth === 0) return src.slice(start, i + 1);
    }
  }
  throw new Error('unterminated function ' + name);
}

const file = process.argv[2] || path.join(__dirname, '../../web/skins/classic/views/js/watch.js');
const src = fs.readFileSync(file, 'utf8');

function load() {
  const state = {tableCalls: [], alerts: [], urls: [], done: null, fail: null, ajaxFails: [], rows: []};
  const chain = {
    bootstrapTable: (a) => {
      state.tableCalls.push(a);
      return chain;
    },
  };
  const $j = () => chain;
  const req = () => {
    const p = {
      done: (f) => {
        state.done = f;
        return p;
      },
      fail: (f) => {
        state.fail = f;
        return p;
      },
    };
    return p;
  };
  $j.getJSON = (u, d) => {
    state.urls.push([u, d]);
    return req();
  };
  const globals = {
    $j, state, eventListTable: chain, thisUrl: '/zm/index.php', monitorId: 3,
    maxDisplayEvents: 25, zmAuth: {hash: 'abc'},
    deferTableRequestWhileHidden: () => false,
    processRows: (rows) => {
      state.rows = rows;
      return rows;
    },
    logAjaxFail: (...a) => state.ajaxFails.push(a),
    alert: (m) => state.alerts.push(m),
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (t, p) => (p === Symbol.unscopables ? undefined : t[p]),
    set: (t, p, v) => {
      t[p] = v;
      return true;
    },
  });
  vm.createContext(sandbox);
  const fnSrc = extractFunction(src, 'ajaxRequest');
  const ajaxRequest = vm.runInContext('(' + fnSrc + ')', sandbox);
  return {state, ajaxRequest};
}

let ok = true;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
  } catch (e) {
    ok = false;
    console.error('  FAIL ' + name + '\n    ' + e.message);
  }
}

test('watch: result=Error hides the loading indicator and says why', () => {
  const {state, ajaxRequest} = load();
  let succeeded = false;
  ajaxRequest({data: {}, success() {
    succeeded = true;
  }});
  state.done({result: 'Error', message: 'boom'});
  assert.ok(state.tableCalls.includes('hideLoading'));
  assert.deepStrictEqual(state.alerts, ['boom']);
  assert.ok(!succeeded);
});

test('watch: success passes rows to params.success', () => {
  const {state, ajaxRequest} = load();
  let calls = 0;
  const rows = [{Id: 7}];
  ajaxRequest({data: {}, success(r) {
    calls++;
    assert.deepStrictEqual(r, rows);
  }});
  state.done({rows});
  assert.strictEqual(calls, 1);
  assert.deepStrictEqual(state.alerts, []);
});

test('watch: HTTP failure hides the loading indicator and logs', () => {
  const {state, ajaxRequest} = load();
  ajaxRequest({data: {}, success() {}});
  state.fail({status: 500, responseText: ''}, 'error', 'Internal Server Error');
  assert.ok(state.tableCalls.includes('hideLoading'));
  assert.strictEqual(state.ajaxFails.length, 1);
});

if (!ok) process.exit(1);
console.log('ok');
