'use strict';

// Event view (event.js) faults that left the page showing or doing something
// other than what the user asked for:
//
//  - A refused delete (HTTP 200, result=Error) was treated as done, so the page
//    moved on to the next event as if the event were gone.
//  - When zms moved to another event the delete button kept the state of the
//    first one, the tag chips stayed those of the first event, and the details
//    table showed "undefined" for the path and raw bytes for the disk space.
//  - Enter in the notes textarea of the edit modal pressed Save, and arrow keys
//    used to pick from a select moved to another event.
//  - The countdown to the next event wrapped at 24 hours.
//  - A saved reverse rate showed in the rate select while a native player
//    played forward.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/event.js'), 'utf8');
const skin = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');
const escapeSrc = skin.match(/^function escapeHTML\(text\) \{[\s\S]*?^\}/m)[0];
const sizeSrc = skin.match(/^function human_filesize\(size, precision = 2\) \{[\s\S]*?^\}/m)[0];

let passed = 0;
let failed = 0;
function test(name, fn) {
  try {
    fn();
    console.log('  ok ' + name);
    passed++;
  } catch (e) {
    console.error('  FAIL ' + name);
    console.error('    ' + (e.stack || e.message));
    failed++;
  }
}

function makeChainable() {
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : proxy),
    apply: () => proxy,
  });
  return proxy;
}

// $j that records what the page asks of it. `selectors` maps a selector to
// overrides of the chainable's methods.
function load(opts) {
  opts = opts || {};
  const log = {json: [], props: [], removed: [], vals: [], cookies: [], html: [], on: []};
  const selectors = opts.selectors || {};
  const $j = function(selector) {
    if (selector === globals.document) {
      return {on: (ev, sel, fn) => log.on.push({ev, fn})};
    }
    if (selector === '.tags-container .tag') {
      return {remove: () => log.removed.push(selector)};
    }
    if (selector && selector.indexOf && selector.indexOf('select[name="rate"]') === 0) {
      return {val: (v) => {
        log.vals.push(v); return undefined;
      }};
    }
    if (typeof selector === 'string' && selectors[selector]) {
      return Object.assign(Object.create(makeChainable()), selectors[selector]);
    }
    return makeChainable();
  };
  $j.getJSON = function(url) {
    log.json.push(url);
    const p = {done: () => p, fail: () => p, always: () => p};
    return p;
  };
  const rec = (name) => ({prop: (k, v) => {
    log.props.push([name, k, v]); return undefined;
  }});
  const globals = Object.assign({
    $j: $j,
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    alert: (m) => {
      log.alerts = (log.alerts || []).concat(m);
    },
    document: {
      getElementById: () => null,
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
    },
    setTimeout: () => 0,
    clearTimeout: () => 0,
    setInterval: () => 0,
    clearInterval: () => 0,
    getCookie: () => null,
    setCookie: (k, v) => log.cookies.push([k, v]),
    thisUrl: 'index.php',
    canEdit: {Events: true},
    canView: {Events: true, Monitors: true},
    zmAuth: {hash: ''},
    rates: [-1600, -1000, -500, -200, -100, -50, -25, 0, 25, 50, 100, 200, 500, 1000, 1600],
    eventData: {Id: 10, MonitorId: '1', Width: 640, Height: 480},
  }, opts.globals || {});
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => {
      if (prop === Symbol.unscopables) return undefined;
      if (prop in target) return target[prop];
      return globalThis[prop];
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox, {filename: 'event.js'});
  vm.runInContext(escapeSrc + '\n' + sizeSrc, sandbox);
  // The buttons are looked up once at load; replace them with recorders.
  for (const name of ['archiveBtn', 'unarchiveBtn', 'deleteBtn']) globals[name] = rec(name);
  return {globals, log};
}

// --- delete --------------------------------------------------------------

function deleteButtonHandler(g) {
  let handler = null;
  g.document.getElementById = (id) => (id === 'delConfirmBtn' ?
    {addEventListener: (ev, fn) => {
      if (ev === 'click') handler = fn;
    }} :
    {addEventListener: () => {}});
  g.manageDelConfirmModalBtns();
  return handler;
}

function runDelete(answer, vid) {
  const {globals, log} = load();
  const calls = [];
  globals.vid = vid || null;
  globals.pauseClicked = () => {};
  globals.streamReq = () => {};
  globals.streamNext = () => calls.push('streamNext');
  globals.restartZmsStream = () => calls.push('restart');
  globals.$j.getJSON = function(url) {
    const p = {done: (fn) => {
      fn(answer); return p;
    }, fail: () => p};
    return p;
  };
  deleteButtonHandler(globals)({preventDefault() {}});
  return {calls, log};
}

