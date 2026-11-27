'use strict';

// The snapshots post-body.bs.table handler used to find the Thumbnail column
// by comparing the <th> text to the English word 'Thumbnail', which broke on
// translated pages. It must locate the column via th[data-field="Thumbnail"].
// The header cells here are translated ('Miniatura'), so the old text filter
// finds nothing (index -1 => nth-child(0) => no cells styled) and the test
// fails, while the data-field selector still finds the column.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] || process.env.ZM_TEST_SNAPSHOTS_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/snapshots.js');
const src = fs.readFileSync(file, 'utf8');

// --- a minimal selector-aware jQuery / DOM stub -----------------------------

function element(tag, text, dataField, id) {
  return {
    tag: tag, text: text || '', dataField: dataField, id: id,
    classes: [], children: [], parent: null, __isElement: true,
    addEventListener() {},
  };
}

function append(parent, child) {
  child.parent = parent;
  parent.children.push(child);
  return child;
}

function descendants(root, out) {
  root.children.forEach((child) => {
    out.push(child);
    descendants(child, out);
  });
  return out;
}

function matchesToken(el, token) {
  let m = token.match(/^(\w+)\[data-field="([^"]*)"\]$/);
  if (m) return el.tag === m[1] && el.dataField === m[2];
  m = token.match(/^(\w+):nth-child\((\d+)\)$/);
  if (m) {
    return el.tag === m[1] &&
        el.parent.children.indexOf(el) + 1 === parseInt(m[2], 10);
  }
  return el.tag === token;
}

const registry = [];
function register(el) {
  registry.push(el);
  el.children.forEach(register);
}

class FakeQuery {
  constructor(elements) {
    this.elements = elements;
  }
  ready() {}
  show() {}
  prop() {}
  remove() {
    this.elements.forEach((el) => {
      el.parent.children.splice(el.parent.children.indexOf(el), 1);
    });
  }
  text() {
    return this.elements.length ? this.elements[0].text : '';
  }
  filter(fn) {
    return new FakeQuery(this.elements.filter((el) => fn.call(el)));
  }
  index() {
    if (!this.elements.length) return -1;
    return this.elements[0].parent.children.indexOf(this.elements[0]);
  }
  addClass(cls) {
    this.elements.forEach((el) => el.classes.push(cls));
    return this;
  }
  find(selector) {
    let set = this.elements.slice();
    selector.split(' ').filter(Boolean).forEach((token) => {
      const found = [];
      set.forEach((root) => descendants(root, found));
      set = found.filter((el) => matchesToken(el, token));
    });
    return new FakeQuery(set);
  }
  on(events, fn) {
    this.elements.forEach((el) => {
      el.handlers = el.handlers || {};
      events.split(' ').forEach((ev) => {
        (el.handlers[ev] = el.handlers[ev] || []).push(fn);
      });
    });
    return this;
  }
  bootstrapTable(cmd) {
    return cmd === 'getSelections' ? [] : this;
  }
}

function select(selector) {
  let set = [];
  selector.split(' ').filter(Boolean).forEach((token) => {
    if (token.startsWith('#')) {
      const el = registry.find((candidate) => candidate.id === token.slice(1));
      set = el ? [el] : [];
    } else {
      const found = [];
      set.forEach((root) => descendants(root, found));
      set = found.filter((el) => matchesToken(el, token));
    }
  });
  return new FakeQuery(set);
}

const $j = function(arg) {
  if (arg && arg.__isElement) return new FakeQuery([arg]);
  if (typeof arg === 'string') return select(arg);
  return new FakeQuery([]);
};

// --- the stub page -----------------------------------------------------------

const table = element('table', '', null, 'snapshotTable');
const headRow = append(append(table, element('thead')), element('tr'));
const headerCells = [
  element('th', 'Id', 'Id'),
  element('th', 'Nome', 'Name'),
  element('th', 'Descrizione', 'Description'),
  // translated: the English word 'Thumbnail' appears nowhere in the header
  element('th', 'Miniatura', 'Thumbnail'),
  element('th', 'Creato il', 'CreatedOn'),
];
headerCells.forEach((th) => append(headRow, th));

const bodyRows = [];
for (let i = 0; i < 2; i++) {
  const tr = append(append(table, element('tbody')), element('tr'));
  for (let c = 0; c < headerCells.length; c++) {
    append(tr, element('td', 'cell ' + i + ',' + c, headerCells[c].dataField));
  }
  bodyRows.push(tr);
}
register(table);
['backBtn', 'refreshBtn', 'deleteBtn', 'exportBtn'].forEach((id) => {
  register(element('button', '', null, id));
});

const sandbox = {
  $j: $j,
  document: {
    referrer: '',
    getElementById: (id) => registry.find((el) => el.id === id),
  },
  window: {},
  thisUrl: 'index.php',
  WEB_LIST_THUMBS: true,
  icons: {},
  canEdit: {Snapshots: true},
  console: console,
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);
sandbox.initPage();

// Fire the post-body.bs.table handler that styles the thumbnail column
(table.handlers['post-body.bs.table'] || []).forEach((fn) => fn());

const thumbNdx = headerCells.findIndex((th) => th.dataField === 'Thumbnail');
bodyRows.forEach((tr, row) => {
  const thumbTd = tr.children[thumbNdx];
  assert.ok(
      thumbTd.classes.indexOf('colThumbnail') !== -1,
      'the thumbnail column (th[data-field="Thumbnail"], translated as ' +
      '"' + thumbTd.text + '") must get the colThumbnail class on its td ' +
      'cells, row ' + row);
  tr.children.forEach((td, ndx) => {
    if (ndx === thumbNdx) return;
    assert.ok(
        td.classes.indexOf('colThumbnail') === -1,
        'the colThumbnail class must not leak outside the thumbnail column ' +
        '(row ' + row + ', cell ' + ndx + ')');
  });
});

console.log('ok - post-body.bs.table styles the translated thumbnail column via th[data-field="Thumbnail"]');
