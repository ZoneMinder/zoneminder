'use strict';

// The monitor form checks the Options field with a port of ffmpeg's
// av_dict_parse_string(), the parser zmc hands it to. The ok/fail expectations
// below were taken from libavutil 61.1.102 run on the same strings with "=" and
// ",\r\n", the separators zmc uses.

const assert = require('assert');
const path = require('path');
const ZM = require(path.join(__dirname,
    '../../web/skins/classic/views/js/monitor-ffmpeg-options.js'));

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

function count(text) {
  const result = ZM.parse(text);
  return result.error === null ? result.pairs.length : 'FAIL';
}

console.log('parse matches av_dict_parse_string');
const cases = [
  ['', 0],
  ['-tls_verify 0', 'FAIL'],
  ['tls_verify=0', 1],
  ['a=1,b=2', 2],
  ['a=1,,b=2', 2],
  ['a=1\n\nb=2', 2],
  ['a=1\n', 1],
  ['a=1,', 1],
  ['a=1,\n', 'FAIL'],
  ['a=1\r\nb=2', 2],
  ['a=', 'FAIL'],
  ['=1', 'FAIL'],
  ['a=\'x,y\',b=2', 2],
  ['a=x\\,y', 1],
  [' a = 1 ', 1],
  ['a=1, b=2', 2],
  ['-tls_verify=0', 1],
];
for (const [text, expected] of cases) {
  test(JSON.stringify(text) + ' -> ' + expected, () => {
    assert.strictEqual(count(text), expected);
  });
}

test('quotes and escapes are removed from values', () => {
  assert.deepStrictEqual(ZM.parse('a=\'x,y\',b=x\\,y').pairs,
      [{key: 'a', value: 'x,y'}, {key: 'b', value: 'x,y'}]);
});
test('whitespace around keys and values is dropped', () => {
  assert.deepStrictEqual(ZM.parse(' a = 1 ').pairs, [{key: 'a', value: '1'}]);
});
test('release-1.38 separators: a newline does not end a pair', () => {
  assert.deepStrictEqual(ZM.parse('a=1\nb=2', ',').pairs, [{key: 'a', value: '1\nb=2'}]);
});

console.log('badEntries');
test('valid options are not reported', () => {
  assert.deepStrictEqual(ZM.badEntries('tls_verify=0,rtsp_transport=tcp\nstimeout=5000000'), []);
});
test('an empty field is fine', () => {
  assert.deepStrictEqual(ZM.badEntries(''), []);
});
test('command line syntax is reported', () => {
  assert.deepStrictEqual(ZM.badEntries('-tls_verify 0'), ['-tls_verify 0']);
});
test('a leading dash is reported even when it parses', () => {
  assert.deepStrictEqual(ZM.badEntries('-tls_verify=0'), ['-tls_verify=0']);
});
test('the entry ffmpeg stops at is reported, not the ones before it', () => {
  assert.deepStrictEqual(ZM.badEntries('rtsp_transport=tcp,tls_verify 0'), ['tls_verify 0']);
});
test('a flag swallowed into the next key is reported', () => {
  // ffmpeg parses this, with the key "tls_verify 0,b".
  assert.deepStrictEqual(ZM.badEntries('rtsp_transport=tcp,tls_verify 0,b=2'), ['tls_verify 0,b=2']);
});
test('a value may contain spaces and quoted separators', () => {
  assert.deepStrictEqual(ZM.badEntries('user_agent=Mozilla 5.0,headers=\'a: b,c\''), []);
});
test('a key with no value is reported', () => {
  assert.deepStrictEqual(ZM.badEntries('a=1\nb='), ['b=']);
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);