test('a delete the server refused does not move on to the next event', () => {
  const {calls, log} = runDelete({result: 'Error', message: 'Event 10 was not deleted: it is archived'});
  assert.ok(!calls.includes('streamNext'), 'moved on after a refused delete: ' + calls);
  assert.deepStrictEqual(log.alerts, ['Event 10 was not deleted: it is archived']);
});

test('a refused delete brings the zms stream back', () => {
  const {calls} = runDelete({result: 'Error', message: 'no'});
  assert.deepStrictEqual(calls, ['restart']);
});

test('a delete the server accepted moves on to the next event', () => {
  const {calls} = runDelete({result: 'Ok', refreshParent: true});
  assert.deepStrictEqual(calls, ['streamNext']);
});

// --- buttons and details follow the event zms moved to ----------------------

function eventResponse(g, prev, next) {
  g.eventData = prev;
  g.zmsEventChanged = () => {};
  g.getStat = () => {};
  g.checkStreamForErrors = () => false;
  g.history = {replaceState() {}};
  g.getEventResponse({event: next});
}

test('moving to an archived event disables the delete button', () => {
  const {globals, log} = load();
  eventResponse(globals, {Id: 10, Archived: false}, {Id: 11, Archived: true});
  const del = log.props.filter((p) => p[0] === 'deleteBtn');
  assert.deepStrictEqual(del.map((p) => p.slice(1)), [['disabled', true], ['title', 'You cannot delete an archived event.']]);
});

test('moving to an event that can be deleted enables the delete button', () => {
  const {globals, log} = load();
  eventResponse(globals, {Id: 10, Archived: true}, {Id: 11, Archived: false});
  const del = log.props.filter((p) => p[0] === 'deleteBtn' && p[1] === 'disabled');
  assert.deepStrictEqual(del.map((p) => p[2]), [false]);
});

test('moving to another event shows that event\'s tags', () => {
  const {globals, log} = load();
  eventResponse(globals, {Id: 10}, {Id: 11});
  assert.strictEqual(log.removed.length, 1, 'old tag chips were left in place');
  assert.ok(log.json.some((u) => u.indexOf('action=getselectedtags&id=11') >= 0), log.json.join(' '));
});

test('the same event again leaves the tags alone', () => {
  const {globals, log} = load();
  eventResponse(globals, {Id: 11}, {Id: 11});
  assert.strictEqual(log.removed.length, 0);
});

// --- details table -------------------------------------------------------

function statRows(eventData) {
  const rows = [];
  const {globals} = load();
  globals.eventDataStrings = {Path: 'Path', DiskSpace: 'DiskSpace'};
  globals.isEmpty = () => false;
  globals.eventData = eventData;
  globals.eventStatsTable = {empty: () => ({append: () => {}})};
  globals.$j = function(sel) {
    return {
      append: (r) => {
        if (r && r.cells) rows.push(r.cells); return {};
      },
    };
  };
  globals.$j.each = (obj, fn) => {
    for (const k of Object.keys(obj)) if (fn(k) === false) break;
  };
  // Build rows as {cells: [label, html]} through the chain getStat uses.
  const cell = (tag) => (tag === '<td>' ? {html: (h) => ({td: h})} : {addClass: () => ({text: (t) => ({th: t})})});
  const origJ = globals.$j;
  globals.$j = Object.assign(function(sel, ...rest) {
    if (sel === '<th class="label">' || sel === '<td>') return cell(sel);
    if (sel === '<tr>') return {append: (th, td) => ({cells: [th.th, td.td]})};
    if (sel === '#eventStatsTable tbody') {
      return {append: (r) => {
        rows.push(r.cells);
      }};
    }
    return makeChainable();
  }, {each: origJ.each});
  globals.getStat();
  return rows;
}

test('an event whose path is not known shows no path row', () => {
  // ajax/status.php entity=event has no Path, so after zms moved on the row
  // read "undefined" and linked to ?view=files&path=undefined.
  const rows = statRows({Id: 11, DiskSpace: '1048576', Storage: 'Default'});
  assert.ok(!rows.some((r) => String(r[1]).indexOf('undefined') >= 0), JSON.stringify(rows));
});

test('disk space in bytes is shown as a size', () => {
  const rows = statRows({Id: 11, DiskSpace: '1048576', Storage: 'Default', Path: '/e/1'});
  const disk = rows.find((r) => r[0] === 'DiskSpace');
  assert.strictEqual(disk[1], '1MB on Default');
});

