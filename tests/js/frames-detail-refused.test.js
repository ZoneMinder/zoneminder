'use strict';

// Opening a frame's stats row on the frames list fetches ajax/stats.php. A
// refusal (no permission, missing frame id) comes back as HTTP 200 with
// result 'Error' and no html, and $detail.html(undefined) is jQuery's getter,
// so the row kept saying "Please wait" for ever.

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const src = fs.readFileSync(process.env.FRAMES_JS ||
    path.join(__dirname, '../../web/skins/classic/views/js/frames.js'), 'utf8');
const match = src.match(/function detailFormatter\(index, row, \$detail\) \{[\s\S]*?\n\}\n/);
assert.ok(match, 'detailFormatter not found in frames.js');

function open(reply) {
  const $detail = {
    content: '',
    html(v) {
      if (v !== undefined) this.content = v;
      return this;
    },
    text(v) {
      this.content = v; return this;
    },
    empty() {
      this.content = ''; return this;
    },
  };
  const $j = () => ({});
  $j.get = () => {
    const p = {
      done(cb) {
        cb(reply); return p;
      },
      fail() {
        return p;
      },
    };
    return p;
  };
  const sandbox = {$j, thisUrl: '?', logAjaxFail() {}};
  vm.createContext(sandbox);
  vm.runInContext(match[0], sandbox);
  sandbox.detailFormatter(0, {EventId: 1, FrameId: 0}, $detail);
  return $detail.content;
}

assert.strictEqual(open({result: 'Error', message: 'Frame Id Not Provided'}), 'Frame Id Not Provided',
    'a refusal replaces "Please wait" with the message');
assert.strictEqual(open({result: 'Ok', html: '<table></table>'}), '<table></table>', 'stats still load');
console.log('ok - frame stats row shows a refused request');
