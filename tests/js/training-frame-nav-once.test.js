'use strict';

// The Alarm and Snapshot buttons of the training frame bar, and Enter in the
// frame number box, were each wired twice (once by training.js, once by the
// view's inline script), so one click changed frame twice and cancelling the
// unsaved changes prompt asked again.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.join(__dirname, '../../web/skins/classic/views/js/');
const trainingSrc = fs.readFileSync(process.env.TRAINING_JS || path.join(root, 'training.js'), 'utf8');
const phpSrc = fs.readFileSync(process.env.TRAINING_JS_PHP || path.join(root, 'training.js.php'), 'utf8');
const block = phpSrc.match(/<\?php if \(\$Event->Id\(\)\) \{ \?>([\s\S]*)<\?php \} \?>/);
assert.ok(block, 'training.js.php page script not found');

// A small stand-in for the parts of jQuery the two scripts use.
function matches(el, sel) {
  const re = /(\.[\w-]+)|(\[([\w-]+)(?:="([^"]*)")?\])|(:not\((\.[\w-]+)\))/g;
  let m;
  let ok = true;
  let any = false;
  while ((m = re.exec(sel))) {
    any = true;
    if (m[1]) ok = ok && el.classes.has(m[1].slice(1));
    else if (m[2]) ok = ok && (m[4] === undefined ? m[3] in el.attrs : el.attrs[m[3]] === m[4]);
    else ok = ok && !el.classes.has(m[6].slice(1));
  }
  return any && ok;
}
function makeEl(id, classes, attrs, parent) {
  const el = {id, classes: new Set(classes), attrs: attrs || {}, handlers: [], parent: parent || null, children: [], value: ''};
  if (parent) parent.children.push(el);
  return el;
}
function descendants(el) {
  return el.children.reduce((a, c) => a.concat([c], descendants(c)), []);
}
function dispatch(target, type, evt) {
  evt.type = type;
  for (let node = target; node; node = node.parent) {
    for (const h of node.handlers.slice()) {
      if (h.type !== type) continue;
      if (!h.sel) {
        h.fn.call(node, evt);
        continue;
      }
      for (let cur = target; cur && cur !== node; cur = cur.parent) {
        if (matches(cur, h.sel)) {
          h.fn.call(cur, evt);
          break;
        }
      }
    }
  }
}
const base = (t) => t.split('.')[0];
function wrap(els) {
  const w = {
    els,
    on(types, a, b) {
      els.forEach((el) => el.handlers.push({type: base(types), full: types, sel: b ? a : null, fn: b || a}));
      return w;
    },
    off(types) {
      els.forEach((el) => {
        el.handlers = el.handlers.filter((h) => h.full !== types);
      });
      return w;
    },
    find: (sel) => wrap(els.reduce((a, el) => a.concat(descendants(el).filter((d) => matches(d, sel))), [])),
    each(fn) {
      els.forEach((el) => fn.call(el));
      return w;
    },
    show: () => w,
    hide: () => w,
    text: () => w,
    prop: () => w,
    removeClass(c) {
      els.forEach((el) => el.classes.delete(c));
      return w;
    },
    addClass(c) {
      els.forEach((el) => el.classes.add(c));
      return w;
    },
    hasClass: (c) => els.length > 0 && els[0].classes.has(c),
    attr: (n) => (els.length ? els[0].attrs[n] : undefined),
    data(n) {
      const v = els[0].attrs['data-' + n];
      return /^\d+$/.test(v) ? Number(v) : v;
    },
    val: () => els[0].value,
    click() {
      els.forEach((el) => dispatch(el, 'click', {preventDefault() {}, stopPropagation() {}}));
      return w;
    },
    ready(fn) {
      fn();
      return w;
    },
    length: els.length,
  };
  return w;
}

const container = makeEl('annotationFrameSelector', ['annotation-frame-selector']);
const byId = {annotationFrameSelector: container};
const buttons = {};
['skip-back', 'prev', 'alarm', 'snapshot', 'next', 'skip-forward'].forEach((f) => {
  buttons[f] = makeEl(null, ['btn'].concat(f === 'alarm' || f === 'snapshot' ? ['frame-btn'] : []), {'data-frame': f}, container);
});
byId.annotationFrameInput = makeEl('annotationFrameInput', ['frame-input'], {}, container);
byId.annotationGoToFrame = makeEl('annotationGoToFrame', [], {}, container);
makeEl(null, ['frame-total'], {}, container);

function $j(x) {
  if (x === sandbox.document) return wrap([{handlers: [], children: [], classes: new Set(), attrs: {}}]);
  if (typeof x === 'string') {
    const id = x.replace(/^#/, '');
    if (!byId[id]) byId[id] = makeEl(id, [], {});
    return wrap([byId[id]]);
  }
  return wrap([x]);
}

const switched = [];
const sandbox = {
  $j, document: {}, thisUrl: '?', logAjaxFail() {}, URLSearchParams,
  window: {location: {search: '', assign() {}}}, setTimeout() {},
  confirm: () => true, eventData: {Id: 5}, trainingTranslations: {},
};
vm.createContext(sandbox);
vm.runInContext(trainingSrc, sandbox);
const proto = vm.runInContext('AnnotationEditor.prototype', sandbox);
proto.init = proto.open = proto.browseTrainingData = function() {};
proto.switchFrame = function(f) {
  switched.push(f);
};
let editor = null;
const Real = vm.runInContext('AnnotationEditor', sandbox);
sandbox.AnnotationEditor = function(o) {
  editor = new Real(o);
  return editor;
};
vm.runInContext(block[1], sandbox);
assert.ok(editor, 'the page script created the editor');
editor.totalFrames = 10;
editor.currentFrameId = '3';
editor._updateFrameSelector(['alarm', 'snapshot']);

function click(name) {
  switched.length = 0;
  wrap([buttons[name]]).click();
  return switched.slice();
}
assert.deepStrictEqual(click('alarm'), ['alarm'], 'Alarm switches frame once');
assert.deepStrictEqual(click('snapshot'), ['snapshot'], 'Snapshot switches frame once');
assert.deepStrictEqual(click('next'), ['4'], 'Next still works');
assert.deepStrictEqual(click('prev'), ['2'], 'Prev still works');

switched.length = 0;
byId.annotationFrameInput.value = '7';
dispatch(byId.annotationFrameInput, 'keydown', {key: 'Enter', preventDefault() {}, stopPropagation() {}});
assert.deepStrictEqual(switched, ['7'], 'Enter in the frame box switches frame once');

switched.length = 0;
wrap([byId.annotationGoToFrame]).click();
assert.deepStrictEqual(switched, ['7'], 'the Go button still works');
console.log('ok - frame bar controls switch frame once per action');