test('disk space the page already formatted is left as it is', () => {
  const rows = statRows({Id: 10, DiskSpace: '12.50MB', Storage: 'Default', Path: '/e/1'});
  const disk = rows.find((r) => r[0] === 'DiskSpace');
  assert.strictEqual(disk[1], '12.50MB on Default');
});

// --- keyboard --------------------------------------------------------------

function keyHandler(modalVisible) {
  const sel = {
    '.tag-input': {is: () => false},
    '.modal': {is: () => modalVisible},
    '#eventDetailModal': {is: () => true},
    '#deleteConfirm': {is: () => false},
    '#eventDetailSaveBtn': {click: () => {
      sel.saved = true;
    }},
    '#deleteBtn': {is: () => false},
  };
  const {globals, log} = load({selectors: sel});
  const calls = [];
  globals.prevEvent = () => calls.push('prev');
  globals.nextEvent = () => calls.push('next');
  globals.isLightboxOpen = () => false;
  // load() ran event.js before the handler could be wired to the spies above;
  // $j(document).on was recorded in log.on.
  return {handler: log.on.find((o) => o.ev === 'keydown').fn, calls, sel};
}

test('Enter in the notes textarea is a new line, not Save', () => {
  const {handler, sel} = keyHandler(true);
  handler({key: 'Enter', target: {tagName: 'TEXTAREA'}});
  assert.ok(!sel.saved, 'Enter in the textarea pressed Save');
});

test('Enter elsewhere in the modal still saves', () => {
  const {handler, sel} = keyHandler(true);
  handler({key: 'Enter', target: {tagName: 'INPUT'}});
  assert.ok(sel.saved);
});

test('arrow keys picking from a select do not change event', () => {
  const {handler, calls} = keyHandler(false);
  handler({key: 'ArrowRight', target: {tagName: 'SELECT'}});
  handler({key: 'ArrowLeft', target: {tagName: 'INPUT'}});
  assert.deepStrictEqual(calls, []);
});

test('arrow keys on the page still change event', () => {
  const {handler, calls} = keyHandler(false);
  handler({key: 'ArrowRight', target: {tagName: 'BODY'}});
  handler({key: 'ArrowLeft', target: {tagName: 'BODY'}});
  assert.deepStrictEqual(calls, ['next', 'prev']);
});

// --- replay all: gap countdown ------------------------------------------------

test('the countdown to the next event does not wrap at 24 hours', () => {
  const now = Date.UTC(2026, 10, 1, 0, 0, 0);
  class FakeDate extends Date {
    static now() {
      return now;
    }
  }
  const {globals} = load({globals: {Date: FakeDate}});
  let tick = null;
  let shown = null;
  globals.setInterval = (fn) => {
    tick = fn; return 1;
  };
  globals.replayMode = {value: 'all'};
  globals.vid = {pause() {}};
  globals.nextEventId = 12;
  globals.eventData = {Id: 11, EndDateTime: '2026-11-01 00:00:00'};
  // 25 hours between the end of this event and the start of the next
  globals.nextEventStartTime = Date.parse('2026-11-01 00:00:00') + 25 * 3600 * 1000;
  globals.$j = function(sel) {
    if (sel === '.vjsMessage') {
      return {html: (h) => {
        shown = h;
      }};
    }
    if (sel === '#videoobj') return {append: () => {}, height: () => 100};
    return makeChainable();
  };
  globals.vjsReplay();
  assert.ok(tick, 'no countdown started');
  tick();
  assert.strictEqual(shown, '25:00:00 to next event.');
});

// --- initial rate ------------------------------------------------------------

test('a saved reverse rate is not left showing on a player that plays forward', () => {
  const {globals, log} = load({globals: {rate: '-200'}});
  const rates = [];
  globals.vid = {playbackRate: (r) => rates.push(r)};
  globals.applyInitialRate();
  assert.deepStrictEqual(rates, []);
  assert.deepStrictEqual(log.vals, [100]);
  assert.deepStrictEqual(log.cookies, [['zmEventRate', 100]]);
});

test('a forward rate is applied to the player', () => {
  const {globals, log} = load({globals: {rate: '200'}});
  const rates = [];
  globals.vid = {playbackRate: (r) => rates.push(r)};
  globals.applyInitialRate();
  assert.deepStrictEqual(rates, [2]);
  assert.deepStrictEqual(log.vals, []);
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
