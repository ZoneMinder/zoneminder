'use strict';

// getStat() (event.js) empties #eventStatsTable and builds the monitor's Live,
// Edit and All Events buttons again, because the table is rebuilt for every
// event the view shows. Their click handlers used to be bound once, in
// initPage(), directly on the buttons the first getStat() had built, so after
// zms moved the view on to the next event (getEventResponse -> getStat) the
// rebuilt buttons carried no handlers and did nothing until a page reload.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const srcPath = process.env.EVENT_JS || process.argv[2] ||
    path.join(__dirname, '../../web/skins/classic/views/js/event.js');
const src = fs.readFileSync(srcPath, 'utf8');
const skin = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/skin.js'), 'utf8');
// The shipped escapeHTML() and bindButton(), not copies of them
const escapeSrc = skin.match(/^function escapeHTML\(text\) \{[\s\S]*?^\}/m)[0];
const bindSrc = skin.match(/^function bindButton\(selector, action, data, func\) \{[\s\S]*?^\}/m)[0];

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

// A chainable that reports itself as an empty selection, for ids the page does
// not have.
function makeEmpty() {
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : (prop === 'length' ? 0 : proxy)),
    apply: () => proxy,
  });
  return proxy;
}

// A $j that models the stats table and the buttons getStat() puts in it. Each
// button is an element of its own: a button built again is a new element and
// starts with no handlers, which is what happens in the browser. Handlers on
// #eventStatsTable are kept apart as delegated ones and survive .empty().
function load(eventData) {
  const elements = {}; // id -> element with the handlers bound to it
  const delegated = []; // {selector, ev, fn} bound on the table
  const navigations = [];

  function makeButton(id) {
    const el = {
      length: 1,
      handlers: {},
      on(ev, data, fn) {
        (el.handlers[ev] = el.handlers[ev] || []).push(fn);
        return el;
      },
    };
    elements[id] = el;
    return el;
  }

  const table = {
    empty() {
      // .empty() removes the rows, buttons and all, but not the table itself,
      // so the handlers delegated on it stay.
      for (const id of Object.keys(elements)) delete elements[id];
      return table;
    },
    append: () => table,
    on(ev, selector, fn) {
      if (typeof selector === 'function') return table; // not delegation
      delegated.push({selector: selector, ev: ev, fn: fn});
      return table;
    },
  };

  function cell(tag) {
    const el = {
      addClass: () => el,
      text: () => el,
      append: () => el,
      // getStat() puts the cell's markup in with .html(); the buttons in it
      // become elements of the page.
      html(h) {
        (String(h).match(/id="([^"]+)"/g) || []).forEach((m) => makeButton(m.slice(4, -1)));
        return el;
      },
    };
    return el;
  }

  const $j = function(sel) {
    const tag = typeof sel === 'string' && sel.match(/^<(\w+)/);
    if (tag) return cell(tag[1]);
    if (typeof sel === 'string' && sel.indexOf('#eventStatsTable') === 0) return table;
    if (typeof sel === 'string' && sel.charAt(0) === '#') {
      const id = sel.slice(1);
      if (elements[id]) return elements[id];
      // Nothing in the page for this id
      return makeEmpty();
    }
    return makeChainable();
  };
  $j.each = (obj, fn) => Object.keys(obj).forEach((k) => fn(k, obj[k]));
  $j.getJSON = () => {
    const p = {done: () => p, fail: () => p, always: () => p};
    return p;
  };

  const globals = {
    $j: $j,
    $: makeChainable(),
    console: {log() {}, warn() {}, error() {}, debug() {}},
    document: {
      referrer: '',
      getElementById: () => null,
      querySelector: () => null,
      querySelectorAll: () => [],
      addEventListener: () => {},
      createElement: () => ({}),
    },
    setTimeout: () => 0, clearTimeout: () => 0, setInterval: () => 0, clearInterval: () => 0,
    getCookie: () => null, setCookie: () => {},
    eventData: eventData,
    eventDataStrings: {Name: 'Name', MonitorName: 'Monitor'},
    canView: {Monitors: true},
    canEdit: {Events: true},
    translate: {},
    isEmpty: (o) => !o || Object.keys(o).length === 0,
    canStreamNative: false,
    zmPanZoom: {init: () => {}},
    ZM_EventZmsHints: {create: () => ({})},
    ZM_OPT_USE_GEOLOCATION: '0',
  };
  const sandbox = new Proxy(globals, {
    has: () => true,
    get: (target, prop) => {
      if (prop === Symbol.unscopables) return undefined;
      if (prop in target) return target[prop];
      return globalThis[prop];
    },
    set: (target, prop, value) => {
      target[prop] = value;
      return true;
    },
  });
  globals.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(escapeSrc + '\n' + bindSrc, sandbox, {filename: 'skin.js'});
  vm.runInContext(src, sandbox, {filename: 'event.js'});

  // Stand-ins for the parts of initPage() that need the stream, the player and
  // the page layout, so that its button bindings can run.
  Object.assign(sandbox, {
    location: {assign: (url) => navigations.push(url)},
    isMobile: () => false,
    changeScale: () => {},
    changeStreamQuality: () => {},
    progressBarNav: () => {},
    nearEventsQuery: () => {},
    initialAlarmCues: () => {},
    streamPlay: () => {},
    streamPause: () => {},
    getTracksFromStream: () => {},
  });
  sandbox.window.location = sandbox.location;

  function click(selector) {
    const id = selector.slice(1);
    const el = elements[id];
    assert.ok(el, 'no ' + selector + ' button in the table');
    const evt = {preventDefault() {}};
    // Delegated handlers on the table fire for a click inside it, then the
    // handlers bound to the element itself.
    delegated
        .filter((d) => d.ev === 'click' && d.selector === selector)
        .forEach((d) => d.fn.call(el, evt));
    (el.handlers.click || []).forEach((fn) => fn(evt));
    return navigations.splice(0);
  }

  return {sandbox, click, initPage: sandbox.initPage, getStat: sandbox.getStat};
}

