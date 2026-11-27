'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/js/export.js'), 'utf8');

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

// Just enough of a DOM for the download links: siblings in one parent.
class Node {
  constructor(name) {
    this.nodeName = name.toUpperCase();
    this.id = '';
    this.parentNode = null;
    this.childNodes = [];
  }
  get previousSibling() {
    const s = this.parentNode.childNodes;
    return s[s.indexOf(this) - 1] || null;
  }
  get nextSibling() {
    const s = this.parentNode.childNodes;
    return s[s.indexOf(this) + 1] || null;
  }
  insertBefore(node, ref) {
    const s = this.childNodes;
    node.parentNode = this;
    s.splice(ref ? s.indexOf(ref) : s.length, 0, node);
  }
  before(node) {
    this.parentNode.insertBefore(node, this);
  }
  remove() {
    const s = this.parentNode.childNodes;
    s.splice(s.indexOf(this), 1);
  }
  cloneNode() {
    return new Node(this.nodeName);
  }
}

function load() {
  const h3 = new Node('h3');
  const link = new Node('a');
  link.id = 'downloadLink';
  h3.insertBefore(link, null);
  const proxy = new Proxy(function() {}, {
    get: (target, prop) => (prop === 'then' ? undefined : proxy),
    apply: () => proxy,
  });
  const sandbox = {
    console: {log() {}},
    $j: proxy,
    thisUrl: 'index.php',
    exportSucceededString: 'Export Succeeded',
    URLSearchParams,
    setTimeout() {},
    setInterval() {},
    clearInterval() {},
    document: {
      getElementById: (id) => h3.childNodes.find((n) => n.id === id) || null,
      createElement: (tag) => new Node(tag),
    },
  };
  vm.createContext(sandbox);
  vm.runInContext(src, sandbox);
  return {sandbox, h3};
}

function files(sandbox, n) {
  // An array from the page's realm, so export.js's instanceof Array holds.
  const exportFile = vm.runInContext('[]', sandbox);
  for (let i = 0; i < n; i++) exportFile.push('?view=archive&file=f' + i + '.mp4');
  return {result: 'Ok', exportFile};
}

function count(h3, name) {
  return h3.childNodes.filter((n) => n.nodeName === name).length;
}

test('a new download clears the line breaks of the previous links', () => {
  const {sandbox, h3} = load();
  sandbox.exportEvent();
  sandbox.exportResponse(files(sandbox, 3));
  assert.strictEqual(count(h3, 'A'), 3);
  assert.strictEqual(count(h3, 'BR'), 2);
  sandbox.exportEvent();
  assert.strictEqual(count(h3, 'A'), 1);
  assert.strictEqual(count(h3, 'BR'), 0);
});

test('repeated downloads do not pile up blank lines', () => {
  const {sandbox, h3} = load();
  for (let i = 0; i < 3; i++) {
    sandbox.exportEvent();
    sandbox.exportResponse(files(sandbox, 3));
  }
  assert.strictEqual(count(h3, 'BR'), 2);
  sandbox.exportEvent();
  sandbox.exportResponse(files(sandbox, 1));
  assert.strictEqual(count(h3, 'BR'), 0);
});

console.log('');
console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
