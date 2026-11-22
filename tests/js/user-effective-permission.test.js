'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(
    path.join(__dirname, '../../web/skins/classic/views/js/user.js'), 'utf8');

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

function effective(role, monitorsLevel, monitor, groups, radios) {
  const sandbox = {
    window: {addEventListener: () => {}},
    role: role,
    groups: groups,
    monitors: [],
    // $j('input[name="monitor_permission[1]"]:checked').val() and the Monitors select
    $j: (sel) => ({
      val: () => sel.indexOf('#user') === 0 ? monitorsLevel : radios[sel.replace(/^input\[name="|\\|"\]:checked$/g, '')],
    }),
    console: console,
  };
  vm.createContext(sandbox);
  vm.runInContext(src + ';this.getEffectivePermission = getEffectivePermission;', sandbox);
  return sandbox.getEffectivePermission(monitor);
}

const role = {
  monitors: 'Edit',
  monitor_permissions: {2: 'None'},
  group_permissions: {7: 'View'},
};
const groups = [{id: 7, monitor_ids: [1]}];
const inherit = {'monitor_permission[1]': 'Inherit', 'monitor_permission[2]': 'Inherit',
  'monitor_permission[3]': 'Inherit', 'group_permission[7]': 'Inherit'};

test('no role falls back to the user Monitors level', () => {
  assert.strictEqual(effective(null, 'None', {id: 3}, groups, inherit), 'None');
});

test('role monitor permission applies', () => {
  assert.strictEqual(effective(role, 'None', {id: 2}, groups, inherit), 'None');
});

test('role group permission applies', () => {
  assert.strictEqual(effective(role, 'None', {id: 1}, groups, inherit), 'View');
});

test('role Monitors level applies when the user has none', () => {
  assert.strictEqual(effective(role, 'None', {id: 3}, groups, inherit), 'Edit');
});

test('user Monitors level wins when it is higher than the role', () => {
  assert.strictEqual(effective(role, 'Create', {id: 3}, groups, inherit), 'Create');
});

test('a user monitor permission wins over the role', () => {
  const radios = Object.assign({}, inherit, {'monitor_permission[2]': 'View'});
  assert.strictEqual(effective(role, 'None', {id: 2}, groups, radios), 'View');
});

// A monitor in two groups, View in one and Edit in the other: editableMonitor() lets any
// group's Edit through, so the preview must say Edit.
const twoGroups = [{id: 7, monitor_ids: [1]}, {id: 8, monitor_ids: [1]}];
test('user groups View and Edit on the same monitor show Edit', () => {
  const radios = Object.assign({}, inherit, {'group_permission[7]': 'View', 'group_permission[8]': 'Edit'});
  assert.strictEqual(effective(null, 'None', {id: 1}, twoGroups, radios), 'Edit');
});
test('role groups View and Edit on the same monitor show Edit', () => {
  const r = {monitors: 'None', monitor_permissions: {}, group_permissions: {7: 'View', 8: 'Edit'}};
  assert.strictEqual(effective(r, 'None', {id: 1}, twoGroups, inherit), 'Edit');
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