const EVENT = {Id: 10, MonitorId: '1', MonitorName: 'Front'};

// Load the page as the browser does: initPage() builds and binds the table.
function openPage(eventData) {
  const page = load(eventData || EVENT);
  page.initPage();
  return page;
}

// getEventResponse(): eventData = respObj.event; getStat();
function moveTo(page, eventData) {
  page.sandbox.eventData = eventData;
  page.getStat();
}

console.log('event details table buttons');
test('Live, Edit and All Events go where they say', () => {
  const page = openPage();
  assert.deepStrictEqual(page.click('#eventLiveBtn'), ['?view=watch&mid=1']);
  assert.deepStrictEqual(page.click('#eventEditBtn'), ['?view=monitor&mid=1']);
  const all = page.click('#eventAllEvents');
  assert.strictEqual(all.length, 1, 'All Events did nothing: ' + all);
  assert.ok(all[0].indexOf('?view=events&page=1') === 0, all[0]);
  assert.ok(all[0].indexOf('%5Bval%5D=1') > 0, 'not filtered to the monitor: ' + all[0]);
});

test('the buttons still work after the view moves to the next event', () => {
  const page = openPage();
  moveTo(page, {Id: 11, MonitorId: '1', MonitorName: 'Front'});
  assert.deepStrictEqual(page.click('#eventLiveBtn'), ['?view=watch&mid=1']);
  assert.deepStrictEqual(page.click('#eventEditBtn'), ['?view=monitor&mid=1']);
  const all = page.click('#eventAllEvents');
  assert.strictEqual(all.length, 1, 'All Events did nothing: ' + all);
});

test('a rebuilt button runs its handler once', () => {
  const page = openPage();
  moveTo(page, {Id: 11, MonitorId: '1', MonitorName: 'Front'});
  moveTo(page, {Id: 12, MonitorId: '1', MonitorName: 'Front'});
  assert.deepStrictEqual(page.click('#eventLiveBtn'), ['?view=watch&mid=1']);
});

test('the buttons follow the monitor of the event shown', () => {
  const page = openPage();
  // zms moved on to another monitor's event (refs #4186)
  moveTo(page, {Id: 11, MonitorId: '2', MonitorName: 'Back'});
  assert.deepStrictEqual(page.click('#eventLiveBtn'), ['?view=watch&mid=2']);
});

console.log(`\n${passed} passed, ${failed} failed`);
process.exit(failed ? 1 : 0);
