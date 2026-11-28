'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const file = process.argv[2] ||
  path.join(__dirname, '../../web/skins/classic/views/js/console.js');
const src = fs.readFileSync(file, 'utf8');

const stub = {
  bootstrapTable() {
    return stub;
  },
  on() {
    return stub;
  },
  ready() {},
  show() {},
  sortable() {},
  find() {
    return {each() {}};
  },
  attr() {
    return stub;
  },
  parent() {
    return stub;
  },
  length: 0,
};

function makeElement(tag) {
  const name = tag.replace(/[<>]/g, '');
  const classes = [];
  const attrs = {};
  let content = '';
  return {
    addClass(className) {
      classes.push(className);
      return this;
    },
    attr(key, value) {
      attrs[key] = value;
      return this;
    },
    text(value) {
      if (value === undefined) return content;
      content = String(value);
      return this;
    },
    prop(propName) {
      if (propName !== 'outerHTML') return undefined;
      const classAttr = classes.length ? ' class="' + classes.join(' ') + '"' : '';
      const attrHtml = Object.keys(attrs)
          .map((key) => ' ' + key + '="' + attrs[key] + '"').join('');
      return '<' + name + classAttr + attrHtml + '>' + content + '</' + name + '>';
    },
  };
}

const $j = (arg) => (typeof arg === 'string' ? makeElement(arg) : stub);
$j.each = (rows, fn) => {
  rows.forEach((row, index) => fn(index, row));
};

function row(overrides) {
  const base = {
    Id: 1,
    Name: 'Camera',
    Deleted: false,
    Type: 'Ffmpeg',
    Status: 'Running',
    Capturing: 'Always',
    CaptureFPS: '5.00',
    AnalysisFPS: '5.00',
    Analysing: 'None',
    ONVIF_Event_Listener: 0,
    Recording: 'None',
    UpdatedOn: '',
    Source: 'rtsp://example',
    Width: 640,
    Height: 480,
    ZoneCount: 1,
    Groups: '',
    TotalEvents: 0,
    TotalEventDiskSpace: '0 B',
    HourEvents: 0,
    HourEventDiskSpace: '0 B',
    DayEvents: 0,
    DayEventDiskSpace: '0 B',
    WeekEvents: 0,
    WeekEventDiskSpace: '0 B',
    MonthEvents: 0,
    MonthEventDiskSpace: '0 B',
    ArchivedEvents: 0,
    ArchivedEventDiskSpace: '0 B',
  };
  return Object.assign(base, overrides);
}

function ago(ms) {
  return new Date(Date.now() - ms).toISOString();
}

const sandbox = {
  $j,
  document: {forms: {}},
  window: {},
  getCookie: () => 'x',
  consoleRefreshTimeout: 0,
  showDonatePopup: false,
  icons: {},
  setInterval() {},
  console,
  canView: {Stream: true, Events: false, Monitors: false},
  canEdit: {Monitors: false},
  server_utc_offset: -new Date().getTimezoneOffset() * 60,
};
vm.createContext(sandbox);
vm.runInContext(src, sandbox);

function runRow(updatedOn) {
  return sandbox.processRows([row({UpdatedOn: updatedOn})])[0];
}

const tenMinutes = runRow(ago(10 * 60 * 1000));
assert.ok(tenMinutes.Function.includes('Offline'), '10 minute old row is offline');
assert.ok(tenMinutes.Function.includes('10 min ago'),
    '10 minute old row shows the relative age: ' + tenMinutes.Function);
const title = tenMinutes.Function.match(/title="([^"]+)"/);
assert.ok(title, 'relative age has a title attribute: ' + tenMinutes.Function);
assert.ok(title[1].length > 0, 'title holds the full date/time');

const threeHours = runRow(ago(3 * 60 * 60 * 1000));
assert.ok(threeHours.Function.includes('Offline'), '3 hour old row is offline');
assert.ok(threeHours.Function.includes('3 h ago'),
    '3 hour old row shows hours: ' + threeHours.Function);

const fresh = runRow(new Date().toISOString());
assert.ok(!fresh.Function.includes('Offline'), 'fresh row is not offline');
assert.ok(!fresh.Function.includes('ago'), 'fresh row has no age text');

const noUpdate = runRow('');
assert.ok(noUpdate.Function.includes('Offline'), 'row without UpdatedOn is offline');
assert.ok(!noUpdate.Function.includes('ago'),
    'row without UpdatedOn has no relative age: ' + noUpdate.Function);

console.log('ok console offline rows show the last update time');
