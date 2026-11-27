'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

// Labels in scripts such as Devanagari or Thai, or typed decomposed, carry
// combining marks next to their letters. The editor and the save handler must
// both accept them.
const root = path.join(__dirname, '../..');
const js = fs.readFileSync(path.join(root, 'web/skins/classic/views/js/training.js'), 'utf8');
const php = fs.readFileSync(path.join(root, 'web/ajax/training.php'), 'utf8');

const jsMatch = js.match(/if \(label && (\/\^.*?\/u)\.test\(label\)\)/);
assert.ok(jsMatch, 'label check not found in training.js');
const phpMatch = php.match(/preg_match\('(\/\^.*?\/u)', \$ann\['label'\]\)/);
assert.ok(phpMatch, 'label check not found in training.php');

const toRegExp = (s) => new RegExp(s.slice(1, s.lastIndexOf('/')), 'u');
for (const [where, re] of [['training.js', toRegExp(jsMatch[1])], ['training.php', toRegExp(phpMatch[1])]]) {
  for (const label of ['person', 'traffic_light', 'कुत्ता', 'สุนัข', 'café']) {
    assert.ok(re.test(label), where + ' rejects ' + label);
  }
  for (const label of ['a b', 'a/b', '../x', 'a.b']) {
    assert.ok(!re.test(label), where + ' accepts ' + label);
  }
}
console.log('  ok training labels accept letters with combining marks');
