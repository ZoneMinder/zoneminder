'use strict';

// Options > Menu: an entry with no icon of its own shows its default icon
// (Console: dashboard). Switching the icon type away and back to Material
// used to blank the placeholder, so the preview fell back to the generic
// "menu" icon although saving keeps the entry's default.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/options.js'), 'utf8');

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

// One server rendered row from views/_options_menu.php for a built-in entry,
// without a custom icon unless customIcon is given.
function load(defaultIcon, customIcon) {
  const select = {kind: 'select', value: 'material', data: {'item-id': '1'}};
  const input = {
    kind: 'input', value: customIcon || '',
    attrs: {'placeholder': customIcon || defaultIcon},
    data: defaultIcon === undefined ? {} : {'default-icon': defaultIcon},
  };
  const preview = {kind: 'preview', html: '<i class="material-icons">' + defaultIcon + '</i>'};
  const row = {kind: 'row'};
  const handlers = {};

  function wrap(el) {
    const w = {
      length: el ? 1 : 0,
      val(v) {
        if (v === undefined) return el.value;
        el.value = v; return w;
      },
      data: (k) => el.data[k],
      attr(k, v) {
        if (v === undefined) return el.attrs[k];
        el.attrs[k] = v; return w;
      },
      html(v) {
        el.html = v; return w;
      },
      css: () => w, show: () => w, hide: () => w, change: () => w,
      prop: () => w, click: () => w, sortable: () => w,
      bootstrapTable: () => w,
      closest: () => wrap(row),
      find(sel) {
        if (el === row) {
          if (sel === '.menuIconPreview') return wrap(preview);
          if (sel === '.iconTypeSelect') return wrap(select);
          if (sel === '.iconNameInput') return wrap(input);
        }
        return wrap(null);
      },
      on(ev, sel, fn) {
        handlers[ev + ' ' + sel] = fn; return w;
      },
    };
    return w;
  }
  const $j = function(sel) {
    if (sel === select) return wrap(select);
    if (sel === '#menuItemsBody') return wrap(row);
    if (sel === '#iconName-1') return wrap(input);
    if (sel === ctx.document) return {ready() {}};
    return wrap(null);
  };
  $j.getJSON = () => ({done: () => ({fail() {}})});
  const ctx = {
    $j, canEdit: {System: true}, icons: {}, console, document: {},
    escapeHTML: (s) => String(s),
  };
  vm.createContext(ctx);
  vm.runInContext(src, ctx);
  ctx.initPage();
  return {
    choose(type) {
      select.value = type;
      handlers['change .iconTypeSelect'].call(select);
    },
    type(name) {
      input.value = name;
      handlers['input .iconNameInput'].call(input);
    },
    preview, input,
  };
}

test('back to Material previews the entry\'s default icon, not "menu"', () => {
  const page = load('dashboard');
  page.choose('fontawesome');
  page.choose('material');
  assert.strictEqual(page.preview.html, '<i class="material-icons">dashboard</i>');
  assert.strictEqual(page.input.attrs.placeholder, 'dashboard');
});

test('via Image and None as well', () => {
  const page = load('event');
  page.choose('image');
  page.choose('none');
  page.choose('material');
  assert.strictEqual(page.preview.html, '<i class="material-icons">event</i>');
});

test('a row without a default (a new entry) still falls back to "menu"', () => {
  const page = load(undefined);
  page.choose('fontawesome');
  page.choose('material');
  assert.strictEqual(page.preview.html, '<i class="material-icons">menu</i>');
});

// An empty name saves as NULL and MenuItem::effectiveIconType() then renders
// the default material icon whatever type was chosen (except None).
test('Font Awesome with no name previews the default icon the navbar shows', () => {
  const page = load('dashboard');
  page.choose('fontawesome');
  assert.strictEqual(page.preview.html, '<i class="material-icons">dashboard</i>');
});

test('Image with no path previews the default icon the navbar shows', () => {
  const page = load('event');
  page.choose('image');
  assert.strictEqual(page.preview.html, '<i class="material-icons">event</i>');
});

test('a Font Awesome name or image path still previews as typed', () => {
  const page = load('dashboard');
  page.choose('fontawesome');
  page.type('fa-home');
  assert.strictEqual(page.preview.html, '<i class="fa fa-home"></i>');
  page.choose('image');
  page.type('graphics/menu/x.png');
  assert.ok(page.preview.html.startsWith('<img src="graphics/menu/x.png"'), page.preview.html);
  page.choose('none');
  assert.strictEqual(page.preview.html, '');
});

test('clearing a custom material icon previews the default, not the old icon', () => {
  const page = load('dashboard', 'star');
  page.type('');
  assert.strictEqual(page.preview.html, '<i class="material-icons">dashboard</i>');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
