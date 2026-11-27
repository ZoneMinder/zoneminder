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

// What user.js.php emits for a French user.
const permissionStrings = {None: 'Aucun', View: 'Voir', Edit: 'Editer', Create: 'Créer'};

function cellAfterChange(radios, monitorsLevel) {
  const cells = {};
  const sandbox = {
    window: {addEventListener: () => {}},
    document: {getElementById: () => ({})},
    role: null,
    groups: [],
    monitors: [{id: 1, name: 'Front'}],
    permissionStrings: permissionStrings,
    $j: (sel) => ({
      val: () => sel.indexOf('#user') === 0 ? monitorsLevel : radios[sel.replace(/^input\[name="|\\|"\]:checked$/g, '')],
      html: (v) => {
        cells[sel] = v;
      },
    }),
    console: {log() {}, error() {}},
  };
  vm.createContext(sandbox);
  vm.runInContext(src + ';this.updateEffectivePermissions = updateEffectivePermissions;', sandbox);
  sandbox.updateEffectivePermissions();
  return cells['#effective_permission1'];
}

test('a monitor permission is shown translated', () => {
  assert.strictEqual(cellAfterChange({'monitor_permission[1]': 'View'}, 'None'), 'Voir');
});

test('an inherited Monitors level is shown translated', () => {
  assert.strictEqual(cellAfterChange({'monitor_permission[1]': 'Inherit'}, 'Create'), 'Créer');
});

console.log(passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
